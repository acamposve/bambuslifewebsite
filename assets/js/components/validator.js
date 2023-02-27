var validate = require("validate.js");
//require('../../css/components/validator.scss');
import "../../css/components/validator.scss";


function handleFormSubmit(form, constraints, ev, successCb) {
  // validate the form aainst the constraints
  var errors = validate(form, constraints);
  // then we update the form to reflect the results
  showErrors(form, errors || {});
  if (!errors) {
    successCb(ev);
  }
}

function handleFormSubmitWithEv(form, constraints, ev, successCb) {
  // validate the form aainst the constraints
  var errors = validate(form, constraints);
  // then we update the form to reflect the results
  showErrors(form, errors || {});
  if (!errors) {
    successCb(ev);
  }
}

// Updates the inputs with the validation errors
function showErrors(form, errors) {
    // We loop through all the inputs and show the errors for that input
    form.querySelectorAll("input[name], select[name], textarea[name]").forEach(input => {
        // Since the errors can be null if no errors were found we need to handle
        // that
        showErrorsForInput(input, errors && errors[input.name]);
    });
}

// Shows the errors for a specific input
function showErrorsForInput(input, errors) {
    // This is the root of the input
    var formGroup = closestParent(input.parentNode, "field");
    if (!formGroup) return;
    // Find where the error messages will be insert into
    var messages = formGroup.querySelector(".messages");
    // First we remove any old messages and resets the classes

    resetFormGroup(formGroup);

    // If we have errors
    if (errors) {
        // we first mark the group has having errors
        formGroup.classList.add("has-error");
        input.classList.add("is-danger");

        // then we append all the errors
        errors.forEach(error => {
            addError(messages, error);
        });
    } else {
        // otherwise we simply mark it as success
        formGroup.classList.add("has-success");
    }
}

// Recusively finds the closest parent that has the specified class
function closestParent(child, className) {
    if (!child || child == document) {
        return null;
    }
    if (child.classList.contains(className)) {
        return child;
    } else {
        return closestParent(child.parentNode, className);
    }
}

function resetFormGroup(formGroup) {
    // Remove the success and error classes
    formGroup.classList.remove("has-error");
    formGroup.classList.remove("has-success");

    // and remove any old messages
    formGroup.querySelectorAll(".is-danger").forEach(el => {
        el.classList.remove('is-danger');
        el.innerHTML = '';
    });
}

// Adds the specified error with the following markup
// <p class="help-block error">[message]</p>
function addError(messages, error) {
    var block = document.createElement("p");
    block.classList.add("help", "is-danger");
    block.innerText = error;
    if (messages) {
        messages.appendChild(block);
    }
}

const validator = {
    initialize(form, constraints, successCb) {
        // Remove browser validation bubble
        for (var f = document.forms, i = f.length; i--;) f[i].setAttribute("novalidate", i)

        // Hook up the form so we can prevent it from being posted
        form.addEventListener("submit", function (ev) {
            ev.preventDefault();
            handleFormSubmit(form, constraints, ev, successCb);
        });

        // Hook up the inputs to validate on the fly
        var inputs = form.querySelectorAll("input, textarea, select")
        for (var i = 0; i < inputs.length; ++i) {
            inputs.item(i).addEventListener("change", function (ev) {
                var errors = validate(form, constraints) || {};
                showErrorsForInput(this, errors[this.name])
            });
        }
    },
    initializeConditional(form, constraints, mustValidateCb, successCb) {
        // Remove browser validation bubble
        for (var f = document.forms, i = f.length; i--;) f[i].setAttribute("novalidate", i)

        // Hook up the form so we can prevent it from being posted
        form.addEventListener("submit", function (ev) {
            if (mustValidateCb()) {
                ev.preventDefault();
                handleFormSubmitWithEv(form, constraints, ev, successCb);
            } else {
                successCb(ev);
            }
        });

        // Hook up the inputs to validate on the fly
        var inputs = form.querySelectorAll("input, textarea, select")
        for (var i = 0; i < inputs.length; ++i) {
            inputs.item(i).addEventListener("change", function (ev) {
                var errors = validate(form, constraints) || {};
                showErrorsForInput(this, errors[this.name])
            });
        }
    }
}

export default validator;