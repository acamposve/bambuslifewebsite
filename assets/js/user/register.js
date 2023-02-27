import "../../css/pages/user/register.scss"; 

var postlogin = document.getElementById('postlogin');
var loginComp = require("../components/login.js").default;
if (loginComp){
  loginComp.initialize(function(logged){
    var redirectUrl = postlogin.getAttribute('data-url');
    window.location = redirectUrl;
    //console.log('LOGGED=>'+logged);
  }, true);
}
