document.querySelectorAll('.fr-disable-button-when-submit')?.forEach(element => {
    element.addEventListener('submit', (event) => {
        if (element.checkValidity()) {
            element.querySelectorAll('button[type=submit]')?.forEach(element => {
                element.setAttribute('disabled', true);
            })
        }
    })
})

const selects = document.querySelectorAll('.fr-select-submit');
selects.forEach(select => {
    select.addEventListener('change', function() {
        this.form.submit();
    });
});