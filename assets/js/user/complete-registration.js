import validator from "../components/validator.js";
import "../../css/components/bulmaCalendar.scss";
import SlimSelect from 'slim-select';
import 'slim-select/src/slim-select/slimselect.scss';
import '../../css/components/slimSelect.scss';
import "../../css/pages/user/complete-registration.scss"; 


//
// Init form validation
//
// Validate register form
var registerConstraints = {
  inputDocument: {
    presence: true,
    presence:{
      message: "^Debe ingresar su documento"
    }
  },
  inputGiven: {
    presence: true,
    presence:{
      message: "^Debe ingresar su nombre"
    }
  },
  inputFamily: {
    presence: true,
    presence:{
      message: "^Debe ingresar su apellido"
    }
  },
  gender: {
    presence: true,
    presence:{
      message: "^Debe ingresar su género"
    }
  },
  inputBirthdate: {
    presence: true,
    presence:{
      message: "^Debe ingresar su fecha de nacimiento"
    }
  },
  password: {
    // Password is also required
    presence: true,
    presence:{
      message: "^Debe ingresar una contraseña"
    },
  }
}
//
// Init Register Form
//
var registerForm = document.querySelector("form#completeRegistration");
if (registerForm){
  validator.initialize(registerForm, registerConstraints, function() {
    var btnRegister = document.getElementById("btnRegister");
    btnRegister.classList.add("is-loading");
    registerForm.submit();
  });

  var contactCityId   = document.getElementById('inputHomeCityId');
  var contactCityName = document.getElementById('inputHomeCityName');
  var country = contactCityId.getAttribute('country');
  var contactSlimCity = null;
  var contactSlimCityTimer = null;
  function searchCity(search, callback){
    var url = '/api/searchCity/'+country+'/'+search;
    fetch(url).then(function (response) {
      return response.json()
    }).then(function (json) {
      let data = []
      var innerHTML = null;
      for (let i = 0; i < json.length; i++) {
        innerHTML = '<div class="ss-item"><div class="ss-item-title">'+json[i].name+'</div></div>';
        data.push({
          text      : json[i].name, 
          value     : json[i].id,
          innerHTML : innerHTML
        })
      }
      callback(data)
    })
    .catch(function(error) {
      callback(false)
    })
  }
  contactSlimCity = new SlimSelect({
    select           : '#contactSelCity',
    searchPlaceholder: 'Buscar',
    searchText       : 'Escriba al menos 3 caracteres',
    searchingText    : 'Buscando...',
    ajax             : function (search, callback) {
      if (search.length < 3) {
        callback('Escriba al menos 3 caracteres');
        return
      }
      if (contactSlimCityTimer) {
        clearTimeout(contactSlimCityTimer);
      }
      contactSlimCityTimer = setTimeout(function(){
        searchCity(search, callback);
      }, 500);
    },
    beforeOnChange   : (item) => {
      contactCityId.value = item.value;
      contactCityName.value = item.text;
    }
  });
}