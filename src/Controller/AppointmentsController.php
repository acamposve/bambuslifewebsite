<?php

namespace App\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\Config\Definition\Exception\Exception;

use App\Service\HubClient;
use App\Service\NgageClient;
use App\Controller\AuthController;
use App\Utils\Settings;

use MercadoPago;
use Symfony\Component\HttpKernel\Exception\HttpException;

class AppointmentsController extends AbstractController {

  private $hubClient;
  private $logger;
  private $authCtrl;

  public function __construct(HubClient $hubClient, NgageClient $ngageClient, LoggerInterface $logger, AuthController $authCtrl){
    $this->hubClient = $hubClient;
    $this->logger    = $logger;
    $this->authCtrl  = $authCtrl;
  }

  private function convertStatus($status){
    if ($status == 'BOOKED'){
      return 'Agendada';
    }else if ($status == 'CANCELLED'){
      return 'Cancelada';
    }else{
      return $status;
    }
  }

  public function appointments(Request $request) {
    $user = $this->authCtrl->getUserData($request);
    $this->logger->debug("AppointmentsController|appointments| get appointments...");
    $this->hubClient->setAccessToken($user['at']);
    $res = $this->hubClient->getAppointments();
    if (!$res['resolved']){
      $this->logger->error("AppointmentsController|appointments| Error requesting user data!!");
      return $this->render('user/appointments.html.twig', [
        'error_msg' => "Ocurrio un error"
      ]);
    }else{
      if ($res['status_code'] != 200 && $res['status_code'] != 0){
        $this->logger->error("AppointmentsController|appointments| Server returned status code ".$res['status_code']." requesting getAppointments!!");
        return $this->render('appointments/appointments.html.twig', [
          'error_msg' => "Ocurrio un error"
        ]);
      }else{

        \Moment\Moment::setLocale('es_ES');
        for($i=0; $i < count($res['data']['appointments']); $i++){
          $momentDate = new \Moment\Moment($res['data']['appointments'][$i]['startDate'], 'UTC');
          //$momentDate = new \Moment\Moment("2020-06-16T00:00:00.000Z", "UTC");
          $res['data']['appointments'][$i]['startDateNice']  = $momentDate->format('l j');
          $res['data']['appointments'][$i]['startDateNice2'] = $momentDate->format('F [de] Y');
          $res['data']['appointments'][$i]['startHourNice']  = Settings::convertMilitaryTime($res['data']['appointments'][$i]['startHour'], TRUE);
          $res['data']['appointments'][$i]['statusNice']     = $this->convertStatus($res['data']['appointments'][$i]['status']);
        }
        // print_r($res);
        // die();

        $info_msg = NULL;
        if ($request->query->get('ac') == '1'){
          $info_msg = "La reserva fue cancelada.";
        }

        return $this->render('appointments/appointments.html.twig', [
          'info_msg'     => $info_msg,
          'appointments' => $res['data']['appointments'], 
          'paginator'    => $res['data']['appointmentsCount']
        ]);
      }
    }
  }

  public function getSpecialities(Request $request) {
    $user = $this->authCtrl->getUserData($request);
    $this->logger->debug("AppointmentsController|getResources| get resources...");
    $this->hubClient->setAccessToken($user['at']);
    $res = $this->hubClient->getSpecialities();
    if (!$res['resolved']){
      $this->logger->error("AppointmentsController|getResources| Error requesting user data!!");
      return $this->render('user/s1-searchResources.html.twig', [
        'error_msg' => "Ocurrio un error"
      ]);
    }else{
      if ($res['status_code'] != 200 && $res['status_code'] != 0){
        $this->logger->error("AppointmentsController|getResources| Server returned status code ".$res['status_code']." requesting getResources!!");
        return $this->render('appointments/s1-searchResources.html.twig', [
          'error_msg' => "Ocurrio un error"
        ]);
      }else{
        return $this->render('appointments/s1-searchResources.html.twig', ['specialities'=>$res['data']]);
      }
    }
  }

  //
  // List Doctors
  //
  public function getResources(Request $request) {
    $bag = $request->request;
    $text = $bag->get('practitionerName');
    $categoryId = $bag->get('speciality');
    $term['text'] = $text;
    $term['categoryId'] = $categoryId;
    $user = $this->authCtrl->getUserData($request);
    if ($user == NULL){
      return $this->redirect('/login?r=/newappointment');
    }
    $this->logger->debug("AppointmentsController|getResources| get resources...");
    $this->hubClient->setAccessToken($user['at']);
    $res = $this->hubClient->getResources($term);
    if (!$res['resolved']){
      $this->logger->error("AppointmentsController|getResources| Error requesting user data!!");
      return $this->render('appointments/s2-selectResource.html.twig', [
        'error_msg' => "Ocurrio un error"
      ]);
    }else{
      if ($res['status_code'] != 200 && $res['status_code'] != 0){
        $this->logger->error("AppointmentsController|getResources| Server returned status code ".$res['status_code']." requesting getResources!!");
        return $this->render('appointments/s2-selectResource.html.twig', [
          'error_msg' => "Ocurrio un error"
        ]);
      }else{
        // print_r($res);
        // die();
        //
        // Store session
        //
        $newAppointment = array(
          'healthcareServiceId' => $categoryId
        );
        $this->get('session')->set('newAppointment', $newAppointment);

        $resources = $res['data'];
        return $this->render('appointments/s2-selectResource.html.twig', ['resources'=>$resources]);
      }
    }
  }
  
  //
  // List Doctor's available slots
  //
  public function getAvailableTime($practId, $practRoleId, $serviceId, $locationId, Request $request) {
    $user = $this->authCtrl->getUserData($request);
    if ($user == NULL){
      return $this->redirect('/login?r='.$request->getUri());
    }
    $this->logger->debug("AppointmentsController|getResources| get resources...");
    $this->hubClient->setAccessToken($user['at']);
    //
    // Check startdate and build navigation dates
    //
    $daysCount = 7;
    if ($request->query->has('sd')){
      $startDateTmp = $request->query->get('sd');
      $startDate    = substr($startDateTmp, 0, 4) . '-' . substr($startDateTmp, 4, 2) . '-' . substr($startDateTmp, 6, 2);
      $dtDate       = \DateTime::createFromFormat('Ymd', $startDateTmp);
      $nextDate     = $dtDate->add(new \DateInterval('P'.$daysCount.'D'))->format('Ymd');
      $dtDate->sub(new \DateInterval('P'.($daysCount+1).'D'));
      if ($dtDate < new \DateTime('now')){
        $prevDate = NULL;
      }else{
        $prevDate  = $dtDate->sub(new \DateInterval('P'.($daysCount-1).'D'))->format('Ymd');
      }
    }else{
      $startDate = date('Y-m-d');
      $dtDate    = new \DateTime('now');
      $nextDate  = $dtDate->add(new \DateInterval('P'.$daysCount.'D'))->format('Ymd');
      $dtDate->sub(new \DateInterval('P'.($daysCount+1).'D'));
      if ($dtDate < new \DateTime('now')){
        $prevDate = NULL;
      }else{
        $prevDate  = $dtDate->sub(new \DateInterval('P'.($daysCount-1).'D'))->format('Ymd');
      }
    }
    //
    // Update appointment
    //
    $newAppointment = $this->get('session')->get('newAppointment');
    if ($newAppointment == NULL){
      return $this->redirectToRoute('searchResources');
    }
    $newAppointment['practitionerId']     = $practId;
    $newAppointment['serviceId']          = $serviceId;
    $newAppointment['practitionerRoleId'] = $practRoleId;
    $newAppointment['locationId']         = $locationId;
    $newAppointment['startDate']          = $startDate;
    $this->get('session')->set('appointment', $newAppointment);
    //
    // Get availabilty for doctor 
    //
    $filters = array(
      'healthcareServiceId' => $serviceId,
      'locationId'          => $locationId,
      'practitionerId'      => $practId,
      'practitionerRoleId'  => $practRoleId,
      'startDate'           => $newAppointment['startDate'],
      'days'                => $daysCount,
      'showPassedSlots'     => FALSE
    );
    $res = $this->hubClient->getAvailableTime($filters);
    if (!$res['resolved']){
      $this->logger->error("AppointmentsController|getResources| Error requesting user data!!");
      return $this->render('appointments/s3-selectDate.html.twig', [
        'error_msg' => "Ocurrio un error"
      ]);
    }else{
      if ($res['status_code'] != 200 && $res['status_code'] != 0){
        $this->logger->error("AppointmentsController|getResources| Server returned status code ".$res['status_code']." requesting getResources!!");
        return $this->render('appointments/s3-selectDate.html.twig', [
          'error_msg' => "Ocurrio un error"
        ]);
      }else{
        // print_r($res);
        // die();
        // Store more data in session
        $newAppointment['serviceName'] = $res['data']['service']['name'];
        $newAppointment['practName']   = $res['data']['practitioner']['name'];
        $newAppointment['locName']     = $res['data']['location']['name'];
        $newAppointment['locAddress']  = $res['data']['location']['address'];
        $newAppointment['locPhone']    = $res['data']['location']['primaryPhone'];
        $this->get('session')->set('appointment', $newAppointment);
        // Adjust date for URL
        $res['data']['weekDate'] = $newAppointment['startDate'];
        if (array_key_exists('days', $res['data']['slots'])){
          for ($i = 0; $i < count($res['data']['slots']['days']); $i ++) {
            $res['data']['slots']['days'][$i]['dateAux'] = str_replace('-', '', $res['data']['slots']['days'][$i]['date']);
            //$res['data']['days'][$i]['dateAux'] = date('Y-m-d', strtotime($res['data']['days'][$i]['dateAux']));
          }
        }

        return $this->render('appointments/s3-selectDate.html.twig', [
          'calendar'           => $res['data'],
          'appointment'        => $newAppointment,
          'prevDate'           => $prevDate,
          'nextDate'           => $nextDate
        ]);
      }
    }
  }

  public function selectReason($date, $slotStart, $slotEnd, Request $request){
    $user = $this->authCtrl->getUserData($request);
    if ($user == NULL){
      return $this->redirect('/login?r='.$request->getUri());
    }

    \Moment\Moment::setLocale('es_ES');
    $appointment = $this->get('session')->get('appointment');

    //
    // Prepare dates
    //
    $fDate = substr($date, 0, 4) . '-' . substr($date, 4, 2) . '-' . substr($date, 6, 2);
    $cDate = $fDate . ' ' . Settings::convertMilitaryTime($slotStart, FALSE) . ':00';

    $mDate = new \Moment\Moment($cDate);

    //
    // Update session
    //
    $appointment['date']          = $date;
    $appointment['fDate']         = $fDate;
    $appointment['cDate']         = $cDate;
    $appointment['mDate']         = $mDate;
    $appointment['dateNice']      = $mDate->format('l j F [de] Y');
    $appointment['slotStart']     = $slotStart;
    $appointment['slotStartNice'] = Settings::convertMilitaryTime($slotStart, TRUE);
    $appointment['slotEnd']       = $slotEnd;
    $this->get('session')->set('appointment', $appointment);

    // print_r($appointment);
    // die();

    return $this->render('appointments/s4-selectReason.html.twig', ['appointment' => $appointment]);
  }

  public function preConfirmAppointment(Request $request){
    $user = $this->authCtrl->getUserData($request);
    if ($user == NULL){
      return $this->redirect('/login?r='.$request->getUri());
    }

    $bag = $request->request;
    $appointment = $this->get('session')->get('appointment');
    $appointment['reason'] = $bag->get('reason');
    if($bag->get('appointmentType') == 'video'){
      $appointment['isVirtual'] = true;
    }else{
      $appointment['isVirtual'] = false;  
    }
    $this->get('session')->set('appointment', $appointment);

    // print_r($appointment);
    // die();

    return $this->render('appointments/s5-confirmation.html.twig', [
      'appointment'=> $appointment
    ]);
  }

  public function confirmAppointment(Request $request){
    $user = $this->authCtrl->getUserData($request);
    if ($user == NULL){
      return $this->redirect('/login?r='.$request->getUri());
    }

    $appointment = $this->get('session')->get('appointment');
    // print_r($appointment);
    // die();

    //
    // Build appointment to send to hub server
    //
    $newApp = new \stdClass();
    $newApp->start = $appointment['date'];
    $newApp->end   = $appointment['date'];
    $newApp->slots = array();
      $slot          = new \stdClass();
      $slot->start   = $appointment['slotStart'];
      $slot->end     = $appointment['slotEnd'];
      array_push($newApp->slots, $slot);
    $newApp->participants = array();
      $resource             = new \stdClass();
      $resource->type       = "RESOURCE_ROLE";
      $resource->id         = $appointment['practitionerRoleId'];
      array_push($newApp->participants, $resource);
      $customer             = new \stdClass();
      $customer->type       = "CUSTOMER";
      $customer->id         = $user['patient']['id'];
      array_push($newApp->participants, $customer);
    $newApp->isVirtual       = $appointment['isVirtual'];
    $newApp->reason          = $appointment['reason'];
    $newApp->sendConfirmMail = TRUE;
    //
    // Send appointment request to server
    //
    $this->hubClient->setAccessToken($user['at']);
    $res = $this->hubClient->confirmAppointment($newApp);
    if (!$res['resolved']){
      $this->logger->error("AppointmentsController|getResources| Error requesting user data!!");
      $error_msg = 'Ocurrió un error al intentar confirmar la reserva.';
        return $this->render('appointments/s5-confirmation.html.twig', [
          'appointment' => $appointment,
          'error_msg'   => $error_msg
        ]);
    }else{
      if ($res['status_code'] != 200 && $res['status_code'] != 0){
        $this->logger->error("AppointmentsController|getResources| Server returned status code ".$res['status_code']." requesting getResources!!");
        $error_msg = 'Ocurrió un error al intentar confirmar la reserva.';
        return $this->render('appointments/s5-confirmation.html.twig', [
          'appointment' => $appointment,
          'error_msg'   => $error_msg
        ]);
      }else{
        // echo("<pre>");
        // print_r($res['data']);
        // echo("</pre>");
        // return $this->render('appointments/s6-final.html.twig');
        return $this->redirectToRoute('appointment', array(
          'c'     => '1',
          'appId' => $res['data']['id'])
        );
      }
    }
  }

  public function cancelAppointment(Request $request, $appId){
    $session = $this->get('session');
    $user = $this->authCtrl->getUserData($request);
    if ($user == NULL){
      return $this->redirect('/login?r='.$request->getUri());
    }
    $this->logger->debug("AppointmentsController|cancelAppointment | cancelling appointment id ".$appId."...");
    $this->hubClient->setAccessToken($user['at']);
    $res = $this->hubClient->cancelAppointment($appId);
    if (!$res['resolved']){
      $this->logger->error("AppointmentsController|cancelAppointment| Error cancelling appointment id ".$appId."!!");
      $session->getFlashBag()->add('error_msg', 'Ocurri&oacute; un error al cancelar la reserva.');
      return $this->redirectToRoute('appointment', array('appId'=>$appId));
    }else{
      if ($res['status_code'] != 200 && $res['status_code'] != 0){
        $this->logger->error("AppointmentsController|cancelAppointment| Server returned status code ".$res['status_code']." requesting getAppointments!!");
        $session->getFlashBag()->add('error_msg', 'Ocurri&oacute; un error al cancelar la reserva (2)');
        return $this->redirectToRoute('appointment', array('appId'=>$appId));
      }else{
        return $this->redirect('/account/appointments?ac=1');
      }
    }
  }

  public function appointment(Request $request, $appId) {
    $session = $this->get('session');
    $user = $this->authCtrl->getUserData($request);
    if ($user == NULL){
      return $this->redirect('/login?r='.$request->getUri());
    }
    $error_msg   = implode('', $session->getFlashBag()->get('error_msg', []));
    $this->logger->debug("AppointmentsController|appointment | get appointment id ".$appId."...");
    $this->hubClient->setAccessToken($user['at']);
    $res = $this->hubClient->getAppointment($appId);
    if (!$res['resolved']){
      $this->logger->error("AppointmentsController|appointment| Error requesting user data!!");
      return $this->render('user/appointment.html.twig', [
        'error_msg' => "Ocurrio un error"
      ]);
    }else{
      if ($res['status_code'] != 200 && $res['status_code'] != 0){
        $this->logger->error("AppointmentsController|appointments| Server returned status code ".$res['status_code']." requesting getAppointments!!");
        return $this->render('appointments/appointment.html.twig', [
          'error_msg' => "Ocurrio un error"
        ]);
      }else{
        // print_r($res['data']);
        // echo date("h:i:sa");
        // die();

        $utcStartTime = Settings::convertMilitaryTime($res['data']['startHour'], FALSE);
        // $utcEndTime   = Settings::convertMilitaryTime($res['data']['endHour'], FALSE);
        $startTime    = Settings::convertMilitaryTime( strval((intval($res['data']['startHour']))), TRUE);
        // $endTime      = Settings::convertMilitaryTime( strval((intval($res['data']['endHour']))), TRUE);
        
        //
        // Build dates to show
        //
        \Moment\Moment::setLocale('es_ES');
        $momentDate = new \Moment\Moment(substr($res['data']['startDate'], 0, 10) . ' ' . $utcStartTime . ':00', 'UTC');
        $res['data']['startDateLocal'] = $momentDate->format('Y-m-d H:i:s');
        $res['data']['startDateDay']   = $momentDate->format('l');
        $res['data']['startDateDayN']  = $momentDate->format('j');
        $res['data']['startDateMonth'] = $momentDate->format('F');
        $res['data']['startDateYear']  = $momentDate->format('Y');
        $res['data']['startDateHour']  = $startTime;

        //
        // Build dates to calculate in time
        //
        $momentDate      = new \Moment\Moment($res['data']['startDate'], 'UTC');
        $momentDateEnd   = new \Moment\Moment($res['data']['endDate'], 'UTC');
        $momentFromVo    = $momentDate->fromNow();
        $momentFromVoEnd = $momentDateEnd->fromNow();
        $res['data']['startDateDirection'] = $momentFromVo->getDirection(); // future | past
        $res['data']['startDateRelative']  = $momentFromVo->getRelative(); // hace 2 horas | en 2 dias
        $res['data']['endDateDirection']   = $momentFromVoEnd->getDirection();
        $res['data']['endDateRelative']    = $momentFromVoEnd->getRelative(); 

        // print_r($res['data']);
        // die();

        $confirmed = ($request->query->get('c') == '1');

        return $this->render('appointments/appointment.html.twig', [
          'error_msg'    => $error_msg,
          'confirmed'    => $confirmed,
          'appId'        => $appId,
          'appointment'  => $res['data']
        ]);
      }
    }
  }

  public function virtualAppointment(Request $request, $appId) {
    $user = $this->authCtrl->getUserData($request);
    $this->logger->debug("AppointmentsController|virtualAppointment | get appointment id ".$appId."...");
    $this->hubClient->setAccessToken($user['at']);

    $res = $this->hubClient->getAppointment($appId);
    if (!$res['resolved']){
      $this->logger->error("AppointmentsController|virtualAppointment| Error requesting user data!!");
      return $this->render('user/appointment.html.twig', [
        'error_msg' => "Ocurrio un error"
      ]);
    }else{
      if ($res['status_code'] != 200 && $res['status_code'] != 0){
        $this->logger->error("AppointmentsController|virtualAppointment| Server returned status code ".$res['status_code']." requesting getAppointments!!");
        return $this->render('appointments/appointment.html.twig', [
          'error_msg' => "Ocurrio un error"
        ]);
      }else{
        return $this->render('appointments/videoroom.html.twig', [
          'participant'         => $res['data']['roomParticipants'][0],
          'videoroomsPublicUrl' => $res['data']['videoroomsPublicUrl']
        ]);
      }
    }
  }
  
}

