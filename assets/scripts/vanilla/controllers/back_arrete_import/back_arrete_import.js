import { attacheAutocompleteAddressEvent } from '../../services/component/component_search_address';

const STORAGE_DATA_KEY = 'import_arrete_data';
const STORAGE_FILENAME_KEY = 'import_arrete_filename';
const STORAGE_EXPIRATION_KEY = 'import_arrete_expiration';
const STORAGE_DURATION_MS = 60 * 60 * 1000; // 1 hour in milliseconds

const root = document.querySelector('#import-arrete-container');

if (root) {
  const elements = getElements(root);

  if (elements.fileInput && elements.form) {
    bindEvents(elements);
    restoreFromLocalStorage(elements);
  }
}

function restoreFromLocalStorage(elements) {
  const savedData = localStorage.getItem(STORAGE_DATA_KEY);
  const savedFileName = localStorage.getItem(STORAGE_FILENAME_KEY);
  const expiration = Number(localStorage.getItem(STORAGE_EXPIRATION_KEY));

  if (!expiration || Date.now() >= expiration) {
    clearStoredImport();
    return;
  }

  if (savedData) {
    try {
      const data = JSON.parse(savedData);
      showSuccess(elements, savedFileName);
      renderCards(elements, data);
      updateValidationState(elements);
    } catch (e) {
      console.error('Error restoring data from localStorage', e);
      clearStoredImport();
    }
  }
}

function clearStoredImport() {
  localStorage.removeItem(STORAGE_DATA_KEY);
  localStorage.removeItem(STORAGE_FILENAME_KEY);
  localStorage.removeItem(STORAGE_EXPIRATION_KEY);
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

    if (payload.errors?.length && !payload.data?.length) {
      showErrors(elements, payload.errors);
      return;
    }

    if (payload.data?.length) {
      localStorage.setItem(STORAGE_DATA_KEY, JSON.stringify(payload.data));
      localStorage.setItem(STORAGE_EXPIRATION_KEY, String(Date.now() + STORAGE_DURATION_MS));
      if (elements.fileInput.files.length > 0) {
        localStorage.setItem(STORAGE_FILENAME_KEY, elements.fileInput.files[0].name);
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
  try {
    const rows = collectRowsToConfirm();

    if (!rows.length) {
      showErrors(elements, ['Aucune ligne à valider.']);
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
      return;
    }

    clearStoredImport();
    window.location.href = elements.root.dataset.urlRedirectionArreteList;
  } catch (error) {
    console.error('Error during import confirmation:', error);
    showErrors(elements, ['Une erreur est survenue.']);
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

  card.id = `card-${index}`;
  card.dataset.index = index;
  card.classList.add(
    row.isIgnored
      ? 'import-csv-card--ignored'
      : row.addressToValidate
        ? 'import-csv-card--to-check'
        : 'import-csv-card--valid'
  );

  card.dataset.arreteDenominationSyndic = row.denominationSyndic ?? '';
  card.dataset.arreteIdentifiantParcellaire = row.identifiantParcellaire ?? '';
  card.dataset.arreteNumeroVoie = row.numeroVoie ?? '';
  card.dataset.arreteNomVoie = row.nomVoie ?? '';
  card.dataset.arreteCodePostal = row.codePostal ?? '';
  card.dataset.arreteCommune = row.commune ?? '';
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

  if (!row.addressToValidate || row.isIgnored) {
    wrap?.classList.add('fr-icon-check-line');
    if (!row.isIgnored) {
      clone
        .querySelector('[data-address-container]')
        ?.classList.add('import-csv-card--valid-address');
    }
  } else {
    wrap?.classList.add('fr-icon-search-line');
  }

  const suggestions = clone.querySelector('[data-arrete-adresse-suggestions]');
  if (suggestions) {
    suggestions.id = `suggestions-${index}`;
    input.dataset.autocompleteQuerySelector = `#${suggestions.id}`;
  }
}

function attachAutocomplete(elements, input) {
  attacheAutocompleteAddressEvent(input);

  input.addEventListener('input', () => {
    const addressContainer = input.closest('[data-address-container]');
    addressContainer?.classList.remove('import-csv-card--valid-address', 'fr-input-group--error');
    addressContainer?.querySelector('[data-arrete-adresse-error]')?.classList.add('fr-hidden');

    const wrap = input.closest('.fr-input-wrap');
    wrap?.classList.remove('fr-icon-check-line');
    wrap?.classList.add('fr-icon-search-line');

    const card = input.closest('.import-csv-card');
    if (card && !card.classList.contains('import-csv-card--ignored')) {
      card.classList.remove('import-csv-card--valid');
      card.classList.add('import-csv-card--to-check');

      const index = card.dataset.index;
      updateRowInData(index, { addressToValidate: true });
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

  if (card.classList.contains('import-csv-card--ignored')) {
    checkbox.checked = true;
    const addressInput = card?.querySelector('[data-arrete-adresse-complete]');
    if (addressInput) {
      addressInput.readOnly = true;
    }
  }

  checkbox.addEventListener('change', () => {
    const addressContainer = card?.querySelector('[data-address-container]');
    const addressInput = card?.querySelector('[data-arrete-adresse-complete]');
    const isIgnored = checkbox.checked;
    if (isIgnored) {
      card?.classList.add('import-csv-card--ignored');
      card?.classList.remove('import-csv-card--to-check', 'import-csv-card--valid');
      addressContainer?.classList.remove('import-csv-card--valid-address');
      if (addressInput) {
        addressInput.readOnly = true;
      }
    } else {
      card?.classList.remove('import-csv-card--ignored');
      if (addressInput) {
        addressInput.readOnly = false;
      }
      const isValidAddress = card
        ?.querySelector('.fr-input-wrap')
        ?.classList.contains('fr-icon-check-line');
      card?.classList.add(isValidAddress ? 'import-csv-card--valid' : 'import-csv-card--to-check');
      if (isValidAddress) {
        addressContainer?.classList.add('import-csv-card--valid-address');
      }
    }

    const cardIndex = card.dataset.index;
    updateRowInData(cardIndex, { isIgnored });

    updateValidationState(elements);
  });
}

function updateRowInData(index, updatedFields) {
  const savedData = localStorage.getItem(STORAGE_DATA_KEY);
  if (savedData) {
    try {
      const data = JSON.parse(savedData);
      if (data[index]) {
        data[index] = { ...data[index], ...updatedFields };
        localStorage.setItem(STORAGE_DATA_KEY, JSON.stringify(data));
      }
    } catch (e) {
      console.error('Error updating row in data', e);
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
