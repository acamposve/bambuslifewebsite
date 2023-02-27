<?php
namespace App\Service;

use Psr\Log\LoggerInterface;
use Symfony\Component\Config\Definition\Exception\Exception;

class NgageClient  {

  private $TIMEOUT  = 120;
  private $RETRIES  = 3;

  private $logger;
  private $base_url;
  private $access_token = NULL;
  private $server_api_key = NULL;


  public function __construct(LoggerInterface $logger, $hostUrl, $timeout, $apiKey){
    $this->logger         = $logger;
    $this->base_url       = $hostUrl;
    $this->TIMEOUT        = $timeout;
    $this->server_api_key = $apiKey;
  }

  private function _post($url, $data, $headers=NULL){
    try {
      // Check headers (for device and access/user token)
      if ($headers){
        //curl_setopt($fc, CURLOPT_HTTPHEADER, $headers);
        $httpheaders = array_merge($headers, array( 'Content-Type: application/json'));
      }else{
        $httpheaders = array( 'Content-Type: application/json');
      }

      $start = microtime(true);
      $fc = curl_init();
      curl_setopt($fc, CURLOPT_URL, $url);
      curl_setopt($fc, CURLOPT_RETURNTRANSFER, 1);
      curl_setopt($fc, CURLOPT_HEADER, 1);
      curl_setopt($fc, CURLOPT_VERBOSE, 0);
      curl_setopt($fc, CURLOPT_POST, 1);
      curl_setopt($fc, CURLOPT_POSTFIELDS, $data);
      curl_setopt($fc, CURLOPT_HTTPHEADER, $httpheaders);
      curl_setopt($fc, CURLOPT_SSL_VERIFYPEER, false);
      curl_setopt($fc, CURLOPT_TIMEOUT, $this->TIMEOUT);

      // Execute CURL and get output
      $this->logger->info('NgageClient | _post |Sending to ['.$url.'] with data ***'.json_encode($data).'*** with headers '.json_encode($httpheaders));
      $retry = 0;
      $res = curl_exec($fc);
      $curlErr = curl_errno($fc);
      while($curlErr == 28 && $retry < $this->RETRIES){
        $this->logger->warning('NgageClient | _post |CURL timedout! retry ' . ($retry+1) . ' of ' . $this->RETRIES . '...');
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
        $this->logger->warning('NgageClient | _post |With ' . $retry . ' retries|'.round(($finish - $start), 4) . ' secs for '.$url.' ['. $httpcode .']');
      }else{
        $this->logger->info('NgageClient | _post |'.round(($finish - $start), 4) . ' secs for '.$url.' ['. $httpcode .']: ' . $url);
      }
      $this->logger->info('NgageClient | _post |______Response body: ----|'.$res_body.'|----');

      // Check CURL result
      if ($curlErr != 0){
        throw new Exception('NgageClient | _post |CURL failed with code ['.$curlErr.'] for ('.$url.')');
      }

      return array(
        'resolved'    => TRUE,
        'status_code' => $httpcode,
        'data'        => json_decode($res_body, TRUE)
      );

    } catch (Exception $ex) {
      $this->logger->error('NgageClient | _post |Request raised exception:');
      $this->logger->error($ex->getMessage());
      throw $ex;
    }
  }

  public function sendContactForm($name, $email, $phone, $message){
    $apiUrl = $_ENV['NGAGECLIENT_URL'].'/sender/sendprivate';
    $params = new \stdClass();
    $params->campaignId = 1;
    $params->subject    = "Contacto Web: " . $name;
    $params->source     = "info@bambus.life";
    $params->to         = $_ENV["CONTACT_FORM"];
    $params->htmlBody   = "Nombre: " . $name . "<br> Mail: " . $email . "<br> Telefono: " . $phone . "<br>Mensaje: " . $message;
    
    $res = $this->_post($apiUrl, json_encode($params));
    return $res;
  }

  public function sendOrgContactForm($orgname, $country, $contactName, $contactEmail, $message){
    $apiUrl = $_ENV['NGAGECLIENT_URL'].'/sender/sendprivate';
    $params = new \stdClass();
    $params->campaignId = 1;
    $params->subject    = "Contacto Web de Organizacion: " . $orgname;
    $params->source     = "info@bambus.life";
    $params->to         = $_ENV["CONTACT_FORM"];
    $params->htmlBody   = "Nombre organizacion: " . $orgname . "<br> Pais: " . $country . "<br> Nombre de contacto: " . $contactName . "<br>Email de contacto: " . $contactEmail . "<br>Mensaje: " . $message;
    
    $res = $this->_post($apiUrl, json_encode($params));
    return $res;
  }

  public function sendCasmuForm($name, $email, $phone, $socio){
    $apiUrl = $_ENV['NGAGECLIENT_URL'].'/sender/sendprivate';
    $params = new \stdClass();
    $params->campaignId = 1;
    $params->subject    = "Sorteo Casmu: " . $name;
    $params->source     = "info@bambus.life";
    $params->to         = 'rondy@tingelmar.com'; //$_ENV["CONTACT_FORM"];
    $params->htmlBody   = "Nombre: " . $name . "<br> Mail: " . $email . "<br> Telefono: " . $phone . "<br>Nro de Cédula: " . $socio;
    
    $res = $this->_post($apiUrl, json_encode($params));
    return $res;
  }

}