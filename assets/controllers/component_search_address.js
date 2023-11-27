document.querySelectorAll('[data-fr-adresse-bo-autocomplete]').forEach((autocomplete) => {
    autocomplete.addEventListener('keyup', () => {
        histoFetchAutocompleteAddress(document, autocomplete)
    })
})

let idFetchAutocompleteAddressTimeout
const urlHistoFetchAutocompleteAddress = 'https://api-adresse.data.gouv.fr/search/?q='
const histoFetchAutocompleteAddress = (e,t) => {
    clearTimeout(idFetchAutocompleteAddressTimeout);
    if (t.classList.contains('search-address-autocomplete')) {
        const idForm = t.getAttribute('data-form-id')
        idFetchAutocompleteAddressTimeout = setTimeout( () => {
            if (t.value.length > 10) {
                t.removeEventListener('keyup', searchAddress)
                fetch(urlHistoFetchAutocompleteAddress + t.value).then((res) => {
                    res.json().then((r) => {
                        e.querySelectorAll('.search-address-autocomplete-list')?.forEach((element) => {
                            element.innerHTML = '';
                            for (let feature of r.features) {
                                let suggestion = e.createElement('div');
                                suggestion.classList.add(
                                    'fr-col-12',
                                    'fr-p-3v',
                                    'fr-text-label--blue-france',
                                    'fr-adresse-suggestion'
                                );
                                suggestion.innerHTML = feature.properties.label;
                                suggestion.addEventListener('click', () => {
                                    histoRefreshOnSuggestionClicked(idForm, e, feature)
                                    element.innerHTML = '';
                                })
                                element.appendChild(suggestion)
                            }
                        });
                    })
                })
                return false;
            }
        }, 300 );
    }
}

const histoRefreshOnSuggestionClicked = (idForm, e,feature) => {
    e.querySelector('#'+idForm+' input[name=adresse-visible]').value = feature.properties.name;
    e.querySelector('#'+idForm+' input[name=adresse]').value = feature.properties.name;
    e.querySelector('#'+idForm+' input[name=codePostal-visible]').value = feature.properties.postcode;
    e.querySelector('#'+idForm+' input[name=codePostal]').value = feature.properties.postcode;
    e.querySelector('#'+idForm+' input[name=ville-visible]').value = feature.properties.city;
    e.querySelector('#'+idForm+' input[name=ville]').value = feature.properties.city;
    if (e.querySelector('#'+idForm+' input[name=insee]')) {
        e.querySelector('#'+idForm+' input[name=insee]').value = feature.properties.citycode;
    }
    if (e.querySelector('#'+idForm+' input[name=geolocLng]')) {
        e.querySelector('#'+idForm+' input[name=geolocLng]').value = feature.geometry.coordinates[0];
        e.querySelector('#'+idForm+' input[name=geolocLat]').value = feature.geometry.coordinates[1];
    }
}