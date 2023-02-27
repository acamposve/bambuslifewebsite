<?php

namespace App\Controller\Components;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class SubscriptionLinesController extends AbstractController {


  public function __construct(){
  }


  public function index($data){

    return $this->render('components/subscriptionLines.html.twig', [
      'data'              => $data
    ]);
  }

}