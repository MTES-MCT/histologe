const containerFormDemandeLienSignalement = document?.querySelector('#container-form-demande-lien-signalement')
if (containerFormDemandeLienSignalement) {
    attachSubmitFormDemandeLienSignalementEvent()
    document.addEventListener('click', function(event) {
        let addressGroup = document?.querySelector('#form-demande-lien-signalement .fr-address-group')
        let isClickInside = addressGroup.contains(event.target);
        if (!isClickInside) {
            addressGroup.innerHTML = ''
        }
    });
}

function attachSubmitFormDemandeLienSignalementEvent() {
    const fomDemandeLienSignalement = document?.querySelector('#form-demande-lien-signalement')
    fomDemandeLienSignalement.addEventListener('submit', (e) => {
        e.preventDefault();
        fomDemandeLienSignalement.querySelector('button[type="submit"]').disabled = true;
        let form = e.target;
        fetch(form.action, { method: form.method, body: new FormData(form) })
            .then(response => response.json())
            .then(json => {
                containerFormDemandeLienSignalement.innerHTML = json.html;
                attachSubmitFormDemandeLienSignalementEvent();
            })
        return false
    })
    const inputAdresse = document?.querySelector('#demande_lien_signalement_adresseHelper')
    const apiAdresse = 'https://api-adresse.data.gouv.fr/search/?q='
    const addressGroup = fomDemandeLienSignalement.querySelector('.fr-address-group')
    inputAdresse.addEventListener('input', (e) => {
        let adresse = e.target.value
        if (adresse.length > 9) {
            fetch(apiAdresse + adresse)
                .then(response => response.json())
                .then(json => {
                    addressGroup.innerHTML = ''
                    json.features.forEach((feature) => {
                        addressGroup.innerHTML += `
                        <div class="fr-col-12 fr-p-3v fr-text-label--blue-france fr-address-suggestion" 
                        data-name="${feature.properties.name}" 
                        data-city="${feature.properties.city}" 
                        data-postcode="${feature.properties.postcode}"
                        >${feature.properties.label}</div>`
                    })
                    let suggestions = addressGroup.querySelectorAll('.fr-address-suggestion')    
                    suggestions.forEach((suggestion) => {
                        attachAddressSuggestionEvent(suggestion)
                    })
                })
        }
        if(adresse.length === 0) {
            addressGroup.innerHTML = ''
            document.querySelector('#demande_lien_signalement_adresseHelper').value = ''
            document.querySelector('#demande_lien_signalement_adresse').value = ''
            document.querySelector('#demande_lien_signalement_codePostal').value = ''
            document.querySelector('#demande_lien_signalement_ville').value = ''
        }
    })
}

function attachAddressSuggestionEvent(suggestion) {
    suggestion.addEventListener('click', (e) => {
        const inputAdresse = document.querySelector('#demande_lien_signalement_adresseHelper')
        inputAdresse.value = suggestion.dataset.name + ' ' + suggestion.dataset.postcode + ' ' + suggestion.dataset.city
        document.querySelector('#demande_lien_signalement_adresse').value = suggestion.dataset.name
        document.querySelector('#demande_lien_signalement_codePostal').value = suggestion.dataset.postcode
        document.querySelector('#demande_lien_signalement_ville').value = suggestion.dataset.city
        suggestion.parentElement.innerHTML = ''
    })
}