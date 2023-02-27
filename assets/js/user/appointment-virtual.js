require('../../css/pages/user/appointment-virtual.scss');


var msgprompt = document.getElementById('msgprompt');
var msgdenied = document.getElementById('msgdenied');
var videoinit = document.getElementById('videoinit');
var videocntr = document.getElementById('videocntr');
var callend   = document.getElementById('callend');
var reEnter   = document.getElementById('reEnter');
var initiated = false;

setTimeout(() => {
  if (navigator.permissions){
    navigator.permissions.query(
      { name: 'camera' },
      { name: 'microphone' }
    ).then(function(permissionStatus){
      
      console.log(permissionStatus.state); // granted, denied, prompt

      evaluateMediaStatus(permissionStatus);
      permissionStatus.onchange = function(){
        evaluateMediaStatus(this);
      }

    });
  }else{
    requestMediaAccess();
  }
}, 2000);

function evaluateMediaStatus(permissionStatus){
  if (permissionStatus.state == 'granted'){
    // Init video
    msgprompt.style.display = 'none';
    msgdenied.style.display = 'none';
    videoinit.style.display = 'none';
    videocntr.style.display = 'flex';
    initVideoSDK();
  }else if (permissionStatus.state == 'denied'){
    // Show denied message
    msgprompt.style.display = 'none';
    msgdenied.style.display = 'inline-block';
    requestMediaAccess();
  }else if (permissionStatus.state == 'prompt'){
    // Show promt message
    msgprompt.style.display = 'inline-block';
    msgdenied.style.display = 'none';
    requestMediaAccess();
  }
}

function requestMediaAccess(){
  navigator.getUserMedia = navigator.getUserMedia || navigator.webkitGetUserMedia || navigator.mozGetUserMedia || navigator.msGetUserMedia;
  if (navigator.getUserMedia) {
      navigator.getUserMedia({ audio: true, video: true }, function (stream) {
        console.log('permited');
        
      }, function (e) {
          var message;
          switch (e.name) {
              case 'NotFoundError':
              case 'DevicesNotFoundError':
                  message = 'Please setup your webcam first.';
                  msgprompt.style.display = 'none';
                  msgdenied.style.display = 'inline-block';
                  msgdenied.children[0].children[1].innerHTML = 'No se encontró cámara o micrófono en este dispositivo';
                  break;
              case 'SourceUnavailableError':
                  message = 'Your webcam is busy';
                  msgprompt.style.display = 'none';
                  msgdenied.style.display = 'inline-block';
                  msgdenied.children[0].children[1].innerHTML = 'La cámara está en uso por otra aplicación';
                  break;
              case 'PermissionDeniedError':
              case 'SecurityError':
                  message = 'Permission denied!';
                  msgprompt.style.display = 'none';
                  msgdenied.style.display = 'inline-block';
                  msgdenied.children[0].children[1].innerHTML = 'Los permisos a la cámara y el micrófono no estan habilitados. <br>Por favor, habilite el acceso del navegador a la cámara y al micrófono para poder acceder a la cita.';
                  break;
              default: 
                console.log('Reeeejected!', e);
                  return;
          }
          console.log(message);
      });
  } else {
    msgprompt.style.display = 'none';
    msgdenied.style.display = 'inline-block';
    msgdenied.children[0].children[1].innerHTML = 'El navegador no es compatible. Por favor acceda a este enlace desde otro navegador que permita activar la cámara y el micrófono.';
    console.log('Uncompatible browser!');
  }
}

function initVideoSDK(){
  var partTokenEl = document.getElementById('videoinit');
  if (partTokenEl){
    var partToken = partTokenEl.getAttribute('data-token');
    var vrPublicUrl = partTokenEl.getAttribute('data-publicurl');
    if (!initiated){
      initiated = true;
      var opts = {
        server                  : vrPublicUrl,
        layout                  : '101',
        showLayoutToggle        : false,
        showShare               : false,
        showCallInfoPanel       : false,
        showParticipantsPanel   : false,
        autoHideControlsBar     : false,
        transparentFooter       : false,
        showFirstParticipantMsg : false,
        showSetNickname         : false
      };
      // if (process.env.NODE_ENV == 'development'){
      //   opts.server = 'http://localhost:8888';
      // }else{
      //   opts.server = vrPublicUrl;
      // }
      VideoSDK.init('videocntr', opts);
      VideoSDK.events.on("roomLeft", function(){
        videocntr.style.display = 'none';
        callend.style.display = 'block';
      });
    }
    videocntr.style.display = 'flex';
    callend.style.display = 'none';
    console.log('Joining ' + partToken + ' under ' + process.env.NODE_ENV + ' env');
    VideoSDK.joinRoom(partToken);
  }
}

reEnter.addEventListener('click', function(ev){
  ev.preventDefault();
  ev.stopPropagation();
  initVideoSDK();
})