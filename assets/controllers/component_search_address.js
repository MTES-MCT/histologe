if(document.querySelector('[data-fr-adresse-autocomplete]')){
    attachAutocompleteClickOutsideEvent()
}
document.querySelectorAll('[data-fr-adresse-autocomplete]').forEach((inputAdresse) => {
    attacheAutocompleteAddressEvent(inputAdresse)
})

document.querySelector('#form-edit-address-adresse')?.addEventListener('input', (event) => {
    let idForm = event.target.closest('form').id
    document.querySelector('#' + idForm + ' [data-autocomplete-manual]').value = 1
});
document.querySelector('#form-edit-address-codepostal')?.addEventListener('input', (event) => addressResetGeoloc(event.target));
document.querySelector('#form-edit-address-ville')?.addEventListener('input', (event) => addressResetGeoloc(event.target));

function attachAutocompleteClickOutsideEvent() {
    document.addEventListener('click', function (event) {
        document?.querySelectorAll('.fr-address-group, .fr-address-group-bo').forEach((addressGroup) => {
            let isClickInside = addressGroup.contains(event.target)
            if (!isClickInside) {
                addressGroup.innerHTML = ''
            }
        })
    })
}

export function attacheAutocompleteAddressEvent(inputAdresse) {
    const apiAdresse = 'https://api-adresse.data.gouv.fr/search/?q='
    const addressGroup = document?.querySelector(inputAdresse.dataset.autocompleteQuerySelector)
    inputAdresse.addEventListener('input', (e) => {
        let adresse = e.target.value
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
                            let suggestion = document.createElement('div')
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
            let idForm = inputAdresse.closest('form').id
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
            if (document?.querySelector('#' + idForm + ' [data-autocomplete-geoloclng]')) {
                document.querySelector('#' + idForm + ' [data-autocomplete-geoloclng]').value = ''
            }
            if (document?.querySelector('#' + idForm + ' [data-autocomplete-geoloclat]')) {
                document.querySelector('#' + idForm + ' [data-autocomplete-geoloclat]').value = ''
            }
        }
    })
}

function attachAddressSuggestionEvent(inputAdresse, suggestion, feature) {
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
        if (document?.querySelector('#' + idForm + ' [data-autocomplete-insee]')) {
            document.querySelector('#' + idForm + ' [data-autocomplete-insee]').value = feature.properties.citycode
        }
        if (document?.querySelector('#' + idForm + ' [data-autocomplete-geolocLng]')) {
            document.querySelector('#' + idForm + ' [data-autocomplete-geolocLng]').value = feature.geometry.coordinates[0]
        }
        if (document?.querySelector('#' + idForm + ' [data-autocomplete-geolocLat]')) {
            document.querySelector('#' + idForm + ' [data-autocomplete-geolocLat]').value = feature.geometry.coordinates[1]
        }
        const addressGroup = document?.querySelector(inputAdresse.dataset.autocompleteQuerySelector)
        addressGroup.innerHTML = ''
    })
}

var idTimeout
function addressResetGeoloc(input) {
    clearTimeout(idTimeout)
    idTimeout = setTimeout(() => {
        const apiAdresse = 'https://api-adresse.data.gouv.fr/search/?q='
        let newCodePostal = document.querySelector('#form-edit-address-codepostal')?.value
        let newVille = document.querySelector('#form-edit-address-ville')?.value
        let idForm = input.closest('form').id
        if (newCodePostal !== '' && newVille !== '') {
            let query = apiAdresse + newCodePostal + ' ' + newVille
            fetch(query)
                .then(response => response.json())
                .then(json => {
                    json.features.forEach((feature) => {
                        if (feature.properties.citycode) {
                            document.querySelector('#' + idForm + ' [data-autocomplete-ville]').value = feature.properties.city
                            document.querySelector('#' + idForm + ' [data-autocomplete-manual]').value = 1
                            document.querySelector('#' + idForm + ' [data-autocomplete-insee]').value = feature.properties.citycode
                            document.querySelector('#' + idForm + ' [data-autocomplete-geoloclng]').value = feature.geometry.coordinates[0]
                            document.querySelector('#' + idForm + ' [data-autocomplete-geoloclat]').value = feature.geometry.coordinates[1]
                        }
                    })
                })
        }
    }, 1000)
}