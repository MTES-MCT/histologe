const addVisitePanel = document.querySelector('#panel-add-visite');
const addVisiteForm = addVisitePanel?.querySelector('#add-visite-form');

if (addVisitePanel && addVisiteForm) {
  const timezone = document.querySelector('[data-territory-timezone]')?.dataset.territoryTimezone;
  const todayInTerritory = new Intl.DateTimeFormat('en-CA', {timeZone: timezone,year: 'numeric',month: '2-digit',day: '2-digit'}).format(new Date());

  const dateField = addVisiteForm.querySelector('input[name$="[scheduledAt]"]');
  const partnerSelect = addVisiteForm.querySelector('select[name$="[partnerChoice]"]');
  const externalOperatorField = addVisiteForm.querySelector('input[name$="[externalOperator]"]');
  const externalOperatorRow = externalOperatorField?.closest('.fr-input-group');
  const visiteDoneRadios = addVisiteForm.querySelectorAll('input[name$="[visiteDone]"]');
  const pastDateFields = addVisitePanel.querySelector('.visite-add-past-date-complementary-fields');
  const futureDateFields = addVisitePanel.querySelector('.visite-add-future-date-complementary-fields');
  const visiteConcludeProcedure = addVisitePanel.querySelector('.visite-conclude-procedure');
  const partnerDoubleError = addVisitePanel.querySelector('.signalement-add-visite-partner-double-error');

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
    const isVisiteDone = addVisiteForm.querySelector('input[name$="[visiteDone]"]:checked')?.value === '1';
    toggleElement(visiteConcludeProcedure, isVisiteDone);
    if (!isVisiteDone) {
      resetConcludeProcedure();
    }
  };

  // Replace l'ensemble du formulaire dans une position cohérente
  const syncAddVisiteFormState = () => {
    syncDateDependantFields();
    syncPartnerFields();
    syncVisiteDoneFields();
  };

  // Champs réactifs : toute modification replace le formulaire dans un état cohérent
  dateField?.addEventListener('change', syncAddVisiteFormState);
  partnerSelect?.addEventListener('change', syncAddVisiteFormState);
  externalOperatorField?.addEventListener('input', syncAddVisiteFormState);
  visiteDoneRadios.forEach((radio) => {
    radio.addEventListener('change', syncAddVisiteFormState);
  });

  // Position cohérente au chargement de la page
  syncAddVisiteFormState();
}