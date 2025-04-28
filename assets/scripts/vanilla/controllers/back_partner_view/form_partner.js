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


function histoUpdatePermissionsFromRole () {
  const elementTogglePermissionAffectation = document.querySelector('#user_partner_permission_affectation_toggle input')
  const elementTextPermissionAffectation = document.querySelector('#user_partner_permission_affectation_text')
  if (!elementTogglePermissionAffectation || !elementTextPermissionAffectation) {
    return
  }
  let rolesSelect = document.querySelector('#user_partner_role')
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

function updateMailingSumaryState() {
  const isMailingActive = document.querySelector('input[name="user_partner[isMailingActive]"]:checked').value
  if(isMailingActive == 1) {
    document.querySelectorAll('input[name="user_partner[isMailingSummary]"]').forEach(input => {
      input.removeAttribute('disabled')
    })
  } else {
    document.querySelectorAll('input[name="user_partner[isMailingSummary]"]').forEach(input => {
      input.setAttribute('disabled', 'disabled')
    })
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

const territorySelect = document.querySelector('#partner_territory')

if (territorySelect) {
  territorySelect.addEventListener('change', function () {
    const bailleurSocialSelect = document.querySelector('#partner_bailleur')
    if (bailleurSocialSelect) {
      const territoryId = this.value
      bailleurSocialSelect.innerHTML = '<option value="">Sélectionner un bailleur social</option>'

      if (!territoryId) {
        return
      }

      // route back_territory_bailleurs
      fetch(`/bo/territoire/${territoryId}/bailleurs`)
        .then(response => {
          if (!response.ok) {
            throw new Error('Erreur lors de la récupération des bailleurs sociaux.')
          }
          return response.json()
        })
        .then(data => {
          // Ajouter les nouvelles options
          data.forEach(bailleur => {
            const option = document.createElement('option')
            option.value = bailleur.id
            option.textContent = bailleur.name
            bailleurSocialSelect.appendChild(option)
          })
        })
        .catch(error => {
          console.error('Erreur:', error)
        })
    }
  })
}

const deletePartnerForm = document.querySelectorAll('form[name="deletePartner"]')
deletePartnerForm.forEach(form => {
  form.addEventListener('submit', function (event) {
    if (!confirm('Êtes-vous sûr de vouloir supprimer ce partenaire ?')) {
      event.preventDefault()
    }
  })
})

loadWindowWithLocalStorage('click', '[data-filter-list-partner]', 'search-partner-form')
updateLocalStorageOnEvent('input', '#partner-input', 'back_link_partners')
updateLocalStorageOnEvent('change', '#partner-filters-territories', 'back_link_partners')
updateLocalStorageOnEvent('change', '#partner-filters-types', 'back_link_partners')
updateLocalStorageWithPaginationParams('click', '#partner-pagination a', 'back_link_partners')

document.querySelectorAll('.btn-edit-partner-user').forEach(swbtn => {
  swbtn.addEventListener('click', event => {
    const refreshUrl = event.target.dataset.refreshUrl
    document.querySelector('#fr-modal-user-edit button[type="submit"]').disabled = true
    document.querySelector('#fr-modal-user-edit-title').innerHTML = 'Chargement en cours...'
    document.querySelector('#fr-modal-user-edit-form-container').innerHTML = 'Chargement en cours...'
    fetch(refreshUrl).then(response => {
      updateModaleFromResponse(response, '#fr-modal-user-edit', addEventListenerOnFormUser)
    })
  })
})

const modalPartnerUserCreate = document?.querySelector('#fr-modal-user-create')
if (modalPartnerUserCreate) {
  modalPartnerUserCreate.addEventListener('dsfr.conceal', (event) => {
    const refreshUrl = event.target.dataset.refreshUrl
    modalPartnerUserCreate.querySelector('button[type="submit"]').disabled = true
    fetch(refreshUrl).then(response => {
      updateModaleFromResponse(response, '#fr-modal-user-create', addEventListenerOnFormUser)
    })
  })
}

function addEventListenerOnFormUser () {
  if (document.querySelector('#user_partner_role')) {
    document.querySelector('#user_partner_role').addEventListener('change', () => {
      histoUpdatePermissionsFromRole()
    })
    histoUpdatePermissionsFromRole()
  }
  if(document.querySelector('#user_partner_isMailingSummary_0')) {
    document.querySelectorAll('input[name="user_partner[isMailingActive]"]').forEach(input => {
      input.addEventListener('change', () => {
        updateMailingSumaryState()
      })
    })
    updateMailingSumaryState()
  }
}

function updateModaleFromResponse (response, modalSelector, callback = null) {
  if (response.ok) {
    response.json().then((response) => {
      if (response.redirect) {
        window.location.href = response.url
        window.location.reload()
      } else {
        document.querySelector(modalSelector + '-title').innerHTML = response.title
        document.querySelector(modalSelector + '-form-container').innerHTML = response.content
        document.querySelector(modalSelector + ' button[type="submit"]').innerHTML = response.submitLabel ? response.submitLabel : 'Valider'
        attachSubmitFormModal(modalSelector, callback)
        if (typeof callback === 'function') {
          callback()
        }
        if (response.disabled) {
          return
        }
        document.querySelector(modalSelector + ' button[type="submit"]').disabled = false
      }
    })
  } else {
    const content = '<div class="fr-alert fr-alert--error" role="alert"><p class="fr-alert__title">Erreur</p><p>Une erreur s\'est produite. Veuillez actualiser la page.</p></div>'
    document.querySelector(modalSelector + '-form-container').innerHTML = content
  }
}

function attachSubmitFormModal (modalSelector, callback) {
  document.querySelector(modalSelector + ' form').addEventListener('submit', (e) => {
    e.preventDefault()
    document.querySelector(modalSelector + ' button[type="submit"]').disabled = true
    const formData = new FormData(e.target)
    fetch(e.target.action, { method: 'POST', body: formData }).then(response => {
      updateModaleFromResponse(response, modalSelector, callback)
    })
  })
}
