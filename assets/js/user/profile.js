import "../../css/pages/user/profile.scss"; 
import validator from "../components/validator.js";

// Hook ECG Interpretation modal
var assignedUserTitleArray = document.getElementsByClassName('assignedUserTitle');
var assignedUser = document.getElementById('assignedUser');
var assignedUserBack = document.getElementById('assignedUserBack');
var closeBtn = document.getElementById('assignedUserCloseBtn');
for(var i=0 ; i<assignedUserTitleArray.length ; i++) {
  assignedUserTitleArray[i].addEventListener("click", function(ev){
    ev.preventDefault();
    assignedUser.classList.add("is-active");
  });
}
closeBtn.addEventListener("click", function(ev){
  ev.preventDefault();
  assignedUser.classList.remove("is-active");
});
assignedUserBack.addEventListener("click", function(ev){
  ev.preventDefault();
  assignedUser.classList.remove("is-active");
});


// Hook practitioner removal
var formRemPract = document.getElementById('remPract');
var modalRemPract = document.getElementById('confirmRemPract');
var modalRemPractBack = document.getElementById('confirmRemPractBack');
var modalRemPractOK = document.getElementById('confirmRemPractOK');
var modalRemPractCancel = document.getElementById('confirmRemPractCancel');
if (formRemPract){
  formRemPract.addEventListener('submit', function(ev){
    ev.preventDefault();
    modalRemPract.classList.add("is-active");
  });
  modalRemPractBack.addEventListener("click", function(ev){
    ev.preventDefault();
    modalRemPract.classList.remove("is-active");
  });
  modalRemPractCancel.addEventListener("click", function(ev){
    ev.preventDefault();
    modalRemPract.classList.remove("is-active");
  });
  modalRemPractOK.addEventListener("click", function(ev){
    ev.preventDefault();
    modalRemPract.classList.remove("is-active");
    formRemPract.submit();
  });
}


// Hook family member invitation
var formInviteMember         = document.getElementById('inviteMemberForm');
var inviteBtn                = document.getElementById('inviteBtn');
var modalInviteMember        = document.getElementById('inviteFamilyMember');
var inviteFamilyMemberBack   = document.getElementById('inviteFamilyMemberBack');
var inviteFamilyMemberOK     = document.getElementById('inviteFamilyMemberOK');
var inviteFamilyMemberCancel = document.getElementById('inviteFamilyMemberCancel');
if (formInviteMember){
  var inviteMemberFormConstraints = {
    recipientName: {
      presence: true,
      presence:{
        message: "^Debe ingresar un nombre"
      }
    },
    recipientEmail: {
      presence: true,
      presence:{
        message: "^Debe ingresar un mail"
      }
    },
    // recipientEmail: {
    //   presence: true,
    //   email: true,
    //   presence:{
    //     message: "^Debe ingresar un correo electronico"
    //   },
    //   email:{
    //     message: "^El formato del correo electronico no es valido"
    //   }
    // }
  }
  var inviteMemberForm = document.querySelector("form#inviteMemberForm");
  validator.initialize(inviteMemberForm, inviteMemberFormConstraints, function() {
    inviteFamilyMemberOK.classList.add("is-loading");
    inviteMemberForm.submit();
  });
  if (inviteBtn){
    inviteBtn.addEventListener('click', function(ev){
      var url = '/api/getFamilyPlanData';
      fetch(url).then(function (response) {
        return response.json();
      }).then(function (jsonPlanData) {
        var familyPlanPrice = document.getElementById('familyMemberPlanPrice');
        if (familyPlanPrice){
          familyPlanPrice.innerHTML = jsonPlanData.currency + ' ' + jsonPlanData.price;
          modalInviteMember.classList.add("is-active");
        }
      })
      .catch(function(error) {
        console.log('error getting prices');
      })
    });
    inviteFamilyMemberBack.addEventListener("click", function(ev){
      ev.preventDefault();
      modalInviteMember.classList.remove("is-active");
    });
    inviteFamilyMemberCancel.addEventListener("click", function(ev){
      ev.preventDefault();
      modalInviteMember.classList.remove("is-active");
    });
  }

  //
  // Hook remove familiy plan
  //
  var removeBtns               = document.getElementsByClassName('removeMemberBtns');
  var modalRemoveMember        = document.getElementById('removeFamilyMember');
  var removeFamilyMemberOK     = document.getElementById('removeFamilyMemberOK');
  var removeFamilyMemberBack   = document.getElementById('removeFamilyMemberBack');
  var removeFamilyMemberCancel = document.getElementById('removeFamilyMemberCancel');
  if (removeBtns && removeBtns.length){
    for( var remBtn of removeBtns){
      remBtn.addEventListener('click', function(ev){
        var currTarget = ev.currentTarget;
        var url = '/api/getFamilyPlanData';
        fetch(url).then(function (response) {
          return response.json();
        }).then(function (jsonPlanData) {
          var familyPlanPrice = document.getElementById('familyMemberRemPlanPrice');
          if (familyPlanPrice){
            familyPlanPrice.innerHTML = jsonPlanData.currency + ' ' + jsonPlanData.price;
          }
          var pId = currTarget.getAttribute('data-pid');
          var dId = currTarget.getAttribute('data-did');
          var pGiven = currTarget.getAttribute('data-pnam');
          document.getElementById('remPatName').innerHTML = pGiven;
          removeFamilyMemberOK.setAttribute('data-pid', pId);
          removeFamilyMemberOK.setAttribute('data-did', dId);
          modalRemoveMember.classList.add("is-active");
        })
        .catch(function(error) {
          console.log('error getting prices');
        })
      });
    };

    removeFamilyMemberBack.addEventListener("click", function(ev){
      ev.preventDefault();
      modalRemoveMember.classList.remove("is-active");
    });
    removeFamilyMemberCancel.addEventListener("click", function(ev){
      ev.preventDefault();
      modalRemoveMember.classList.remove("is-active");
    });
    removeFamilyMemberOK.addEventListener('click', function(ev){
      var pId = ev.currentTarget.getAttribute('data-pid');
      var dId = ev.currentTarget.getAttribute('data-did');
      removeFamilyMemberOK.classList.add("is-loading");
      window.location = 'remMem/'+pId+'/'+dId;
    });
  }
}