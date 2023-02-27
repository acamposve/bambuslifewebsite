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

class ObservationsController extends AbstractController {

  private $hubClient;
  private $logger;
  private $authCtrl;

  public function __construct(HubClient $hubClient, NgageClient $ngageClient, LoggerInterface $logger, AuthController $authCtrl){
    $this->hubClient = $hubClient;
    $this->logger    = $logger;
    $this->authCtrl  = $authCtrl;
  }

  public function observations(Request $request) {
    
    $user = $this->authCtrl->getUserData($request);

    $this->logger->debug("ObservationsController|observations| get observations...");
    $this->hubClient->setAccessToken($user['at']);
    $res = $this->hubClient->getObservations();

    // print_r($res);
    // die();

    if (!$res['resolved']){
      $this->logger->error("ObservationsController|observations| Error requesting user data!!");
      return $this->render('observations/observations.html.twig', [
        'error_msg' => "Ocurrio un error"
      ]);
    }else{
      if ($res['status_code'] != 200 && $res['status_code'] != 0){
        $this->logger->error("ObservationsController|observations| Server returned status code ".$res['status_code']." requesting getUserData!!");
        return $this->render('observations/observations.html.twig', [
          'error_msg' => "Ocurrio un error"
        ]);
      }else{

        \Moment\Moment::setLocale('es_ES');
        $momentDate = NULL;
        for($i=0; $i < count($res['data']); $i++){
          $momentDate = new \Moment\Moment($res['data'][$i]['date'], 'UTC');
          $res['data'][$i]['niceDate'] = $momentDate->calendar();
          $res['data'][$i]['niceDiagCreatedDate'] = NULL;
          if (array_key_exists('diagCreatedDate', $res['data'][$i])){
            if (strlen($res['data'][$i]['diagCreatedDate'])>0){
              $momentDate = new \Moment\Moment($res['data'][$i]['diagCreatedDate'], 'UTC');
              $res['data'][$i]['niceDiagCreatedDate'] = $momentDate->calendar();
            }
          }
          if (array_key_exists('serviceRequestDate', $res['data'][$i])){
            if ($res['data'][$i]['serviceRequestCoding'] == 'BAMBUSLIFE' && $res['data'][$i]['serviceRequestCode'] == 'ECG_DIAGNOSTIC_REPORT'){
              $momentDate = new \Moment\Moment($res['data'][$i]['serviceRequestDate'], 'UTC');
              $res['data'][$i]['niceServiceRequestDate'] = $momentDate->calendar();
            }
          }
        }

        return $this->render('observations/observations.html.twig', ['observations'=>$res['data']]);
      }
    }
  }
  
  public function viewObservationPdf($obsId, $dl, Request $request){
    $user = $this->authCtrl->getUserData($request);
    $res = $this->hubClient->getObservationsEcgPdf($obsId, $user['at']);
    if ($res){
      if ($res['status_code'] != 200) {
        throw new HttpException($res['status_code']);
      }
      $responseFilename = 'ecg'.$user['lastname'].'-'.$obsId.'.pdf';
      $response = new Response(base64_decode($res['data']));
      $response->setContent($res['data']);
      $response->headers->set('Content-Type', 'application/pdf');
      if ($dl == '1'){
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$responseFilename.'"');
      }
      return $response;
    }else{
      return 'REDIRECT!';
    }
  }

  public function viewDiagnosticReportPdf($obsId, $dl, Request $request){
    $user = $this->authCtrl->getUserData($request);
    $res = $this->hubClient->getDiagReportPdf($obsId, $user['at']);
    if ($res){
      if ($res['status_code'] != 200) {
        throw new HttpException($res['status_code']);
      }
      $responseFilename = 'ecgDiagRep-'.$user['lastname'].'-'.$obsId.'.pdf';
      $response = new Response(base64_decode($res['data']));
      $response->setContent($res['data']);
      $response->headers->set('Content-Type', 'application/pdf');
      if ($dl == '1'){
        $response->headers->set('Content-Disposition', 'attachment; filename="'.$responseFilename.'"');
      }
      
      return $response;
    }else{
      return 'REDIRECT!';
    }
  }
  
  public function diagnosticReport($obId,Request $request) {
    $user = $this->authCtrl->getUserData($request);
    $this->hubClient->setAccessToken($user['at']);

    $res = $this->hubClient->getDiagnosticReport($obId);
    if ($res['status_code'] != 200) {
      if ($res['status_code'] == 403) {
        $this->logger->error("ObservationsController|diagnosticReport| Observation " . $obId . " was forbidden for user " . $user['id']);
      }
      return $this->render('observations/diagnosticReport.html.twig', ['error_msg'=>'Ocurrió un error al intentar recuperar los datos desde el servidor.']);
    }
    if (!($res['resolved']) || isset($res['data']['error'])){
      $this->logger->error("ObservationsController|diagnosticReport| Error!! ".json_encode($res));
      $this->logger->error("ObservationsController|diagnosticReport| Error!!");
      return $this->render('user/diagnosticReport.html.twig', ['error_msg'=>'Ocurrió un error al intentar recuperar los datos desde el servidor.']);
    } else {
      return $this->render('observations/diagnosticReport.html.twig', ['diagRep'=>$res['data']]);
    }
  }

  public function requestDiagnosticReport(Request $request, $obsId){
    $session = $this->get('session');
    $session->start();
    $sessionId = $session->getId();
    $user = $this->authCtrl->getUserData($request);
    $this->hubClient->setAccessToken($user['at']);
    $country     = Settings::resolveCountry($user, $request);

    //
    // Get observation
    //
    $res = $this->hubClient->getSimpleObservation($user['patient']['id'], $obsId);
    if (!($res['resolved']) || $res['status_code'] != 200) {
      if ($res['status_code'] == 403) {
        $this->logger->error("ObservationsController|requestDiagnosticReport| Observation " . $obsId . " was forbidden for user " . $user['id']);
      }
      return $this->render('observations/requestDiagRep.html.twig', [
        'error_msg' =>'Ocurrió un error al intentar recuperar los datos desde el servidor.',
        'requested' => FALSE
      ]);
    } else {
      $observations = $res['data'];
      // print_r(json_encode($res));
      // die();
      //
      // Build Form
      //
      $resForm = $this->hubClient->buildFormPatient(1);
      if (!($resForm['resolved']) || $resForm['status_code'] != 200) {
        return $this->render('observations/requestDiagRep.html.twig', [
          'error_msg' =>'Ocurrió un error al intentar recuperar los datos del formulario desde el servidor.',
          'requested' => FALSE
        ]);
      } else {

        //
        // Get Service Price
        //
        $res = $this->hubClient->getServicePrice(Settings::SERVICE_PRODUCT_DIAG_REP_ECG, $country['code']);
        if (!($res['resolved']) || $res['status_code'] != 200) {
          if ($res['status_code'] == 403) {
            $this->logger->error("ObservationsController|requestDiagnosticReport| Error getting service price for DIAG_REP_ECG!");
          }
          return $this->render('observations/requestDiagRep.html.twig', [
            'error_msg' =>'Ocurrió un error al intentar recuperar los datos desde el servidor.',
            'requested' => FALSE
          ]);
        } else {
          $servicePricing = $res['data'];
          $session->set('service_pricing', $servicePricing);
          // print_r(json_encode($res));
          // die();
          //
          // Arrange questions (remove already loaded questions)
          //
          $qForm = $resForm['data'];
          for($i=count($qForm['formData']['groups'])-1; $i >= 0; $i--){
            for($x=count($qForm['formData']['groups'][$i]['questions'])-1; $x >= 0; $x--){
              if (!$qForm['formData']['groups'][$i]['questions'][$x]['showIfValueExists'] &&
                  isset($qForm['formData']['groups'][$i]['questions'][$x]['currentValue']) ){
                unset($qForm['formData']['groups'][$i]['questions'][$x]);
              }
            }
            if (count($qForm['formData']['groups'][$i]['questions']) == 0){
              // Empty questions, remove entire group
              unset($qForm['formData']['groups'][$i]);
            }
          }

          // print_r(json_encode($qForm,JSON_PRETTY_PRINT));
          // die();

          //
          // Arrange Observation list
          //
          $obsIncluded = array();
          $obsIncluded[] = $observations;
          for($i=0; $i < count($obsIncluded); $i++){
            $momentDate = new \Moment\Moment($obsIncluded[$i]['issued'], 'UTC');
            $obsIncluded[$i]['niceDate'] = $momentDate->calendar();
            if ($obsIncluded[$i]['autoeval'] == 'HIGH'){
              $obsIncluded[$i]['niceAutoeval'] = 'Rojo';
            }else if ($obsIncluded[$i]['autoeval'] == 'LOW'){
              $obsIncluded[$i]['niceAutoeval'] = 'Amarillo';
            }else {
              $obsIncluded[$i]['niceAutoeval'] = 'Normal';
            }
          }
        }
      }

      //
      // Store payment info to session
      //
      // $payInfo = array(
      //   'callback'    => "/account/reqDiagRep/".$obsId,
      //   'currency'    => $currency,
      //   'countryCode' => $country['code'],
      //   'amountToPay' => $amountToPay
      // );
      // $session->set('payInfo', $payInfo);

      return $this->render('observations/requestDiagRep.html.twig', [
        'obsIncluded'     => $obsIncluded,
        'requested'       => FALSE,
        'qform'           => $qForm,
        'sid'             => $sessionId,
        'user'            => $user,
        'countryCode'     => $country['code'],
        'servicePricing'  => $servicePricing
        // 'currency'        => $currency,
        // 'baseAmountToPay' => $baseAmountToPay,
        // 'baseCurrency'    => $baseCurrency,
        // 'amountToPay'     => $amountToPay
      ]);
    }
  }

  private function addOrAppendAnswer(&$responses, $responseId, $answer){
    $bFound = FALSE;
    for($i=0; $i < count($responses); $i++){
      if ($responses[$i]['id'] == $responseId){
        $bFound = TRUE;
        $responses[$i]['answers'][] = $answer;
        break;
      }
    }
    if (!$bFound){
      $responses[] = array(
        'id'      => $responseId,
        'answers' => array($answer)
      );
    }
  }

  public function doRequestDiagnosticReport(Request $request){
    $session = $this->get('session');
    // Get user
    $user = $this->authCtrl->getUserData($request);
    if ($user == NULL){
      return $this->redirectToRoute('login', array('r' => $request->getPathInfo()));
    }
    $this->hubClient->setAccessToken($user['at']);
    // Get country 
    $country = Settings::resolveCountry($user, $request);
    // Get currency
    $currency = Settings::get_currency_by_country($country['code']);

    $params = $request->request->all();

    // print_r($params);
    // die();
    //
    // Check payment required
    //
    $paymentData = NULL;
    if (array_key_exists('mptoken', $params)){
      // print_r($params);
      // die();
      //
      // Prepare Service Purchase
      //
      $servicePricing = $session->get('service_pricing');
      // print_r($servicePricing);
      // die();
      $prepareData = array(
        'country'  => $country['code'],
        'currency' => $currency['code'],
        'customer' => $user["email"],
        'items'    => array(
          array(
            'serv_code'   => $servicePricing['sku'],
            'org_id'      => $servicePricing['organizationId'],
            'qty'         => 1,
            'amount'      => $servicePricing['amount']
          )
        )
      );
      $resPrep = $this->hubClient->prepareServiceRequest($prepareData);
      if (!($resPrep['resolved']) || $resPrep['status_code'] != 200) {
        $this->logger->error("ObservationsController|doRequestDiagnosticReport| Error preparing request " . $params['obsId']);
        return $this->render('observations/requestDiagRep.html.twig', [
          'error_msg' =>'Ocurrió un error preparando la solicitud.',
          'requested' => FALSE
        ]);
      }
      // print_r($resPrep);
      // die();
      //
      // Perform payment
      //
      $payInfo = array(
        'amountToPay' => $servicePricing['amount']
      );
      $payResult = Settings::doFastPay($request, $params['mptoken'], $this->logger, $this->authCtrl, $payInfo);
      if ($payResult['status'] == 'APPROVED' || $payResult['status'] == 'IN_PROCESS'){
        // Payment successfully
        $this->get('session')->remove('payInfo');
        $this->logger->info("ObservationsController|doRequestDiagnosticReport| Payment performed successfully! Will continue...");
      }else{
        // Payment failed!
        $this->logger->error("ObservationsController|doRequestDiagnosticReport| Payment failed! ");
        return $this->render('observations/requestDiagRep.html.twig', [
          'error_msg' => $payResult['errMsg'],
          'requested' => FALSE
        ]);
      }

      $paymentData = array(
        'si_num'               => $resPrep['data']['name'],
        'amount'               => $servicePricing['amount'],
        'currency'             => $servicePricing['currency'],
        'gateway'              => $payResult['gateway'],
        'transaction_id'       => $payResult['transaction_id'],
        'issuer_name'          => $payResult['issuer_name'],
        'card_last4'           => $payResult['card_last_four'],
        'card_holder'          => $payResult['card_holder'],
        'statement_descriptor' => $payResult['statement_descriptor'],
        'status'               => $payResult['status'],
        'external_userid'      => $payResult['external_userid']
      );

      // print($resPrep['data']['name']);
      // print("<br><br>------------------------------------<br><br>");
      // print_r($payResult);

    }


    //
    // Load API params
    //
    $data = array(
      'observationId' => $params['obsId'],
      'priority'      => $params['priority'],
      'note'          => $params['comments']
    );
    if ($paymentData){
      $data['paymentData'] = $paymentData;
    }
    //
    // Read questionnaire and load answers meta-data
    //
    if (array_key_exists('questionnaireId', $params)){
      //
      // Build responses
      //
      $qAnswer = array(
        'formId'    => $params['questionnaireId'],
        'responses' => array()
      );
      foreach ($params as $paramKey => $paramVal){
        if (substr($paramKey,0, 2) == 'q^'){
          // Check normal question
          $splittedAnswer = explode("^", $paramKey);
          if (count($splittedAnswer) == 3){
            // Simple value answer
            $answer = array(
              'value'  => $paramVal,
              'coding' => NULL,
              'code'   => NULL
            );
          }else{
            // Choice value answer
            $answer = array(
              'value'  => $splittedAnswer[3],
              'coding' => $splittedAnswer[4],
              'code'   => $splittedAnswer[5]
            );
          }
          $this->addOrAppendAnswer($qAnswer['responses'], $splittedAnswer[2], $answer);
        }else if (substr($paramKey,0, 6) == 'qcond^'){
          // Check condition question
          $splittedAnswer = explode("^", $paramKey);
          $answer = array(
            'value'  => $splittedAnswer[3],
            'coding' => $splittedAnswer[4],
            'code'   => $splittedAnswer[5]
          );
          // Search additional fields for conditions
          foreach ($params as $paramKey2 => $paramVal2){
            if ($paramKey2 == 'qcondsev^'.$splittedAnswer[1].'^'.$splittedAnswer[2]){
              $answer['severity'] = $paramVal2;
            }else if ($paramKey2 == 'qcondstart^'.$splittedAnswer[1].'^'.$splittedAnswer[2]){
              $answer['onsetDatetime'] = $paramVal2;
            }else if ($paramKey2 == 'qcondend^'.$splittedAnswer[1].'^'.$splittedAnswer[2]){
              $answer['abatementDatetime'] = $paramVal2;
            }
          }
          $this->addOrAppendAnswer($qAnswer['responses'], $splittedAnswer[2], $answer);
        }
      }

      /**
       * 
       * [qcond^1^4^1^SNOMED-CT^111] => on
       * [qcondsev^1^4] => MILD
       * [qcondstart^1^4] => 2010-11-06
       * [qcond^1^5^1^SNOMED-CT^222] => on
       * [qcondstart^1^5] => 2010-10-29
       * [q^1^6^^SNOMED-CT^161915001] => on
       * [q^1^6^^SNOMED-CT^161972006] => on
       * [q^1^6^^SNOMED-CT^287045000] => on
       */

      // print_r($qAnswer);
      // die();

      //
      // Check extra data from questionnaire questions
      //
      $resForm = $this->hubClient->buildFormPatient(intval($params['questionnaireId']));
      if (!($resForm['resolved']) || $resForm['status_code'] != 200) {
        return $this->render('observations/requestDiagRep.html.twig', [
          'error_msg' =>'Ocurrió un error al intentar recuperar los datos del formulario desde el servidor.',
          'requested' => FALSE
        ]);
      } else {
        $qForm = $resForm['data'];
        // print_r($qForm);
        // die();

        $bQuestionFound = FALSE;
        for($i=0; $i < count($qAnswer['responses']); $i++){
          for($x=0; $x < count($qForm['formData']['groups']); $x++){
            $bQuestionFound = FALSE;
            for($y=0; $y < count($qForm['formData']['groups'][$x]['questions']); $y++){
              if ($qForm['formData']['groups'][$x]['questions'][$y]['id'] == $qAnswer['responses'][$i]['id']){
                $qAnswer['responses'][$i]['questionCoding']        = $qForm['formData']['groups'][$x]['questions'][$y]['coding'];
                $qAnswer['responses'][$i]['questionCode']          = $qForm['formData']['groups'][$x]['questions'][$y]['code'];
                $qAnswer['responses'][$i]['questionText']          = $qForm['formData']['groups'][$x]['questions'][$y]['text'];
                $qAnswer['responses'][$i]['questionType']          = $qForm['formData']['groups'][$x]['questions'][$y]['type'];
                $qAnswer['responses'][$i]['questionShowInSummary'] = $qForm['formData']['groups'][$x]['questions'][$y]['showInSummary'];
                $bQuestionFound = TRUE;
                break;
              }
            }
            if ($bQuestionFound){
              break;
            }
          }
        }
        // echo(json_encode($qAnswer, JSON_PRETTY_PRINT));
        // die();
        $data['questionnaire'] = $qAnswer;
      }
    }

    //
    // REquest serviceRequest at API
    //
    $res = $this->hubClient->requestServiceDiagRep($data);
    //$res['resolved'] = TRUE;
    if (!($res['resolved']) || $res['status_code'] != 200) {
      $this->logger->error("ObservationsController|doRequestDiagnosticReport| Error requesting service for obsId " . $params['obsId']);
      return $this->render('observations/requestDiagRep.html.twig', [
        'error_msg' =>'Ocurrió un error al intentar enviar la solicitud.',
        'requested' => FALSE
      ]);
    } else {
      return $this->render('observations/requestDiagRep.html.twig', [
        'obsIncluded' => NULL,
        'requested'   => TRUE
      ]);
    }
  }

}

