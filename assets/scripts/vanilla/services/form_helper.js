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

const selects = document.querySelectorAll('.fr-select-submit')
selects.forEach(select => {
  select.addEventListener('change', function () {
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

const forms = document.querySelectorAll('form.needs-validation:not([name="bug-report"])')
forms.forEach((form) => {
  form?.querySelectorAll('input[type="file"]')?.forEach((file) => {
    file.addEventListener('change', (event) => {
      if (event.target.files.length > 0) {
        const resTextEl = event.target.parentElement.nextElementSibling
        const fileData = new FormData()
        const deleter = event.target.parentElement.parentElement.querySelector('.signalement-uploadedfile-delete')
        const preview = event.target?.parentElement?.querySelector('img')
        let fileIsOk = false; const file = event.target.files[0]
        const id = event.target.id
        const progress = document.querySelector('#progress_' + id)
        const totalProgress = document.querySelector('#form_global_file_progress')
        if (preview) {
          if (event.target.files[0].type === 'image/heic' || event.target.files[0].type === 'image/heif') {
            event.target.value = ''
            resTextEl.innerHTML = "Les fichiers de format HEIC/HEIF ne sont pas pris en charge, merci de convertir votre image en JPEG ou en PNG avant de l'envoyer."
            resTextEl.classList.remove('fr-hidden')
          } else if (event.target.files[0].size > 10 * 1024 * 1024) {
            event.target.value = ''
            resTextEl.innerHTML = "L'image dépasse 10MB"
            resTextEl.classList.remove('fr-hidden')
          } else {
            preview.src = URL.createObjectURL(file);
            ['fr-icon-camera-fill', 'fr-py-7v', 'fr-icon-refresh-line', 'fr-disabled'].map(v => event.target.parentElement.classList.toggle(v))
            fileIsOk = true
          }
        } else if (event.target.parentElement.classList.contains('fr-icon-attachment-fill')) {
          if (event.target.files[0].type === 'image/heic' || event.target.files[0].type === 'image/heif') {
            event.target.value = ''
            resTextEl.innerHTML = "Les fichiers de format HEIC/HEIF ne sont pas pris en charge, merci de convertir votre image en JPEG ou en PNG avant de l'envoyer."
            resTextEl.classList.remove('fr-hidden')
          } else if (event.target.files[0].size > 10 * 1024 * 1024) {
            event.target.value = ''
            resTextEl.innerHTML = 'Le document dépasse 10MB'
            resTextEl.classList.remove('fr-hidden')
          } else {
            resTextEl.classList.add('fr-hidden')
            fileIsOk = true;
            ['fr-icon-attachment-fill', 'fr-icon-refresh-line', 'fr-disabled'].map(v => event.target.parentElement.classList.toggle(v))
          }
        }
        if (fileIsOk) {
          // [preview, deleter].forEach(el => el?.classList?.remove('fr-hidden'))
          deleter.addEventListeners('click touchdown', (e) => {
            e.preventDefault()
            if (preview) {
              preview.src = '#'
              event.target.parentElement.classList.add('fr-icon-camera-fill')
            } else if (event.target.parentElement.classList.contains('fr-icon-checkbox-circle-fill')) {
              ['fr-icon-attachment-fill', 'fr-icon-checkbox-circle-fill'].map(v => event.target.parentElement.classList.toggle(v))
            } else {
              event.target.parentElement.classList.add('fr-icon-attachment-fill')
            }
            event.target.value = ''
            fileData.delete(event.target.name)
            delete uploadedFiles[event.target.id];
            [preview, deleter].forEach(el => el?.classList.add('fr-hidden'))
            event.target.parentElement.classList.remove('fr-disabled')
            resTextEl.innerText = ''
          })
          fileData.append(event.target.name, file)
          const request = new XMLHttpRequest()
          const finishSubmitBtn = document.querySelector('#form_finish_submit')
          request.open('POST', event.target.getAttribute('data-handle-url'))
          request.upload.addEventListener('progress', function (e) {
            totalProgress.classList.remove('fr-hidden')
            finishSubmitBtn.disabled = true
            finishSubmitBtn.innerHTML = 'Téléversement en cours, veuillez patienter....'
            const activeProgresses = document.querySelectorAll('progress:not(.fr-hidden,.final-progress)')
            const percentCompleted = (e.loaded / e.total) * 100
            let totalPercentCompleted = 0
            activeProgresses.forEach(acp => {
              totalPercentCompleted += acp.value
            })
            totalProgress.value = totalPercentCompleted / activeProgresses.length
            progress.value = percentCompleted
          })
          request.addEventListener('load', function (e) {
            event.target.parentElement.classList.remove('fr-icon-refresh-line');
            [preview, deleter].forEach(el => el?.classList?.remove('fr-hidden'))
            progress.value = 0
            const jsonRes = JSON.parse(request.response)
            if (request.status !== 200) {
              progress.value = 0
              deleter.click()
              resTextEl.innerText = jsonRes.error
              resTextEl.classList.remove('fr-hidden')
              resTextEl.classList.add('fr-text-label--red-marianne')
              console.error(jsonRes.error)
              Sentry.captureException(new Error(error))
            } else {
              resTextEl.innerText = jsonRes.titre
              resTextEl.classList.remove('fr-hidden')
              resTextEl.classList.add('fr-text-label--green-emeraude')
              if (!preview) { ['fr-icon-checkbox-circle-fill'].map(v => event.target.parentElement.classList.toggle(v)) }
              uploadedFiles[event.target.id] = request.response
            }
            progress.classList.add('fr-hidden')
            event.target.value = ''
            if (document.querySelectorAll('progress:not(.fr-hidden,.final-progress)').length < 1) {
              totalProgress.classList.add('fr-hidden')
              finishSubmitBtn.innerHTML = 'Confirmer'
              finishSubmitBtn.disabled = false
            }
          })
          progress.classList.remove('fr-hidden')
          request.send(fileData)
          event.target.parentElement.classList.add('fr-disabled')
        }
      }
    })
  })
  form.addEventListener('submit', (event) => {
    event.preventDefault()
    if (!form.checkValidity() || !checkFieldset(form)) {
      event.stopPropagation()
      form.querySelectorAll('input,textarea,select,fieldset[aria-required="true"]').forEach((field) => {
        if (field.tagName === 'FIELDSET') {
          if (!checkFieldset(form)) {
            field.addEventListener('change', () => {
              checkFieldset(form)
            })
            invalid = field.parentElement
          }
        } else if (!field.checkValidity()) {
          let parent = field.parentElement
          if (field.type === 'radio') { parent = field.parentElement.parentElement.parentElement }
          [field.classList, parent.classList].forEach((f) => {
            f.add(f[0] + '--error')
          })
          parent?.querySelector('.fr-error-text')?.classList.remove('fr-hidden')
          field.addEventListener('input', () => {
            if (field.checkValidity()) {
              [field.classList, parent.classList].forEach((f) => {
                f.remove(f[0] + '--error')
              })
              parent.querySelector('.fr-error-text')?.classList.add('fr-hidden')
            }
          })
          invalid = form?.querySelector('*:invalid:first-of-type')?.parentElement
        }
      })
      if (invalid) {
        const y = invalid.getBoundingClientRect().top + window.scrollY
        window.scroll({
          top: y,
          behavior: 'smooth'
        })
      }
    } else {
      Object.keys(uploadedFiles).forEach((f, index) => {
        const fi = JSON.parse(uploadedFiles[f])
        form.insertAdjacentHTML('beforeend', `<input type="hidden" name="signalement[files][${fi.key}][${fi.titre}]" value="${fi.file}">`)
      })
      form.submit()
    }
  })
})
