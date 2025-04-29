import * as Sentry from "@sentry/browser";

Node.prototype.addEventListeners = function (eventNames, eventFunction) {
  for (const eventName of eventNames.split(' ')) { this.addEventListener(eventName, eventFunction) }
}

const uploadedFiles = []
let invalid = null

document.querySelectorAll('.fr-disable-button-when-submit')?.forEach(element => {
  element.addEventListener('submit', (event) => {
    if (element.checkValidity()) {
      element.querySelectorAll('button[type=submit]')?.forEach(element => {
        element.setAttribute('disabled', true)
      })
    }
  })
})

const autoSubmitElements = document.querySelectorAll('.fr-auto-submit')
autoSubmitElements.forEach(autoSubmitElements => {
  autoSubmitElements.addEventListener('change', function () {
    document.getElementById('page').value = 1
    this.form.submit()
  })
})

// This function was taken from an old minified file
const checkFieldset = e => {
  const t = e.querySelector('fieldset[aria-required="true"]')
  if (t && t.querySelector('[type="checkbox"]:checked') === null) {
    invalid = t.parentElement
  }
  return !t || (t.querySelector('[type="checkbox"]:checked') === null ? (t.classList.add('fr-fieldset--error'), t?.querySelector('.fr-error-text')?.classList.remove('fr-hidden'), !1) : (t.classList.remove('fr-fieldset--error'), t?.querySelector('.fr-error-text')?.classList.add('fr-hidden'), !0))
}
