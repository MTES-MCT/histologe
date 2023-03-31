document.querySelectorAll('.fr-disable-button-when-submit')?.forEach(element => {
    element.addEventListener('submit', (event) => {
        if (element.checkValidity()) {
            element.querySelectorAll('button[type=submit]')?.forEach(element => {
                element.setAttribute('disabled', true);
            })
        }
    })
})
