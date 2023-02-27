
// Import validator 
import validator from "../components/validator.js";

// Validate login form
var constraints = {
  pass: {
      presence: true,
      presence:{
        message: "^Debe ingresar una contraseña"
      }
    },
  confirmPassword: {
      presence: true,
      presence:{
        message: "^Debe confirmar la contraseña"
      },
      equality: {
        attribute: "pass",
        message: "Las contraseñas deben coincidir",
        comparator: (v1, v2) => {
          return v1 === v2;
        }
      }
    },
}

document.addEventListener('DOMContentLoaded', () => {
  var form = document.querySelector("form#resetPassword");
  validator.initialize(form, constraints, () =>{
    var btn = document.getElementById("btnContinue");
    btn.classList.add("is-loading");
    form.submit();
  });

});


