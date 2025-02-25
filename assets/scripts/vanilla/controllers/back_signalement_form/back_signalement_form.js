import { attacheAutocompleteAddressEvent } from '../../services/component_search_address'

if (document?.querySelector('#bo-form-signalement-address')) {
  initBoFormSignalementAddress()
}

function initBoFormSignalementAddress() {
  const boFormSignalementAddress = document?.querySelector('#bo-form-signalement-address')
  const inputAdresse = document?.querySelector('#signalement_draft_address_adresseCompleteOccupant')
  const inputForceSave = document?.querySelector('#signalement_draft_address_forceSave')
  attacheAutocompleteAddressEvent(inputAdresse)

  const modaleDuplicate = document.querySelector('#fr-modal-duplicate')
  const modaleDuplicateContainer = document.querySelector('#fr-modal-duplicate-container')
  const modaleDuplicateOpenLink = document.querySelector('#fr-modal-duplicate-open-duplicates')
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

  boFormSignalementAddress.addEventListener('submit', async (event) => {
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
            document.querySelector("#tabpanel-adresse-panel").innerHTML = response.tabContent
            document.querySelector('#tabpanel-adresse').scrollIntoView({ behavior: 'smooth' });
            initBoFormSignalementAddress()
            if(response.hasDuplicates){
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

  modaleDuplicateIgnoreButton?.addEventListener('click', (event) => {
    inputForceSave.value = 1
    document.querySelector('#signalement_draft_address_save').click()
  });

}

if (document?.querySelector('#bo-form-signalement-logement')) {
  initBoFormSignalementLogement()
}

function initBoFormSignalementLogement() {
  const boFormSignalementLogement = document?.querySelector('#bo-form-signalement-logement')

  boFormSignalementLogement.addEventListener('submit', async (event) => {
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
            document.querySelector("#tabpanel-logement-panel").innerHTML = response.tabContent
            document.querySelector('#tabpanel-logement').scrollIntoView({ behavior: 'smooth' });
            initBoFormSignalementLogement()
          }
        });
    } else {
      const errorHtml = '<div class="fr-alert fr-alert--error" role="alert"><p class="fr-alert__title">Une erreur est survenue lors de la soumission du formulaire, veuillez rafraichir la page.</p></div>';
      document.querySelector("#tabpanel-adresse-panel").innerHTML = errorHtml; 
    }
    })
  })
}
