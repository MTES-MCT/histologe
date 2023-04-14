const dateFields = document.querySelectorAll('.add-fields-if-past-date');
dateFields.forEach(dateField => {
    dateField.addEventListener('change', evt => {
        let fieldToToggle = dateField.dataset.fields
        let todayDate = new Date()
        if (dateField.value <= todayDate.toISOString().split('T')[0]) {
            document.querySelector('#' + fieldToToggle).classList.remove('fr-hidden')
        } else {
            document.querySelector('#' + fieldToToggle).classList.add('fr-hidden')
        }
    })
})