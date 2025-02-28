import { attacheAutocompleteAddressEvent } from '../../services/component_search_address'

function initBoFormSignalementSubmit(tabName) {
  const boFormSignalement = document?.querySelector('#bo-form-signalement-' + tabName)

  boFormSignalement.addEventListener('submit', async (event) => {
    event.preventDefault()
    const formData = new FormData(event.target)
    const submitButton = event.submitter;
    formData.append(submitButton.name, submitButton.value);

    fetch(event.target.action, {method: 'POST', body: formData}).then(response => {
      if (response.ok) {
        response.json().then((response) => {
          if (response.redirect) {
            const currentUrl = window.location.href.split('#')[0];
            const newUrl = response.url.split('#')[0];
            window.location.href = response.url;
            if (currentUrl === newUrl) {
              window.location.reload(true);
            }
          } else {
            document.querySelector("#tabpanel-" +tabName+ "-panel").innerHTML = response.tabContent
            document.querySelector("#tabpanel-" +tabName).scrollIntoView({ behavior: 'smooth' });
            initBoFormSignalementSubmit(tabName)
            if (tabName === 'adresse' && response.hasDuplicates) {
              const modaleDuplicate = document.querySelector('#fr-modal-duplicate')
              const modaleDuplicateContainer = document.querySelector('#fr-modal-duplicate-container')
              const modaleDuplicateOpenLink = document.querySelector('#fr-modal-duplicate-open-duplicates')
              modaleDuplicateContainer.innerHTML = response.duplicateContent
              modaleDuplicateOpenLink.href = response.linkDuplicates
              dsfr(modaleDuplicate).modal.disclose();
            }
          }
        });
      } else {
        const errorHtml = '<div class="fr-alert fr-alert--error" role="alert"><p class="fr-alert__title">Une erreur est survenue lors de la soumission du formulaire, veuillez rafraichir la page.</p></div>';
        document.querySelector("#tabpanel-adresse-panel").innerHTML = errorHtml; 
      }
    })
  })
}

if (document?.querySelector('#bo-form-signalement-adresse')) {
  initBoFormSignalementSubmit('adresse')
  initBoFormSignalementAdresse()
}
if (document?.querySelector('#bo-form-signalement-logement')) {
  initBoFormSignalementSubmit('logement')
  initBoFormSignalementLogement()
}

function initBoFormSignalementAdresse() {
  const inputAdresse = document?.querySelector('#signalement_draft_address_adresseCompleteOccupant')
  const inputForceSave = document?.querySelector('#signalement_draft_address_forceSave')
  attacheAutocompleteAddressEvent(inputAdresse)

  const modaleDuplicateIgnoreButton = document.querySelector('#fr-modal-duplicate-ignore-duplicates')

  const manualAddressSwitcher = document?.querySelector('#bo-signalement-manual-address-switcher')
  const manualAddressContainer = document?.querySelector('#bo-form-signalement-manual-address-container')
  const manualAddressAddress = document?.querySelector('#signalement_draft_address_adresseOccupant')
  const manualAddressInputs = document.querySelectorAll('.bo-form-signalement-manual-address');
  const hasManualAddressValues = Array.from(manualAddressInputs).some(input => input.value !== '')

  manualAddressSwitcher?.addEventListener('click', (event) => {
    event.preventDefault()
    if (manualAddressContainer.classList.contains('fr-hidden')) {
      manualAddressContainer.classList.remove('fr-hidden')
      manualAddressSwitcher.textContent = 'Rechercher une adresse'
      inputAdresse.value = ''
      inputAdresse.disabled = true
      manualAddressAddress.focus()
    } else {
      manualAddressContainer.classList.add('fr-hidden')
      manualAddressSwitcher.textContent = 'Saisir une adresse manuellement'
      manualAddressInputs.forEach(input => input.value = '');
      inputAdresse.disabled = false
      inputAdresse.focus()
    }
  })

  if(inputAdresse.value == '' && hasManualAddressValues) {
    manualAddressSwitcher.click()
  }

  modaleDuplicateIgnoreButton?.addEventListener('click', (event) => {
    inputForceSave.value = 1
    document.querySelector('#signalement_draft_address_save').click()
  });
}

function initBoFormSignalementLogement() {
  const boFormSignalementLogement = document?.querySelector('#bo-form-signalement-logement')
  const natureLogementInputs = boFormSignalementLogement?.querySelectorAll('#signalement_draft_logement_natureLogement input')
  natureLogementInputs.forEach((natureLogementInput) => {
    natureLogementInput.addEventListener('click', (event) => {
      refreshFromNatureLogement()
    })
  })
  refreshFromNatureLogement()

  function refreshFromNatureLogement() {
    let natureLogementValue = ''
    natureLogementInputs.forEach((natureLogementInput) => {
      if (natureLogementInput.checked) {
        natureLogementValue = natureLogementInput.value
      }
    })

    const natureLogementAutre = boFormSignalementLogement?.querySelector('#signalement_draft_logement_natureLogementAutre')
    if (natureLogementValue === 'autre') {
      natureLogementAutre.parentElement.classList.remove('fr-input-group--disabled')
      natureLogementAutre.disabled = false
    } else {
      natureLogementAutre.parentElement.classList.add('fr-input-group--disabled')
      natureLogementAutre.disabled = true
      natureLogementAutre.value = ''
    }

    const appartementEtage = boFormSignalementLogement?.querySelector('#signalement_draft_logement_appartementEtage')
    const appartementEtageInputs = appartementEtage?.querySelectorAll('input')
    const appartementAvecFenetres = boFormSignalementLogement?.querySelector('#signalement_draft_logement_appartementAvecFenetres')
    const appartementAvecFenetresInputs = appartementAvecFenetres?.querySelectorAll('input')
    if (natureLogementValue === 'appartement') {
      appartementEtage?.parentElement.classList.remove('fr-input-group--disabled')
      appartementEtageInputs.forEach((appartementEtageInput) => {
        appartementEtageInput.disabled = false
      })
      appartementAvecFenetres.parentElement.classList.remove('fr-input-group--disabled')
      appartementAvecFenetresInputs.forEach((appartementAvecFenetresInput) => {
        appartementAvecFenetresInput.disabled = false
      })
    } else {
      appartementEtage?.parentElement.classList.add('fr-input-group--disabled')
      appartementEtageInputs.forEach((appartementEtageInput) => {
        appartementEtageInput.checked = false
        appartementEtageInput.disabled = true
      })
      appartementAvecFenetres.parentElement.classList.add('fr-input-group--disabled')
      appartementAvecFenetresInputs.forEach((appartementAvecFenetresInput) => {
        appartementAvecFenetresInput.checked = false
        appartementAvecFenetresInput.disabled = true
      })
    }
  }


  const cuisineInputs = boFormSignalementLogement?.querySelectorAll('#signalement_draft_logement_cuisine input')
  cuisineInputs.forEach((cuisineInput) => {
    cuisineInput.addEventListener('click', (event) => {
      refreshFromCuisineAndToilettes()
    })
  })
  const toilettesInputs = boFormSignalementLogement?.querySelectorAll('#signalement_draft_logement_toilettes input')
  toilettesInputs.forEach((toilettesInput) => {
    toilettesInput.addEventListener('click', (event) => {
      refreshFromCuisineAndToilettes()
    })
  })
  refreshFromCuisineAndToilettes()

  function refreshFromCuisineAndToilettes() {
    let cuisineValue = ''
    cuisineInputs.forEach((cuisineInput) => {
      if (cuisineInput.checked) {
        cuisineValue = cuisineInput.value
      }
    })
    let toilettesValue = ''
    toilettesInputs.forEach((toilettesInput) => {
      if (toilettesInput.checked) {
        toilettesValue = toilettesInput.value
      }
    })

    const toilettesCuisineMemePiece = boFormSignalementLogement?.querySelector('#signalement_draft_logement_toilettesCuisineMemePiece')
    const toilettesCuisineMemePieceInputs = toilettesCuisineMemePiece?.querySelectorAll('input')
    if (cuisineValue === 'oui' && toilettesValue === 'oui') {
      toilettesCuisineMemePiece.parentElement.classList.remove('fr-input-group--disabled')
      toilettesCuisineMemePieceInputs.forEach((toilettesCuisineMemePieceInput) => {
        toilettesCuisineMemePieceInput.disabled = false
      })

    } else {
      toilettesCuisineMemePiece.parentElement.classList.add('fr-input-group--disabled')
      toilettesCuisineMemePieceInputs.forEach((toilettesCuisineMemePieceInput) => {
        toilettesCuisineMemePieceInput.checked = false
        toilettesCuisineMemePieceInput.disabled = true
      })
    }
  }
}
