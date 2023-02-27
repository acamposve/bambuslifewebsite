<?php

namespace App\Twig;

use Twig\Extension\AbstractExtension;
use Twig\TwigFilter;
use Twig\TwigFunction;

class AppExtension extends AbstractExtension{
  public function getFilters(){
    return [
      new TwigFilter('price',                  [$this, 'formatPrice']),
      new TwigFilter('momentDate',             [$this, 'formatMomentDate']),
      new TwigFilter('orderStatus',            [$this, 'formatOrderStatus']),
      new TwigFilter('invoiceStatus',          [$this, 'formatInvoiceStatus']),
      new TwigFilter('subscriptionStatus',     [$this, 'formatSubscriptionStatus']),
      new TwigFilter('subscriptionStatusIcon', [$this, 'formatSubscriptionStatusIcon']),
      new TwigFilter('cUrlEncode',             [$this, 'cUrlEncode']),
    ];
  }

  public function getFunctions(){
    return [
      new TwigFunction('cUrlEncode', [$this, 'cUrlEncode']),
    ];
  }

  public function formatPrice($number, $decimals = 2, $decPoint = ',', $thousandsSep = '.'){
    //$price = number_format($number, $decimals, $decPoint, $thousandsSep);
    $price = $number;

    return $price;
  }

  public function formatMomentDate($date, $country = 'UY', $method = 'calendar', $format = NULL){
    $m = new \Moment\Moment($date, 'UTC');
    if ($country == 'UY'){
      $m->setTimezone('GMT-0300');
    }
    if ($method == 'calendar'){
      return $m->calendar();
    }
  }

  public function formatOrderStatus($status){
    if ($status == 'To Deliver'){
      return "Preparando envío";
    } else if ($status == 'To Bill'){
      return "Pendiente de pago";
    } else if ($status == 'Completed'){
      return "Entregada";
    }
  }

  public function formatInvoiceStatus($status){
    if ($status == 'Unpaid'){
      return "Pendiente de pago";
    }else if ($status == 'Paid'){
      return "Paga";
    }else if ($status == 'Cancelled'){
      return "Cancelada";
    }else if ($status == 'Overdue'){
      return "Vencida";
    }else{
      return $status;
    }
  }

  public function formatSubscriptionStatus($status){
    if ($status == 'Active'){
      return "Activa";
    } else if ($status == 'Unpaid'){
      return "Pago pendiente";
    } else if ($status == 'Suspended'){
      return "Suspendida";
    } else if ($status == 'Cancelled'){
      return "Cancelada";
    } else if ($status == 'Past Due Date'){
      return "Pago vencido";
    } else if ($status == 'Trialling'){
      return "Período de prueba";
    }
  }

  public function formatSubscriptionStatusIcon($status){
    if ($status == 'Active'){
      return '<i class="fas fa-check-circle" style="color:#38B09D"></i>';
    }else if ($status == 'Trialling'){
      return '<i class="fas fa-check-circle" style="color:#38B09D"></i>';
    } else if ($status == 'Suspended'){
      return '<i class="fas fa-exclamation-circle" style="color:#FF9800"></i>';
    } else if ($status == 'Unpaid'){
      return '<i class="fas fa-exclamation-circle" style="color:#FF9800"></i>';
    } else if ($status == 'Cancelled'){
      return '<i class="fas fa-times-circle" style="color:#FF5722"></i>';
    } else if ($status == 'Past Due Date'){
      return '<i class="fas fa-exclamation-circle" style="color:#FF9800"></i>';
    }
  }

  public function cUrlEncode($val){
    return urlencode(str_replace(' ', '-', $val));
  }
}