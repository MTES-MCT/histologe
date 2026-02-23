import { attacheAutocompleteAddressEvent, initComponentAddress } from '../../services/component/component_search_address';

attachFormServiceSecoursEvent();

function attachFormServiceSecoursEvent() {
  const fomServiceSecours = document?.querySelector('#fo-form-service-secours');
  if (!fomServiceSecours) {
    return false;
  }
  const inputAdresse = document?.querySelector('[data-fr-adresse-autocomplete]');
  if (inputAdresse) {
    attacheAutocompleteAddressEvent(inputAdresse);
    initComponentAddress('#fo-form-service-secours [data-fr-adresse-autocomplete]');
  }

  // Si on coche la valeur "appartement" pour la nature du logement (service_secours[step2][natureLogement]), on affiche "type-etage-logement-appartement-container"
  const natureLogementRadios = document.querySelectorAll('input[name="service_secours[step2][natureLogement]"]');
  const typeEtageLogementAppartementContainer = document.querySelector('.type-etage-logement-appartement-container');
  natureLogementRadios.forEach(radio => {
    radio.addEventListener('change', (event) => {
      if (event.target.value === 'appartement') {
        typeEtageLogementAppartementContainer.classList.remove('fr-hidden');
      } else {
        typeEtageLogementAppartementContainer.classList.add('fr-hidden');
      }
    });
  });

  // Si on coche la valeur "autre" pour le type d'étage (service_secours[step2][typeEtageLogement]), on affiche "etage-occupant-container"
  const typeEtageLogementRadios = document.querySelectorAll('input[name="service_secours[step2][typeEtageLogement]"]');
  const etageOccupantContainer = document.querySelector('.etage-occupant-container');
  typeEtageLogementRadios.forEach(radio => {
    radio.addEventListener('change', (event) => {
      if (event.target.value === 'AUTRE') {
        etageOccupantContainer.classList.remove('fr-hidden');
      } else {
        etageOccupantContainer.classList.add('fr-hidden');
      }
    });
  });
}
