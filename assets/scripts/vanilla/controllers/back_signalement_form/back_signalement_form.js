import { initSearchAndSelectBadges } from '../../services/component/component_search_and_select_badges';
import { attacheAutocompleteAddressEvents } from '../../services/component/component_search_address';
import { addFlashMessage } from '../../services/component/component_json_response_handler';

let boFormSignalementCurrentTabIsDirty = false;
let boFormNeedRefreshValidationTab = true;
let boFormSignalementTargetTab = '';

const boFormValidationTab = document.getElementById('tabpanel-validation-panel');
if (boFormValidationTab && window.location.hash === '#validation') {
  refreshValidationTab();
}

const modaleDuplicateIgnoreButton = document.querySelector('#fr-modal-duplicate-ignore-duplicates');
if (modaleDuplicateIgnoreButton) {
  modaleDuplicateIgnoreButton?.addEventListener('click', (event) => {
    const inputForceSave = document?.querySelector('#signalement_draft_address_forceSave');
    inputForceSave.value = 1;
    saveCurrentTab(event);
  });
}

const tabButtons = document?.querySelectorAll('ul.fr-tabs__list.fr-tabs__list--bo-create button');
tabButtons.forEach((tabButton) => {
  tabButton.addEventListener('click', (event) => {
    if (boFormSignalementCurrentTabIsDirty) {
      event.stopImmediatePropagation();
      boFormSignalementTargetTab = event.target.id.substring(9);
      saveCurrentTab(event);
    }
    if (event.target.id === 'tabpanel-validation' && boFormNeedRefreshValidationTab) {
      refreshValidationTab();
    }
  });
});

function refreshValidationTab() {
  boFormNeedRefreshValidationTab = false;
  boFormValidationTab.classList.add('fr-tabs__panel--loading');
  fetch(boFormValidationTab.dataset.route).then((response) => {
    if (response.ok) {
      response.json().then((response) => {
        boFormValidationTab.innerHTML = response.tabContent;
        initBoFormValidation();
        boFormValidationTab.classList.remove('fr-tabs__panel--loading');
      });
    } else {
      const errorHtml =
        '<div class="fr-notice fr-notice--alert"><div class="fr-container"><div class="fr-notice__body"><p><span class="fr-notice__title">Erreur</span><span class="fr-notice__desc">Une erreur s\'est produite. Veuillez actualiser la page.</span></p></div></div></div>';
      boFormValidationTab.innerHTML = errorHtml;
      boFormValidationTab.classList.remove('fr-tabs__panel--loading');
    }
  });
}

function saveCurrentTab(event) {
  const currentTab = document?.querySelector('.fr-tabs__panel.fr-tabs__panel--selected');
  const currentTabName = currentTab.id.substring(9, currentTab.id.length - 6);
  if (currentTabName === 'validation') {
    return;
  }
  currentTab.classList.add('fr-tabs__panel--saving');

  let formData = null;
  let formAction = null;
  if (event.type === 'submit') {
    formData = new FormData(event.target);
    formData.append(event.submitter.name, event.submitter.value);
    formAction = event.target.action;
  }
  if (event.type === 'click') {
    const currentTabForm = currentTab?.querySelector('form#bo-form-signalement-' + currentTabName);
    formData = new FormData(currentTabForm);
    formAction = currentTabForm.action;
  }

  fetch(formAction, { method: 'POST', body: formData }).then((response) => {
    if (response.ok) {
      const contentType = response.headers.get('Content-Type') || '';

      if (!contentType.includes('application/json') && response.redirected) {
        window.location.href = response.url;
        window.location.reload();
        return;
      }

      response.json().then((response) => {
        currentTab.classList.remove('fr-tabs__panel--saving');

        document.querySelector('#tabpanel-' + currentTabName + '-panel').innerHTML =
          response.tabContent;
        document
          .querySelector('#tabpanel-' + currentTabName)
          .scrollIntoView({ behavior: 'smooth' });

        if (response.redirect) {
          boFormSignalementCurrentTabIsDirty = false;
          boFormNeedRefreshValidationTab = true;

          if (response.url === undefined || response.url === '') {
            const targetTabButton = document?.querySelector(
              '#tabpanel-' + boFormSignalementTargetTab
            );
            boFormSignalementTargetTab = '';
            targetTabButton.click();
          } else {
            window.location.href = response.url;
          }
        } else {
          if (currentTabName === 'adresse' && response.hasDuplicates) {
            const modaleDuplicate = document.querySelector('#fr-modal-duplicate');
            const modaleDuplicateContainer = document.querySelector(
              '#fr-modal-duplicate-container'
            );
            const modaleDuplicateOpenLink = document.querySelector(
              '#fr-modal-duplicate-open-duplicates'
            );
            modaleDuplicateContainer.innerHTML = response.duplicateContent;
            modaleDuplicateOpenLink.href = response.linkDuplicates;
            modaleDuplicateOpenLink.textContent = response.labelBtnDuplicates;
            dsfr(modaleDuplicate).modal.disclose();
          } else {
            addFlashMessage({
              type: 'alert',
              title: 'Erreur',
              message: 'Merci de corriger les champs où des erreurs sont signalées.',
            });
          }
          attacheAutocompleteAddressEvents();
        }
        initBoFormSignalementSubmit(currentTabName);
      });
    } else {
      addFlashMessage({
        type: 'alert',
        title: 'Erreur',
        message:
          'Une erreur est survenue lors de la soumission du formulaire, veuillez rafraichir la page.',
      });
    }
  });
}

function initBoFormSignalementSubmit(tabName) {
  const boFormSignalementTab = document?.querySelector('#bo-form-signalement-' + tabName);

  boFormSignalementTab.addEventListener('submit', async (event) => {
    event.preventDefault();
    boFormSignalementTargetTab = event.submitter.getAttribute('data-target');
    saveCurrentTab(event);
  });

  const tabInputs = boFormSignalementTab?.querySelectorAll('input');
  tabInputs.forEach((tabInput) => {
    tabInput.addEventListener('click', () => {
      if (tabInput.type == 'radio') {
        boFormSignalementCurrentTabIsDirty = true;
      }
    });
    tabInput.addEventListener('change', () => {
      boFormSignalementCurrentTabIsDirty = true;
    });
  });
  const tabSelects = boFormSignalementTab?.querySelectorAll('select');
  tabSelects.forEach((tabSelect) => {
    tabSelect.addEventListener('change', () => {
      boFormSignalementCurrentTabIsDirty = true;
    });
  });
  const tabTextAreas = boFormSignalementTab?.querySelectorAll('textarea');
  tabTextAreas.forEach((tabTextArea) => {
    tabTextArea.addEventListener('change', () => {
      boFormSignalementCurrentTabIsDirty = true;
    });
  });
  switch (tabName) {
    case 'adresse':
      initBoFormSignalementAdresse();
      break;
    case 'logement':
      initBoFormSignalementLogement();
      break;
    case 'situation':
      initBoFormSignalementSituation();
      break;
    case 'coordonnees':
      initBoFormSignalementCoordonnees();
      initComponentAdress('#signalement_draft_coordonnees_adresseCompleteProprio');
      initComponentAdress('#signalement_draft_coordonnees_adresseCompleteAgence');
      break;
    case 'desordres':
      initBoFormSignalementDesordres();
      break;
  }
}

function initRefreshFromRadio(
  tabName,
  radioName,
  listElementsToEnable,
  valueToEnable = undefined,
  listElementsToHide = [],
  tabDestinationName = null
) {
  const boFormSignalementTab = document?.querySelector('#bo-form-signalement-' + tabName);

  const radioInputs = boFormSignalementTab?.querySelectorAll('#' + radioName + ' input');
  radioInputs.forEach((radioInput) => {
    radioInput.addEventListener('click', () => {
      refreshFromRadioInput();
    });
  });
  refreshFromRadioInput();

  function refreshFromRadioInput() {
    let radioInputValue = '';
    radioInputs.forEach((radioInput) => {
      if (radioInput.checked) {
        radioInputValue = radioInput.value;
      }
    });

    const matchValue =
      radioInputValue === 'oui' ||
      radioInputValue === '1' ||
      (Array.isArray(valueToEnable) && valueToEnable.includes(radioInputValue)) ||
      (valueToEnable !== undefined && radioInputValue === valueToEnable);
    listElementsToEnable.forEach((elementSelector) => {
      refreshElementEnable('enable', tabDestinationName ?? tabName, elementSelector, matchValue);
    });

    listElementsToHide.forEach((elementSelector) => {
      refreshElementEnable('show', tabDestinationName ?? tabName, elementSelector, matchValue);
    });
  }
}

function refreshElementEnable(action, tabName, elementSelector, isEnabled) {
  const boFormSignalementTab = document?.querySelector('#bo-form-signalement-' + tabName);
  const elementSelected = boFormSignalementTab?.querySelector(elementSelector);

  if (!elementSelected) {
    return;
  }

  if (isEnabled) {
    if (action === 'show') {
      elementSelected.classList.remove('fr-display-none');
    } else {
      elementSelected.parentElement.classList.remove('fr-input-group--disabled');
      elementSelected.disabled = false;
      const elementSelectedInputs = elementSelected?.querySelectorAll('input');
      if (elementSelectedInputs) {
        elementSelectedInputs.forEach((elementSelectedInput) => {
          elementSelectedInput.disabled = false;
        });
      }
    }
  } else {
    if (action === 'show') {
      elementSelected.classList.add('fr-display-none');
    } else {
      elementSelected.parentElement.classList.add('fr-input-group--disabled');
      if (elementSelected.value) {
        elementSelected.value = '';
      }
      elementSelected.disabled = true;
      const elementSelectedInputs = elementSelected?.querySelectorAll('input');
      elementSelectedInputs.forEach((elementSelectedInput) => {
        elementSelectedInput.checked = false;
        elementSelectedInput.disabled = true;
      });
    }
  }
}

if (document?.querySelector('#bo-form-signalement-adresse')) {
  initBoFormSignalementSubmit('adresse');
}
if (document?.querySelector('#bo-form-signalement-logement')) {
  initBoFormSignalementSubmit('logement');
}
if (document?.querySelector('#bo-form-signalement-situation')) {
  initBoFormSignalementSubmit('situation');
}
if (document?.querySelector('#bo-form-signalement-coordonnees')) {
  initBoFormSignalementSubmit('coordonnees');
}

if (document?.querySelector('#bo-form-signalement-desordres')) {
  initBoFormSignalementSubmit('desordres');
}

function initBoFormSignalementDesordres() {
  // Gestion de la fermeture des modales de sélection des critères
  document.querySelectorAll('.valid-add-critere').forEach((openModalBtn) => {
    openModalBtn.addEventListener('click', function () {
      updateSelectedCriteres(openModalBtn.closest('dialog'));
    });
  });

  // Gestion de la fermeture des modales d'édition des précisions
  document.querySelectorAll('.valid-edit-precisions').forEach((openModalBtn) => {
    openModalBtn.addEventListener('click', function () {
      updateSelectedPrecisions(openModalBtn.closest('dialog'));
    });
  });

  function updateSelectedCriteres(modal) {
    window.dispatchEvent(new Event('refreshSearchCheckboxContainerEvent'));
    const zone = modal.dataset.zone;
    const listCriteres = document.querySelector(`#list-critere-${zone}`);
    const heading = listCriteres.querySelector('h4');

    // Vider les encarts existants
    document.querySelectorAll(`.item-critere-${zone}`).forEach((container) => {
      container.remove();
    });

    let nbCriteres = 0;
    // Récupérer les critères sélectionnés
    modal.querySelectorAll("input[type='checkbox']:checked").forEach((checkbox) => {
      nbCriteres++;
      const critereId = checkbox.value;

      // Créer un encart pour le critère sélectionné
      let encart = document.createElement('div');
      encart.classList.add(
        'fr-grid-row',
        'fr-p-3v',
        'fr-mb-3v',
        'fr-grid-row--top',
        'fr-border--grey',
        `item-critere-${zone}`
      );
      encart.id = `item-critere-${critereId}`;
      const encartSubItem = document.createElement('div');
      encartSubItem.classList.add('fr-col-12', 'fr-col-md-8');
      encartSubItem.innerText = checkbox.labels[0].innerText;
      encart.appendChild(encartSubItem);

      const modalId = `modal-precisions-${critereId}`;
      const modalElement = document.getElementById(modalId);
      const buttonDeleteCritereHtml = `<button class="fr-a-edit fr-btn--icon-left fr-icon-delete-line delete-critere-btn" 
                title="Supprimer le désordre">
                    Supprimer
            </button>`;
      if (modalElement) {
        const btnDeleteContainer = document.createElement('div');
        btnDeleteContainer.classList.add('fr-col-12', 'fr-col-md-4', 'fr-text--right');
        btnDeleteContainer.innerHTML = `
          <a href="#" aria-controls="${modalId}" data-fr-opened="false" 
                class="fr-a-edit fr-btn--icon-left fr-icon-edit-line edit-precisions-btn" 
                title="Editer les détails" data-fr-js-modal-button="true">
                    Editer les détails
          </a>
          <br>
          ${buttonDeleteCritereHtml}
        `;
        encart.appendChild(btnDeleteContainer);

        const listPrecisionsContainer = document.createElement('div');
        listPrecisionsContainer.classList.add('fr-col-12');
        const listPrecisions = document.createElement('ul');
        listPrecisions.classList.add('fr-list');
        listPrecisions.dataset.precisions = checkbox.value;
        listPrecisionsContainer.appendChild(listPrecisions);
        listPrecisionsContainer.innerHTML += `
            <span id="details-critere"></span>
            <p class="fr-hidden fr-error-text"></p>
        `;
        encart.appendChild(listPrecisionsContainer);
      } else {
        encart.innerHTML += `<div class="fr-col-12 fr-col-md-4 fr-text--right">${buttonDeleteCritereHtml}</div>`;
      }

      // Ajouter l'encart dans la bonne colonne (Logement ou Bâtiment)
      listCriteres.appendChild(encart);
      if (modalElement) {
        updateSelectedPrecisions(modalElement);
      }

      const deleteCritereBtn = encart.querySelector('.delete-critere-btn');
      deleteCritereBtn.addEventListener('click', function (e) {
        e.preventDefault();

        if (checkbox) checkbox.checked = false;
        if (modalElement) {
          modalElement
            .querySelectorAll("input[type='checkbox']")
            .forEach((cb) => (cb.checked = false));
          modalElement
            .querySelectorAll("input[type='text']")
            .forEach((inputTextElement) => (inputTextElement.value = null));
        }
        encart.remove();

        if (listCriteres.querySelectorAll('.fr-grid-row').length === 0) {
          heading.classList.add('fr-hidden');
        } else {
          heading.classList.remove('fr-hidden');
        }

        window.dispatchEvent(new Event('refreshSearchCheckboxContainerEvent'));
      });
    });
    if (nbCriteres > 0) {
      heading.classList.remove('fr-hidden');
    } else {
      heading.classList.add('fr-hidden');
    }
  }

  function clearInputError(event) {
    const inputTextElement = event.target;
    inputTextElement.parentElement.classList.remove('fr-input-group--error');
    const errorElement = inputTextElement.parentElement.querySelector('.fr-error-text');
    if (errorElement) {
      errorElement.classList.add('fr-hidden');
    }
  }

  function updateSelectedPrecisions(modal) {
    const critereId = modal.dataset.critereid;
    const precisionContainer = document.querySelector(`#item-critere-${critereId}`);

    let hasPrecisionsChosen = false;
    let displayPrecisionError = false;
    const ulElement = precisionContainer ? precisionContainer.querySelector('ul') : null;
    if (ulElement) {
      ulElement.innerHTML = '';
      const checkboxes = modal.querySelectorAll("input[type='checkbox']");
      checkboxes.forEach((checkbox) => {
        if (checkbox.checked) {
          let precision = '';
          if ('desordres_logement_lumiere_plafond_trop_bas' == modal.dataset.critereslug) {
            const inputTextElement = checkbox.parentElement.querySelector("input[type='text']");
            inputTextElement.removeEventListener('input', clearInputError); // avoids double listener
            inputTextElement.addEventListener('input', clearInputError);
            if ('' != inputTextElement.value) {
              precision = ' (hauteur : ' + inputTextElement.value + ' cm)';
            }
            if (inputTextElement.parentElement.classList.contains('fr-input-group--error')) {
              displayPrecisionError = true;
            }
          }

          const precisionLabel = checkbox.labels[0].innerHTML + precision;
          const precisionItem = document.createElement('li');
          precisionItem.innerHTML = precisionLabel;
          ulElement.appendChild(precisionItem);
          hasPrecisionsChosen = true;
        }
      });
    }

    if (
      'desordres_batiment_nuisibles_autres' == modal.dataset.critereslug ||
      'desordres_logement_nuisibles_autres' == modal.dataset.critereslug
    ) {
      const detailsCritereElement = precisionContainer
        ? precisionContainer.querySelector('#details-critere')
        : null;
      detailsCritereElement.innerHTML = '';
      const inputTextElement = modal.querySelector("input[type='text']");
      if ('' != inputTextElement.value) {
        const detailItem = document.createElement('span');
        detailItem.innerText = 'Commentaire : ';
        const detailItemValue = document.createElement('i');
        detailItemValue.innerText = inputTextElement.value;
        detailItem.appendChild(detailItemValue);
        detailsCritereElement.appendChild(detailItem);
        if ('desordres_batiment_nuisibles_autres' == modal.dataset.critereslug) {
          hasPrecisionsChosen = true; // ce désordre doit automatiquement avoir un commentaire
        }
      } else if ('desordres_logement_nuisibles_autres' == modal.dataset.critereslug) {
        hasPrecisionsChosen = false; // ce désordre doit automatiquement avoir un commentaire + une précision (géré ligne 338)
      }
    }

    const errorElement = precisionContainer ? precisionContainer.querySelector('p') : null;
    if (hasPrecisionsChosen && !displayPrecisionError) {
      precisionContainer.classList.add('fr-border--grey');
      precisionContainer.classList.remove('fr-border--red');
      errorElement.classList.add('fr-hidden');
    } else {
      precisionContainer.classList.remove('fr-border--grey');
      precisionContainer.classList.add('fr-border--red');
      if (displayPrecisionError) {
        errorElement.innerHTML = 'Merci de corriger les détails du désordre';
      } else {
        errorElement.innerHTML = 'Veuillez renseigner les détails du désordre';
      }
      errorElement.classList.remove('fr-hidden');
    }
  }
  updateSelectedCriteres(document.getElementById('fr-modal-desordres-batiment-add'));
  updateSelectedCriteres(document.getElementById('fr-modal-desordres-logement-add'));
}

// TODO utiliser import { initComponentAdress } from '../../services/component/component_search_address';
// nécessite de renommer certaines classes dans les templates et formType et de vérifier q'il n'y a pas des confusions
function initComponentAdress(id) {
  const addressInput = document.querySelector(id);
  const addressInputParent = addressInput.parentElement.parentElement.parentElement;
  const manualAddressSwitcher = addressInputParent?.querySelector(
    '.bo-signalement-manual-address-switcher'
  );
  const manualAddressContainer = addressInputParent?.querySelector(
    '.bo-form-signalement-manual-address-container'
  );
  const manualAddressAddress = addressInputParent?.querySelector(
    '.bo-form-signalement-manual-address-input'
  );
  const manualAddressInputs = addressInputParent?.querySelectorAll(
    '.bo-form-signalement-manual-address'
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

function initBoFormValidation() {
  initSearchAndSelectBadges();
  const quitValidationButton = document.getElementById('quit-validation');
  if (quitValidationButton) {
    quitValidationButton.addEventListener('click', () => {
      const inputAffectationPartnerIds = document.querySelector('#affectation-partner-ids');
      if (inputAffectationPartnerIds && inputAffectationPartnerIds.value !== '') {
        const modalQuitValidation = document.getElementById('fr-modal-form-bo-quit-validation');
        dsfr(modalQuitValidation).modal.disclose();
      } else {
        window.location.href = quitValidationButton.dataset.route;
      }
    });
  }
  const boFormSignalementValidation = document.querySelector('#bo-form-signalement-validation');
  if (boFormSignalementValidation) {
    boFormSignalementValidation.addEventListener('submit', (event) => {
      event.preventDefault();
      boFormValidationTab.classList.add('fr-tabs__panel--saving');
      const formData = new FormData(boFormSignalementValidation);
      const formAction = boFormSignalementValidation.action;
      fetch(formAction, { method: 'POST', body: formData }).then((response) => {
        if (response.ok) {
          response.json().then((response) => {
            if (response.redirect) {
              window.location.href = response.url;
            } else {
              boFormValidationTab.innerHTML = response.tabContent;
              initBoFormValidation();
              boFormValidationTab.classList.remove('fr-tabs__panel--saving');
            }
          });
        } else {
          const errorHtml =
            '<div class="fr-notice fr-notice--alert"><div class="fr-container"><div class="fr-notice__body"><p><span class="fr-notice__desc">Une erreur est survenue, veuillez rafraichir la page.</span></p></div></div></div>';
          boFormValidationTab.innerHTML = errorHtml;
          boFormValidationTab.classList.remove('fr-tabs__panel--saving');
        }
      });
    });
  }
}

function initBoFormSignalementAdresse() {
  initComponentAdress('#signalement_draft_address_adresseCompleteOccupant');

  initRefreshFromRadio(
    'adresse',
    'signalement_draft_address_profileDeclarant',
    ['#signalement_draft_address_lienDeclarantOccupant'],
    'TIERS_PARTICULIER'
  );

  initRefreshFromRadio(
    'adresse',
    'signalement_draft_address_profileDeclarant',
    ['#signalement_draft_address_logementVacant'],
    ['TIERS_PARTICULIER', 'TIERS_PRO', 'SERVICE_SECOURS', 'BAILLEUR']
  );

  initRefreshFromRadio(
    'adresse',
    'signalement_draft_address_profileDeclarant',
    ['#signalement_draft_coordonnees_isProTiersDeclarant_0'],
    'TIERS_PRO',
    [],
    'coordonnees'
  );

  initRefreshFromRadio(
    'adresse',
    'signalement_draft_address_profileDeclarant',
    [
      '#signalement_draft_coordonnees_structureDeclarant',
      '#signalement_draft_coordonnees_nomDeclarant',
      '#signalement_draft_coordonnees_prenomDeclarant',
      '#signalement_draft_coordonnees_mailDeclarant',
      '#signalement_draft_coordonnees_telDeclarant_select',
      '#signalement_draft_coordonnees_telDeclarant_input',
      '#telDeclarantContainer',
    ],
    ['TIERS_PARTICULIER', 'TIERS_PRO', 'SERVICE_SECOURS', 'BAILLEUR'],
    [],
    'coordonnees'
  );

  const boFormSignalementAdresse = document?.querySelector('#bo-form-signalement-adresse');
  const natureLogementInputs = boFormSignalementAdresse?.querySelectorAll(
    '#signalement_draft_address_natureLogement input'
  );

  natureLogementInputs.forEach((natureLogementInput) => {
    natureLogementInput.addEventListener('click', () => {
      refreshFromNatureLogement();
    });
  });
  refreshFromNatureLogement();

  function refreshFromNatureLogement() {
    let natureLogementValue = '';
    natureLogementInputs.forEach((natureLogementInput) => {
      if (natureLogementInput.checked) {
        natureLogementValue = natureLogementInput.value;
      }
    });

    refreshElementEnable(
      'enable',
      'adresse',
      '#signalement_draft_address_natureLogementAutre',
      natureLogementValue === 'autre'
    );
    refreshElementEnable(
      'enable',
      'logement',
      '#signalement_draft_logement_appartementEtage',
      natureLogementValue === 'appartement'
    );
    refreshElementEnable(
      'enable',
      'logement',
      '#signalement_draft_logement_appartementAvecFenetres',
      natureLogementValue === 'appartement'
    );
  }

  const profileDeclarantInputs = boFormSignalementAdresse?.querySelectorAll(
    '#signalement_draft_address_profileDeclarant input'
  );
  profileDeclarantInputs.forEach((profileDeclarantInput) => {
    profileDeclarantInput.addEventListener('click', (event) => {
      if (event.target.value == 'LOCATAIRE' || event.target.value == 'BAILLEUR_OCCUPANT') {
        const proTiersStructure = document.querySelector(
          '#signalement_draft_coordonnees_structureDeclarant'
        );
        const proTiersNom = document.querySelector('#signalement_draft_coordonnees_nomDeclarant');
        const proTiersPrenom = document.querySelector(
          '#signalement_draft_coordonnees_prenomDeclarant'
        );
        const proTiersMail = document.querySelector('#signalement_draft_coordonnees_mailDeclarant');
        if (proTiersStructure) {
          proTiersStructure.value = '';
          proTiersNom.value = '';
          proTiersPrenom.value = '';
          proTiersMail.value = '';
        }
      }
    });
  });
}

function initBoFormSignalementLogement() {
  const boFormSignalementLogement = document?.querySelector('#bo-form-signalement-logement');
  const compositionLogementInputs = boFormSignalementLogement?.querySelectorAll(
    '#signalement_draft_logement_pieceUnique input'
  );
  compositionLogementInputs.forEach((compositionLogementInput) => {
    compositionLogementInput.addEventListener('click', () => {
      refreshFromCompositionLogement();
    });
  });
  refreshFromCompositionLogement();

  function refreshFromCompositionLogement() {
    let compositionLogementValue = '';
    compositionLogementInputs.forEach((compositionLogementInput) => {
      if (compositionLogementInput.checked) {
        compositionLogementValue = compositionLogementInput.value;
      }
    });

    refreshElementEnable(
      'enable',
      'logement',
      '#signalement_draft_logement_nombrePieces',
      compositionLogementValue === 'plusieurs_pieces'
    );
  }

  const cuisineInputs = boFormSignalementLogement?.querySelectorAll(
    '#signalement_draft_logement_cuisine input'
  );
  cuisineInputs.forEach((cuisineInput) => {
    cuisineInput.addEventListener('click', () => {
      refreshFromCuisineAndToilettes();
    });
  });
  const toilettesInputs = boFormSignalementLogement?.querySelectorAll(
    '#signalement_draft_logement_toilettes input'
  );
  toilettesInputs.forEach((toilettesInput) => {
    toilettesInput.addEventListener('click', () => {
      refreshFromCuisineAndToilettes();
    });
  });
  refreshFromCuisineAndToilettes();

  function refreshFromCuisineAndToilettes() {
    let cuisineValue = '';
    cuisineInputs.forEach((cuisineInput) => {
      if (cuisineInput.checked) {
        cuisineValue = cuisineInput.value;
      }
    });
    let toilettesValue = '';
    toilettesInputs.forEach((toilettesInput) => {
      if (toilettesInput.checked) {
        toilettesValue = toilettesInput.value;
      }
    });

    refreshElementEnable(
      'enable',
      'logement',
      '#signalement_draft_logement_toilettesCuisineMemePiece',
      cuisineValue === 'oui' && toilettesValue === 'oui'
    );
  }
}

function initBoFormSignalementSituation() {
  initRefreshFromRadio('situation', 'signalement_draft_situation_allocataire', [
    '#signalement_draft_situation_caisseAllocation',
    '#signalement_draft_situation_dateNaissanceAllocataire',
    '#signalement_draft_situation_numeroAllocataire',
    '#signalement_draft_situation_typeAllocation',
    '#signalement_draft_situation_montantAllocation',
  ]);
  initRefreshFromRadio('situation', 'signalement_draft_situation_isProprioAverti', [
    '#signalement_draft_situation_dateProprietaireAverti',
    '#signalement_draft_situation_moyenInformationProprietaire',
    '#signalement_draft_situation_reponseProprietaire',
  ]);
  initRefreshFromRadio('situation', 'signalement_draft_situation_logementAssure', [
    '#signalement_draft_situation_assuranceContactee',
  ]);
  initRefreshFromRadio('situation', 'signalement_draft_situation_assuranceContactee', [
    '#signalement_draft_situation_reponseAssurance',
  ]);
  initRefreshFromRadio('situation', 'signalement_draft_situation_accompagnementTravailleurSocial', [
    '#signalement_draft_situation_accompagnementTravailleurSocialNomStructure',
  ]);
  window.dispatchEvent(new Event('refreshUploadButtonEvent'));

  reloadDeleteFileList();
}

function reloadFileList() {
  const urlListFiles = document?.querySelector('#url-signalement-files').value;
  fetch(urlListFiles, { method: 'GET' }).then((response) => {
    if (response.ok) {
      response.json().then((response) => {
        let newList = '';
        response.forEach((responseItem) => {
          newList += '<div class="fr-grid-row">';
          newList += '<div class="fr-col-8">';
          newList += '<i>' + responseItem.filename + '</i> (Type ' + responseItem.type + ')';
          newList += '</div>';
          newList += '<div class="fr-col-4">';
          newList += '<button form="form-delete-file" ';
          newList += 'class="fr-link fr-icon-close-circle-line fr-link--icon-left fr-link--error" ';
          newList += 'aria-label="Supprimer le fichier ' + responseItem.filename + '" ';
          newList += 'title="Supprimer le fichier ' + responseItem.filename + '" ';
          newList += 'data-doc="' + responseItem.id + '" ';
          newList += '>Supprimer</button>';
          newList += '</div>';
          newList += '</div>';
        });

        document.querySelector('#bo-create-file-list').innerHTML = newList;

        reloadDeleteFileList();
      });
    }
  });
}

function reloadDeleteFileList() {
  const deleteFilesButtons = document?.querySelectorAll('#bo-create-file-list button');
  if (deleteFilesButtons) {
    deleteFilesButtons.forEach((button) => {
      button.addEventListener('click', (event) => {
        event.preventDefault();
        button.disabled = true;
        button.innerHTML = 'Suppression en cours...';
        const formDeleteFile = document?.querySelector('#form-delete-file');
        formDeleteFile.querySelector('input[name="file_id"]').value =
          button.getAttribute('data-doc');
        const formData = new FormData(formDeleteFile);
        const formAction = formDeleteFile.action;

        fetch(formAction, { method: 'POST', body: formData }).then((response) => {
          if (response.ok) {
            window.dispatchEvent(new Event('refreshUploadedFileList'));
          } else {
            response.json().then((response) => {
              alert(response.message);
            });
          }
        });
      });
    });
  }
}

window.addEventListener('refreshUploadedFileList', () => {
  reloadFileList();
});

function initBoFormSignalementCoordonnees() {
  initRefreshFromRadio(
    'coordonnees',
    'signalement_draft_coordonnees_typeProprio',
    ['#signalement_draft_coordonnees_denominationProprio'],
    'ORGANISME_SOCIETE',
    [
      '#signalement_draft_coordonnees_nomProprio_help',
      '#signalement_draft_coordonnees_prenomProprio_help',
    ]
  );
  const checkIsProTiersDeclarant = document.querySelector(
    '#signalement_draft_coordonnees_isProTiersDeclarant_0'
  );
  checkIsProTiersDeclarant.addEventListener('change', (event) => {
    const proTiersStructure = document.querySelector(
      '#signalement_draft_coordonnees_structureDeclarant'
    );
    const proTiersNom = document.querySelector('#signalement_draft_coordonnees_nomDeclarant');
    const proTiersPrenom = document.querySelector('#signalement_draft_coordonnees_prenomDeclarant');
    const proTiersMail = document.querySelector('#signalement_draft_coordonnees_mailDeclarant');
    if (event.target.checked) {
      proTiersStructure.value =
        checkIsProTiersDeclarant.parentElement.parentElement.parentElement.dataset.userStructure;
      proTiersNom.value =
        checkIsProTiersDeclarant.parentElement.parentElement.parentElement.dataset.userNom;
      proTiersPrenom.value =
        checkIsProTiersDeclarant.parentElement.parentElement.parentElement.dataset.userPrenom;
      proTiersMail.value =
        checkIsProTiersDeclarant.parentElement.parentElement.parentElement.dataset.userMail;
    } else {
      proTiersStructure.value = '';
      proTiersNom.value = '';
      proTiersPrenom.value = '';
      proTiersMail.value = '';
    }
  });

  window.dispatchEvent(new Event('refreshPhoneNumberEvent'));
}
