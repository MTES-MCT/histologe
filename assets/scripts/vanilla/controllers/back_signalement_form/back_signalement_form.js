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

function initRefreshFromRadio(tabName, radioName, listElements) {
  const boFormSignalementTab = document?.querySelector('#bo-form-signalement-' + tabName)

  const radioInputs = boFormSignalementTab?.querySelectorAll('#' +radioName+ ' input')
  radioInputs.forEach((radioInput) => {
    radioInput.addEventListener('click', (event) => {
      refreshFromRadioInput()
    })
  })
  refreshFromRadioInput()

  function refreshFromRadioInput() {
    let radioInputValue = ''
    radioInputs.forEach((radioInput) => {
      if (radioInput.checked) {
        radioInputValue = radioInput.value
      }
    })

    listElements.forEach((elementSelector) => {
      refreshElementEnable(tabName, elementSelector, (radioInputValue === 'oui'))
    })
  }
}

function refreshElementEnable(tabName, elementSelector, isEnabled) {
  const boFormSignalementTab = document?.querySelector('#bo-form-signalement-' + tabName)
  const elementSelected = boFormSignalementTab?.querySelector(elementSelector)

  if (isEnabled) {
    elementSelected.parentElement.classList.remove('fr-input-group--disabled')
    elementSelected.disabled = false
    const elementSelectedInputs = elementSelected?.querySelectorAll('input')
    if (elementSelectedInputs) {
      elementSelectedInputs.forEach((elementSelectedInput) => {
        elementSelectedInput.disabled = false
      })
    }

  } else {
    elementSelected.parentElement.classList.add('fr-input-group--disabled')
    if (elementSelected.value) {
      elementSelected.value = ''
    }
    elementSelected.disabled = true
    const elementSelectedInputs = elementSelected?.querySelectorAll('input')
    elementSelectedInputs.forEach((elementSelectedInput) => {
      elementSelectedInput.checked = false
      elementSelectedInput.disabled = true
    })
  }
}

if (document?.querySelector('#bo-form-signalement-adresse')) {
  initBoFormSignalementSubmit('adresse')
  initBoFormSignalementAdresse()
}
if (document?.querySelector('#bo-form-signalement-logement')) {
  initBoFormSignalementSubmit('logement')
  initBoFormSignalementLogement()
}
if (document?.querySelector('#bo-form-signalement-situation')) {
  initBoFormSignalementSubmit('situation')
  initBoFormSignalementSituation()
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

    refreshElementEnable('logement', '#signalement_draft_logement_natureLogementAutre', (natureLogementValue === 'autre'))
    refreshElementEnable('logement', '#signalement_draft_logement_appartementEtage', (natureLogementValue === 'appartement'))
    refreshElementEnable('logement', '#signalement_draft_logement_appartementAvecFenetres', (natureLogementValue === 'appartement'))
  }

  const compositionLogementInputs = boFormSignalementLogement?.querySelectorAll('#signalement_draft_logement_pieceUnique input')
  compositionLogementInputs.forEach((compositionLogementInput) => {
    compositionLogementInput.addEventListener('click', (event) => {
      refreshFromCompositionLogement()
    })
  })
  refreshFromCompositionLogement()

  function refreshFromCompositionLogement() {
    let compositionLogementValue = ''
    compositionLogementInputs.forEach((compositionLogementInput) => {
      if (compositionLogementInput.checked) {
        compositionLogementValue = compositionLogementInput.value
      }
    })

    refreshElementEnable('logement', '#signalement_draft_logement_nombrePieces', (compositionLogementValue === 'plusieurs_pieces'))
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

    refreshElementEnable('logement', '#signalement_draft_logement_toilettesCuisineMemePiece', (cuisineValue === 'oui' && toilettesValue === 'oui'))
  }
}

function initBoFormSignalementSituation() {
  initRefreshFromRadio(
    'situation',
    'signalement_draft_situation_allocataire',
    [
      '#signalement_draft_situation_caisseAllocation',
      '#signalement_draft_situation_dateNaissanceAllocataire',
      '#signalement_draft_situation_numeroAllocataire',
      '#signalement_draft_situation_typeAllocation',
      '#signalement_draft_situation_montantAllocation',
    ]
  )
  initRefreshFromRadio(
    'situation',
    'signalement_draft_situation_proprietaireAverti',
    [
      '#signalement_draft_situation_dateProprietaireAverti',
      '#signalement_draft_situation_moyenInformationProprietaire',
      '#signalement_draft_situation_reponseProprietaire',
    ]
  )
  initRefreshFromRadio(
    'situation',
    'signalement_draft_situation_logementAssure',
    [
      '#signalement_draft_situation_assuranceContactee',
      '#signalement_draft_situation_reponseAssurance',
    ]
  )
}
