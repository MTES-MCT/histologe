import * as Sentry from '@sentry/browser'

const modalEditEmail = document?.querySelector('#fr-modal-profil-edit-email')
const modalEditEmailTitle = document?.querySelector('#fr-profil-edit-email-title')
const modalAlert = document?.querySelector('#fr-modal-profil-edit-email-alert')
const modalEmailInput = document?.querySelector('#fr-modal-profil-edit-email-email-input')
const modalCodeText = document?.querySelector('#fr-modal-profil-edit-email-code-text')
const modalCodeInput = document?.querySelector('#fr-modal-profil-edit-email-code-input')
const modalCodeInputInput = document?.querySelector('#profil_edit_email_code')

function clearErrors () {
  const divErrorElements = document.querySelectorAll('.fr-input-group--error')
  divErrorElements.forEach((divErrorElement) => {
    divErrorElement.querySelectorAll('.fr-error-text').forEach((pErrorElement) => {
      pErrorElement.remove()
    })
    divErrorElement.classList.remove('fr-input-group--error')
  })
}

function showStepOne () {
  modalCodeInputInput.value = '' // Vider le champ du code de confirmation
  modalAlert.classList.remove('fr-hidden')
  modalEmailInput.classList.remove('fr-hidden')
  modalCodeText.classList.add('fr-hidden')
  modalCodeInput.classList.add('fr-hidden')
  modalEditEmailTitle.innerText = 'Modifier mon adresse e-mail'
}

function showStepTwo () {
  modalCodeInputInput.value = '' // Vider le champ du code de confirmation
  modalAlert.classList.add('fr-hidden')
  modalEmailInput.classList.add('fr-hidden')
  modalCodeText.classList.remove('fr-hidden')
  modalCodeInput.classList.remove('fr-hidden')
  modalEditEmailTitle.innerText = 'Confirmer mon adresse e-mail'
}

async function submitEditEmail (formElement) {
  let response
  try {
    const formData = new FormData(formElement)
    const payload = {}
    formData.forEach((value, key) => {
      if (key === 'profil_edit_email[code]' && modalEditEmailTitle.innerText !== 'Confirmer mon adresse e-mail') {
        // on ne fait rien
      } else {
        payload[key] = value
      }
    })
    response = await fetch(formElement.action, {
      method: 'POST',
      body: JSON.stringify(payload),
      headers: {
        'Content-Type': 'application/json'
      }
    })
    if (response.ok && response.status === 200) {
      location.reload()
      window.scrollTo(0, 0)
    } else if (response.ok && response.status === 204) {
      const submitElement = document.querySelector('.fr-modal--opened [type="submit"]')
      submitElement.disabled = false
      submitElement.classList.remove('fr-btn--loading', 'fr-btn--icon-left', 'fr-icon-refresh-line')
      showStepTwo()
    } else if (response.status === 400) {
      const responseData = await response.json()
      const errors = responseData.errors
      const submitElement = document.querySelector('.fr-modal--opened [type="submit"]')
      let firstErrorElement = true
      for (const property in errors) {
        const inputElement = document.querySelector(`.fr-modal--opened [name="${property}"]`) || document.querySelector('.fr-modal--opened input')
        inputElement.setAttribute('aria-describedby', `${property}-desc-error`)
        inputElement.parentElement.classList.add('fr-input-group--error')

        const existingErrorElement = document.getElementById(`${property}-desc-error`)
        if (!existingErrorElement) {
          const pElement = document.createElement('p')
          pElement.classList.add('fr-error-text')
          pElement.id = `${property}-desc-error`

          let messageError = ''
          errors[property].errors.forEach((error) => {
            messageError = messageError + error
          })
          pElement.innerHTML = messageError
          inputElement.insertAdjacentElement('afterend', pElement)
        }
        if (firstErrorElement) {
          inputElement.focus()
          firstErrorElement = false
        }
      }
      submitElement.disabled = false
      submitElement.classList.remove('fr-btn--loading', 'fr-btn--icon-left', 'fr-icon-refresh-line')
    } else {
      const responseData = await response.json()
      alert(responseData.message)
    }
  } catch (error) {
    alert('Une erreur s\'est produite. Veuillez actualiser la page.')
    Sentry.captureException(new Error(error))
  }
}

modalEditEmail?.addEventListener('dsfr.conceal', (event) => {
  event.preventDefault()
  clearErrors()
  showStepOne()
})

modalEditEmail?.addEventListener('submit', (event) => {
  event.preventDefault()
  const formElement = document.getElementById(event.target.id)
  const submitElement = document.querySelector('.fr-modal--opened [type="submit"]')
  submitElement.disabled = true
  submitElement.classList.add('fr-btn--loading', 'fr-btn--icon-left', 'fr-icon-refresh-line')
  clearErrors()
  submitEditEmail(formElement)
})

showStepOne()
