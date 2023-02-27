
// Import validator 
import validator from "../components/validator.js";

// Validate login form
var orgContactConstraints = {
  orgname: {
    presence: true,
    presence:{
      message: "^Debe ingresar el nombre de su organización"
    },
  },
  country: {
    presence: true,
    presence:{
      message: "^Debe ingresar el país de la organización"
    },
  },
  contactName: {
    presence: true,
    presence:{
      message: "^Debe ingresar un nombre de contacto"
    },
  },
  contactEmail: {
    presence: true,
    email: true,
    presence:{
      message: "^Debe ingresar un correo electrónico de contacto"
    },
    email:{
      message: "^El formato del correo electrónico no es válido"
    }
  },
  messageText: {
    presence: true,
    presence: {
      message: "^Debe ingresar un mensaje para enviar"
    }
  }
}

document.addEventListener('DOMContentLoaded', () => {
  var orgContactForm = document.querySelector("form#orgContactForm");
  if (orgContactForm){
    validator.initialize(orgContactForm, orgContactConstraints, () =>{
      var btnSend = document.getElementById("btnSend");
      btnSend.classList.add("is-loading");
      orgContactForm.submit();
    });
  }
});