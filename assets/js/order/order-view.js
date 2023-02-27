

document.addEventListener('DOMContentLoaded', () => {
  var payMoreInfo = document.getElementById('payMoreInfo');
  if (payMoreInfo){
    var paymentModal = document.getElementById('paymentModal');
    var closeBtn = document.getElementById('paymentModalCloseBtn');
    var paymentModalBack = document.getElementById('paymentModalBack');
    payMoreInfo.addEventListener("click", function(ev){
      ev.preventDefault();
      paymentModal.classList.add("is-active");
    });
    closeBtn.addEventListener("click", function(ev){
      ev.preventDefault();
      paymentModal.classList.remove("is-active");
    });
    paymentModalBack.addEventListener("click", function(ev){
      ev.preventDefault();
      paymentModal.classList.remove("is-active");
    });
  }
});