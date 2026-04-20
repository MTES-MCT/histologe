import {
  attacheAutocompleteAddressEvent,
  initComponentAddress,
} from '../../services/component/component_search_address';

import axios from 'axios';

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
  const natureLogementRadios = document.querySelectorAll(
    'input[name="service_secours[step2][natureLogement]"]'
  );
  const typeEtageLogementAppartementContainer = document.querySelector(
    '.type-etage-logement-appartement-container'
  );
  const natureLogementAutreContainer = document.querySelector('.nature-logement-autre-container');

  // Vérifier l'état initial au chargement de la page
  const natureLogementChecked = document.querySelector(
    'input[name="service_secours[step2][natureLogement]"]:checked'
  );
  if (natureLogementChecked) {
    if (natureLogementChecked.value === 'appartement') {
      typeEtageLogementAppartementContainer.classList.remove('fr-hidden');
    }
    if (natureLogementChecked.value === 'autre') {
      natureLogementAutreContainer.classList.remove('fr-hidden');
    }
  }

  natureLogementRadios.forEach((radio) => {
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
  const typeEtageLogementRadios = document.querySelectorAll(
    'input[name="service_secours[step2][typeEtageLogement]"]'
  );
  const etageOccupantContainer = document.querySelector('.etage-occupant-container');

  // Vérifier l'état initial au chargement de la page
  const typeEtageLogementChecked = document.querySelector(
    'input[name="service_secours[step2][typeEtageLogement]"]:checked'
  );
  if (typeEtageLogementChecked && typeEtageLogementChecked.value === 'AUTRE') {
    etageOccupantContainer.classList.remove('fr-hidden');
  }

  typeEtageLogementRadios.forEach((radio) => {
    radio.addEventListener('change', (event) => {
      if (event.target.value === 'AUTRE') {
        etageOccupantContainer.classList.remove('fr-hidden');
      } else {
        etageOccupantContainer.classList.add('fr-hidden');
      }
    });
  });

  initDesordresAutreToggle();
  initUploadPhotos();
  initPickLocalisationButton();
}

function initPickLocalisationButton() {
  const adresseInput = document.querySelector('#service_secours_step2_adresseOccupant');
  const cpInput = document.querySelector('#service_secours_step2_cpOccupant');
  const formRnbIdInput = document.getElementById('service_secours_step2_rnbId');
  const pickButton = document.querySelector('.btn-pick-localisation');
  const modal = document.querySelector('#fr-modal-pick-localisation');
  const pickLocationSuccess = document.getElementById('pick-location-success');

  if (!adresseInput || !cpInput || !pickButton || !modal) {
    return;
  }

  function updatePickButton() {
    const address = adresseInput.value.trim();
    const postcode = cpInput.value.trim();
    const rnbId = formRnbIdInput ? formRnbIdInput.value.trim() : null;
    if (address && postcode) {
      pickButton.classList.remove('fr-hidden');
      modal.setAttribute('data-address', address);
      modal.setAttribute('data-postcode', postcode);
      if (pickLocationSuccess && rnbId) {
        pickLocationSuccess.classList.remove('fr-hidden');
      }
    } else {
      pickButton.classList.add('fr-hidden');
      if (pickLocationSuccess) {
        pickLocationSuccess.classList.add('fr-hidden');
      }
    }
  }

  adresseInput.addEventListener('input', updatePickButton);
  cpInput.addEventListener('input', updatePickButton);

  updatePickButton();

  const submitBtn = document.getElementById('fr-modal-pick-localisation-submit');
  if (submitBtn) {
    submitBtn.addEventListener('click', () => {
      const clickedBatRnbId = document.getElementById('fr-modal-pick-localisation-rnb-id').value;
      const modalPickLocalisation = document.getElementById('fr-modal-pick-localisation');

      if (clickedBatRnbId && formRnbIdInput) {
        formRnbIdInput.value = clickedBatRnbId;
        if (pickLocationSuccess) {
          pickLocationSuccess.classList.remove('fr-hidden');
        }
      }
      if (modalPickLocalisation) {
        dsfr(modalPickLocalisation).modal.conceal();
      }
    });
  }
}

function initUploadPhotos() {
  const ALLOWED_MIME_TYPES = ['image/jpeg', 'image/png', 'image/gif', 'application/pdf'];

  const MAX_FILE_SIZE_BYTES = 10 * 1024 * 1024; // 10 MB

  /** @type {HTMLElement|null} */
  const wrapper = document.querySelector('.js-upload-photos');

  if (!wrapper) {
    return;
  }

  /** @type {HTMLButtonElement|null} */
  const trigger = wrapper.querySelector('[data-upload-photos-trigger]');

  /** @type {HTMLInputElement|null} */
  const input = wrapper.querySelector('[data-upload-photos-input]');

  /** @type {HTMLElement|null} */
  const fileList = wrapper.querySelector('[data-upload-photos-list]');

  /** @type {HTMLElement|null} */
  const hiddenContainer = wrapper.querySelector('[data-upload-photos-hidden-container]');

  const uploadUrl = wrapper.dataset.ajaxurlHandleUpload;

  const uploadedFilesElement = wrapper.querySelector('[data-uploaded-files]');
  const existingUploadedFilesRaw = uploadedFilesElement
    ? uploadedFilesElement.dataset.uploadedFiles
    : null;

  if (!trigger || !input || !fileList || !hiddenContainer || !uploadUrl) {
    return;
  }

  /**
   * @type {Array<{
   *   titre: string,
   *   file: string,
   *   filePath: string|null,
   *   progress: number,
   *   status: 'uploading'|'uploaded'|'error',
   *   errorMessage?: string
   * }>}
   */
  let uploadedFiles = [];

  if (existingUploadedFilesRaw) {
    try {
      const parsedFiles = JSON.parse(existingUploadedFilesRaw);

      if (Array.isArray(parsedFiles)) {
        uploadedFiles = parsedFiles
          .map((file) => {
            if (!file) {
              return null;
            }

            if (typeof file === 'string') {
              try {
                const parsedFile = JSON.parse(file);

                if (
                  parsedFile &&
                  typeof parsedFile.titre === 'string' &&
                  typeof parsedFile.filePath === 'string' &&
                  typeof parsedFile.file === 'string'
                ) {
                  return {
                    titre: parsedFile.titre,
                    file: parsedFile.file,
                    filePath: parsedFile.filePath,
                    progress: 100,
                    status: 'uploaded',
                  };
                }
              } catch (error) {
                console.error('Impossible de parser un fichier encodé en JSON', error);
              }

              return null;
            }

            if (
              typeof file === 'object' &&
              typeof file.file === 'string' &&
              typeof file.titre === 'string' &&
              typeof file.filePath === 'string'
            ) {
              return {
                titre: file.titre,
                file: file.file,
                filePath: file.filePath,
                progress: 100,
                status: 'uploaded',
              };
            }

            return null;
          })
          .filter((file) => file && file.filePath);

        renderFiles();
        updateHiddenInputs();
      }
    } catch (error) {
      console.error('Impossible de parser les fichiers existants', error);
    }
  }

  trigger.addEventListener('click', () => {
    input.click();
  });

  input.addEventListener('change', async () => {
    uploadedFiles = uploadedFiles.filter((file) => file.status !== 'error');
    const files = Array.from(input.files || []);

    if (files.length === 0) {
      return;
    }

    setButtonsDisabled(true);

    try {
      for (const file of files) {
        const validationError = validateFile(file);

        if (validationError) {
          uploadedFiles.push({
            titre: file.name,
            file: null,
            filePath: null,
            progress: 0,
            status: 'error',
            errorMessage: validationError,
          });

          renderFiles();
          continue;
        }
        const fileIndex =
          uploadedFiles.push({
            titre: file.name,
            file: null,
            filePath: null,
            progress: 0,
            status: 'uploading',
          }) - 1;

        renderFiles();

        const formData = new FormData();
        formData.append('signalement[documents]', file);

        try {
          const response = await axios.post(uploadUrl, formData, {
            onUploadProgress: (progressEvent) => {
              if (!progressEvent.total) {
                return;
              }

              uploadedFiles[fileIndex].progress = Math.round(
                (progressEvent.loaded * 100) / progressEvent.total
              );
              renderFiles();
            },
          });

          const data = response.data;
          if (data.error) {
            uploadedFiles[fileIndex].status = 'error';
            uploadedFiles[fileIndex].errorMessage = data.error;
            renderFiles();
            continue;
          }

          uploadedFiles[fileIndex] = {
            titre: data.titre,
            filePath: data.filePath,
            file: data.file,
            progress: 100,
            status: 'uploaded',
          };

          renderFiles();
          updateHiddenInputs();
        } catch (error) {
          console.error(error);

          uploadedFiles[fileIndex].status = 'error';
          uploadedFiles[fileIndex].errorMessage =
            error.response?.data?.error || 'Erreur lors du téléversement.';
          renderFiles();
        }
      }
    } finally {
      input.value = '';
      setButtonsDisabled(false);
    }
  });

  function renderFiles() {
    fileList.innerHTML = '';
    uploadedFiles.forEach((uploadedFile, index) => {
      const row = document.createElement('div');
      row.className = 'fr-grid-row fr-grid-row--middle fr-mb-1w';
      if (uploadedFile.status === 'error') {
        row.classList.add('row-error');
      }

      const colFilename = document.createElement('div');
      colFilename.className = 'fr-col';

      const colAction = document.createElement('div');
      colAction.className = 'fr-col-auto';

      const filenameContainer = document.createElement('div');

      if (uploadedFile.status !== 'error') {
        const titleElement = document.createElement('div');
        titleElement.textContent = uploadedFile.titre;
        filenameContainer.appendChild(titleElement);
      }

      if (uploadedFile.status === 'uploading') {
        const progressText = document.createElement('div');
        progressText.className = 'fr-hint-text fr-mt-1v';
        progressText.textContent = `Téléversement… ${uploadedFile.progress}%`;

        const progress = document.createElement('progress');
        progress.className = 'fr-mt-1v';
        progress.max = 100;
        progress.value = uploadedFile.progress;

        filenameContainer.appendChild(progressText);
        filenameContainer.appendChild(progress);
      }

      if (uploadedFile.status === 'error') {
        const error = document.createElement('p');
        error.className = 'fr-error-text fr-mt-1v';
        error.textContent = uploadedFile.errorMessage || 'Erreur lors du téléversement.';
        filenameContainer.appendChild(error);
      }

      colFilename.appendChild(filenameContainer);

      if (uploadedFile.status === 'uploaded') {
        const removeButton = document.createElement('button');
        removeButton.type = 'button';
        removeButton.className =
          'fr-link fr-icon-close-circle-line fr-link--icon-left fr-link--error';
        removeButton.textContent = 'Supprimer';

        removeButton.setAttribute('aria-label', `Supprimer le fichier ${uploadedFile.titre}`);

        removeButton.setAttribute('title', `Supprimer le fichier ${uploadedFile.titre}`);

        removeButton.addEventListener('click', () => {
          uploadedFiles.splice(index, 1);
          uploadedFiles = uploadedFiles.filter((file) => file.status !== 'error');
          renderFiles();
          updateHiddenInputs();
        });

        colAction.appendChild(removeButton);
      }

      row.appendChild(colFilename);
      row.appendChild(colAction);

      fileList.appendChild(row);
    });
  }

  function updateHiddenInputs() {
    hiddenContainer.innerHTML = '';

    uploadedFiles
      .filter((file) => file.status === 'uploaded' && file.filePath)
      .forEach((file, index) => {
        const inputHidden = document.createElement('input');
        inputHidden.type = 'hidden';
        inputHidden.name = `service_secours[step5][uploadedFiles][${index}]`;
        inputHidden.value = JSON.stringify({
          titre: file.titre,
          filePath: file.filePath,
          file: file.file,
        });
        hiddenContainer.appendChild(inputHidden);
      });
  }

  function setButtonsDisabled(disabled) {
    const buttonNextReview = document.getElementById('service_secours_navigator_nextReview');
    const buttonPrevious = document.getElementById('service_secours_navigator_previous');

    if (buttonNextReview) {
      buttonNextReview.disabled = disabled;
    }

    if (buttonPrevious) {
      buttonPrevious.disabled = disabled;
    }
  }

  function validateFile(file) {
    if (!ALLOWED_MIME_TYPES.includes(file.type)) {
      return `Le fichier "${file.name}" n'est pas pris en charge. Formats autorisés : JPEG, PNG, GIF, PDF.`;
    }

    if (file.size > MAX_FILE_SIZE_BYTES) {
      return `Le fichier "${file.name}" dépasse la taille maximale autorisée de 10 Mo.`;
    }

    return null;
  }
}

function initDesordresAutreToggle() {
  const autreCheckbox = document.querySelector(
    'input[name="service_secours[step5][desordres][]"][value="desordres_service_secours_autre"]'
  );

  const autreContainer = document.querySelector('#desordres-autre-wrapper');
  const autreTextarea = document.querySelector(
    'textarea[name="service_secours[step5][desordresAutre]"]'
  );

  if (!autreCheckbox || !autreContainer) {
    return;
  }

  const toggleAutreField = () => {
    if (autreCheckbox.checked) {
      autreContainer.classList.remove('fr-hidden');
    } else {
      autreContainer.classList.add('fr-hidden');
      autreTextarea.value = '';
    }
  };

  autreCheckbox.addEventListener('change', toggleAutreField);

  // état initial au chargement
  toggleAutreField();
}

//step4
const showInformationsSyndicContainer = document.querySelector(
  '#show-informations-syndic-container'
);
const informationsSyndicContainer = document.querySelector('#informations-syndic-container');
if (showInformationsSyndicContainer && informationsSyndicContainer) {
  showInformationsSyndicContainer.addEventListener('click', (event) => {
    event.preventDefault();
    informationsSyndicContainer.classList.remove('fr-hidden');
    showInformationsSyndicContainer.classList.add('fr-hidden');
  });
  const denominationSyndic = document.querySelector('#service_secours_step4_denominationSyndic');
  const nomSyndic = document.querySelector('#service_secours_step4_nomSyndic');
  const mailSyndic = document.querySelector('#service_secours_step4_mailSyndic');
  const telSyndic = document.querySelector('#service_secours_step4_telSyndic_input');
  const telSyndicSecondaire = document.querySelector(
    '#service_secours_step4_telSyndicSecondaire_input'
  );
  if (
    denominationSyndic.value ||
    nomSyndic.value ||
    mailSyndic.value ||
    telSyndic.value ||
    telSyndicSecondaire.value
  ) {
    informationsSyndicContainer.classList.remove('fr-hidden');
    showInformationsSyndicContainer.classList.add('fr-hidden');
  }
}
