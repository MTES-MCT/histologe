function histoUpdateSubmitButton(elementName, elementLabel) {
  document.querySelector(elementName).innerHTML = elementLabel
  document.querySelector(elementName).disabled = true
}
function histoUpdateFieldsVisibility() {
  const partner_type = document.getElementById("partner_type");
  partner_type.value = partner_type.value.toUpperCase();

  let showZonePDL = showEsabora = showIdoss = false;
  if (partner_type.value === 'COMMUNE_SCHS') {
    showZonePDL = true
    showEsabora = true
    showIdoss = true
  } else if (partner_type.value === 'ARS') {
    showEsabora = true
  } else if (partner_type.value === 'EPCI') {
    showZonePDL = true
  }
  if (showZonePDL) {
    document.querySelector('#partner_create_zone_pdl').classList.remove('fr-hidden')
  } else {
    document.querySelector('#partner_create_zone_pdl').classList.add('fr-hidden')
  }
  if (showEsabora) {
    document.querySelector('#partner_esabora').classList.remove('fr-hidden')
  } else {
    document.querySelector('#partner_esabora').classList.add('fr-hidden')
  }
  if (showIdoss) {
    document.querySelector('#partner_idoss').classList.remove('fr-hidden')
  } else {
    document.querySelector('#partner_idoss').classList.add('fr-hidden')
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
    const rolesSelect = document.querySelector('#user_edit_roles');
    rolesSelect.value = userRole;

    document.querySelector('#user_edit_form').addEventListener('submit', (e) => {
      histoUpdateSubmitButton('#user_edit_form_submit', 'Edition en cours...')
    })
  })
})

if (document.querySelector('#partner_type')) {
  histoUpdateFieldsVisibility()
  document.querySelector('#partner_type').addEventListener('change', () => {
    histoUpdateFieldsVisibility()
  })
}

const deletePartnerForm = document.querySelectorAll('form[name="deletePartner"]');
deletePartnerForm.forEach(form => {
  form.addEventListener('submit', function(event) {
        if (!confirm('Êtes-vous sûr de vouloir supprimer ce partenaire ?')) {
            event.preventDefault();
        }
    });
});

const checkUserMail = (el) => {
  let formData = new FormData();
  formData.append('email', el.value)
  formData.append('_token', el.getAttribute('data-token'))
  fetch('/bo/partenaires/checkmail', {
      method: 'POST',
      body: formData
  }).then(r => {
      if (!r.ok) {
          r.json().then((r) => {
              el.classList.add('fr-input--error');
              el.parentElement.classList.add('fr-input-group--error');
              el.parentElement.querySelector('p.fr-error-text').innerText = r.error;
              el.parentElement.querySelector('p.fr-error-text').classList.remove('fr-hidden');
              document.querySelector('#user_create_form_submit').disabled = true;
              document.querySelector('#user_edit_form_submit').disabled = true;
          })
      } else {
          el.classList.remove('fr-input--error');
          el.parentElement.classList.remove('fr-input-group--error');
          el.parentElement.querySelector('p.fr-error-text').classList.add('fr-hidden');
          document.querySelector('#user_create_form_submit').disabled = false;
          document.querySelector('#user_edit_form_submit').disabled = false;            
      }
  })
  .catch(function (err) {
      console.warn('Something went wrong.', err);
  });
};

const emailInputs = document.querySelectorAll('.fr-input-email');
emailInputs.forEach(emailInput => {
  emailInput.addEventListener('change', function() {
      checkUserMail(this);
  });

  emailInput.addEventListener('input', function() {
      checkUserMail(this);
  });
});
