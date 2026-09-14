import { initTinyMCE } from '../../services/form/form_helper.js';

const initVisiteForm = (visiteForm) => {
  const timezone = document.querySelector('[data-territory-timezone]')?.dataset.territoryTimezone;
  const todayInTerritory = new Intl.DateTimeFormat('en-CA', {timeZone: timezone,year: 'numeric',month: '2-digit',day: '2-digit'}).format(new Date());

  const dateField = visiteForm.querySelector('input[name$="[scheduledAt]"]');
  const partnerSelect = visiteForm.querySelector('select[name$="[partnerChoice]"]');
  const externalOperatorField = visiteForm.querySelector('input[name$="[externalOperator]"]');
  const externalOperatorRow = externalOperatorField?.closest('.fr-input-group');
  const visiteDoneRadios = visiteForm.querySelectorAll('input[name$="[visiteDone]"]');
  const pastDateFields = visiteForm.querySelector('.visite-past-date-complementary-fields');
  const futureDateFields = visiteForm.querySelector('.visite-future-date-complementary-fields');
  const visiteConcludeProcedure = visiteForm.querySelector('.visite-conclude-procedure');
  const partnerDoubleError = visiteForm.querySelector('.signalement-visite-partner-double-error');

  // Réinitialise les champs du bloc "visite passée" lorsqu'il est masqué
  const resetPastDateFields = () => {
    pastDateFields?.querySelectorAll('input[type="radio"]:checked, input[type="checkbox"]:checked').forEach((input) => {
      input.checked = false;
    });
    const fileField = pastDateFields?.querySelector('input[type="file"]');
    if (fileField) {
      fileField.value = '';
    }
    pastDateFields?.querySelectorAll('textarea').forEach((textarea) => {
      textarea.value = '';
      window.tinymce?.get(textarea.id)?.setContent('');
    });
    resetConcludeProcedure();
  };

  // Réinitialise le bloc "conclusion de la visite" lorsqu'il est masqué
  const resetConcludeProcedure = () => {
    if (visiteConcludeProcedure) {
      visiteConcludeProcedure.querySelectorAll('input[type="checkbox"]:checked').forEach((checkbox) => {
        checkbox.checked = false;
      });
      window.dispatchEvent(new Event('refreshSearchCheckboxContainerEvent'));
    }
  };

  // Affiche les champs "visite passée" ou "visite à venir" selon la date choisie
  const syncDateDependantFields = () => {
    const isPastDate = Boolean(dateField?.value) && dateField.value <= todayInTerritory;
    toggleElement(pastDateFields, isPastDate);
    toggleElement(futureDateFields, !isPastDate);
    if (!isPastDate) {
      resetPastDateFields();
    }
  };

  const toggleElement = (element, isVisible) => {
    element?.classList.toggle('fr-hidden', !isVisible);
  };

  // Affiche le champ "opérateur externe" et signale les opérateurs ayant déjà une visite en cours
  const syncPartnerFields = () => {
    if (!partnerSelect) {
      return;
    }
    const isExternalOperator = partnerSelect.value === 'extern';
    toggleElement(externalOperatorRow, isExternalOperator);
    if (!isExternalOperator && externalOperatorField) {
      externalOperatorField.value = '';
    }

    const partnerHasPendingVisite = Boolean(partnerSelect.selectedOptions[0]?.classList.contains('alert-partner'));
    let externalOperatorHasPendingVisite = false;
    if (isExternalOperator && externalOperatorField?.value) {
      const pendingOperators = JSON.parse(externalOperatorField.dataset.existingPendingExternalOperators || '[]');
      externalOperatorHasPendingVisite = pendingOperators.map((name) => name.toLowerCase()).includes(externalOperatorField.value.trim().toLowerCase());
    }
    toggleElement(partnerDoubleError, partnerHasPendingVisite || externalOperatorHasPendingVisite);
  };

  // Affiche le fieldset "conclusion de la visite" uniquement si la visite est indiquée comme effectuée
  const syncVisiteDoneFields = () => {
    const isVisiteDone = visiteForm.querySelector('input[name$="[visiteDone]"]:checked')?.value === '1';
    toggleElement(visiteConcludeProcedure, isVisiteDone);
    if (!isVisiteDone) {
      resetConcludeProcedure();
    }
  };

  // Replace l'ensemble du formulaire dans une position cohérente
  const syncVisiteFormState = () => {
    syncDateDependantFields();
    syncPartnerFields();
    syncVisiteDoneFields();
  };

  // Champs réactifs : toute modification replace le formulaire dans un état cohérent
  dateField?.addEventListener('change', syncVisiteFormState);
  partnerSelect?.addEventListener('change', syncVisiteFormState);
  externalOperatorField?.addEventListener('input', syncVisiteFormState);
  visiteDoneRadios.forEach((radio) => {
    radio.addEventListener('change', syncVisiteFormState);
  });

  // Position cohérente au chargement de la page
  syncVisiteFormState();
};

// Initialise le formulaire d'ajout de visite au chargement de la page
const addVisiteForm = document.querySelector('#add-visite-form');
if (addVisiteForm) {
  initVisiteForm(addVisiteForm);
}

document.addEventListener('click', (event) => {
  const btn = event.target.closest('.btn-manage-visite');
  if (!btn) {
    return;
  }
  const url = btn.dataset.url;
  const submitButton = document.querySelector('#panel-manage-visite button[type="submit"]');
  submitButton.disabled = true;
  document.querySelector('#panel-manage-visite-title').innerHTML = 'Chargement en cours...';
  document.querySelector('#panel-manage-visite-content').innerHTML = 'Chargement en cours...';
  fetch(url).then((response) => {
    if (response.ok) {
      response.json().then((response) => {
        document.querySelector('#panel-manage-visite-title').innerHTML = response.title;
        document.querySelector('#panel-manage-visite-content').innerHTML = response.content;
        submitButton.disabled = false;
        submitButton.setAttribute('form', document.querySelector('#panel-manage-visite-content form')?.id ?? '');

        //TODO : voir si on peux rendre générique les traitements d'initialisation
        const rescheduleVisiteForm = document.querySelector('#reschedule-visite-form');
        const cancelVisiteForm = document.querySelector('#cancel-visite-form');
        const confirmVisiteForm = document.querySelector('#confirm-visite-form');
        const editConclusionVisiteForm = document.querySelector('#edit-conclusion-visite-form');
        if (rescheduleVisiteForm) {
          initVisiteForm(rescheduleVisiteForm);
          initTinyMCE('#reschedule-visite-form textarea.editor');
        }else if(cancelVisiteForm) {
          initTinyMCE('#cancel-visite-form textarea.editor');
        }else if (confirmVisiteForm) {
          initVisiteForm(confirmVisiteForm);
          initTinyMCE('#confirm-visite-form textarea.editor');
        }else if (editConclusionVisiteForm) {
          window.dispatchEvent(new Event('refreshSearchCheckboxContainerEvent'));
          initTinyMCE('#edit-conclusion-visite-form textarea.editor');
        }
      });
    } else {
      const content =
        '<div class="fr-notice fr-notice--alert"><div class="fr-container"><div class="fr-notice__body"><p><span class="fr-notice__title">Erreur</span><span class="fr-notice__desc">Une erreur s\'est produite. Veuillez actualiser la page.</span></p></div></div></div>';
      document.querySelector('#panel-manage-visite-content').innerHTML = content;
    }
  });
});