<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Psr\Log\LoggerInterface;

use App\Controller\AuthController;
use App\Service\HubClient;
use App\Utils\Settings;

// Third Party Payment
use MercadoPago;


class CheckoutController extends AbstractController {

  private $logger;
  private $authCtrl;
  private $hubClient;

  public function __construct(AuthController $authCtrl, HubClient $hubClient, LoggerInterface $logger){
    $this->logger    = $logger;
    $this->authCtrl  = $authCtrl;
    $this->hubClient = $hubClient;
  }

  // 
  // STEP 1 | User info 
  // 
  public function userInfo(Request $request){
    // Check if user is logged in
    $user = $this->authCtrl->getUserData($request);
    $navCountry = strtoupper($request->getLocale());
    
    // Check CS Pro Warning
    // if (!$request->query->has('wpa')){
    //   $items = $this->get('session')->get('checkout_cart_items'); // ACAA
    //   $proPresent = FALSE;
    //   for($i=0; $i < count($items); $i++){
    //     if (substr($items[$i]['code'],0,6) == 'CS-PRO'){
    //       $proPresent = TRUE;
    //     }
    //   }
    
    //   if ($proPresent) {
    //     $cities = $this->hubClient->getCityByCountryCode('UY');
    //     $navCountry = strtoupper($request->getLocale());
    //     return $this->render('checkout/warning-pro.html.twig', [
    //       'navCountry'            => $navCountry,
    //       'countries'            => Settings::countries,
    //       'cities'                => $cities['data'],
    //       'user' => $user
    //     ]);
    //   }
    // }
    // Check CS Act Warning
    // if (!$request->query->has('waa')){
    //   $items = $this->get('session')->get('checkout_cart_items');
    //   $missingECGs = 0;
    //   for($i=0; $i < count($items); $i++){
    //     $preCode = substr($items[$i]['code'],0,6);
    //     if ($preCode == 'CS-ACT'){
    //       if ($items[$i]['interpretation_code'] == NULL){
    //         $missingECGs += $items[$i]['qty'];
    //       }
    //     }
    //   }
    
    //   if ($missingECGs > 0) {
    //     // Get price and offer to add it 
    //     if ($user != NULL){
    //       $countryCode = $user['countryCode'];
    //     }else{
    //       $countryCode = '';
    //     }
    //     $prodsPriceRes = $this->hubClient->getProductsPriceAndStock($countryCode);
    //     if (!$prodsPriceRes['resolved'] || $prodsPriceRes['status_code'] != '200'){
    //       // Error getting price
    //       $this->logger->error("CheckoutCtrl|userInfo|Error on hubClient->getProductsPriceAndStock (".$prodsPriceRes['status_code']." | " . json_encode($prodsPriceRes['data']) . ")");
    //     }else{
    //       $prices = $prodsPriceRes['data'];
    //       $ecgPrice = -1;
    //       $ecgCurrency = '';
    //       for($i=0; $i < count($prices); $i++ ){
    //         if ($prices[$i]['code'] == 'CS-ECG-INTER'){
    //           $ecgPrice    =  $prices[$i]['price'];
    //           $ecgCurrency = $prices[$i]['currency'];
    //           break;
    //         }
    //       }
    //       $totalEcgPrice = $missingECGs * $ecgPrice;
    //       return $this->render('checkout/warning-act.html.twig', [
    //         'user'             => $user,
    //         'missingECGs'      => $missingECGs,
    //         'totalEcgPrice'    => $totalEcgPrice,
    //         'totalEcgCurrency' => $ecgCurrency
    //       ]);
    //     }
    //   }
    // }
    // Check add interpretation to missing ones request
    if ($request->query->has('ad')){
      if ($request->query->get('ad') == '1'){
        $items = $this->get('session')->get('checkout_cart_items');
        $interAdded = 0;
        for($i=0; $i < count($items); $i++){
          $preCode = substr($items[$i]['code'],0,6);
          if ($preCode == 'CS-ACT'){
            if ($items[$i]['interpretation_code'] == NULL){
              $items[$i]['interpretation_code'] = 'CS-ECG-INTER';
              $interAdded += $items[$i]['qty'];
            }
          }
        }
        // $info_msg = "Se agregaron interpretaciones de electros a los dispositivos faltantes.";
        // $error_msg = "";
        // return $this->render('order/cartView.html.twig', array(
        //   'error_msg'         => $error_msg,
        //   'info_msg'          => $info_msg,
        //   'details'           => $calcRes['data'],
        //   'amountToPay'       => $amountToPay
        // ));
        if ($interAdded == 1){
          $this->get('session')->getFlashBag()->add('info_msg', 'Se agreg&oacute; '.$interAdded.' interpretaci&oacute;n de electrocardiograma con &eacute;xito.');
        }else if ($interAdded > 1){
          $this->get('session')->getFlashBag()->add('info_msg', 'Se agregaron '.$interAdded.' interpretaciones de electrocardiograma con &eacute;xito.');
        }
        $this->get('session')->set('checkout_cart_items', $items);
        return $this->redirectToRoute('cart_view');
      }
    }

    return $this->render('checkout/s1-UserInfo.html.twig', [
      'user'       => $user,
      'navCountry' => $navCountry,
      'countries'  => Settings::countries
    ]);
  }

  public function userInfoPost($mode, Request $request){
    $error_msg = NULL;
    $user = NULL;
    $bag = $request->request;
    $navCountry = strtoupper($request->getLocale());

    // print_r($bag);
    // die();

    //
    // Check form action ($mode)
    //   0 => Show form with selected
    //   1 => Login
    //   2 => Register
    //   3 => Continue with current user
    //   4 => Change User
    if ($mode == '0'){
      $user = $this->authCtrl->getUserData($request);

      // TODO: Validate

      // Build cart items
      $cart_items = array(
        array(
          'code' => $bag->get('csactive_os'),
          'qty'  => 2
        )
      );

      // TODO: Add partner device????
      //$bag->get('partner_os');

      $this->get('session')->set('checkout_cart_items', $cart_items);

      return $this->render('checkout/s1-UserInfo.html.twig', [
        'user'      => $user,
        'error_msg' => '',
        'countries' => Settings::countries
      ]);

    }else if ($mode == '4'){

      // Change user
      $this->authCtrl->logout();
      return $this->render('checkout/s1-UserInfo.html.twig', [
        'user'      => NULL,
        'countries' => Settings::countries,
        'navCountry' => $navCountry,
      ]);

    }else if ($mode == '3'){
      
      // Continue with logged user 
      $user = $this->authCtrl->getUserData($request);
      return $this->redirectToRoute('checkout_patientinfo');


    }else if ($mode == '1'){
      // Process Login
      $email     = $bag->get('loginEmail');
      $password  = $bag->get('loginPassword'); 
    }else if ($mode == '2'){
      // Process register
      $this->logger->debug('CheckoutCtrl|userInfoPost|Processing registration for email ' . $bag->get('email'));
      $email    = $bag->get('email'); 
      $password = $bag->get('password');
      $userData = array(
        "username"     => $bag->get('firstname').' '.$bag->get('lastname'),
        "firstname"    => $bag->get('firstname'),
        "lastname"     => $bag->get('lastname'),
        "email"        => $email,
        "password"     => $password,
        "countryCode"  => $bag->get('regcountry'),
        "countryName"  => Settings::countries[strtolower($bag->get('regcountry'))]['name'],
        "address"      => array(
          "cityId"        => $bag->get('cityId'),
          "cityName"      => $bag->get('cityName'),
          "address_line1" => $bag->get('address'),
          "phone"         => $bag->get('phonenumber')
        ),
        "documentType" => $bag->get("customerDocumentType"),
        "documentId"   => $bag->get("customerDocumentId")
      );
      
      // Check business data for invoicing
      if ($bag->get('invoiceAsCompany') == 'on'){
        $userData['invoiceCompany'] = array(
          "name"    => $bag->get('invoiceCompanyName'),
          "number"  => $bag->get('invoiceCompanyRUT'),
          "address" => $bag->get('invoiceCompanyAddress'),
          "state"   => $bag->get('invoiceCompanyState'),
          "city"    => $bag->get('invoiceCompanyCity')
        );
      }

      // print_r($userData);
      // die();

      $res = $this->hubClient->upsertUser($userData);
      if ($res['resolved']){
        if ($res['status_code'] != 200){
          // ERROR
          $this->logger->error("CheckoutCtrl|userInfoPost|Error on hubClient->register (".$res['status_code']." | " . json_encode($res['data']) . ")");
          $error_msg = "No fue posible procesar el registro en este momento. (" . $res['status_code'] . ")";
          if (array_key_exists('error', $res['data'])){
            if (array_key_exists('message', $res['data']['error'])){
              if (strpos($res['data']['error']['message'], "Email already exists")){
                $error_msg = "El email ya esta registrado.";
              }
            }
          }
          return $this->render('checkout/s1-UserInfo.html.twig', [
            'user'       => $user,
            'error_msg'  => $error_msg,
            'navCountry' => $navCountry,
            'countries'  => Settings::countries
          ]);
        }
      }else{
        // ERROR
        $this->logger->error("CheckoutCtrl|userInfoPost|Error on hubClient->register (Exception)");
        $error_msg = "No fue posible procesar el registro en este momento. <br>(General Exception)";
        return $this->render('checkout/s1-UserInfo.html.twig', [
          'user'      => $user,
          'navCountry' => $navCountry,
          'error_msg' => $error_msg,
          'countries' => Settings::countries
        ]);
      }
    }

    // Do Login
    $this->logger->debug('CheckoutCtrl|userInfoPost|Processing login for email ' . $bag->get('email'));
    $loginRes = $this->authCtrl->__login($request, $email, $password);

    if (!$loginRes['resolved']){
      // Error logging in
      if($loginRes['msg'] == "Las credenciales no son correctas") {
        $error_msg = "Las credenciales no son correctas.";
      }else {
        $error_msg = "Ocurri&oacute; un problema iniciando sesi&oacute;n.";
      }
      $response = new Response($this->renderView('checkout/s1-UserInfo.html.twig', [
        'user'       => NULL,
        'error_msg'  => $error_msg,
        'countries'  => Settings::countries,
        'navCountry' => $navCountry,
      ]), 200);
      // Check DT new cookie
      if ($loginRes['saveDT_Token']){
        $cookieDT  = Cookie::create('bl_dt', $loginRes['dt'], time() + (10 * 365 * 24 * 60 * 60));
        $response->headers->setCookie($cookieDT);
      }
      return $response;

    }else{
      $at = $loginRes['data']['id'];

      // Preapre Response (setting the locale according to user country)
      $response = $this->redirectToRoute('checkout_patientinfo', array('_locale'=> strtolower($loginRes['data']['user']['countryCode'])));

      // Check DT new cookie
      if ($loginRes['saveDT_Token']){
        $cookieDT  = Cookie::create('bl_dt', $loginRes['dt'], time() + (10 * 365 * 24 * 60 * 60));
        $response->headers->setCookie($cookieDT);
      }
      
      $this->hubClient->setAccessToken($at);
      $cliRes = $this->hubClient->getUserData();

      // print_r($cliRes);
      // die();

      if (!$cliRes['resolved']){
        $this->logger->error("AuthController|doLogin|Error requesting user data!!");
        return $this->render('checkout/s1-UserInfo.html.twig', [
          'user'       => NULL,
          'error_msg'  => "Ocurrió un problema con el registro. Intente iniciar sesión o de lo contrario volver a registrarse.",
          'navCountry' => $navCountry,
          'countries'  => Settings::countries
        ]);
      }else{
        if ($cliRes['status_code'] != 200 && $cliRes['status_code'] != 0){
          $this->logger->error("AuthController|doLogin|Server returned status code ".$cliRes['status_code']." requesting getUserData!!");
          return $this->render('checkout/s1-UserInfo.html.twig', [
            'user'       => NULL,
            'error_msg'  => "Ocurrió un problema con el registro. Intente iniciar sesión o de lo contrario volver a registrarse.",
            'navCountry' => $navCountry,
            'countries'  => Settings::countries
          ]);
        }else{
          // Store user data in session
          $cliRes['data']['at'] = $at;
          $this->get('session')->set('current_user', $cliRes['data']);
        }
      }

      return $response;
    }

  }



  // 
  // STEP 2 | Patient info 
  // 
  public function patientInfo(Request $request){
    $navCountry = strtoupper($request->getLocale());

    // Check if user is logged in
    $user = $this->authCtrl->getUserData($request);

    if ($user == NULL){
      // Go back to step 1 to select user
      return $this->render('checkout/s1-UserInfo.html.twig', array(
        'countries'  => Settings::countries,
        'navCountry' => $navCountry
      ));
    }else{
      // Check ongoing checkout
      if ($this->get('session')->has('checkout_cart_patient')){
        // Load patient data
        $patient = $this->get('session')->get('checkout_cart_patient');
      }else{
        // Create patient data
        $patient = [
          'forOther'     => FALSE,
          'firstname'    => $user['firstname'],
          'lastname'     => $user['lastname'],
          'email'        => $user['email'],
          'phone'        => '',
          'countryName'  => $user['countryName'],
          'countryCode'  => $user['countryCode'],
          'birthdate'    => '',
          'gender'       => 'female',
          'weight'       => '',
          'height'       => ''
        ];
        // $splittedUsername = explode(' ', $user['username']);
        // $patient['firstname'] = $splittedUsername[0];
        // if (count($splittedUsername) > 1){
        //   $patient['lastname'] = $splittedUsername[1];
        // }else{
        //   $patient['lastname'] = '';
        // }
      }

      //
      // Check prescription need
      //
      // WARNING:
      // HARDCODED: ONLY FOR URUGUAY FOR NOW!
      // REPLACE WITH HUB SERVER CALL LATER!!
      // 
      if (false){
        return $this->render('checkout/s2-PatientInfo.html.twig', [
          'user'      => $user,
          'patient'   => $patient
        ]);
      }else{
        $this->get('session')->set('checkout_cart_patient', $patient);
        $response = $this->redirectToRoute('checkout_shipping');
        return $response;
      }
    }
  }

  public function patientInfoPost(Request $request){
    $error_msg = NULL;
    $user = $this->authCtrl->getUserData($request);
    $bag = $request->request;

    // Check if same user as patient
    if ($bag->get('forOther')){
      // Create new user and new patient
    }else{
      // Create new patient for current user
      $forOther = $bag->get('forOther');
      $patient = [
        'forOther'     => $forOther,
        'firstname'    => $bag->get('firstname'),
        'lastname'     => $bag->get('lastname'),
        'email'        => $user['email'],
        'phone'        => $bag->get('phonenumber'),
        'birthdate'    => $bag->get('birthdate'),
        'gender'       => $bag->get('gender'),
        'weight'       => $bag->get('weight'),
        'height'       => $bag->get('height')
      ];

      if ($forOther){
        $patient['countryName'] = '';
        $patient['countryCode'] = $bag->get('countryCode');
      }else{
        $patient['countryName'] = $user['countryName'];
        $patient['countryCode'] = $user['countryCode'];
      }

      $password    = $bag->get('password'); 
      $userId      = $user['id'];
      
    }

        
    $data = $request->request->get('resume');
    if($_FILES['resume']['name'] == "") {
      $response = new Response($this->renderView('checkout/s2-PatientInfo.html.twig', [
        'user'      => $user,
        'patient'   => $patient,
        'error_msg' => 'Por cuestiones regulatorias de su pais, es necesario que nos envie la receta medica donde le prescriben el uso del dispositivo.'
      ]), 200);
      return $response;
    }

    // Store in session
    $this->get('session')->set('checkout_cart_patient', $patient);

    // Check if prescription is here
    $this->get('session')->remove('patient_prescription_file');
    if (count($_FILES) && array_key_exists("resume", $_FILES) && !$_FILES["resume"]["error"]) {
      $this->logger->debug(sprintf("CheckoutCtrl|patientInfoPost|A prescription has been submitted.\n%s", json_encode($_FILES)));

      // Check file mimes
      $mimeType = mime_content_type($_FILES['resume']['tmp_name']);
      $allowedMimes = array('image/jpeg', 'image/png', 'application/pdf');
      if (!in_array($mimeType, $allowedMimes)) {
        $this->logger->debug(sprintf("CheckoutCtrl|patientInfoPost|mime/type not allowed. %s", $mimeType));
        $error_msg = "El archivo de prescripcion debe ser una imagen o documento pdf.";
        $response = new Response($this->renderView('checkout/s2-PatientInfo.html.twig', [
          'user'      => $user,
          'patient'   => $patient,
          'error_msg' => $error_msg
        ]), 200);
        return $response;
      };


      $prescriptionUploadRes = $this->hubClient->uploadPrescriptionFile($_FILES["resume"]);
      if (!$prescriptionUploadRes['resolved'] || $prescriptionUploadRes['status_code'] != '200') {
        // Error uploading the prescription file
        $this->logger->error("CheckoutCtrl|patientInfoPost|Error on hubClient->uploadPrescriptionFile (".$prescriptionUploadRes['status_code']." | " . json_encode($prescriptionUploadRes['data']) . ")");
        $error_msg = "Ocurri&oacute; un problema subiendo el archivo de prescripcion";
        $response = new Response($this->renderView('checkout/s2-PatientInfo.html.twig', [
          'user'      => $user,
          'patient'   => $patient,
          'error_msg' => $error_msg
        ]), 200);
        return $response;
      }

      $this->get('session')->set('patient_prescription_file', $prescriptionUploadRes["data"]["fileName"]);
    }

    // 
    // NOTE: PATIENT INFO MOVED TO DEVICES PAGE
    // 
    // $this->logger->debug(sprintf("CheckoutCtrl|patientInfoPost|Upserting patient for %s-%s userid %s...", $patient['firstname'], $patient['lastname'], $userId));
    // $upsertRes = $this->hubClient->upsertPatient($patient['firstname'], $patient['lastname'], $patient['birthdate'], $patient['email'], $patient['phone'], $patient['gender'], $patient['weight'], $patient['height'], $patient['countryCode'], $userId);
    // if (!$upsertRes['resolved'] || $upsertRes['status_code'] != '200'){
    //   // Error logging in
    //   $this->logger->error("CheckoutCtrl|patientInfoPost|Error on hubClient->upsertPatient (".$upsertRes['status_code']." | " . json_encode($upsertRes['data']) . ")");
    //   $error_msg = "Ocurri&oacute; un problema creando registro clinico.";
    //   $response = new Response($this->renderView('checkout/s2-PatientInfo.html.twig', [
    //     'user'      => $user,
    //     'patient'   => $patient,
    //     'error_msg' => $error_msg
    //   ]), 200);
    //   return $response;
    // }else{
    //   $response = $this->redirectToRoute('checkout_shipping');
    //   return $response;
    // }
    $response = $this->redirectToRoute('checkout_shipping');
    return $response;
  }



  //
  // Step 3 | Shipping Info
  //
  public function shippingInfo(Request $request){
    $session     = $this->get('session');
    $user        = $this->authCtrl->getUserData($request);
    $error_msg   = $session->getFlashBag()->get('error_msg', []);
    $country     = Settings::resolveCountry($user, $request);
    $currentPath = $request->getPathInfo();
    
    // Check CS Pro Warning
    if (!$request->query->has('wpa')){
      $items = $this->get('session')->get('checkout_cart_items'); // ACAA
      $proPresent = FALSE;
      for($i=0; $i < count($items); $i++){
        if (substr($items[$i]['code'],0,6) == 'CS-PRO'){
          $proPresent = TRUE;
        }
      }

      $user = $this->authCtrl->getUserData($request);
      $hasOrganization = FALSE;
      if($user['organization'] && $user['organization']['alias']!='BLUY') {
        $hasOrganization = TRUE;
      }
    
      if ($proPresent) {

        // $cities = $this->hubClient->getCityByCountryCode('UY');
        $navCountry = strtoupper($request->getLocale());
        return $this->render('checkout/warning-pro.html.twig', [
          'hasOrganization'       => $hasOrganization,
          'organization'=>json_encode($user['organization']),
          'navCountry'            => $navCountry,
          'countries'            => Settings::countries,
          // 'cities'                => $cities['data'],
          'user' => $user
        ]);
      }
    }


    //$this->logger->debug("-----------------> ERROR MSG:".$error_msg);

    // Check ongoing checkout
    if (!$session->has('checkout_cart_patient')){
      return $this->redirectToRoute('checkout_patientinfo');
    }else{
      $patient = $session->get('checkout_cart_patient');
      $items = $session->get('checkout_cart_items');

      if (!isset($items) || $items == NULL || count($items) == 0){
        return $this->redirectToRoute('cart_view');
      }

      $this->hubClient->setAccessToken($user['at']);
      $getDeliveryOptionsData = array(
        'country' => $country['code'],
        'items'   => $items
      );
      $res = $this->hubClient->getDeliveryOptions($getDeliveryOptionsData);
      if ($res['resolved']){
        if ($res['status_code'] != 200){
          // ERROR
          $this->logger->error("CheckoutCtrl|shippingInfo|Error on hubClient->getUserAddresses (".$res['status_code']." | " . json_encode($res['data']) . ")");
          return $this->render('checkout/s3-ShippingInfo.html.twig', [
            'currentPath' => $currentPath,
            'country'     => $country,
            // 'shipping_data' => NULL,
            'error_msg'   => ['Ocurri&oacute; un problema obteniendo información de entregas.']
          ]);
        }else{
          //
          // Show delivery options
          //
          // print_r($res);
          // die();

          if ($session->has('checkout_cart_shipping')){
            // Load shipping data
            $shipping_data = $session->get('checkout_cart_shipping');
          }else{
            // Create shipping data
            $shipping_data = [
              'name'        => '',
              'address'     => '',
              'countryName' => $patient['countryName'],
              'countryCode' => $patient['countryCode'],
              'city'        => '',
              'postalcode'  => '',
              'phone'       => ''
            ];
          }
          return $this->render('checkout/s3-ShippingInfo.html.twig', [
            'currentPath'     => $currentPath,
            'deliveryOptions' => $res['data'],
            'country'         => $country,
            // 'shipping_data' => $shipping_data,
            'error_msg'       => $error_msg
          ]);
        }
      }else{
        $this->logger->error("CheckoutCtrl|shippingInfo|Error on hubClient->getUserAddresses (".$res['status_code']." | " . json_encode($res['data']) . ")");
        return $this->render('checkout/s3-ShippingInfo.html.twig', [
          'currentPath' => $currentPath,
          'country'     => $country,
          // 'shipping_data' => NULL,
          'error_msg'   => ['Ocurri&oacute; un problema obteniendo información de entregas [2]']
        ]);
      }
    }

  }

  public function shippingInfoPost(Request $request){
    $session = $this->get('session');
    $patient = $session->get('checkout_cart_patient');
    $user    = $this->authCtrl->getUserData($request);
    $bag     = $request->request;

    // print_r($bag);
    // die();

    $shipping_data = array(
      'name'   => NULL,
      'method' => NULL
    );
    // Check delivery option
    $deliveryOption = $bag->get('delivery_option');
    if ($deliveryOption == "0"){
      // Pickup
      $shipping_data['name']   = $bag->get('pickup_option');
      $shipping_data['method'] = 'Pickup';
    }else if ($deliveryOption == "1"){
      // Delivery
      $shipping_data['method'] = 'Delivery';
      $deliveryAddress = $bag->get('delivery_address');
      if ($deliveryAddress == "__new__"){
        // Must create address before 
        $countryName = Settings::countries[strtolower($bag->get('countryCode'))]['name'];
        $newAddressData = [
          'address_recipient' => $bag->get('fullname'),
          'address_line1'     => $bag->get('address'),
          'country'           => $countryName,
          'city'              => $bag->get('city'),
          'pincode'           => $bag->get('postalCode'),
          'phone'             => $bag->get('phone')
        ];
        $this->hubClient->setAccessToken($user['at']);
        $newAddressRes = $this->hubClient->addShippingAddress($newAddressData);
        if (!$newAddressRes['resolved'] || $newAddressRes['status_code'] != 200){
          // Something wrong happened
          $this->logger->error("CheckoutCtrl|shippingInfoPost|Error on hubClient->addShippingAddress (".$newAddressRes['status_code']." | " . json_encode($newAddressRes['data']) . ")");
          $session->getFlashBag()->add('error_msg', 'Ocurri&oacute; un problema procesando los datos de entrega.');
          $response = $this->redirectToRoute('checkout_shipping');
          return $response;
        }else{
          $shipping_data['name']   = $newAddressRes['data']['name'];
        }
      }else{
        $shipping_data['name']   = $deliveryAddress;
      }
    }
    
    // Store shipping info
    // $shipping_data = [
    //   'name'        => $bag->get('fullname'),
    //   'address'     => $bag->get('address'),
    //   'countryName' => $patient['countryName'],
    //   'countryCode' => $patient['countryCode'],
    //   'city'        => $bag->get('city'),
    //   'postalcode'  => $bag->get('postalCode'),
    //   'phone'       => $bag->get('phone')
    // ];

    // print_r($shipping_data);
    // die();

    $this->get('session')->set('checkout_cart_shipping', $shipping_data);
    // Move next
    $response = $this->redirectToRoute('checkout_pay');
    return $response;
  }



  //
  // Step 4 | Payment
  //
  public function payInfo(Request $request){
    $session = $this->get('session');
    $session->start();
    return $this->doPayInfo($request, $session);
  }

  public function checkCoupon(Request $request){
    $session = $this->get('session');
    $session->start();
    // Check coupon
    $bag = $request->request;
    // print_r($bag);
    // die();
    //
    // Check coupon type
    //
    if ($bag->get('beneficiary') == 'OTHERS'){
      $couponCode = $bag->get('discBambusCouponCode');
      $discount = array(
        'couponType'  => 'KEYWORD',
        'couponValue' => $couponCode,
        'couponData'  => NULL
      );
      $session->set('checkout_cart_discount', $discount);
    }else if ($bag->get('beneficiary') == 'ANTEL'){
      $discount = array(
        'couponType'  => 'ANTEL',
        'couponValue' => NULL,
        'couponData'  => array(
          'phonenum'  => $bag->get('discAntelCelnum'),
          'code'      => $bag->get('discAntelDiscCode')
        )
      );
      $session->set('checkout_cart_discount', $discount);
    }else if ($bag->get('beneficiary') == 'CEP'){
      $discount = array(
        'couponType'  => 'CEP',
        'couponValue' => NULL,
        'couponData'  => array(
          'ciNum'     => $bag->get('discCEPCI')
        )
      );
      $session->set('checkout_cart_discount', $discount);
    }
    return $this->redirectToRoute('checkout_pay');
    //return $this->doPayInfo($request, $session);
  }

  private function doPayInfo(Request $request, $session){
    $sessionId   = $session->getId();
    $user        = $this->authCtrl->getUserData($request);
    $error_msg   = implode('', $session->getFlashBag()->get('error_msg', []));
    $success_msg = implode('', $session->getFlashBag()->get('success_msg', []));
    $country     = Settings::resolveCountry($user, $request);
    $navCountry  = strtoupper($request->getLocale());

    // Get Items
    $items = $session->get('checkout_cart_items');

    // Get coupon
    $discount = $session->get('checkout_cart_discount');

    // Get Shipping option
    $cartShippingAddress = $session->get('checkout_cart_shipping');

    // Set currency
    $currency = Settings::get_currency_by_country($country['code']);

    // Calculate Payment details
    $cartDetails = array(
      'user'           => $user,
      'items'          => $items,
      'shippingOption' => $cartShippingAddress,
      'currency'       => $currency['code'],
      'countryCode'    => $country['code'],
      'discount'       => $discount,
    );

    $this->logger->debug('CheckoutCtrl|payInfo|Calculating items total with hubClient->calculateCart...');
    $calcRes = $this->hubClient->calculateCart($cartDetails);

    if (!$calcRes['resolved'] || $calcRes['status_code'] != 200){
      // Something wrong happened
      $this->logger->error("CheckoutCtrl|payInfo|Error on hubClient->calculateCart (".$calcRes['status_code']." | " . json_encode($calcRes['data']) . ")");
      $session->getFlashBag()->add('error_msg', 'Ocurri&oacute; un problema procesando la orden.');
      $response = $this->redirectToRoute('checkout_shipping');
      return $response;
    }else{
      // Store prices to use on payment
      $session->set('checkout_cart_prices', $calcRes['data']);

      // print_r($calcRes['data']);
      // die();

      // ATTENTION: 
      //    USING price instead of rounded_price to charge without rounding
      $amountToPay = $currency['code'] == "USD" ? $calcRes['data']['grand_total']['price'] : $calcRes['data']['local_grand_total']['rounded_price'];
      //$amountToPay = $currency['code'] == "USD" ? $calcRes['data']['grand_total']['price'] : $calcRes['data']['local_grand_total']['price'];

      $payment = array(
        'currency'      => $currency['code'],
        'amount'        => $amountToPay,
        'exchange_rate' => $currency['code'] == "USD" ? 1 : $calcRes['data']['local_grand_total']['exchange_rate']
      );
      $session->set('checkout_cart_payment', $payment);

      // print_r($calcRes['data']);
      // die();

      // Get shipping address info
      $shippingAddressRes = $this->hubClient->getShippingAddress($cartShippingAddress['name']);
      if (!$shippingAddressRes['resolved'] || $shippingAddressRes['status_code'] != 200){
        // Something wrong happened
        $this->logger->error("CheckoutCtrl|payInfo|Error on hubClient->calculateCart (".$shippingAddressRes['status_code']." | " . json_encode($shippingAddressRes['data']) . ")");
        $session->getFlashBag()->add('error_msg', 'Ocurri&oacute; un problema procesando la orden [22].');
        $response = $this->redirectToRoute('checkout_shipping');
        return $response;
      }

      // print_r($shippingAddressRes['data']);
      // die();
      // print_r($currency);
      // die();

      // Move next
      return $this->render('checkout/s4-PaymentInfo.html.twig', array(
        'error_msg'         => $error_msg,
        'success_msg'       => $success_msg,
        'details'           => $calcRes['data'],
        'amountToPay'       => $amountToPay,
        'currency'          => $currency,
        'countryCode'       => $country['code'],
        'navCountry'        => $navCountry,
        'sid'               => $sessionId,
        'user'              => $user,
        'shippingAddress'   => $shippingAddressRes['data']
      ));
    }
  }

  public function processPayment($sid, Request $request) {
    $this->logger->info(sprintf("CheckoutCtrl|processPayment| Recovering session data with id %s...", $sid));
    // Ensure sessionId from previous checkout step
    $session = $this->get('session');
    $session->setId($sid);
    $session->start();

    // Get session cart data
    $user             = $this->authCtrl->getUserData($request);
    $cartItems        = $session->get('checkout_cart_items');
    $calcRes          = $session->get('checkout_cart_prices');
    $shipping         = $session->get('checkout_cart_shipping');
    $paymentAmount    = $session->get('checkout_cart_payment');
    $prescriptionFile = $session->get('patient_prescription_file');
    $discount         = $session->get('checkout_cart_discount');
    $country          = Settings::resolveCountry($user, $request);

    //
    // Setup MP
    //
    $mpKeys = Settings::getMPKeys($country['code']);

    // $this->logger->info(sprintf("CheckoutCtrl|processPayment| -----------------------------"));
    // $this->logger->info(sprintf("CheckoutCtrl|processPayment| CLIENT_ID     : " . $mpKeys['CLIENT_ID']));
    // $this->logger->info(sprintf("CheckoutCtrl|processPayment| CLIENT_SECRET : " . $mpKeys['CLIENT_SECRET']));
    // $this->logger->info(sprintf("CheckoutCtrl|processPayment| PRIVATE_KEY   : " . $mpKeys['PRIVATE_KEY']));
    // $this->logger->info(sprintf("CheckoutCtrl|processPayment| -----------------------------"));

    MercadoPago\SDK::setClientId($mpKeys['CLIENT_ID']);
    MercadoPago\SDK::setClientSecret($mpKeys['CLIENT_SECRET']);
    MercadoPago\SDK::setAccessToken($mpKeys["PRIVATE_KEY"]);

    $this->logger->info(sprintf("CheckoutCtrl|processPayment| Start processing order payment for %s...", $user["email"]));

    //
    // STEP 1 | Prepare order at ERP
    //
    $this->logger->info(sprintf("CheckoutCtrl|processPayment|1| Start processing order payment for %s...", $user["email"]));
    $preparePurchaseRes = $this->hubClient->prepareOrder($country['code'], $user, $paymentAmount, $cartItems, $shipping, $discount, $prescriptionFile);
    if (!$preparePurchaseRes['resolved'] || $preparePurchaseRes['status_code'] != '200'){
      $this->logger->error("CheckoutCtrl|processPayment|1|Error preparing order (".$preparePurchaseRes['status_code']." | " . json_encode($preparePurchaseRes['data']) . ")");
      //
      // Check special error message 
      //
      $userErrorMsg = 'No se ha prodido procesar la orden este momento. Por favor int&eacute;ntelo m&aacute;s tarde.';
      if (array_key_exists('error', $preparePurchaseRes['data'])){
        if (array_key_exists('message', $preparePurchaseRes['data']['error'])){
          $userErrorMsg = 'No se ha prodido procesar la orden. ' . $preparePurchaseRes['data']['error']['message'];
        }
      }
      $this->get('session')->getFlashBag()->add('error_msg', $userErrorMsg);
      //
      // Check coupon present and clean it
      //
      if ($discount){
        $session->remove('checkout_cart_discount');
      }
      return $this->redirectToRoute('checkout_pay');
    }
    $pareparedPurchase = $preparePurchaseRes['data'];
    $this->logger->info(sprintf("CheckoutCtrl|processPayment|1| Orders were prepared successfully as SO Intl %s | SO Local %s...", $pareparedPurchase['orderIntl']['name'], $pareparedPurchase['orderLocal']['name']));
    

    //
    // STEP 2 | Request payment to Pasarela
    //
    $this->logger->info(sprintf("CheckoutCtrl|processPayment|2| Start processing MercadoPago payment..."));
    // Get MP data
    $token             = $_REQUEST["token"];
    $payment_method_id = $_REQUEST["payment_method_id"];
    if (array_key_exists('installments', $_REQUEST)){
      $installments    = $_REQUEST["installments"];
    }else{
      $installments    = 1;
    }
    $issuer_id         = $_REQUEST["issuer_id"];

    // Resolve MP customer
    $this->logger->info(sprintf("CheckoutCtrl|processPayment|2| Searching customer %s", $user["email"]));
    $existing_customer = MercadoPago\Customer::search(array(
      "email" => $user['email']
    ));

    if (!count($existing_customer)) {
      $customer = new MercadoPago\Customer();
      $customer->email = $user["email"];
      $this->logger->info(sprintf("CheckoutCtrl|processPayment|2| Saving MP customer with email: %s", $user["email"]));
      $resClient = $customer->save();
      $this->logger->debug(sprintf("CheckoutCtrl|processPayment|2| Customer save returned: [%s]", json_encode($resClient)));
      if ($customer->id == NULL || strlen($customer->id) == 0){
        $errMsg = "<strong>El pago no pudo ser completado</strong><br>Error en el registro de cliente";
        return $this->handlePaymentError($pareparedPurchase, $errMsg);
      }
    } else {
      $this->logger->info(sprintf("CheckoutCtrl|processPayment|2| Using existing MP customer with email: %s", $user["email"]));
      $customer = $existing_customer[0];
    }

    // ---------------------------------
    // DO PAYMENT IN MP
    // ---------------------------------
    // BEGIN ---------------------------
    $payment = new MercadoPago\Payment();
    $payment->transaction_amount = $paymentAmount['amount'];
    $payment->token              = $token;
    if ($installments){
      $payment->installments       = $installments;
    }
    $payment->payment_method_id  = $payment_method_id;
    $payment->issuer_id          = $issuer_id;
    $payment->binary_mode        = TRUE;
    
    $payment->payer = array(
      "id" => $customer->id
    );

    $this->logger->info(sprintf("CheckoutCtrl|processPayment|2| Requesting payment at MP with token: %s, method: %s, issuer: %s, amount: %s, customerId: %s", $token, $payment_method_id, $issuer_id, $calcRes['total']['price'], $customer->id));

    $this->logger->debug('----------------------------------------------------');
    $this->logger->debug(print_r($payment, TRUE));
    $this->logger->debug('----------------------------------------------------');

    $payment->save();
    if (!isset($payment->card) || (isset($payment->card) && $payment->card->id == NULL)) {
      $this->logger->info("CheckoutCtrl|processPayment|2| Saving card at MP...");
      $card = new MercadoPago\Card();
      $card->token = $token;
      $card->customer_id = $customer->id;
      $savedCard = $card->save();
      $this->logger->debug(sprintf("CheckoutCtrl|processPayment|2| Card save returned: [%s]", json_encode($savedCard)));
      if ($card->id == NULL || strlen($card->id) == 0){
        $errMsg = "<strong>El pago no pudo ser completado</strong><br>Error en el registro de tarjeta";
        $this->logger->warning(sprintf("CheckoutCtrl|processPayment|2| Error saving card %s!!"), $payment->card->id);
        return $this->handlePaymentError($pareparedPurchase, $errMsg);
      }
    } else {
      $this->logger->info(sprintf("CheckoutCtrl|processPayment|2| Searching card id %s..."), $payment->card->id);
      $card = MercadoPago\Card::find_by_id($payment->card->id);
      if (!isset($card->issuer)) {
        $this->logger->info(sprintf("CheckoutCtrl|processPayment|2| Card id %s loaded."), $payment->card->id);
        $card = $payment->card;
      }else{
        $this->logger->warning(sprintf("CheckoutCtrl|processPayment|2| Card id %s is missing issuer!"), $payment->card->id);
      }
    }
    // END 
    // ------------------------------
    // TEST PAYMENT SIMULATED - BEGIN
    // $payment = new \stdClass();
    // $payment->id = 'abcdefghijklmnopqrstuvxyz';
    // $payment->statement_descriptor = 'statement_descriptor';
    // $payment->status = 'approved';
    // $payment->card = new \stdClass();
    // $payment->card->last_four_digits = '0000';
    // $payment->card->cardholder = new \stdClass();
    // $payment->card->cardholder->name = 'TITULAR TEST';
    // $card = new \stdClass();
    // $card->issuer = new \stdClass();
    // $card->issuer->name = 'visa';
    // TEST PAYMENT SIMULATED - END
    // ------------------------------

    // $this->logger->debug('----------------------------------------------------');
    // $this->logger->debug(json_encode($payment));
    // $this->logger->debug('----------------------------------------------------');

    // Check if MP confirms payment
    if ($payment->status == 'approved' || $payment->status == 'in_process'){

      // Check payment status
      $this->logger->info(sprintf("CheckoutCtrl|processPayment|2|MP payment was approved with id: %s.", $payment->id));
      
      $paymentGW = array(
        'gateway'              => "MERCADOPAGO",
        'transaction_id'       => $payment->id,
        'user_id'              => $customer->id,
        'statement_descriptor' => $payment->statement_descriptor,
        'external_userid'      => $customer->id
      );
      if ($payment->status == 'approved'){
        $paymentGW['status'] = 'APPROVED';
      }else{
        $paymentGW['status'] = 'IN_PROGRESS';
      }
      $paymentGW['issuer_name'] = 'Tarjeta';
      if (isset($card->issuer)) {
        $paymentGW['issuer_name'] = $card->issuer->name;
      }
      if (isset($card->last_four_digits)){
        $paymentGW['card_last_four'] = $card->last_four_digits;
      }else if (isset($payment->card->last_four_digits)){
        $paymentGW['card_last_four'] = $payment->card->last_four_digits;
      }
      if (isset($card->cardholder)){
        $paymentGW['card_holder'] = $card->cardholder->name;
      }else if (isset($payment->card->cardholder)){
        $paymentGW['card_holder'] = $payment->card->cardholder->name;
      }

      //
      // STEP 3 | Confirm order at ERP
      //
      $this->logger->info(sprintf("CheckoutCtrl|processPayment|3|Confirming orders SO Intl %s | SO Local %s...", $pareparedPurchase['orderIntl']['name'], $pareparedPurchase['orderLocal']['name']));
      $confirmOrderRes = $this->hubClient->confirmOrder($country['code'], $discount, $pareparedPurchase['orderIntl']['name'], $pareparedPurchase['orderLocal']['name'], $paymentAmount, $paymentGW);
      if (!$confirmOrderRes['resolved'] || $confirmOrderRes['status_code'] != '200'){
        // Order could not be confirmed
        // Send email to operations and show customer order
        $this->logger->error("CheckoutCtrl|processPayment|3|Error confirming order (".$confirmOrderRes['status_code']." | " . json_encode($confirmOrderRes['data']) . ")");
        // ------------------------------
        // TODO!!!!!!!!!!!!!!!!!!!!!!!!!!
        // ------------------------------
        // SEND MAIL TO OPERATIONS

        // Clean session
        $this->get('session')->remove('checkout_cart_prices');
        $this->get('session')->remove('checkout_cart_shipping');
        $this->get('session')->remove('checkout_cart_payment');
        $this->get('session')->remove('checkout_cart_items');
        $this->get('session')->remove('checkout_cart_discount');

        // return $this->redirectToRoute('order_view', [
        //   'orderId'   =>  $pareparedPurchase['orderIntl']['hashedName'],
        //   'error_msg' =>  "Ocurri&oacute; un error al pagar"
        //   ]);
        $confirmedOrder = $confirmOrderRes['data'];
        $params = array(
          'intlOrderId'  => '-',
          'localOrderId' => '-'
        );
        if (array_key_exists('hashedName', $confirmedOrder['orderIntl'])){
          $params['intlOrderId'] = $confirmedOrder['orderIntl']['hashedName'];
        }
        if (array_key_exists('hashedName', $confirmedOrder['orderLocal'])){
          $params['localOrderId'] = $confirmedOrder['orderIorderLocalntl']['hashedName'];
        }

        return $this->redirectToRoute('purchase_view', $params);
      }else{
        $confirmedOrder = $confirmOrderRes['data'];
        $this->logger->info(sprintf("CheckoutCtrl|processPayment|3|Order Intl %s | Local %s was confirmed successfully at ERP!", $confirmedOrder['orderIntl']['name'], $confirmedOrder['orderLocal']['name']));

        // Clean session
        $this->get('session')->remove('checkout_cart_prices');
        $this->get('session')->remove('checkout_cart_shipping');
        $this->get('session')->remove('checkout_cart_payment');
        $this->get('session')->remove('checkout_cart_items');
        $this->get('session')->remove('checkout_cart_discount');

        // Show first time confirmation
        $this->get('session')->getFlashBag()->set('first_confirmation', TRUE);

        $params = array(
          'intlOrderId'  => '-',
          'localOrderId' => '-'
        );
        if (array_key_exists('hashedName', $confirmedOrder['orderIntl'])){
          $params['intlOrderId'] = $confirmedOrder['orderIntl']['hashedName'];
        }
        if (array_key_exists('hashedName', $confirmedOrder['orderLocal'])){
          $params['localOrderId'] = $confirmedOrder['orderLocal']['hashedName'];
        }

        return $this->redirectToRoute('purchase_view', $params);
      }
    }else{
      // print_r($payment);
      // die();
      $paymentDetails = array(
        'id'                 => $payment->id,
        'status'             => $payment->status,
        'status_detail'      => $payment->status_detail,
        'currency_id'        => $payment->currency_id,
        'transaction_amount' => $payment->transaction_amount,
      );
      // if ($payment->payer != NULL){
      //   $paymentDetails['payer'] = array(
      //     'id'               => $payment->payer->id,
      //     'type'             => $payment->payer->type,
      //     'first_name'       => $payment->payer->first_name,
      //     'last_name'        => $payment->payer->last_name,
      //     'email'            => $payment->payer->email,
      //     'phone' => array(
      //       'area_code'      => $payment->payer->phone->area_code,
      //       'number'         => $payment->payer->phone->number,
      //       'extension'      => $payment->payer->phone->extension
      //     )
      //   );
      // }
      $this->logger->error("CheckoutCtrl|processPayment|2| Error on PASARELLA, payment was not completed! --> ". json_encode($paymentDetails));
      $this->logger->error(sprintf("CheckoutCtrl|processPayment|2| Payment was ***%s***", json_encode($payment)));


      // 
      // New handler
      //
      $errMsg = "<strong>El pago no pudo ser completado</strong><br>";
      if (array_key_exists($payment->status_detail, Settings::mp_status_errors)){
        $errMsg .= Settings::mp_status_errors[$payment->status_detail];
      }
      return $this->handlePaymentError($pareparedPurchase, $errMsg);

    }
  }

  private function handlePaymentError($pareparedPurchase, $errMsg) {
    $this->logger->info(sprintf("CheckoutCtrl|processPayment|2|Payment could NOT BE COMPLETED for Intl SO %s - Local SO %s! All ERP documents will be deleted.",  $pareparedPurchase['orderIntl']['name'], $pareparedPurchase['orderLocal']['name']));

    // Cancel Order
    // $this->logger->info(sprintf("CheckoutCtrl|processPayment|2|Canceling order Intl %s - Local %s...", $pareparedPurchase['orderIntl']['name'], $pareparedPurchase['orderLocal']['name']));
    // $cancelOrderRes = $this->hubClient->cancelOrder($pareparedPurchase['orderIntl']['name'], $pareparedPurchase['orderLocal']['name']);
    // if (!$cancelOrderRes['resolved'] || $cancelOrderRes['status_code'] != '200'){
    //   // Order could not be cancelled
    //   // Send email to operations 
    //   $this->logger->error("CheckoutCtrl|processPayment|2|Error cancelling order (".$cancelOrderRes['status_code']." | " . json_encode($cancelOrderRes['data']) . ")");
    //   // ------------------------------
    //   // TODO!!!!!!!!!!!!!!!!!!!!!!!!!!
    //   // ------------------------------
    //   // SEND MAIL TO OPERATIONS
    // }else{
    //   $this->logger->info(sprintf("CheckoutCtrl|processPayment|2|Order cancelled successfully."));
    // }
    
    $this->get('session')->getFlashBag()->add('error_msg', $errMsg);

    return $this->redirectToRoute('checkout_pay');
  }
}
