<?php
namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\Config\Definition\Exception\Exception;
use Symfony\Component\HttpFoundation\Request;

use App\Utils\Settings;

class HubClient  {

  private $TIMEOUT      = 220;
  private $LONG_TIMEOUT = 320;
  private $RETRIES      = 3;

  private $logger;
  private $base_url;
  private $access_token = NULL;
  private $server_api_key = NULL;

  public function __construct(LoggerInterface $logger, $hostUrl, $timeout, $longTimeout, $apiKey){
    $this->logger         = $logger;
    $this->base_url       = $hostUrl;
    $this->TIMEOUT        = $timeout;
    $this->LONG_TIMEOUT   = $longTimeout;
    $this->server_api_key = $apiKey;
  }

  private function _getUser($url){
    $httpheaders = NULL;
    // Check access token
    if ($this->access_token != NULL){
      $httpheaders = array('Authorization: '.$this->access_token);
    }

    return $this->_get($url, $httpheaders);
  }

  //
  // Base Methods
  //
  private function _get($url, $headers=NULL){
    try {
      $uri = $this->base_url . $url;

      $start = microtime(true);
      $fc = curl_init();
      curl_setopt($fc, CURLOPT_URL, $uri);
      curl_setopt($fc, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($fc, CURLOPT_HEADER, 1);
      curl_setopt($fc, CURLOPT_VERBOSE, 0);
      curl_setopt($fc, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($fc, CURLOPT_TIMEOUT, $this->TIMEOUT);

      // Check headers (for device and access/user token)
      if ($headers){
          curl_setopt($fc, CURLOPT_HTTPHEADER, $headers);
      }
      // Execute CURL and get output
      $this->logger->info('HubClient|_get|Sending to ['.$uri.'] with headers '.json_encode($headers));
      $retry = 0;
      $res = curl_exec($fc);
      $curlErr = curl_errno($fc);
      while($curlErr == 28 && $retry < $this->RETRIES){
        $this->logger->warning('HubClient|_get|CURL timedout! retry ' . ($retry+1) . ' of ' . $this->RETRIES . '...');
        $res = curl_exec($fc);
        $curlErr = curl_errno($fc);
        $retry++;
      }

      // Parse response
      $res_header_size = curl_getinfo($fc, CURLINFO_HEADER_SIZE);
      $res_header = substr($res, 0, $res_header_size);
      $res_body = substr($res, $res_header_size);

      $httpcode = curl_getinfo($fc, CURLINFO_HTTP_CODE);
      $curlErr  = curl_errno($fc);
      curl_close($fc);

      $finish = microtime(true);

      // Log HTTP result
      if ($retry > 0){
        $this->logger->warning('HubClient|_get|With ' . $retry . ' retries|'.round(($finish - $start), 4) . ' secs for '.$url.' ['. $httpcode .']: ' . $uri);
      }else{
        $this->logger->info('HubClient|_get|'.round(($finish - $start), 4) . ' secs for '.$url.' ['. $httpcode .']: ' . $uri);
      }

      // Check CURL result
      if ($curlErr != 0){
        throw new Exception('HubClient|_get|CURL failed with code ['.$curlErr.'] for ('.$uri.')');
      }

      $this->logger->info('HubClient|_get|______Response body: ----|'.$res_body.'|----');
      $this->logger->debug('HubClient|_get|Return resoved with statusCode ' . $httpcode);

      return array(
        'resolved'    => TRUE,
        'status_code' => $httpcode,
        'data'        => json_decode($res_body, TRUE)
      );
    } catch (Exception $ex) {
      $this->logger->error('HubClient|_get|Request raised exception:');
      $this->logger->error($ex->getMessage());
      throw $ex;
    }
  }

  private function _getRaw($url, $headers=NULL){
    try {
      $uri = $this->base_url . $url;

      $start = microtime(true);
      $fc = curl_init();
      curl_setopt($fc, CURLOPT_URL, $uri);
      curl_setopt($fc, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($fc, CURLOPT_HEADER, 1);
      curl_setopt($fc, CURLOPT_VERBOSE, 0);
      curl_setopt($fc, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($fc, CURLOPT_TIMEOUT, $this->TIMEOUT);

      // Check headers (for device and access/user token)
      if ($headers){
          curl_setopt($fc, CURLOPT_HTTPHEADER, $headers);
      }
      // Execute CURL and get output
      $this->logger->info('HubClient|_getRaw|Sending to ['.$uri.'] with headers '.json_encode($headers));
      $retry = 0;
      $res = curl_exec($fc);
      $curlErr = curl_errno($fc);
      while($curlErr == 28 && $retry < $this->RETRIES){
        $this->logger->warning('HubClient|_getRaw|CURL timedout! retry ' . ($retry+1) . ' of ' . $this->RETRIES . '...');
        $res = curl_exec($fc);
        $curlErr = curl_errno($fc);
        $retry++;
      }

      // Parse response
      $res_header_size = curl_getinfo($fc, CURLINFO_HEADER_SIZE);
      $res_header = substr($res, 0, $res_header_size);
      $res_body = substr($res, $res_header_size);

      $httpcode = curl_getinfo($fc, CURLINFO_HTTP_CODE);
      $curlErr  = curl_errno($fc);
      curl_close($fc);

      $finish = microtime(true);

      // Log HTTP result
      if ($retry > 0){
        $this->logger->warning('HubClient|_getRaw|With ' . $retry . ' retries|'.round(($finish - $start), 4) . ' secs for '.$url.' ['. $httpcode .']: ' . $uri);
      }else{
        $this->logger->info('HubClient|_getRaw|'.round(($finish - $start), 4) . ' secs for '.$url.' ['. $httpcode .']: ' . $uri);
      }

      // Check CURL result
      if ($curlErr != 0){
        throw new Exception('HubClient|_getRaw|CURL failed with code ['.$curlErr.'] for ('.$uri.')');
      }

      $this->logger->debug('HubClient|_getRaw|Return resoved with statusCode ' . $httpcode);

      return array(
        'resolved'    => TRUE,
        'status_code' => $httpcode,
        'data'        => $res_body
      );
    } catch (Exception $ex) {
      $this->logger->error('HubClient|_getRaw|Request raised exception:');
      $this->logger->error($ex->getMessage());
      throw $ex;
    }
  }

  private function _postUser($url, $data){
    $httpheaders = NULL;
    // Check access token
    if ($this->access_token != NULL){
      $httpheaders = array('Authorization: '.$this->access_token);
    }
    return $this->_post($url, $data, $httpheaders);
  }

  //New Cp
  private function _ventanillaPostUser($url, $data){
    $httpheaders = NULL;
    // Check access token
    if ($this->access_token != NULL){
      $httpheaders = array('Authorization: '.$this->access_token);
    }
    return $this->_ventanillaPost($url, $data, $httpheaders);
  }

  private function _postServer($url, $data, $files=NULL){
    $httpheaders = array('key: '.$this->server_api_key );
    return $this->_post($url, $data, $httpheaders, $files);
  }

  private function _getServer($url){
    $httpheaders = array('key: '.$this->server_api_key );
    return $this->_get($url, $httpheaders);
  }

  private function _post($url, $data, $headers=NULL, $files=NULL){
    try {
      $uri = $this->base_url . $url;

      $contentType = array('Content-Type: application/json');

      if ($files) {
        $data = array('body' => $data);

        foreach ($files as $i => $file) {
          $fileName = $file['tmp_name'];
          $curlFileObject = curl_file_create($fileName);
          $curlFileObject->setPostFilename($file['name']);
          $data['files['. $i . ']'] = $curlFileObject;
        }

        $contentType = array('Content-Type: multipart/form-data');
      }

      // Check headers (for device and access/user token)
      if ($headers) {
        //curl_setopt($fc, CURLOPT_HTTPHEADER, $headers);
        $httpheaders = array_merge($headers, $contentType);
      }else{
        $httpheaders = $contentType;
      }

      $start = microtime(true);
      $fc = curl_init();
      curl_setopt($fc, CURLOPT_URL, $uri);
      curl_setopt($fc, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($fc, CURLOPT_HEADER, 1);
      curl_setopt($fc, CURLOPT_VERBOSE, 0);
      curl_setopt($fc, CURLOPT_POST, 1);
      curl_setopt($fc, CURLOPT_POSTFIELDS, $data);
      curl_setopt($fc, CURLOPT_HTTPHEADER, $httpheaders);
      curl_setopt($fc, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($fc, CURLOPT_TIMEOUT, $this->TIMEOUT);

      // Execute CURL and get output
      $retry = 0;
      $res = curl_exec($fc);
      $curlErr = curl_errno($fc);
      while($curlErr == 28 && $retry < $this->RETRIES){
        $this->logger->warning('HubClient|_post|CURL timedout! retry ' . ($retry+1) . ' of ' . $this->RETRIES . '...');
        $res = curl_exec($fc);
        $curlErr = curl_errno($fc);
        $retry++;
      }

      // Parse response
      $res_header_size = curl_getinfo($fc, CURLINFO_HEADER_SIZE);
      $res_header = substr($res, 0, $res_header_size);
      $res_body = substr($res, $res_header_size);

      // Get status code and error info and close
      $httpcode = curl_getinfo($fc, CURLINFO_HTTP_CODE);
      $curlErr  = curl_errno($fc);
      curl_close($fc);

      $finish = microtime(true);

      // Log HTTP result
      if ($retry > 0){
        $this->logger->warning('HubClient|_post|______With ' . $retry . ' retries|'.round(($finish - $start), 4) . ' secs ['. $httpcode .']: ' . $uri . '[Data:'.$data.']');
      }else{
        $this->logger->info('HubClient|_post|______'.round(($finish - $start), 4) . ' secs for '.$url.' ['. $httpcode .']: ' . $uri . '[Data:'.$data.']');
      }
      $this->logger->info('HubClient|_post|______Response body: ----|'.$res_body.'|----');

      // Check CURL result
      if ($curlErr != 0){
        throw new Exception('HubClient|_post|CURL failed with code ['.$curlErr.'] for ('.$uri.')');
      }

      return array(
        'resolved'    => TRUE,
        'status_code' => $httpcode,
        'data'        => json_decode($res_body, TRUE)
      );

    } catch (Exception $ex) {
      $this->logger->error('HubClient|_post|Request raised exception:');
      $this->logger->error($ex->getMessage());
      throw $ex;
    }
  }


  //New Cp	
  private function _ventanillaPost($url, $data, $headers=NULL, $files=NULL){
    try {
      $uri = $this->base_url . $url;

      $contentType = array('Content-Type: application/json');

      if ($files) {
        $data = array('body' => $data);

        foreach ($files as $i => $file) {
          $fileName = $file['tmp_name'];
          $curlFileObject = curl_file_create($fileName);
          $curlFileObject->setPostFilename($file['name']);
          $data['files['. $i . ']'] = $curlFileObject;
        }

        $contentType = array('Content-Type: multipart/form-data');
      }

      // Check headers (for device and access/user token)
      if ($headers) {
        //curl_setopt($fc, CURLOPT_HTTPHEADER, $headers);
        $httpheaders = array_merge($headers, $contentType);
      }else{
        $httpheaders = $contentType;
      }

      $start = microtime(true);
      $fc = curl_init();
      curl_setopt($fc, CURLOPT_URL, $uri);
      curl_setopt($fc, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($fc, CURLOPT_HEADER, 1);
      curl_setopt($fc, CURLOPT_VERBOSE, 0);
      curl_setopt($fc, CURLOPT_POST, 1);
      curl_setopt($fc, CURLOPT_POSTFIELDS, $data);
      curl_setopt($fc, CURLOPT_HTTPHEADER, $httpheaders);
      curl_setopt($fc, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($fc, CURLOPT_TIMEOUT, $this->TIMEOUT);

      // Execute CURL and get output
      $retry = 0;
      $res = curl_exec($fc);
      $curlErr = curl_errno($fc);
      while($curlErr == 28 && $retry < $this->RETRIES){
        $this->logger->warning('HubClient|_ventanillaPost|CURL timedout! retry ' . ($retry+1) . ' of ' . $this->RETRIES . '...');
        $res = curl_exec($fc);
        $curlErr = curl_errno($fc);
        $retry++;
      }

      // Parse response
      $res_header_size = curl_getinfo($fc, CURLINFO_HEADER_SIZE);
      $res_header = substr($res, 0, $res_header_size);
      $res_body = substr($res, $res_header_size);

      // Get status code and error info and close
      $httpcode = curl_getinfo($fc, CURLINFO_HTTP_CODE);
      $curlErr  = curl_errno($fc);
      curl_close($fc);

      $finish = microtime(true);

      // Log HTTP result
      if ($retry > 0){
        $this->logger->warning('HubClient|_ventanillaPost|______With ' . $retry . ' retries|'.round(($finish - $start), 4) . ' secs ['. $httpcode .']: ' . $uri . '[Data:'.$data.']');
      }else{
        $this->logger->info('HubClient|_ventanillaPost|______'.round(($finish - $start), 4) . ' secs for '.$url.' ['. $httpcode .']: ' . $uri . '[Data:'.$data.']');
      }
      $this->logger->info('HubClient|_ventanillaPost|______Response body: ----|'.$res_body.'|----');

      // Check CURL result
      if ($curlErr != 0){
        throw new Exception('HubClient|_ventanillaPost|CURL failed with code ['.$curlErr.'] for ('.$uri.')');
      }

      return array(
        'resolved'    => TRUE,
        'status_code' => $httpcode,
        'data'        => json_decode($res_body, TRUE)
      );

    } catch (Exception $ex) {
      $this->logger->error('HubClient|_ventanillaPost|Request raised exception:');
      $this->logger->error($ex->getMessage());
      throw $ex;
    }
  }

  public function setAccessToken($at){
    $this->access_token = $at;
  }


  //
  // Users Methods
  //
  public function registerDevice(){
    try{
      $data = [
        'userId'    => NULL,
        'userAgent' => $_SERVER['HTTP_USER_AGENT'],
        'OSToken'  => NULL
      ];

      return $this->_postUser('/api/clientDevices/new', json_encode($data));
      
    }catch (Exception $ex) {
      return array(
        'resolved'    => FALSE,
        'status_code' => -1,
        'data'        => NULL
      );
    }
  }
  
  //New Cp
  public function ventanillaRegisterDevice(){
    try{
      $data = [
        'userId'    => NULL,
        'userAgent' => $_SERVER['HTTP_USER_AGENT'],
        'OSToken'  => NULL
      ];

      return $this->_ventanillaPostUser('/api/clientDevices/new', json_encode($data));
      	  
    }catch (Exception $ex) {
      return array(
        'resolved'    => FALSE,
        'status_code' => -1,
        'data'        => NULL
      );
    }
  }


  public function login($email, $password, $deviceToken){
    // Send Login
    // $response = $client->get('/users');
    // $response = $client->request('GET', 'https://api.github.com/repos/guzzle/guzzle');
    // echo $response->getStatusCode(); # 200
    // echo $response->getHeaderLine('content-type'); # 'application/json; charset=utf8'
    // echo $response->getBody(); # '{"id": 1420053, "name": "guzzle", ...}'

    try{
      $data = [
        'credentials' => [
          'email'       => $email,
          'password'    => $password,
          'deviceToken' => $deviceToken
        ]
      ];

      return $this->_postUser('/api/users/login?include=user', json_encode($data));
      
    }catch (Exception $ex) {
      return array(
        'resolved'    => FALSE,
        'status_code' => -1,
        'data'        => NULL
      );
    }
  }

  public function ventanillalogin($cedula, $deviceToken){

    try{
      $data = [
        'credentials' => [
          'cedula'       => $cedula,
          'deviceToken' => $deviceToken
        ]
      ];

      return $this->_ventanillaPostUser('/api/users/ventanillalogin?include=user', json_encode($data));
      
    }catch (Exception $ex) {
      return array(
        'resolved'    => FALSE,
        'status_code' => -1,
        'data'        => NULL
      );
    }
  }
 	

  public function logout(){
    // Send logout

    // Clean AT cookie

    // Clean user data in session
  }

  public function unpaidInvoiceCount(){
    try{
      return $this->_getUser('/api/shops/unpaidInvoiceCount', NULL);
    }catch (Exception $ex) {
      return array(
        'resolved'    => FALSE,
        'status_code' => -1,
        'data'        => NULL
      );
    }
  }

  public function changePassword($actual, $new){
    try{
      $data = new \stdClass;
      $data->oldPassword = $actual;
      $data->newPassword = $new;
      
      return $this->_postUser('/api/users/change-password', json_encode($data));
    }catch (Exception $ex) {
      return array(
        'resolved'    => FALSE,
        'status_code' => -1,
        'data'        => NULL
      );
    }
  }

  public function requestResetPassword($username){
    // Send request to reset password to server (and show ok page)
  }

  public function setResetPassword($at, $userId, $password){
    // Send reset password to server
  }

  public function _resetPassword($password , $confirmation, $userId , $at) {
    $this->setAccessToken($at);
    try{
      $data = [
        'body' => [
          'confirmation' => $confirmation,
          'password'     => $password,
          'userId'       => $userId
        ]
      ];

      return $this->_postUser('/api/users/_resetPassword', json_encode($data));
      
    }catch (Exception $ex) {
      return array(
        'resolved'    => FALSE,
        'status_code' => -1,
        'data'        => NULL
      );
    }
  }

  public function checkEmail($email){
    try{
      $data = ['email' => $email];

      return $this->_postServer('/api/users/checkEmail', json_encode($data));
      
    }catch (Exception $ex) {
      return array(
        'resolved'    => FALSE,
        'status_code' => -1,
        'data'        => NULL
      );
    }
  }

  public function getCompleteRegistration($token){
    try{
      $data = ['token' => $token];
      return $this->_postServer('/api/users/getPatientCompleteRegistration', json_encode($data));
    }catch (Exception $ex) {
      return array(
        'resolved'    => FALSE,
        'status_code' => -1,
        'data'        => NULL
      );
    }
  }

  public function setPatientCompleteRegistration($patient){
    try{
      return $this->_postServer('/api/users/setPatientCompleteRegistration', json_encode($patient));
    }catch (Exception $ex) {
      return array(
        'resolved'    => FALSE,
        'status_code' => -1,
        'data'        => NULL
      );
    }
  }

  //
  // Server methods
  //
  public function upsertUser($userData){
    try{
      return $this->_postServer('/api/users/webUpsertUser', json_encode($userData));
    }catch (Exception $ex) {
      return array(
        'resolved'    => FALSE,
        'status_code' => -1,
        'data'        => NULL
      );
    }
  }

  public function upsertPatient($firstname, $lastname, $birthdate, $email, $phone, $gender, $weight, $height, $country, $userId){
    try{
      
      $data = array(
        "firstname"   => $firstname,
        "lastname"    => $lastname,
        "birthdate"   => $birthdate,
        "email"       => $email,
        "phone"       => $phone,
        "gender"      => $gender,
        "weight"      => $weight,
        "height"      => $height,
        "countryCode" => $country,
        "userId"      => $userId
      );

      return $this->_postServer('/api/users/webUpsertPatient', json_encode($data));
      
    }catch (Exception $ex) {
      return array(
        'resolved'    => FALSE,
        'status_code' => -1,
        'data'        => NULL
      );
    }
  }


  //
  // Shipping methods
  //
  public function getDeliveryOptions($data){
    try{
      return $this->_postUser('/api/shops/getDeliveryOptions', json_encode($data));
    }catch (Exception $ex) {
      return array(
        'resolved'    => FALSE,
        'status_code' => -1,
        'data'        => NULL
      );
    }
  }

  public function addShippingAddress($data){
    try{
      return $this->_postUser('/api/shops/addShippingAddress', json_encode($data));
    }catch (Exception $ex) {
      return array(
        'resolved'    => FALSE,
        'status_code' => -1,
        'data'        => NULL
      );
    }
  }

  public function getShippingAddress($addressName){
    try{
      return $this->_postServer('/api/shops/readShippingAddress', '{"shipping_address_name":"'.$addressName.'"}');
    }catch (Exception $ex) {
      return array(
        'resolved'    => FALSE,
        'status_code' => -1,
        'data'        => NULL
      );
    }
  }


  //
  // Shop methods
  //
  public function calculateCart($cartData){
    try{
      
      return $this->_postServer('/api/shops/calculateTotal', json_encode($cartData));
      
    }catch (Exception $ex) {
      return array(
        'resolved'    => FALSE,
        'status_code' => -1,
        'data'        => NULL
      );
    }
  }
  
  public function getProductsPriceAndStock($countryCode){
    try{
      return $this->_get('/api/shops/getProductsPriceAndStock?country='.$countryCode);
    }catch (Exception $ex) {
      return array(
        'resolved'    => FALSE,
        'status_code' => -1,
        'data'        => NULL
      );
    }
  }

  public function getProductsByCategory($category, $countryCode){
    try{
      return $this->_get('/api/shops/getProductsByCategory?category='.$category.'&country='.$countryCode);
    }catch (Exception $ex) {
      return array(
        'resolved'    => FALSE,
        'status_code' => -1,
        'data'        => NULL
      );
    }
  }

  public function getProductData($item_code, $countryCode){
    try{
      return $this->_get('/api/shops/getProductData?item_code='.$item_code.'&country='.$countryCode);
    }catch (Exception $ex) {
      return array(
        'resolved'    => FALSE,
        'status_code' => -1,
        'data'        => NULL
      );
    }
  }

  public function currencyEchange($currency){
    try{
      // api/resource/Currency Exchange/2019-03-05-USD-UYU
      //
      // WARNING!!!
      // HubServer is working on UTC and PHP is configured wuth GMT-3
      // Do not send `date` parameter to receive current exchange.
      //$exchangeParam = '?date='.date("Y-m-d").'&origCurr=USD&destCurr='.$currency;
      $exchangeParam = '?origCurr=USD&destCurr='.$currency;
      return $this->_getServer('/api/shops/currencyExchange'.$exchangeParam);
      
    }catch (Exception $ex) {
      return array(
        'resolved'    => FALSE,
        'status_code' => -1,
        'data'        => NULL
      );
    }
  }
  
  public function prepareOrder($workingCountry, $user, $paymentAmount, $cartItems, $shipping, $discount, $prescriptionFileName) {
    
    try{
      // Calculate delivery date
      $mDeliveryDate = new \Moment\Moment('now', 'UTC');
      if ($workingCountry == 'UY'){
        $mDeliveryDate->setTimezone('GMT-0300');
      }
      
      // Prepare object
      $order = array(
        'customer' => array (
          'mail'    => $user['email'],
          'name'    => $user['firstname'] . ' ' . $user['lastname'],
          'user_id' => $user['userId']
        ),
        'items'    => [],
        // 'shipping' => array (
        //   'name'        => $shipping['name'],
        //   'address'     => $shipping['address'],
        //   'countryName' => $shipping['countryName'],
        //   'countryCode' => $shipping['countryCode'],
        //   'city'        => $shipping['city'],
        //   'postalcode'  => $shipping['postalcode'],
        //   'phone'       => $shipping['phone']
        // ),
        'shipping'               => $shipping,
        'country'                => $workingCountry,
        'currency'               => $paymentAmount['currency'],
        'delivery_date'          => $mDeliveryDate->addDays(2)->format(),
        'prescription_file_name' => $prescriptionFileName,
        'discount'               => $discount
      );
      $totalEcgInterprations = 0;
      $orderItem = null;
      foreach ($cartItems as $key => $item) {
        $orderItem = array(
          'item_code'         => $item['code'], 
          'qty'               => $item['qty'],
        );
        if (array_key_exists('subscription_code', $item)){
          $orderItem['subscription_code'] = $item['subscription_code'];
        }else{
          $orderItem['subscription_code'] = '';
        }
        if (array_key_exists('payment_option', $item)){
          $orderItem['payment_option'] = $item['payment_option'];
        }
        array_push($order['items'], $orderItem);
        // Check ECG iterpretation
        // if ($item['interpretation_code'] != NULL){
        //   $bAddEcgInter = TRUE;
        //   for($i=0; $i < count($order['items']); $i++){
        //     if ($order['items'][$i]['item_code'] == $item['interpretation_code']){
        //       $order['items'][$i]['qty']++;
        //       $bAddEcgInter = FALSE;
        //       break;
        //     }
        //   }
        //   if ($bAddEcgInter){
        //     array_push($order['items'], array(
        //       'item_code'         => $item['interpretation_code'], 
        //       'qty'               => $item['qty']
        //     ));
        //   }
        // }
      };

      // Request to server (special timeouts and retries)
      $prevRetries  = $this->RETRIES;
      $this->RETRIES = 0;
      $prevTimeout   = $this->TIMEOUT;
      $this->TIMEOUT = $this->LONG_TIMEOUT;
      $response = $this->_postServer('/api/shops/preparePurchase', json_encode($order));
      // Restore defaults and return response
      $this->RETRIES = $prevRetries;
      $this->TIMEOUT = $prevTimeout;
      return $response;
    } catch (Exception $ex) {
      return array(
        'resolved'    => FALSE,
        'status_code' => -1,
        'data'        => NULL
      );
    }

  }

  public function confirmOrder($workingCountry, $discount, $orderIntlName, $orderLocalName, $paymentAmount, $paymentGW) {
    
    try{
      $order = array(
        'orderIntl_name'  => $orderIntlName,
        'orderLocal_name' => $orderLocalName,
        'country'         => $workingCountry,
        'currency'        => $paymentAmount['currency'],
        'payment'         => array (
          'amount'               => $paymentAmount['amount'],
          'currency'             => $paymentAmount['currency'],
          'gateway'              => $paymentGW['gateway'],
          'transaction_id'       => $paymentGW['transaction_id'],
          'user_id'              => $paymentGW['user_id'],
          'issuer_name'          => $paymentGW['issuer_name'],
          'card_last4'           => $paymentGW['card_last_four'],
          'card_holder'          => $paymentGW['card_holder'],
          'status'               => $paymentGW['status'],
          'statement_descriptor' => $paymentGW['statement_descriptor'],
          'external_userid'      => $paymentGW['external_userid']
        ),
        'discount'        => $discount
      );

      // Request to server (special timeouts and retries)
      $prevRetries  = $this->RETRIES;
      $this->RETRIES = 0;
      $prevTimeout   = $this->TIMEOUT;
      $this->TIMEOUT = $this->LONG_TIMEOUT;
      $response = $this->_postServer('/api/shops/confirmPurchase', json_encode($order));
      // Restore defaults and return response
      $this->RETRIES = $prevRetries;
      $this->TIMEOUT = $prevTimeout;
      return $response;
    } catch (Exception $ex) {
      return array(
        'resolved'    => FALSE,
        'status_code' => -1,
        'data'        => NULL
      );
    }

  }

  public function cancelOrder($orderIntlName, $orderLocalName){
    try{
      
      $data = array (
        'orderIntl_name'  => $orderIntlName,
        'orderLocal_name' => $orderLocalName
      );

      // Request to server (special timeouts and retries)
      $prevRetries  = $this->RETRIES;
      $this->RETRIES = 0;
      $prevTimeout   = $this->TIMEOUT;
      $this->TIMEOUT = $this->LONG_TIMEOUT;
      $response = $this->_postServer('/api/shops/cancelPurchase', json_encode($data));
      // Restore defaults and return response
      $this->RETRIES = $prevRetries;
      $this->TIMEOUT = $prevTimeout;
      return $response;
    }catch (Exception $ex) {
      return array(
        'resolved'    => FALSE,
        'status_code' => -1,
        'data'        => NULL
      );
    }
  }

  public function cancelSubscription($subId, $email, $username) {
    try{
      $data = array(
        'name' => $subId,
        'username' => $username,
        'email'=> $email
      );

      // Request to server (special timeouts and retries)
      $prevRetries  = $this->RETRIES;
      $this->RETRIES = 0;
      $prevTimeout   = $this->TIMEOUT;
      $this->TIMEOUT = $this->LONG_TIMEOUT;
      $response = $this->_postUser('/api/shops/cancelSubscription', json_encode($data));
      // Restore defaults and return response
      $this->RETRIES = $prevRetries;
      $this->TIMEOUT = $prevTimeout;
      return $response;
    }catch (Exception $ex) {
      return array(
        'resolved'    => FALSE,
        'status_code' => -1,
        'data'        => NULL
      );
    }
  }

  public function orderView($orderId){
    try{
      return $this->_getUser('/api/shops/orderView/'.$orderId);
    }catch (Exception $ex) {
      return array(
        'resolved'    => FALSE,
        'status_code' => -1,
        'data'        => NULL
      );
    }
  }

  public function purchaseView($intlOrderId, $localOrderId){
    try{
      return $this->_getUser('/api/shops/purchaseView/'.$intlOrderId.'/'.$localOrderId);
    }catch (Exception $ex) {
      return array(
        'resolved'    => FALSE,
        'status_code' => -1,
        'data'        => NULL
      );
    }
  }

  public function resetPasswordRequest($email) {
    try{
      $email = urlencode($email);
      return $this->_postUser('/api/users/resetPasswordRequest?email='.$email, null);  
    }catch (Exception $ex) {
      return array(
        'resolved'    => FALSE,
        'status_code' => -1,
        'data'        => NULL
      );
    }
  }

  public function getInvoices(){
    try{
      return $this->_postUser('/api/shops/getInvoices', NULL);  
    }catch (Exception $ex) {
      return array(
        'resolved'    => FALSE,
        'status_code' => -1,
        'data'        => NULL
      );
    }
  }

  public function getInvoiceData($invoiceToken){
    try{

      return $this->_getUser('/api/shops/getInvoiceData?invoice_name='.$invoiceToken, NULL);  
    }catch (Exception $ex) {
      return array(
        'resolved'    => FALSE,
        'status_code' => -1,
        'data'        => NULL
      );
    }
  }

  public function getSubscription(){
    try{
      return $this->_postUser('/api/shops/getSubscription', NULL);  
    }catch (Exception $ex) {
      return array(
        'resolved'    => FALSE,
        'status_code' => -1,
        'data'        => NULL
      );
    }
  }

  public function preparePaymentEntry($pEntry) {
    try {
      // Request to server (special timeouts and retries)
      $prevRetries  = $this->RETRIES;
      $this->RETRIES = 0;
      $prevTimeout   = $this->TIMEOUT;
      $this->TIMEOUT = $this->LONG_TIMEOUT;
      $response = $this->_postServer('/api/shops/preparePaymentEntry', json_encode($pEntry));
      // Restore defaults and return response
      $this->RETRIES = $prevRetries;
      $this->TIMEOUT = $prevTimeout;
      return $response;
    } catch (Exception $ex) {
      return array(
        'resolved'    => FALSE,
        'status_code' => -1,
        'data'        => NULL
      );
    }
  }

  public function cancelPaymentEntry($pEntryName) {
    try {
      $pEntry = array(
        'payment_entry' => $pEntryName
      );
      // Request to server (special timeouts and retries)
      $prevRetries  = $this->RETRIES;
      $this->RETRIES = 0;
      $prevTimeout   = $this->TIMEOUT;
      $this->TIMEOUT = $this->LONG_TIMEOUT;
      $response =  $this->_postServer('/api/shops/cancelPaymentEntry', json_encode($pEntry));
      // Restore defaults and return response
      $this->RETRIES = $prevRetries;
      $this->TIMEOUT = $prevTimeout;
      return $response;
    } catch (Exception $ex) {
      return array(
        'resolved'    => FALSE,
        'status_code' => -1,
        'data'        => NULL
      );
    }
  }

  public function confirmPaymentEntry($pEntry) {
    try {
      // $pEntry = array(
      //   'payment_entry' => $pEntryName
      // );
      // Request to server (special timeouts and retries)
      $prevRetries  = $this->RETRIES;
      $this->RETRIES = 0;
      $prevTimeout   = $this->TIMEOUT;
      $this->TIMEOUT = $this->LONG_TIMEOUT;
      $response = $this->_postServer('/api/shops/confirmPaymentEntry', json_encode($pEntry));
      // Restore defaults and return response
      $this->RETRIES = $prevRetries;
      $this->TIMEOUT = $prevTimeout;
      return $response;
    } catch (Exception $ex) {
      return array(
        'resolved'    => FALSE,
        'status_code' => -1,
        'data'        => NULL
      );
    }
  }

  public function uploadPrescriptionFile($fileName) {
    try {
      return $this->_postServer('/api/shops/uploadPrescriptionFile', NULL, array($fileName));
    } catch (Exception $ex) {
      return array(
        'resolved'    => FALSE,
        'status_code' => -1,
        'data'        => NULL
      );
    }
  }

  public function getCityByCountryCode($code) {
    try {
      return $this->_getServer('/api/Cities/getByCountryCode?countryCode='.$code);
    } catch (Exception $ex) {
      return array(
        'resolved'    => FALSE,
        'status_code' => -1,
        'data'        => NULL
      );
    }
  }

  public function searchCity($country_code, $term) {
    try {
      return $this->_getServer('/api/Cities/searchCity?countryCode='.$country_code."&term=".$term);
    } catch (Exception $ex) {
      return array(
        'resolved'    => FALSE,
        'status_code' => -1,
        'data'        => NULL
      );
    }
  }


  //
  // Profile Methods
  // 
  public function getProfile(){
    try{
      return $this->_postUser('/api/users/getProfile', NULL);
    }catch (Exception $ex) {
      return array(
        'resolved'    => FALSE,
        'status_code' => -1,
        'data'        => NULL
      );
    }
  }

  public function getUserData(){
    try{
      return $this->_postUser('/api/users/getUserDataWeb', NULL);
    }catch (Exception $ex) {
      return array(
        'resolved'    => FALSE,
        'status_code' => -1,
        'data'        => NULL
      );
    }
  }

  public function selectPatientPractitioner($userId , $practitionerId, $orgId) {
    try {
      $data = array(
        'term' => array(
          'userId'         => $userId,
          'practitionerId' => $practitionerId,
          'orgId'          => $orgId
        )
      );
      // return $this->_postUser('/api/patientPermissions/newPatientPermissions' , json_encode($data));
      return $this->_postUser('/api/patientpractitioners/assignPractitioner' , json_encode($data));
    } catch (Exception $ex) {
      return array(
        'resolved'    => FALSE,
        'status_code' => -1,
        'data'        => NULL
      );    
    }
  }

  public function removeFamilyPlan($pId , $dId) {
    try {
      $data = array(
        'term' => array(
          'patientId' => $pId,
          'deviceId'  => $dId
        )
      );
      return $this->_postUser('/api/invitations/removeFamilyPlan', json_encode($data));
    } catch (Exception $ex) {
      return array(
        'resolved'    => FALSE,
        'status_code' => -1,
        'data'        => NULL
      );
    }
  }
  
  public function removePractitionerPermissions() {
    try {
      return $this->_postUser('/api/patientpractitioners/unassignPractitioner', NULL);
    } catch (Exception $ex) {
      return array(
        'resolved'    => FALSE,
        'status_code' => -1,
        'data'        => NULL
      );
    }
  }

  public function getCatalog() {
    try {
      return $this->_getUser('/api/healthcareServices/getCatalog');
    } catch (Exception $ex) {
      return array(
        'resolved'    => FALSE,
        'status_code' => -1,
        'data'        => NULL
      );
    }
  }

  public function getPatientPractitioner($userId) {
    try {
      return $this->_getServer('/api/healthcareServices/getPatientPractitioner?userId='.$userId);
    } catch (Exception $ex) {
      return array(
        'resolved'    => FALSE,
        'status_code' => -1,
        'data'        => NULL
      );
    }
  }

  public function insertOrganization($organization) {
    try {
      return $this->_postUser('/api/organizations/registerOrganization',json_encode($organization));
    } catch (Exception $ex) {
      return array(
        'resolved'    => FALSE,
        'status_code' => -1,
        'data'        => NULL
      );
    }
  }

  public function registerPractitioner($practitioner) {
    try {
      return $this->_postServer('/api/practitioners/registerPractitioner',json_encode($practitioner));
    } catch (Exception $ex) {
      return array(
        'resolved'    => FALSE,
        'status_code' => -1,
        'data'        => NULL
      );
    }
  }

  public function getClinicalProfile() {
    try {
      return $this->_getUser('/api/patients/webClinicalProfile',NULL , NULL);
    } catch (Exception $ex) {
      return array(
        'resolved'    => FALSE,
        'status_code' => -1,
        'data'        => NULL
      );
    }
  }

  public function clinicalProfileSave($data) {
    try {
      return $this->_postUser('/api/patients/webClinicalProfileSet', json_encode($data));
    } catch (Exception $ex) {
      return array(
        'resolved'    => FALSE,
        'status_code' => -1,
        'data'        => NULL
      );
    }
  }

  public function getUserCountry() {
    if(!empty($_SERVER['HTTP_CLIENT_IP'])) { $ip = $_SERVER['HTTP_CLIENT_IP'];
    } elseif(!empty($_SERVER['HTTP_X_FORWARDED_FOR'])) { $ip = $_SERVER['HTTP_X_FORWARDED_FOR'];
    } else { $ip = $_SERVER['REMOTE_ADDR']; }

    try {
      $data = array(
        'ip' => $ip
      );
      return $this->_postServer('/api/utils/get_country_by_ip', json_encode($data));
    } catch (Exception $ex) {
      return array(
        'resolved'    => FALSE,
        'status_code' => -1,
        'data'        => NULL
      );
    }
  }

  public function resolveWebCountry(Request $request) {
    if ($request->cookies->has('bl_loc')) {
      $country = $request->cookies->get('bl_loc');
    } else {
      $res = $this->getUserCountry();
      $country = 'uy';
      if ($res['status_code'] != 200) {
        $this->logger->error("hubClient | resolveWebCountry | Error | (".$res['status_code']." | " . json_encode($res['data']) . ")");
      } else {
        $country = strtolower($res['data']['country_code']);
        if (!array_key_exists($country, Settings::countries)) {
          $country = 'uy';
        };
      }
    }

    $this->logger->debug("hubClient | resolveWebCountry | Country: " . $country);
    return $country;
  }

  public function inviteMember($data){
    try {
      return $this->_postUser('/api/invitations/inviteUserToPlan', json_encode($data));
    } catch (Exception $ex) {
      return array(
        'resolved'    => FALSE,
        'status_code' => -1,
        'data'        => NULL
      );
    }
  }

  public function acceptInvitation($token) {
    try {
      return $this->_getUser('/api/invitations/acceptInvitation?token='.$token);
    } catch (Exception $ex) {
      return array(
        'resolved'    => FALSE,
        'status_code' => -1,
        'data'        => NULL
      );
    }
  }

  public function buildFormPatient($formId) {
    try {
      $data = array(
        'formId'=>$formId
      );
      return $this->_postUser('/api/questionnaires/buildFormPatient',json_encode($data));
    } catch (Exception $ex) {
      return array(
        'resolved'    => FALSE,
        'status_code' => -1,
        'data'        => NULL
      );
    }
  }

  public function getInvitationData($token) {
    try {
      return $this->_getServer('/api/invitations/get?token='.$token);
    } catch (Exception $ex) {
      return array(
        'resolved'    => FALSE,
        'status_code' => -1,
        'data'        => NULL
      );
    }
  }

  public function requestServiceDiagRep($data) {
    try {
      return $this->_postUser('/api/serviceRequest/requestRepWeb',json_encode($data));
    } catch (Exception $ex) {
      return array(
        'resolved'    => FALSE,
        'status_code' => -1,
        'data'        => NULL
      );
    }
  }


  //
  // Observations and diagnostics
  //
  public function getDiagnosticReport($obId) {
    try {
      return $this->_getUser('/api/diagnosticReports/getForBambusLife?observationId='.$obId);
    } catch (Exception $ex) {
      return array(
        'resolved'    => FALSE,
        'status_code' => -1,
        'data'        => NULL
      );
    }
  }

  public function getObservations() {
    try {
      return $this->_getUser('/api/observations/getUserObs');
    } catch (Exception $ex) {
      return array(
        'resolved'    => FALSE,
        'status_code' => -1,
        'data'        => NULL
      );
    }
  }

  public function getObservationsEcgPdf($obsId, $at) {
    try {
      $url = '/api/observations/getResultOfECGPDF/observacion-'.$obsId.'.pdf?observationId='.$obsId.'&access_token='.$at;
      return $this->_getRaw($url);
    } catch (Exception $ex) {
      return array(
        'resolved'    => FALSE,
        'status_code' => -1,
        'data'        => NULL
      );
    }
  }

  public function getSimpleObservation($patId, $obsId){
    try {
      $url = '/api/observations/getSimpleObservation?observationId='.$obsId.'&patientId='.$patId;
      return $this->_getUser($url);
    } catch (Exception $ex) {
      return array(
        'resolved'    => FALSE,
        'status_code' => -1,
        'data'        => NULL
      );
    }
  }

  public function getDiagReportPdf($obsId, $at) {
    try {
      $url = '/api/diagnosticReports/getReportPDF?obsId='.$obsId.'&access_token='.$at;
      return $this->_getRaw($url);
    } catch (Exception $ex) {
      return array(
        'resolved'    => FALSE,
        'status_code' => -1,
        'data'        => NULL
      );
    }
  }


  //
  // Appointments
  //
  public function getSpecialities() {
    try {
      return $this->_postUser('/api/categories/getWithCatalog', NULL);
    } catch (Exception $ex) {
      return array(
        'resolved'    => FALSE,
        'status_code' => -1,
        'data'        => NULL
      );
    }
  }

  public function getResources($term) {
    try {
      return $this->_postUser('/api/practitionerRoles/get', json_encode($term));
    } catch (Exception $ex) {
      return array(
        'resolved'    => FALSE,
        'status_code' => -1,
        'data'        => NULL
      );
    }
  }

  public function getAvailableTime($term) {
    try {
      return $this->_postUser('/api/appointments/getAvailableTime', json_encode($term));
    } catch (Exception $ex) {
      return array(
        'resolved'    => FALSE,
        'status_code' => -1,
        'data'        => NULL
      );
    }
  }

  public function confirmAppointment($appointment) {
    try {
      return $this->_postUser('/api/appointments/new', json_encode($appointment));
    } catch (Exception $ex) {
      return array(
        'resolved'    => FALSE,
        'status_code' => -1,
        'data'        => NULL
      );
    }
  }

  public function cancelAppointment($appointmentId){
    //cancelledByRole 
    $appointment = array(
      'appointmentId'      => $appointmentId,
      'cancelledByRole'    => 'PATIENT',
      'cancellationReason' => 'PAT'
    );
    try {
      return $this->_postUser('/api/appointments/cancel', json_encode($appointment));
    } catch (Exception $ex) {
      return array(
        'resolved'    => FALSE,
        'status_code' => -1,
        'data'        => NULL
      );
    }
  }

  public function getAppointments() {
    try {
      return $this->_getUser('/api/appointments/getAppointmentsBambus');
    } catch (Exception $ex) {
      return array(
        'resolved'    => FALSE,
        'status_code' => -1,
        'data'        => NULL
      );
    }
  }

  //
  // Service Request method
  //
  public function getServicePrice($serv_code, $country) {
    try {
      return $this->_postUser('/api/shops/getServicePrice?serv_code='.$serv_code.'&country='.$country, null);
    } catch (Exception $ex) {
      return array(
        'resolved'    => FALSE,
        'status_code' => -1,
        'data'        => NULL
      );
    }
  }

  public function getAppointment($appId) {
    try {
      $term = array('id'=>$appId);
      return $this->_postUser('/api/appointments/getAppointmentBambus', json_encode($term));
    } catch (Exception $ex) {
      return array(
        'resolved'    => FALSE,
        'status_code' => -1,
        'data'        => NULL
      );
    }
  }

  public function prepareServiceRequest($data) {
    try {
      return $this->_postUser('/api/serviceRequest/prepareServiceRequest',json_encode($data));
    } catch (Exception $ex) {
      return array(
        'resolved'    => FALSE,
        'status_code' => -1,
        'data'        => NULL
      );
    }
  }
}
