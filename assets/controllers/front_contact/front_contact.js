const containerFormContact = document?.querySelector('#container-form-contact')
if (containerFormContact) {
    attachSubmitFormContactEvent()
}

function attachSubmitFormContactEvent() {
    const formContact = document?.querySelector('#front_contact')
    formContact.addEventListener('submit', (e) => {
        e.preventDefault();
        formContact.querySelector('button[type="submit"]').disabled = true;
        let form = e.target;
        fetch(form.action, { method: form.method, body: new FormData(form) })
            .then(response => response.json())
            .then(json => {
                containerFormContact.innerHTML = json.html;
                attachSubmitFormContactEvent()
            })
    })
}