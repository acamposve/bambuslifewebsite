<?php

namespace App\Controller\Components;

use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\Request;

use App\Controller\AuthController;
use App\Utils\Settings;

class HeaderController extends AbstractController {

  private $authCtrl;

  public function __construct(AuthController $authCtrl){
    $this->authCtrl = $authCtrl;
  }


  public function index(Request $request){
    // Get locale
    $locale = $request->getLocale();
    $country = Settings::countries[$locale];
    // Load logged user
    $user = $this->authCtrl->getUserData($request);
    // Load cart
    $cart_items = $this->get('session')->get('checkout_cart_items');
    if ($cart_items){
      $itemsInCart = 0;
      for($i=0; $i < count($cart_items); $i++){
        $itemsInCart += $cart_items[$i]['qty'];
      }
      if ($itemsInCart == 0){
        $itemsInCart = NULL;
      }
    }else{
      $itemsInCart = NULL;
    }
    return $this->render('components/header.html.twig', [
      'locale'      => $locale,
      'country'     => $country,
      'user'        => $user,
      'itemsInCart' => $itemsInCart
    ]);
  }

}