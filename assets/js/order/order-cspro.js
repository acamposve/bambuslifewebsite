import "../../css/pages/order/order-cspro.scss"; 
import initializeTabs from "../components/tabs.js"; 
initializeTabs();
// Import validator 
import validator from "../components/validator.js";
// Import PriceTagDisclaimer
require("../components/priceTagDisclaimer.js");
// Import ElectrodesProviders
require("../components/electrodeProviders.js");
// Import countries restriction
require("../components/countriesRestriction.js");
// Import coupons info modal
require("../components/couponsInfo.js");

// Validate login form
var loginConstraints = {
  cspro_os: {
    presence: true,
    presence:{
      message: "^Debe seleccionar un tipo de sistema operativo"
    }
  }
}

document.addEventListener('DOMContentLoaded', () => {
  // Add form events and validation
  var buyForm = document.querySelector("form#formBuy");
  validator.initialize(buyForm, loginConstraints, () =>{
    var btnBuy = document.getElementById("btnBuy");
    btnBuy.classList.add("is-loading");
    buyForm.submit();
  });

  // Add product prices events
  var subsPrice    = document.getElementById('subsPrice');
  var subsCurrency = document.getElementById('subsCurrency');
  //var csProSubs    = document.getElementById("cspro_subs");
  document.querySelectorAll('[id^="cspro_subs"]').forEach(inputRadio => {
    inputRadio.addEventListener("change", function(ev){
      subsPrice.innerText    = ev.currentTarget.getAttribute('data-price') + ' / mes';
      subsCurrency.innerText = ev.currentTarget.getAttribute('data-currency');
    })
  });

});

//
// Hook price tag handlers
//
var priceTag    = document.getElementById('priceTag');
var isRemoteLbl = document.getElementById('isRemoteLbl');
var isLocalLbl  = document.getElementById('isLocalLbl');

var getBasePrice = function(){
  var cspro_items = document.getElementsByName('cspro_os');
  var basePrice = {
    price  : 0,
    isLocal: 0
  };
  var ePrice = null;
  for(var i = 0; i < cspro_items.length; i++){
    if(cspro_items[i].checked && !cspro_items[i].disabled){
      ePrice = parseFloat(cspro_items[i].getAttribute('data-price'));
      if (!isNaN(ePrice)){
        basePrice.price   = ePrice;
        basePrice.isLocal = cspro_items[i].getAttribute('data-isLocal')
        break;
      }
    }
  }
  return basePrice;
}

var calcAdditionals = function(){
  var basePrice = getBasePrice();
  var additionals = document.getElementsByClassName('fAdds');
  var addPrice = 0;
  for (var i=0; i < additionals.length; i++){
    if (additionals[i].checked){
      addPrice = parseFloat(additionals[i].getAttribute('data-price'));
      if (!isNaN(addPrice)){
        basePrice.price += addPrice;
      }
    }
  }
  // priceTag.innerText = parseFloat(Math.round(basePrice * 100) / 100).toFixed(2);
  var priceText = parseFloat(Math.round(basePrice.price * 100) / 100).toFixed(2);
  if (basePrice.isLocal == 0){
    // priceText += ' <span style="color: #999;font-size: 14px;display:block">(*)</span>';
    if (isRemoteLbl){
      isRemoteLbl.style.display = 'inline';
    }
    isLocalLbl.style.display = 'none';
  }else{
    // priceText += ' <span style="color: #999;font-size: 14px;display:block">(producto en plaza)</span>';
    if (isRemoteLbl){
      isRemoteLbl.style.display = 'none';
    }
    isLocalLbl.style.display = 'inline';
  }
  priceTag.innerHTML = priceText;
}

var prodsCntr = document.getElementById('prods');
if (prodsCntr){
  var prods = prodsCntr.querySelectorAll('input[type=radio]')
  prods.forEach(prod => {
    prod.addEventListener('click', function(ev){
      calcAdditionals();
    })
  });
}

// Hook additionals checkbox
var totalAdditionals = document.getElementById('additionals');
if (totalAdditionals){
  if (totalAdditionals.value > 0){
    var eAdditional;
    for(var i=0; i < totalAdditionals.value; i++){
      eAdditional = document.getElementById('additional'+i);
      eAdditional.addEventListener("click", function(ev){
        calcAdditionals();
      });
    }
  }
}





// Click first
var cspro_items = document.getElementsByName('cspro_os');
for(var i = 0; i < cspro_items.length; i++){
  if(!cspro_items[i].disabled){
    cspro_items[i].click();
    break;
  }
}