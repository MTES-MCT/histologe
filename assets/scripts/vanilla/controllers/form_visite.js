/* global tinymce */

const timezoneElement = document.querySelector('[data-territory-timezone]');
const timezone = timezoneElement?.dataset.territoryTimezone;
const todayDate = new Date();
const options = { timeZone: timezone, year: 'numeric', month: '2-digit', day: '2-digit' };
const formatter = new Intl.DateTimeFormat('en-CA', options);
const localDateString = formatter.format(todayDate);

document.addEventListener('change', (event) => {
  console.log('Change event on', event.target);
  if (event.target.classList.contains('add-fields-if-past-date')) {
    const dateField = event.target;
    const fieldToDisplay = dateField.dataset.displayfields;
    const fieldToHide = dateField.dataset.hidefields;

    if (dateField.value && dateField.value <= localDateString) {
      document.querySelector('#' + fieldToDisplay).classList.remove('fr-hidden');
      document.querySelector('#' + fieldToHide).classList.add('fr-hidden');
    } else {
      document.querySelector('#' + fieldToDisplay).classList.add('fr-hidden');
      document.querySelector('#' + fieldToHide).classList.remove('fr-hidden');
    }
    return;
  }

  if (event.target.classList.contains('visite-partner-select')) {
    const partnerSelect = event.target;
    const operatorExtern = partnerSelect.parentElement.nextElementSibling;
    const operatorExternField = operatorExtern.querySelector('input');

    if (partnerSelect.value === 'extern') {
      operatorExtern.classList.remove('fr-hidden');
      operatorExternField.setAttribute('required', 'required');
    } else {
      operatorExtern.classList.add('fr-hidden');
      operatorExternField.removeAttribute('required');
    }
    return;
  }

  const checkField = event.target.closest('input[name^="visite-"][name$="[visiteDone]"]');
  if (!checkField) {
    return;
  }

  const visiteForm = checkField.closest('form');
  const fieldsetConcludeProcedure = visiteForm.querySelector('#fieldset-conclude-procedure');
  const isVisiteDone = checkField.value === '1';

  if (isVisiteDone) {
    fieldsetConcludeProcedure.classList.remove('fr-hidden');
  } else {
    fieldsetConcludeProcedure.classList.add('fr-hidden');
    const selectConcludeProcedure = visiteForm.querySelector('select[name$="[concludeProcedure]"]');
    if (selectConcludeProcedure) {
      selectConcludeProcedure.value = '';
    }
  }
});
document.querySelectorAll('.add-fields-if-past-date').forEach((dateField) => {
  dateField.dispatchEvent(new Event('change'));
});
document.querySelectorAll('.visite-partner-select').forEach((partnerSelect) => {
  partnerSelect.dispatchEvent(new Event('change'));
});

export function histoCheckVisiteForms(formType, visiteForm) {
  console.log('[histoCheckVisiteForms] Checking form type:', formType, visiteForm);
  if (!visiteForm) {
    console.warn('[histoCheckVisiteForms] form not provided for', formType);
    return true;
  }

  let isValid = true;

  const listInputVisiteDoneError = visiteForm.querySelector(
    `#signalement-${formType}-visite-done-error`
  );
  const listInputOccupantPresentError = visiteForm.querySelector(
    `#signalement-${formType}-visite-occupant-present-error`
  );
  const listInputProprietairePresentError = visiteForm.querySelector(
    `#signalement-${formType}-visite-proprietaire-present-error`
  );
  const selectConcludeProcedureError = visiteForm.querySelector(
    `#signalement-${formType}-visite-procedure-error`
  );
  const textareaDetailsError = visiteForm.querySelector(
    `#signalement-${formType}-visite-details-error`
  );

  [
    listInputVisiteDoneError,
    listInputOccupantPresentError,
    listInputProprietairePresentError,
    selectConcludeProcedureError,
    textareaDetailsError,
  ].forEach((el) => el?.classList.add('fr-hidden'));

  /* ---------- PARTENAIRE ---------- */

  const selectVisitePartner = visiteForm.querySelector('.visite-partner-select');
  if (selectVisitePartner) {
    const selectVisitePartnerError = visiteForm.querySelector(
      '#signalement-' + formType + '-visite-partner-double-error'
    );
    selectVisitePartnerError?.classList.add('fr-hidden');

    if (selectVisitePartner.value === 'extern') {
      const operatorExternField = visiteForm.querySelector('.visite-external-operator');
      const operatorNames = JSON.parse(
        document.getElementById('list-pending-visite-external-operator-names').dataset.list
      );

      if (operatorNames.includes(operatorExternField.value) && formType !== 'reschedule') {
        selectVisitePartnerError?.classList.remove('fr-hidden');
        isValid = false;
      }
    } else if (selectVisitePartner.selectedOptions[0].classList.contains('alert-partner')) {
      selectVisitePartnerError?.classList.remove('fr-hidden');
      isValid = false;
    }
  }

  /* ---------- DATE / VISITE ---------- */

  const dateField = visiteForm.querySelector('.add-fields-if-past-date');
  if (!dateField || dateField.value <= localDateString) {
    let isVisiteDone = false;
    let hasCheckedVisiteDone = false;

    visiteForm.querySelectorAll(`input[name="visite-${formType}[visiteDone]"]`).forEach((input) => {
      if (input.checked) {
        hasCheckedVisiteDone = true;
        if (input.value === '1') {
          isVisiteDone = true;
        }
      }
    });

    if (!hasCheckedVisiteDone) {
      listInputVisiteDoneError?.classList.remove('fr-hidden');
      isValid = false;
    }

    if (!visiteForm.querySelector(`input[name="visite-${formType}[occupantPresent]"]:checked`)) {
      listInputOccupantPresentError?.classList.remove('fr-hidden');
      isValid = false;
    }

    if (
      !visiteForm.querySelector(`input[name="visite-${formType}[proprietairePresent]"]:checked`)
    ) {
      listInputProprietairePresentError?.classList.remove('fr-hidden');
      isValid = false;
    }

    if (
      isVisiteDone &&
      !visiteForm.querySelector(`input[name="visite-${formType}[concludeProcedure][]"]:checked`)
    ) {
      selectConcludeProcedureError?.classList.remove('fr-hidden');
      isValid = false;
    }

    const editor = tinymce.get(`visite-${formType}[details]`);
    const textContent = editor ? editor.getContent({ format: 'text' }).trim() : '';
    if (!textContent) {
      textareaDetailsError?.classList.remove('fr-hidden');
      isValid = false;
    }
  }

  return isValid;
}

document.addEventListener('submit', (event) => {
  console.log('Submit event on', event.target);

  const cancelVisiteForm = event.target.closest('form[name="signalement-cancel-visite"]');
  const visiteForm = event.target.closest(
    '.signalement-add-visite, .signalement-reschedule-visite, .signalement-confirm-visite'
  );

  if (!cancelVisiteForm && !visiteForm) {
    return;
  }

  // ---- CANCEL VISITE ----
  if (cancelVisiteForm) {
    const idIntervention = cancelVisiteForm.getAttribute('data-intervention-id');
    const tinyMCE = tinymce.get('visite-cancel[details]-' + idIntervention);
    const textContent = tinyMCE ? tinyMCE.getContent() : '';
    const textareaDetailsError = cancelVisiteForm.querySelector(
      '#signalement-cancel-visite-details-error-' + idIntervention
    );

    if (textContent === '') {
      textareaDetailsError.classList.remove('fr-hidden');
      event.preventDefault();
      event.stopImmediatePropagation();
      return;
    }

    textareaDetailsError.classList.add('fr-hidden');
    return;
  }

  // ---- VISITE FORMS ----
  if (visiteForm) {
    const match = visiteForm.className.match(/signalement-(add|reschedule|confirm)-visite/);
    if (!match) return;

    const formType = match[1];

    if (!histoCheckVisiteForms(formType, visiteForm)) {
      event.preventDefault();
      event.stopImmediatePropagation();
      return;
    }

    // uniquement ici si formulaire valide
    const submitButton = visiteForm.querySelector('button[type=submit]');
    if (submitButton) {
      submitButton.disabled = true;
      submitButton.textContent = 'En cours';
    }
  }
});
