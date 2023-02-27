import "../../css/pages/order/order-csactive.scss"; 
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
  csactive_os: {
    presence: true,
    presence:{
      message: "^Debe seleccionar un tipo de sistema operativo"
    }
  }
}

document.addEventListener('DOMContentLoaded', () => {
  // Initialize form validator
  var buyForm = document.querySelector("form#formBuy");
  validator.initialize(buyForm, loginConstraints, () =>{
    var btnBuy = document.getElementById("btnBuy");
    btnBuy.classList.add("is-loading");
    buyForm.submit();
  });

  // Hook ECG Interpretation modal
  var ecgInterTitle = document.getElementById('ecgInterTitle');
  var ecgInterModal = document.getElementById('ecgInterModal');
  var ecgInterModalBack = document.getElementById('ecgInterModalBack');
  var closeBtn = document.getElementById('ecgInterModalCloseBtn');
  if (ecgInterTitle){
    ecgInterTitle.addEventListener("click", function(ev){
      ev.preventDefault();
      ecgInterModal.classList.add("is-active");
    });
  }
  if (closeBtn){
    closeBtn.addEventListener("click", function(ev){
      ev.preventDefault();
      ecgInterModal.classList.remove("is-active");
    });
  }
  if (ecgInterModalBack){
    ecgInterModalBack.addEventListener("click", function(ev){
      ev.preventDefault();
      ecgInterModal.classList.remove("is-active");
    });
  }

  // Hook Connector Helper modal
  var ecgInterTitle = document.getElementById('connHelperTitle');
  var connHelperModal = document.getElementById('connHelperModal');
  var connHelperModalBack = document.getElementById('connHelperModalBack');
  var connHelperModalCloseBtn = document.getElementById('connHelperModalCloseBtn');
  connHelperTitle.addEventListener("click", function(ev){
    ev.preventDefault();
    connHelperModal.classList.add("is-active");
  });
  connHelperModalCloseBtn.addEventListener("click", function(ev){
    ev.preventDefault();
    connHelperModal.classList.remove("is-active");
  });
  connHelperModalBack.addEventListener("click", function(ev){
    ev.preventDefault();
    connHelperModal.classList.remove("is-active");
  });


  //
  // Hook price tag handlers
  //
  var priceTag    = document.getElementById('priceTag');
  var isRemoteLbl = document.getElementById('isRemoteLbl');
  var isLocalLbl  = document.getElementById('isLocalLbl');

  var getBasePrice = function(){
    var csactivce_items = document.getElementsByName('csactive_os');
    var basePrice = {
      price  : 0,
      isLocal: 0
    };
    var ePrice = null;
    for(var i = 0; i < csactivce_items.length; i++){
      if(csactivce_items[i].checked && !csactivce_items[i].disabled){
        ePrice = parseFloat(csactivce_items[i].getAttribute('data-price'));
        if (!isNaN(ePrice)){
          basePrice.price   = ePrice;
          basePrice.isLocal = csactivce_items[i].getAttribute('data-isLocal')
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
    var priceText = parseFloat(Math.round(basePrice.price * 100) / 100).toFixed(2);
    if (basePrice.isLocal == 0){
      // priceText += ' <span style="color: #999;font-size: 14px;">(*)</span>';
      if (isRemoteLbl){
        isRemoteLbl.style.display = 'inline';
      }
      isLocalLbl.style.display = 'none';
    }else{
      // priceText += ' <span style="color: #999;font-size: 14px;">(producto en plaza)</span>';
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

  // Hook ECG interpretation checkbox
  var csactive_ecginter = document.getElementById('csactive_ecginter');
  if (csactive_ecginter){
    csactive_ecginter.addEventListener("click", function(ev){
      calcAdditionals();
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
  var csactivce_items = document.getElementsByName('csactive_os');
  for(var i = 0; i < csactivce_items.length; i++){
    if(!csactivce_items[i].disabled){
      csactivce_items[i].click();
      break;
    }
  }
  
});

