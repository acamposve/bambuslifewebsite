import "../../css/components/bulmaCalendar.scss";
import SlimSelect from 'slim-select';
import 'slim-select/src/slim-select/slimselect.scss';
import '../../css/components/slimSelect.scss';
import "../../css/pages/user/profile-clinic.scss"; 

//
// Hook form to detect changes
//
var form = document.getElementById('clinicalData');
if (form){
  form.addEventListener('submit', function(ev){
    ev.preventDefault();
    
    var els = form.querySelectorAll('input,select');
    var currValue = null;
    var prevValue = null;
    els.forEach(function(el){
      prevValue = el.getAttribute('prevvalue');
      
      if (el.tagName == 'INPUT'){
        currValue = el.value;
      }else if (el.tagName == 'SELECT'){
        currValue = el.options[el.selectedIndex].value;
      }else{
        // Not supported
        return;
      }

      if (prevValue == currValue){
        // remove change needed
        var currName = el.getAttribute("name");
        if (currName && currName.substr(0,1) == "$"){
          el.setAttribute("name",currName.substr(1));
        }
      }else{
        // Add change needed
        var currName = el.getAttribute("name");
        if (currName && currName.substr(0,1) != "$"){
          el.setAttribute("name","$"+currName);
        }
      }
    });

    // els.forEach(function(el){
    //   console.log('FIELD ' + el.getAttribute('name') + ' PrevValue: ' + el.getAttribute('prevvalue') + ' NewValue:' + el.value);
    // });
    form.submit();
  });
}


//
// Contacts handlers
//
var btnAddContact      = document.getElementById('btnAddContact');
var modalContact       = document.getElementById('modalContact');
var modalContactBack   = document.getElementById('modalContactBack');
var modalContactOK     = document.getElementById('modalContactOK');
var modalContactCancel = document.getElementById('modalContactCancel');
btnAddContact.addEventListener('click', function(ev){
  ev.preventDefault();
  modalContact.classList.add("is-active");
});
modalContactBack.addEventListener('click', function(ev){
  ev.preventDefault();
  modalContact.classList.remove("is-active");
});
modalContactCancel.addEventListener('click', function(ev){
  ev.preventDefault();
  modalContact.classList.remove("is-active");
});
var contactCountry = document.getElementById('contactCountry');
var contactCityId = document.getElementById('contactCityId');
var contactCityName = document.getElementById('contactCityName');
var contactSlimCity = null;
var contactSlimCityTimer = null;
contactCountry.addEventListener('change', function(ev){
  contactSlimCity.setData([]);
  contactSlimCity.set(null);
});
function searchCity(search, callback){
  var url = '/api/searchCity/'+contactCountry.value+'/'+search;
  fetch(url).then(function (response) {
    return response.json()
  }).then(function (json) {
    let data = []
    var innerHTML = null;
    for (let i = 0; i < json.length; i++) {
      innerHTML = '<div class="ss-item"><div class="ss-item-title">'+json[i].name+'</div><div class="ss-item-subtitle">'+json[i].admin1Name;
      if (json[i].admin2Name){
        innerHTML += ', '+json[i].admin2Name;
      }
      innerHTML += '</div></div>';
      data.push({
        text      : json[i].name, 
        value     : json[i].id,
        innerHTML : innerHTML
      })
    }
    callback(data)
  })
  .catch(function(error) {
    callback(false)
  })
}
contactSlimCity = new SlimSelect({
  select           : '#contactSelCity',
  searchPlaceholder: 'Buscar',
  searchText       : 'Escriba al menos 3 caracteres',
  searchingText    : 'Buscando...',
  ajax             : function (search, callback) {
    if (search.length < 3) {
      callback('Escriba al menos 3 caracteres');
      return
    }
    if (contactSlimCityTimer) {
      clearTimeout(contactSlimCityTimer);
    }
    contactSlimCityTimer = setTimeout(function(){
      searchCity(search, callback);
    }, 500);
  },
  beforeOnChange   : (item) => {
    contactCityId.value = item.value;
    contactCityName.value = item.text;
  }
});
var contactList      = document.getElementById('contactList');
var contactsTotal    = document.getElementById('contactsTotal');
var contactListEmpty = document.getElementById('contactListEmpty');
modalContactOK.addEventListener('click', function(ev){
  ev.preventDefault();
  contactsTotal.value = parseInt(contactsTotal.value)+1;
  var elContactRelation = document.getElementById('contactRelation');
  var elContactTelecomUse = document.getElementById('contactTelecomUse');
  // Create box
  var newContactEl = document.createElement('div');
  newContactEl.setAttribute('id', 'contact_'+contactsTotal.value);
  newContactEl.classList.add("cliperf-box");
  newContactEl.innerHTML=`
  <h4>${document.getElementById('contactName').value}</h4>
  <p>${elContactRelation.options[elContactRelation.selectedIndex].innerText}</p>
  <p>Tel: ${document.getElementById('contactPhone').value} (${elContactTelecomUse.options[elContactTelecomUse.selectedIndex].innerText})</p>
  <div>
    <button class="button is-text" type="button" onclick="removeContact(${contactsTotal.value})">Quitar</button>
  </div>
  <input type="hidden" id="con_${contactsTotal.value}_id" name="con_${contactsTotal.value}_id" value="-1" prevvalue="">
  <input type="hidden" id="con_${contactsTotal.value}_contactRelation" name="con_${contactsTotal.value}_contactRelation" value="${document.getElementById('contactRelation').value}" prevvalue="">
  <input type="hidden" id="con_${contactsTotal.value}_contactName" name="con_${contactsTotal.value}_contactName" value="${document.getElementById('contactName').value}" prevvalue="">
  <input type="hidden" id="con_${contactsTotal.value}_contactAddressUse" name="con_${contactsTotal.value}_contactAddressUse" value="${document.getElementById('contactAddressUse').value}" prevvalue="">
  <input type="hidden" id="con_${contactsTotal.value}_contactAddress" name="con_${contactsTotal.value}_contactAddress" value="${document.getElementById('contactAddress').value}" prevvalue="">
  <input type="hidden" id="con_${contactsTotal.value}_contactCountry" name="con_${contactsTotal.value}_contactCountry" value="${document.getElementById('contactCountry').value}" prevvalue="">
  <input type="hidden" id="con_${contactsTotal.value}_contactCityId" name="con_${contactsTotal.value}_contactCityId" value="${document.getElementById('contactCityId').value}" prevvalue="">
  <input type="hidden" id="con_${contactsTotal.value}_contactCityName" name="con_${contactsTotal.value}_contactCityName" value="${document.getElementById('contactCityName').value}" prevvalue="">
  <input type="hidden" id="con_${contactsTotal.value}_contactTelecomUse" name="con_${contactsTotal.value}_contactTelecomUse" value="${document.getElementById('contactTelecomUse').value}" prevvalue="">
  <input type="hidden" id="con_${contactsTotal.value}_contactPhone" name="con_${contactsTotal.value}_contactPhone" value="${document.getElementById('contactPhone').value}" prevvalue="">
  <input type="hidden" id="con_${contactsTotal.value}_contactEmail" name="con_${contactsTotal.value}_contactEmail" value="${document.getElementById('contactEmail').value}" prevvalue="">
  </div>
  `;  
  contactList.appendChild(newContactEl);
  if (contactsTotal.value >= 1){
    contactListEmpty.style.display = 'none';
  }else{
    contactListEmpty.style.display = 'block';
  }
  modalContact.classList.remove("is-active");
});
window.removeContact = function(index){
  var el = document.getElementById('contact_'+index);
  var elId = document.getElementById('con_'+index+'_id');
  if (elId.value > 0){
    // Add delete request
    var input = document.createElement("input");
    input.type = "hidden";
    input.name = "delcon_" + index;
    input.value = elId.value;
    form.appendChild(input);
  }
  contactList.removeChild(el);
}