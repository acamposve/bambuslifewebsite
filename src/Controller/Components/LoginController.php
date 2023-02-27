<?php

namespace App\Controller\Components;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

use App\Controller\AuthController;
use App\Utils\Settings;

class LoginController extends AbstractController {

  private $authCtrl;
  private $logger;

  public function __construct(AuthController $authCtrl, LoggerInterface $logger){
    $this->authCtrl = $authCtrl;
    $this->logger = $logger;
  }


  /**
   * Params
   *   - email             => preloaded email
   *   - mailExists        => indicate if email already exists (if exists automatically jumps to password step)
   *   - showRememerMe     => indicates if must show remember me option
   *   - allowChangeEmail  => if email is specified, this indicates if it can be changed or not
   *   - loginBtnText      => text for the login button
   *   - registerBtnText   => text for the register button
   */
  public function index(Request $request, 
                        $email=NULL, 
                        $mailExists=FALSE, 
                        $showRememberMe=TRUE, 
                        $allowChangeEmail=TRUE,
                        $loginBtnText="Siguiente",
                        $registerBtnText="Siguiente",
                        $title="Acceder",
                        $showBillAsOption=TRUE){
    $navCountry = strtoupper($request->getLocale());


    return $this->render('components/login.html.twig', [
      'navCountry'        => $navCountry,
      'countries'         => Settings::countries,
      'showRememberMe'    => $showRememberMe,
      'title'             => $title,
      'email'             => $email,
      'mailExists'        => $mailExists,
      'allowChangeEmail'  => $allowChangeEmail,
      'loginBtnText'      => $loginBtnText,
      'registerBtnText'   => $registerBtnText,
      'showBillAsOption'  => $showBillAsOption
    ]);
  }

}
