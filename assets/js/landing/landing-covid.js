import "../../css/pages/landing/landing_covid.scss"; 
import bulmaAccordion from '../../../node_modules/bulma-accordion/dist/js/bulma-accordion.min.js';

var accordions = bulmaAccordion.attach();

var alemaniaExpanded = false;
var telemetriaExpanded = false;

var elementAlemania = document.getElementById("accordion-alemania");
var elementTelemetria = document.getElementById("accordion-telemetria");

elementAlemania.addEventListener('click', function() {
    if(!alemaniaExpanded) {
        elementAlemania.innerHTML = "Cerrar el artículo completo <span></span>";
        alemaniaExpanded = true;
    } else {
        elementAlemania.innerHTML = "Leer el artículo completo <span></span>";
        alemaniaExpanded = false;
    }
});

elementTelemetria.addEventListener('click', function() {
   if(!telemetriaExpanded) {
       elementTelemetria.innerHTML = "Cerrar ampliación <span></span>";
       telemetriaExpanded = true;
   } else {
       elementTelemetria.innerHTML = "Más información <span></span>";
       telemetriaExpanded = false;
   }
});