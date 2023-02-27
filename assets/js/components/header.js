
const initializeHeader = () => {
  document.addEventListener('DOMContentLoaded', () => {

    // Get all "navbar-burger" elements
    const $navbarBurgers = Array.prototype.slice.call(document.querySelectorAll('.navbar-burger'), 0);
  
    // Check if there are any navbar burgers
    if ($navbarBurgers.length > 0) {
  
      // Add a click event on each of them
      $navbarBurgers.forEach( el => {
        el.addEventListener('click', () => {
  
          // Get the target from the "data-target" attribute
          const target = el.dataset.target;
          const $target = document.getElementById(target);
  
          // Toggle the "is-active" class on both the "navbar-burger" and the "navbar-menu"
          el.classList.toggle('is-active');
          $target.classList.toggle('is-active');
  
        });
      });
    }

    var navBar = document.getElementById('navbar');
    window.onscroll = function () { 
        "use strict";
        // if (document.body.scrollTop >= 200 ) {
        if (window.scrollY <= 50) {
          //navBar.classList.add("nav-colored");
          //navBar.classList.remove("nav-transparent");
          navBar.classList.remove("nav-colored");
        } else {
          //navBar.classList.add("nav-transparent");
          //navBar.classList.remove("nav-colored");
          navBar.classList.add("nav-colored");
        }
    };
  
  });
}

export default initializeHeader;