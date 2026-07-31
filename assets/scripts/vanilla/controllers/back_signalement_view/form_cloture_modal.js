const radioButtons = document.querySelectorAll('input[name="cloture[isVisibleForUsager]"]');
const cloturePublicOui = document.querySelector('#warning_cloture_public_oui');
const cloturePublicNon = document.querySelector('#warning_cloture_public_non');
radioButtons.forEach((radioButton) => {
  radioButton.addEventListener('change', function (event) {
    const value = event.target.value;
    if (value === '1') {
      cloturePublicOui?.classList.remove('fr-hidden');
      cloturePublicNon?.classList.add('fr-hidden');
    } else if (value === '0') {
      cloturePublicOui?.classList.add('fr-hidden');
      cloturePublicNon?.classList.remove('fr-hidden');
    }
  });
});

//cloture affectation v2
const motifClotureSelect = document.querySelector('#close_affectation_motifCloture');
const travauxContainer = document.querySelector(
  '#close_affectation_travauxMiseEnConformite_container'
);

function updateTravauxVisibilityAffectation() {
  if (!motifClotureSelect || !travauxContainer) return;
  const selectedOption = motifClotureSelect.options[motifClotureSelect.selectedIndex];
  const needTravaux = selectedOption?.hasAttribute('data-need-travaux-precisions');
  travauxContainer.classList.toggle('fr-hidden', !needTravaux);
}

motifClotureSelect?.addEventListener('change', updateTravauxVisibilityAffectation);
updateTravauxVisibilityAffectation();

//cloture signalement v2
const motifClotureSelectSignalement = document.querySelector('#close_signalement_motifCloture');
const travauxContainerSignalement = document.querySelector(
  '#close_signalement_travauxMiseEnConformite_container'
);
const travauxRadioButtonsSignalement = document.querySelectorAll(
  'input[name="close_signalement[travauxMiseEnConformite]"]'
);
const travauxWarningSignalement = document.querySelector(
  '#close_signalement_travauxMiseEnConformite_warning'
);
const proceduresContainerSignalement = document.querySelector(
  '#close_signalement_procedures_container'
);
const withoutProcedureCheckboxSignalement = document.querySelector(
  '#close_signalement_withoutProcedure'
);

function updateTravauxVisibilitySignalement() {
  if (!motifClotureSelectSignalement || !travauxContainerSignalement) return;
  const selectedOption =
    motifClotureSelectSignalement.options[motifClotureSelectSignalement.selectedIndex];
  const needTravaux = selectedOption?.hasAttribute('data-need-travaux-precisions');
  travauxContainerSignalement.classList.toggle('fr-hidden', !needTravaux);
}

function updateTravauxWarningSignalement() {
  const selectedTravaux = document.querySelector(
    'input[name="close_signalement[travauxMiseEnConformite]"]:checked'
  );
  travauxWarningSignalement?.classList.toggle('fr-hidden', selectedTravaux?.value !== 'EN_COURS');
}

function updateProceduresStateSignalement() {
  if (!proceduresContainerSignalement || !withoutProcedureCheckboxSignalement) return;

  const isWithoutProcedure = withoutProcedureCheckboxSignalement.checked;
  proceduresContainerSignalement.querySelectorAll('input, button').forEach((control) => {
    if (isWithoutProcedure && control.type === 'checkbox') {
      control.checked = false;
    }
    control.disabled = isWithoutProcedure;
  });

  window.dispatchEvent(new Event('refreshSearchCheckboxContainerEvent'));
}

motifClotureSelectSignalement?.addEventListener('change', updateTravauxVisibilitySignalement);
travauxRadioButtonsSignalement.forEach((radioButton) => {
  radioButton.addEventListener('change', updateTravauxWarningSignalement);
});
withoutProcedureCheckboxSignalement?.addEventListener('change', updateProceduresStateSignalement);
updateTravauxVisibilitySignalement();
updateTravauxWarningSignalement();
updateProceduresStateSignalement();
