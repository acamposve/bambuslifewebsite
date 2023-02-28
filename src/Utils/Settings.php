<?php
namespace App\Utils;

use Symfony\Component\HttpFoundation\Request;

// Third Party Payment
use MercadoPago;

class Settings {

  const countries = array (
    'ar' => array('code' => 'AR', 'name' => 'Argentina'),
    'bo' => array('code' => 'BO', 'name' => 'Bolivia'),
    'br' => array('code' => 'BR', 'name' => 'Brasil'),
    'cl' => array('code' => 'CL', 'name' => 'Chile'),
    'co' => array('code' => 'CO', 'name' => 'Colombia'),
    'cr' => array('code' => 'CR', 'name' => 'Costa Rica'),
    'cu' => array('code' => 'CU', 'name' => 'Cuba'),
    'ec' => array('code' => 'EC', 'name' => 'Ecuador'),
    'gt' => array('code' => 'GT', 'name' => 'Guatemala'),
    'sv' => array('code' => 'SV', 'name' => 'El Salvador'),
    'hn' => array('code' => 'HN', 'name' => 'Honduras'),
    'mx' => array('code' => 'MX', 'name' => 'Mexico'),
    'ni' => array('code' => 'NI', 'name' => 'Nicaragua'),
    'py' => array('code' => 'PY', 'name' => 'Paraguay'),
    'pe' => array('code' => 'PE', 'name' => 'Perú'),
    'pa' => array('code' => 'PA', 'name' => 'Panamá'),
    'pr' => array('code' => 'PR', 'name' => 'Puerto Rico'),
    'do' => array('code' => 'DO', 'name' => 'República Dominicana'),
    'uy' => array('code' => 'UY', 'name' => 'Uruguay'),
    've' => array('code' => 'VE', 'name' => 'Venezuela'),
  );

  const currency_country = array(
    'AR' => 'UYU',
    //'CL' => 'UYU', // 'CLP',
    'CL' => 'CLP',
    'PY' => 'UYU',
    'BO' => 'UYU',
    'PE' => 'UYU',
    'EC' => 'UYU',
    'CO' => 'UYU',
    'VE' => 'UYU',
    'PA' => 'UYU',
    'CR' => 'UYU',
    'NI' => 'UYU',
    'SV' => 'UYU',
    'HN' => 'UYU',
    'GT' => 'UYU',
    'MX' => 'UYU',
    'PR' => 'UYU',
    'DO' => 'UYU',
    'CU' => 'UYU',
    'BR' => 'UYU',
    'UY' => 'UYU'
  );

  const currency_name = array(
    'CLP' => 'pesos chilenos',
    'UYU' => 'pesos uruguayos',
    'USD' => 'dolares americanos'
  );

  // const mp_keys = array(
  //   'UY' => array(
  //     'private' => 'TEST-3945512895144203-022509-d3458a75fa3768bbe00cbda3b9790ffb-407141024',
  //     'public'  => 'TEST-ca3cbeec-5d8c-4d29-8df1-59b116b16afc'
  //   ),
  //   'CL' => array(
  //     // 'private' => 'TEST-4450788232191490-042612-40ccc8aacefa128d70ac5e97783b8b78-429640315',
  //     // 'public'  => 'TEST-100845d7-0ced-4802-afef-43ea4a5b545f'
  //     'private' => 'TEST-3945512895144203-022509-d3458a75fa3768bbe00cbda3b9790ffb-407141024',
  //     'public'  => 'TEST-ca3cbeec-5d8c-4d29-8df1-59b116b16afc'
  //   )
  // );

  const mp_payment_methods = array(
    'DEFAULT' => array(
      array(
        "css_class" => "visa",
        "name" => "Visa"
      ),
      array(
        "css_class" => "amex",
        "name" => "American Express"
      ),
      array(
        "css_class" => "master",
        "name" => "Mastercard"
      )
    ),
    'UY' => array(
      // array(
      //   'css_class' => 'account_money',
      //   'name' => 'Dinero en cuenta Mercado Pago',
      // ),
      array(
        'css_class' => 'visa',
        'name' => 'Visa'
      ),
      array(
        'css_class' => 'master',
        'name' => 'Mastercard'
      ),
      array(
        'css_class' => 'diners',
        'name' => 'Diners'
      ),
      // array(
      //   'css_class' => 'abitab',
      //   'name' => 'Abitab'
      // ),
      array(
        'css_class' => 'oca',
        'name' => 'Oca'
      ),
      // array(
      //   'css_class' => 'redpagos',
      //   'name' => 'Red Pagos'
      // ),
      array(
        'css_class' => 'lider',
        'name' => 'Lider'
      ),
      array(
        'css_class' => 'debvisa',
        'name' => 'Visa Débito'
      )
    ),
    'CL' => array (
      array(
        "css_class" => "visa",
        "name" => "Visa"
      ),
      array(
        "css_class" => "amex",
        "name" => "American Express"
      ),
      array(
        "css_class" => "master",
        "name" => "Mastercard"
      ),
      array(
        'css_class' => 'diners',
        'name' => 'Diners'
      )
    )
  );

  const mp_script_src = array(
    'UY' => 'https://www.mercadopago.com.uy/integrations/v1/web-tokenize-checkout.js',
    'CL' => 'https://www.mercadopago.cl/integrations/v1/web-tokenize-checkout.js'
    //'CL' => 'https://www.mercadopago.com.uy/integrations/v1/web-tokenize-checkout.js'
  );

  const mp_status_errors = array (
    // Rejected
    "cc_rejected_bad_filled_card_number"   => "Revisa el número de tarjeta.",
    "cc_rejected_bad_filled_date"          => "Revisa la fecha de vencimiento.",
    "cc_rejected_bad_filled_other"         => "Revisa los datos.",
    "cc_rejected_bad_filled_security_code" => "Revisa el código de seguridad.",
    "cc_rejected_blacklist"                => "No pudimos procesar tu pago.",
    "cc_rejected_call_for_authorize"       => "Debes autorizar ante el pago a Mercado Pago",
    "cc_rejected_card_disabled"            => "Llama al emisor/banco para que active tu tarjeta. El teléfono está al dorso de tu tarjeta.",
    "cc_rejected_card_error"               => "No pudimos procesar tu pago.",
    "cc_rejected_duplicated_payment"       => "Ya hiciste un pago por ese valor.",
    "cc_rejected_high_risk"                => "Tu pago fue rechazado. Elige otro de los medios de pago.",
    "cc_rejected_insufficient_amount"      => "Tu tarjeta no tiene fondos suficientes.",
    "cc_rejected_invalid_installments"     => "Tu tarjeta no procesa pagos en las cuotas especificadas.",
    "cc_rejected_max_attempts"             => "Llegaste al límite de intentos permitidos. Elige otra tarjeta.",
    "cc_rejected_other_reason"             => "El emisor/banco no procesó el pago.",
    // in_process
    "pending_contingency"                  => "Estamos procesando el pago. En menos de 2 días hábiles te enviaremos por e-mail el resultado.",
    "pending_review_manual"                => "Estamos procesando el pago. En menos de 2 días hábiles te diremos por e-mail si se acreditó o si necesitamos más información.",
    // aprroved
    "accredited"                           => "Listo, se acreditó tu pago!"
  );

  const support_parties = array (
    'uy'              => 'sacuy',
    'cl'              => 'saccl',
    'default'         => 'sac',
    'uy_logged'       => 'gsouy',
    'cl_logged'       => 'gsocl',
    'default_logged'  => 'gso'
  );

  const SERVICE_PRODUCT_DIAG_REP_ECG = 'DIAG_REP_ECG';
  const SERVICE_PRODUCT_ENCOUNTER    = 'ENCOUNTER';

  public static function getMPPaymentMethods($countryCode){
    if (array_key_exists($countryCode, self::mp_payment_methods)){
      return self::mp_payment_methods[$countryCode];
    }else{
      return self::mp_payment_methods['DEFAULT'];
    }
  }

  public static function getMPKeys($countryCode){
    // Build Country dedicated MP Keys
    $ENV_PRIVATE_KEY   = "BL_MP".$countryCode."_PRIVATE_KEY";
    $ENV_PUBLIC_KEY    = "BL_MP".$countryCode."_PUBLIC_KEY";
    $ENV_CLIENT_ID     = "BL_MP".$countryCode."_CLIENT_ID";
    $ENV_CLIENT_SECRET = "BL_MP".$countryCode."_CLIENT_SECRET";
    // Build default keys (UY)
    $MPKEYS = array(
      'PRIVATE_KEY'   => $_SERVER['BL_MPUY_PRIVATE_KEY'],
      'PUBLIC_KEY'    => $_SERVER['BL_MPUY_PUBLIC_KEY'],
      'CLIENT_ID'     => $_SERVER['BL_MPUY_CLIENT_ID'],
      'CLIENT_SECRET' => $_SERVER['BL_MPUY_CLIENT_SECRET']
    );
    // Override if exists
    if (array_key_exists($ENV_PRIVATE_KEY, $_SERVER)){
      $MPKEYS['PRIVATE_KEY'] = $_SERVER[$ENV_PRIVATE_KEY];
    }
    if (array_key_exists($ENV_PUBLIC_KEY, $_SERVER)){
      $MPKEYS['PUBLIC_KEY'] = $_SERVER[$ENV_PUBLIC_KEY];
    }
    if (array_key_exists($ENV_CLIENT_ID, $_SERVER)){
      $MPKEYS['CLIENT_ID'] = $_SERVER[$ENV_CLIENT_ID];
    }
    if (array_key_exists($ENV_CLIENT_SECRET, $_SERVER)){
      $MPKEYS['CLIENT_SECRET'] = $_SERVER[$ENV_CLIENT_SECRET];
    }
    return $MPKEYS;
  }

  public static function get_currency_by_country($country) {
    if ($country == NULL || !strlen($country)) {
      $currency_code = 'USD';
    } else {
      $currency_code = self::currency_country[strtoupper($country)];
    }

    return Array(
      'code' => $currency_code,
      'name' => self::currency_name[$currency_code]
    );
  }

  public static function get_country_from_locale(Request $request){
    $locale = strtolower($request->getLocale());
    return Self::countries[$locale];
  }

  public static function resolveCountry($user, Request $request){
    // $countryCode = NULL;
    // if (isset($user)){
    //   if (array_key_exists('countryCode', $user)){
    //     if (strlen($user['countryCode']) > 0 ){
    //       $countryCode = strtolower($user['countryCode']);
    //     }
    //   }
    // }
    // if ($countryCode == NULL){
      $countryCode = strtolower($request->getLocale());
    // }
    return Self::countries[$countryCode];
  }

  public static function convertMilitaryTime($mTime, $ampm=FALSE){
    $hours = intval($mTime / 100);
    $mins  = intval($mTime % 100);
    $strTime = '';
    if ($ampm){
      $strAMPM = 'AM';
      if ($hours < 10){
       $strTime .= "0" . $hours;
      }else{
        if ($hours > 12){
          $strTime .= ($hours-12);
          $strAMPM = 'PM';
        }else{
          $strTime .= $hours;
        }
      }
      if ($mins < 10){
        $strTime .= ":0" . $mins;
      }else{
        $strTime .= ":" . $mins;
      }
      $strTime .= " " . $strAMPM;
    }else{
      if ($hours < 10){
        $strTime .= "0" . $hours;
      }else{
        $strTime .= $hours;
      }
      if ($mins < 10){
        $strTime .= ":0" . $mins;
      }else{
        $strTime .= ":" . $mins;
      }
    }
    return $strTime;
  }


  public static function convertStatus($status){
    if ($status == 'BOOKED'){
      return 'Agendada';
    }else if ($status == 'CANCELLED'){
      return 'Cancelada';
    }else{
      return $status;
    }
  }

  //
  // Fast Payment processor (TEST)
  //
  public static function doFastPay(Request $request, $token, $logger, $authCtrl, $payInfo){
    //$this->logger->info(sprintf("Default|doFastPay| Recovering session data with id %s...", $sid));
    // Ensure sessionId from previous checkout step
    // $session = $this->get('session');
    // $session->setId($sid);
    // $session->start();

    // Get Payment details
    $user        = $authCtrl->getUserData($request);
    $country     = Settings::resolveCountry($user, $request);
    $navCountry  = strtoupper($request->getLocale());

    $logger->info('Default|doFastPay|1| Processing payment ***'.json_encode($payInfo).'***...');

    //
    // Setup MP
    //
    $mpKeys = Settings::getMPKeys($country['code']);
    MercadoPago\SDK::setClientId($mpKeys['CLIENT_ID']);
    MercadoPago\SDK::setClientSecret($mpKeys['CLIENT_SECRET']);
    MercadoPago\SDK::setAccessToken($mpKeys["PRIVATE_KEY"]);

    $logger->info(sprintf("Default|doFastPay|1| Start processing fast payment for %s...", $user["email"]));
    //
    // Prepare Invoice
    //
    // $logger->info("Default|doFastPay|1| Preparing invoice...");
    // $pareparedInvoice = NULL;
    // TODO
    // FOR NOW, INVOICE IS CREATED OUTSIDE THIS FUNCTION, PREVIOUS TO THIS CALL

    //
    // Get MP data
    //
    // $token             = $_REQUEST["mptoken"];
    //$payment_method_id = $_REQUEST["mppayment_method_id"];
    if (array_key_exists('installments', $_REQUEST)){
      $installments    = $_REQUEST["mpinstallments"];
    }else{
      $installments    = 1;
    }
    //
    // Resolve MP customer
    //
    $logger->info(sprintf("Default|doFastPay|2| Searching customer %s", $user["email"]));
    $existing_customer = MercadoPago\Customer::search(array("email" => $user['email']));
    if (!count($existing_customer)) {
      $customer = new MercadoPago\Customer();
      $customer->email = $user["email"];
      $this->logger->info(sprintf("Default|doFastPay|2| Saving MP customer with email: %s", $user["email"]));
      $resClient = $customer->save();
      $this->logger->debug(sprintf("Default|doFastPay|2| Customer save returned: [%s]", json_encode($resClient)));
      if ($customer->id == NULL || strlen($customer->id) == 0){
        $errMsg = "<strong>El pago no pudo ser completado</strong><br>Error en el registro de cliente";
        return Settings::cancelFP($logger, NULL, $errMsg);
      }
    } else {
      $logger->info(sprintf("Default|doFastPay|2| Using existing MP customer with email: %s", $user["email"]));
      $customer = $existing_customer[0];
    }
    // ---------------------------------
    // DO PAYMENT IN MP
    // ---------------------------------
    $payment = new MercadoPago\Payment();
    $payment->transaction_amount = $payInfo['amountToPay'];
    $payment->token              = $token;
    if ($installments){
      $payment->installments       = $installments;
    }
    //$payment->payment_method_id  = $payment_method_id;
    //$payment->issuer_id          = $issuer_id;
    $payment->binary_mode        = TRUE;
    
    $payment->payer = array(
      "id" => $customer->id
    );
    $logger->info(sprintf("Default|doFastPay|2| Requesting payment at MP with token: %s, amount: %s, customerId: %s", $token, $payInfo['amountToPay'], $customer->id));
    $logger->debug('----------------------------------------------------');
    $logger->debug(print_r($payment, TRUE));
    $logger->debug('----------------------------------------------------');
    $payment->save();
    if (!isset($payment->card) || (isset($payment->card) && $payment->card->id == NULL)) {
      $logger->info("Default|doFastPay|2| Saving card at MP...");
      $card = new MercadoPago\Card();
      $card->token = $token;
      $card->customer_id = $customer->id;
      $savedCard = $card->save();
      $logger->debug(sprintf("Default|doFastPay|2| Card save returned: [%s]", print_r($savedCard)));
      if ($card->id == NULL || strlen($card->id) == 0){
        $errMsg = "<strong>El pago no pudo ser completado</strong><br>Error en el registro de tarjeta";
        $logger->warning(sprintf("Default|doFastPay|2| Error saving card (card id empty)!!"));
        return Settings::cancelFP($logger, NULL, $errMsg);
      }
    } else {
      $logger->info(sprintf("Default|doFastPay|2| Searching card id %s...", $payment->card->id));
      $card = MercadoPago\Card::find_by_id($payment->card->id);
      if (!isset($card->issuer)) {
        $logger->info(sprintf("Default|doFastPay|2| Card id %s loaded.", $payment->card->id));
        $card = $payment->card;
      }else{
        $logger->warning(sprintf("Default|doFastPay|2| Card id %s is missing issuer!", $payment->card->id));
      }
    }
    //
    // CHECK PAYMENT RESULT
    //

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

    if ($payment->status == 'approved' || $payment->status == 'in_process'){
      //
      // PAYMENT APPROVED
      //
      $logger->info(sprintf("Default|doFastPay|2|MP payment was approved with id: %s.", $payment->id));
      
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
      // STEP 3 | Return payment info in order to confirm docs
      //
      return $paymentGW;
    }else{
      //
      // PAYMENT FAILED
      //
      $this->logger->error("Default|doFastPay|2| Error on PASARELLA, payment was not completed! --> ". json_encode($paymentDetails));
      $this->logger->error(sprintf("Default|doFastPay|2| Payment was ***%s***", json_encode($payment)));
      // 
      // New handler
      //
      $errMsg = "<strong>El pago no pudo ser completado</strong><br>";
      if (array_key_exists($payment->status_detail, Settings::mp_status_errors)){
        $errMsg .= Settings::mp_status_errors[$payment->status_detail];
      }
      return Settings::cancelFP($logger, NULL, $errMsg);
    }
  }
  private static function cancelFP($logger, $preparedInvoice, $errMsg) {
    $logger->info(sprintf("Default|doFastPay|2|Payment could NOT BE COMPLETED! All ERP documents will be deleted."));

    // Cancel Order
    // $this->logger->info(sprintf("CheckoutCtrl|doFastPay|2|Canceling order Intl %s - Local %s...", $pareparedPurchase['orderIntl']['name'], $pareparedPurchase['orderLocal']['name']));
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
    
    return array(
      'status' => 'error',
      'errMsg' => $errMsg
    );
  }

};

?>