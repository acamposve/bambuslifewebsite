import "../../css/components/payform.scss";
var Cleave = require('cleave.js').default;

var payForm =  {
  doSubmit      : false,
  hlprCard      : null,
  formFastPay   : null,
  manualSubmit  : null,
  manualCallback: null,

  initialize(manualSubmit=null){
    payForm.manualSubmit = manualSubmit;

    //
    // Form helper
    //
    payForm.cardValidFormat = false;
    payForm.cardValidNumber = false;

    // Capture elements and basic validation
    payForm.hlprForm       = document.getElementById('hlpr-form');
    payForm.elCardNumber   = document.getElementById('cardNumber');
    payForm.hlprCardNumber = document.getElementById('hlpr-cardNumber');
    if (payForm.elCardNumber){
      payForm.elCardNumber.addEventListener('keyup', function(e){
        var realVal = payForm.elCardNumber.value.replace(/ /g, '');
        if (realVal.length >= 16){
          payForm.cardValidFormat = true;
        }else{
          payForm.cardValidFormat = false;
        }
        payForm.checkCardNumber();
      })
    }
    payForm.elCardholderName   = document.getElementById('cardholderName');
    payForm.hlprCardholderName = document.getElementById('hlpr-cardholderName');
    if (payForm.elCardholderName){
      payForm.elCardholderName.addEventListener('keyup', function(e){
        if (payForm.elCardholderName.value.length > 5){
          payForm.elCardholderName.classList.remove('is-danger')
          payForm.elCardholderName.classList.add('is-success');
        }else{
          payForm.elCardholderName.classList.remove('is-success');
          payForm.elCardholderName.classList.add('is-danger')
        }
      });
    }
    payForm.elCardExpiration      = document.getElementById('cardExpiration');
    payForm.elCardExpirationMonth = document.getElementById('cardExpirationMonth');
    payForm.elCardExpirationYear  = document.getElementById('cardExpirationYear');
    payForm.hlprCardExpiration    = document.getElementById('hlpr-cardExpiration');
    if (payForm.elCardExpiration){
      payForm.elCardExpiration.addEventListener('keyup', function(e){
        var fDate = payForm.elCardExpiration.value;
        if (fDate.indexOf('/') >= 0){
          var fDateSp = fDate.split('/');
          var month   = fDateSp[0];
          var year    = fDateSp[1];
          var today   = new Date();
          var dayNum  = today.getDate();
          var expDate = new Date('20'+year,month,dayNum);
          // console.log(expDate.toString());
          if (today > expDate){
            // Expired
            // TODO: Clear MP hidden fields
            payForm.elCardExpiration.classList.remove('is-success');
            payForm.elCardExpiration.classList.add('is-danger');
            payForm.elCardExpirationMonth.setAttribute('value', '');
            payForm.elCardExpirationYear.setAttribute('value', '');
          }else{
            // Valid
            // TODO: Add values to MP hidden fields
            payForm.elCardExpiration.classList.add('is-success');
            payForm.elCardExpiration.classList.remove('is-danger');
            payForm.elCardExpirationMonth.setAttribute('value', month);
            payForm.elCardExpirationYear.setAttribute('value', year);
          }
        }
      });
    }
    payForm.elSecurityCode   = document.getElementById('securityCode');
    payForm.hlprSecurityCode = document.getElementById('hlpr-securityCode');
    if (payForm.elSecurityCode){
      payForm.elSecurityCode.addEventListener('keyup', function(e){
        if (payForm.elSecurityCode.value.length != 3){
          payForm.elSecurityCode.classList.remove('is-success');
          payForm.elSecurityCode.classList.add('is-danger');
        }else{
          payForm.elSecurityCode.classList.remove('is-danger');
          payForm.elSecurityCode.classList.add('is-success');
        }
      });
    }
    payForm.elDocNumber = document.getElementById('docNumber');
    payForm.hlprDocNumber = document.getElementById('hlpr-docNumber');
    if (payForm.elDocNumber){
      payForm.elDocNumber.addEventListener('keyup', function(e){
        if (payForm.elDocNumber.value.length >= 6){
          payForm.elDocNumber.classList.remove('is-danger');
          payForm.elDocNumber.classList.add('is-success');
        }else{
          payForm.elDocNumber.classList.remove('is-success');
          payForm.elDocNumber.classList.add('is-danger');
        }
      });
    }
    
    // Cleave
    if (!Cleave) Cleave = require('cleave.js');
    var cleaveCardNumber = new Cleave('.cardNumber', {creditCard: true});
    var cleaveCardExpiration = new Cleave('.cardExpiration', {
      date: true,
      datePattern: ['m', 'y']
    });

    //
    // Hook Pay modal
    //
    payForm.dialogProcess    = document.getElementById('modalProgress');
    payForm.payModalBtn      = document.getElementById('payModalBtn');
    payForm.payModal         = document.getElementById('payModal');
    payForm.payModalBack     = document.getElementById('payModalBack');
    payForm.payModalCloseBtn = document.getElementById('payModalCloseBtn');
    if (payForm.payModalBtn){
      payForm.payModalBtn.addEventListener("click", function(ev){
        ev.preventDefault();
        if (payForm.manualSubmit){
          console.log('Manual mode, calling external form submit...');
          payForm.manualSubmit();
          return false;
        }
        payForm.payModal.classList.add("is-active");
      });
    }
    if (payForm.payModalCloseBtn){
      payForm.payModalCloseBtn.addEventListener("click", function(ev){
        ev.preventDefault();
        payForm.payModal.classList.remove("is-active");
      });
    }
    if (payForm.payModalBack){
      payForm.payModalBack.addEventListener("click", function(ev){
        ev.preventDefault();
        payForm.payModal.classList.remove("is-active");
      });
    }

    //
    // MP CUSTOM FORM
    //
    payForm.payModalBtnEl = document.getElementById('payModalBtn');
    Mercadopago.setPublishableKey(payForm.payModalBtnEl.getAttribute('data-mppkey'));
    Mercadopago.getIdentificationTypes();

    payForm.totalAmount = payForm.payModalBtnEl.getAttribute('data-payAmount'); 

    payForm.cardNumField = document.getElementById('cardNumber');
    payForm.cardNumField.addEventListener('keyup', payForm.guessingPaymentMethod);
    payForm.elPMIcon = document.getElementById('pmicon');

    payForm.form = document.getElementById('pay');
    payForm.form.addEventListener('submit', (event) => {
      event.preventDefault();
      if(!payForm.doSubmit){
        payForm.clearError();
        payForm.dialogProcess.classList.add("is-active");
        var $form = document.querySelector('#pay');
        Mercadopago.createToken($form, payForm.sdkResponseHandler); // The function "sdkResponseHandler" is defined below
        return false;
      }
    });
  },
  checkCardNumber(){
    if (payForm.cardValidFormat && payForm.cardValidNumber){
      payForm.elCardNumber.classList.add('is-success');
      payForm.elCardNumber.classList.remove('is-danger')
    }else{
      payForm.elCardNumber.classList.remove('is-success');
      payForm.elCardNumber.classList.add('is-danger')
    }
  },
  getBin(){
    var cardStart = document.getElementById('cardNumber');
    return cardStart.value.replace(/ /g,'');
  },
  guessingPaymentMethod(event) {
    var bin = payForm.getBin();
    if (event.type == "keyup") {
        if (bin.length >= 6) {
            Mercadopago.getPaymentMethod({
                "bin": bin
            }, payForm.setPaymentMethodInfo);
            Mercadopago.getInstallments({
              "bin"    : bin,
              "amount" : payForm.totalAmount
            }, payForm.setInstallmentInfo);
        }else{
          payForm.elPMIcon.classList.forEach( function(item){
            payForm.elPMIcon.classList.remove(item);
          });
        }
    } else {
        setTimeout(function() {
            if (bin.length >= 6) {
                Mercadopago.getPaymentMethod({
                    "bin": bin
                }, payForm.setPaymentMethodInfo);
                Mercadopago.getInstallments({
                  "bin"    : bin,
                  "amount" : payForm.totalAmount
                }, payForm.setInstallmentInfo);
            }else{
              payForm.elPMIcon.classList.forEach( function(item){
                payForm.elPMIcon.classList.remove(item);
              });
            }
        }, 100);
    }
  },
  setPaymentMethodInfo(status, response) {
    if (status == 200) {
      var paymentMethod = document.getElementById('payment_method_id');
      if (!paymentMethod){
        paymentMethod = document.createElement("input");
        paymentMethod.setAttribute('name', "payment_method_id");
        paymentMethod.setAttribute('id', "payment_method_id");
        paymentMethod.setAttribute('type', "hidden");
        payForm.form.appendChild(paymentMethod);
      }

      paymentMethod.setAttribute('value', response[0].id);

      payForm.elPMIcon.classList.forEach( function(item){
        payForm.elPMIcon.classList.remove(item);
      });
      payForm.elPMIcon.classList.add('paymentmethod-'+response[0].id);
      payForm.elPMIcon.classList.add('paymentmethod-small');
    } else {
      document.querySelector("input[name=payment_method_id]").value = response[0].id;
      payForm.elPMIcon.classList.forEach( function(item){
        payForm.elPMIcon.classList.remove(item);
      });
    }
  },
  setInstallmentInfo(status, response){
    if (status == 200) {
      var paymentMethod = document.getElementById('issuer_id');
      if (!paymentMethod){
        paymentMethod = document.createElement("input");
        paymentMethod.setAttribute('name', "issuer_id");
        paymentMethod.setAttribute('id', "issuer_id");
        paymentMethod.setAttribute('type', "hidden");
        payForm.form.appendChild(paymentMethod);
      }

      paymentMethod.setAttribute('value', response[0].issuer.id);
      payForm.cardValidNumber = true;
      payForm.checkCardNumber();
    } else {
      if (response && response.length > 0){
        if (response[0].id){
          document.querySelector("input[name=issuer_id]").value = response[0].id;
        }
      }
      payForm.cardValidNumber = false;
      payForm.checkCardNumber();
    }
  },
  sdkResponseHandler(status, response) {
    if (status != 200 && status != 201) {
      payForm.dialogProcess.classList.remove("is-active");
      if (response && response.cause && response.cause.length){
        response.cause.forEach( function(cause){
          if (cause.code == "205" || cause.code == "E301"){
            payForm.showError(elCardNumber, hlprCardNumber);
          }else if (cause.code == "208" || cause.code == "325"){
            payForm.showError(elCardExpiration, hlprCardExpiration);
          }else if (cause.code == "209" || cause.code == "326"){
            payForm.showError(elCardExpiration, hlprCardExpiration);
          }else if (cause.code == "214" || cause.code == "324"){
            payForm.showError(elDocNumber, hlprDocNumber);
          }else if (cause.code == "221" || cause.code == "316"){
            payForm.showError(elCardholderName, hlprCardholderName);
          }else if (cause.code == "224" || cause.code == "E302"){
            payForm.showError(elSecurityCode, hlprSecurityCode);
          }else{
            payForm.showError(null, hlprForm);
          }
        });
      }else{
        payForm.showError(null, hlprForm);
      }
    }else{
      if (payForm.manualCallback){
        payForm.manualCallback(response.id);
      }else{
        var card = document.createElement('input');
        card.setAttribute('name', 'token');
        card.setAttribute('type', 'hidden');
        card.setAttribute('value', response.id);
        payForm.form.appendChild(card);
        payForm.doSubmit=true;
        payForm.dialogProcess.classList.add("is-active");
        payForm.form.submit();
      }
    }
  },
  showError(el, hel){
    if (el){
      el.classList.remove('is-success');
      el.classList.add('is-danger');
    }
    hel.style.display = "block";
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
    payForm.hlprForm.style.display = "none";
  },
  requestToken(cb){
    payForm.manualCallback = cb;
    payForm.clearError();
    console.log('Open dialog...');
    payForm.payModal.classList.add("is-active");
    //Mercadopago.createToken(payForm.form, payForm.sdkResponseHandler);
  }
}

export default payForm;