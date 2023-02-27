<?php

namespace App\Controller\Components;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Psr\Log\LoggerInterface;
use App\Controller\AuthController;
use App\Utils\Settings;
use MercadoPago;

class MercadoPagoController extends AbstractController {

  private $logger;
  private $authCtrl;

  public function __construct(AuthController $authCtrl, LoggerInterface $logger) {
    $this->authCtrl = $authCtrl;
    $this->logger = $logger;

    // Tingelmar MP account
    // MercadoPago\SDK::setClientId("3945512895144203");
    // MercadoPago\SDK::setClientSecret("opvClTaQi1t8lqciPfZhYSmgIllXJOhu");
    // Bambus Life MP Account
    // MercadoPago\SDK::setClientId("2920429832101828");
    // MercadoPago\SDK::setClientSecret("xoJaXvBQDRf39GGPZ9j72vUU1OhefRUQ");
    
  }


  public function index($amountToPay, $callbackUrl, $currencyName, $currency, Request $request) {
    $user = $this->authCtrl->getUserData($request);
    // Check if MP customer exists
    $this->logger->debug(sprintf('MercadoPagoController | Searching customer with email %s...', $user['email']));

    $apiKey = Settings::mp_keys[$user['countryCode']];
    MercadoPago\SDK::setAccessToken($apiKey['private']);

    $mp_existing_customer = MercadoPago\Customer::search(array(
      "email" => $user['email']
    ));
    $mp_customer_id = null;
    $mp_customer_cards = array();

    if ($mp_existing_customer && count($mp_existing_customer)) {
      $mp_existing_customer = end($mp_existing_customer);
      foreach ($mp_existing_customer->cards as $key => $card) {
        array_push($mp_customer_cards, $card->id);
      }

      $mp_customer_id = $mp_existing_customer->id;
    }
    $mp_customer_cards = implode(',', $mp_customer_cards);

    $this->get('session')->getFlashBag()->set('paid_out', TRUE);
    return $this->render('components/mercadopago.html.twig', [
      'mp_customer_id'    => $mp_customer_id,
      'mp_customer_cards' => $mp_customer_cards,
      'amountToPayToShow' => number_format($amountToPay, 2, ",", "."),
      'amountToPay'       => $amountToPay,
      'callbackUrl'       => $callbackUrl,
      'currencyName'      => $currencyName,
      'currency'          => $currency,
      'country'           => strtolower($user['countryCode']),
      'apiKey'            => $apiKey['public'],
      'paymentMethods'    => Settings::mp_payment_methods[$user['countryCode']],
      'scriptSrc'         => Settings::mp_script_src[$user['countryCode']]
    ]);
  }


  public function indexTry2($sesId, $items, $shipping, $customer){
    $this->logger->debug('MercadoPagoController | indexTry2 | Initializing with session ' . $sesId);
    // Set preferences
    $preference = new MercadoPago\Preference();
    $preference->back_urls = array(
      "success" => "http://localhost:8000/checkout/payment/".$sesId,
      "failure" => "http://localhost:8000/checkout/payment/".$sesId,
      "pending" => "http://localhost:8000/checkout/payment/".$sesId,
    );
    //$preference->external_reference = $orderName;

    // Load items
    $mpItems = array();
    for($i=0; $i < count($items); $i++){
      $this->logger->debug('MercadoPagoController | indexTry2 | Adding item ' . $items[$i]['name'] . '...');
      $item              = new MercadoPago\Item();
      $item->id          = $items[$i]['code'];
      $item->title       = $items[$i]['name'];
      $item->quantity    = $items[$i]['qty'];
      $item->currency_id = "USD";
      $item->unit_price  = $items[$i]['price'];
      $mpItems[] = $item;
    }
    // if ($shipping){
    //   $this->logger->debug('MercadoPagoController | indexTry2 | Adding shipping ' . $shipping['name'] . '...');
    //   $item              = new MercadoPago\Item();
    //   $item->id          = 'SHPMNT';
    //   $item->title       = $shipping['name'];
    //   $item->quantity    = 1;
    //   $item->currency_id = "USD";
    //   $item->unit_price  = $shipping['price'];
    //   $mpItems[] = $item;
    // }


    // Create payer
    $payer          = new MercadoPago\Payer();
    // $payer->name    = $customer['firstname'];
    // $payer->surname = $customer['lastname'];
    // $payer->email   = $customer['email'];
    $payer->name    ='TESTNF2HVREQ';
    $payer->email   = 'test_user_12831096@testuser.com';

    // Setting preference properties
    $preference->items = $mpItems;
    $preference->payer = $payer;

    // Save and posting preference
    $preference->save();

    $this->get('session')->getFlashBag()->set('paid_out', TRUE);
    return $this->render('components/mercadopagotry2.html.twig', [
      'init_point'    => $preference->init_point,
    ]);
    
  }

  /* 
    curl -X POST \
    -H "Content-Type: application/json" \
    "https://api.mercadopago.com/users/test_user?access_token=TEST-3945512895144203-022509-d3458a75fa3768bbe00cbda3b9790ffb-407141024" \
    -d '{"site_id":"MLA"}'
      */
      // {
      //   "id"         : 436512460,
      //   "nickname"   : "TESTNF2HVREQ",
      //   "password"   : "qatest3912",
      //   "site_status": "active",
      //   "email"      : "test_user_12831096@testuser.com"
      // }


  public function indexCustom(Request $request, $callbackUrl, $userEmail, $amountToPay, $currency, $countryCode, $btnText=NULL, $btnSize=NULL){
    $user = $this->authCtrl->getUserData($request);
    // Check if MP customer exists
    $this->logger->debug(sprintf('MercadoPagoController | Searching customer with email %s...', $user['email']));

    $mpKeys = Settings::getMPKeys($countryCode);
    MercadoPago\SDK::setAccessToken($mpKeys['PRIVATE_KEY']);

    if ($btnText == NULL){
      $btnText = "Pagar";
    }
    if ($btnSize == NULL){
      $btnSize = "";
    }

    $this->logger->debug('MercadoPagoController | indexTry3 | Initializing with callback ' . $callbackUrl);

    $this->get('session')->getFlashBag()->set('paid_out', TRUE);
    return $this->render('components/mercadopago-custom.html.twig', [
      //'mp_customer_cards' => $cards,
      'mpPublicKey'       => $mpKeys['PUBLIC_KEY'],
      'callbackUrl'       => $callbackUrl,
      'userEmail'         => $userEmail,
      'country'           => strtolower($countryCode),
      'paymentMethods'    => Settings::getMPPaymentMethods($countryCode),
      'amountToPay'       => $amountToPay,
      'currency'          => $currency,
      'btnText'           => $btnText,
      'btnSize'           => $btnSize
    ]);
  }




  public function renderFastPay(Request $request, $callbackUrl, $userEmail, $amountToPay, $currency, $countryCode, $masterFormName, $btnText=NULL, $btnSize=NULL){
    $user = $this->authCtrl->getUserData($request);
    // Search MP customer
    $this->logger->debug(sprintf('MercadoPagoController|fastPay| Searching customer with email %s...', $user['email']));
    // Initialize MP
    $mpKeys = Settings::getMPKeys($countryCode);
    MercadoPago\SDK::setAccessToken($mpKeys['PRIVATE_KEY']);
    $mp_existing_customers = MercadoPago\Customer::search(array("email" => $user['email']));
    // print_r($mp_existing_customers[0]);
    // die();

    if ($btnText == NULL){
      $btnText = "Pagar";
    }
    if ($btnSize == NULL){
      $btnSize = "";
    }

    // $cards = [
    //   'id'                 => $mp_existing_customers[0]->cards[0]->id,
    //   'issuer'             => $mp_existing_customers[0]->cards[0]->issuer->id,
    //   'last_four_digits'   => $mp_existing_customers[0]->cards[0]->last_four_digits,
    //   'payment_method'     => array(
    //     'name'             => $mp_existing_customers[0]->cards[0]->payment_method->name,
    //     'secure_thumbnail' => $mp_existing_customers[0]->cards[0]->payment_method->secure_thumbnail,
    //   ),
    // ];
    // print_r($cards);
    // die();
    $this->logger->debug('MercadoPagoController|fastPay| Initializing with callback ' . $callbackUrl);

    $this->get('session')->getFlashBag()->set('paid_out', TRUE);
    return $this->render('components/mercadopago-fast.html.twig', [
      'mp_customer_cards' => $mp_existing_customers[0]->cards,
      'mpPublicKey'       => $mpKeys['PUBLIC_KEY'],
      'callbackUrl'       => $callbackUrl,
      'userEmail'         => $userEmail,
      'country'           => strtolower($countryCode),
      'paymentMethods'    => Settings::getMPPaymentMethods($countryCode),
      'amountToPay'       => $amountToPay,
      'currency'          => $currency,
      'masterFormName'    => $masterFormName,
      'btnText'           => $btnText,
      'btnSize'           => $btnSize
    ]);
  }


}
