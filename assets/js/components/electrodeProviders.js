
document.addEventListener('DOMContentLoaded', () => {
  // Hook Electrodes Providers modal
  var electrodesProvidersDetailsBtn = document.getElementById('electrodesProvidersDetailsBtn');
  var electrodesProvidersModal = document.getElementById('electrodesProvidersModal');
  var electrodesProvidersModalBack = document.getElementById('electrodesProvidersModalBack');
  var closeBtn = document.getElementById('electrodesProvidersModalCloseBtn');
  if (electrodesProvidersDetailsBtn){
    electrodesProvidersDetailsBtn.addEventListener("click", function(ev){
      ev.preventDefault();
      electrodesProvidersModal.classList.add("is-active");
    });
  }
  if (closeBtn){
    closeBtn.addEventListener("click", function(ev){
      ev.preventDefault();
      electrodesProvidersModal.classList.remove("is-active");
    });
  }
  if (electrodesProvidersModalBack){
    electrodesProvidersModalBack.addEventListener("click", function(ev){
      ev.preventDefault();
      electrodesProvidersModal.classList.remove("is-active");
    });
  }
});