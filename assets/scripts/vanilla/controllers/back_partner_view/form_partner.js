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
function histoUpdatePermissionsFromRole (editOrCreate, toggleValue) {
  const elementTogglePermissionAffectation = document.querySelector('#user_' + editOrCreate + '_permission_affectation_toggle input')
  const elementTextPermissionAffectation = document.querySelector('#user_' + editOrCreate + '_permission_affectation_text')
  if (!elementTogglePermissionAffectation || !elementTextPermissionAffectation) {
    return
  }
  const rolesSelect = document.querySelector('#user_' + editOrCreate + '_roles')
  if (rolesSelect.value === 'ROLE_ADMIN' || rolesSelect.value === 'ROLE_ADMIN_TERRITORY') {
    if (!elementTogglePermissionAffectation.checked) {
      elementTogglePermissionAffectation.click()
    }
    elementTogglePermissionAffectation.setAttribute('disabled', 'disabled')
    elementTextPermissionAffectation.classList.remove('fr-hidden')
  } else {
    elementTogglePermissionAffectation.removeAttribute('disabled')
    if (toggleValue === false && elementTogglePermissionAffectation.checked) {
      elementTogglePermissionAffectation.click()
    }
    if (editOrCreate === 'edit' && toggleValue === true && !elementTogglePermissionAffectation.checked) {
      elementTogglePermissionAffectation.click()
    }
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
let userEditedId = null

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
document.querySelectorAll('.btn-edit-partner-user').forEach(swbtn => {
  swbtn.addEventListener('click', evt => {
    clearErrors()
    const target = evt.target
    document.querySelectorAll('.fr-modal-user-edit_useremail').forEach(userItem => {
      userItem.textContent = target.getAttribute('data-useremail')
    })
    userEditedId = target.getAttribute('data-userid')
    if (target.getAttribute('data-submit-url')) {
      document.querySelector('#fr-modal-user-edit form').action = target.getAttribute('data-submit-url')
    }
    histoUpdateValueFromData('#user_edit_userid', 'data-userid', target)
    histoUpdateValueFromData('#user_edit_email', 'data-useremail', target)
    histoUpdateValueFromData('#user_edit_nom', 'data-usernom', target)
    histoUpdateValueFromData('#user_edit_prenom', 'data-userprenom', target)
    const isMailingActive = target.getAttribute('data-userismailingactive')
    if (isMailingActive === '1') {
      document.querySelector('#user_edit_is_mailing_active-1').checked = true
    } else {
      document.querySelector('#user_edit_is_mailing_active-2').checked = true
    }

    const elementPermissionAffectation = document.querySelector('#user_edit_permission_affectation')
    if (elementPermissionAffectation) {
      elementPermissionAffectation.removeAttribute('disabled')
    }

    const userRole = target.getAttribute('data-userrole')
    const rolesSelect = document.querySelector('#user_edit_roles')
    rolesSelect.value = userRole
    histoUpdatePermissionsFromRole('edit', target.getAttribute('data-userpermissionaffectation') === '1')

    document.querySelector('#user_edit_form').addEventListener('submit', (e) => {
      histoUpdateSubmitButton('#user_edit_form_submit', 'Edition en cours...')
    })
  })
})
if (document.querySelector('.fr-btn-add-user')) {
  document.querySelector('.fr-btn-add-user').addEventListener('click', () => {
    clearErrors()
    userEditedId = null
  })
}

if (document.querySelector('#partner_type')) {
  histoUpdateFieldsVisibility()
  document.querySelector('#partner_type').addEventListener('change', () => {
    histoUpdateFieldsVisibility()
  })
}

if (document.querySelector('#user_create_roles')) {
  document.querySelector('#user_create_roles').addEventListener('change', () => {
    histoUpdatePermissionsFromRole('create', null)
  })
  histoUpdatePermissionsFromRole('create', null)
}
if (document.querySelector('#user_edit_roles')) {
  document.querySelector('#user_edit_roles').addEventListener('change', () => {
    histoUpdatePermissionsFromRole('edit', null)
  })
  histoUpdatePermissionsFromRole('edit', null)
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

const checkUserMail = (el) => {
  const formData = new FormData()
  formData.append('email', el.value)
  formData.append('userEditedId', userEditedId)
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

loadWindowWithLocalStorage('click', '[data-filter-list-partner]', 'back_link_partners')
updateLocalStorageOnEvent('input', '#partner-input', 'back_link_partners')
updateLocalStorageOnEvent('change', '#partner-filters-territories', 'back_link_partners')
updateLocalStorageOnEvent('change', '#partner-filters-types', 'back_link_partners')
updateLocalStorageWithPaginationParams('click', '#partner-pagination a', 'back_link_partners')
