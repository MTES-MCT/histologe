import { attacheAutocompleteAddressEvent } from '../../services/component/component_search_address';
import * as Sentry from '@sentry/browser';

const STORAGE_DATA_KEY = 'import_arrete_data';
const STORAGE_FILENAME_KEY = 'import_arrete_filename';

const root = document.querySelector('#import-arrete-container');

if (root) {
  const elements = getElements(root);

  if (elements.fileInput && elements.form) {
    bindEvents(elements);
    restoreFromSessionStorage(elements);
  }
}

function restoreFromSessionStorage(elements) {
  const savedData = sessionStorage.getItem(STORAGE_DATA_KEY);
  const savedFileName = sessionStorage.getItem(STORAGE_FILENAME_KEY);

  if (savedData) {
    try {
      const data = JSON.parse(savedData);
      showSuccess(elements, savedFileName);
      renderCards(elements, data);
      updateValidationState(elements);
    } catch (error) {
      const errorMessage = 'Error restoring data from sessionStorage';
      console.error(errorMessage, error);
      Sentry.captureException(new Error(errorMessage));
      clearStoredImport();
    }
  }
}

function clearStoredImport() {
  sessionStorage.removeItem(STORAGE_DATA_KEY);
  sessionStorage.removeItem(STORAGE_FILENAME_KEY);
}

function getElements(root) {
  const fileInput = root.querySelector('input[type="file"]');

  return {
    root,
    fileInput,
    form: fileInput?.closest('form'),
    uploadFile: root.querySelector('.import-csv-upload-file'),
    loadingBlock: root.querySelector('.import-csv-loading'),
    successBlock: root.querySelector('.import-csv-success'),
    errorBlock: root.querySelector('.import-csv-error-callout'),
    errorList: root.querySelector('.import-csv-error-list'),
    cardsContainer: document.querySelector('#import-csv-cards-container'),
    cardTemplate: document.querySelector('#import-csv-card-template'),
    validationSection: document.querySelector('#import-csv-data-validation'),
    validationBlock: document.querySelector('#import-csv-validation'),
    validateButton: document.querySelector('#import-csv-validation-submit'),
    countContainer: document.querySelector('#import-csv-address-to-check-count-container'),
    countSpan: document.querySelector('#import-csv-address-to-check-count'),
    cancelElements: root.querySelectorAll(
      '.import-csv-success a, #import-csv-validation button.fr-btn--secondary'
    ),
    sidePanel: document.querySelector('dialog#container-pick-localisation'),
    logoutElement: document.querySelector('a.remove-session-storage'),
  };
}

function bindEvents(elements) {
  elements.fileInput.addEventListener('change', () => handleUpload(elements));
  elements.validateButton?.addEventListener('click', () => handleConfirm(elements));
  elements.cancelElements.forEach((element) => {
    element.addEventListener('click', (e) => {
      e.preventDefault();
      clearStoredImport();
      window.location.reload();
    });
  });
  elements.logoutElement?.addEventListener('click', () => {
    clearStoredImport();
  });

  const sidePanelSubmitBtn = document.getElementById('container-pick-localisation-submit');
  sidePanelSubmitBtn?.addEventListener('click', () => handleSidePanelSubmit(elements));
}

function handleSidePanelSubmit(elements) {
  const sidePanel = elements.sidePanel;
  const rnbId = document.getElementById('container-pick-localisation-rnb-id')?.value;
  if (!sidePanel || !rnbId) return;

  const cardIndex = sidePanel.dataset.currentCardIndex;
  if (!cardIndex) return;

  const card = document.querySelector(`#card-${cardIndex}`);
  if (!card) return;

  let selectedBuilding = null;
  if (sidePanel.dataset.selectedBuilding) {
    try {
      selectedBuilding = JSON.parse(sidePanel.dataset.selectedBuilding);
    } catch (error) {
      const errorMessage = 'Error parsing selected building from side-panel dataset';
      console.error(errorMessage, error);
      Sentry.captureException(new Error(errorMessage));
    }
  }

  const successBadge = card.querySelector('[data-pick-location-success]');
  const errorBadge = card.querySelector('[data-pick-location-error]');
  const rnbIdInput = card.querySelector('[data-arrete-rnb-id-input]');
  const addressContainer = card.querySelector('[data-address-container]');
  const wrap = card.querySelector('.fr-input-wrap');

  const hasAddresses = Boolean(
    selectedBuilding &&
      Array.isArray(selectedBuilding.addresses) &&
      selectedBuilding.addresses.length > 0
  );

  if (!hasAddresses) {
    successBadge?.classList.add('fr-hidden');
    errorBadge?.classList.remove('fr-hidden');

    card.dataset.arreteRnbId = '';
    if (rnbIdInput) {
      rnbIdInput.value = '';
    }

    const addressInput = card.querySelector('[data-arrete-adresse-complete]');
    if (addressInput) {
      addressInput.value = '';
    }

    wrap?.classList.remove('fr-icon-check-line');
    wrap?.classList.add('fr-icon-search-line');

    addressContainer?.classList.remove('import-csv-card--valid-address');
    addressContainer?.classList.add('fr-input-group--error');

    if (!card.classList.contains('import-csv-card--ignored')) {
      card.classList.remove('import-csv-card--valid');
      card.classList.add('import-csv-card--to-check');
    }

    updateRowInData(cardIndex, {
      rnbId: null,
      addressToValidate: true,
    });

    closeSidePanel(sidePanel);
    updateValidationState(elements);
    return;
  }

  errorBadge?.classList.add('fr-hidden');

  let houseNumber = card.dataset.arreteNumeroVoie ?? '';
  let street = card.dataset.arreteNomVoie ?? '';
  let postcode = card.dataset.arreteCodePostal ?? '';
  let city = card.dataset.arreteCommune ?? '';

  const addressObj = selectedBuilding.addresses[0];
  const streetNum = [addressObj.street_number, addressObj.street_rep]
    .filter(Boolean)
    .join(' ')
    .trim();
  if (addressObj.street) {
    houseNumber = streetNum;
    street = addressObj.street || '';
    postcode = addressObj.city_zipcode || '';
    city = sanitizeCity(addressObj.city_name) || '';
  }

  card.dataset.arreteNumeroVoie = houseNumber;
  card.dataset.arreteNomVoie = street;
  card.dataset.arreteCodePostal = postcode;
  card.dataset.arreteCommune = city;
  card.dataset.arreteRnbId = rnbId;

  const houseNumberInput = card.querySelector('[data-autocomplete-housenumber]');
  if (houseNumberInput) {
    houseNumberInput.value = houseNumber;
  }
  const streetInput = card.querySelector('[data-autocomplete-street]');
  if (streetInput) {
    streetInput.value = street;
  }
  const postcodeInput = card.querySelector('[data-autocomplete-codepostal]');
  if (postcodeInput) {
    postcodeInput.value = postcode;
  }
  const cityInput = card.querySelector('[data-autocomplete-ville]');
  if (cityInput) {
    cityInput.value = city;
  }

  const addressInput = card.querySelector('[data-arrete-adresse-complete]');
  const fullAddress = `${houseNumber} ${street} ${postcode} ${city}`.trim();
  if (addressInput && fullAddress) {
    addressInput.value = fullAddress;
  }

  if (fullAddress) {
    setText(card, '[data-arrete-adresse-1]', `${houseNumber} ${street}`.trim());
    setText(card, '[data-arrete-adresse-2]', `${postcode} ${city}`.trim());
  }

  card.querySelector('[data-arrete-adresse-suggestions]')?.replaceChildren();

  if (rnbIdInput) {
    rnbIdInput.value = rnbId;
  }

  if (successBadge) {
    successBadge.classList.remove('fr-hidden');
  }

  addressContainer?.classList.remove('fr-input-group--error');
  addressContainer?.querySelector('[data-arrete-adresse-error]')?.classList.add('fr-hidden');
  addressContainer?.classList.add('import-csv-card--valid-address');

  wrap?.classList.remove('fr-icon-search-line');
  wrap?.classList.add('fr-icon-check-line');

  if (!card.classList.contains('import-csv-card--ignored')) {
    card.classList.remove('import-csv-card--to-check');
    card.classList.add('import-csv-card--valid');
  }

  updateRowInData(cardIndex, {
    numeroVoie: houseNumber,
    nomVoie: street,
    codePostal: postcode,
    commune: city,
    rnbId,
    addressToValidate: false,
  });

  closeSidePanel(sidePanel);
  updateValidationState(elements);
}

async function handleUpload(elements) {
  if (!elements.fileInput.files.length) return;

  resetUploadState(elements);

  try {
    const response = await fetch(elements.form.action, {
      method: 'POST',
      body: new FormData(elements.form),
      headers: {
        'X-Requested-With': 'XMLHttpRequest',
      },
    });

    const payload = await response.json();

    if (!response.ok) {
      showErrors(elements, payload.errors ?? ['Une erreur inconnue est survenue.']);
      return;
    }

    if (payload.errors?.length) {
      showErrors(elements, payload.errors);
      if (!payload.data?.length) {
        return;
      }
    }

    if (payload.data?.length) {
      sessionStorage.setItem(STORAGE_DATA_KEY, JSON.stringify(payload.data));
      if (elements.fileInput.files.length > 0) {
        sessionStorage.setItem(STORAGE_FILENAME_KEY, elements.fileInput.files[0].name);
      }
      showSuccess(elements);
      renderCards(elements, payload.data);
      updateValidationState(elements);
    } else {
      elements.validationSection?.classList.add('import-csv-section--disabled');
    }
  } catch (error) {
    console.error('Error during CSV import:', error);
    showErrors(elements, ['Une erreur est survenue.']);
  } finally {
    elements.loadingBlock?.classList.add('fr-display-none');
  }
}

async function handleConfirm(elements) {
  if (elements.validateButton) {
    elements.validateButton.disabled = true;
  }

  try {
    const rows = collectRowsToConfirm();

    if (!rows.length) {
      showErrors(elements, ['Aucune ligne à valider.']);
      updateValidationState(elements);
      return;
    }

    const response = await fetch(elements.root.dataset.urlConfirmArrete, {
      method: 'POST',
      body: JSON.stringify(rows),
      headers: {
        'Content-Type': 'application/json',
        'X-Requested-With': 'XMLHttpRequest',
      },
    });

    if (!response.ok) {
      showErrors(elements, ["Une erreur est survenue lors de la validation de l'import."]);
      updateValidationState(elements);
      return;
    }

    clearStoredImport();
    window.location.href = elements.root.dataset.urlRedirectionArreteList;
  } catch (error) {
    const errorMessage = 'Error during import confirmation';
    console.error(errorMessage, error);
    showErrors(elements, ['Une erreur est survenue.']);
    updateValidationState(elements);
  }
}

function resetUploadState(elements) {
  clearStoredImport();
  elements.uploadFile?.classList.add('fr-display-none');
  elements.loadingBlock?.classList.remove('fr-display-none');
  elements.successBlock?.classList.add('fr-display-none');
  elements.errorBlock?.classList.add('fr-display-none');

  if (elements.errorList) {
    elements.errorList.innerHTML = '';
  }

  if (elements.cardsContainer) {
    elements.cardsContainer.innerHTML = '';
  }

  disableValidation(elements);
}

function showSuccess(elements, fileName = null) {
  elements.uploadFile?.classList.add('fr-display-none');
  elements.successBlock?.classList.remove('fr-display-none');

  const fileNameElement = elements.successBlock?.querySelector('.fr-text--sm');
  if (fileNameElement) {
    if (fileName) {
      fileNameElement.textContent = fileName;
    } else if (elements.fileInput.files.length > 0) {
      fileNameElement.textContent = elements.fileInput.files[0].name;
    }
  }

  elements.validationSection?.classList.remove('import-csv-section--disabled');
}

function showErrors(elements, errors) {
  elements.uploadFile?.classList.remove('fr-display-none');
  elements.errorBlock?.classList.remove('fr-display-none');

  if (!elements.errorList) return;

  elements.errorList.innerHTML = '';

  errors.forEach((error) => {
    const li = document.createElement('li');
    li.textContent = error;
    elements.errorList.appendChild(li);
  });

  elements.errorBlock?.scrollIntoView({
    behavior: 'smooth',
    block: 'start',
  });
}

function renderCards(elements, rows) {
  if (!(elements.cardTemplate instanceof HTMLTemplateElement) || !elements.cardsContainer) return;

  elements.cardsContainer.innerHTML = '';

  rows.forEach((row, index) => {
    const clone = createCard(elements, row, index);
    const input = clone.querySelector('[data-arrete-adresse-complete]');

    elements.cardsContainer.appendChild(clone);

    if (row && input) {
      attachAutocomplete(elements, input);
    }
  });
}

function createCard(elements, row, index) {
  const clone = elements.cardTemplate.content.cloneNode(true);
  const card = clone.querySelector('.import-csv-card');

  fillCardContent(clone, row);
  configureCardState(card, row, index);
  configureAddressInput(clone, row, index);
  configureIgnoreCheckbox(elements, clone, card, index);
  configurePickLocalisation(elements, clone, card, row, index);

  return clone;
}

function fillCardContent(clone, row) {
  setText(clone, '[data-arrete-date]', row.dateArrete);
  setText(clone, '[data-arrete-classification]', row.classificationArrete);
  setText(clone, '[data-arrete-adresse-1]', `${row.numeroVoie ?? ''} ${row.nomVoie ?? ''}`);
  setText(clone, '[data-arrete-adresse-2]', `${row.codePostal ?? ''} ${row.commune ?? ''}`);

  if (row.dateArreteMainLevee) {
    setText(clone, '[data-arrete-date-main-levee]', row.dateArreteMainLevee);
    clone
      .querySelector('[data-arrete-date-main-levee-container]')
      ?.classList.remove('fr-display-none');
  }
}

function configureCardState(card, row, index) {
  if (!card) return;

  const isValid = !row.addressToValidate || Boolean(row.rnbId);

  card.id = `card-${index}`;
  card.dataset.index = index;
  card.classList.add(
    row.isIgnored
      ? 'import-csv-card--ignored'
      : isValid
        ? 'import-csv-card--valid'
        : 'import-csv-card--to-check'
  );

  card.dataset.arreteDenominationSyndic = row.denominationSyndic ?? '';
  card.dataset.arreteIdentifiantParcellaire = row.identifiantParcellaire ?? '';
  card.dataset.arreteNumeroVoie = row.numeroVoie ?? '';
  card.dataset.arreteNomVoie = row.nomVoie ?? '';
  card.dataset.arreteCodePostal = row.codePostal ?? '';
  card.dataset.arreteCommune = row.commune ?? '';
  card.dataset.arreteRnbId = row.rnbId ?? '';
}

function configureAddressInput(clone, row, index) {
  const input = clone.querySelector('[data-arrete-adresse-complete]');
  const label = clone.querySelector('[data-arrete-adresse-complete-label]');
  const helpText = clone.querySelector('[data-arrete-adresse-help]');
  const wrap = input?.closest('.fr-input-wrap');
  const form = clone.querySelector('form');

  if (!input || !form) return;

  const id = `address-complete-${index}`;
  const helpId = `address-help-${index}`;
  form.id = `form-address-${index}`;
  input.id = id;
  input.name = id;
  input.setAttribute('aria-describedby', helpId);
  if (helpText) {
    helpText.id = helpId;
  }
  input.value =
    `${row.numeroVoie ?? ''} ${row.nomVoie ?? ''} ${row.codePostal ?? ''} ${row.commune ?? ''}`.trim();
  label?.setAttribute('for', id);

  const isValid = (!row.addressToValidate || Boolean(row.rnbId)) && !row.isIgnored;
  if (isValid) {
    wrap?.classList.add('fr-icon-check-line');
    clone
      .querySelector('[data-address-container]')
      ?.classList.add('import-csv-card--valid-address');
  } else if (!row.isIgnored) {
    wrap?.classList.add('fr-icon-search-line');
  }

  const suggestions = clone.querySelector('[data-arrete-adresse-suggestions]');
  if (suggestions) {
    suggestions.id = `suggestions-${index}`;
    input.dataset.autocompleteQuerySelector = `#${suggestions.id}`;
  }
}

function configurePickLocalisation(elements, clone, card, row, index) {
  const pickBtn = clone.querySelector('[data-btn-pick-localisation]');
  const sidePanel = elements.sidePanel;
  const successBadge = clone.querySelector('[data-pick-location-success]');
  const rnbIdInput = clone.querySelector('[data-arrete-rnb-id-input]');

  if (row.rnbId) {
    card.dataset.arreteRnbId = row.rnbId;
    if (rnbIdInput) {
      rnbIdInput.value = row.rnbId;
    }
    if (successBadge && !row.isIgnored) {
      successBadge.classList.remove('fr-hidden');
    }
  }

  if (!row.isIgnored) {
    pickBtn?.classList.remove('fr-hidden');
  }

  if (pickBtn) {
    pickBtn.addEventListener('click', () => {
      const errorBadge = card.querySelector('[data-pick-location-error]');
      if (errorBadge) {
        errorBadge.classList.add('fr-hidden');
      }
      if (!sidePanel) return;
      const address =
        `${card.dataset.arreteNumeroVoie ?? ''} ${card.dataset.arreteNomVoie ?? ''}`.trim() ||
        card.querySelector('[data-arrete-adresse-complete]')?.value?.trim() ||
        '';
      const postcode = card.dataset.arreteCodePostal || '';

      sidePanel.dataset.address = address;
      sidePanel.dataset.postcode = postcode;
      sidePanel.dataset.currentCardIndex = String(index);
      if (card.dataset.arreteRnbId) {
        sidePanel.dataset.previousRnbId = card.dataset.arreteRnbId;
      } else {
        delete sidePanel.dataset.previousRnbId;
      }
    });
  }
}

function attachAutocomplete(elements, input) {
  attacheAutocompleteAddressEvent(input);

  input.addEventListener('input', () => {
    const card = input.closest('.import-csv-card');
    const addressContainer = input.closest('[data-address-container]');
    addressContainer?.classList.remove('import-csv-card--valid-address', 'fr-input-group--error');
    addressContainer?.querySelector('[data-arrete-adresse-error]')?.classList.add('fr-hidden');

    const wrap = input.closest('.fr-input-wrap');
    wrap?.classList.remove('fr-icon-check-line');
    wrap?.classList.add('fr-icon-search-line');

    if (card) {
      card.dataset.arreteRnbId = '';
      const rnbIdInput = card.querySelector('[data-arrete-rnb-id-input]');
      if (rnbIdInput) {
        rnbIdInput.value = '';
      }
      const successBadge = card.querySelector('[data-pick-location-success]');
      if (successBadge) {
        successBadge.classList.add('fr-hidden');
      }
      const errorBadge = card.querySelector('[data-pick-location-error]');
      if (errorBadge) {
        errorBadge.classList.add('fr-hidden');
      }

      const pickBtn = card.querySelector('[data-btn-pick-localisation]');
      if (!card.classList.contains('import-csv-card--ignored')) {
        pickBtn?.classList.remove('fr-hidden');
        card.classList.remove('import-csv-card--valid');
        card.classList.add('import-csv-card--to-check');

        const index = card.dataset.index;
        updateRowInData(index, { addressToValidate: true, rnbId: null });
      }
    }
    updateValidationState(elements);
  });

  input.addEventListener('autocompleteAddressSelected', () => {
    const card = input.closest('.import-csv-card');
    if (!card) return;

    const parent = input.parentElement;

    const houseNumber = parent.querySelector('[data-autocomplete-housenumber]')?.value ?? '';
    const street = parent.querySelector('[data-autocomplete-street]')?.value ?? '';
    const postcode = parent.querySelector('[data-autocomplete-codepostal]')?.value ?? '';
    const city = parent.querySelector('[data-autocomplete-ville]')?.value ?? '';
    const currentZip = input.dataset.territoryZip ?? '';

    const addressContainer = input.closest('[data-address-container]');

    if (false === postcode.startsWith(currentZip)) {
      addressContainer?.classList.add('fr-input-group--error');
      addressContainer?.querySelector('[data-arrete-adresse-error]')?.classList.remove('fr-hidden');
      return;
    }

    addressContainer?.classList.add('import-csv-card--valid-address');
    addressContainer?.classList.remove('fr-input-group--error');
    addressContainer?.querySelector('[data-arrete-adresse-error]')?.classList.add('fr-hidden');

    card.dataset.arreteNumeroVoie = houseNumber;
    card.dataset.arreteNomVoie = street;
    card.dataset.arreteCodePostal = postcode;
    card.dataset.arreteCommune = city;
    card.dataset.arreteRnbId = '';

    const rnbIdInput = card.querySelector('[data-arrete-rnb-id-input]');
    if (rnbIdInput) {
      rnbIdInput.value = '';
    }
    const successBadge = card.querySelector('[data-pick-location-success]');
    if (successBadge) {
      successBadge.classList.add('fr-hidden');
    }
    const errorBadge = card.querySelector('[data-pick-location-error]');
    if (errorBadge) {
      errorBadge.classList.add('fr-hidden');
    }

    setText(card, '[data-arrete-adresse-1]', `${houseNumber} ${street}`);
    setText(card, '[data-arrete-adresse-2]', `${postcode} ${city}`);

    card.querySelector('[data-arrete-adresse-suggestions]')?.replaceChildren();

    if (card.classList.contains('import-csv-card--to-check')) {
      card.classList.remove('import-csv-card--to-check');
      card.classList.add('import-csv-card--valid');
    }

    const wrap = input.closest('.fr-input-wrap');
    wrap?.classList.remove('fr-icon-search-line');
    wrap?.classList.add('fr-icon-check-line');

    const index = card.dataset.index;
    updateRowInData(index, {
      numeroVoie: houseNumber,
      nomVoie: street,
      codePostal: postcode,
      commune: city,
      addressToValidate: false,
      rnbId: null,
    });

    updateValidationState(elements);
  });
}

function configureIgnoreCheckbox(elements, clone, card, index) {
  const checkbox = clone.querySelector('[data-arrete-ignore-checkbox]');
  const label = clone.querySelector('[data-arrete-ignore-label]');

  if (!checkbox || !label) return;

  const id = `ignore-${index}`;

  checkbox.id = id;
  checkbox.name = id;
  label.setAttribute('for', id);

  const pickBtn = clone.querySelector('[data-btn-pick-localisation]');
  const successBadge = clone.querySelector('[data-pick-location-success]');
  const errorBadge = clone.querySelector('[data-pick-location-error]');

  if (card.classList.contains('import-csv-card--ignored')) {
    checkbox.checked = true;
    const addressInput = card?.querySelector('[data-arrete-adresse-complete]');
    if (addressInput) {
      addressInput.readOnly = true;
    }
    pickBtn?.classList.add('fr-hidden');
    successBadge?.classList.add('fr-hidden');
    errorBadge?.classList.add('fr-hidden');
  }

  checkbox.addEventListener('change', () => {
    const addressContainer = card?.querySelector('[data-address-container]');
    const addressInput = card?.querySelector('[data-arrete-adresse-complete]');
    const currentPickBtn = card?.querySelector('[data-btn-pick-localisation]');
    const currentSuccessBadge = card?.querySelector('[data-pick-location-success]');
    const currentErrorBadge = card?.querySelector('[data-pick-location-error]');
    const isIgnored = checkbox.checked;
    if (isIgnored) {
      card?.classList.add('import-csv-card--ignored');
      card?.classList.remove('import-csv-card--to-check', 'import-csv-card--valid');
      addressContainer?.classList.remove('import-csv-card--valid-address');
      if (addressInput) {
        addressInput.readOnly = true;
      }
      currentPickBtn?.classList.add('fr-hidden');
      currentSuccessBadge?.classList.add('fr-hidden');
      currentErrorBadge?.classList.add('fr-hidden');
    } else {
      card?.classList.remove('import-csv-card--ignored');
      if (addressInput) {
        addressInput.readOnly = false;
      }
      currentPickBtn?.classList.remove('fr-hidden');
      if (card?.dataset.arreteRnbId) {
        currentSuccessBadge?.classList.remove('fr-hidden');
      }
      const isValidAddress = card
        ?.querySelector('.fr-input-wrap')
        ?.classList.contains('fr-icon-check-line');
      const hasRnbId = Boolean(card?.dataset.arreteRnbId);
      const isValid = isValidAddress || hasRnbId;
      card?.classList.add(isValid ? 'import-csv-card--valid' : 'import-csv-card--to-check');
      if (isValid) {
        addressContainer?.classList.add('import-csv-card--valid-address');
      }
    }

    const cardIndex = card.dataset.index;
    updateRowInData(cardIndex, { isIgnored });

    updateValidationState(elements);
  });
}

function updateRowInData(index, updatedFields) {
  const savedData = sessionStorage.getItem(STORAGE_DATA_KEY);
  if (savedData) {
    try {
      const data = JSON.parse(savedData);
      if (data[index]) {
        data[index] = { ...data[index], ...updatedFields };
        sessionStorage.setItem(STORAGE_DATA_KEY, JSON.stringify(data));
      }
    } catch (error) {
      const errorMessage = 'Error updating row in data';
      console.error(errorMessage, error);
      Sentry.captureException(new Error(errorMessage));
    }
  }
}

function collectRowsToConfirm() {
  return [...document.querySelectorAll('.import-csv-card:not(.import-csv-card--ignored)')].map(
    (card) => {
      const addressInput = card.querySelector('[data-arrete-adresse-complete]');

      return {
        dateArrete: card.querySelector('[data-arrete-date]')?.textContent?.trim() ?? '',
        dateArreteMainLevee:
          card.querySelector('[data-arrete-date-main-levee]')?.textContent?.trim() ?? '',
        classificationArrete:
          card.querySelector('[data-arrete-classification]')?.textContent?.trim() ?? '',
        adresseComplete: addressInput?.value ?? '',
        denominationSyndic: card.dataset.arreteDenominationSyndic ?? '',
        identifiantParcellaire: card.dataset.arreteIdentifiantParcellaire ?? '',
        numeroVoie: card.dataset.arreteNumeroVoie ?? '',
        nomVoie: card.dataset.arreteNomVoie ?? '',
        codePostal: card.dataset.arreteCodePostal ?? '',
        commune: card.dataset.arreteCommune ?? '',
        rnbId:
          card.dataset.arreteRnbId ||
          card.querySelector('[data-arrete-rnb-id-input]')?.value ||
          null,
      };
    }
  );
}

function updateValidationState(elements) {
  const cards = [...document.querySelectorAll('.import-csv-card')];
  const cardsToCheck = cards.filter((card) => {
    return (
      card.classList.contains('import-csv-card--to-check') &&
      !card.classList.contains('import-csv-card--ignored')
    );
  });

  const hasRemainingToConfirm = cardsToCheck.length > 0;

  if (elements.countSpan) {
    elements.countSpan.textContent = String(cardsToCheck.length);
  }

  elements.countContainer?.classList.toggle('fr-display-none', cardsToCheck.length === 0);
  elements.validationBlock?.classList.toggle('import-csv-section--disabled', hasRemainingToConfirm);
  elements.validationBlock?.querySelectorAll('button').forEach((button) => {
    button.disabled = hasRemainingToConfirm;
  });
}

function disableValidation(elements) {
  elements.validationSection?.classList.add('import-csv-section--disabled');
  elements.validationBlock?.classList.add('import-csv-section--disabled');

  elements.validationBlock?.querySelectorAll('button').forEach((button) => {
    button.disabled = true;
  });
}

function setText(root, selector, value) {
  const element = root.querySelector(selector);
  if (element) element.textContent = value ?? '';
}

function sanitizeCity(city) {
  return city ? city.replace(/\s\d{1,2}(?:er|e)\sArrondissement$/i, '').trim() : '';
}

function closeSidePanel(currentSidePanel) {
  currentSidePanel.close();
  document.querySelectorAll(`[data-panel-open="${currentSidePanel.id}"]`).forEach((btn) => {
    btn.setAttribute('aria-expanded', 'false');
  });
}
