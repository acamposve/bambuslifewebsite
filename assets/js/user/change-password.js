// Import validator 
import validator from "../components/validator.js";

// Validate login form
var changePasswordConstraints = {
  actualPassword: {
    presence: true,
    presence:{
      message: "^Debe ingresar la contraseña actual"
    },
  },
  newPassword: {
    presence: true,
    presence:{
      message: "^Debe ingresar una nueva contraseña"
    },
  },
  newPasswordConfirm: {
    presence: true,
    presence:{
      message: "^Debe ingresar la nueva contraseña"
    },
  }
}
console.log(document);
document.addEventListener('DOMContentLoaded', () => {
  var changePasswordForm = document.querySelector("form#changePasswordForm");
  validator.initialize(changePasswordForm, changePasswordConstraints, () =>{
    var btnChange = document.getElementById("btnChange");
    var newPass = document.getElementById('newPassword').value;
    var newPassConfirm = document.getElementById('newPasswordConfirm').value;
    if(newPass == newPassConfirm){
      btnChange.classList.add("is-loading");
      changePasswordForm.submit();
    }else{
      document.getElementById('messageFinal').innerHTML = "La nueva contraseña debe ser la misma en los dos campos.";
    }
    
    
  });
});