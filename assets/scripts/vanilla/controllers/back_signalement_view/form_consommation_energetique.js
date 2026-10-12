// La soumission et l'affichage des erreurs sont gérés par ajax_form_handler.js (data-ajax-form)
// Ce fichier ne gère que l'affichage des champs en fonction de la date du dernier DPE

// Les champs consommation et superficie ne sont affichés qu'une fois la date du dernier DPE sélectionnée :
// - avant 2023 : consommation annuelle (kWh/an) et superficie
// - à partir de 2023 : consommation en kWh/m²/an sur toute la largeur, sans superficie
const updateFieldsFromDateDernierDpe = (isBefore2023) => {
  const formRow = document.querySelector('#signalement-edit-consommation-energetique-form-row');
  const consoEnergie = document.querySelector('.field-consommation-energetique-conso-energie');
  const consoEnergieUnity = document.querySelector(
    '.field-consommation-energetique-conso-energie-unity'
  );
  const superficie = document.querySelector('.field-consommation-energetique-superficie');

  formRow.classList.remove('fr-hidden');
  consoEnergie.classList.toggle('fr-col-6', isBefore2023);
  consoEnergie.classList.toggle('fr-col-12', !isBefore2023);
  consoEnergieUnity.classList.toggle('fr-hidden', isBefore2023);
  superficie.classList.toggle('fr-col-6', isBefore2023);
  superficie.classList.toggle('fr-hidden', !isBefore2023);
};

document
  .querySelector('#signalement-edit-consommation-energetique-dpe-date-before')
  ?.addEventListener('change', () => updateFieldsFromDateDernierDpe(true));
document
  .querySelector('#signalement-edit-consommation-energetique-dpe-date-after')
  ?.addEventListener('change', () => updateFieldsFromDateDernierDpe(false));
