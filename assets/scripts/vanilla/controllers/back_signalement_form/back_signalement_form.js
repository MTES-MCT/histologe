import { attacheAutocompleteAddressEvent } from '../../services/component_search_address'

let boFormSignalementCurrentTabIsDirty = false
let boFormSignalementTargetTab = ''

const modaleDuplicateIgnoreButton = document.querySelector('#fr-modal-duplicate-ignore-duplicates')
if(modaleDuplicateIgnoreButton) {
  modaleDuplicateIgnoreButton?.addEventListener('click', (event) => {
    const inputForceSave = document?.querySelector('#signalement_draft_address_forceSave')
    inputForceSave.value = 1
    saveCurrentTab(event)
  });
}

const tabButtons = document?.querySelectorAll('ul.fr-tabs__list.fr-tabs__list--bo-create button')
tabButtons.forEach((tabButton) => {
  tabButton.addEventListener('click', (event) => {
    if (boFormSignalementCurrentTabIsDirty) {
      event.stopImmediatePropagation()
      boFormSignalementTargetTab = event.target.id.substring(9)
      saveCurrentTab(event)
    }
  })
})

function saveCurrentTab(event) {
  const currentTab = document?.querySelector('.fr-tabs__panel.fr-tabs__panel--selected')
  currentTab.classList.add('fr-tabs__panel--saving')
  const currentTabName = currentTab.id.substring(9, currentTab.id.length - 6)

  let formData = null
  let formAction = null
  if (event.type === 'submit') {
    formData = new FormData(event.target)
    formData.append(event.submitter.name, event.submitter.value)
    formAction = event.target.action
  }
  if (event.type === 'click') {
    const currentTabForm = currentTab?.querySelector('form#bo-form-signalement-' + currentTabName)
    formData = new FormData(currentTabForm)
    formAction = currentTabForm.action
  }

  fetch(formAction, {method: 'POST', body: formData}).then(response => {
    if (response.ok) {
      response.json().then((response) => {
        currentTab.classList.remove('fr-tabs__panel--saving')
        
        document.querySelector('#tabpanel-' +currentTabName+ '-panel').innerHTML = response.tabContent
        document.querySelector('#tabpanel-' +currentTabName).scrollIntoView({ behavior: 'smooth' });

        if (response.redirect) {
          boFormSignalementCurrentTabIsDirty = false

          if (response.url === undefined || response.url === '') {
            const targetTabButton = document?.querySelector('#tabpanel-' + boFormSignalementTargetTab)
            boFormSignalementTargetTab = ''
            targetTabButton.click()
          } else {
            window.location.href = response.url;
          }

        } else {
          if (currentTabName === 'adresse' && response.hasDuplicates) {
            const modaleDuplicate = document.querySelector('#fr-modal-duplicate')
            const modaleDuplicateContainer = document.querySelector('#fr-modal-duplicate-container')
            const modaleDuplicateOpenLink = document.querySelector('#fr-modal-duplicate-open-duplicates')
            modaleDuplicateContainer.innerHTML = response.duplicateContent
            modaleDuplicateOpenLink.href = response.linkDuplicates
            dsfr(modaleDuplicate).modal.disclose();
          } else {
            const errorAlertStr = '<div class="fr-alert fr-alert--sm fr-alert--error fr-mb-2v" role="alert"><p class="fr-alert__title">Merci de corriger les champs où des erreurs sont signalées.</p></div>'
            document.querySelector('#tabpanel-' +currentTabName+ '-panel').innerHTML = errorAlertStr + document.querySelector('#tabpanel-' +currentTabName+ '-panel').innerHTML
          }
        }
        initBoFormSignalementSubmit(currentTabName)
      });
    } else {
      const errorHtml = '<div class="fr-alert fr-alert--sm fr-alert--error" role="alert"><p class="fr-alert__title">Une erreur est survenue lors de la soumission du formulaire, veuillez rafraichir la page.</p></div>';
      document.querySelector('#tabpanel-' +currentTabName+ '-panel').innerHTML = errorHtml; 
    }
  })
}

function initBoFormSignalementSubmit(tabName) {
  const boFormSignalementTab = document?.querySelector('#bo-form-signalement-' + tabName)

  boFormSignalementTab.addEventListener('submit', async (event) => {
    event.preventDefault()
    boFormSignalementTargetTab = event.submitter.getAttribute('data-target')
    saveCurrentTab(event)
  })

  const tabInputs = boFormSignalementTab?.querySelectorAll('input')
  tabInputs.forEach((tabInput) => {
    tabInput.addEventListener('click', (event) => {
      if (tabInput.type == 'radio') {
        boFormSignalementCurrentTabIsDirty = true
      }
    })
    tabInput.addEventListener('change', (event) => {
      boFormSignalementCurrentTabIsDirty = true
    })
  })
  const tabSelects = boFormSignalementTab?.querySelectorAll('select')
  tabSelects.forEach((tabSelect) => {
    tabSelect.addEventListener('change', (event) => {
      boFormSignalementCurrentTabIsDirty = true
    })
  })
  const tabTextAreas = boFormSignalementTab?.querySelectorAll('textarea')
  tabTextAreas.forEach((tabTextArea) => {
    tabTextArea.addEventListener('change', (event) => {
      boFormSignalementCurrentTabIsDirty = true
    })
  })

  switch (tabName) {
    case 'adresse':
      break
    case 'logement':
      initBoFormSignalementLogement()
      break
    case 'situation':
      initBoFormSignalementSituation()
      break
    case 'coordonnees':
      initBoFormSignalementCoordonnees()
      break
  }
}

function initRefreshFromRadio(tabName, radioName, listElementsToEnable, valueToEnable = undefined, listElementsToHide = []) {
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

    listElementsToEnable.forEach((elementSelector) => {
      refreshElementEnable('enable', tabName, elementSelector, (radioInputValue === 'oui' || radioInputValue === '1' || (valueToEnable !== undefined && radioInputValue === valueToEnable)))
    })

    listElementsToHide.forEach((elementSelector) => {
      refreshElementEnable('show', tabName, elementSelector, (radioInputValue === 'oui' || radioInputValue === '1' || (valueToEnable !== undefined && radioInputValue === valueToEnable)))
    })
  }
}

function refreshElementEnable(action, tabName, elementSelector, isEnabled) {
  const boFormSignalementTab = document?.querySelector('#bo-form-signalement-' + tabName)
  const elementSelected = boFormSignalementTab?.querySelector(elementSelector)

  if (isEnabled) {
    if (action === 'show') {
      elementSelected.classList.remove('fr-display-none')

    } else {
      elementSelected.parentElement.classList.remove('fr-input-group--disabled')
      elementSelected.disabled = false
      const elementSelectedInputs = elementSelected?.querySelectorAll('input')
      if (elementSelectedInputs) {
        elementSelectedInputs.forEach((elementSelectedInput) => {
          elementSelectedInput.disabled = false
        })
      }
    }

  } else {
    if (action === 'show') {
      elementSelected.classList.add('fr-display-none')

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
}

initComponentsAdress()

if (document?.querySelector('#bo-form-signalement-adresse')) {
  initBoFormSignalementSubmit('adresse')
}
if (document?.querySelector('#bo-form-signalement-logement')) {
  initBoFormSignalementSubmit('logement')
}
if (document?.querySelector('#bo-form-signalement-situation')) {
  initBoFormSignalementSubmit('situation')
}
if (document?.querySelector('#bo-form-signalement-coordonnees')) {
  initBoFormSignalementSubmit('coordonnees')
}

function initComponentsAdress() {
  const addressInputs = document.querySelectorAll('[data-fr-adresse-autocomplete]')
  addressInputs.forEach((addressInput) => {
    attacheAutocompleteAddressEvent(addressInput)
  
    const addressInputParent = addressInput.parentElement.parentElement.parentElement
    const manualAddressSwitcher = addressInputParent?.querySelector('.bo-signalement-manual-address-switcher')
    const manualAddressContainer = addressInputParent?.querySelector('.bo-form-signalement-manual-address-container')
    const manualAddressAddress = addressInputParent?.querySelector('.bo-form-signalement-manual-address-input')
    const manualAddressInputs = addressInputParent?.querySelectorAll('.bo-form-signalement-manual-address');
    const hasManualAddressValues = Array.from(manualAddressInputs).some(input => input.value !== '')
  
    manualAddressSwitcher?.addEventListener('click', (event) => {
      event.preventDefault()
      if (manualAddressContainer.classList.contains('fr-hidden')) {
        manualAddressContainer.classList.remove('fr-hidden')
        manualAddressSwitcher.textContent = 'Rechercher une adresse'
        addressInput.value = ''
        addressInput.disabled = true
        manualAddressAddress.focus()
      } else {
        manualAddressContainer.classList.add('fr-hidden')
        manualAddressSwitcher.textContent = 'Saisir une adresse manuellement'
        manualAddressInputs.forEach(input => input.value = '');
        addressInput.disabled = false
        addressInput.focus()
      }
    })
  
    if(addressInput.value == '' && hasManualAddressValues) {
      manualAddressSwitcher.click()
    }
  })
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

    refreshElementEnable('enable', 'logement', '#signalement_draft_logement_natureLogementAutre', (natureLogementValue === 'autre'))
    refreshElementEnable('enable', 'logement', '#signalement_draft_logement_appartementEtage', (natureLogementValue === 'appartement'))
    refreshElementEnable('enable', 'logement', '#signalement_draft_logement_appartementAvecFenetres', (natureLogementValue === 'appartement'))
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

    refreshElementEnable('enable', 'logement', '#signalement_draft_logement_nombrePieces', (compositionLogementValue === 'plusieurs_pieces'))
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

    refreshElementEnable('enable', 'logement', '#signalement_draft_logement_toilettesCuisineMemePiece', (cuisineValue === 'oui' && toilettesValue === 'oui'))
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
    'signalement_draft_situation_isProprioAverti',
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
    ]
  )
  initRefreshFromRadio(
    'situation',
    'signalement_draft_situation_assuranceContactee',
    [
      '#signalement_draft_situation_reponseAssurance',
    ]
  )
  window.dispatchEvent(new Event('refreshUploadButtonEvent'))

  reloadDeleteFileList()
}

function reloadFileList() {
  const urlListFiles = document?.querySelector('#url-signalement-files').value
  fetch(urlListFiles, {method: 'GET'}).then(response => {
    if (response.ok) {
      response.json().then((response) => {
        let newList = ''
        response.forEach((responseItem, index) => {
          newList += '<div class="fr-grid-row">'
          newList += '<div class="fr-col-8">'
          newList += '<i>' + responseItem.filename + '</i> (Type ' + responseItem.type + ')'
          newList += '</div>'
          newList += '<div class="fr-col-4">'
          newList += '<button form="form-delete-file" '
          newList += 'class="fr-link fr-icon-close-circle-line fr-link--icon-left fr-link--error" '
          newList += 'aria-label="Supprimer le fichier ' + responseItem.filename + '" '
          newList += 'title="Supprimer le fichier ' + responseItem.filename + '" '
          newList += 'data-doc="' + responseItem.id + '" '
          newList += '>Supprimer</button>'
          newList += '</div>'
          newList += '</div>'
        })

        document.querySelector('#bo-create-file-list').innerHTML = newList

        reloadDeleteFileList()
      })
    }
  })
}

function reloadDeleteFileList() {
  const deleteFilesButtons = document?.querySelectorAll('#bo-create-file-list button')
  if (deleteFilesButtons) {
    deleteFilesButtons.forEach((button) => {
      button.addEventListener('click', (event) => {
        event.preventDefault()
        button.disabled = true
        button.innerHTML = 'Suppression en cours...'
        const formDeleteFile = document?.querySelector('#form-delete-file')
        formDeleteFile.querySelector('input[name="file_id"]').value = button.getAttribute('data-doc')
        const formData = new FormData(formDeleteFile)
        const formAction = formDeleteFile.action

        fetch(formAction, {method: 'POST', body: formData}).then(response => {
          if (response.ok) {
            window.dispatchEvent(new Event('refreshUploadedFileList'))
          }
        })
      })
    })
  }
}

window.addEventListener('refreshUploadedFileList', (e) => {
  reloadFileList()
})

function initBoFormSignalementCoordonnees() {
  initRefreshFromRadio(
    'coordonnees',
    'signalement_draft_coordonnees_typeProprio',
    [
      '#signalement_draft_coordonnees_denominationProprio',
    ],
    'ORGANISME_SOCIETE',
    [
      '#signalement_draft_coordonnees_nomProprio_help',
      '#signalement_draft_coordonnees_prenomProprio_help',
    ]
  )
}
