<?php

namespace App\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\JsonResponse;

use App\Service\HubClient;
use App\Service\NgageClient;
use App\Controller\AuthController;
use App\Utils\Settings;

use MercadoPago;
use PhpParser\Node\Stmt\Foreach_;
use Symfony\Component\HttpKernel\Exception\HttpException;

class Profile extends AbstractController {

  private $hubClient;
  private $logger;
  private $authCtrl;

  public function __construct(HubClient $hubClient, NgageClient $ngageClient, LoggerInterface $logger, AuthController $authCtrl){
    $this->hubClient = $hubClient;
    $this->logger    = $logger;
    $this->authCtrl  = $authCtrl;
    $this->userId    = null;
  }

  public function profileRender(Request $request){
    $okMessage = $this->get('session')->getFlashBag()->get('okMessage', []);
    if($okMessage) {
      $okMessage = implode(' ', $okMessage);
    }
    $error_msg = $this->get('session')->getFlashBag()->get('error_msg', []);
    if ($error_msg){
      $error_msg = implode(' ', $error_msg);
    }
    $nice_error_msg = $this->get('session')->getFlashBag()->get('nice_error_msg', []);
    if ($nice_error_msg){
      $nice_error_msg = implode(' ', $nice_error_msg);
    }

    $user = $this->authCtrl->getUserData($request);
    if ($user == NULL){
      return $this->redirectToRoute('login', array('r' => $request->getPathInfo()));
    }
    // print_r($user);
    // die();
    $request->setLocale(strtolower($user['countryCode']));
    $profile = $this->getProfile($user['at']);
    // print_r($profile);
    // die();
    if($profile == NULL) {
      return $this->render('user/profile.html.twig', [
        'error_msg' => "Ocurrio un error"
      ]);
    }
    \Moment\Moment::setLocale('es_ES');

    $subscriptionStatus = NULL;
    $subscriptionStatusIcon = NULL;
    if (array_key_exists('subscription', $profile)){
      if (array_key_exists('status', $profile['subscription'])){
        $subscriptionStatus = $profile['subscription']['status'];
      }
    }
    
    
    // ESTA REPETIDO ABAJO Y SIN S
    //
    // if (array_key_exists('userDevices', $profile)){
    //   for($i = 0 ; $i<count($profile['userDevices']) ; $i++){
    //     if(substr( $profile['userDevices'][$i]['name'], 0, 15 ) === "CardioSecur Pro") {
    //       $profile['userDevices'][$i]['isPro'] = true;
    //     } else {
    //       $profile['userDevices'][$i]['isPro'] = false;
    //     }
    //   }
    // }


    if (array_key_exists('billingAddress', $profile)){
      $user['billingAddress'] = $profile['billingAddress'];
    }
    $invoices = $this->getInvoices($request, $user);
    $payment_attempt = 0;
    if ($invoices){
      if(count($invoices) > 0){
        if($invoices[0]['payment_attempts'] > 0 && $invoices[0]['status'] != 'Paid' && $invoices[0]['status'] != 'Cancelled'){
          $payment_attempt = 1;
        }
      }
    }
    //
    // Update subscription status depending on debt
    //
    if ($payment_attempt > 0 && $subscriptionStatus != 'Cancelled'){
      $subscriptionStatus = 'Suspended';
    }
    //
    // Arrange patient data
    //
    if (array_key_exists('patient', $profile)){
      if ($profile['patient']['gender'] == 'm'){
        $profile['patient']['genderDesc'] = 'Masculino';
      }else if ($profile['patient']['gender'] == 'f'){
        $profile['patient']['genderDesc'] = 'Femenino';
      }else{
        $profile['patient']['genderDesc'] = '';
      }
      if (array_key_exists('birthdate', $profile['patient'])){
        if ($profile['patient']['birthdate'] != NULL && strlen($profile['patient']['birthdate']) > 0 && $profile['patient']['birthdate'] != "0000-00-00"){
          $momentDate = new \Moment\Moment($profile['patient']['birthdate']);
          $profile['patient']['birthdateDesc'] = $momentDate->format('d-m-Y');
          $profile['patient']['birthdateAge'] = floor($momentDate->fromNow()->getYears());
        }
      }
    }
    //
    // Check last observations
    //
    $lastObs = NULL;
    $lastObsServReq = NULL;
    if (array_key_exists('lastObservations', $profile)){
      $lastObs = $profile['lastObservations'];
      for($i=0; $i < count($lastObs); $i++){
        // Check ECG
        if ($lastObs[$i]['coding']=='LOINC' && $lastObs[$i]['code']=='11524-6'){
          if (!$lastObs[$i]['hasDiagnostic'] && $lastObs[$i]['serviceRequestId'] == NULL){
            $lastObsServReq = $lastObs[$i];
            break;
          }
        }
      }
    }
    //
    // Check User devices
    //
    $userDevices = NULL;
    if (array_key_exists('userDevice', $profile)){
      $userDevices = $profile['userDevice'];
      for($i = 0 ; $i<count($userDevices) ; $i++){
        if(substr( $userDevices[$i]['name'], 0, 15 ) === "CardioSecur Pro") {
          $userDevices[$i]['isPro'] = true;
        } else {
          $userDevices[$i]['isPro'] = false;
        }
      }
    }
    //
    // Check if own at least 1 device
    //
    $profile['hasOwnDevices'] = FALSE;
    if (array_key_exists('devices', $profile)){
      $devices = $profile['devices'];
      for($i = 0 ; $i<count($devices) ; $i++){
        if($devices[$i]['isOwner'] == '1'){
          $profile['hasOwnDevices'] = TRUE;
          break;
        }
      }
    }
    //
    // Check User invitations
    //
    if (array_key_exists('pendingInvitations', $profile)){
      for($i=0; $i < count($profile['pendingInvitations']); $i++){
        $momentDate = new \Moment\Moment($profile['pendingInvitations'][$i]['createdDate'], 'UTC');
        $profile['pendingInvitations'][$i]['createdDateNice'] = $momentDate->calendar();
      }
    }
    //
    // Check orders
    //
    if (array_key_exists('orders', $profile)){
      for($i=0; $i < count($profile['orders']); $i++){
        $momentDate = new \Moment\Moment($profile['orders'][$i]['creation'], 'UTC');
        $profile['orders'][$i]['creationNice'] = $momentDate->format('l d M H:i');
      }
    }
    //
    // Check appointments
    //
    $momentDate = NULL;
    if (array_key_exists('appointments', $profile)){
      for($i=0; $i < count($profile['appointments']); $i++){

        $profile['appointments'][$i]['statusNice'] = Settings::convertStatus($profile['appointments'][$i]['status']);

        $momentDate = new \Moment\Moment($profile['appointments'][$i]['startDate'], 'UTC');
        $profile['appointments'][$i]['startDateNice'] = $momentDate->format('l d M');
        $profile['appointments'][$i]['startHourNice']  = Settings::convertMilitaryTime($profile['appointments'][$i]['startHour'], TRUE);
      }
    }

    return $this->render('user/profile.html.twig', [
      'error_msg'                => $error_msg,
      'nice_error_msg'           => $nice_error_msg,
      'user'                     => $user,
      'profile'                  => $profile,
      'allInvoices'              => $invoices,
      'subscription_status'      => $subscriptionStatus,
      'subscription_status_icon' => $subscriptionStatusIcon,
      'payment'                  => $payment_attempt,
      'userDevices'              => $userDevices,
      'devices'                  => $profile['devices'],
      'lastObservations'         => $lastObs,
      'assignedDevices'          => $profile['devices'],
      'info_msg'                 => $okMessage,
      'lastObsServReq'           => $lastObsServReq
    ]);
  }

  public function getProfile($accessToken){ 
    $this->hubClient->setAccessToken($accessToken);
    $cliRes = $this->hubClient->getProfile();
    if (!$cliRes['resolved']){
      $this->logger->error("Profile | getProfile | Error requesting user data!!");
      return NULL;
    }else{
      if ($cliRes['status_code'] != 200 && $cliRes['status_code'] != 0){
        $this->logger->error("Profile | getProfile | Server returned status code ".$cliRes['status_code']." requesting getUserData!!");
        return NULL;
      }else{
        // Store user data in session
        $this->logger->debug("Profile | getProfile |Got user data from server");
        $this->hubClient->setAccessToken($accessToken);
        return $cliRes['data'];
      }
    }
    
  }

  public function getInvoices(Request $request, $user){
    $this->logger->debug("Profile | getInvoices | Start.");
    
    $this->hubClient->setAccessToken($user['at']);
    $cliRes = $this->hubClient->getInvoices();
    // print_r($cliRes);
    // die();
    if (!$cliRes['resolved']){
      $this->logger->error("Profile | getInvoices| Error requesting user invoices data!!");
      return NULL;
    }else{
      if ($cliRes['status_code'] != 200 && $cliRes['status_code'] != 0){
        $this->logger->error("Profile | getInvoices | Server returned status code ".$cliRes['status_code']." requesting getUserData!!");
        return NULL;
      }else{
        $this->logger->debug("Profile | getInvoices |Got user invoices data from server");
        if ($cliRes['data']['result'] != NULL && array_key_exists('data', $cliRes['data']['result'])){
          return $cliRes['data']['result']['data'];
        }else{
          return NULL;
        }
      }
    }
  }

  public function getSubscription(Request $request){
    $this->logger->debug("Profile | getSubscription | Start.");
    $user = $this->authCtrl->getUserData($request);
    if ($user == NULL){
      return $this->redirectToRoute('login', array('r' => $request->getPathInfo()));
    }
    $this->hubClient->setAccessToken($user['at']);
    $cliRes = $this->hubClient->getSubscription();
    if (!$cliRes['resolved']){
      $this->logger->error("Profile | getSubscription| Error requesting user invoices data!!");
      return NULL;
    }else{
      if ($cliRes['status_code'] != 200 && $cliRes['status_code'] != 0){
        $this->logger->error("Profile | getSubscription| Server returned status code ".$cliRes['status_code']." requesting getUserData!!");
        return NULL;
      }else{
        $this->logger->debug("Profile | getSubscription|Got user invoices data from server");
        $cliRes['data']['userMail'] = $user['email'];
        $cliRes['data']['userName'] = $user['username'];

        return $cliRes['data'];
      }
    }  
  }
  

  //
  // Change Password Page
  //
  public function changePasswordGet(Request $request){
    return $this->render('user/changePassword.html.twig');
  }

  public function changePasswordPost(Request $request){
    $this->logger->debug("Profile | changePassword | Start...");
    $data = $request->request->get('form');
    $actualPassword = $request->request->get('actualPassword');
    $newPassword = $request->request->get('newPassword');
    $user = $this->authCtrl->getUserData($request);
    if ($user == NULL){
      return $this->redirectToRoute('login');
    }
    $this->hubClient->setAccessToken($user['at']);
    $cliRes = $this->hubClient->changePassword($actualPassword, $newPassword);
    if ($cliRes['status_code'] != 204){
      if($cliRes['status_code'] == 406){
        return $this->render('user/changePassword.html.twig', ['err_message' => 'La nueva contraseña no es aceptable. Puede que ya la haya usado o no cumple con el patrón mínimo de seguridad. Pruebe ingresando una nueva.']);
      }
      else if($cliRes['status_code'] == 400){
        return $this->render('user/changePassword.html.twig', ['err_message' => 'La contraseña actual no es correcta.']);
      }  
    }else{
      return $this->render('user/changePassword.html.twig', ['message' => 'Contraseña cambiada correctamente.']);
    }
  }

  
  // 
  // patientPractitioner
  //
  public function patientPractitioner(Request $request) {
    $user = $this->authCtrl->getUserData($request);
    if ($user == NULL){
      return $this->redirect('/login?r='.$request->getUri());
    }
    
    $this->logger->debug("Profile | patientPractitioner | get practitioners...");
    
    $errorMessage = $this->get('session')->getFlashBag()->get('errorMessage', []);
    if($errorMessage) {
      $errorMessage = $errorMessage[0];
    }
    $this->hubClient->setAccessToken($user['at']);
    $res = $this->hubClient->getCatalog();

    // $res['data'] = [];

    return $this->render('user/patientPractitioner.html.twig',[
      'services'    => $res['data'] , 
      'err_message' => $errorMessage]);
  }

  public function selectPatientPractitioner($practitionerId, $orgId, Request $request) {
    
    $user = $this->authCtrl->getUserData($request);
    $userId = $user['userId'];
    
    $this->hubClient->setAccessToken($user['at']);
    $cliRes = $this->hubClient->selectPatientPractitioner($userId , $practitionerId, $orgId);
    if (!$cliRes['resolved'] || $cliRes['status_code'] != 200){
      $this->logger->error("Profile | selectPatientPractitioner | Error!!");
      $this->get('session')->getFlashBag()->add('errorMessage', 'Ocurrió un error al asignar medico' );
      return $this->redirectToRoute('patientPractitioner');
    }else{
      $this->get('session')->getFlashBag()->add('okMessage', 'Se asignó a '.$cliRes['data']['result']['name']. ' como su médico de referencia.' );
      return $this->redirectToRoute('profile');
    }
  }


  public function removePatientPractitioner(Request $request){
    $user = $this->authCtrl->getUserData($request);
    $this->hubClient->setAccessToken($user['at']);
    $cliRes = $this->hubClient->removePractitionerPermissions();
    if (!$cliRes['resolved'] || $cliRes['status_code'] != 200){
      $this->logger->error("Profile | removePatientPractitioner | Error!!");
      $this->get('session')->getFlashBag()->add('errorMessage', 'Ocurrió un error al desasignar medico' );
      return $this->redirectToRoute('profile');
    }else{
      $this->get('session')->getFlashBag()->add('okMessage', 'Se desasignó el médico de referencia.' );
      return $this->redirectToRoute('profile');
    }
  }

  //
  // Billing
  //
  public function billingDetailsGet(Request $request){
    $user = $this->authCtrl->getUserData($request);
    if ($user == NULL){
      return $this->redirectToRoute('login', array('r' => $request->getPathInfo()));
    }
    $invoices = $this->getInvoices($request, $user);
    $subscription = $this->getSubscription($request);
    $old_date = Date_create($subscription['result']['current_invoice_start']);
    $subscription['result']['current_invoice_start_to_show'] = Date_format($old_date, "d-m-Y");
    
    for ($i=0; $i < count($invoices); $i++){
      $old_from_date = Date_create($invoices[$i]['from_date']);
      $invoices[$i]['from_date_to_show'] = Date_format($old_from_date, "d-m-Y");
      $old_to_date = Date_create($invoices[$i]['to_date']);
      $invoices[$i]['to_date_to_show'] = Date_format($old_to_date, "d-m-Y");
    }

    // print_r($subscription);
    // die();

    if($subscription) {
      $payment_attempt = 0;
      if(count($invoices) > 0){
        if($invoices[0]['payment_attempts'] > 0 && $invoices[0]['status'] != 'Paid' && $invoices[0]['status'] != 'Cancelled'){
          $payment_attempt = 1;
        }else{
          $payment_attempt = 0;
        }
      }

      //
      // Check internal suspended state
      //
      if ($payment_attempt > 0 && $subscription['result']['status'] != 'Cancelled'){
        $subscription['result']['status'] = 'Suspended';
      }
      return $this->render('user/billingDetails.html.twig', [
        'invoices'      => $invoices, 
        'subscriptions' => $subscription, 
        'payment'       => $payment_attempt]);
    } else {
      return $this->render('user/billingDetails.html.twig', [
        'invoices'      => $invoices, 
        'subscriptions' => $subscription , 
        'error_msg'     => 'No se pudo obtener los datos']);
    }
  }

  public function getInvoiceData($invoiceToken, Request $request){
    // Recover session data
    $session = $this->get('session');
    $session->start();
    $sessionId = $session->getId();

    // Check success message
    $paid_out = $this->get('session')->getFlashBag()->get('paid_out', []);
    
    // Check error messsage
    $error_msg = implode('', $this->get('session')->getFlashBag()->get('error_msg', []));

    // Get user and invoice data
    $this->logger->debug("Profile | getInvoiceData | Users has AT but data is not in session.");
    $user = $this->authCtrl->getUserData($request);
    if ($user == NULL){
      return $this->redirect('/login?r='.$request->getUri());
    }
    $this->hubClient->setAccessToken($user['at']);
    $cliRes = $this->hubClient->getInvoiceData($invoiceToken);

    // print_r($cliRes);
    // die();

    if (!$cliRes['resolved']){
      $this->logger->error("Profile | getInvoiceData| Error requesting user invoices data!!");

      return $this->render('user/invoice.html.twig',[
        'error_msg' => 'Error! Hubo un error al leer la factura',
        'invoice'   => NULL,
        'data'      => NULL,
        'currency'  => NULL
      ]);
    }else{
      if ($cliRes['status_code'] != 200 && $cliRes['status_code'] != 0){
        $this->logger->error("Profile | getInvoiceData | Server returned status code ".$cliRes['status_code']." requesting getUserData!!");

        return $this->render('user/invoice.html.twig',[
          'error_msg' => 'Error! Hubo un error al leer la factura',
          'invoice'   => NULL,
          'data'      => NULL,
          'currency'  => NULL
        ]);
      }else{
        $orderData = array(
          'order_hashed_id'    => $invoiceToken,
          'order_id'           => $cliRes['data']['invoice_name'],
          'status'             => $cliRes['data']['status'],
          'transaction_date'   => $cliRes['data']['transaction_date'],
          'delivery_date'      => $cliRes['data']['due_date'],
          'bill_id'            => '',
          'customer'           => $cliRes['data']['customer'],
          'items'              => array(),
          'currency'           => $cliRes['data']['currency'],
          'discount'           => array(
            'name'                 => '',
            'apply_discount_on'    => $cliRes['data']['apply_discount_on'],
            'discountAmount'       => $cliRes['data']['base_discount_amount'],
            'discountAmountToShow' => $cliRes['data']['base_discount_amountToShow'],
            'price'                => $cliRes['data']['discount_amount'],
            'priceToShow'          => $cliRes['data']['discount_amountToShow'],
            'base_price'           => $cliRes['data']['base_discount_amount'],
            'base_priceToShow'     => $cliRes['data']['base_discount_amountToShow'],
          ),
          'total'              => array(
            'price'            => $cliRes['data']['total'],
            'priceToShow'      => $cliRes['data']['totalToShow'],
            'base_price'       => $cliRes['data']['base_total'],
            'base_priceToShow' => $cliRes['data']['base_totalToShow'],
            'currency'         => $cliRes['data']['currency']
          ),
          'grand_total'        => array(
            'price'            => $cliRes['data']['grand_total'],
            'priceToShow'      => $cliRes['data']['grand_totalToShow'],
            'base_price'       => $cliRes['data']['base_grand_total'],
            'base_priceToShow' => $cliRes['data']['base_grand_totalToShow'],
            'currency'         => $cliRes['data']['currency']
          ),
          'rounded_total'       => array(
            'price'            => $cliRes['data']['rounded_total'],
            'priceToShow'      => $cliRes['data']['rounded_totalToShow'],
            'base_price'       => $cliRes['data']['base_rounded_total'],
            'base_priceToShow' => $cliRes['data']['base_rounded_totalToShow'],
            'currency'         => $cliRes['data']['currency'],
            'price_float'      => $cliRes['data']['rounded_total']
          ),
          'rounding_adjustment' => array(
            'price'            => $cliRes['data']['rounding_adjustment'],
            'priceToShow'      => $cliRes['data']['rounding_adjustmentToShow'],
            'base_price'       => $cliRes['data']['base_rounding_adjustment'],
            'base_priceToShow' => $cliRes['data']['base_rounding_adjustmentToShow'],
            'currency'         => $cliRes['data']['currency']
          )
        );
        // load taxes and charges
        if(array_key_exists('shipping_rule' , $cliRes['data'])) {
          $shipRuleName = $cliRes['data']['shipping_rule'];
        } else {
          $shipRuleName = '';
        }
        $orderData['taxes'] = array(
          'taxes_amount'                          => 0,
          'base_taxes_amount'                     => 0,
          'tax_amount_after_discount_amount'      => 0,
          'base_tax_amount_after_discount_amount' => 0,
          'currency'                              => $cliRes['data']['currency']
        );
        $orderData['shipping'] = array(
          'price'      => 0,
          'base_price' => 0,
          'currency'   => $cliRes['data']['currency']
        );
        for ($i=0; $i < count($cliRes['data']['taxes']); $i++){
          if ($cliRes['data']['taxes'][$i]['name'] == $shipRuleName){
            $orderData['shipping']['price']      = $cliRes['data']['taxes'][$i]['total'];
            $orderData['shipping']['base_price'] = $cliRes['data']['taxes'][$i]['base_total'];
          }else{
            $orderData['taxes']['taxes_amount']      += $cliRes['data']['taxes'][$i]['total'];
            $orderData['taxes']['base_taxes_amount'] += $cliRes['data']['taxes'][$i]['base_total'];
            $orderData['taxes']['tax_amount_after_discount_amount']      += $cliRes['data']['taxes'][$i]['total_after_discount_amount'];
            $orderData['taxes']['base_tax_amount_after_discount_amount'] += $cliRes['data']['taxes'][$i]['base_total_after_discount_amount'];
          }
        }
        $orderData['taxes']['taxes_amountToShow']      = number_format($orderData['taxes']['taxes_amount'], 2, ',', '.');
        $orderData['taxes']['base_taxes_amountToShow'] = number_format($orderData['taxes']['base_taxes_amount'], 2, ',', '.');
        $orderData['taxes']['tax_amount_after_discount_amountToShow'] = number_format($orderData['taxes']['tax_amount_after_discount_amount'], 2, ',', '.');
        $orderData['taxes']['base_tax_amount_after_discount_amountToShow'] = number_format($orderData['taxes']['base_tax_amount_after_discount_amount'], 2, ',', '.');
        $orderData['shipping']['priceToShow']          = number_format($orderData['shipping']['price'], 2, ',', '.');
        $orderData['shipping']['base_priceToShow']     = number_format($orderData['shipping']['base_price'], 2, ',', '.');
        
        //load items
        for ($i=0; $i < count($cliRes['data']['items']); $i++){
          $orderData['items'][] = array(
            'total'            => $cliRes['data']['items'][$i]['amount'],
            'totalToShow'      => $cliRes['data']['items'][$i]['amountToShow'],
            'base_total'       => $cliRes['data']['items'][$i]['base_amount'],
            'base_totalToShow' => $cliRes['data']['items'][$i]['base_amountToShow'],
            'price'            => $cliRes['data']['items'][$i]['item_amount'],
            'priceToShow'      => $cliRes['data']['items'][$i]['item_amountToShow'],
            'base_price'       => $cliRes['data']['items'][$i]['base_item_amount'],
            'base_priceToShow' => $cliRes['data']['items'][$i]['base_item_amountToShow'],
            'currency'         => $cliRes['data']['currency'],
            'qty'              => $cliRes['data']['items'][$i]['qty'],
            'name'             => $cliRes['data']['items'][$i]['item_name'],
          ); 
        }
        
        // ====================================================
        // ====================================================
        // ====================================================
        // ====================================================
        // WARNING !!!!
        // CHOOSE COUNTRY DEPENDING ON MP AVAILABILITY !!!!!
        $payCountry = 'UY';
        // ====================================================
        // ====================================================
        // ====================================================

        return $this->render('user/invoice.html.twig', [
          'error_msg'  => $error_msg,
          'invoice'    => $cliRes['data'], 
          'data'       => $orderData, 
          'paid_out'   => $paid_out,  
          'currency'   => Settings::get_currency_by_country($user['countryCode']),
          'sid'        => $sessionId,
          'user'       => $user,
          'payCountry' => $payCountry
        ]);
      }
    }
  }

  public function processInvoicePayment($invoiceToken, $sid, Request $request) {
    $this->logger->info(sprintf("ProfileCtrl|processInvoicePayment| Recovering session data with id %s...", $sid));
    // Ensure sessionId from previous checkout step
    $session = $this->get('session');
    $session->setId($sid);
    $session->start();

    $user = $this->authCtrl->getUserData($request);
    $this->hubClient->setAccessToken($user['at']);

    // $apiKey = Settings::mp_keys[$user['countryCode']];
    // MercadoPago\SDK::setAccessToken($apiKey['private']);
    MercadoPago\SDK::setAccessToken($_SERVER["BL_MPUY_PRIVATE_KEY"]);

    $cliRes = $this->hubClient->getInvoiceData($invoiceToken);

    // print_r($cliRes);
    // die();

    if (!$cliRes['resolved']) {
      $this->logger->error("Profile | processInvoicePayment | Error requesting data for invoice '" . $invoiceToken . "'.");
      return $this->redirectToRoute('invoice_details', ['invoiceToken' => $invoiceToken]);
    } 
    
    if ($cliRes['status_code'] != 200 && $cliRes['status_code'] != 0){
      $this->logger->error("Profile | processInvoicePayment | Server returned status code ".$cliRes['status_code']." - method: getInvoiceData!!");
      return $this->redirectToRoute('invoice_details', ['invoiceToken' => $invoiceToken]);;
    }

    $invoiceData = $cliRes['data'];

    $this->logger->info(sprintf("Profile | processInvoicePayment | Start processing MercadoPago payment..."));
    $user = $this->authCtrl->getUserData($request);

    //
    // Get MP data
    $token             = $_REQUEST["token"];
    $payment_method_id = $_REQUEST["payment_method_id"];
    if (array_key_exists('installments', $_REQUEST)){
      $installments = $_REQUEST["installments"];
    }else{
      $installments = 1;
    }
    $issuer_id = $_REQUEST["issuer_id"];
    // Define AmountToPay
    $amountToPay = $invoiceData['grand_total'];


    // ====================================================
    // ====================================================
    // ====================================================
    // ====================================================
    // WARNING !!!!
    // CHOOSE COUNTRY DEPENDING ON MP AVAILABILITY !!!!!
    $payCountry = 'UY';
    // ====================================================
    // ====================================================
    // ====================================================

    //
    // Prepare payment Entry
    //
    $paymentEntry = array(
      'gateway'              => "MERCADOPAGO",
      'customer_name'        => $invoiceData['customer'],
      'currency'             => $invoiceData['currency'],
      'invoice_name'         => $invoiceData['invoice_name'],
      'amount'               => $amountToPay,
      'country'              => $payCountry,
      'exchange_rate'        => $invoiceData['conversion_rate']
    );
    //
    // We create the paymentEntry instance
    //
    $pEntryRes = $this->hubClient->preparePaymentEntry($paymentEntry);
    if (!$pEntryRes['resolved'] || $pEntryRes['status_code'] != '200') {
      $this->logger->error(sprintf("Profile | processInvoicePayment | ERROR PREPARING PAYMENT ENTRY!!!!!! -> PAYMENT IS '" . json_encode($pEntryRes) . "'."));
      $this->get('session')->getFlashBag()->add('error_msg', 'Error al crear el paymentEntry');
      return $this->redirectToRoute('invoice_details', ['invoiceToken' => $invoiceToken]);
    }
    $paymentEntry = $pEntryRes['data'];
    //
    // Resolve MP customer
    //
    $this->logger->info(sprintf("Profile | processInvoicePayment | Searching customer %s", $user["email"]));
    $existing_customer = MercadoPago\Customer::search(array(
      "email" => $user['email']
    ));
    if (!count($existing_customer)) {
      $customer = new MercadoPago\Customer();
      $customer->email = $user["email"];
      $this->logger->info(sprintf("Profile | processInvoicePayment | Saving MP customer with email: %s", $user["email"]));
      $customer->save();
    } else {
      $this->logger->info(sprintf("Profile | processInvoicePayment | Using existing MP customer with email: %s", $user["email"]));
      $customer = $existing_customer[0];
    }

    // ---------------------------------
    // DO PAYMENT IN MP
    // ---------------------------------
    $payment                     = new MercadoPago\Payment();
    $payment->transaction_amount = $amountToPay;
    $payment->token              = $token;
    $payment->installments       = $installments;
    $payment->payment_method_id  = $payment_method_id;
    $payment->issuer_id          = $issuer_id;
    $payment->binary_mode        = TRUE;
    $payment->payer              = array(
      "id" => $customer->id
    );
    $this->logger->info(sprintf("Profile | processInvoicePayment | Requesting payment at MP with token: %s, method: %s, issuer: %s, amount: %s, customerId: %s", $token, $payment_method_id, $issuer_id, $invoiceData['rounded_total'], $customer->id));
    $payment->save();

    $this->logger->info(sprintf("Profile | processInvoicePayment | The payment status is: %s.", $payment->status));

    //
    // Check MP Response
    //
    if ($payment->status == 'approved' || $payment->status == 'in_process') {
      //
      // PAYMENT WAS APPROVED
      // Must check if it was already approved or in progress
      //
      if (!isset($payment->card) || (isset($payment->card) && $payment->card->id == NULL)) {
        $card = new MercadoPago\Card();
        $card->token = $token;
        $card->customer_id = $customer->id;
        $card->save();
      } else {
        $card = MercadoPago\Card::find_by_id($payment->card->id);
        if (!isset($card->issuer)) {
          $card = $payment->card;
        }
      }

      //
      // Complete data at Payment Entry from MP
      //
      $paymentEntry['country']              = $payCountry;
      $paymentEntry['transaction_id']       = $payment->id;
      $paymentEntry['statement_descriptor'] = $payment->statement_descriptor;
      $paymentEntry['external_userid']      = $customer->id;
      $paymentEntry['issuer_name']          = 'Tarjeta';
      if (isset($card->issuer)) {
        $paymentEntry['issuer_name'] = $card->issuer->name;
      }
      if (isset($card->last_four_digits)) {
        $paymentEntry['card_last4'] = $card->last_four_digits;
      } else if (isset($payment->card->last_four_digits)) {
        $paymentEntry['card_last4'] = $payment->card->last_four_digits;
      }
      if (isset($card->cardholder)) {
        $paymentEntry['card_holder'] = $card->cardholder->name;
      } else if (isset($payment->card->cardholder)) {
        $paymentEntry['card_holder'] = $payment->card->cardholder->name;
      }
      //
      // Check Status
      //
      if ($payment->status == 'approved') {
        $this->logger->info(sprintf("Profile | processInvoicePayment | Confirming payment entry %s...", $paymentEntry['name']));
        $confirmpEntryRes = $this->hubClient->confirmPaymentEntry($paymentEntry);

        if (!$confirmpEntryRes['resolved'] || $confirmpEntryRes['status_code'] != '200') {
          $this->get('session')->getFlashBag()->add('error_msg', 'Error al submit el paymentEntry');
        }

        return $this->redirectToRoute('invoice_details', ['invoiceToken' => $invoiceToken]);
      } else if ($payment->status == 'in_process') {
        $this->logger->info(sprintf("Profile | processInvoicePayment | The payment is in process, will not submit the paymentEntry, yet"));
        return $this->redirectToRoute('invoice_details', ['invoiceToken' => $invoiceToken]);
      }
    } else {
      //
      // PAYMENT FAILED !!!
      //
      //$cancelpEntryRes = $this->hubClient->cancelPaymentEntry($paymentEntry['name']);
      // Build error message for user
      $errMsg = "<strong>El pago no pudo ser completado</strong><br>";
      $errMsg .= Settings::mp_status_errors[$payment->status_detail];
      $this->get('session')->getFlashBag()->add('error_msg', $errMsg);
      return $this->redirectToRoute('invoice_details', [
        'invoiceToken' => $invoiceToken
      ]);
    }
  }


  //
  // Cancel subcription
  //
  public function cancelSubscriptionPost(Request $request){
    $user = $this->authCtrl->getUserData($request);
    $this->hubClient->setAccessToken($user['at']);
    $error_msg = '';
    $info_msg = '';
    $subscription = $this->getSubscription($request);
    $cliRes = $this->hubClient->cancelSubscription($subscription['result']['name'], $subscription['userMail'], $subscription['userName']);
    if (!$cliRes['resolved']){
      $this->logger->error("Profile | cancelSubscription | Error canceling subscription!!");
      $error_msg = 'Error! Hubo un error al cancelar la subscripcion. Intentelo nuevamente.';
    }
    else {
      if($cliRes['status_code'] == 200){
        $info_msg = 'La subscripcion fue cancelada correctamente.';
      }
      else{
        $error_msg = 'Error! Hubo un error al cancelar la subscripcion. Intentelo nuevamente.';
      }
      
    }
    return $this->render('user/cancelSubscription.html.twig', array(
      'error_msg'         => $error_msg,
      'info_msg'          => $info_msg,
    ));
  }

  public function cancelSubscription(Request $request){
    // TODO: Process cancellation
    return $this->render('user/cancelSubscription.html.twig');
  }


  //
  // Susbcription Config
  //
  public function subscriptionConfig(Request $request){

    $subscription = array (
      'plans' => array (
        array (
          'name'    => 'CardioSecur Active subscription',
          'patient' => array (
            'id'        => 1,
            'email'     => 'test@test.com',
            'firstname' => 'Test',
            'lastname'  => 'Tester',
            'phone'     => '123123123'
          )
        ),
        array (
          'name'    => 'CardioSecur Pro subscription (12L)',
          'patient' => array (
            'id'        => NULL,
            'email'     => '',
            'firstname' => '',
            'lastname'  => '',
            'phone'     => ''
          )
        )
      ),
      'devices' => array (
        array(
          'id'    => 1,
          'name'  => 'CardioSecur Active',
          'sn'    => 'MA1234'
        ),
        array(
          'id'    => 2,
          'name'  => 'CardioSecur Active',
          'sn'    => 'MA2345'
        ),
      )
    );

    return $this->render('user/subscriptionConfig.html.twig', array(
      'subscription' => $subscription
    ));
  }

  public function subscriptionConfigPost(Request $request){
  }

  public function inviteMember(Request $request){
    // Get User
    $user = $this->authCtrl->getUserData($request);
    if ($user == NULL){
      return $this->redirectToRoute('login', array('r' => $request->getPathInfo()));
    }

    // Build data
    $recipientName   = $request->request->get('recipientName');
    $recipientEmail  = $request->request->get('recipientEmail');
    $deviceSerialNum = $request->request->get('deviceSerialNum');

    $data = array(
      'recipientName'   => $recipientName,
      'recipientEmail'  => $recipientEmail,
      'deviceSerialNum' => $deviceSerialNum
    );

    // Request send invitation
    $this->hubClient->setAccessToken($user['at']);
    $cliRes = $this->hubClient->inviteMember($data);
    if (!$cliRes['resolved']){
      $this->logger->error("Profile|inviteMember| Error requesting user data!!");
      return $this->render('user/inviteMember.html.twig', [
        'error_msg'      => "Ocurrio un error intentando enviar la invitación",
        'recipientName'  => $recipientName,
        'recipientEmail' => $recipientEmail
      ]);
    }else{
      if ($cliRes['status_code'] != 200 && $cliRes['status_code'] != 0){
        $this->logger->error("Profile|inviteMember| Error requesting user data!! StatusCode: ".$cliRes['status_code']);
        return $this->render('user/inviteMember.html.twig', [
          'error_msg'      => "Ocurrio un error intentando enviar la invitación",
          'recipientName'  => $recipientName,
          'recipientEmail' => $recipientEmail
        ]);
      }
      return $this->render('user/inviteMember.html.twig', [
        'error_msg'      => NULL,
        'recipientName'  => $recipientName,
        'recipientEmail' => $recipientEmail
      ]);
    }

  }

  public function removeMember(Request $request , $pid, $did) {

    
    $user = $this->authCtrl->getUserData($request);
    $this->hubClient->setAccessToken($user['at']);
    $cliRes = $this->hubClient->removeFamilyPlan($pid , $did);
    if (!$cliRes['resolved'] || $cliRes['status_code'] != 200){
      $this->logger->error("Profile | removePatientPractitioner | Error!!");
      $this->get('session')->getFlashBag()->add('errorMessage', 'Ocurrió un error al desasignar paciente' );
      return $this->redirectToRoute('profile');
    }else{
      $this->get('session')->getFlashBag()->add('okMessage', 'Se desasignó el paciente' );
      return $this->redirectToRoute('profile');
    }

    // if (FALSE){
    //   $this->get('session')->getFlashBag()->set('okMessage', ['Se quitó el miembro del dispositivo con éxito']);
    // }else{
    //   $this->get('session')->getFlashBag()->set('nice_error_msg', ['Ocurrió un problema al intentar quitar el miembro']);
    // }
    // return $this->redirectToRoute('profile');
  }

  //
  // Clinical Profile
  //
  public function clinicalProfile(Request $request){
    $info_msg = $this->get('session')->getFlashBag()->get('info_msg', []);
    if($info_msg) {
      $info_msg = implode(' ', $info_msg);
    }

    // Get User
    $user = $this->authCtrl->getUserData($request);
    if ($user == NULL){
      return $this->redirectToRoute('login', array('r' => $request->getPathInfo()));
    }
    // Get country 
    $country = Settings::resolveCountry($user, $request);
    // Get data
    $this->hubClient->setAccessToken($user['at']);
    $cliRes = $this->hubClient->getClinicalProfile();
    
    // print_r($cliRes);
    // die();

    if (!$cliRes['resolved']){
      $this->logger->error("Profile|clinicalProfile| Error requesting user data!!");
      return $this->render('user/profileClinic.html.twig', [
        'error_msg' => "Ocurrio un error",
        'country'   => $country['code'],
        'countries' => Settings::countries
      ]);
    }else{
      if ($cliRes['status_code'] != 200 && $cliRes['status_code'] != 0){
        $this->logger->error("Profile|clinicalProfile| Server returned status code ".$cliRes['status_code']." requesting getUserData!!");
        return $this->render('user/profileClinic.html.twig', [
          'error_msg' => "Ocurrio un error",
          'country'   => $country['code'],
          'countries' => Settings::countries
        ]);
      }else{
        // Store user data in session
        $this->logger->debug("Profile|clinicalProfile|Got patient data from server");
        
        // print_r($cliRes['data']);
        // die();
        $patient = $cliRes['data'];

        //
        // Arrange data
        //
        if (array_key_exists('contacts', $patient)){
          for($i=0; $i < count($patient['contacts']); $i++){
            if ($patient['contacts'][$i]['relationship'] == 'C'){
              $patient['contacts'][$i]['relationshipDesc'] = 'Contacto de emergencia';
            }else if ($patient['contacts'][$i]['relationship'] == 'E'){
              $patient['contacts'][$i]['relationshipDesc'] = 'Empleador';
            }else if ($patient['contacts'][$i]['relationship'] == 'I'){
              $patient['contacts'][$i]['relationshipDesc'] = 'Compañía de seguros/mutualista';
            }else if ($patient['contacts'][$i]['relationship'] == 'N'){
              $patient['contacts'][$i]['relationshipDesc'] = 'Familiar';
            }else if ($patient['contacts'][$i]['relationship'] == 'U'){
              $patient['contacts'][$i]['relationshipDesc'] = 'Otro';
            }else {
              $patient['contacts'][$i]['relationshipDesc'] = 'Otro';
            }
            if ($patient['contacts'][$i]['telecom_use'] == 'home'){
              $patient['contacts'][$i]['telecom_useDesc'] = 'Particular';
            }else if ($patient['contacts'][$i]['telecom_use'] == 'work'){
              $patient['contacts'][$i]['telecom_useDesc'] = 'Laboral';
            }else if ($patient['contacts'][$i]['telecom_use'] == 'temp'){
              $patient['contacts'][$i]['telecom_useDesc'] = 'Temporal';
            }else if ($patient['contacts'][$i]['telecom_use'] == 'mobile'){
              $patient['contacts'][$i]['telecom_useDesc'] = 'Móvil';
            }else{
              $patient['contacts'][$i]['telecom_useDesc'] = 'Otro';
            }
          }
        }

        return $this->render('user/profileClinic.html.twig', [
          'info_msg'  => $info_msg,
          'patient'   => $patient,
          'country'   => $country['code'],
          'countries' => Settings::countries
        ]);
      }
    }
  }

  private function buildObsComp($coding, $code, $value){
    // Check units
    $unit = '';
    if ($coding == 'LOINC' && $code == '29463-7'){
      //BodyWeight
      $unit = 'kg';
    }else if ($coding == 'LOINC' && $code == '8302-2'){
      //BodyHeight
      $unit = 'cm';
    }
    // Check for codable option response or just value
    if (Substr($value, 0, 3) == 'op_'){
      $splittedValue = explode('_', $value);
      return array(
        'coding'        => $coding,
        'code'          => $code,
        'codableCoding' => $splittedValue[1],
        'codableCode'   => $splittedValue[2],
        'unit'          => $unit 
      );
    }else{
      return array(
        'coding' => $coding,
        'code'   => $code,
        'value'  => $value,
        'unit'   => $unit
      );
    }
  }

  public function clinicalProfileSave(Request $request){
    // Get user
    $user = $this->authCtrl->getUserData($request);
    if ($user == NULL){
      return $this->redirectToRoute('login', array('r' => $request->getPathInfo()));
    }
    // Get country 
    $country = Settings::resolveCountry($user, $request);
    
    //
    // Parse parmeters and build request
    //
    $reqObj = [
      'patientData'    => [],
      'observations'   => [],
      'contacts'       => [],
      'contactsDelete' => []
    ];
    $params = $request->request->all();

    // print_r($params);
    // die();

    //
    // Check patient basic data
    //
    if (array_key_exists('$document', $params)){
      if (strlen($params['$document']) > 0){
        $reqObj['patientData']['document'] = $params['$document'];
      }
    }
    if (array_key_exists('$gender', $params)){
      if (strlen($params['$gender']) > 0){
        $reqObj['patientData']['gender'] = $params['$gender'];
      }
    }
    if (array_key_exists('$birthdate', $params)){
      if (strlen($params['$birthdate']) > 0){
        $reqObj['patientData']['birthdate'] = $params['$birthdate'];
      }
    }
    //
    // Check params
    //
    $contactsAux = [];
    foreach ($params as $paramKey => $paramVal) {
      if ($paramVal && strlen($paramVal) > 0){
        // Check component changed
        if (substr($paramKey, 0, 5) == '$comp'){
          $splittedKey = explode('_', $paramKey);
          $bFound = FALSE;
          for($i=0; $i < count($reqObj['observations']); $i++){
            $reqObj['observations'][$i]['performerOrganizationId'] = $user['organization']['id'];
            if ($reqObj['observations'][$i]['templateCode'] == $splittedKey[1]){
              $bFound = TRUE;
              $reqObj['observations'][$i]['components'][] = $this->buildObsComp($splittedKey[2], $splittedKey[3], $paramVal);
            }
          }
          if (!$bFound){
            $reqObj['observations'][] = array(
              'templateCode'       => $splittedKey[1],
              'components' => array($this->buildObsComp($splittedKey[2], $splittedKey[3], $paramVal))
            );
          }
        }
        // Check contact changed
        if (substr($paramKey, 0, 5) == '$con_'){
          $splittedKey = explode('_', $paramKey);
          if (!isset($contactsAux[$splittedKey[1]])){
            $contactsAux[$splittedKey[1]] = [];
          }
          $contactsAux[$splittedKey[1]][$splittedKey[2]] = $paramVal;
        }
        // Check contact deletion
        if (substr($paramKey, 0, 8) == '$delcon_'){
          $reqObj['contactsDelete'][] = $paramVal;
        }
      }
    }
    // re-arrange contacts
    foreach ($contactsAux as $contact) {
      $reqObj['contacts'][] = $contact;
    }

    // print_r($params);
    // print_r($reqObj);
    // die();
    //
    // Check changes
    //
    if (count($reqObj['patientData']) == 0 && count($reqObj['observations']) == 0 && count($reqObj['contacts']) == 0 && count($reqObj['contactsDelete']) == 0){
      return $this->redirectToRoute('clinicalProfile');
    }
    $this->hubClient->setAccessToken($user['at']);

    $cliRes = $this->hubClient->clinicalProfileSave($reqObj);
    if (!$cliRes['resolved']){
      $this->logger->error("Profile|clinicalProfileSave| Error requesting user data!!");
      return $this->render('user/profileClinic.html.twig', [
        'country'   => $country,
        'error_msg' => "Ocurrio un error intentando guardar los cambios",
        'countries' => Settings::countries
      ]);
    }else{
      
      $this->get('session')->getFlashBag()->add('info_msg', 'Se guardaron los datos con éxito');

      return $this->redirectToRoute('clinicalProfile');
      // $cliRes = $this->hubClient->getClinicalProfile();
      // if (!$cliRes['resolved']){
      //   $this->logger->error("Profile|clinicalProfileSave| Error requesting user data!!");
      //   return $this->render('user/profileClinic.html.twig', [
      //     'error_msg' => "Ocurrio un error de conexión con el servidor"
      //   ]);
      // }else{
      //   if ($cliRes['status_code'] != 200 && $cliRes['status_code'] != 0){
      //     $this->logger->error("Profile|clinicalProfileSave| Server returned status code ".$cliRes['status_code']." requesting getUserData!!");
      //     return $this->render('user/profileClinic.html.twig', [
      //       'error_msg' => "Ocurrio obteniendo los datos modificados"
      //     ]);
      //   }else{
      //     $patient = $cliRes['data'];
      //     return $this->render('user/profileClinic.html.twig', [
      //       'patient' => $patient
      //     ]);
      //   }
      // }
    }
    
  }

  //
  // API Methods
  //
  public function getFamilyPlanData(Request $request){
    $user       = $this->authCtrl->getUserData($request);
    $country    = Settings::resolveCountry($user, $request);
    $prodsPriceRes = $this->hubClient->getProductsPriceAndStock($country['code']);
    // echo json_encode($prodsPriceRes);
    // die();
    if (!$prodsPriceRes['resolved'] || $prodsPriceRes['status_code'] != '200'){
      // Error getting price
      $this->logger->error("Profile|getFamilyPlanData|Error on hubClient->getProductsPriceAndStock (".$prodsPriceRes['status_code']." | " . json_encode($prodsPriceRes['data']) . ")");
      $response = new JsonResponse(NULL);
      return $response;
    }else{
      $planData = NULL;
      $prices = $prodsPriceRes['data']['items'];
      for($i=0; $i < count($prices); $i++ ){
        if ($prices[$i]['code'] == 'CS-ACT-FAM-SUBS'){
          $planData = $prices[$i];
          break;
        }
      }
      if ($planData){
        // {
        // "code": "CS-ACT-FAM-SUBS",
        // "stock": -1,
        // "orderTo": "G",
        // "invoiceTo": "G",
        // "shipWith": null,
        // "priceList": "Precios Web",
        // "price": 9.99,
        // "currency": "USD"
        // }
        $response = new JsonResponse($planData);
        return $response;
      }else{
        $this->logger->error("Profile|getFamilyPlanData|Product Item CS-ACT-FAM-SUBS NOT FOUND)");
        $response = new JsonResponse(NULL);
        return $response;
      }
    }
  }
}
