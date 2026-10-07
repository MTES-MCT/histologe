import { jsonResponseHandler } from '../../services/component/component_json_response_handler.js';

const formBtn = document.querySelector('#signalement-edit-consommation-energetique-form-submit');

formBtn?.addEventListener('click', () => {
  formBtn.disabled = true;
  formBtn.classList.add('fr-btn--loading', 'fr-btn--icon-left', 'fr-icon-refresh-line');

  // Check fields
  let postForm = true;
  if (!document.querySelector('#signalement-edit-consommation-energetique-date-entree').value) {
    document
      .querySelector('#signalement-edit-consommation-energetique-date-entree-error')
      .classList.remove('fr-hidden');
    postForm = false;
  } else {
    document
      .querySelector('#signalement-edit-consommation-energetique-date-entree-error')
      .classList.add('fr-hidden');
  }
  if (
    !document.querySelector('#signalement-edit-consommation-energetique-dpe-0').checked &&
    !document.querySelector('#signalement-edit-consommation-energetique-dpe-1').checked &&
    !document.querySelector('#signalement-edit-consommation-energetique-dpe-2').checked
  ) {
    document
      .querySelector('#signalement-edit-consommation-energetique-dpe-error')
      .classList.remove('fr-hidden');
    postForm = false;
  } else {
    document
      .querySelector('#signalement-edit-consommation-energetique-dpe-error')
      .classList.add('fr-hidden');
  }

  document
    .querySelector('#signalement-edit-consommation-energetique-superficie-error')
    .classList.add('fr-hidden');

  // Post form
  if (postForm) {
    const form = document.querySelector('form#signalement-edit-consommation-energetique-form');
    const url = form.action;
    const type = form.method;

    const stringToBoolean = (stringValue) => {
      switch (stringValue?.toLowerCase()?.trim()) {
        case 'true':
        case 'yes':
        case '1':
          return true;

        case 'false':
        case 'no':
        case '0':
          return false;

        case 'null':
        case null:
        case undefined:
        default:
          return null;
      }
    };

    const inputValueToNumber = (inputValue) => {
      switch (inputValue) {
        case '':
        case null:
        case undefined:
          return null;
        default:
          return Math.round(Number(inputValue));
      }
    };

    const data = {
      _token: document.getElementById('signalement-edit-consommation-energetique-token').value,
      dateEntree: document.getElementById('signalement-edit-consommation-energetique-date-entree')
        ?.value,
      dpe: stringToBoolean(document.querySelector('input[name=dpe]:checked')?.value),
      classeEnergetique: document.getElementById(
        'signalement-edit-consommation-energetique-classe-energetique'
      )?.value,
      dateDernierDPE: document.querySelector('input[name=dateDernierDPE]:checked')?.value,
      consommationEnergie: inputValueToNumber(
        document.getElementById('signalement-edit-consommation-energetique-conso-energie')?.value
      ),
      superficie: inputValueToNumber(
        document.getElementById('signalement-edit-consommation-energetique-superficie')?.value
      ),
    };

    const options = {
      method: type,
      headers: {
        'Content-Type': 'application/json',
      },
      body: JSON.stringify(data),
    };

    fetch(url, options)
      .then(async (response) => {
        if (response.ok) {
          jsonResponseHandler(response);
        } else if (response.status === 400) {
          const data = await response.json();
          if (data.errors?.superficie) {
            document
              .querySelector('#signalement-edit-consommation-energetique-superficie-error')
              .classList.remove('fr-hidden');
          }
        }
        formBtn.disabled = false;
        formBtn.classList.remove('fr-btn--loading', 'fr-btn--icon-left', 'fr-icon-refresh-line');
      })
      .catch((error) => {
        console.error('Error:', error);
        formBtn.disabled = false;
        formBtn.classList.remove('fr-btn--loading', 'fr-btn--icon-left', 'fr-icon-refresh-line');
      });
  } else {
    formBtn.disabled = false;
    formBtn.classList.remove('fr-btn--loading', 'fr-btn--icon-left', 'fr-icon-refresh-line');
  }
});

if (document.querySelector('#signalement-edit-consommation-energetique-dpe-date-before')) {
  document
    .querySelector('#signalement-edit-consommation-energetique-dpe-date-before')
    .addEventListener('change', () => {
      document
        .querySelector('#signalement-edit-consommation-energetique-form-row')
        .classList.remove('fr-hidden');
      document
        .querySelector('.field-consommation-energetique-conso-energie')
        .classList.add('fr-col-6');
      document
        .querySelector('.field-consommation-energetique-conso-energie')
        .classList.remove('fr-col-12');
      document
        .querySelector('.field-consommation-energetique-conso-energie-unity')
        .classList.add('fr-hidden');
      document
        .querySelector('.field-consommation-energetique-superficie')
        .classList.add('fr-col-6');
      document
        .querySelector('.field-consommation-energetique-superficie')
        .classList.remove('fr-hidden');
    });
}
if (document.querySelector('#signalement-edit-consommation-energetique-dpe-date-after')) {
  document
    .querySelector('#signalement-edit-consommation-energetique-dpe-date-after')
    .addEventListener('change', () => {
      document
        .querySelector('#signalement-edit-consommation-energetique-form-row')
        .classList.remove('fr-hidden');
      document
        .querySelector('.field-consommation-energetique-conso-energie')
        .classList.remove('fr-col-6');
      document
        .querySelector('.field-consommation-energetique-conso-energie')
        .classList.add('fr-col-12');
      document
        .querySelector('.field-consommation-energetique-conso-energie-unity')
        .classList.remove('fr-hidden');
      document
        .querySelector('.field-consommation-energetique-superficie')
        .classList.remove('fr-col-6');
      document
        .querySelector('.field-consommation-energetique-superficie')
        .classList.add('fr-hidden');
    });
}
