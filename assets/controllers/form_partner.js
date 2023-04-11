document.querySelectorAll('.btn-transfer-partner-user').forEach(swbtn => {
  swbtn.addEventListener('click', evt => {
    const target = evt.target
    document.querySelector('#fr-modal-user-transfer_username').innerHTML = target.getAttribute('data-username')
    document.querySelector('#fr-modal-user-transfer_userid').value = target.getAttribute('data-userid')
    document.querySelector('#user_transfer_form').addEventListener('submit', (e) => {
      document.querySelector('#user_transfer_form_submit').innerHTML = 'Transfert en cours...'
      document.querySelector('#user_transfer_form_submit').disabled = true
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
    document.querySelector('#fr-modal-user-delete_userid').value = target.getAttribute('data-userid')
    document.querySelector('#user_delete_form').addEventListener('submit', (e) => {
      document.querySelector('#user_delete_form_submit').innerHTML = 'Suppression en cours...'
      document.querySelector('#user_delete_form_submit').disabled = true
    })
  })
})

document.querySelectorAll('.btn-delete-partner').forEach(swbtn => {
  swbtn.addEventListener('click', evt => {
    const target = evt.target
    document.querySelectorAll('.fr-modal-partner-delete_name').forEach(userItem => {
      userItem.innerHTML = target.getAttribute('data-partnername')
    })
    document.querySelector('#fr-modal-partner-delete_partnerid').value = target.getAttribute('data-partnerid')
    document.querySelector('#partner_delete_form').addEventListener('submit', (e) => {
      document.querySelector('#partner_delete_form_submit').innerHTML = 'Suppression en cours...'
      document.querySelector('#partner_delete_form_submit').disabled = true
    })
  })
})

document.querySelectorAll('.btn-edit-partner-user').forEach(swbtn => {
  swbtn.addEventListener('click', evt => {
    const target = evt.target
    document.querySelectorAll('.fr-modal-user-edit_useremail').forEach(userItem => {
      userItem.innerHTML = target.getAttribute('data-useremail')
    })
    document.querySelector('#user_edit_userid').value = target.getAttribute('data-userid')
    document.querySelector('#user_edit_email').value = target.getAttribute('data-useremail')
    document.querySelector('#user_edit_nom').value = target.getAttribute('data-usernom')
    document.querySelector('#user_edit_prenom').value = target.getAttribute('data-userprenom')
    const isMailingActive = target.getAttribute('data-userismailingactive')
    if ( '1' === isMailingActive ) {
      document.querySelector('#user_edit_is_mailing_active-1').checked = true
    } else {
      document.querySelector('#user_edit_is_mailing_active-2').checked = true
    }

    const userRoles = target.getAttribute('data-userrole').split(',')
    const rolesSelect = document.querySelector('#user_edit_roles')
    if (userRoles.includes('ROLE_ADMIN')) {
      rolesSelect.value  = 'ROLE_ADMIN'
    } else if (userRoles.includes('ROLE_ADMIN_TERRITORY')) {
      rolesSelect.value  = 'ROLE_ADMIN_TERRITORY'
    } else if (userRoles.includes('ROLE_ADMIN_PARTNER')) {
      rolesSelect.value  = 'ROLE_ADMIN_PARTNER'
    } else if (userRoles.includes('ROLE_USER_PARTNER')) {
      rolesSelect.value  = 'ROLE_USER_PARTNER'
    } else {
      rolesSelect.value  = 'ROLE_USER_PARTNER'
    }

    document.querySelector('#user_edit_form').addEventListener('submit', (e) => {
      document.querySelector('#user_edit_form_submit').innerHTML = 'Edition en cours...'
      document.querySelector('#user_edit_form_submit').disabled = true
    })
  })
})

if (document.querySelector('#partner_type')) {
  document.querySelector('#partner_type').addEventListener('change', (event) => {
      const partner_type = document.getElementById("partner_type");
      partner_type.value = partner_type.value.toUpperCase();
      if (partner_type.value === 'COMMUNE_SCHS') {
          document.querySelector('#partner_create_zone_pdl').classList.remove('fr-hidden')
          document.querySelector('#partner_create_esabora_title').classList.remove('fr-hidden')
          document.querySelector('#partner_create_esabora_div').classList.remove('fr-hidden')
      } else if (partner_type.value === 'EPCI') {
          document.querySelector('#partner_create_zone_pdl').classList.remove('fr-hidden')
          document.querySelector('#partner_create_esabora_title').classList.add('fr-hidden')
          document.querySelector('#partner_create_esabora_div').classList.add('fr-hidden')
      } else {
          document.querySelector('#partner_create_zone_pdl').classList.add('fr-hidden')
          document.querySelector('#partner_create_esabora_title').classList.add('fr-hidden')
          document.querySelector('#partner_create_esabora_div').classList.add('fr-hidden')
      }
  });
}
