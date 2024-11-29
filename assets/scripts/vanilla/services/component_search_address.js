if (document.querySelector('[data-fr-adresse-autocomplete]')) {
  attachAutocompleteClickOutsideEvent()
}
document.querySelectorAll('[data-fr-adresse-autocomplete]').forEach((inputAdresse) => {
  attacheAutocompleteAddressEvent(inputAdresse)
})

document.querySelector('#form-edit-address-adresse')?.addEventListener('input', (event) => setManualEdit(event.target, false))
document.querySelector('#form-edit-address-codepostal')?.addEventListener('input', (event) => setManualEdit(event.target, true))
document.querySelector('#form-edit-address-ville')?.addEventListener('input', (event) => setManualEdit(event.target, true))

function attachAutocompleteClickOutsideEvent () {
  document.addEventListener('click', function (event) {
    document?.querySelectorAll('.fr-address-group, .fr-address-group-bo').forEach((addressGroup) => {
      const isClickInside = addressGroup.contains(event.target)
      if (!isClickInside) {
        addressGroup.innerHTML = ''
      }
    })
  })
}

export function attacheAutocompleteAddressEvent (inputAdresse) {
  if (!inputAdresse) {
    return false
  }

  let selectionIndex = -1
  const addressGroup = document?.querySelector(inputAdresse.dataset.autocompleteQuerySelector)
  const apiAdresse = 'https://api-adresse.data.gouv.fr/search/?q='
  inputAdresse.addEventListener('input', (e) => {
    const adresse = e.target.value
    if (adresse.length > 8) {
      let query = apiAdresse + adresse
      const limit = inputAdresse.getAttribute('data-form-limit')
      if (inputAdresse.getAttribute('data-form-lat')) {
        query += '&lat=' + inputAdresse.getAttribute('data-form-lat')
      }
      if (inputAdresse.getAttribute('data-form-lng')) {
        query += '&lon=' + inputAdresse.getAttribute('data-form-lng')
      }
      fetch(query)
        .then(response => response.json())
        .then(json => {
          addressGroup.innerHTML = ''
          json.features.forEach((feature) => {
            if (limit === null || feature.properties.citycode.startsWith(limit)) {
              const suggestion = document.createElement('div')
              suggestion.classList.add(
                'fr-col-12',
                'fr-p-3v',
                'fr-text-label--blue-france',
                'fr-adresse-suggestion'
              )
              suggestion.innerHTML = feature.properties.label
              attachAddressSuggestionEvent(inputAdresse, suggestion, feature)
              addressGroup.appendChild(suggestion)
            }
          })
        })
    }
    if (adresse.length === 0) {
      addressGroup.innerHTML = ''
      const idForm = inputAdresse.closest('form').id
      if (document?.querySelector('#' + idForm + ' [data-autocomplete-addresse]')) {
        document.querySelector('#' + idForm + ' [data-autocomplete-addresse]').value = ''
      }
      if (document?.querySelector('#' + idForm + ' [data-autocomplete-codepostal]')) {
        document.querySelector('#' + idForm + ' [data-autocomplete-codepostal]').value = ''
      }
      if (document?.querySelector('#' + idForm + ' [data-autocomplete-ville]')) {
        document.querySelector('#' + idForm + ' [data-autocomplete-ville]').value = ''
      }
      if (document?.querySelector('#' + idForm + ' [data-autocomplete-insee]')) {
        document.querySelector('#' + idForm + ' [data-autocomplete-insee]').value = ''
      }
    }
  })

  inputAdresse.addEventListener('keydown', (e) => {
    if (e.key === 'Escape' || e.key === 'Tab') {
      addressGroup.innerHTML = ''
      selectionIndex = -1
    }

    if (e.key === 'ArrowDown') {
      if (addressGroup.children.length > 0) {
        selectionIndex = Math.min(selectionIndex + 1, addressGroup.children.length - 1)
      } else {
        selectionIndex = -1
      }
    }
    if (e.key === 'ArrowUp') {
      if (addressGroup.children.length > 0) {
        selectionIndex = Math.max(selectionIndex - 1, 0)
      } else {
        selectionIndex = -1
      }
    }
    if (e.key === 'Enter') {
      e.preventDefault() // avoids form submit
      if (selectionIndex > -1) {
        addressGroup.children[selectionIndex].click()
      }
      addressGroup.innerHTML = ''
      selectionIndex = -1
    }

    document.querySelectorAll('.fr-adresse-suggestion').forEach((element) => {
      element.classList.remove('fr-autocomplete-suggestion-highlighted')
    })
    if (selectionIndex > -1) {
      addressGroup.children[selectionIndex].classList.add('fr-autocomplete-suggestion-highlighted')
    }
  })
}

function attachAddressSuggestionEvent (inputAdresse, suggestion, feature) {
  suggestion.addEventListener('click', (e) => {
    const idForm = inputAdresse.closest('form').id
    inputAdresse.value = feature.properties.label
    if (document?.querySelector('#' + idForm + ' [data-autocomplete-addresse]')) {
      document.querySelector('#' + idForm + ' [data-autocomplete-addresse]').value = feature.properties.name
    }
    if (document?.querySelector('#' + idForm + ' [data-autocomplete-codepostal]')) {
      document.querySelector('#' + idForm + ' [data-autocomplete-codepostal]').value = feature.properties.postcode
    }
    if (document?.querySelector('#' + idForm + ' [data-autocomplete-ville]')) {
      document.querySelector('#' + idForm + ' [data-autocomplete-ville]').value = feature.properties.city
    }
    if (document?.querySelector('#' + idForm + ' [data-autocomplete-manual]')) {
      document.querySelector('#' + idForm + ' [data-autocomplete-manual]').value = 0
    }
    if (document?.querySelector('#' + idForm + ' [data-autocomplete-need-reset-insee]')) {
      document.querySelector('#' + idForm + ' [data-autocomplete-need-reset-insee]').value = 0
    }
    if (document?.querySelector('#' + idForm + ' [data-autocomplete-insee]')) {
      document.querySelector('#' + idForm + ' [data-autocomplete-insee]').value = feature.properties.citycode
    }
    const addressGroup = document?.querySelector(inputAdresse.dataset.autocompleteQuerySelector)
    addressGroup.innerHTML = ''
  })
}

function setManualEdit (input, needResetInsee) {
  const idForm = input.closest('form').id
  document.querySelector('#' + idForm + ' [data-autocomplete-manual]').value = 1
  if (needResetInsee) {
    document.querySelector('#' + idForm + ' [data-autocomplete-need-reset-insee]').value = 1
  }
}
