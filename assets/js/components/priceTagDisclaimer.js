
document.addEventListener('DOMContentLoaded', () => {
  // Hook Price Tag Disclaimer modal
  var priceDisclaimerDetailsBtn = document.getElementById('priceDisclaimerDetailsBtn');
  var priceDisclaimerModal = document.getElementById('priceDisclaimerModal');
  var priceDisclaimerModalBack = document.getElementById('priceDisclaimerModalBack');
  var closeBtn = document.getElementById('priceDisclaimerModalCloseBtn');
  if (priceDisclaimerDetailsBtn){
    priceDisclaimerDetailsBtn.addEventListener("click", function(ev){
      ev.preventDefault();
      priceDisclaimerModal.classList.add("is-active");
    });
  }
  if (closeBtn){
    closeBtn.addEventListener("click", function(ev){
      ev.preventDefault();
      priceDisclaimerModal.classList.remove("is-active");
    });
  }
  if (priceDisclaimerModalBack){
    priceDisclaimerModalBack.addEventListener("click", function(ev){
      ev.preventDefault();
      priceDisclaimerModal.classList.remove("is-active");
    });
  }
});