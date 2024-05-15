initializeUploadModal(
    '#fr-modal-upload-files',
    '#select-type-situation-to-clone',
    '#select-type-procedure-to-clone',
    '#select-desordre-to-clone',
);

document?.querySelectorAll('.fr-modal-visites-upload-files')?.forEach(modalVisiteUpload => {
    initializeUploadModal(
        '#'+modalVisiteUpload.id,
        null,
        null,
        null,
    )
})


function initializeUploadModal(
    modalSelector, 
    selectTypeSituationToCloneSelector, 
    selectTypeProcedureToCloneSelector, 
    selectDesordreToCloneSelector, 
) {
    const modal = document?.querySelector(modalSelector);
    if (!modal) return;

    const dropArea = modal.querySelector('.modal-upload-drop-section');
    const listContainer = modal.querySelector('.modal-upload-list');
    const fileSelector = modal.querySelector('.modal-upload-files-selector')
    const fileSelectorInput = modal.querySelector('.modal-upload-files-selector-input')
    const addFileRoute = modal.dataset.addFileRoute;
    const addFileToken = modal.dataset.addFileToken;
    const waitingSuiviRoute = modal.dataset.waitingSuiviRoute;
    const deleteTmpFileRoute = modal.dataset.deleteTmpFileRoute;
    const selectTypeSituationToClone = selectTypeSituationToCloneSelector ? modal.querySelector(selectTypeSituationToCloneSelector) : null;
    const selectTypeProcedureToClone = selectTypeProcedureToCloneSelector ? modal.querySelector(selectTypeProcedureToCloneSelector) : null;
    const selectDesordreToClone = selectDesordreToCloneSelector ? modal.querySelector(selectDesordreToCloneSelector) : null;    
    const editFileRoute = modal.dataset.editFileRoute;
    const editFileToken = modal.dataset.editFileToken;
    const btnValidate = modal.querySelector('#btn-validate-modal-upload-files');
    const ancre = modal.querySelector('#modal-upload-file-dynamic-content');


    fileSelector.onclick = () => fileSelectorInput.click()
    fileSelectorInput.onchange = () => {
        [...fileSelectorInput.files].forEach((file) => {
            if (typeValidation(file)) {
                uploadFile(file)
            }
        })
        ancre.scrollIntoView({block: 'start'});
    }

    dropArea.ondragover = (e) => {
        e.preventDefault();
        [...e.dataTransfer.items].forEach((item) => {
            dropArea.classList.add('drag-over-effect')
        })
    }

    dropArea.ondragleave = () => {
        dropArea.classList.remove('drag-over-effect')
    }

    dropArea.ondrop = (e) => {
        e.preventDefault();
        dropArea.classList.remove('drag-over-effect')
        if (e.dataTransfer.items) {
            [...e.dataTransfer.items].forEach((item) => {
                if (item.kind === 'file') {
                    const file = item.getAsFile()
                    if (typeValidation(file)) {
                        uploadFile(file)
                    }
                }
            })
        } else {
            [...e.dataTransfer.files].forEach((file) => {
                if (typeValidation(file)) {
                    uploadFile(file)
                }
            })
        }
        ancre.scrollIntoView({block: 'start'});
    }

    function typeValidation(file) {
        let acceptedType = fileSelectorInput.getAttribute('accept');
        if (acceptedType == '*/*') {
            return true
        }
        let acceptedTypes = acceptedType.split(',').map((type) => type.trim())
        if (acceptedTypes.includes(file.type)) {
            return true
        }
        let div = document.createElement('div')
        div.classList.add('fr-alert', 'fr-alert--error', 'fr-alert--sm')
        div.innerHTML = 'Impossible d\'ajouter le fichier ' +file.name +' car le format n\'est pas pris en charge. Veuillez sélectionner un fichier au format ' +modal.dataset.acceptedExtensions+'.';
        listContainer.prepend(div)
        return false;
    }

    function uploadFile(file) {
        let div = document.createElement('div')
        div.classList.add('fr-grid-row', 'fr-grid-row--gutters', 'fr-grid-row--middle', 'fr-mb-2w', 'modal-upload-list-item')
        div.innerHTML = initInnerHtml(file)
        let btnDeleteTmpFile = div.querySelector('a.delete-tmp-file')
        addEventListenerDeleteTmpFile(btnDeleteTmpFile)
        listContainer.prepend(div)
        let http = new XMLHttpRequest()
        let data = new FormData()
        if (modal.dataset.fileType == 'photo') {
            data.append('signalement-add-file[photos][]', file)
        } else {
            if (modal.dataset.fileFilter == 'procedure') {
                data.append('documentType', 'AUTRE_PROCEDURE')
            }
            data.append('signalement-add-file[documents][]', file)
        }
        data.append('_token', addFileToken)
        http.upload.onprogress = (e) => {
            let percent_complete = (e.loaded / e.total) * 100
            div.querySelectorAll('span')[0].style.width = percent_complete + '%'
            div.querySelectorAll('span')[1].innerHTML = Math.round(percent_complete) + '%'
        }
        http.onreadystatechange = function () {
            if (this.readyState == XMLHttpRequest.DONE) {
                let response = JSON.parse(this.response)
                if (this.status == 200) {
                    modal.dataset.hasChanges = true
                    if (null !== selectDesordreToClone || null !== selectTypeSituationToClone || null !== selectTypeProcedureToClone){
                        let clone
                        if (modal.dataset.fileType == 'photo') {
                            clone = selectDesordreToClone.cloneNode(true)
                            clone.id = 'select-desordre-' + response.response
                        }else{
                            if ('situation' === modal.dataset.fileFilter ){
                                clone = selectTypeSituationToClone.cloneNode(true)
                            } else {
                                clone = selectTypeProcedureToClone.cloneNode(true)
                            }
                            clone.id = 'select-type-' + response.response
                        }
                        clone.dataset.fileId = response.response
                        if (clone.querySelectorAll('option').length == 1) {
                            clone.remove()
                        } else {
                            div.querySelector('.select-container').appendChild(clone)
                            addEventListenerSelectTypeDesordre(clone)
                        }
                    } else {
                        if (null !== modal.dataset.documentType){
                            let divFileId = div.querySelector('#file-id')
                            divFileId.value = response.response
                            callEditFileRoute(div)
                            addEventListenerDescription(div)
                        }

                    }
                    btnDeleteTmpFile.href = btnDeleteTmpFile.href.replace('REPLACE', response.response)
                    btnDeleteTmpFile.classList.remove('fr-hidden', 'delete-html')
                } else {
                    div.querySelector('.file-error').innerHTML = '<div class="fr-alert fr-alert--error fr-alert--sm">' + response.response + '</div>'
                    btnDeleteTmpFile.classList.remove('fr-hidden')
                }
            }
        }
        http.open('POST', addFileRoute, true)
        http.setRequestHeader("X-Requested-With", "XMLHttpRequest")
        http.send(data)
    }

    function initInnerHtml(file) {
        let innerHTML =`<div class="fr-col-12 file-error"></div>`;
        if (modal.dataset.fileType == 'photo') {
            innerHTML += `
            <div class="fr-col-2">
                <img class="fr-content-media__img" src="${URL.createObjectURL(file)}">
            </div>
            <div class="fr-col-6">`
        } else {
            innerHTML += `<div class="fr-col-8">`
        }
        innerHTML += `
            <div class="file-name">
                <div class="name">${file.name}</div>
            </div>
            <div class="file-progress">
                <span></span>
            </div>
            <div class="file-size">
                <div class="size">${(file.size / (1024 * 1024)).toFixed(2)} MB</div>
                <span>0%</span>
            </div>
        </div>
        `
        if (modal.dataset.documentType == 'PHOTO_VISITE') {
            innerHTML += `
            <div class="fr-col-3">                
                <input type="text" id="file-description" name="file[description]"
                required="required" class="fr-input" placeholder="Description de l'image" maxlength=250>
                <input type="hidden" id="file-id" name="file[id]">
            </div>           
            `
        } else{
            innerHTML += `<div class="fr-col-3 select-container">           
            </div>
            <input type="hidden" id="file-id" name="file[id]">`
        }
        innerHTML += `<div class="fr-col-1">
            <a href="${deleteTmpFileRoute}" title="Supprimer" class="fr-btn fr-btn--sm fr-btn--secondary fr-background--white fr-fi-delete-line fr-hidden delete-tmp-file delete-html"></a>         
        </div>
        `
        return innerHTML
    }

    function addEventListenerSelectTypeDesordre(select) {
        select.addEventListener('change', function () {
            let selectField = this
            let http = new XMLHttpRequest()
            let data = new FormData()
            data.append('file_id', selectField.dataset.fileId)
            if (modal.dataset.fileType == 'photo') {
                data.append('documentType', 'PHOTO_SITUATION')
                data.append('desordreSlug', selectField.value)
            } else {
                data.append('documentType', selectField.value)
            }
            data.append('_token', editFileToken)
            http.onreadystatechange = function () {
                if (this.readyState == XMLHttpRequest.DONE) {
                    let response = JSON.parse(this.response)
                    let parent = selectField.closest('.modal-upload-list-item')
                    if (this.status == 200) {
                        parent.querySelector('.file-error').innerHTML = ''
                    } else {
                        parent.querySelector('.file-error').innerHTML = '<div class="fr-alert fr-alert--error fr-alert--sm">' + response.response + '</div>'
                    }
                }
            }
            http.open('POST', editFileRoute, true)
            http.setRequestHeader("X-Requested-With", "XMLHttpRequest")
            http.send(data)
        })
    }

    function addEventListenerDeleteTmpFile(button) {
        button.addEventListener('click', (e) => {
            e.preventDefault()
            button.setAttribute('disabled', '')
            if (button.classList.contains('delete-html')) {
                button.closest('.fr-grid-row').remove()
                return true
            }
            fetch(button.href, { method: 'DELETE' })
                .then((response) => response.json())
                .then((data) => {
                    if (data.success) {
                        button.closest('.fr-grid-row').remove()
                    } else {
                        button.removeAttribute('disabled')
                    }
                })
        })
    }

    function callEditFileRoute(divFileItem) {
        let httpEdit = new XMLHttpRequest()
        let dataEdit = new FormData()
        dataEdit.append('file_id', divFileItem.querySelector('#file-id')?.value)
        dataEdit.append('documentType', modal.dataset.documentType)            
        dataEdit.append('interventionId', modal.dataset.interventionId) 
        dataEdit.append('description', divFileItem.querySelector('#file-description')?.value)

        dataEdit.append('_token', editFileToken)
        httpEdit.onreadystatechange = function () {
            if (this.readyState == XMLHttpRequest.DONE) {
                let response = JSON.parse(this.response)
                if (this.status != 200) {
                    let parent = divFileItem
                    parent.querySelector('.file-error').innerHTML = '<div class="fr-alert fr-alert--error fr-alert--sm">' + response.response + '</div>'

                }
            }
        }
        httpEdit.open('POST', editFileRoute, true)
        httpEdit.setRequestHeader("X-Requested-With", "XMLHttpRequest")
        httpEdit.send(dataEdit)
    }

    function addEventListenerDescription(divFileItem) {
            listContainer?.querySelectorAll('.fr-grid-row')?.forEach(divFileItem => {
                divFileItem.querySelector('#file-description')?.addEventListener('change', (e) => {
                    callEditFileRoute(divFileItem)
                })
            })
    }


    modal.addEventListener('dsfr.conceal', (e) => {
        if (modal.dataset.validated == "true" && modal.dataset.hasChanges == "true") {
            fetch(waitingSuiviRoute).then((response) => {
                window.location.reload()
                window.scrollTo(0, 0)
            })
            return true;
        }
        document.querySelectorAll('a.delete-tmp-file').forEach((button) => {
            button.click()
        })
        modal.dataset.hasChanges = false
    })

    let fileType, fileFilter, documentType, interventionId, acceptedTypeMimes, acceptedExtensions
    document.querySelectorAll('.open-modal-upload-files-btn').forEach((button) => {
        button.addEventListener('click', (e) => {
            fileType = e.target.dataset.fileType
            fileFilter = e.target.dataset.fileFilter ?? null
            documentType = e.target.dataset.documentType ?? null
            interventionId = e.target.dataset.interventionId ?? null
            acceptedTypeMimes = e.target.dataset.acceptedTypeMimes ?? null
            acceptedExtensions = e.target.dataset.acceptedExtensions ?? null
        })
    })

    modal.addEventListener('dsfr.disclose', (e) => {
        listContainer.innerHTML = '';
        modal.dataset.validated = false
        modal.dataset.hasChanges = false
        modal.querySelectorAll('.type-conditional').forEach((type) => {
            type.classList.add('fr-hidden')
        })
        modal.querySelectorAll('.filter-conditional').forEach((type) => {
            type.classList.add('fr-hidden')
        })
        modal.dataset.documentType = documentType
        modal.dataset.fileFilter = fileFilter
        modal.dataset.interventionId = interventionId    
        modal.dataset.acceptedExtensions = acceptedExtensions        
        if (fileType == 'photo') {
            modal.dataset.fileType = 'photo'
            modal.querySelector('.type-photo').classList.remove('fr-hidden')

        } else {
            modal.dataset.fileType = 'document'
            modal.querySelector('.type-document').classList.remove('fr-hidden')
        }   
        if (null !== acceptedTypeMimes) {
            fileSelectorInput.setAttribute('accept', acceptedTypeMimes)
        }else{
            fileSelectorInput.setAttribute('accept', '*/*')
        }
        if (fileFilter == 'procedure') {
            modal.querySelector('.filter-procedure').classList.remove('fr-hidden')
        } else if (fileFilter == 'situation')  {
            modal.querySelector('.filter-situation').classList.remove('fr-hidden')
        }
    })

    btnValidate.addEventListener('click', (e) => {
        modal.dataset.validated = true
    })
}