import "../../css/pages/checkout/checkout.scss"; 
import "../../css/pages/order/cart.scss";
import validator from "../components/validator.js";
import payForm from "../components/payform.js";

// Validate coupon form
var couponFormConstraints = {
  couponCode: {
    presence: true,
    presence:{
      message: "^Debe ingresar un código válido"
    }
  }
}
document.addEventListener('DOMContentLoaded', () => {
  var couponForm = document.querySelector("form#formCoupon");
  if (couponForm){
    validator.initialize(couponForm, couponFormConstraints, () =>{
      couponForm.submit();
    });
  }
});


// New Coupon
var couponForm = document.querySelector("form#formCoupon");
var modalDiscount          = document.getElementById('modalDiscount');
var modalDiscountBack      = document.getElementById('modalDiscountBack');
var modalDiscountOK        = document.getElementById('modalDiscountOK');
var modalDiscountCancel    = document.getElementById('modalDiscountCancel');
var btnDiscount            = document.getElementById('btnDiscount');
btnDiscount.addEventListener("click", function(ev){
  ev.preventDefault();
  modalDiscount.classList.add("is-active");
});
modalDiscountBack.addEventListener("click", function(ev){
  ev.preventDefault();
  modalDiscount.classList.remove("is-active");
});
modalDiscountCancel.addEventListener("click", function(ev){
  ev.preventDefault();
  modalDiscount.classList.remove("is-active");
});
modalDiscountOK.addEventListener("click", function(ev){
  ev.preventDefault();
  // Check valid data
  var bCanContinue = false;
  if (selBeneficiary.value == 'OTHERS'){
    // TODO
    bCanContinue = true;
  }else if (selBeneficiary.value == 'ANTEL'){
    // Check ANTEL
    var phoneNum = document.getElementById('discAntelCelnum');
    var errMsgNum = document.getElementById('discAntelCelnumErr');
    if (!phoneNum.value || phoneNum.value.length < 9){
      errMsgNum.innerHTML = '<p class="help-block error">Ingrese un número de celular válido</p>';
      return;
    }else{
      errMsgNum.innerHTML = '';
    }
    var couponCode = document.getElementById('discAntelDiscCode');
    var errMsgCode = document.getElementById('discAntelDiscCodeErr');
    if (!couponCode.value || couponCode.value.length < 2){
      errMsgCode.innerHTML = '<p class="help-block error">Ingrese un código de cupón válido</p>';
      return;
    }else{
      errMsgCode.innerHTML = '';
    }
    bCanContinue = true;
  }else if (selBeneficiary.value == 'CEP'){
    // Check CLUB EL PAIS
    var ciNum = document.getElementById('discCEPCI');
    var errMsgCI = document.getElementById('discCEPCIErr');
    if (!ciNum.value || ciNum.value.length < 10 || isNaN(ciNum.value)){
      errMsgCI.innerHTML = '<p class="help-block error">Ingrese su número de tarjeta del club sin espacios.</p>';
      return;
    }else{
      errMsgCI.innerHTML = '';
    }
    bCanContinue = true;
  }
  
  if (bCanContinue){
    modalDiscount.classList.remove("is-active");
    couponForm.submit();
  }
});
var selBeneficiary = document.getElementById('beneficiary');
var discPanel      = document.getElementById('discountsPanel');
var discBambus     = document.getElementById('discBambus');
//
// Handle options
//
var country = btnDiscount.getAttribute('data-country');
if (country == 'UY'){
  var discAntel = document.getElementById('discAntel');
  var discCEP = document.getElementById('discCEP');
  selBeneficiary.addEventListener('change', function(ev){
    for (var el of discPanel.children){
      el.style.display = 'none';
    };
    if (ev.currentTarget.value == 'OTHERS'){
      discBambus.style.display = 'block';
    }else if (ev.currentTarget.value == 'ANTEL'){
      discAntel.style.display = 'block';
    }else if (ev.currentTarget.value == 'CEP'){
      discCEP.style.display = 'block';
    }
  });
}else{
  selBeneficiary.addEventListener('change', function(ev){
    for (var el of discPanel.children){
      el.style.display = 'none';
    };
    if (ev.currentTarget.value == 'OTHERS'){
      discBambus.style.display = 'block';
    }
  });
}
for (var i=0; i < discPanel.children.length; i++){
  discPanel.children[i].style.display = 'none';
};

//
// Initialize new card payment
//
payForm.initialize();