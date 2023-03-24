const formBtn = document.querySelector('#signalement-edit-nde-form-submit');

formBtn?.addEventListener('click', evt => {
    // Check fields
    let postForm = true
    if ( !document.querySelector('#signalement-edit-nde-date-entree-before').checked
        && !document.querySelector('#signalement-edit-nde-date-entree-after').checked
        ) {
            document.querySelector('#signalement-edit-nde-date-entree-error').classList.remove('fr-hidden')
            postForm = false
    } else {
        document.querySelector('#signalement-edit-nde-date-entree-error').classList.add('fr-hidden')
    }
    if ( !document.querySelector('#signalement-edit-nde-dpe-0').checked
        && !document.querySelector('#signalement-edit-nde-dpe-1').checked
        && !document.querySelector('#signalement-edit-nde-dpe-2').checked
        ) {
            document.querySelector('#signalement-edit-nde-dpe-error').classList.remove('fr-hidden')
            postForm = false
    } else {
        document.querySelector('#signalement-edit-nde-dpe-error').classList.add('fr-hidden')
    }
    if ( !document.querySelector('#signalement-edit-nde-dernier-bail-before').checked
        && !document.querySelector('#signalement-edit-nde-dernier-bail-after').checked
        ) {
            document.querySelector('#signalement-edit-nde-dernier-bail-error').classList.remove('fr-hidden')
            postForm = false
    } else {
        document.querySelector('#signalement-edit-nde-dernier-bail-error').classList.add('fr-hidden')
    }

    // Post form
    if (postForm) {
        const form = document.querySelector('form#signalement-edit-nde-form');
        const url = form.action;
        const type = form.method;

        const stringToBoolean = (stringValue) => {
            switch(stringValue?.toLowerCase()?.trim()){
                case "true": 
                case "yes": 
                case "1": 
                return true;
        
                case "false": 
                case "no": 
                case "0": 
                return false;
                
                case 'null': 
                case null: 
                case undefined:
                default: 
                return null;
            }
        }

        const data = { 
            _token: document.getElementById('signalement-edit-nde-token').value,
            dateEntree: document.querySelector('input[name=dateEntree]:checked')?.value,
            dpe: stringToBoolean(document.querySelector('input[name=dpe]:checked')?.value),
            dateDernierBail: document.querySelector('input[name=dateDernierBail]:checked')?.value,
            dateDernierDPE: document.querySelector('input[name=dateDernierDPE]:checked')?.value,
            consommationEnergie: Number(document.getElementById('signalement-edit-nde-conso-energie')?.value),
            superficie: Number(document.getElementById('signalement-edit-nde-superficie')?.value),
        };

        const options = {
            method: type,
            headers: {
            "Content-Type": "application/json",
            },
            body: JSON.stringify(data),
        };

        fetch(url, options)
            .then((response) => {
                if (response.ok) {
                    window.location.reload();

                }
            })
            .catch((error) => {
                console.error("Error:", error);
            });
    }
})

if (document.querySelector('#signalement-edit-nde-dpe-date-before')) {
    document.querySelector('#signalement-edit-nde-dpe-date-before').addEventListener('change', (event) => {
        document.querySelector('.field-nde-conso-energie').classList.add('fr-col-6')
        document.querySelector('.field-nde-conso-energie').classList.remove('fr-col-12')
        document.querySelector('.field-nde-conso-energie-unity').classList.add('fr-hidden')
        document.querySelector('.field-nde-superficie').classList.add('fr-col-6')
        document.querySelector('.field-nde-superficie').classList.remove('fr-hidden')
    });
}
if (document.querySelector('#signalement-edit-nde-dpe-date-after')) {
    document.querySelector('#signalement-edit-nde-dpe-date-after').addEventListener('change', (event) => {
        document.querySelector('.field-nde-conso-energie').classList.remove('fr-col-6')
        document.querySelector('.field-nde-conso-energie').classList.add('fr-col-12')
        document.querySelector('.field-nde-conso-energie-unity').classList.remove('fr-hidden')
        document.querySelector('.field-nde-superficie').classList.remove('fr-col-6')
        document.querySelector('.field-nde-superficie').classList.add('fr-hidden')
    });
}
