<?php

namespace App\Controller;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Cookie;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RedirectResponse;

use Psr\Log\LoggerInterface;
use App\Service\HubClient;
use App\Utils\Settings;


class PressReleasesController extends AbstractController{

  
    public function __construct(HubClient $hubClient, LoggerInterface $logger){
        $this->hubClient = $hubClient;
        $this->logger = $logger;
    }

    public function index(Request $request){
        return $this->render('pressreleases/_press.html.twig');
    }

    public function secnacdep(Request $request){
      return $this->render('pressreleases/secnacdep.html.twig');
    }

    public function webinar(Request $request){
      return $this->render('pressreleases/webinar.html.twig');
    }

}