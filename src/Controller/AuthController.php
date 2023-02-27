<?php

namespace App\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\JsonResponse;

use App\Service\HubClient;
use App\Utils\Settings;

class AuthController extends AbstractController {

  private $hubClient;
  private $logger;

  public function __construct(HubClient $hubClient, LoggerInterface $logger){
    $this->hubClient = $hubClient;
    $this->logger    = $logger;
  }


  public function __login(Request $request, $email, $password){
    $saveDT_Token = FALSE;

    // Check device token
    if (!$request->cookies->has('bl_dt')){
      $this->logger->debug("AuthController|__login|DeviceToken not found, will request one...");
      // Must request deviceToken
      $cliRes = $this->hubClient->registerDevice();
      if (!$cliRes['resolved']){
        $this->logger->error("AuthController|__login|Error requesting new deviceToken!!");
        return array(
          'status_code'  => $cliRes['status_code'],
          'resolved'     => FALSE,
          'msg'          => 'No es posible iniciar sesion en este momento (4)',
          'data'         => NULL,
          'dt'           => NULL,
          'saveDT_Token' => $saveDT_Token
        );
      }else{
        if ($cliRes['status_code'] != 200 && $cliRes['status_code'] != 0){
          $this->logger->error("AuthController|__login|Server returned status code ".$cliRes['status_code']." requesting new deviceToken!!");
          return array(
            'status_code'  => $cliRes['status_code'],
            'resolved'     => FALSE,
            'msg'          => 'No es posible iniciar sesion en este momento (3)',
            'data'         => NULL,
            'dt'           => NULL,
            'saveDT_Token' => $saveDT_Token
          );
        }else{
          $dt = $cliRes['data']['result']['deviceToken'];
          $this->logger->debug("AuthController|__login|New device token ".$dt." acquired");
          $saveDT_Token = TRUE;
        }
      }
    }else{
      // Read dt from cookie
      $dt = $request->cookies->get('bl_dt');
    }
    
    $cliRes = $this->hubClient->login($email, $password, $dt);
    if (!$cliRes['resolved']){
      $this->logger->error("AuthController|__login|Error requesting login!!");
      return array(
        'status_code'  => $cliRes['status_code'],
        'resolved'     => FALSE,
        'msg'          => 'No es posible iniciar sesion en este momento (1)',
        'data'         => NULL,
        'dt'           => $dt,
        'saveDT_Token' => $saveDT_Token
      );
    }else{

      if ($cliRes['status_code'] != 200 && $cliRes['status_code'] != 0 && $cliRes['status_code'] != 401){
        $this->logger->error("AuthController|__login|Server returned status code ".$cliRes['status_code']." requesting login!!");
        return array(
          'status_code'  => $cliRes['status_code'],
          'resolved'     => FALSE,
          'msg'          => 'No es posible iniciar sesion en este momento (2)',
          'data'         => NULL,
          'dt'           => $dt,
          'saveDT_Token' => $saveDT_Token
        );

      }else if ($cliRes['status_code'] == 401){
        $this->logger->info("AuthController|__login|Server returned status code ".$cliRes['status_code']." Wrong credentials.");
        return array(
          'status_code'  => $cliRes['status_code'],
          'resolved'     => FALSE,
          'msg'          => 'Las credenciales no son correctas',
          'data'         => NULL,
          'dt'           => $dt,
          'saveDT_Token' => $saveDT_Token
        );
      }else{
        $this->logger->debug("AuthController|__login|HubClient response is ", $cliRes);

        // Add extra data to User structure
        $cliRes['data']['user']['id']       = $cliRes['data']['userId'];
        $cliRes['data']['user']['at']       = $cliRes['data']['id'];
        $cliRes['data']['user']['roleName'] = $cliRes['data']['roleName'];
        $cliRes['data']['user']['roleId']   = $cliRes['data']['roleId'];

        return array(
          'resolved'     => TRUE,
          'msg'          => NULL,
          'data'         => $cliRes['data'],
          'dt'           => $dt,
          'saveDT_Token' => $saveDT_Token
        );

      }
    }
  }


  //Nuevo Cp
  public function __ventanillalogin(Request $request, $cedula){
    $saveDT_Token = FALSE;

    // Check device token
    if (!$request->cookies->has('bl_dt')){
      $this->logger->debug("AuthController|__ventanillalogin|DeviceToken not found, will request one...");
      // Must request deviceToken
      //Ori- $cliRes = $this->hubClient->registerDevice();
	  $cliRes = $this->hubClient->ventanillaRegisterDevice();
	  
      if (!$cliRes['resolved']){
        $this->logger->error("AuthController|__ventanillalogin|Error requesting new deviceToken!!");
        return array(
          'status_code'  => $cliRes['status_code'],
          'resolved'     => FALSE,
          'msg'          => 'No es posible iniciar sesion en este momento (4) - Ventanilla',
          'data'         => NULL,
          'dt'           => NULL,
          'saveDT_Token' => $saveDT_Token
        );
      }else{
        if ($cliRes['status_code'] != 200 && $cliRes['status_code'] != 0){
          $this->logger->error("AuthController|__ventanillalogin|Server returned status code ".$cliRes['status_code']." requesting new deviceToken!!");
          return array(
            'status_code'  => $cliRes['status_code'],
            'resolved'     => FALSE,
            'msg'          => 'No es posible iniciar sesion en este momento (3)',
            'data'         => NULL,
            'dt'           => NULL,
            'saveDT_Token' => $saveDT_Token
          );
        }else{
          $dt = $cliRes['data']['result']['deviceToken'];
          $this->logger->debug("AuthController|__ventanillalogin|New device token ".$dt." acquired");
          $saveDT_Token = TRUE;
        }
      }
    }else{
      // Read dt from cookie
      $dt = $request->cookies->get('bl_dt');
    }
    
    $cliRes = $this->hubClient->ventanillalogin($cedula, $dt);
    if (!$cliRes['resolved']){
      $this->logger->error("AuthController|__ventanillalogin|Error requesting login!!");
      return array(
        'status_code'  => $cliRes['status_code'],
        'resolved'     => FALSE,
        'msg'          => 'No es posible iniciar sesion en este momento (1)',
        'data'         => NULL,
        'dt'           => $dt,
        'saveDT_Token' => $saveDT_Token
      );
    }else{

      if ($cliRes['status_code'] != 200 && $cliRes['status_code'] != 0 && $cliRes['status_code'] != 401){
        $this->logger->error("AuthController|__ventanillalogin|Server returned status code ".$cliRes['status_code']." requesting login!!");
        return array(
          'status_code'  => $cliRes['status_code'],
          'resolved'     => FALSE,
          'msg'          => 'No es posible iniciar sesion en este momento (2)',
          'data'         => NULL,
          'dt'           => $dt,
          'saveDT_Token' => $saveDT_Token
        );

      }else if ($cliRes['status_code'] == 401){
        $this->logger->info("AuthController|__ventanillalogin|Server returned status code ".$cliRes['status_code']." Wrong credentials.");
        return array(
          'status_code'  => $cliRes['status_code'],
          'resolved'     => FALSE,
          'msg'          => 'Las credenciales no son correctas',
          'data'         => NULL,
          'dt'           => $dt,
          'saveDT_Token' => $saveDT_Token
        );
      }else{
        $this->logger->debug("AuthController|__ventanillalogin|HubClient response is ", $cliRes);

        // Add extra data to User structure
        $cliRes['data']['user']['id']       = $cliRes['data']['userId'];
        $cliRes['data']['user']['at']       = $cliRes['data']['id'];
        $cliRes['data']['user']['roleName'] = $cliRes['data']['roleName'];
        $cliRes['data']['user']['roleId']   = $cliRes['data']['roleId'];

        return array(
          'resolved'     => TRUE,
          'msg'          => NULL,
          'data'         => $cliRes['data'],
          'dt'           => $dt,
          'saveDT_Token' => $saveDT_Token
        );

      }
    }
  }
	

  public function login(Request $request){
    
    $last_username = '';
    if ($request->cookies->has('bl_lu')){
      $last_username = $request->cookies->get('bl_lu');
    }
    $loginUrlPost = '/login';
    if($request->query->get('r') != NULL) {
      $loginUrlPost = '/login?r='.$request->query->get("r");
    }
    return $this->render('user/login.html.twig', [
      'loginUrlPost'  => $loginUrlPost,
      'last_username' => $last_username,
      'error'         => false
    ]);
  }

  //Ventanilla unica
  public function ventanillalogin(Request $request){
    
    $last_cedula = '';
    if ($request->cookies->has('bl_lu')){
      $last_username = $request->cookies->get('bl_lu');
    }
    $ventanillaloginUrlPost = '/ventanillalogin';
    if($request->query->get('r') != NULL) {
      $ventanillaloginUrlPost = '/ventanillalogin?r='.$request->query->get("r");
    }
    return $this->render('user/ventanilla.html.twig', [
      'ventanillaloginUrlPost'  => $ventanillaloginUrlPost,
      'last_cedula' => $last_cedula,
      'error'         => false
    ]);
  }  

  public function reset_password_POST(Request $request , $tokenData) {
    $explodedTokenData = explode("|", $tokenData);
    $userId            = $explodedTokenData[0];
    $token             = $explodedTokenData[1];
    $from              = $explodedTokenData[2];
    $password          = $request->request->get('pass');
    $repeatPassword    = $request->request->get('confirmPassword');
    //
    // Request password reset to HubServer
    //
    $cliRes = $this->hubClient->_resetPassword($password, $repeatPassword, $userId , $token);
    // FOR TEST:
    // $cliRes = array();
    // $cliRes['status_code'] = 200;
    //
    // Check response
    //
    if ($cliRes['status_code'] != 200 && $cliRes['status_code'] != 0 && $cliRes['status_code'] != 401){
      $response = new Response($this->renderView('user/reset-password.html.twig', [
        'userId'   => $userId,
        'token'    => $token,
        'last_username' => null,
        'error'    => 'No se pudo actualizar la contraseña'
      ]), 200);
      return $response;
    } else {
      if ($from == 'hhgo'){
        $response = new Response($this->renderView('user/password-reseted.html.twig', [
          'link' => 'https://app.bambus.life',
          'error' => null
        ]), 200);
      }else{
        $response = new Response($this->renderView('user/login.html.twig', [
          'loginUrlPost' => '/login',
          'userId' => $userId,
          'token' => $token,
          'last_username' => null,
          'info_msg' => 'Se cambio la contraseña correctamente',
          'error' => null
        ]), 200);
      }
      return $response;
    }

  }

  public function reset_password(Request $request , $tokenData) {
    $explodedTokenData = explode("|", $tokenData);
    $userId = $explodedTokenData[0];
    $token  = $explodedTokenData[1];
    $from   = $explodedTokenData[2];
    $password = null;

    if ($request->cookies->has('bl_lu')){
      $last_username = $request->cookies->get('bl_lu');
    }

    return $this->render('user/reset-password.html.twig', [
      'userId' => $userId,
      'token'  => $token,
      'from'   => $from,
      'error'  => false
    ]);
  }

  public function sendEmailResetPassword(Request $request) {
    $email         = $request->request->get('email');
    $cliRes = $this->hubClient->resetPasswordRequest($email);
    $res = null;
    
    if($cliRes['status_code'] == 404){
      if($cliRes['data']['error']['code'] == 'EMAIL_NOT_FOUND'){
        $response = new Response($this->renderView('user/login.html.twig', [
          'loginUrlPost' => '/login',
          'last_username' => $email,
          'error'         => 'Este email no esta registrado en nuestro sistema.'
        ]), 200);
        return $response;
      }
      else{
        $response = new Response($this->renderView('user/login.html.twig', [
          'loginUrlPost' => '/login',
          'last_username' => $email,
          'error'         => 'Ocurrio un error, intente nuevamente mas tarde.'
        ]), 200);
        return $response;
      }
      
    }
    if (!$cliRes['resolved']){
    $this->logger->error("AuthController|__login|Error requesting login!!");

    $response = new Response($this->renderView('user/login.html.twig', [
      'loginUrlPost' => '/login',
      'last_username' => $email,
      'error'         => 'No es posible enviar mail en este momento (1)'
    ]), 200);
      return $response;
    }else{
      $response = new Response($this->renderView('user/login.html.twig', [
        'loginUrlPost' => '/login',
        'last_username' => $email,
        'error'         => null,
        'info_msg'      => 'Le enviamos un mail a '.$email.' siga las instrucciones para restablecer su contraseña'
      ]), 200);
      return $response;
    }
  }

  public function changePassword(Request $request) {
    $password         = $request->request->get('password');
    $repeatPassword   = $request->request->get('repeatPassword');

    if($repeatPassword == $password) {
      $response = new Response($this->renderView('user/login.html.twig', [
        'loginUrlPost' => '/login',
        'last_username' => $email,
        'error'         => null,
        'info_msg'      => 'La contraseña se cambio correctamente'
      ]), 200);
      return $response;
    } else {
      $this->logger->info("AuthController | changePassword | Passwords do not match");
      $response = new Response($this->renderView('user/reset-password.html.twig', [
        'error'         => "Las contraseñas no coinciden"
      ]), 200);
      return $response;
    }
  }

  public function forgot_password(Request $request) {
    $last_username = null;
    if ($request->cookies->has('bl_lu')){
      $last_username = $request->cookies->get('bl_lu');
    }

    return $this->render('user/forgotPassword.html.twig', [
      'last_username' => $last_username,
      'error'         => false
    ]);
  }

  public function doLogin(Request $request, $email, $password, $rememberMe) {
    $cookies = array();
    $loginRes = $this->__login($request, $email, $password);
    if (!$loginRes['resolved']){
      // ERROR
      return $loginRes;
    }else{
      //
      // LOGGED IN
      //
      // Set access_token cookie
      $at = $loginRes['data']['id'];
      if ($rememberMe){
        $cookieTTL = time() + $loginRes['data']['ttl'];
        $cookieAT  = Cookie::create('bl_at', $at, $cookieTTL);
        $cookies[] = $cookieAT;
      }
      // Set device_token cookie
      if ($loginRes['saveDT_Token']){
        $cookieDT  = Cookie::create('bl_dt', $loginRes['dt'], time() + (10 * 365 * 24 * 60 * 60));
        $cookies[] = $cookieDT;
      }
      // Get user data for session
      $this->hubClient->setAccessToken($at);
      $cliRes = $this->hubClient->getUserData();
      if (!$cliRes['resolved']){
        $this->logger->error("AuthController|doLogin|Error requesting user data!!");
        return NULL;
      }else{
        if ($cliRes['status_code'] != 200 && $cliRes['status_code'] != 0){
          $this->logger->error("AuthController|doLogin|Server returned status code ".$cliRes['status_code']." requesting getUserData!!");
          return NULL;
        }else{
          // Store user data in session
          $cliRes['data']['at'] = $at;
          $this->get('session')->set('current_user', $cliRes['data']);
          // Return Cookies to be set on response
          $loginRes['cookies'] = $cookies;
          return $loginRes;
        }
      }
    }
  }

  //Nuevo Cp
   public function doventanillaLogin(Request $request, $cedula) {
    $cookies = array();
    $loginRes = $this->__ventanillalogin($request, $cedula);
    if (!$loginRes['resolved']){
      // ERROR
      return $loginRes;
    }else{
      //
      // LOGGED IN
      //
      // Set access_token cookie
      $at = $loginRes['data']['id'];
      if ($rememberMe){
        $cookieTTL = time() + $loginRes['data']['ttl'];
        $cookieAT  = Cookie::create('bl_at', $at, $cookieTTL);
        $cookies[] = $cookieAT;
      }
      // Set device_token cookie
      if ($loginRes['saveDT_Token']){
        $cookieDT  = Cookie::create('bl_dt', $loginRes['dt'], time() + (10 * 365 * 24 * 60 * 60));
        $cookies[] = $cookieDT;
      }
      // Get user data for session
      $this->hubClient->setAccessToken($at);
      $cliRes = $this->hubClient->getUserData();
      if (!$cliRes['resolved']){
        $this->logger->error("AuthController|doventanillaLogin|Error requesting user data!!");
        return NULL;
      }else{
        if ($cliRes['status_code'] != 200 && $cliRes['status_code'] != 0){
          $this->logger->error("AuthController|doventanillaLogin|Server returned status code ".$cliRes['status_code']." requesting getUserData!!");
          return NULL;
        }else{
          // Store user data in session
          $cliRes['data']['at'] = $at;
          $this->get('session')->set('current_user', $cliRes['data']);
          // Return Cookies to be set on response
          $loginRes['cookies'] = $cookies;
          return $loginRes;
        }
      }
    }
  }


  public function loginPost(Request $request){
    $saveDT_Token = FALSE;

    // Check params
    $email         = $request->request->get('email');
    $password      = $request->request->get('password');
    $rememberMe    = ("on" == $request->request->get('_remember_me'));
    $last_username = $email;
    $this->logger->debug(sprintf("AuthController|doLogin|Requesting login for %s", $email));

    $loginRes = $this->doLogin($request, $email, $password, $rememberMe);

    // $loginRes = $this->__login($request, $email, $password);
    if ($loginRes == NULL || !$loginRes['resolved']){
      if ($loginRes == NULL){
        $errMsg = 'Ocurrió un error intentando iniciar sesión';
      }else{
        $errMsg = $loginRes['msg'];
      }
      // Error logging in
      $response = new Response($this->renderView('user/login.html.twig', [
        'loginUrlPost' => '/login',
        'last_username' => $last_username,
        'error'         => $errMsg
      ]), 200);
      $response->headers->setCookie(Cookie::create('bl_lu', $last_username, time() + (365 * 24 * 60 * 60)));
      
      // // Check DT new cookie
      // if ($loginRes['saveDT_Token']){
      //   $cookieDT  = Cookie::create('bl_dt', $loginRes['dt'], time() + (10 * 365 * 24 * 60 * 60));
      //   $response->headers->setCookie($cookieDT);
      // }
      if (array_key_exists('cookies', $loginRes)){
        foreach ($loginRes['cookies'] as $cookie) {
          $response->headers->setCookie($cookie);
        }
      }
      return $response;
    }else{
      // Preapre Response (setting the locale according to user country)
      if($request->query->get('r') != NULL) {
        $response = new RedirectResponse($request->query->get('r'));
      } else {
        // $response = new RedirectResponse(strtolower($loginRes['data']['user']['countryCode']).'/');
        $response = new RedirectResponse('/account');
      }
      $response->headers->setCookie(Cookie::create('bl_lu', $last_username, time() + (365 * 24 * 60 * 60)));

      if (array_key_exists('cookies', $loginRes)){
        foreach ($loginRes['cookies'] as $cookie) {
          $response->headers->setCookie($cookie);
        }
      }

      return $response;
    }
  }

  //Nuevo metodo para ventanilla unica	
  public function ventanillaloginPost(Request $request){
    $saveDT_Token = FALSE;

    // Check params
    $cedula        = $request->request->get('cedula');
    //$password      = $request->request->get('password');
    //$rememberMe    = ("on" == $request->request->get('_remember_me'));
    $last_cedula = $cedula;
    $this->logger->debug(sprintf("AuthController|ventanilladoLogin|Requesting ventanilla login for %s", $cedula));

    //$loginRes = $this->doLogin($request, $email, $password, $rememberMe);
	$loginRes = $this->doventanillaLogin($request, $cedula);

    // $loginRes = $this->__login($request, $email, $password);
    if ($loginRes == NULL || !$loginRes['resolved']){
      if ($loginRes == NULL){
        $errMsg = 'Ocurrió un error intentando iniciar sesión';
      }else{
        $errMsg = $loginRes['msg'];
      }
      // Error logging in
      $response = new Response($this->renderView('user/ventanilla.html.twig', [
        'ventanillaloginUrlPost' => '/ventanillalogin',
        'last_cedula' => $last_cedula,
        'error'         => $errMsg
      ]), 200);
      $response->headers->setCookie(Cookie::create('bl_lu', $last_cedula, time() + (365 * 24 * 60 * 60)));
      
      // // Check DT new cookie
      // if ($loginRes['saveDT_Token']){
      //   $cookieDT  = Cookie::create('bl_dt', $loginRes['dt'], time() + (10 * 365 * 24 * 60 * 60));
      //   $response->headers->setCookie($cookieDT);
      // }
      if (array_key_exists('cookies', $loginRes)){
        foreach ($loginRes['cookies'] as $cookie) {
          $response->headers->setCookie($cookie);
        }
      }
      return $response;
    }else{
      // Preapre Response (setting the locale according to user country)
      if($request->query->get('r') != NULL) {
        $response = new RedirectResponse($request->query->get('r'));
      } else {
        // $response = new RedirectResponse(strtolower($loginRes['data']['user']['countryCode']).'/');
        $response = new RedirectResponse('/account');
      }
      $response->headers->setCookie(Cookie::create('bl_lu', $last_username, time() + (365 * 24 * 60 * 60)));

      if (array_key_exists('cookies', $loginRes)){
        foreach ($loginRes['cookies'] as $cookie) {
          $response->headers->setCookie($cookie);
        }
      }

      return $response;
    }
  }


  public function loginAjax(Request $request){
    $parametersAsArray = NULL;
    if ($content = $request->getContent()) {
      $parametersAsArray = json_decode($content, true);
    }
    if ($parametersAsArray == NULL){
      $jsonRes = array('logged' => TRUE);
      $response = new JsonResponse($jsonRes);
      return $response;
    }
    $email         = $parametersAsArray['email'];
    $password      = $parametersAsArray['password'];
    $rememberMe    = ("on" == $parametersAsArray['_remember_me']);
    $this->logger->debug(sprintf("AuthController|loginAjax|Requesting login for %s", $email));

    $loginRes = $this->doLogin($request, $email, $password, $rememberMe);

    if (!$loginRes['resolved']){
      $jsonRes = array('logged' => FALSE);
      $response = new JsonResponse($jsonRes);
      return $response;
    }else{
      $jsonRes = array('logged' => TRUE);
      $response = new JsonResponse($jsonRes);
      if (array_key_exists('cookies', $loginRes)){
        foreach ($loginRes['cookies'] as $cookie) {
          $response->headers->setCookie($cookie);
        }
      }
      return $response;
    }
  }

   public function ventanillaloginAjax(Request $request){
    $parametersAsArray = NULL;
    if ($content = $request->getContent()) {
      $parametersAsArray = json_decode($content, true);
    }
    if ($parametersAsArray == NULL){
      $jsonRes = array('logged' => TRUE);
      $response = new JsonResponse($jsonRes);
      return $response;
    }
    $email         = $parametersAsArray['email'];
    $password      = $parametersAsArray['password'];
    $rememberMe    = ("on" == $parametersAsArray['_remember_me']);
    $this->logger->debug(sprintf("AuthController|loginAjax|Requesting login for %s", $email));

    //Ori- $loginRes = $this->doLogin($request, $email, $password, $rememberMe);
	$loginRes = $this->doventanillaLogin($request, $cedula);

    if (!$loginRes['resolved']){
      $jsonRes = array('logged' => FALSE);
      $response = new JsonResponse($jsonRes);
      return $response;
    }else{
      $jsonRes = array('logged' => TRUE);
      $response = new JsonResponse($jsonRes);
      if (array_key_exists('cookies', $loginRes)){
        foreach ($loginRes['cookies'] as $cookie) {
          $response->headers->setCookie($cookie);
        }
      }
      return $response;
    }
  }


  public function registerAjax(Request $request){
    $parametersAsArray = NULL;
    if ($content = $request->getContent()) {
      $parametersAsArray = json_decode($content, true);
    }
    if ($parametersAsArray == NULL){
      $jsonRes = array('logged' => TRUE);
      $response = new JsonResponse($jsonRes);
      return $response;
    }
    $email         = $parametersAsArray['email'];
    $password      = $parametersAsArray['password'];
    $last_username = $email;
    $userData = array(
      "username"     => $parametersAsArray['firstname'].' '.$parametersAsArray['lastname'],
      "firstname"    => $parametersAsArray['firstname'],
      "lastname"     => $parametersAsArray['lastname'],
      "email"        => $email,
      "password"     => $password,
      "countryCode"  => $parametersAsArray['regcountry'],
      "countryName"  => Settings::countries[strtolower($parametersAsArray['regcountry'])]['name'],
      "address"      => array(
        "cityId"        => $parametersAsArray['cityId'],
        "cityName"      => $parametersAsArray['cityName'],
        "address_line1" => $parametersAsArray['address'],
        "phone"         => $parametersAsArray['phonenumber']
      )
    );
    $this->logger->debug('AuthController|registerAjax|Registering user ' . $email . '...');
    $registerRes = $this->hubClient->upsertUser($userData);
    if (!$registerRes['resolved']){
      $this->logger->error('AuthController|registerAjax|Error registering email ' . $email);
      $jsonRes = array('logged' => FALSE, 'msg' => 'Ocurrió un error intentando registrar un nuevo usuario');
      $response = new JsonResponse($jsonRes);
      return $response;
    }else{
      $jsonRes = array('logged' => TRUE);
      $response = new JsonResponse($jsonRes);
      // Do Login
      $this->logger->debug('AuthController|registerAjax|Processing login for email ' . $email);
      $loginRes = $this->doLogin($request, $email, $password, FALSE);
      if ($loginRes == NULL || !$loginRes['resolved']){
        $this->logger->error('AuthController|registerAjax|Error logging email ' . $email);
        // Check DT new cookie
        if ($loginRes['saveDT_Token']){
          $cookieDT  = Cookie::create('bl_dt', $loginRes['dt'], time() + (10 * 365 * 24 * 60 * 60));
          $response->headers->setCookie($cookieDT);
        }
        // Error logging in
        $jsonRes = array('logged' => FALSE, 'msg' => 'Ocurrió un error intentando iniciar sesión del usuario creado');
        $response = new JsonResponse($jsonRes);
        return $response;
      }else{
        $response->headers->setCookie(Cookie::create('bl_lu', $last_username, time() + (365 * 24 * 60 * 60)));
        if (array_key_exists('cookies', $loginRes)){
          foreach ($loginRes['cookies'] as $cookie) {
            $response->headers->setCookie($cookie);
          }
        }
        return $response;
      }
    }
  }

  public function getUserData(Request $request){
    // Check user data in session
    if ($this->get('session')->has('current_user')){
      $this->logger->debug("AuthController|getUserData|Found user data in session");
      return $this->get('session')->get('current_user');
    }else{
      // Must request to server
      if ($request->cookies->has('bl_at')){
        $this->logger->debug("AuthController|getUserData|Users has AT but data is not in session.");
        $at = $request->cookies->get('bl_at');
        $this->hubClient->setAccessToken($at);
        $cliRes = $this->hubClient->getUserData();
        if (!$cliRes['resolved']){
          $this->logger->error("AuthController|getUserData|Error requesting user data!!");
          return NULL;
        }else{
          if ($cliRes['status_code'] != 200 && $cliRes['status_code'] != 0){
            $this->logger->error("AuthController|getUserData|Server returned status code ".$cliRes['status_code']." requesting getUserData!!");
            return NULL;
          }else{
            // Add at to user data in session
            $cliRes['data']['at'] = $at;
            // Store user data in session
            $this->logger->debug("AuthController|getUserData|Got user data from server");
            $this->get('session')->set('current_user', $cliRes['data']);
            return $cliRes['data'];
          }
        }
      }else{
        // User is not logged in
        return NULL;
      }
    }
  }

  public function logout(){
    $this->logger->debug("AuthController|logout|Logging out user!!");
    $response = new RedirectResponse('/');
    $response->headers->clearCookie('bl_at');
    //$this->get('session')->remove('current_user');
    $this->get('session')->start();
    $this->get('session')->invalidate();
    return $response;
  }

  public function organizationRegister(Request $request) {

    $organizationName = $request->request->get('organizationName');
    $organizationPhone = $request->request->get('orgphone');
    $organizationAddress = $request->request->get('organizationAddress');
    $isVirtual = $request->request->get('isVirtual');
    $locationName = $request->request->get('locationName1');
    $country = $request->request->get('country');
    $city = $request->request->get('selCity');
    $locationAddress = $request->request->get('locationAddress1');
    $organization = array(
      'organizationName'    => $organizationName,
      'organizationPhone'   => $organizationPhone,
      'organizationAddress' => $organizationAddress,
      'isVirtual'           => $isVirtual,
      'virtualService'      => $isVirtual,
      'locationName'        => $locationName,
      'country'             => $country,
      'city'                => $city,
      'locationAddress'     => $locationAddress
    );

    $user = $this->getUserData($request);
    
    $this->hubClient->setAccessToken($user['at']);
    $response = $this->hubClient->insertOrganization($organization);

    if ($response['resolved'] 
          && !(isset($response['data']) 
          && isset($response['data']['error']) 
          && isset($response['data']['error']['code']))){

          $cities = $this->hubClient->getCityByCountryCode('UY');
          $navCountry = strtoupper($request->getLocale());
          
          $this->logger->info("AuthController|organizationRegister|OK!!");

          
        $this->hubClient->setAccessToken($user['at']);
        $cliRes = $this->hubClient->getUserData();

        // print_r($cliRes);
        // die();

        if (!$cliRes['resolved']){
          $this->logger->error("AuthController|organizationRegister|Error requesting user data!!");
          return $this->render('checkout/warning-pro.html.twig', [
            'hasOrganization' => $hasOrganization,
            'organization'    => json_encode($user['organization']),
            'navCountry'      => $navCountry,
            'countries'       => Settings::countries,
            'cities'          => $cities['data'],
            'user'            => $user,
            'error_msg'       => "Ocurrio un error al registrar los datos"
          ]);
        }else{
          if ($cliRes['status_code'] != 200 && $cliRes['status_code'] != 0){
            $this->logger->error("AuthController|organizationRegister|Server returned status code ".$cliRes['status_code']." requesting getUserData!!");
            return $this->render('checkout/warning-pro.html.twig', [
              'hasOrganization' => $hasOrganization,
              'organization'    => json_encode($user['organization']),
              'navCountry'      => $navCountry,
              'countries'       => Settings::countries,
              'cities'          => $cities['data'],
              'user'            => $user,
              'error_msg'       => "Ocurrio un error al registrar los datos"
            ]);
          }else{
            // Store user data in session
            $cliRes['data']['at'] = $user['at'];
            $this->get('session')->set('current_user', $cliRes['data']);
            
            return $this->redirectToRoute('checkout_shipping',array('wpa'=>'1'));
          }
        }

    }else{
      $this->logger->error("AuthController|organizationRegister|Error inserting practitioner");
      
      $cities = $this->hubClient->getCityByCountryCode('UY');
      $navCountry = strtoupper($request->getLocale());
      $user = $this->getUserData($request);
      $hasOrganization = FALSE;
      if($user['organization'] && $user['organization']['alias']!='BLUY') {
        $hasOrganization = TRUE;
      }

      return $this->render('checkout/warning-pro.html.twig', [
        'hasOrganization' => $hasOrganization,
        'organization'    => json_encode($user['organization']),
        'navCountry'      => $navCountry,
        'countries'       => Settings::countries,
        'cities'          => $cities['data'],
        'user'            => $user,
        'error_msg'       => "Ocurrio un error al registrar los datos"
      ]);
    }
  }

  public function practitionerRegister(Request $request) {
    $navCountry = strtoupper($request->getLocale());

    $cliRes = $this->hubClient->getSpecialities();
    //$cities = $this->hubClient->getCityByCountryCode('UY');
    if (!$cliRes['resolved']){
      $this->logger->error("AuthController|__login|Error getting countries!!");
      return $this->render('user/practitionerRegister.html.twig', [
        'navCountry'           => $navCountry,
        'specialities'         => $cliRes['data'],
        'specialitiesSelected' => [],
        'countries'            => Settings::countries,
        'error'                => 'Ocurrio un error al intentar cargar los datos'
      ]);
    }else{
      return $this->render('user/practitionerRegister.html.twig', [
        'navCountry'           => $navCountry,
        'specialities'         => $cliRes['data'],
        'specialitiesSelected' => [],
        'countries'            => Settings::countries,
        'error'                => false
      ]);
    }
    
  }
  
  public function insertPractitioner(Request $request) {
    $navCountry = strtoupper($request->getLocale());

    // print_r($request->request);
    // die();

    //
    // Recaptcha verification
    //
    $recaptcha_url      = 'https://www.google.com/recaptcha/api/siteverify';
    $recaptcha_secret   = $_ENV["RECAPTCHA_KEY"];
    $recaptcha_response = $_POST['recaptcha_response'];
    $this->logger->info("AuthController | insertPractitioner | Checking captcha with ".$recaptcha_response.".");
    $recaptcha = file_get_contents($recaptcha_url . '?secret=' . $recaptcha_secret . '&response=' . $recaptcha_response);
    $recaptcha = json_decode($recaptcha);

    // Take action based on the score returned:
    if ($recaptcha->score >= 0.5) {
      // Verified - CONTINUE
      // print_r($request->request);
      // die();
      $name         = $request->request->get('firstname')." ".$request->request->get('lastname');
      $firstname    = $request->request->get('firstname');
      $lastname     = $request->request->get('lastname');
      $email        = $request->request->get('email');
      $password     = $request->request->get('password');
      $country      = $request->request->get('country');
      $cityId       = $request->request->get('cityId');
      $cityName     = $request->request->get('cityName');
      $address      = $request->request->get('address');
      $phone        = $request->request->get('phone');
      $orgphone     = $request->request->get('orgphone');
      $specialities = $request->request->get('specialities');
      $isVirtual    = $request->request->get('isVirtual');

      $graduations = [];
      for($i=0 ; $i<count($specialities) ; $i++) {
        array_push($graduations , 
          array(
            'specialityId' => $specialities[$i]
          )
      );
      }
      
      $practitioner = array(
        'name'         => $name,
        'firstname'    => $firstname,
        'lastname'     => $lastname,
        'email'        => $email,
        'password'     => $password,
        'country'      => $country,
        'cityId'       => $cityId,
        'cityName'     => $cityName,
        'primaryPhone' => $phone,
        'organization' => array(
          'name'           => $request->request->get('organizationName'),
          'address'        => $request->request->get('organizationAddress'),
          'primaryPhone'   => $orgphone,
          'virtualService' => $request->request->get('isVirtual'),
          'aparecer'       => TRUE,
          'location'       => array( 
            'name'    => $request->request->get('locationName1'),
            'address' => $request->request->get('locationAddress1')
          )
        ),
        'Graduations'=>$graduations
      );
      $response = $this->hubClient->registerPractitioner($practitioner);
      $cliResSpecialities = $this->hubClient->getSpecialities();
      $specialities = [];

      if (!$cliResSpecialities['resolved'] || ($cliResSpecialities['status_code'] != 200 && $cliResSpecialities['status_code'] != 0)) {
        $this->logger->error("AuthController | insertPractitioner | Error getting the specialities. | Server returned status code ".$cliResSpecialities['status_code']." requesting insergetSpecialitiestProfile!!");
      } else {
        $specialities = $cliResSpecialities['data'];
      }

      if (!$response['resolved'] || ($response['status_code'] != 200 && $response['status_code'] != 0)) {
        $this->logger->error("AuthController | insertPractitioner | Error inserting practitioner | Server returned status code ".$response['status_code']." requesting insertPractitioner!!");

        $errMsg = 'Ocurrió un error al registrar el médico';
        if (array_key_exists('data', $response)){
          if (array_key_exists('error', $response['data'])){
            if (array_key_exists('message', $response['data']['error'])){
              $errMsg = $response['data']['error']['message'];
            }
          }
        }

        return $this->render('user/practitionerRegister.html.twig', [
          'specialities'         => $specialities,
          'specialitiesSelected' => [],
          'countries'            => Settings::countries,
          'navCountry'           => $navCountry,
          'error'                => $errMsg
        ]);
      } else {
        $this->logger->debug("AuthController | insertPractitioner | Success.");
        return $this->render('user/welcomePractitioner.html.twig', [
          'practitionerName'  => $name
        ]);
      }
    }else{
      $cliResSpecialities = $this->hubClient->getSpecialities();
      $specialities = [];

      if (!$cliResSpecialities['resolved'] || ($cliResSpecialities['status_code'] != 200 && $cliResSpecialities['status_code'] != 0)) {
        $this->logger->error("AuthController | insertPractitioner | Error getting the specialities. | Server returned status code ".$cliResSpecialities['status_code']." requesting insergetSpecialitiestProfile!!");
      } else {
        $specialities = $cliResSpecialities['data'];
      }
      $this->logger->error("AuthController | insertPractitioner | Captcha failed!");
      return $this->render('user/practitionerRegister.html.twig', [
        'specialities'         => $specialities,
        'specialitiesSelected' => [],
        'countries'            => Settings::countries,
        'navCountry'           => $navCountry,
        'error'                => 'No se pudo verificar el control contra robots. Por favor inténtelo de nuevo.'
      ]);
    }
  }

  public function patientRegistration(Request $request){
    $navCountry = strtoupper($request->getLocale());

    // $patient = array(
    //   'document'         => '',
    //   'given'            => '',
    //   'family'           => '',
    //   'gender'           => '',
    //   'birthdate'        => NULL,
    //   'homeAddress'      => '',
    //   'homePrimaryPhone' => ''
    // );
    $completed = $request->query->get('c') == '1';

    return $this->render('user/register.html.twig', [
      'completed'  => $completed,
      'country'    => $navCountry,
      // 'patient'    => $patient,
      'error'      => NULL
    ]);
  }

  public function getCompleteRegistration(Request $request, $token){
    $navCountry = strtoupper($request->getLocale());

    $cliRes = $this->hubClient->getCompleteRegistration($token);
    if (!$cliRes['resolved']){
      $this->logger->error("AuthController|completeRegistrarion|Error getting countries!!");
      return $this->render('user/completeRegistration.html.twig', [
        'completed'  => FALSE,
        'country'    => $navCountry,
        'token'      => $token,
        'patient'    => NULL,
        'error'      => 'Ocurrio un error al intentar cargar los datos'
      ]);
    }else{

      // prepare date for date input
      $cliRes['data']['birthdate'] = substr($cliRes['data']['birthdate'], 0, 10);

      return $this->render('user/completeRegistration.html.twig', [
        'completed'  => FALSE,
        'country'    => $cliRes['data']['countryCode'],
        'token'      => $token,
        'patient'    => $cliRes['data'],
        'error'      => NULL
      ]);
    }
  }

  public function postCompleteRegistration(Request $request, $token){
    $navCountry = strtoupper($request->getLocale());
    // print_r($request->request);
    // die();
    $mode = $request->request->get('mode');
    $patient = array(
      'token'            => $token,
      'document'         => $request->request->get('inputDocument'),
      'given'            => $request->request->get('inputGiven'),
      'family'           => $request->request->get('inputFamily'),
      'gender'           => $request->request->get('gender'),
      'birthdate'        => $request->request->get('inputBirthdate'),
      'homeAddress'      => $request->request->get('inputHomeAddress'),
      'homeCityId'       => $request->request->get('inputHomeCityId'),
      'homePrimaryPhone' => $request->request->get('homePrimaryPhone'),
      'password'         => $request->request->get('password')
    );
    $cliRes = $this->hubClient->setPatientCompleteRegistration($patient);
    // print_r($cliRes);
    // die();
    if (!$cliRes['resolved']){
      $this->logger->error("AuthController|postCompleteRegistration|Error on setCompleteRegistration!!");
      return $this->render('user/completeRegistration.html.twig', [
        'mode'       => $mode,
        'completed'  => FALSE,
        'country'    => $navCountry,
        'token'      => $token,
        'patient'    => NULL,
        'error'      => 'Ocurrio un error al intentar guardar los datos'
      ]);
    }else{
      if ($cliRes['status_code'] != 200 && $cliRes['status_code'] != 204){
        $this->logger->error("AuthController|postCompleteRegistration|Error on setCompleteRegistration!!");
        return $this->render('user/completeRegistration.html.twig', [
          'mode'       => $mode,
          'completed'  => FALSE,
          'country'    => $navCountry,
          'token'      => $token,
          'patient'    => NULL,
          'error'      => 'Ocurrio un error al intentar guardar los datos (2)'
        ]);
      }else{
         // Do Login
        $email = $cliRes['data']['email'];
        $this->logger->debug('AuthController|postCompleteRegistration|Processing login for email ' . $email);
        $loginRes = $this->doLogin($request, $email, $patient['password'], FALSE);
        if ($loginRes == NULL || !$loginRes['resolved']){
          $this->logger->error('AuthController|postCompleteRegistration|Error logging email ' . $email);
          $response = $this->render('user/completeRegistration.html.twig', [
            'mode'       => $mode,
            'completed'  => FALSE,
            'country'    => $navCountry,
            'token'      => $token,
            'patient'    => NULL,
            'error'      => 'Ocurrió un error intentando iniciar sesión'
            ]);
          // Check DT new cookie
          if ($loginRes['saveDT_Token']){
            $cookieDT  = Cookie::create('bl_dt', $loginRes['dt'], time() + (10 * 365 * 24 * 60 * 60));
            $response->headers->setCookie($cookieDT);
          }
          return $response;
        }else{
          $response = $this->render('user/completeRegistration.html.twig', [
            'mode'       => $mode,
            'completed'  => TRUE,
            'country'    => $navCountry,
            'token'      => $token,
            'patient'    => NULL,
            'error'      => NULL
          ]);
          $response->headers->setCookie(Cookie::create('bl_lu', $email, time() + (365 * 24 * 60 * 60)));
          if (array_key_exists('cookies', $loginRes)){
            foreach ($loginRes['cookies'] as $cookie) {
              $response->headers->setCookie($cookie);
            }
          }
          return $response;
        }
      }
    }

  }

  //
  // API Methods
  //
  public function checkEmail(Request $request){
    $email = $request->query->get('em');
    if (strlen($email) == 0){
      $response = new JsonResponse(NULL);
      return $response;
    }
    $mailExists = $this->hubClient->checkEmail($email);
    $response = new JsonResponse($mailExists);
    return $response;
  }


  public function getCitiesByCountry($countryCode){
    $cities = $this->hubClient->getCityByCountryCode($countryCode);
    $response = new JsonResponse($cities['data']);
    return $response;
  }

  public function searchCity($countryCode, $term){
    $cities = $this->hubClient->searchCity($countryCode, urlencode($term));
    $response = new JsonResponse($cities['data']);
    return $response;
  }
}
