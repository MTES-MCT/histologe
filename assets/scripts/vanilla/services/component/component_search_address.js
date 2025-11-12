export function attacheAutocompleteAddressEvents() {
  if (document.querySelector('[data-fr-adresse-autocomplete]')) {
    attachAutocompleteClickOutsideEvent();
  }
  document.querySelectorAll('[data-fr-adresse-autocomplete]').forEach((inputAdresse) => {
    attacheAutocompleteAddressEvent(inputAdresse);
  });
}
attacheAutocompleteAddressEvents();

document
  .querySelector('#form-edit-address-adresse')
  ?.addEventListener('input', (event) => setManualEdit(event.target, false));
document
  .querySelector('#form-edit-address-codepostal')
  ?.addEventListener('input', (event) => setManualEdit(event.target, true));
document
  .querySelector('#form-edit-address-ville')
  ?.addEventListener('input', (event) => setManualEdit(event.target, true));

function attachAutocompleteClickOutsideEvent() {
  document.addEventListener('click', function (event) {
    document
      ?.querySelectorAll('.fr-address-group, .fr-address-group-bo')
      .forEach((addressGroup) => {
        const isClickInside = addressGroup.contains(event.target);
        if (!isClickInside) {
          addressGroup.innerHTML = '';
        }
      });
  });
}

export function attacheAutocompleteAddressEvent(inputAdresse) {
  if (!inputAdresse) {
    return false;
  }

  let selectionIndex = -1;
  let suffix = '';
  if (inputAdresse.dataset.suffix !== undefined && inputAdresse.dataset.suffix !== '') {
    suffix = '-' + inputAdresse.dataset.suffix;
  }
  const addressGroup = document?.querySelector(inputAdresse.dataset.autocompleteQuerySelector);
  const fieldFilterAddress = document?.querySelector(
    '#signalement_draft_address_filterSearchAddressTerritory'
  );

  const apiAdresse = 'https://data.geopf.fr/geocodage/search/?q=';
  let addressAbortController;
  inputAdresse.addEventListener('input', (e) => {
    const adresse = e.target.value;

    if (addressAbortController) {
      addressAbortController.abort();
    }

    if (adresse.length > 8) {
      addressAbortController = new AbortController();
      const zipFilterAddress = fieldFilterAddress ? fieldFilterAddress.value : '';
      let query = apiAdresse + adresse;
      let limit = inputAdresse.getAttribute('data-form-limit');
      if (zipFilterAddress !== '') {
        const splitFilter = zipFilterAddress.split('|');
        query += ' ' + splitFilter[1];
        limit = splitFilter[0];
      }
      if (inputAdresse.getAttribute('data-form-lat')) {
        query += '&lat=' + inputAdresse.getAttribute('data-form-lat');
      }
      if (inputAdresse.getAttribute('data-form-lng')) {
        query += '&lon=' + inputAdresse.getAttribute('data-form-lng');
      }
      fetch(query, { signal: addressAbortController.signal })
        .then((response) => response.json())
        .then((json) => {
          addressGroup.innerHTML = '';
          json.features.forEach((feature) => {
            if (limit === null || feature.properties.citycode.startsWith(limit)) {
              const suggestion = document.createElement('div');
              suggestion.classList.add(
                'fr-col-12',
                'fr-p-3v',
                'fr-text-label--blue-france',
                'fr-adresse-suggestion'
              );
              suggestion.innerHTML = feature.properties.label;
              attachAddressSuggestionEvent(inputAdresse, suggestion, feature, suffix);
              addressGroup.appendChild(suggestion);
            }
          });
        })
        .catch((error) => {
          if (error.name === 'AbortError') {
            return;
          }
          console.error('Error:', error);
        });
    }
    if (adresse.length === 0) {
      addressGroup.innerHTML = '';
      const idForm = inputAdresse.closest('form').id;
      if (document?.querySelector('#' + idForm + ' [data-autocomplete-addresse' + suffix + ']')) {
        document.querySelector('#' + idForm + ' [data-autocomplete-addresse' + suffix + ']').value =
          '';
      }
      if (document?.querySelector('#' + idForm + ' [data-autocomplete-codepostal' + suffix + ']')) {
        document.querySelector(
          '#' + idForm + ' [data-autocomplete-codepostal' + suffix + ']'
        ).value = '';
      }
      if (document?.querySelector('#' + idForm + ' [data-autocomplete-ville' + suffix + ']')) {
        document.querySelector('#' + idForm + ' [data-autocomplete-ville' + suffix + ']').value =
          '';
      }
      if (document?.querySelector('#' + idForm + ' [data-autocomplete-insee' + suffix + ']')) {
        document.querySelector('#' + idForm + ' [data-autocomplete-insee' + suffix + ']').value =
          '';
      }
    }
  });

  inputAdresse.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' || e.key === 'Tab') {
      addressGroup.innerHTML = '';
      selectionIndex = -1;
    }

    if (e.key === 'ArrowDown') {
      if (addressGroup.children.length > 0) {
        selectionIndex = Math.min(selectionIndex + 1, addressGroup.children.length - 1);
      } else {
        selectionIndex = -1;
      }
    }
    if (e.key === 'ArrowUp') {
      if (addressGroup.children.length > 0) {
        selectionIndex = Math.max(selectionIndex - 1, 0);
      } else {
        selectionIndex = -1;
      }
    }
    if (e.key === 'Enter') {
      e.preventDefault(); // avoids form submit
      if (selectionIndex > -1) {
        addressGroup.children[selectionIndex].click();
      }
      addressGroup.innerHTML = '';
      selectionIndex = -1;
    }

    document.querySelectorAll('.fr-adresse-suggestion').forEach((element) => {
      element.classList.remove('fr-autocomplete-suggestion-highlighted');
    });
    if (selectionIndex > -1) {
      addressGroup.children[selectionIndex].classList.add('fr-autocomplete-suggestion-highlighted');
    }
  });
}

function attachAddressSuggestionEvent(inputAdresse, suggestion, feature, suffix) {
  suggestion.addEventListener('click', () => {
    const idForm = inputAdresse.closest('form').id;
    inputAdresse.value = feature.properties.label;
    if (document?.querySelector('#' + idForm + ' [data-autocomplete-addresse' + suffix + ']')) {
      document.querySelector('#' + idForm + ' [data-autocomplete-addresse' + suffix + ']').value =
        feature.properties.name;
    }
    if (document?.querySelector('#' + idForm + ' [data-autocomplete-codepostal' + suffix + ']')) {
      document.querySelector('#' + idForm + ' [data-autocomplete-codepostal' + suffix + ']').value =
        feature.properties.postcode;
    }
    if (document?.querySelector('#' + idForm + ' [data-autocomplete-ville' + suffix + ']')) {
      document.querySelector('#' + idForm + ' [data-autocomplete-ville' + suffix + ']').value =
        feature.properties.city;
    }
    if (document?.querySelector('#' + idForm + ' [data-autocomplete-manual' + suffix + ']')) {
      document.querySelector('#' + idForm + ' [data-autocomplete-manual' + suffix + ']').value = 0;
    }
    if (
      document?.querySelector('#' + idForm + ' [data-autocomplete-need-reset-insee' + suffix + ']')
    ) {
      document.querySelector(
        '#' + idForm + ' [data-autocomplete-need-reset-insee' + suffix + ']'
      ).value = 0;
    }
    if (document?.querySelector('#' + idForm + ' [data-autocomplete-insee' + suffix + ']')) {
      document.querySelector('#' + idForm + ' [data-autocomplete-insee' + suffix + ']').value =
        feature.properties.citycode;
    }
    const addressGroup = document?.querySelector(inputAdresse.dataset.autocompleteQuerySelector);
    addressGroup.innerHTML = '';
  });
}

function setManualEdit(input, needResetInsee) {
  const idForm = input.closest('form').id;
  document.querySelector('#' + idForm + ' [data-autocomplete-manual]').value = 1;
  if (needResetInsee) {
    document.querySelector('#' + idForm + ' [data-autocomplete-need-reset-insee]').value = 1;
  }
}

export function initComponentAdress(id) {
  const addressInput = document.querySelector(id);
  const addressInputParent = addressInput.parentElement.parentElement.parentElement;
  const manualAddressSwitcher = addressInputParent?.querySelector(
    '.manual-address-switcher'
  );
  const manualAddressContainer = addressInputParent?.querySelector(
    '.manual-address-container'
  );
  const manualAddressAddress = addressInputParent?.querySelector(
    '.manual-address-input'
  );
  const manualAddressInputs = addressInputParent?.querySelectorAll(
    '.manual-address'
  );
  const hasManualAddressValues = Array.from(manualAddressInputs).some(
    (input) => input.value !== ''
  );

  manualAddressSwitcher?.addEventListener('click', (event) => {
    event.preventDefault();
    if (manualAddressContainer.classList.contains('fr-hidden')) {
      manualAddressContainer.classList.remove('fr-hidden');
      manualAddressSwitcher.textContent = 'Rechercher une adresse';
      addressInput.value = '';
      addressInput.disabled = true;
      manualAddressAddress.focus();
    } else {
      manualAddressContainer.classList.add('fr-hidden');
      manualAddressSwitcher.textContent = 'Saisir une adresse manuellement';
      manualAddressInputs.forEach((input) => (input.value = ''));
      addressInput.disabled = false;
      addressInput.focus();
    }
  });

  if (addressInput.value == '' && hasManualAddressValues) {
    manualAddressSwitcher.click();
  }
}
