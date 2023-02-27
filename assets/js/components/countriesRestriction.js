//
// Countries Restriction Modal
//
// Hook ECG Interpretation modal
var countriesTitle = document.getElementById('countriesTitle');
var countriesModal = document.getElementById('countriesModal');
var countriesModalBack = document.getElementById('countriesModalBack');
var closeBtn = document.getElementById('countriesModalCloseBtn');
if (countriesTitle){
  countriesTitle.addEventListener("click", function(ev){
    ev.preventDefault();
    countriesModal.classList.add("is-active");
  });
}
if (closeBtn){
  closeBtn.addEventListener("click", function(ev){
    ev.preventDefault();
    countriesModal.classList.remove("is-active");
  });
}
if (countriesModalBack){
  countriesModalBack.addEventListener("click", function(ev){
    ev.preventDefault();
    countriesModal.classList.remove("is-active");
  });
}