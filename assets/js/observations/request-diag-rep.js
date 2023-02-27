require('../../css/pages/observations/request-diag-rep.scss');
import validator from "../components/validator.js";
import payForm from "../components/payform.js";
import fastPayForm from "../components/fastpayform.js";


//
// Capture submit button to check if charge is needed
//
var btnDiagReq = document.getElementById('btnDiagReq');

//
// Init form validation
//
var calledForm = null;
var requestFormContraints = {
  priority: {
    presence: true,
    presence:{
      message: "^Debe seleccionar una razón"
    }
  }
}
var reqDiagRepForm = document.querySelector("form#reqDiagRepForm");
if (reqDiagRepForm){
  validator.initialize(reqDiagRepForm, requestFormContraints, function(ev) {
    if (btnDiagReq){
      //
      // No charge, just request
      //
      btnDiagReq.classList.add("is-loading");
      reqDiagRepForm.submit();
    }else{
      //
      // Trigger charge before submit
      //
      console.log('Requesting token...');
      calledForm.requestToken(function(token){
        if (token){
          console.log('Token acquired, adding it and submitting form...');
          var card = document.createElement('input');
          card.setAttribute('name', 'mptoken');
          card.setAttribute('type', 'hidden');
          card.setAttribute('value', token);
          reqDiagRepForm.appendChild(card);
          
          var btnRequest = document.getElementById("fastPayBtn");
          btnRequest.classList.add("is-loading");

          reqDiagRepForm.submit();
        }else{
          console.log('No token acquired!');
        }
      });
    }
  });
}

//
// Init questions interactivity
//
function toggleConditionAdditionals(questionId, showElement){
  var condAddInfoEls = document.querySelectorAll('.cond-add-info-cntr');
  if (condAddInfoEls && condAddInfoEls.length){
    var addItemEl = null;
    for (var i=0; i < condAddInfoEls.length; i++) {
      addItemEl = condAddInfoEls.item(i);
      if (addItemEl.getAttribute('data-qid') == questionId){
        if (showElement){
          addItemEl.classList.add('cond-add-info-show');
        }else{
          addItemEl.classList.remove('cond-add-info-show');
        }
      }
    }
  }
}
var condEls = document.querySelectorAll('.question');
if (condEls && condEls.length > 0){
  var el = null;
  for (var i=0; i < condEls.length; i++) {
    el = condEls.item(i);
    if (el.getAttribute('data-qcoding') == 'BAMBUSLIFE' && el.getAttribute('data-qcode') == 'PATIENT_CONDITION'){
      // Capture checkboxes 
      var condChecksEls = el.querySelectorAll('input[type="checkbox"]');
      for (var x=0; x < condChecksEls.length; x++) {
        condChecksEls.item(x).addEventListener("click", function(ev) {
          toggleConditionAdditionals(ev.currentTarget.getAttribute('data-qid'), ev.currentTarget.checked);
        });
      }
    }
  }
}

//
// Check if we must charge
//
if (btnDiagReq){
  //
  // No charge, just request
  //
  btnDiagReq.addEventListener("click", function(ev){
    console.log('Triggering form submit...');
    calledForm = fastPayForm;
    var event = new Event('submit', {
      'bubbles': true,
      'cancelable': true
    });
    reqDiagRepForm.dispatchEvent(event);
  });
}else{
  //
  // Must charge
  //
  //
  // Initialize current cards payment
  //
  fastPayForm.initialize(function(){
    console.log('Triggering form submit...');
    calledForm = fastPayForm;
    var event = new Event('submit', {
      'bubbles': true,
      'cancelable': true
    });
    reqDiagRepForm.dispatchEvent(event);
  });
  
  //
  // Initialize new card payment
  //
  payForm.initialize(function(){
    console.log('Triggering form submit...');
    calledForm = payForm;
    var event = new Event('submit', {
      'bubbles': true,
      'cancelable': true
    });
    reqDiagRepForm.dispatchEvent(event);
  });
}
