<?php

namespace App\Controller\Components;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;

class InvoiceLinesController extends AbstractController {


  public function __construct(){
  }


  public function index($data, $show_remove){

    return $this->render('components/invoiceLines.html.twig', [
      'data'              => $data,
      'show_remove'       => $show_remove
    ]);
  }

}