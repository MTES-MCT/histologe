const $territory = document.querySelector('#user_territory');
const $partner = document.querySelector('#user_partner');

$territory?.addEventListeners('change', (event) => {
    const $form = document.querySelector('form#account_user');
    const url = $form.action
    const type = $form.getAttribute('method')

    const options = {
        method: type,      
        headers: {},
        body: new FormData($form)
    };

    fetch(url, options)
        .then(function (response) {
            // The API call was successful!
            return response.text();
        })
        .then(function (html) {
            // This is the HTML from our response as a text string
            // console.log(html);
            // Convert the HTML string into a document object
            var parser = new DOMParser();
            var doc = parser.parseFromString(html, 'text/html');
            const newPartner = doc.querySelector('#user_partner')
            if (null !== newPartner ){
                newPartner.addEventListener('change', function(e) {
                    e.target.classList.remove('is-invalid');
                });

                console.log(newPartner)
                // Replace current position field ...
                $partner.replaceWith(newPartner);

            }else {
                // vider la    liste ?
            }
        })
        .catch(function (err) {
            // There was an error
            console.warn('Something went wrong.', err);
        });


})

