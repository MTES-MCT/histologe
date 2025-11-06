const refusAffectationButtons = document.querySelectorAll(
  'button[name="signalement-affectation-response[deny]"]'
);
refusAffectationButtons.forEach((button) => {
  button.addEventListener('click', function (event) {
    if (!confirm('Êtes-vous certain de vouloir refuser ce signalement ?')) {
      event.preventDefault();
    }
  });
});
const refusValidationButtons = document.querySelectorAll(
  'button[name="signalement-validation-response[deny]"]'
);
refusValidationButtons.forEach((button) => {
  button.addEventListener('click', function (event) {
    if (!confirm('Êtes-vous certain de vouloir refuser ce signalement ?')) {
      event.preventDefault();
    }
  });
});
const cancelRefusButton = document.querySelectorAll(
  'button[name="signalement-affectation-response[accept]]"]'
);
cancelRefusButton.forEach((button) => {
  button.addEventListener('click', function (event) {
    if (!confirm('Êtes-vous certain de vouloir accepter ce signalement ?')) {
      event.preventDefault();
    }
  });
});
const validationButton = document.querySelectorAll(
  'button[name="signalement-validation-response[accept]"]'
);
validationButton.forEach((button) => {
  button.addEventListener('click', function (event) {
    if (!confirm('Êtes-vous certain de vouloir valider ce signalement ?')) {
      event.preventDefault();
    }
  });
});

document.querySelectorAll('[data-select-all-agents]').forEach((link) => {
  link.addEventListener('click', (e) => {
    e.preventDefault();

    const form = link.closest('form');
    if (!form) return;

    /** @type {NodeListOf<HTMLInputElement>} */
    const checkboxes = form.querySelectorAll('input[type="checkbox"]');
    const allChecked = Array.from(checkboxes).every((cb) => cb.checked);

    checkboxes.forEach((cb) => {
      cb.checked = !allChecked;
    });

    link.textContent = allChecked ? 'Tout sélectionner' : 'Tout désélectionner';
  });
});
