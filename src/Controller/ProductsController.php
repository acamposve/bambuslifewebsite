<?php

namespace App\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

use App\Service\NgageClient;
use App\Service\HubClient;
use App\Controller\AuthController;
use App\Utils\Settings;

class ProductsController extends AbstractController {

  private $ngageClient;
  private $logger;
  private $authCtrl;
  private $hubClient;

  public function __construct(AuthController $authCtrl, HubClient $hubClient, NgageClient $ngageClient, LoggerInterface $logger){
    $this->ngageClient = $ngageClient;
    $this->logger      = $logger;
    $this->authCtrl    = $authCtrl;
    $this->hubClient   = $hubClient;
  }


  // Products page
  public function products(){
    // TODO: SHOW TO PICKUP
    return $this->render('products/products.html.twig');
  }

  // CSActive 
  // Product Description Page
  public function csactive(){
    return $this->render('products/csactive.html.twig');
  }


  //*** Hot to Use it */

  // CSActive 
  // Product Description Page
  public function receiveADiagnosisForChestPain(){
    return $this->render('products/how-to-use-it/receive-a-diagnosis-for-chest-pain.html.twig');
  }

  // CSActive 
  // Product Description Page
  public function earlyDetectionOfSymptomsOfHeartAttack(){
    return $this->render('products/how-to-use-it/early-detection-of-symptoms-of-heart-attack.html.twig');
  }

  // CSActive 
  // Product Description Page
  public function detectionOfCardiacArrhythmia(){
    return $this->render('products/how-to-use-it/detection-of-cardiac-arrhythmia.html.twig');
  }

  // CSActive 
  // Product Description Page
  public function prevention(){
    return $this->render('products/how-to-use-it/prevention.html.twig');
  }

  // CSPro
  // Product Description Page
  public function cspro(){
    return $this->render('products/cspro.html.twig');
  }

  // CSPro Organizaciones
  // Product Description Page
  public function csproorgs(){
    return $this->render('products/csproorgs.html.twig', ['flagForm' => NULL]);
  }
  public function csproorgsPost(Request $request){
    // Build reCaptcha POST request:
    $recaptcha_url      = 'https://www.google.com/recaptcha/api/siteverify';
    $recaptcha_secret   = $_ENV["RECAPTCHA_KEY"];
    $recaptcha_response = $_POST['recaptcha_response'];

    // Make and decode POST request:
    $recaptcha = file_get_contents($recaptcha_url . '?secret=' . $recaptcha_secret . '&response=' . $recaptcha_response);
    $recaptcha = json_decode($recaptcha);

    // Take action based on the score returned:
    if ($recaptcha->score >= 0.5) {
      // Verified - send email
      $orgname      = $request->request->get('orgname');
      $country      = $request->request->get('country');
      $contactName  = $request->request->get('contactName');
      $contactEmail = $request->request->get('contactEmail');
      $message      = $request->request->get('message');
      $this->logger->debug("ContactForm | contactForm | Start...");
      $cliRes = $this->ngageClient->sendOrgContactForm($orgname, $country, $contactName, $contactEmail, $message);

      if (!$cliRes['resolved']){
        return $this->render('products/csproorgs.html.twig', ['flagForm' => 2]);
      }else{
        return $this->render('products/csproorgs.html.twig', ['flagForm' => 1]);
      }
    } else {
      // Not verified - show form error
      return $this->render('products/csproorgs.html.twig', ['flagForm' => 3]);
    }
  }

  
  // HealthHub
  // Product Description Page
  public function hhub(){
    return $this->render('products/hhub.html.twig');
  }

  // Scientific Studies
  // Product Description Page
  public function scientificStudies(){
    return $this->render('products/studies/scientific-studies.html.twig');
  }

  // Lab (TBD)
  // Product Description Page
  public function lab(){
    return $this->render('products/labservices.html.twig');
  }

  // Accesories
  // List of accessories page
  public function accessories(Request $request){
    $user       = $this->authCtrl->getUserData($request);
    $country    = Settings::resolveCountry($user, $request);
    $navCountry = strtoupper($request->getLocale());
    $navCountryFull = Settings::countries[$request->getLocale()];

    // Get prices from ERPNext
    $prodsPriceRes = $this->hubClient->getProductsByCategory('Accessories', $country['code']);
    if (!$prodsPriceRes['resolved'] || $prodsPriceRes['status_code'] != '200'){
      // Error getting price
      $this->logger->error("ProductsCtrl|accessories|Error on hubClient->getProductsPriceAndStock (".$prodsPriceRes['status_code']." | " . json_encode($prodsPriceRes['data']) . ")");
    }
    // print_r($prodsPriceRes);
    // die();
    return $this->render('products/accessories.html.twig', [
      'items' => $prodsPriceRes['data']['items']
    ]);
  }

  public function product(Request $request, $prdName, $prdCode){
    $user       = $this->authCtrl->getUserData($request);
    $country    = Settings::resolveCountry($user, $request);
    $navCountry = strtoupper($request->getLocale());
    $navCountryFull = Settings::countries[$request->getLocale()];

    // Get prices from ERPNext
    $prodDataRes = $this->hubClient->getProductData($prdCode, $country['code']);
    if (!$prodDataRes['resolved'] || $prodDataRes['status_code'] != '200'){
      // Error getting price
      $this->logger->error("ProductsCtrl|product|Error on hubClient->getProductData (".$prodDataRes['status_code']." | " . json_encode($prodDataRes['data']) . ")");
    }
    // print_r($prodDataRes);
    // die();
    if (array_key_exists('data', $prodDataRes)){
      if (array_key_exists('items', $prodDataRes['data'])){
        if (count($prodDataRes['data']['items']) > 0){
          $product = $prodDataRes['data']['items'][0];
          // print_r($product);
          // die();
          //
          // Check local stock only
          //
          if (!$product['isLocal']){
            $product['stock'] = -1;
          }
          return $this->render('products/product.html.twig', [
            'error_msg'         => NULL,
            'navCountry'        => $navCountry,
            'product'           => $product,
            'deliveryAvailable' => $prodDataRes['data']['deliveryAvailable']
          ]);
        }
      }
    }
    return $this->render('products/product.html.twig', [
      'error_msg'  => 'Error',
      'navCountry' => $navCountry,
      'product'    => NULL
    ]);
  }
}