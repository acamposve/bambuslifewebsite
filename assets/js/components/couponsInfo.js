// DISCOUNTS MODALS
var modalDiscount          = document.getElementById('modalDiscount');
var modalDiscountBack      = document.getElementById('modalDiscountBack');
var modalDiscountCancel    = document.getElementById('modalDiscountCancel');
var btnDiscount            = document.getElementById('btnDiscount');
if (btnDiscount){
  btnDiscount.addEventListener("click", function(ev){
    ev.preventDefault();
    modalDiscount.classList.add("is-active");
  });
  modalDiscountBack.addEventListener("click", function(ev){
    ev.preventDefault();
    modalDiscount.classList.remove("is-active");
  });
  modalDiscountCancel.addEventListener("click", function(ev){
    ev.preventDefault();
    modalDiscount.classList.remove("is-active");
  });
}