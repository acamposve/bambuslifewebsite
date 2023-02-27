import validator from "../components/validator.js";
import SlimSelect from 'slim-select';
import 'slim-select/src/slim-select/slimselect.scss';
import '../../css/components/slimSelect.scss';

var modLogin = {};
modLogin.initialize = (successCb, registerAjax=false) => {
  // Store config
  modLogin.loginCntr = document.getElementById('loginCntr');
  modLogin.registerAjax = registerAjax;
  modLogin.successCb = successCb;
  modLogin.allowChangeMail = modLogin.loginCntr.getAttribute('data-acm');
  // Check first element
  var loginStep0 = document.getElementById('loginStep0');
  if (!loginStep0){
    return;
  }
  //
  // Init panels
  //
  var loginErrMsg   = document.getElementById('loginErrMsg');
  var loginStep1    = document.getElementById('loginStep1');
  var loginStep2    = document.getElementById('loginStep2');
  var step0back     = document.getElementById('step0back');
  var step1next     = document.getElementById('step1next');
  var step2back     = document.getElementById('step2back');
  var loginEmail    = document.getElementById('loginEmail');
  var loginPassword = document.getElementById('loginPassword');
  var loginRemember = document.getElementById('_remember_me');
  var email         = document.getElementById('email');
  var selectedEmail = document.getElementById('selectedEmail');

  function step1Action(ev){
    if (loginEmail.value && loginEmail.value.length > 0 && modLogin.validateEmailInput(loginEmail.value)){
      ev.target.classList.add("is-loading");
      modLogin.checkEmail(loginEmail.value, function(err, exists){
        if (err){
          ev.target.classList.remove("is-loading");
          modLogin.showError('Ocurrió un error verificando el correo electrónico');
        }else{
          modLogin.clearError();
          ev.target.classList.remove("is-loading");
          if (exists){
            // Do login
            document.getElementById('selectedEmail').innerText = loginEmail.value;
            loginStep2.style.display = 'block';
            loginStep1.style.display = 'none';
          }else{
            // Do register
            email.value = loginEmail.value;
            loginStep0.style.display = 'block';
            loginStep1.style.display = 'none';
          }
        }
      });
    }else{
      modLogin.showError('Ingrese un correo electrónico válido');
    }
  }

  step1next.addEventListener("click", function(ev){
    step1Action(ev);
  });
  loginEmail.addEventListener('keyup', function(ev){
    if (ev.keyCode == 13){
      step1Action(ev);
    }
  });

  step2next.addEventListener("click", function(ev){
    if (loginPassword.value && loginPassword.value.length > 0){
      ev.target.classList.add("is-loading");
      modLogin.login(loginEmail.value, loginPassword.value, loginRemember.value, function(err, logged){
        if (err){
          ev.target.classList.remove("is-loading");
          modLogin.showError('Ocurrió un error intentando iniciar sesión');
        }else{
          modLogin.clearError();
          if (logged.logged){
            modLogin.successCb(true);
          }else{
            ev.target.classList.remove("is-loading");
            modLogin.showError('El correo eléctronico o la contraseña no son válidos.');
          }
        }
      });
    }else{
      modLogin.showError('Ingrese una contraseña válida');
    }
  });

  if (step2back){
    step2back.addEventListener("click", function(ev){
      loginStep1.style.display = 'block';
      loginStep2.style.display = 'none';
    })
  }
  
  if (step0back){
    step0back.addEventListener("click", function(ev){
      loginStep1.style.display = 'block';
      loginStep0.style.display = 'none';
    })
  }

  selectedEmail.addEventListener("click", function(ev){
    if (modLogin.allowChangeMail){
      loginStep1.style.display = 'block';
      loginStep2.style.display = 'none';
    }
  });

  //
  // Init Register Form
  //
  var registerConstraints = {
    firstname: {
      presence: true,
      presence:{
        message: "^Debe ingresar un nombre"
      }
    },
    lastname: {
      presence: true,
      presence:{
        message: "^Debe ingresar un apellido"
      }
    },
    customerDocumentId: {
      presence: true,
      presence:{
        message: "^Debe ingresar su documento de identidad"
      }
    },
    regcountry: {
      presence: true,
      presence:{
        message: "^Debe ingresar un país"
      }
    },
    selCity: {
      presence: true,
      presence:{
        message: "^Debe ingresar una ciudad de residencia"
      }
    },
    address: {
      presence: true,
      presence:{
        message: "^Debe ingresar una dirección"
      }
    },
    phonenumber: {
      presence: true,
      presence:{
        message: "^Debe ingresar un teléfono de contacto"
      }
    },
    email: {
      presence: true,
      email: true,
      presence:{
        message: "^Debe ingresar un correo electronico"
      },
      email:{
        message: "^El formato del correo electronico no es valido"
      }
    },
    password: {
      // Password is also required
      presence: true,
      presence:{
        message: "^Debe ingresar una contraseña"
      },
    },
    password2: {
      // Password is also required
      presence: {message: "^Re-ingrese la contraseña"},
      equality: {
        attribute: "password",
        message: "^Las contraseñas no coinciden",
      }
    },
    invoiceCompanyName : function(value, attributes, attributeName, options, constraints) {
      if (!attributes.invoiceAsCompany) return null;
      return {
        presence: {message: "^Debe ingresar el nombre de la empresa"}
      };
    },
    invoiceCompanyRUT : function(value, attributes, attributeName, options, constraints) {
      if (!attributes.invoiceAsCompany) return null;
      return {
        presence: {message: "^Debe ingresar el número fiscal (RUT) de la empresa"}
      };
    },
    invoiceCompanyAddress : function(value, attributes, attributeName, options, constraints) {
      if (!attributes.invoiceAsCompany) return null;
      return {
        presence: {message: "^Debe ingresar la direccón de la empresa"}
      };
    },
    invoiceCompanyState : function(value, attributes, attributeName, options, constraints) {
      if (!attributes.invoiceAsCompany) return null;
      return {
        presence: {message: "^Debe ingresar el estado/departamento de la direccón de la empresa"}
      };
    },
    invoiceCompanyCity : function(value, attributes, attributeName, options, constraints) {
      if (!attributes.invoiceAsCompany) return null;
      return {
        presence: {message: "^Debe ingresar la ciudad de la direccón de la empresa"}
      };
    },
    terms: {
      presence: true,
      inclusion:{
        within: [true],
        message: "^Debe aceptar los terminos y condiciones"
      },
      presence:{
        message: "^Debe aceptar los terminos y condiciones"
      }
    }
  }
  var registerForm = document.querySelector("form#userInfoRegister");
  validator.initialize(registerForm, registerConstraints, function() {
    var btnRegister = document.getElementById("step0next");
    btnRegister.classList.add("is-loading");
    if (modLogin.registerAjax){
      modLogin.doRegisterAjax(function(err, res){
        btnRegister.classList.remove("is-loading");
        if (err){
          console.log(err);
          modLogin.showError('Ocurrió un error intentando registrar el nuevo usuario');
        }else{
          if (res.logged){
            modLogin.successCb(true);
          }else{
            btnRegister.classList.remove("is-loading");
            modLogin.showError('Ocurrió un error intentando registrar el nuevo usuario (2)');
          }
        }
      });
    }else{
      registerForm.submit();
    }
  });
  var invoiceAsCompany      = document.getElementById('invoiceAsCompany');
  if (invoiceAsCompany){
    var invoiceCompanyName    = document.getElementById('invoiceCompanyName');
    var invoiceCompanyRUT     = document.getElementById('invoiceCompanyRUT');
    var invoiceCompanyAddress = document.getElementById('invoiceCompanyAddress');
    var invoiceCompanyState   = document.getElementById('invoiceCompanyState');
    var invoiceCompanyCity    = document.getElementById('invoiceCompanyCity');
    invoiceAsCompany.addEventListener('click', function(ev){
      var valDisplay = 'none';
      if (invoiceAsCompany.checked){
        valDisplay = 'block';
      }
      invoiceCompanyName.parentElement.parentElement.parentElement.style.display = valDisplay;
      invoiceCompanyRUT.parentElement.parentElement.parentElement.style.display = valDisplay;
      invoiceCompanyAddress.parentElement.parentElement.parentElement.style.display = valDisplay;
      invoiceCompanyState.parentElement.parentElement.parentElement.style.display = valDisplay;
      invoiceCompanyCity.parentElement.parentElement.parentElement.style.display = valDisplay;
    });
  }
  //
  // Init Slim Select for city select
  //
  var fCountry = document.getElementById('regcountry');
  var cityId = document.getElementById('cityId');
  var cityName = document.getElementById('cityName');
  var slimCity = null;
  var slimCityTimer = null;
  fCountry.addEventListener('change', function(ev){
    slimCity.setData([]);
    slimCity.set(null);
  });
  function searchCity(search, callback){
    var url = '/api/searchCity/'+fCountry.value+'/'+search;
    fetch(url).then(function (response) {
      return response.json()
    }).then(function (json) {
      let data = []
      var innerHTML = null;
      for (let i = 0; i < json.length; i++) {
        innerHTML = '<div class="ss-item"><div class="ss-item-title">'+json[i].name+'</div><div class="ss-item-subtitle">'+json[i].admin1Name;
        if (json[i].admin2Name){
          innerHTML += ', '+json[i].admin2Name;
        }
        innerHTML += '</div></div>';
        data.push({
          text      : json[i].name, 
          value     : json[i].id,
          innerHTML : innerHTML
        })
      }

      // Upon successful fetch send data to callback function.
      // Be sure to send data back in the proper format.
      // Refer to the method setData for examples of proper format.
      callback(data)
    })
    .catch(function(error) {
      // If any erros happened send false back through the callback
      callback(false)
    })
  }
  slimCity = new SlimSelect({
    select           : '#selCity',
    searchPlaceholder: 'Buscar',
    searchText       : 'Escriba al menos 3 caracteres',
    searchingText    : 'Buscando...',
    ajax             : function (search, callback) {
      // Check search value. If you dont like it callback(false) or callback('Message String')
      if (search.length < 3) {
        callback('Escriba al menos 3 caracteres');
        return
      }
      if (slimCityTimer) {
        clearTimeout(slimCityTimer);
      }
      // Perform your own ajax request here
      slimCityTimer = setTimeout(function(){
        console.log('triggered! with ' + search);
        searchCity(search, callback);
      }, 500);
    },
    beforeOnChange   : (item) => {
      cityId.value = item.value;
      cityName.value = item.text;
      //return false // this will stop propagation
    }
  });
}

modLogin.validateEmailInput = (value) => {
  var re = /^(([^<>()[\]\\.,;:\s@\"]+(\.[^<>()[\]\\.,;:\s@\"]+)*)|(\".+\"))@((\[[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\.[0-9]{1,3}\])|(([a-zA-Z\-0-9]+\.)+[a-zA-Z]{2,}))$/;
  return re.test(value);
}

modLogin.checkEmail = (emailVal, cb) =>{
  var url = '/api/user/checkEmail?em='+encodeURIComponent(emailVal);
  fetch(url).then(function (response) {
    return response.json()
  }).then(function (json) {
    cb(null, json.data.exists);
  }).catch(function(error) {
    cb(error, null);
  })
}

modLogin.validateRUT_UY = (rut) => {
  if (rut.length != 12){
    return false;
  }
  if (!/^([0-9])*$/.test(rut)){
               return false;
    }
  var dc = rut.substr(11, 1);
  var rut = rut.substr(0, 11);
  var total = 0;
  var factor = 2;
 
  for (i = 10; i >= 0; i--) {
    total += (factor * rut.substr(i, 1));
    factor = (factor == 9)?2:++factor;
  }
 
  var dv = 11 - (total % 11);
 
  if (dv == 11){
    dv = 0;
  }else if(dv == 10){
    dv = 1;
  }
  if(dv == dc){
    return true;
  }
  return false;
}

modLogin.login = (email, pass, rememberMe, cb) =>{
  var url = '/api/user/login';
  var data = {
    email       : email,
    password    : pass,
    _remember_me: rememberMe
  }
  fetch(url, {
    method: 'POST',
    body: JSON.stringify(data),
    headers:{
      'Content-Type': 'application/json'
    }
  }).then(function (response) {
    return response.json()
  }).then(function (json) {
    cb(null, json);
  }).catch(function(error) {
    cb(error, null);
  })
}

modLogin.doRegisterAjax = function(cb){
  var regFormData = {
    'email'      : document.getElementById('email').value,
    'password'   : document.getElementById('password').value,
    'firstname'  : document.getElementById('firstname').value,
    'lastname'   : document.getElementById('lastname').value,
    'regcountry' : document.getElementById('regcountry').value,
    'cityId'     : document.getElementById('cityId').value,
    'cityName'   : document.getElementById('cityName').value,
    'address'    : document.getElementById('address').value,
    'phonenumber': document.getElementById('phonenumber').value,
  }
  var url = '/api/user/register';
  fetch(url, {
    method: 'POST',
    body: JSON.stringify(regFormData),
    headers:{
      'Content-Type': 'application/json'
    }
  }).then(function (response) {
    return response.json()
  }).then(function (json) {
    cb(null, json);
  }).catch(function(error) {
    cb(error, null);
  })
};

modLogin.showError = (txt) => {
  loginErrMsg.innerHTML = txt;
  loginErrMsg.style.display = 'block';
  loginErrMsg.focus();
  loginErrMsg.scrollIntoView();
  window.scrollBy(0, -80); 
}

modLogin.clearError = () => {
  loginErrMsg.innerHTML = '';
  loginErrMsg.style.display = 'none';
}

export default modLogin;