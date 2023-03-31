const territory = document.querySelector('#user_territory');

territory?.addEventListeners('change', (event) => {
    const form = document.querySelector('form#account_user');
    const partner = document.querySelector('#user_partner');
    const url = form.action;
    const type = form.method;

    const options = {
        method: type,      
        headers: {},
        body: new FormData(form)
    };

    fetch(url, options)
        .then(function (response) {
            return response.text();
        })
        .then(function (html) {
            const parser = new DOMParser();
            const receivedDoc = parser.parseFromString(html, 'text/html');
            const newPartner = receivedDoc.querySelector('#user_partner');
            if (null !== newPartner ){
                newPartner.addEventListener('change', function(e) {
                    e.target.classList.remove('is-invalid');
                });
                partner.replaceWith(newPartner);
            }
        })
        .catch(function (err) {
            console.warn('Something went wrong.', err);
        });
})

if (document.querySelector('#partner_type')) {
    document.querySelector('#partner_type').addEventListener('change', (event) => {
        // TODO améliorer
        var x = document.getElementById("partner_type");
        x.value = x.value.toUpperCase();
        if (x.value === 'COMMUNE_SCHS'){
            document.querySelector('#partner_create_zone_pdl').classList.remove('fr-hidden')
            document.querySelector('#partner_create_esabora_title').classList.remove('fr-hidden')
            document.querySelector('#partner_create_esabora_div').classList.remove('fr-hidden')
        } else if (x.value === 'EPCI'){
            document.querySelector('#partner_create_zone_pdl').classList.remove('fr-hidden')
            document.querySelector('#partner_create_esabora_title').classList.add('fr-hidden')
            document.querySelector('#partner_create_esabora_div').classList.add('fr-hidden')
        }else{
            document.querySelector('#partner_create_zone_pdl').classList.add('fr-hidden')
            document.querySelector('#partner_create_esabora_title').classList.add('fr-hidden')
            document.querySelector('#partner_create_esabora_div').classList.add('fr-hidden')
        }
    });
}
