import "../../css/pages/checkout/checkout.scss"; 

// Import validator 
import validator from "../components/validator.js";

// Validate login form
var loginConstraints = {
  // firstname: {
  //   presence: true,
  //   presence:{
  //     message: "^Debe ingresar un nombre"
  //   },
  // },
  // lastname: {
  //   presence: true,
  //   presence:{
  //     message: "^Debe ingresar un apellido"
  //   },
  // },
  // email: {
  //   presence: true,
  //   email: true,
  //   presence:{
  //     message: "^Debe ingresar un correo electronico"
  //   },
  //   email:{
  //     message: "^El formato del correo electronico no es valido"
  //   }
  // },
  // phonenumber: {
  //   presence: true,
  //   presence:{
  //     message: "^Debe ingresar un telefono"
  //   },
  // },
  // birthdate: {
  //   presence: true,
  //   presence:{
  //     message: "^Debe ingresar una fecha"
  //   },
  // },
  // weight: {
  //   presence: true,
  //   presence:{
  //     message: "^Debe ingresar un peso"
  //   },
  // },
  // height: {
  //   presence: true,
  //   presence:{
  //     message: "^Debe ingresar una altura"
  //   },
  // }
}

document.addEventListener('DOMContentLoaded', () => {
  var loginForm = document.querySelector("form#userInfoLogin");
  validator.initialize(loginForm, loginConstraints, () =>{
    var btnLogin = document.getElementById("btnContinue");
    btnLogin.classList.add("is-loading");
    loginForm.submit();
  });

  var file = document.getElementsByClassName("file-input")[0];
  file.onchange = function(){
    if (file.files.length > 0) {
      document.getElementsByClassName('file-name')[0].innerHTML = file.files[0].name;
    }
  };
});


