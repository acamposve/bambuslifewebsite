

document.addEventListener('DOMContentLoaded', () => {
    
  var btns = document.querySelectorAll('*[id^="btnModalPatient_"]');
  btns.forEach( (btn) => {
    btn.modal = document.getElementById('modalPatient_' + btn.getAttribute('data-idx'));
    btn.modalClose = document.getElementById('closeModalPatient_' + btn.getAttribute('data-idx'));
    btn.addEventListener("click", function(ev){
      ev.preventDefault();
      btn.modal.classList.add("is-active");
    });
    btn.modalClose.addEventListener("click", function(ev){
      ev.preventDefault();
      btn.modal.classList.remove("is-active");
    });
  });
});