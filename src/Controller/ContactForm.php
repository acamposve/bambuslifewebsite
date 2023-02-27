<?php

namespace App\Controller;

use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

use App\Service\HubClient;
use App\Service\NgageClient;

class ContactForm extends AbstractController {

  private $hubClient;
  private $ngageClient;
  private $logger;

  public function __construct(HubClient $hubClient, NgageClient $ngageClient, LoggerInterface $logger){
    $this->hubClient = $hubClient;
    $this->ngageClient = $ngageClient;
    $this->logger    = $logger;
  }

  public function contactForm(){
    return $this->render('user/contactForm.html.twig', ['error'=> NULL, 'flagForm' => 0]);
  }

  public function contactFormPost(Request $request){
    // Build POST request:
    $recaptcha_url      = 'https://www.google.com/recaptcha/api/siteverify';
    $recaptcha_secret   = $_ENV["RECAPTCHA_KEY"];
    $recaptcha_response = $_POST['recaptcha_response'];

    // Make and decode POST request:
    $recaptcha = file_get_contents($recaptcha_url . '?secret=' . $recaptcha_secret . '&response=' . $recaptcha_response);
    $recaptcha = json_decode($recaptcha);

    // Take action based on the score returned:
    if ($recaptcha->score >= 0.5) {
      // Verified - send email
      $data    = $request->request->get('form');
      $name    = $request->request->get('name');
      $email   = $request->request->get('email');
      $phone   = $request->request->get('phone');
      $message = $request->request->get('message');
      $this->logger->debug("ContactForm | contactForm | Start...");
      $cliRes = $this->ngageClient->sendContactForm($name, $email, $phone, $message);
      if (!$cliRes['resolved']){
        return $this->render('user/contactForm.html.twig', ['error'=> NULL, 'flagForm' => 2]);
      }else{
        return $this->render('user/contactForm.html.twig', ['error'=> NULL, 'flagForm' => 1]);
      }
    } else {
      // Not verified - show form error
      return $this->render('user/contactForm.html.twig', ['error'=> NULL, 'flagForm' => 3]);
    }
    
    
    
  }

}
