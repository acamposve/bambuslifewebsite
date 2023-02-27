
var fastPayForm =  {
  doSubmit      : false,
  hlprCard      : null,
  formFastPay   : null,
  manualSubmit  : null,
  manualCallback: null,

  initialize(manualSubmit=null){
    fastPayForm.dialogProcess = document.getElementById('modalProgress');
    fastPayForm.manualSubmit  = manualSubmit;
    fastPayForm.hlprCard = document.getElementById('hlpr-card');

    // Setup MP
    var payBtn = document.getElementById('fastPayBtn');
    if (!payBtn) return;
    Mercadopago.setPublishableKey(payBtn.getAttribute('data-mppkey'));
    Mercadopago.getIdentificationTypes();

    // Handle form submit
    fastPayForm.formFastPay = document.getElementById('fastpay');
    fastPayForm.formFastPay.addEventListener('submit', (event) => {
      event.preventDefault();
      if (fastPayForm.manualSubmit){
        console.log('Manual mode, calling external form submit...');
        fastPayForm.manualSubmit();
        return false;
      }
      console.log('Handling MP form submit...');
      if(!fastPayForm.doSubmit){
        fastPayForm.clearError();
        console.log('Creating token...');
        fastPayForm.dialogProcess.classList.add("is-active");
        Mercadopago.createToken(fastPayForm.formFastPay, fastPayForm.sdkResponseHandler); // The function "sdkResponseHandler" is defined below
        return false;
      }else{
        console.log('Submitting MP form...');
        return false;
      }
    });
  },
  sdkResponseHandler(status, response) {
    if (status != 200 && status != 201) {
      fastPayForm.dialogProcess.classList.remove("is-active");
      if (response && response.cause && response.cause.length){
        response.cause.forEach( function(cause){
          if (cause.code == "205" || cause.code == "E301"){
            fastPayForm.showError(null, fastPayForm.hlprCard, "Error 205 o E301");
          }else if (cause.code == "208" || cause.code == "325"){
            fastPayForm.showError(null, fastPayForm.hlprCard, "Error 208 o 325");
          }else if (cause.code == "209" || cause.code == "326"){
            fastPayForm.showError(null, fastPayForm.hlprCard, "Error 209 o 326");
          }else if (cause.code == "214" || cause.code == "324"){
            fastPayForm.showError(null, fastPayForm.hlprCard, "Error 214 o 324");
          }else if (cause.code == "221" || cause.code == "316"){
            fastPayForm.showError(null, fastPayForm.hlprCard, "Error 221 o 316");
          }else if (cause.code == "224" || cause.code == "E302"){
            fastPayForm.showError(null, fastPayForm.hlprCard, "Error 224 o E302");
          }else{
            fastPayForm.showError(null, fastPayForm.hlprForm);
          }
        });
      }else{
        fastPayForm.showError(null, hlprForm);
      }
      if (fastPayForm.manualCallback){
        fastPayForm.manualCallback(null);
      }
    }else{
      if (fastPayForm.manualCallback){
        fastPayForm.manualCallback(response.id);
      }else{
        var card = document.createElement('input');
        card.setAttribute('name', 'token');
        card.setAttribute('type', 'hidden');
        card.setAttribute('value', response.id);
        fastPayForm.formFastPay.appendChild(card);
        fastPayForm.doSubmit=true;
        fastPayForm.formFastPay.submit();
      }
    }
  },
  requestToken(cb){
    fastPayForm.manualCallback = cb;
    fastPayForm.clearError();
    console.log('Manually creating token...');
    fastPayForm.dialogProcess.classList.add("is-active");
    Mercadopago.createToken(fastPayForm.formFastPay, fastPayForm.sdkResponseHandler);
  },
  showError(el, hel, txt){
    if (el){
      el.classList.remove('is-success');
      el.classList.add('is-danger');
    }
    hel.style.display = "block";
    hel.innerText = txt;
  },
  clearError(){
    var els = document.getElementsByClassName('help');
    for(var i=0; i < els.length; i++){
      els[i].style.display = "none";
    }
    els = document.getElementsByClassName('input');
    for(i=0; i < els.length; i++){
      els[i].classList.remove('is-success');
      els[i].classList.remove('is-danger');
    }
    fastPayForm.hlprCard.style.display = "none";
  },
  addFormField(form, name, value){
    var field = document.createElement('input');
    field.setAttribute('name', name);
    field.setAttribute('type', 'hidden');
    field.setAttribute('value', value);
    form.appendChild(field);
  }
}

export default fastPayForm;