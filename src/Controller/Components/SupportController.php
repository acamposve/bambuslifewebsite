<?php

namespace App\Controller\Components;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

use App\Controller\AuthController;
use App\Service\HubClient;
use App\Utils\Settings;
use Psr\Log\LoggerInterface;

class SupportController extends AbstractController {

  private $authCtrl;
  private $logger;
  private $hubClient;

  public function __construct(AuthController $authCtrl, LoggerInterface $logger, HubClient $hubClient){
    $this->authCtrl = $authCtrl;
    $this->logger = $logger;
    $this->hubClient = $hubClient;
  }

  public function index(Request $request){
    $user = $this->authCtrl->getUserData($request);
    $support_country = $this->hubClient->resolveWebCountry($request);

    if ($user) { 
      $support_country = $support_country . "_logged";
    }

    $this->logger->debug("SupportController | render | support_country: " . $support_country);
    if (!array_key_exists($support_country, Settings::support_parties)) {
      $this->logger->debug("SupportController | render | No party found. Will default.");
      $support_country = 'default';
      if ($user) { 
        $support_country = $support_country . "_logged";
      }
    };

    $support_party = Settings::support_parties[$support_country];
    $this->logger->debug("SupportController | render | support_party " . $support_party);

    return $this->render('components/support.html.twig', [
      'supportParty' => $support_party
    ]);
  }

}