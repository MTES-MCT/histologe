function histoUpdateSubmitButton(elementName, elementLabel) {
  document.querySelector(elementName).innerHTML = elementLabel
  document.querySelector(elementName).disabled = true
}
function histoUpdateFieldsVisibility(showZonePDL, showEsaboraTitle, showEsaboraDiv) {
  if (showZonePDL) {
    document.querySelector('#partner_create_zone_pdl').classList.remove('fr-hidden')
  } else {
    document.querySelector('#partner_create_zone_pdl').classList.add('fr-hidden')
  }
  if (showEsaboraTitle) {
    document.querySelector('#partner_create_esabora_title').classList.remove('fr-hidden')
  } else {
    document.querySelector('#partner_create_esabora_title').classList.add('fr-hidden')
  }
  if (showEsaboraDiv) {
    document.querySelector('#partner_create_esabora_div').classList.remove('fr-hidden')
  } else {
    document.querySelector('#partner_create_esabora_div').classList.add('fr-hidden')
  }
}
function histoUpdateValueFromData(elementName, elementData, target) {
  document.querySelector(elementName).value = target.getAttribute(elementData)
}

document.querySelectorAll('.btn-transfer-partner-user').forEach(swbtn => {
  swbtn.addEventListener('click', evt => {
    const target = evt.target
    document.querySelector('#fr-modal-user-transfer_username').innerHTML = target.getAttribute('data-username')
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
      userItem.innerHTML = target.getAttribute('data-username')
    })
    document.querySelectorAll('.fr-modal-user-delete_useremail').forEach(userItem => {
      userItem.innerHTML = target.getAttribute('data-useremail')
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
      userItem.innerHTML = target.getAttribute('data-partnername')
    })
    histoUpdateValueFromData('#fr-modal-partner-delete_partnerid', 'data-partnerid', target)
    document.querySelector('#partner_delete_form').addEventListener('submit', (e) => {
      histoUpdateSubmitButton('#partner_delete_form_submit', 'Suppression en cours...')
    })
  })
})

document.querySelectorAll('.btn-edit-partner-user').forEach(swbtn => {
  swbtn.addEventListener('click', evt => {
    const target = evt.target
    document.querySelectorAll('.fr-modal-user-edit_useremail').forEach(userItem => {
      userItem.innerHTML = target.getAttribute('data-useremail')
    })
    histoUpdateValueFromData('#user_edit_userid', 'data-userid', target)
    histoUpdateValueFromData('#user_edit_email', 'data-useremail', target)
    histoUpdateValueFromData('#user_edit_nom', 'data-usernom', target)
    histoUpdateValueFromData('#user_edit_prenom', 'data-userprenom', target)
    const isMailingActive = target.getAttribute('data-userismailingactive')
    if ( '1' === isMailingActive ) {
      document.querySelector('#user_edit_is_mailing_active-1').checked = true
    } else {
      document.querySelector('#user_edit_is_mailing_active-2').checked = true
    }

    const userRole = target.getAttribute('data-userrole')
    const rolesSelect = document.querySelector('#user_edit_roles')
    const rolesSelectHidden = document.querySelector('#user_edit_roles_hidden')
    const rolesMapping = {
      'ROLE_ADMIN': 'Super Admin',
      'ROLE_ADMIN_TERRITORY': 'Responsable Territoire'
    }

    rolesSelect.innerHTML = "";
    for (let i = 0; i < rolesSelectHidden.length; i++) {
      rolesSelect.options[rolesSelect.options.length] = new Option(rolesSelectHidden[i].text, rolesSelectHidden[i].value);
    }
    if (!Array.from(rolesSelect.options).map(option => option.value).includes(userRole)) {
      rolesSelect.options[rolesSelect.options.length] = new Option(rolesMapping[userRole], userRole);
    }
    rolesSelect.value = userRole;

    document.querySelector('#user_edit_form').addEventListener('submit', (e) => {
      histoUpdateSubmitButton('#user_edit_form_submit', 'Edition en cours...')
    })
  })
})

if (document.querySelector('#partner_type')) {
  document.querySelector('#partner_type').addEventListener('change', (event) => {
    const partner_type = document.getElementById("partner_type");
    partner_type.value = partner_type.value.toUpperCase();
    if (partner_type.value === 'COMMUNE_SCHS') {
      histoUpdateFieldsVisibility(true, true, true)
    } else if (partner_type.value === 'EPCI') {
      histoUpdateFieldsVisibility(true, false, false)
    } else {
      histoUpdateFieldsVisibility(false, false, false)
    }
  });
}
