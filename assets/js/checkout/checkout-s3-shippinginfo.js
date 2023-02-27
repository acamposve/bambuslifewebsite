import "../../css/pages/checkout/checkout.scss"; 
import "../../css/pages/checkout/checkout-s3-shippinginfo.scss"; 

// Import validator 
import validator from "../components/validator.js";
// Import countries restriction
require("../components/countriesRestriction.js");

// Validate login form
var newShippingConstraints = {
  fullname: {
    presence:{
      message: "^Debe ingresar un nombre completo"
    },
  },
  address: {
    presence:{
      message: "^Debe ingresar una direccion"
    },
  },
  city: {
    presence:{
      message: "^Debe ingresar una ciudad"
    },
  },
  postalCode: {
    presence:{
      message: "^Debe ingresar un codigo postal"
    },
  },
  phone: {
    presence:{
      message: "^Debe ingresar un telefono"
    },
  }
}

// Search checked radio option
var getRadioValue = (radios) => {
  var value = null;
  for (var i = 0, length = radios.length; i < length; i++){
    if (radios[i].checked){
      value = radios[i].value;
      break;
    }
  }
  return value;
}

var setRadioValue = (radios, value) => {
  for (var i = 0, length = radios.length; i < length; i++){
    if (radios[i].value == value){
      radios[i].checked = true;
      break;
    }
  }
}

// Clean radio checks
var clearRadioValue = (radios) => {
  for (var i = 0, length = radios.length; i < length; i++){
    radios[i].checked = false;
  }
}

var newAddressFields = document.getElementById('newAddressFields');
var toggleEnableNewAddress = (enable) => {
  if (enable){
    newAddressFields.style.display = 'block';
  }else{
    newAddressFields.style.display = 'none';
  }
  var fields = document.querySelectorAll("#newAddressFields input, #newAddressFields select");
  fields.forEach(field => {
    field.disabled = !enable;
  });
}

document.addEventListener('DOMContentLoaded', () => {
  //
  // Options handler
  //
  var radiosPickup       = document.getElementsByName('pickup_option');
  var radiosAddress      = document.getElementsByName('delivery_address');
  var delivery_options   = document.getElementsByName('delivery_option');
  var newDeliveryAddress = document.getElementById('newDeliveryAddress');

  radiosPickup.forEach(pickOp => {
    pickOp.addEventListener('click', (ev) => {
      setRadioValue(delivery_options, 0);
      clearRadioValue(radiosAddress);
      toggleEnableNewAddress(false);
    })
  });

  radiosAddress.forEach(addrOp => {
    addrOp.addEventListener('click', (ev) => {
      setRadioValue(delivery_options, 1);
      clearRadioValue(radiosPickup);
      toggleEnableNewAddress(ev.currentTarget.value == '__new__');
    })
  });

  delivery_options.forEach( deliveryOp => {
    deliveryOp.addEventListener('click', (ev) => {
      var selectedDeliveryOption = getRadioValue(delivery_options);
      if (selectedDeliveryOption == 0){
        // Pickup
        clearRadioValue(radiosAddress);
        toggleEnableNewAddress(false);
      }else if (selectedDeliveryOption == 1){
        // Delivery
        clearRadioValue(radiosPickup);
      }
    })
  });
  
  //
  // Form validator and submit
  //
  var gralError = document.getElementById('gralError');
  var showGralError = (msg) => {
    gralError.innerText = msg;
    gralError.style.display = 'block';
  }
  var hideGralError = () => {
    gralError.style.display = 'none';
  }
  var shippingForm = document.querySelector("form#shippingInfo");
  var mustValidate = () => {
    var delivOption = getRadioValue(delivery_options);
    var shipOption  = getRadioValue(radiosAddress);
    if (delivOption == '1' && shipOption == '__new__'){
      return true;
    }
    return false;
  }
  validator.initializeConditional(shippingForm, newShippingConstraints, mustValidate, (ev) =>{
    ev.preventDefault();
    var delivOption  = getRadioValue(delivery_options);
    var shipOption   = getRadioValue(radiosAddress);
    var pickupOption = getRadioValue(radiosPickup);
    if (delivOption != '0' && delivOption != '1'){
      showGralError('Debe seleccionar un método de entrega');
      return;
    }
    if (delivOption == '0'){
      if (!pickupOption || pickupOption.length == 0){
        showGralError('Debe seleccionar un lugar de retiro');
        return;
      }
    }
    if (delivOption == '1'){
      if (!shipOption || shipOption.length == 0){
        showGralError('Debe seleccionar un lugar de entrega');
        return;
      }
    }
    var btnLogin = document.getElementById("btnContinue");
    btnLogin.classList.add("is-loading");
    shippingForm.submit();
  });
  toggleEnableNewAddress(false);
});
