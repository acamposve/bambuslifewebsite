require('../../css/pages/user/appointment.scss');


var cancelForm           = document.querySelector("form#formCancel");
var modalCancel          = document.getElementById('modalCancel');
if (modalCancel){
  var modalCancelBack      = document.getElementById('modalCancelBack');
  var modalCancelOK        = document.getElementById('modalCancelOK');
  var modalCancelCancel    = document.getElementById('modalCancelCancel');
  var btnDiscount          = document.getElementById('btnCancel');
  btnDiscount.addEventListener("click", function(ev){
    ev.preventDefault();
    modalCancel.classList.add("is-active");
  });
  modalCancelBack.addEventListener("click", function(ev){
    ev.preventDefault();
    modalCancel.classList.remove("is-active");
  });
  modalCancelCancel.addEventListener("click", function(ev){
    ev.preventDefault();
    modalCancel.classList.remove("is-active");
  });
  modalCancelOK.addEventListener("click", function(ev){
    ev.preventDefault();
    // Check valid data
    modalCancel.classList.remove("is-active");
    cancelForm.submit();
  });
}