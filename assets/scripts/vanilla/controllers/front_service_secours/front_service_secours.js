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

  initUploadPhotos();
}

function initUploadPhotos() {

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

  if (!trigger || !input || !fileList || !hiddenContainer || !uploadUrl) {
    return;
  }

  /** @type {Array<{titre:string,filePath:string}>} */
  let uploadedFiles = [];


  trigger.addEventListener('click', () => {
    input.click();
  });

  input.addEventListener('change', async () => {

    const files = Array.from(input.files || []);

    setButtonsDisabled(true);
    for (const file of files) {
      const formData = new FormData();
      formData.append('signalement[documents]', file);

      try {
        const response = await fetch(uploadUrl, {
          method: 'POST',
          body: formData
        });

        const data = await response.json();
        if (data.error) {
          console.error(data.error);
          continue;
        }

        uploadedFiles.push({
          titre: data.titre,
          filePath: data.filePath
        });

        renderFiles();
        updateHiddenInputs();
      } catch (error) {
        console.error(error);
      }
    }
    input.value = '';
    setButtonsDisabled(false);
  });


  function renderFiles() {
    fileList.innerHTML = '';
    uploadedFiles.forEach((uploadedFile, index) => {
      const row = document.createElement('div');
      row.classList.add('uploaded-file-row');

      const name = document.createElement('span');
      name.textContent = uploadedFile.titre;

      const removeButton = document.createElement('button');
      removeButton.type = 'button';
      removeButton.className = 'fr-link fr-icon-close-circle-line fr-link--icon-left fr-link--error fr-ml-2w';
      removeButton.textContent = 'Supprimer';
      removeButton.setAttribute(
          'aria-label',
          `Supprimer le fichier ${uploadedFile.titre}`
      );

      removeButton.setAttribute(
          'title',
          `Supprimer le fichier ${uploadedFile.titre}`
      );

      removeButton.addEventListener('click', () => {
        uploadedFiles.splice(index, 1);
        renderFiles();
        updateHiddenInputs();
      });
      row.appendChild(name);
      row.appendChild(removeButton);
      fileList.appendChild(row);
    });
  }

  function updateHiddenInputs() {
    hiddenContainer.innerHTML = '';
    uploadedFiles.forEach((file, index) => {
      const inputHidden = document.createElement('input');
      inputHidden.type = 'hidden';
      inputHidden.name = `service_secours[step5][uploadedFiles][${index}]`;
      inputHidden.value = file.filePath;
      hiddenContainer.appendChild(inputHidden);
    });
  }


  function setButtonsDisabled(disabled) {
    const buttonNext = document.getElementById('service_secours_navigator_next');
    const butonPrevious = document.getElementById('service_secours_navigator_previous');
    buttonNext.disabled = disabled;
    butonPrevious.disabled = disabled;
  }
}
