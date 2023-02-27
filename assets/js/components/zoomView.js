import "../../css/components/zoomView.scss";

  
  var modZoomView = {};
  modZoomView .initialize = (viewerElId, btnElId) => {
    var vEl = document.getElementById(viewerElId);
    var btnElId = document.getElementById(btnElId);
    
    if (vEl){
      var video = vEl.querySelector('video');
      if (video){
        if (video.length){
          video = video[0];
        }
        video.addEventListener("click", function(ev){
          ev.stopPropagation();
        })
      }
      var backCntr = vEl.querySelector('.zv-modal-content');
      if (backCntr){
        if (backCntr.length){
          backCntr = backCntr[0];
        }
        backCntr.addEventListener("click", function(ev){
          ev.preventDefault();
          vEl.classList.remove("zv-modal-active");
          video.pause();
        });
      }
      var closeBtn = vEl.querySelector('.zv-close-button');
      if (closeBtn){
        if (closeBtn.length){
          closeBtn = closeBtn[0];
        }
        closeBtn.addEventListener("click", function(ev){
          ev.preventDefault();
          vEl.classList.remove("zv-modal-active");
          video.pause();
        });
      }
    }
    if (btnElId){
      btnElId.addEventListener("click", function(ev){
        ev.preventDefault();
        vEl.classList.add("zv-modal-active");
      });
    }
  }


export default modZoomView;