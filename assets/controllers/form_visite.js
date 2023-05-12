const dateFields = document.querySelectorAll('.add-fields-if-past-date')
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

histoCheckVisiteForms('add')
histoCheckVisiteForms('reschedule')
histoCheckVisiteForms('confirm')

function histoCheckVisiteForms(formType) {
    const visiteForms = document.querySelectorAll('.signalement-'+formType+'-visite')
    if (!visiteForms) {
        return
    }
    
    visiteForms.forEach(visiteForm => {
        visiteForm.addEventListener('submit', evt => {
            const listInputVisiteDoneError = visiteForm.querySelector('#signalement-confirm-visite-done-error')
            const listInputOccupantPresentError = visiteForm.querySelector('#signalement-confirm-visite-occupant-present-error')
            const selectConcludeProcedureError = visiteForm.querySelector('#signalement-confirm-visite-procedure-error')
            const textareaDetailsError = visiteForm.querySelector('#signalement-confirm-visite-details-error')
            listInputVisiteDoneError.classList.add('fr-hidden')
            listInputOccupantPresentError.classList.add('fr-hidden')
            selectConcludeProcedureError.classList.add('fr-hidden')
            textareaDetailsError.classList.add('fr-hidden')
        
            const dateField = visiteForm.querySelector('.add-fields-if-past-date')
            let todayDate = new Date()
            if (!dateField || dateField.value <= todayDate.toISOString().split('T')[0]) {
                let stopSubmit = false
        
                let isVisiteDone = false
                let hasCheckedVisiteDone = false
                const listInputVisiteDone = visiteForm.querySelectorAll('input[name="visite-'+formType+'[visiteDone]"]')
                listInputVisiteDone.forEach(checkField => {
                    if (checkField.checked) {
                        hasCheckedVisiteDone = true
                        if (checkField.value === '1') {
                            isVisiteDone = true
                        }
                    }
                })
                if (!hasCheckedVisiteDone) {
                    listInputVisiteDoneError.classList.remove('fr-hidden')
                    stopSubmit = true
                }
                let hasCheckedOccupantPresent = false
                const listInputIOccupantPresent = visiteForm.querySelectorAll('input[name="visite-'+formType+'[occupantPresent]"]')
                listInputIOccupantPresent.forEach(checkField => {
                    if (checkField.checked) {
                        hasCheckedOccupantPresent = true
                    }
                })
                if (!hasCheckedOccupantPresent) {
                    listInputOccupantPresentError.classList.remove('fr-hidden')
                    stopSubmit = true
                }
                if (isVisiteDone) {
                    const selectConcludeProcedure = visiteForm.querySelector('select[name="visite-'+formType+'[concludeProcedure]"]')
                    if (selectConcludeProcedure.value == '') {
                        selectConcludeProcedureError.classList.remove('fr-hidden')
                        stopSubmit = true
                    }
                }
                
                const tinyMCE = tinymce.get('visite-'+formType+'[details]')
                const textContent = tinyMCE ? tinyMCE.getContent() : ''
                if (textContent == '') {
                    textareaDetailsError.classList.remove('fr-hidden')
                    stopSubmit = true
                }
        
                if (stopSubmit) {
                    evt.preventDefault()
                }
            }
        })

        const listInputVisiteDone = visiteForm.querySelectorAll('input[name="visite-'+formType+'[visiteDone]"]')
        listInputVisiteDone.forEach(checkField => {
            checkField.addEventListener('change', evt => {
                const isVisiteDone = (evt.currentTarget.value === '1')
                const fieldsetConcludeProcedure = visiteForm.querySelector('#fieldset-conclude-procedure')
                if (isVisiteDone) {
                    fieldsetConcludeProcedure.classList.remove('fr-hidden')
                } else {
                    fieldsetConcludeProcedure.classList.add('fr-hidden')
                    const selectConcludeProcedure = visiteForm.querySelector('select[name="visite-'+formType+'[concludeProcedure]"]')
                    if (selectConcludeProcedure) {
                        selectConcludeProcedure.value = ''
                    }
                }
            })
        })
    })
}

const cancelVisiteForms = document.querySelectorAll('form[name="signalement-cancel-visite"]')
cancelVisiteForms.forEach(cancelVisiteForm => {
    cancelVisiteForm.addEventListener('submit', evt => {
        const tinyMCE = tinymce.get('visite-cancel[details]')
        const textContent = tinyMCE ? tinyMCE.getContent() : ''
        if (textContent == '') {
            const textareaDetailsError = cancelVisiteForm.querySelector('#signalement-cancel-visite-details-error')
            textareaDetailsError.classList.remove('fr-hidden')
            evt.preventDefault()
        } else {
            textareaDetailsError.classList.add('fr-hidden')
        }
    })
})