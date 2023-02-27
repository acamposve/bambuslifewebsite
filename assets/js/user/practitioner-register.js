import validator from "../components/validator.js";
import SlimSelect from 'slim-select';
import 'slim-select/src/slim-select/slimselect.scss';
import '../../css/components/slimSelect.scss';

//
// Init form validation
//
// Validate register form
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
  phone: {
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
  // specialities: {
  //   presence: true,
  //   presence:{
  //     message: "^Debe ingresar una especialidad"
  //   },
  // },
  organizationName: {
    presence: true,
    presence:{
      message: "^Debe ingresar el nombre de su organización"
    },
  },
  orgphone: {
    presence: true,
    presence:{
      message: "^Debe ingresar un número de teléfono de su organización"
    },
  },
  organizationAddress: {
    presence: true,
    presence:{
      message: "^Debe ingresar una dirección de su organización"
    },
  },
  locationName1: {
    presence: true,
    presence:{
      message: "^Debe ingresar un nombre de una localidad"
    },
  },
  locationAddress1: {
    presence: true,
    presence:{
      message: "^Debe ingresar una dirección de una localidad"
    },
  },
  selCity: {
    presence:{
      message: "^Debe ingresar una ciudad"
    },
  },
  country: {
    presence: true,
    presence:{
      message: "^Debe ingresar un país"
    }
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
//
// Init Register Form
//
var registerForm = document.querySelector("form#userInfoRegister");
validator.initialize(registerForm, registerConstraints, function() {
  var btnRegister = document.getElementById("btnRegister");
  btnRegister.classList.add("is-loading");
  registerForm.submit();
});

//
// Init Slim Select for specialities
//
new SlimSelect({
  select       : '#specialities',
  placeholder  : 'Selecione sus especialidades',
  showSearch   : false,
  closeOnSelect: false
})

//
// Init Slim Select for city select
//
var fCountry = document.getElementById('country');
var cityId = document.getElementById('cityId');
var cityName = document.getElementById('cityName');
var slimCity = null;
var slimCityTimer = null;
fCountry.addEventListener('change', function(ev){
  slimCity.setData([]);
  slimCity.set(null);
});

function searchCity(search, callback){
  var url = '/api/searchCity/'+fCountry.value+'/'+escape(search);
  fetch(url).then(function (response) {
    return response.json()
  }).then(function (json) {
    //slimCitySearching = false;
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
    slimCitySearching = false;
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
      console.log('cancel');
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