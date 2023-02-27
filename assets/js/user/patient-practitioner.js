

var selectedAssignBtn = null;

// Hook Confirmation modal
var confirmModal          = document.getElementById('confirmModal');
var confirmModalBack      = document.getElementById('confirmModalBack');
var confirmModalOK        = document.getElementById('confirmModalOK');
var confirmModalCancel    = document.getElementById('confirmModalCancel');
var confirmModalPractName = document.getElementById('confirmModalPractName');

function openConfirmation(ev){
  ev.preventDefault();
  confirmModal.classList.add("is-active");
};
confirmModalBack.addEventListener("click", function(ev){
  ev.preventDefault();
  confirmModal.classList.remove("is-active");
});
confirmModalCancel.addEventListener("click", function(ev){
  ev.preventDefault();
  confirmModal.classList.remove("is-active");
});
confirmModalOK.addEventListener("click", function(ev){
  ev.preventDefault();
  confirmModal.classList.remove("is-active");
  // SEND COMMAND
  window.location = selectedAssignBtn;
});

var assignBtns = document.getElementsByClassName('assignPractitioner');
for( var btn of assignBtns){
  btn.addEventListener("click", function(ev){
    ev.preventDefault();
    selectedAssignBtn = ev.currentTarget.getAttribute('href');
    var serviceElId = ev.currentTarget.getAttribute('data-index');
    var serviceEl = document.getElementById('serviceName-'+serviceElId);
    confirmModalPractName.innerText = serviceEl.innerText;
    openConfirmation(ev);
  });
};