import { loadWindowWithLocalStorage, updateLocalStorageWithPaginationParams, updateLocalStorageOnEvent } from '../../services/list_filter_helper'
function histoUpdateSubmitButton (elementName, elementLabel) {
  document.querySelector(elementName).innerHTML = elementLabel
  document.querySelector(elementName).disabled = true
}
function histoUpdateFieldsVisibility () {
  const partnerType = document.getElementById('partner_type')
  partnerType.value = partnerType.value.toUpperCase()

  let showEsabora, showIdoss, showBailleurSocial
  showEsabora = showIdoss = showBailleurSocial = false
  if (partnerType.value === 'COMMUNE_SCHS') {
    showEsabora = true
    showIdoss = true
  } else if (partnerType.value === 'ARS') {
    showEsabora = true
  } else if (partnerType.value === 'BAILLEUR_SOCIAL') {
    showBailleurSocial = true
  }
  const esaboraElement = document.querySelector('#partner_esabora')
  const idossElement = document.querySelector('#partner_idoss')
  const bailleurSocialElement = document.querySelector('#partner_bailleur_social')
  if (esaboraElement) {
    if (showEsabora) {
      document.querySelector('#partner_esabora').classList.remove('fr-hidden')
    } else {
      document.querySelector('#partner_esabora').classList.add('fr-hidden')
    }

  }
  if (idossElement) {
    if (showIdoss) {
      document.querySelector('#partner_idoss').classList.remove('fr-hidden')
    } else {
      document.querySelector('#partner_idoss').classList.add('fr-hidden')
    }

  }
  if (bailleurSocialElement) {
    if (showBailleurSocial) {
      document.querySelector('#partner_bailleur_social').classList.remove('fr-hidden')
    } else {
      document.querySelector('#partner_bailleur_social').classList.add('fr-hidden')
    }

  }
}
function histoUpdateValueFromData (elementName, elementData, target) {
  document.querySelector(elementName).value = target.getAttribute(elementData)
}

//TODO : remove editOrCreate param after feature_multi_territories deletion (will always be 'partner')
function histoUpdatePermissionsFromRole (editOrCreate) {
  const elementTogglePermissionAffectation = document.querySelector('#user_' + editOrCreate + '_permission_affectation_toggle input')
  const elementTextPermissionAffectation = document.querySelector('#user_' + editOrCreate + '_permission_affectation_text')
  if (!elementTogglePermissionAffectation || !elementTextPermissionAffectation) {
    return
  }
  let rolesSelect = null
  if(editOrCreate === 'partner'){
    rolesSelect = document.querySelector('#user_partner_role')
  }else{
    rolesSelect = document.querySelector('#user_' + editOrCreate + '_roles')
  }
  if (rolesSelect.value === 'ROLE_ADMIN' || rolesSelect.value === 'ROLE_ADMIN_TERRITORY') {
    if (!elementTogglePermissionAffectation.checked) {
      elementTogglePermissionAffectation.click()
    }
    elementTogglePermissionAffectation.setAttribute('disabled', 'disabled')
    elementTextPermissionAffectation.classList.remove('fr-hidden')
  } else {
    elementTogglePermissionAffectation.removeAttribute('disabled')
    elementTextPermissionAffectation.classList.add('fr-hidden')
  }
}

document.querySelectorAll('.btn-transfer-partner-user').forEach(swbtn => {
  swbtn.addEventListener('click', evt => {
    const target = evt.target
    document.querySelector('#fr-modal-user-transfer_username').textContent = target.getAttribute('data-username')
    histoUpdateValueFromData('#fr-modal-user-transfer_userid', 'data-userid', target)
    document.querySelector('#user_transfer_form').addEventListener('submit', (e) => {
      histoUpdateSubmitButton('#user_transfer_form_submit', 'Transfert en cours...')
    })
  })
})
document.querySelectorAll('.btn-delete-partner-user').forEach(swbtn => {
  swbtn.addEventListener('click', evt => {
    const target = evt.target
    document.querySelectorAll('.fr-modal-user-delete_username').forEach(userItem => {
      userItem.textContent = target.getAttribute('data-username')
    })
    document.querySelectorAll('.fr-modal-user-delete_useremail').forEach(userItem => {
      userItem.textContent = target.getAttribute('data-useremail')
    })
    histoUpdateValueFromData('#fr-modal-user-delete_userid', 'data-userid', target)
    document.querySelector('#user_delete_form').addEventListener('submit', (e) => {
      histoUpdateSubmitButton('#user_delete_form_submit', 'Suppression en cours...')
    })
  })
})

document.querySelectorAll('.btn-delete-partner').forEach(swbtn => {
  swbtn.addEventListener('click', evt => {
    const target = evt.target
    document.querySelectorAll('.fr-modal-partner-delete_name').forEach(userItem => {
      userItem.textContent = target.getAttribute('data-partnername')
    })
    histoUpdateValueFromData('#fr-modal-partner-delete_partnerid', 'data-partnerid', target)
    document.querySelector('#partner_delete_form').addEventListener('submit', (e) => {
      histoUpdateSubmitButton('#partner_delete_form_submit', 'Suppression en cours...')
    })
  })
})

if (document.querySelector('#partner_type')) {
  histoUpdateFieldsVisibility()
  document.querySelector('#partner_type').addEventListener('change', () => {
    histoUpdateFieldsVisibility()
  })
}

const territorySelect = document.querySelector("#partner_territory");

if (territorySelect) {
  territorySelect.addEventListener("change", function () {
    const bailleurSocialSelect = document.querySelector("#partner_bailleur");
    if (bailleurSocialSelect){
      const territoryId = this.value;
      bailleurSocialSelect.innerHTML = '<option value="">Sélectionner un bailleur social</option>';

      if (!territoryId) {
          return;
      }

      // route back_territory_bailleurs
      fetch(`/bo/territoire/${territoryId}/bailleurs`)
          .then(response => {
              if (!response.ok) {
                  throw new Error("Erreur lors de la récupération des bailleurs sociaux.");
              }
              return response.json();
          })
          .then(data => {
              // Ajouter les nouvelles options
              data.forEach(bailleur => {
                  const option = document.createElement("option");
                  option.value = bailleur.id;
                  option.textContent = bailleur.name;
                  bailleurSocialSelect.appendChild(option);
              });
          })
          .catch(error => {
              console.error("Erreur:", error);
          });

    }
  });
}

const deletePartnerForm = document.querySelectorAll('form[name="deletePartner"]')
deletePartnerForm.forEach(form => {
  form.addEventListener('submit', function (event) {
    if (!confirm('Êtes-vous sûr de vouloir supprimer ce partenaire ?')) {
      event.preventDefault()
    }
  })
})

//TODO : delete with feature_multi_territories deletion
function clearErrors () {
  const divErrorElements = document.querySelectorAll('.fr-input-group--error')
  divErrorElements.forEach((divErrorElement) => {
    divErrorElement.classList.remove('fr-input-group--error')
    const pErrorElement = divErrorElement.querySelector('.fr-error-text')
    if (pErrorElement) {
      pErrorElement.classList.add('fr-hidden')
    }
  })
}

if (document.querySelector('.fr-btn-add-user')) {
  document.querySelector('.fr-btn-add-user').addEventListener('click', () => {
    clearErrors()
  })
}

if (document.querySelector('#user_create_roles')) {
  document.querySelector('#user_create_roles').addEventListener('change', () => {
    histoUpdatePermissionsFromRole('create')
  })
  histoUpdatePermissionsFromRole('create')
}

const checkUserMail = (el) => {
  const formData = new FormData()
  formData.append('email', el.value)
  formData.append('_token', el.getAttribute('data-token'))
  fetch('/bo/partenaires/checkmail', {
    method: 'POST',
    body: formData
  }).then(r => {
    if (!r.ok) {
      r.json().then((r) => {
        el.classList.add('fr-input--error')
        el.parentElement.classList.add('fr-input-group--error')
        el.parentElement.querySelector('p.fr-error-text').innerText = r.error
        el.parentElement.querySelector('p.fr-error-text').classList.remove('fr-hidden')
        document.querySelector('#user_create_form_submit').disabled = true
        document.querySelector('#user_edit_form_submit').disabled = true
      })
    } else {
      el.classList.remove('fr-input--error')
      el.parentElement.classList.remove('fr-input-group--error')
      el.parentElement.querySelector('p.fr-error-text').classList.add('fr-hidden')
      document.querySelector('#user_create_form_submit').disabled = false
      document.querySelector('#user_edit_form_submit').disabled = false
    }
  })
    .catch(function (err) {
      console.warn('Something went wrong.', err)
    })
}

const emailInputs = document.querySelectorAll('.fr-input-email')
emailInputs.forEach(emailInput => {
  emailInput.addEventListener('change', function () {
    checkUserMail(this)
  })

  emailInput.addEventListener('input', function () {
    checkUserMail(this)
  })
})
//END TODO : delete with feature_multi_territories deletion

loadWindowWithLocalStorage('click', '[data-filter-list-partner]', 'search-partner-form')
updateLocalStorageOnEvent('input', '#partner-input', 'back_link_partners')
updateLocalStorageOnEvent('change', '#partner-filters-territories', 'back_link_partners')
updateLocalStorageOnEvent('change', '#partner-filters-types', 'back_link_partners')
updateLocalStorageWithPaginationParams('click', '#partner-pagination a', 'back_link_partners')

//add for multi territories
document.querySelectorAll('.btn-edit-partner-user').forEach(swbtn => {
  swbtn.addEventListener('click', event => {
    const refreshUrl = event.target.dataset.refreshUrl;
    document.querySelector('#fr-modal-user-edit button[type="submit"]').disabled = true;
    document.querySelector('#fr-modal-user-edit-title').innerHTML = 'Chargement en cours...'
    document.querySelector('#fr-modal-user-edit-form-container').innerHTML = 'Chargement en cours...'
    fetch(refreshUrl).then(response => {
      updateModaleFromResponse(response, '#fr-modal-user-edit', addEventListenerOnRoleChange)
    })
  })
})

const modalPartnerUserCreate = document?.querySelector('#fr-modal-user-create')
if(modalPartnerUserCreate){
  modalPartnerUserCreate.addEventListener('dsfr.conceal', (event) => {
    const refreshUrl = event.target.dataset.refreshUrl;
    modalPartnerUserCreate.querySelector('button[type="submit"]').disabled = true;
    fetch(refreshUrl).then(response => {
      updateModaleFromResponse(response, '#fr-modal-user-create', addEventListenerOnRoleChange)
    })
  })
}

function addEventListenerOnRoleChange(){
  if (document.querySelector('#user_partner_role')) {
    document.querySelector('#user_partner_role').addEventListener('change', () => {
      histoUpdatePermissionsFromRole('partner')
    })
    histoUpdatePermissionsFromRole('partner')
  }
}

function updateModaleFromResponse(response, modalSelector, callback = null){
  if (response.ok) {
    response.json().then((response) => {
      if (response.redirect) {
        window.location.href = response.url
        window.location.reload()
      }else{
        document.querySelector(modalSelector + '-title').innerHTML = response.title
        document.querySelector(modalSelector + '-form-container').innerHTML = response.content
        if(response.submitLabel){
          document.querySelector(modalSelector + ' button[type="submit"]').innerHTML = response.submitLabel
        }else{
          document.querySelector(modalSelector + ' button[type="submit"]').innerHTML = 'Valider'
        }
        attachSubmitFormModal(modalSelector, callback)
        if (typeof callback === 'function') {
          callback();
        }
        if(response.disabled){
          return
        }
        document.querySelector(modalSelector +' button[type="submit"]').disabled = false
      }
    })
  }else{
    const content = '<div class="fr-alert fr-alert--error" role="alert"><p class="fr-alert__title">Erreur</p><p>Une erreur s\'est produite. Veuillez actualiser la page.</p></div>'
    document.querySelector(modalSelector + '-form-container').innerHTML = content
  }
}

function attachSubmitFormModal (modalSelector, callback) {
  document.querySelector(modalSelector + ' form').addEventListener('submit', (e) => {
    e.preventDefault()
    document.querySelector(modalSelector + ' button[type="submit"]').disabled = true
    const formData = new FormData(e.target)
    fetch(e.target.action, {method: 'POST', body: formData}).then(response => {
      updateModaleFromResponse(response, modalSelector, callback)
    })
  })
}
