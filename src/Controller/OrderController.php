<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;
use Psr\Log\LoggerInterface;

use App\Service\HubClient;
use App\Controller\AuthController;
use App\Utils\Settings;
use Monolog\Handler\Curl\Util;

class OrderController extends AbstractController {

  private $logger;
  private $hubClient;
  private $authCtrl;

  public function __construct(AuthController $authCtrl, HubClient $hubClient, LoggerInterface $logger){
    $this->logger    = $logger;
    $this->authCtrl  = $authCtrl;
    $this->hubClient = $hubClient;
  }
  
  // Order CSActive page
  public function ordercsactive(Request $request){
    $user       = $this->authCtrl->getUserData($request);
    $country    = Settings::resolveCountry($user, $request);
    $navCountry = strtoupper($request->getLocale());
    $navCountryFull = Settings::countries[$request->getLocale()];
    // Init prices object
    $priceData = array (
      'product' => array(
        'CS-ACT-USBC' => array(
          'code'     => 'CS-ACT-USBC',
          'name'     => 'Android (con USB-C)',
          'price'    => '',
          'stock'    => -1,
          'currency' => 'USD',
          'isLocal'  => FALSE
        ),
        'CS-ACT-MUSB' => array (
          'code'     => 'CS-ACT-MUSB',
          'name'     => 'Android (con micro-USB)',
          'price'    => '',
          'stock'    => -1,
          'currency' => 'USD',
          'isLocal'  => FALSE
        ),
        'CS-ACT-LIGHT' => array (
          'code'     => 'CS-ACT-LIGHT',
          'name'     => 'iOS (lightning)',
          'price'    => '',
          'stock'    => -1,
          'currency' => 'USD',
          'isLocal'  => FALSE
        )
      ),
      'additionals' => array(
        'ELECTRODES' => array (
          'code'     => 'ELECTRODES',
          'name'     => 'Electrodos',
          'price'    => '',
          'stock'    => -1,
          'currency' => 'USD',
          'isLocal'  => FALSE
        )
      ),
      'subscription' => array(
        'price'     => '',
        'currency'  => 'USD'
      ),
      'ecgInterpretation' => array(
        'code'      => NULL,
        'price'     => NULL,
        'currency'  => 'USD',
        'isLocal'   => FALSE
      ),
      'totalStock'  => 0,
      'price'       => 0,
      'currency'    => ''
    );
    // Get prices from ERPNext
    $prodsPriceRes = $this->hubClient->getProductsPriceAndStock($country['code']);

    // echo json_encode($prodsPriceRes);
    // die();

    if (!$prodsPriceRes['resolved'] || $prodsPriceRes['status_code'] != '200'){
      // Error getting price
      $this->logger->error("OrderCtrl|ordercsative|Error on hubClient->getProductsPriceAndStock (".$prodsPriceRes['status_code']." | " . json_encode($prodsPriceRes['data']) . ")");
    }else{
      $prices = $prodsPriceRes['data']['items'];
      for($i=0; $i < count($prices); $i++ ){
        if (array_key_exists($prices[$i]['code'], $priceData['product'])){

          if ($prices[$i]['stock'] > 0){
            //
            // ATTENTION: SHOW ONLY LOCAL STOCK !!!!
            //
            if ($prices[$i]['isLocal']){ 
              // if (!$priceData['product'][$prices[$i]['code']]['isLocal']){
                $priceData['product'][$prices[$i]['code']]['code']     = $prices[$i]['code'];
                // $priceData['product'][$prices[$i]['code']]['baseCode'] = $prices[$i]['baseCode'];
                $priceData['product'][$prices[$i]['code']]['price']    = $prices[$i]['price'];
                $priceData['product'][$prices[$i]['code']]['stock']    = $prices[$i]['stock'];
                $priceData['product'][$prices[$i]['code']]['currency'] = $prices[$i]['currency'];
                $priceData['product'][$prices[$i]['code']]['isLocal']  = $prices[$i]['isLocal'];

                // Common data for all active
                $priceData['totalStock'] += $prices[$i]['stock'];
                $priceData['price']      = $prices[$i]['price'];
                $priceData['currency']   = $prices[$i]['currency'];
              // }
            }else{
              $priceData['product'][$prices[$i]['code']]['code']     = $prices[$i]['code'];
              $priceData['product'][$prices[$i]['code']]['price']    = $prices[$i]['price'];
              $priceData['product'][$prices[$i]['code']]['stock']    = 0; // SAME AS ABOVE => SHOW ONLY LOCAL STOCK !!!
              $priceData['product'][$prices[$i]['code']]['currency'] = $prices[$i]['currency'];
              $priceData['product'][$prices[$i]['code']]['isLocal']  = FALSE;

              // Common data for all active
              $priceData['totalStock'] += 0; // SAME AS ABOVE => SHOW ONLY LOCAL STOCK !!!
              $priceData['price']      = $prices[$i]['price'];
              $priceData['currency']   = $prices[$i]['currency'];
            }
          }

        }else if ($prices[$i]['code'] == 'CS-ACT-SUBS'){
          //$priceData['subscription']['baseCode'] = 'CS-ACT-SUBS';
          $priceData['subscription']['code']     = $prices[$i]['code'];
          $priceData['subscription']['price']    = $prices[$i]['price'];
          $priceData['subscription']['currency'] = $prices[$i]['currency'];
        }else if ($prices[$i]['code'] == 'CS-ECG-INTER'){
          //$priceData['ecgInterpretation']['baseCode'] = 'CS-ECG-INTER';
          $priceData['ecgInterpretation']['code']     = $prices[$i]['code'];
          $priceData['ecgInterpretation']['price']    = $prices[$i]['price'];
          $priceData['ecgInterpretation']['currency'] = $prices[$i]['currency'];
          
        }else if ($prices[$i]['code'] == 'ELECTRODES'){
          //$priceData['additionals'][$prices[$i]['baseCode']]['baseCode'] = 'CS-ECG-INTER';
          if (!$priceData['additionals'][$prices[$i]['code']]['isLocal']){
            $priceData['additionals'][$prices[$i]['code']]['code']     = $prices[$i]['code'];
            $priceData['additionals'][$prices[$i]['code']]['price']    = $prices[$i]['price'];
            $priceData['additionals'][$prices[$i]['code']]['currency'] = $prices[$i]['currency'];
            $priceData['additionals'][$prices[$i]['code']]['stock']    = $prices[$i]['stock'];
            $priceData['additionals'][$prices[$i]['code']]['isLocal']  = $prices[$i]['isLocal'];
          }
        }
      }

      //
      // TEMPORARY USBC CONTROL FOR URUGUAY
      //
      // if ($navCountry == 'UY'){
      //   $priceData['product']['CS-ACT-USBC']['stock'] = 0;
      // }

      // print_r($prodsPriceRes);
      // print_r($priceData);
      // die();
    }

    return $this->render('order/csactive.html.twig', [
      'country'           => $country,
      'navCountry'        => $navCountry,
      'navCountryFull'    => $navCountryFull,
      'priceData'         => $priceData,
      'deliveryAvailable' => $prodsPriceRes['data']['deliveryAvailable'],
      'deliveryCountries' => $prodsPriceRes['data']['deliveryCountries']
      ]);
  }

  // Order CSPro page
  public function ordercspro(Request $request){
    $user       = $this->authCtrl->getUserData($request);
    $country    = Settings::resolveCountry($user, $request);
    $navCountry = strtoupper($request->getLocale());
    $navCountryFull = Settings::countries[$request->getLocale()];

    // Init prices object
    $priceData = array (
      'product' => array(
        'CS-PRO-LIGHT' => array(
          'code'     => 'CS-PRO-LIGHT',
          'name'     => 'iOS (lightning)',
          'price'    => '',
          'stock'    => -1,
          'currency' => 'USD'
        )
      ),
      'subscription' => array(
        'CS-PRO-SUBS-12' => array(
          'code'     => 'CS-PRO-SUBS-12',
          'name'     => '12 derivaciones',
          'price'    => '',
          'currency' => 'USD'
        ),
        'CS-PRO-SUBS-22' => array(
          'code'     => 'CS-PRO-SUBS-22',
          'name'     => '22 derivaciones',
          'price' => '',
          'currency' => 'USD'
        )
      ),
      'additionals'  => array(
        'ELECTRODES' => array (
          'code'     => 'ELECTRODES',
          'name'     => 'Electrodos',
          'price'    => '',
          'stock'    => -1,
          'currency' => 'USD'
        )
      ),
      'totalStock'   => 0,
      'price'        => 0,
      'currency'     => '',
      'subsPrice'    => 0,
      'subsCurrency' => ''
    );
    // Get prices from ERPNext
    $prodsPriceRes = $this->hubClient->getProductsPriceAndStock($country['code']);
    if (!$prodsPriceRes['resolved'] || $prodsPriceRes['status_code'] != '200'){
      // Error getting price
      $this->logger->error("OrderCtrl|ordercsative|Error on hubClient->getProductsPriceAndStock (".$prodsPriceRes['status_code']." | " . json_encode($prodsPriceRes['data']) . ")");
    }else{
      // print_r($prodsPriceRes);
      // die();

      //
      // Extract CS-PRO data 
      //
      $prices = $prodsPriceRes['data']['items'];
      for($i=0; $i < count($prices); $i++ ){
        if (array_key_exists($prices[$i]['code'], $priceData['product'])){
          //
          // ATTENTION: SHOW ONLY LOCAL STOCK !!!!
          //
          $priceData['product'][$prices[$i]['code']]['price']    = $prices[$i]['price'];
          $priceData['product'][$prices[$i]['code']]['stock']    = $prices[$i]['stock'];
          $priceData['product'][$prices[$i]['code']]['currency'] = $prices[$i]['currency'];
          $priceData['product'][$prices[$i]['code']]['isLocal']  = $prices[$i]['isLocal'];
          // Common data for all active
          $priceData['price']       = $prices[$i]['price'];
          $priceData['currency']    = $prices[$i]['currency'];
          // check only local stock
          // If its not local, set it to zero
          if (!$prices[$i]['isLocal']){ 
            $prices[$i]['stock'] = 0;
          }
          $priceData['totalStock'] += $prices[$i]['stock'];
        }else if (array_key_exists($prices[$i]['code'], $priceData['subscription'])){
          $priceData['subscription'][$prices[$i]['code']]['price']    =  $prices[$i]['price'];
          $priceData['subscription'][$prices[$i]['code']]['currency'] = $prices[$i]['currency'];
        }else if ($prices[$i]['code'] == 'ELECTRODES'){
          //$priceData['additionals'][$prices[$i]['code']]['baseCode'] = 'CS-ECG-INTER';
          $priceData['additionals'][$prices[$i]['code']]['code']     = $prices[$i]['code'];
          $priceData['additionals'][$prices[$i]['code']]['price']    = $prices[$i]['price'];
          $priceData['additionals'][$prices[$i]['code']]['currency'] = $prices[$i]['currency'];
          $priceData['additionals'][$prices[$i]['code']]['stock']    = $prices[$i]['stock'];
          $priceData['additionals'][$prices[$i]['code']]['isLocal']  = $prices[$i]['isLocal'];
        }
      }
      

      //
      // New options
      //
      // COnvert this
      // "priceLocal" : 2000,
      // "priceGlobal" : 1700,
      // INTO => price
      // Convert this subs->CS-PRO-SUBS-12 | CS-PRO-SUBS-22
      // "subsPrice"    => 75,
      // 'subsCurrency' => 'USD',
      // INTO => subsPrice and subsCurrency
      $priceOptions = NULL;
      if (array_key_exists('paymentOptions', $prodsPriceRes['data'])){
        if (array_key_exists('CS-PRO-LIGHT', $prodsPriceRes['data']['paymentOptions'])){
          $priceOptions = $prodsPriceRes['data']['paymentOptions']['CS-PRO-LIGHT'];
          // print_r($priceData);
          // die();
          for($i=0; $i < count($priceOptions); $i++){
            $priceOptions[$i]['price']        = $priceData['product']['CS-PRO-LIGHT']['isLocal'] ? $priceOptions[$i]['priceLocal'] : $priceOptions[$i]['priceGlobal'];
            $priceOptions[$i]['subsPrice']    = $priceOptions[$i]['subs']['CS-PRO-SUBS-12']['price'];
            $priceOptions[$i]['subsCurrency'] = $priceOptions[$i]['subs']['CS-PRO-SUBS-12']['currency'];
          }
        }
      }


      // Set first subscription price
      $priceData['subsPrice']    = $priceData['subscription']['CS-PRO-SUBS-12']['price'];
      $priceData['subsCurrency'] = $priceData['subscription']['CS-PRO-SUBS-12']['currency'];

      // print_r($priceData);
      // print_r($priceOptions);
      // die();

      // ZERO STOCK TEST
      // $priceData['product']['CS-ACT-USBC']['stock'] = 0;
      // $priceData['product']['CS-ACT-MUSB']['stock'] = 0;
      // $priceData['product']['CS-ACT-LIGHT']['stock'] = 0;
      // $priceData['totalStock'] = 0;
    }

    return $this->render('order/cspro.html.twig', [
      'country'           => $country,
      'navCountry'        => $navCountry,
      'navCountryFull'    => $navCountryFull,
      'priceData'         => $priceData,
      'priceOptions'      => $priceOptions,
      'deliveryAvailable' => $prodsPriceRes['data']['deliveryAvailable'],
    ]);
  }

  // Order View
  public function orderView(Request $request, $orderId){

    $user = $this->authCtrl->getUserData($request);
    if ($user == NULL){
      return $this->redirectToRoute('login', array('r' => $request->getPathInfo()));
    }
    $this->hubClient->setAccessToken($user['at']);
    $orderViewRes = $this->hubClient->orderView($orderId);
    $first_confirmation = $this->get('session')->getFlashBag()->get('first_confirmation', []);

    if (!$orderViewRes['resolved'] || $orderViewRes['status_code'] != '200'){
      return $this->render('order/orderView.html.twig',[
        'error_msg'        => 'ERROR',
        'paymentData'      => NULL,
        'shippingData'     => NULL,
        'orderData'        => NULL,
        'subscriptionData' => NULL
      ]);
    }else{

      // Load payment data
      if (array_key_exists('paymentEntry', $orderViewRes['data'])){
        $paymentData = array(
          'docstatus'               => $orderViewRes['data']['paymentEntry']['docstatus'],
          'transaction_id'          => $orderViewRes['data']['paymentEntry']['hhub_payment_trxid'],
          'transaction_amount'      => $orderViewRes['data']['paymentEntry']['paid_amount'],
          'base_transaction_amount' => $orderViewRes['data']['paymentEntry']['base_paid_amount'],
          'currency'                => $orderViewRes['data']['paymentEntry']['paid_to_account_currency'],
          'statement_descriptor'    => $orderViewRes['data']['paymentEntry']['statement_descriptor']
        );
        $paymentData['issuer_name']    = 'Tarjeta';
        $paymentData['card_last_four'] = NULL;
        if ($orderViewRes['data']['paymentEntry']['hhub_card_issuername'] && 
            strlen($orderViewRes['data']['paymentEntry']['hhub_card_issuername']) > 0) {
          $paymentData['issuer_name'] = $orderViewRes['data']['paymentEntry']['hhub_card_issuername'];
        }
        if ($orderViewRes['data']['paymentEntry']['hhub_card_last4'] && 
            strlen($orderViewRes['data']['paymentEntry']['hhub_card_last4']) > 0) {
          $paymentData['card_last_four'] = $orderViewRes['data']['paymentEntry']['hhub_card_last4'];
        }
      }else{
        $paymentData = array(
          'docstatus'          => '0',
          'transaction_id'     => '(a confirmar)',
          'transaction_amount' => '(a confirmar)',
          'currency'           => '',
          'issuer_name'        => 'Tarjeta',
          'card_last_four'     => '****'
        );
      }

      // print_r($orderViewRes);
      // die();

      // Load order data
      $orderData = array(
        'order_id'              => $orderViewRes['data']['order']['name'],
        'status'                => $orderViewRes['data']['order']['status'],
        'transaction_date'      => $orderViewRes['data']['order']['transaction_date'],
        'delivery_date'         => $orderViewRes['data']['order']['delivery_date'],
        'delivery_date_to_show' => $orderViewRes['data']['order']['delivery_date_to_show'],
        'bill_id'               => '',
        'customer'              => $orderViewRes['data']['order']['customer_name'],
        'items'                 => array(),
        'currency'              => $orderViewRes['data']['order']['currency'],
        'total'                 => array(
          'price'               => $orderViewRes['data']['order']['total'],
          'priceToShow'         => $orderViewRes['data']['order']['totalToShow'],
          'base_price'          => $orderViewRes['data']['order']['base_total'],
          'base_priceToShow'    => $orderViewRes['data']['order']['base_totalToShow'],
          'currency'            => $orderViewRes['data']['order']['currency']
        ),
        'grand_total'           => array(
          'price'               => $orderViewRes['data']['order']['grand_total'],
          'base_price'          => $orderViewRes['data']['order']['base_grand_total'],
          'currency'            => $orderViewRes['data']['order']['currency'],
          'base_priceToShow'    => $orderViewRes['data']['order']['base_grand_totalToShow']
        ),
        'rounded_total'         => array(
          'price'               => $orderViewRes['data']['order']['rounded_total'], 
          'priceToShow'         => $orderViewRes['data']['order']['rounded_totalToShow'],
          'base_priceToShow'    => $orderViewRes['data']['order']['base_rounded_totalToShow'],
          'currency'            => $orderViewRes['data']['order']['currency']
        ),
        'rounding_adjustment'   => array(
          'price'               => $orderViewRes['data']['order']['rounding_adjustment'],
          'priceToShow'         => $orderViewRes['data']['order']['rounding_adjustmentToShow'],
          'base_priceToShow'    => $orderViewRes['data']['order']['base_rounding_adjustmentToShow'],
          'currency'            => $orderViewRes['data']['order']['currency']
        )
      );
      // load taxes and charges
      $shipRuleName = $orderViewRes['data']['order']['shipping_rule'];
      $orderData['taxes'] = array(
        'taxes_amount'                                => 0,
        'base_taxes_amount'                           => 0,
        'taxes_amountToShow'                          => '',
        'base_taxes_amountToShow'                     => '',
        'tax_amount_after_discount_amount'            => 0,
        'tax_amount_after_discount_amountToShow'      => '',
        'base_tax_amount_after_discount_amount'       => 0,
        'base_tax_amount_after_discount_amountToShow' => '',
        'currency'                                    => $orderViewRes['data']['order']['currency']
      );
      $orderData['shipping'] = array(
        'price'      => 0,
        'base_price' => 0,
        'currency'   => $orderViewRes['data']['order']['currency']
      );
      for ($i=0; $i < count($orderViewRes['data']['order']['taxes']); $i++){
        if ($orderViewRes['data']['order']['taxes'][$i]['description'] == $shipRuleName){
          $orderData['shipping']['price']            = $orderViewRes['data']['order']['taxes'][$i]['tax_amount'];
          $orderData['shipping']['priceToShow']      = $orderViewRes['data']['order']['taxes'][$i]['tax_amountToShow'];
          $orderData['shipping']['base_price']       = $orderViewRes['data']['order']['taxes'][$i]['base_tax_amount'];
          $orderData['shipping']['base_priceToShow'] = $orderViewRes['data']['order']['taxes'][$i]['base_tax_amountToShow'];
        }else{
          $orderData['taxes']['taxes_amount']       += $orderViewRes['data']['order']['taxes'][$i]['tax_amount'];
          $orderData['taxes']['base_taxes_amount']  += $orderViewRes['data']['order']['taxes'][$i]['base_tax_amount'];
          $orderData['taxes']['tax_amount_after_discount_amount']       += $orderViewRes['data']['order']['taxes'][$i]['tax_amount_after_discount_amount'];
          $orderData['taxes']['base_tax_amount_after_discount_amount']  += $orderViewRes['data']['order']['taxes'][$i]['base_tax_amount_after_discount_amount'];
        }
      }
      // WTF ? 
      //$orderData['taxes']['taxes_amount']       = $orderData['taxes']['taxes_amount'];
      $orderData['taxes']['taxes_amountToShow']      = number_format($orderData['taxes']['taxes_amount'], 2, ',', '.');
      $orderData['taxes']['base_taxes_amountToShow'] = number_format($orderData['taxes']['base_taxes_amount'], 2, ',', '.');
      $orderData['taxes']['tax_amount_after_discount_amountToShow']      = number_format($orderData['taxes']['tax_amount_after_discount_amount'], 2, ',', '.');
      $orderData['taxes']['base_tax_amount_after_discount_amountToShow'] = number_format($orderData['taxes']['base_tax_amount_after_discount_amount'], 2, ',', '.');
      $orderData['shipping']['price']                = $orderData['shipping']['price'];
      $orderData['shipping']['base_price']           = $orderData['shipping']['base_price'];
      
      // Check discount (override discount attribute with our format for invoiceLines)
      $orderData['discount'] = array(
        'name'                 => '',
        'apply_discount_on'    => $orderViewRes['data']['order']['apply_discount_on'],
        'discountAmount'       => $orderViewRes['data']['order']['base_discount_amount'],
        'discountAmountToShow' => number_format($orderViewRes['data']['order']['base_discount_amount'], 2, ',', '.')
      );

      // Load shipping data
      $shippingData = NULL;
      if ( array_key_exists('shippingAddress', $orderViewRes['data']) ){
        $shippingData = array(
          'address_type' => $orderViewRes['data']['shippingAddress']['address_type'],
          'address'      => $orderViewRes['data']['shippingAddress']['address_line1'],
          'countryName'  => $orderViewRes['data']['shippingAddress']['country'],
          'city'         => $orderViewRes['data']['shippingAddress']['city'],
          'method'       => $shipRuleName,
          'delivered'    => $orderViewRes['data']['delivered'],
          'AWBNumber'    => $orderViewRes['data']['awbn']
        );
        if (array_key_exists('pincode', $orderViewRes['data']['shippingAddress'])){
          $shippingData['postalcode'] = $orderViewRes['data']['shippingAddress']['pincode'];
        }else{
          $shippingData['postalcode'] = '';
        }
        if (array_key_exists('address_recipient', $orderViewRes['data']['shippingAddress'])){
          $shippingData['name'] = $orderViewRes['data']['shippingAddress']['address_recipient'];
        }else{
          $shippingData['name'] = '';
        }

        // load last shipping event
        if (array_key_exists('last_shipping_event', $orderViewRes['data']) && $orderViewRes['data']['last_shipping_event'] != NULL) {
          $shippingData['last_shipping_event'] = $orderViewRes['data']['last_shipping_event'];
        }
      }
      //load items
      for ($i=0; $i < count($orderViewRes['data']['order']['items']); $i++){
        $item = array(
          'total'             => $orderViewRes['data']['order']['items'][$i]['amount'],
          'totalToShow'       => $orderViewRes['data']['order']['items'][$i]['amountToShow'],
          'base_total'        => $orderViewRes['data']['order']['items'][$i]['base_amount'],
          'base_totalToShow'  => $orderViewRes['data']['order']['items'][$i]['base_amountToShow'],
          'price'             => '0',
          'currency'          => $orderViewRes['data']['order']['currency'],
          'qty'               => $orderViewRes['data']['order']['items'][$i]['qty'],
          'name'              => $orderViewRes['data']['order']['items'][$i]['item_name']
        );
        if (array_key_exists('subscription_code', $orderViewRes['data']['order']['items'][$i]) ){
          $item['subscription_code'] = $orderViewRes['data']['order']['items'][$i]['subscription_code'];
        }else{
          $item['subscription_code'] = NULL;
        }
        if (array_key_exists('interpretation_code', $orderViewRes['data']['order']['items'][$i]) ){
          $item['interpretation_code'] = $orderViewRes['data']['order']['items'][$i]['interpretation_code'];
        }else{
          $item['interpretation_code'] = NULL;
        }
        $orderData['items'][] = $item;
      }
      //load subscriptions
      $subscriptionData = NULL;
      if (array_key_exists('subscription',$orderViewRes['data'])){
        if (array_key_exists('items',$orderViewRes['data']['subscription'])){
          $subscriptionData = array(
            'subscriptions'            => $orderViewRes['data']['subscription']['items'],
            'totalSubscriptions'       => $orderViewRes['data']['subscription']['total'],
            'grand_totalSubscriptions' => $orderViewRes['data']['subscription']['grand_total'],
            'discount'                 => $orderViewRes['data']['order']['discount']
          );
          if (array_key_exists('taxes', $orderViewRes['data']['subscription'])){
            $subscriptionData['taxesSubscriptions'] = $orderViewRes['data']['subscription']['taxes'];
          }
        }
      }

      // Calculate current order status
      $orderStatus = 'order_received';
      if (array_key_exists('paymentEntry', $orderViewRes['data'])) {
        if ($orderViewRes['data']['paymentEntry']['docstatus'] != 0) {
          $orderStatus = 'payment_approved';
          if ( array_key_exists('shippingAddress', $orderViewRes['data'])) {
            if ($orderViewRes['data']['delivered']) {
              $orderStatus = 'delivered';
            } else if ($orderViewRes['data']['last_shipping_event'] != NULL && !$orderViewRes['data']['delivered']) {
              $orderStatus = 'shipped';
            }
          }
        }
      }

      if (array_key_exists('transaction_amount', $paymentData)){
        if (is_numeric($paymentData['transaction_amount'])){
          $paymentData['payment_to_show']      = number_format($paymentData['transaction_amount'], 2, ",", ".");
          $paymentData['base_payment_to_show'] = number_format($paymentData['base_transaction_amount'], 2, ",", ".");
        }else{
          $paymentData['base_payment_to_show'] = $paymentData['base_transaction_amount'];
        }
      }else{
        $paymentData['payment_to_show'] = '';
      }

      // print_r($subscriptionData);
      // die();

      return $this->render('order/orderView.html.twig',[
        'error_msg'          => NULL,
        'first_confirmation' => $first_confirmation,
        'paymentData'        => $paymentData,
        'shippingData'       => $shippingData,
        'orderData'          => $orderData,
        'subscriptionData'   => $subscriptionData,
        'orderStatus'        => $orderStatus
      ]);
    }
  }

  // Purchase View
  public function purchaseView(Request $request, $intlOrderId, $localOrderId){
    $first_confirmation = $this->get('session')->getFlashBag()->get('first_confirmation', []);
    $user = $this->authCtrl->getUserData($request);

    // print_r($user);
    // die();

    if ($user == NULL){
      return $this->redirectToRoute('login', array('r' => $request->getPathInfo()));
    }
    $this->hubClient->setAccessToken($user['at']);
    //
    // Read Intl Order
    // 
    $purchaseViewRes = $this->hubClient->purchaseView($intlOrderId, $localOrderId);

    // print_r($purchaseViewRes);
    // die();

    //
    // Prepare purchase data
    //
    

    if (!$purchaseViewRes['resolved'] || $purchaseViewRes['status_code'] != '200'){
      return $this->render('order/purchaseView.html.twig',[
        'error_msg'        => 'ERROR',
        'paymentData'      => NULL,
        'purchaseData'     => NULL,
        'subscriptionData' => NULL
      ]);
    }else{
      return $this->render('order/purchaseView.html.twig',[
        'error_msg'          => NULL,
        'user'               => $user,
        'first_confirmation' => $first_confirmation,
        'localOrder'         => $purchaseViewRes['data']['localOrder'],
        'intlOrder'          => $purchaseViewRes['data']['intlOrder'],
        'customer'           => $purchaseViewRes['data']['customer'],
        'shippingAddress'    => $purchaseViewRes['data']['shippingAddress'],
        'paymentEntry'       => $purchaseViewRes['data']['paymentEntry'],
      ]);
    }
  }

  // Cart Add
  public function cartAdd(Request $request){
    $user      = $this->authCtrl->getUserData($request);
    $error_msg = NULL;
    $bag       = $request->request;
    $item      = NULL;
    $session   = $this->get('session');
    $country   = Settings::resolveCountry($user, $request);
    $currency  = Settings::get_currency_by_country($country['code']);

    // print_r($bag);
    // die();

    //
    // Check main products
    //
    if ($bag->has('csactive_os')){
      // Add Active device
      $item = array(
        'code'                => $bag->get('csactive_os'),
        'subscription_code'   => 'CS-ACT-SUBS',
        'qty'                 => 1,
        'interpretation_code' => NULL,
        'payment_option'      => NULL
      );
      if ($bag->has('csactive_ecginter')){
        $item['interpretation_code'] = $bag->get('csactive_ecginter');
      }
    }else if($bag->has('cspro_os')){
      // Add Pro device
      $item = array(
        'code'                => $bag->get('cspro_os'),
        'subscription_code'   => $bag->get('cspro_subs'),
        'qty'                 => 1,
        'interpretation_code' => NULL,
        'payment_option'      => $bag->get('payment_option')
      );
    }else if($bag->has('item_code')){
      // Add generic product
      $item = array(
        'code'                => $bag->get('item_code'),
        'subscription_code'   => NULL,
        'qty'                 => 1,
        'interpretation_code' => NULL,
        'payment_option'      => NULL
      );
    }

    //
    // Add main product item to cart
    //
    if ($session->has('checkout_cart_items')){
      $cart_items = $session->get('checkout_cart_items');
      // Check if already added
      $bAdd = TRUE;
      for($i=0; $i < count($cart_items); $i++){
        if ($cart_items[$i]['code'] == $item['code']){
          if ($cart_items[$i]['payment_option'] == $item['payment_option'] 
              &&
              $cart_items[$i]['payment_option'] == $item['payment_option'] ){
            if ($cart_items[$i]['subscription_code'] == $item['subscription_code'] 
                &&
                $cart_items[$i]['interpretation_code'] == $item['interpretation_code'] ){
              $cart_items[$i]['qty'] += $item['qty'];
              $bAdd = FALSE;
              break;
            }
          }
        }
      }
      if ($bAdd){
        array_push($cart_items, $item);
      }
    }else{
      $cart_items = array($item);
    }

    //
    // Check additionals
    //
    if ($bag->has('additionals')){
      $additionalQty = $bag->get('additionals');
      for($i=0; $i < $additionalQty; $i++){
        if ($bag->has('additional'.$i)){
          // Add additional
          $item = array(
            'code'                => $bag->get('additional'.$i),
            'subscription_code'   => NULL,
            'qty'                 => 1,
            'interpretation_code' => NULL
          );
          // Check if already added
          $bAdd = TRUE;
          for($i=0; $i < count($cart_items); $i++){
            if ($cart_items[$i]['code'] == $item['code']){
                $cart_items[$i]['qty'] += $item['qty'];
                $bAdd = FALSE;
                break;
            }
          }
          if ($bAdd){
            array_push($cart_items, $item);
          }
        }
      }
    }
    
    $items_count = 0;
    for($i=0 ; $i < count($cart_items) ; $i++) {
      $items_count += $cart_items[$i]['qty'];
    }
    if($items_count > 9) {
      return $this->render('order/cartView.html.twig', array(
        'error_msg'         => "Solo se pueden agregar tres items al carrito",
        'info_msg'          => NULL,
        'details'           => NULL,
        'amountToPay'       => NULL
      ));
    } else {
      $session->set('checkout_cart_items', $cart_items);

      // Calcuate cart
      $cartDetails = array(
        'user'          => $user,
        'items'         => $cart_items,
        'shipping'      => array (
          'countryCode' => $country['code']
        ),
        'currency'      => $currency['code'],
        'countryCode'   => $country['code']
      );
      $this->logger->debug('OrderCtrl|cartAdd|Calculating items total with hubClient->calculateCart...');
      $calcRes = $this->hubClient->calculateCart($cartDetails);
      if (!$calcRes['resolved'] || $calcRes['status_code'] != 200){
        // Something wrong happened
        $this->logger->error("OrderCtrl|cartAdd|Error on hubClient->calculateCart (".$calcRes['status_code']." | " . json_encode($calcRes['data']) . ")");
        $error_msg = 'Ocurri&oacute; un problema agregando el item al carrito.';
        return $this->render('order/cartView.html.twig', array(
          'error_msg'         => $error_msg,
          'info_msg'          => NULL,
          'details'           => NULL,
          'amountToPay'       => NULL
        ));
      }else{
        // Store prices to use on payment
        $session->set('checkout_cart_prices', $calcRes['data']);

        $amountToPay = $currency['code'] == "USD" ? $calcRes['data']['grand_total']['price'] : $calcRes['data']['local_grand_total']['rounded_price'];
        $payment = array(
          'currency'      => $currency['code'],
          'amount'        => $amountToPay,
          'exchange_rate' => $currency['code'] == "USD" ? 1 : $calcRes['data']['local_grand_total']['exchange_rate']
        );
        $session->set('checkout_cart_payment', $payment);

        $info_msg = "El item fue agregado al carrito.";
  
        return $this->render('order/cartView.html.twig', array(
          'error_msg'         => $error_msg,
          'info_msg'          => $info_msg,
          'details'           => $calcRes['data'],
          'amountToPay'       => $amountToPay
        ));
      }
    }
   
    
  }

  // Cart Remove
  public function cartRemove(Request $request, $idx){
    $user      = $this->authCtrl->getUserData($request);
    $currency  = Settings::get_currency_by_country($user['countryCode']);
    $error_msg = NULL;
    $country   = Settings::resolveCountry($user, $request);

    // Check cart
    if ($this->get('session')->has('checkout_cart_items')){
      $cart_items = $this->get('session')->get('checkout_cart_items');
      // Search for item index
      array_splice($cart_items, $idx, 1);
      // Update cart 
      $this->get('session')->set('checkout_cart_items', $cart_items);
      // Re-check cart
      if (count($cart_items) == 0){
        // Empty cart
        // Empty cart (for some strange reason)
        return $this->render('order/cartView.html.twig', array(
          'error_msg'         => NULL,
          'info_msg'          => "El item fue quitado al carrito.",
          'details'           => NULL,
          'amountToPay'       => NULL
        ));
      }else{
        // Re-Calcuate cart
        $cartDetails = array(
          'user'          => $user,
          'items'         => $cart_items,
          'shipping'      => array (
            'countryCode' => $country['code']
          ),
          'currency'      => $currency['code'],
          'countryCode'   => $country['code']
        );
        $this->logger->debug('OrderCtrl|cartAdd|Calculating items total with hubClient->calculateCart...');
        $calcRes = $this->hubClient->calculateCart($cartDetails);
        if (!$calcRes['resolved'] || $calcRes['status_code'] != 200){
          // Something wrong happened
          $this->logger->error("OrderCtrl|cartAdd|Error on hubClient->calculateCart (".$calcRes['status_code']." | " . json_encode($calcRes['data']) . ")");
          $error_msg = 'Ocurri&oacute; un problema agregando el item al carrito.';
          return $this->render('order/cartView.html.twig', array(
            'error_msg'         => $error_msg,
            'info_msg'          => NULL,
            'details'           => NULL,
            'amountToPay'       => NULL
          ));
        }else{

          // Store prices to use on payment
          $this->get('session')->set('checkout_cart_prices', $calcRes['data']);

          $amountToPay = $currency['code'] == "USD" ? $calcRes['data']['grand_total']['price'] : $calcRes['data']['local_grand_total']['rounded_price'];
          $payment = array(
            'currency'      => $currency['code'],
            'amount'        => $amountToPay,
            'exchange_rate' => $currency['code'] == "USD" ? 1 : $calcRes['data']['local_grand_total']['exchange_rate']
          );
          $this->get('session')->set('checkout_cart_payment', $payment);

          return $this->render('order/cartView.html.twig', array(
            'error_msg'         => $error_msg,
            'info_msg'          => "El item fue quitado al carrito.",
            'details'           => $calcRes['data'],
            'amountToPay'       => $amountToPay
          ));
        }
      }
    }else{
      // Empty cart (for some strange reason)
      return $this->render('order/cartView.html.twig', array(
        'error_msg'         => NULL,
        'info_msg'          => NULL,
        'details'           => NULL,
        'amountToPay'       => NULL
      ));
    }

    
    
  }

  // Cart View
  public function cartView(Request $request){
    $user        = $this->authCtrl->getUserData($request);
    $error_msg   = NULL;
    $cart_items  = $this->get('session')->get('checkout_cart_items');
    $info_msg    = implode('', $this->get('session')->getFlashBag()->get('info_msg', []));
    $country     = Settings::resolveCountry($user, $request);

    //print_r($cart_items);

    if ($cart_items == NULL || count($cart_items) == 0){
      // Empty Cart
      return $this->render('order/cartView.html.twig', array(
        'error_msg'         => NULL,
        'info_msg'          => $info_msg,
        'details'           => NULL,
        'amountToPay'       => NULL
      ));
    }else{
      // Get currency
      $currency = Settings::get_currency_by_country($country['code']);
      // Get coupon
      $discount = $this->get('session')->get('checkout_cart_discount');
      // Calcuate cart
      $cartDetails = array(
        'user'          => $user,
        'items'         => $cart_items,
        'shipping'      => array (
          'countryCode' => $country['code']
        ),
        'currency'      => $currency['code'],
        'countryCode'   => $country['code'],
        'discount'      => $discount
      );


      // print_r($cartDetails);
      // die();

      $this->logger->debug('OrderCtrl|cartView|Calculating items total with hubClient->calculateCart...');
      $calcRes = $this->hubClient->calculateCart($cartDetails);

      // print_r($calcRes);
      // die();

      if (!$calcRes['resolved'] || $calcRes['status_code'] != 200){
        // Something wrong happened
        $this->logger->error("OrderCtrl|cartView|Error on hubClient->calculateCart (".$calcRes['status_code']." | " . json_encode($calcRes['data']) . ")");
        $error_msg = 'Ocurri&oacute; un problema agregando el item al carrito.';
        return $this->render('order/cartView.html.twig', array(
          'error_msg'         => $error_msg,
          'info_msg'          => $info_msg,
          'details'           => NULL,
          'amountToPay'       => NULL
        ));
      }else{
        // Store prices to use on payment
        $this->get('session')->set('checkout_cart_prices', $calcRes['data']);

        $amountToPay = $currency['code'] == "USD" ? $calcRes['data']['grand_total']['price'] : $calcRes['data']['local_grand_total']['rounded_price'];
        $payment = array(
          'currency'      => $currency['code'],
          'amount'        => $amountToPay,
          'exchange_rate' => $currency['code'] == "USD" ? 1 : $calcRes['data']['local_grand_total']['exchange_rate']
        );
        $this->get('session')->set('checkout_cart_payment', $payment);

        return $this->render('order/cartView.html.twig', array(
          'error_msg'         => NULL,
          'info_msg'          => $info_msg,
          'details'           => $calcRes['data'],
          'amountToPay'       => $amountToPay
        ));
      }
    }
  }

}