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
  const natureLogementAutreContainer = document.querySelector('.nature-logement-autre-container');

  // Vérifier l'état initial au chargement de la page
  const natureLogementChecked = document.querySelector('input[name="service_secours[step2][natureLogement]"]:checked');
  if (natureLogementChecked) {
    if (natureLogementChecked.value === 'appartement') {
      typeEtageLogementAppartementContainer.classList.remove('fr-hidden');
    }
    if (natureLogementChecked.value === 'autre') {
      natureLogementAutreContainer.classList.remove('fr-hidden');
    }
  }

  natureLogementRadios.forEach(radio => {
    radio.addEventListener('change', (event) => {
      if (event.target.value === 'appartement') {
        typeEtageLogementAppartementContainer.classList.remove('fr-hidden');
      } else {
        typeEtageLogementAppartementContainer.classList.add('fr-hidden');
      }

      if (event.target.value === 'autre') {
        natureLogementAutreContainer.classList.remove('fr-hidden');
      } else {
        natureLogementAutreContainer.classList.add('fr-hidden');
      }
    });
  });

  // Si on coche la valeur "autre" pour le type d'étage (service_secours[step2][typeEtageLogement]), on affiche "etage-occupant-container"
  const typeEtageLogementRadios = document.querySelectorAll('input[name="service_secours[step2][typeEtageLogement]"]');
  const etageOccupantContainer = document.querySelector('.etage-occupant-container');

  // Vérifier l'état initial au chargement de la page
  const typeEtageLogementChecked = document.querySelector('input[name="service_secours[step2][typeEtageLogement]"]:checked');
  if (typeEtageLogementChecked && typeEtageLogementChecked.value === 'AUTRE') {
    etageOccupantContainer.classList.remove('fr-hidden');
  }

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



//step4
const showInformationsSyndicContainer = document.querySelector('#show-informations-syndic-container');
const informationsSyndicContainer = document.querySelector('#informations-syndic-container');
if (showInformationsSyndicContainer && informationsSyndicContainer) {
  showInformationsSyndicContainer.addEventListener('click', (event) => {
    event.preventDefault();
    informationsSyndicContainer.classList.remove('fr-hidden');
    showInformationsSyndicContainer.classList.add('fr-hidden');
  });
  const denominationAgence = document.querySelector('#service_secours_step4_denominationAgence');
  const nomAgence = document.querySelector('#service_secours_step4_nomAgence');
  const mailAgence = document.querySelector('#service_secours_step4_mailAgence');
  const telAgence = document.querySelector('#service_secours_step4_telAgence_input');
  if (denominationAgence.value || nomAgence.value || mailAgence.value || telAgence.value) {
    informationsSyndicContainer.classList.remove('fr-hidden');
    showInformationsSyndicContainer.classList.add('fr-hidden');
  }
}



