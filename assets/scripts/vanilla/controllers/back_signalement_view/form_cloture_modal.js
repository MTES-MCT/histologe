import { initTinyMCE } from '../../services/form/form_helper.js';
import { initializeFormSteps } from '../../services/form/ajax_form_steps.js';

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
  if (needTravaux && travauxContainer) {
    const needTravauxNon = selectedOption?.getAttribute('data-need-travaux-precisions-non');
    const travauxRadioNon = travauxContainer.querySelector('input[value="NON"]');
    if (travauxRadioNon) {
      travauxRadioNon.parentElement.parentElement.classList.toggle(
        'fr-hidden',
        needTravauxNon === 'false'
      );
    }
  }
}

motifClotureSelect?.addEventListener('change', updateTravauxVisibilityAffectation);
updateTravauxVisibilityAffectation();

//cloture signalement v2
function initCloseSignalementForm() {
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
    if (needTravaux && travauxContainerSignalement) {
      const needTravauxNon = selectedOption?.getAttribute('data-need-travaux-precisions-non');
      const travauxRadioNon = travauxContainerSignalement.querySelector('input[value="NON"]');
      if (travauxRadioNon) {
        travauxRadioNon.parentElement.parentElement.classList.toggle(
          'fr-hidden',
          needTravauxNon === 'false'
        );
      }
    }
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
}

const btnCloseSignalement = document.querySelector('.btn-close-signalement');

btnCloseSignalement?.addEventListener('click', () => {
  const url = btnCloseSignalement.dataset.url;
  const panelContent = document.querySelector('#panel-close-signalement-content');
  panelContent.querySelectorAll('textarea.editor').forEach((textarea) => {
    window.tinymce?.get(textarea.id)?.remove();
  });
  const submitButton = document.querySelector('#panel-close-signalement button[type="submit"]');
  submitButton.disabled = true;
  panelContent.innerHTML = 'Chargement en cours...';
  fetch(url).then((response) => {
    if (response.ok) {
      response.json().then((response) => {
        panelContent.innerHTML = response.content;
        submitButton.disabled = false;

        initCloseSignalementForm();
        const closeSignalementForm = panelContent.querySelector('#close-signalement-form');
        if (closeSignalementForm) {
          initializeFormSteps(closeSignalementForm);
        }
        initTinyMCE('#close-signalement-form textarea.editor');
      });
    } else {
      const content =
        '<div class="fr-notice fr-notice--alert"><div class="fr-container"><div class="fr-notice__body"><p><span class="fr-notice__title">Erreur</span><span class="fr-notice__desc">Une erreur s\'est produite. Veuillez actualiser la page.</span></p></div></div></div>';
      document.querySelector('#panel-close-signalement-content').innerHTML = content;
    }
  });
});
