
// Import validator 
import validator from "../components/validator.js";

// Validate login form
var loginConstraints = {
  name: {
    presence: true,
    presence:{
      message: "^Debe ingresar un nombre"
    },
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
  phone: {
    presence: true,
    presence:{
      message: "^Debe ingresar un telefono"
    },
  },
  message: {
    presence: true,
    presence:{
      message: "^Debe ingresar un mensaje"
    },
  }
}

document.addEventListener('DOMContentLoaded', () => {
  var contactForm = document.querySelector("form#contactForm");
  validator.initialize(contactForm, loginConstraints, () =>{
    var btnSend = document.getElementById("btnSend");
    btnSend.classList.add("is-loading");
    contactForm.submit();
  });
});