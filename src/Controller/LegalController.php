<?php

namespace App\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

use App\Service\HubClient;
use App\Service\NgageClient;
use App\Controller\AuthController;
use App\Utils\Settings;

class LegalController extends AbstractController {

  private $hubClient;
  private $logger;

  public function __construct(HubClient $hubClient, NgageClient $ngageClient, LoggerInterface $logger, AuthController $authCtrl){
    $this->hubClient = $hubClient;
    $this->ngageClient = $ngageClient;
    $this->logger    = $logger;
    $this->authCtrl  = $authCtrl;
  }

  //
  // Local resolvers
  //
  public function _resolve_termsOfUse(Request $request){
    $user = $this->authCtrl->getUserData($request);
    if ($user && array_key_exists('countryCode', $user)){
      $cc = strtolower($user['countryCode']);
      return $this->redirectToRoute('terms_use', array('_locale' => $cc));
    }else{
      // Get country using the IP
      $country = 'uy';
      $res = $this->hubClient->getUserCountry();
      if ($res['status_code'] != 200) {
        $this->logger->error("LegalController|_resolve_termsOfUse| Error en hubClient.getUserCountry() | (".$res['status_code']." | " . json_encode($res['data']) . ")");
        return $this->redirectToRoute('terms_use', array('_locale' => 'uy'));
      } else {
        $country = strtolower($res['data']['country_code']);
        $this->logger->info("LegalController|_resolve_termsOfUse| Resolved. | (".$res['status_code']." | " . json_encode($res['data']) . ")");
        if (!array_key_exists($country, Settings::countries)) {
          $this->logger->info("LegalController|_resolve_termsOfUse| Country not found in the list of countries. Will default to UY.");
          return $this->redirectToRoute('listLocal', array('r' => '/uy/terms-of-use'));
        }else{
          return $this->redirectToRoute('terms_use', array('_locale' => $country));
        }
      }
    }
  }

  public function _resolve_termsAndConditions(Request $request){
    $user = $this->authCtrl->getUserData($request);
    if ($user && array_key_exists('countryCode', $user)){
      $cc = strtolower($user['countryCode']);
      return $this->redirectToRoute('terms_conditions', array('_locale' => $cc));
    }else{
      // Get country using the IP
      $country = 'uy';
      $res = $this->hubClient->getUserCountry();
      if ($res['status_code'] != 200) {
        $this->logger->error("LegalController|_resolve_termsAndConditions| Error en hubClient.getUserCountry() | (".$res['status_code']." | " . json_encode($res['data']) . ")");
        return $this->redirectToRoute('terms_conditions', array('_locale' => 'uy'));
      } else {
        $country = strtolower($res['data']['country_code']);
        $this->logger->info("LegalController|_resolve_termsAndConditions| Resolved. | (".$res['status_code']." | " . json_encode($res['data']) . ")");
        if (!array_key_exists($country, Settings::countries)) {
          $this->logger->info("LegalController|_resolve_termsAndConditions| Country not found in the list of countries. Will default to UY.");
          return $this->redirectToRoute('listLocal', array('r' => '/uy/terms-and-conditions'));
        }else{
          return $this->redirectToRoute('terms_conditions', array('_locale' => $country));
        }
      }
    }
  }

  public function _resolve_privacyPolicy(Request $request){
    $user = $this->authCtrl->getUserData($request);
    if ($user && array_key_exists('countryCode', $user)){
      $cc = strtolower($user['countryCode']);
      return $this->redirectToRoute('privacy_policy', array('_locale' => $cc));
    }else{
      // Get country using the IP
      $country = 'uy';
      $res = $this->hubClient->getUserCountry();
      if ($res['status_code'] != 200) {
        $this->logger->error("LegalController|_resolve_privacyPolicy| Error en hubClient.getUserCountry() | (".$res['status_code']." | " . json_encode($res['data']) . ")");
        return $this->redirectToRoute('privacy_policy', array('_locale' => 'uy'));
      } else {
        $country = strtolower($res['data']['country_code']);
        $this->logger->info("LegalController|_resolve_privacyPolicy| Resolved. | (".$res['status_code']." | " . json_encode($res['data']) . ")");
        if (!array_key_exists($country, Settings::countries)) {
          $this->logger->info("LegalController|_resolve_privacyPolicy| Country not found in the list of countries. Will default to UY.");
          return $this->redirectToRoute('listLocal', array('r' => '/uy/privacy-policy'));
        }else{
          return $this->redirectToRoute('privacy_policy', array('_locale' => $country));
        }
      }
    }
  }

  //
  // Contller Methods
  //

  public function acerca(){
    return $this->render('legal/acerca.html.twig');
  }

  public function privacyPolicy(){
    return $this->render('legal/privacyPolicy.html.twig');
  }

  public function termsOfUse(){
    return $this->render('legal/termsOfUse.html.twig');
  }

  public function termsAndConditions(){
    return $this->render('legal/termsAndConditions.html.twig');
  }
  
  public function docConditions(){
    return $this->render('legal/physiciansConditions.html.twig');
  }

  public function faq(){
    return $this->render('legal/faq.html.twig');
  }

  public function hwGuarantee(Request $request){
    // Check user last preference
    if ($request->cookies->has('bl_loc')){
      return $this->redirectToRoute('hw_guarantee_loc', array('_locale' => $request->cookies->get('bl_loc')));
    }
    return $this->redirectToRoute('listLocal', array('r' => $this->generateUrl('hw_guarantee_loc')));
  }

  public function hwGuaranteeLoc(){
    
    return $this->render('legal/hw-guarantee.html.twig');
  }

}