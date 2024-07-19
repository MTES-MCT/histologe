const modalUploadFiles = document?.querySelector('#fr-modal-upload-files-usager')
if (modalUploadFiles) {

    const dropArea = document.querySelector('.modal-upload-drop-section');
    const listContainer = document.querySelector('.modal-upload-list');
    const fileSelector = document.querySelector('.modal-upload-files-selector')
    const fileSelectorInput = document.querySelector('.modal-upload-files-selector-input')
    const addFileRoute = modalUploadFiles.dataset.addFileRoute;
    const addFileToken = modalUploadFiles.dataset.addFileToken;
    const deleteTmpFileRoute = modalUploadFiles.dataset.deleteTmpFileRoute;
    const selectTypeSituationToClone = document.querySelector('#select-type-situation-to-clone')
    const selectDesordreToClone = document.querySelector('#select-desordre-to-clone')
    const editFileRoute = modalUploadFiles.dataset.editFileRoute;
    const editFileToken = modalUploadFiles.dataset.editFileToken;
    const btnValidate = document.querySelector('#btn-validate-modal-upload-files');
    const ancre = document.querySelector('#modal-upload-file-dynamic-content');

    fileSelector.onclick = () => fileSelectorInput.click()
    fileSelectorInput.onchange = () => {
        [...fileSelectorInput.files].forEach((file) => {
            if (typeValidation(file)) {
                uploadFile(file)
            }
        })
        ancre.scrollIntoView({ block: 'start' });
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
        ancre.scrollIntoView({ block: 'start' });
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

        div.innerHTML = 'Impossible d\'ajouter le fichier ' +file.name +' car le format n\'est pas pris en charge. Veuillez sélectionner un fichier au format ' +modalUploadFiles.dataset.acceptedExtensions+'.';
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
        if (modalUploadFiles.dataset.fileType == 'photo') {
            data.append('signalement-add-file[photos][]', file)
        } else {
            data.append('signalement-add-file[documents][]', file)
        }
        data.append('email', document.querySelector('#suivi_from_email').value)
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
                    modalUploadFiles.dataset.hasChanges = true
                    let clone
                    if (modalUploadFiles.dataset.fileType == 'photo') {
                        clone = selectDesordreToClone.cloneNode(true)
                        clone.id = 'select-desordre-' + response.response
                    } else {
                        clone = selectTypeSituationToClone.cloneNode(true)
                        clone.id = 'select-type-' + response.response
                    }
                    clone.dataset.fileId = response.response
                    if (clone.querySelectorAll('option').length == 1) {
                        clone.remove()
                    } else {
                        div.querySelector('.select-container').appendChild(clone)
                        addEventListenerSelectTypeDesordre(clone)
                    }
                    btnDeleteTmpFile.href = btnDeleteTmpFile.href + '?file_id=' + response.response
                    btnDeleteTmpFile.classList.remove('fr-hidden', 'delete-html')
                    addHtmlFile(file, response)
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

    function addHtmlFile(file, response) {
        let uploadedFilesList = document.querySelector('#uploaded-files-list')
        let div = document.createElement('div')
        div.id = 'uploaded-file-' + response.response
        div.classList.add('fr-mb-2v')
        div.innerHTML = file.name
        let deleteFileLink = document.createElement('a')
        deleteFileLink.href = deleteTmpFileRoute + '?file_id=' + response.response
        deleteFileLink.classList.add('uploaded-file-delete', 'fr-ml-2v', 'fr-btn', 'fr-btn--tertiary', 'fr-btn--icon-left', 'fr-icon-delete-line')
        deleteFileLink.title = 'Supprimer'
        deleteFileLink.innerHTML = 'Supprimer'
        div.appendChild(deleteFileLink)
        uploadedFilesList.appendChild(div)
        deleteFileLink.addEventListener('click', (e) => {
            e.preventDefault()
            deleteFileLink.style.display = 'none'
            fetch(deleteFileLink.href, { method: 'DELETE' })
                .then((response) => response.json())
                .then((data) => {
                    if (data.success) {
                        deleteFileLink.closest('div').remove()
                    }else{
                        deleteFileLink.style.display = 'inline'
                    }
                })
        })
    }

    function initInnerHtml(file) {
        let innerHTML = `<div class="fr-col-12 file-error"></div>`;
        if (modalUploadFiles.dataset.fileType == 'photo') {
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
        innerHTML += `<div class="fr-col-3 select-container">           
            </div>
            <input type="hidden" id="file-id" name="file[id]">`
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
            if (modalUploadFiles.dataset.fileType == 'photo') {
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
                        document.querySelector('#uploaded-file-' + data.fileId).remove()
                    } else {
                        button.removeAttribute('disabled')
                    }
                })
        })
    }

    modalUploadFiles.addEventListener('dsfr.conceal', (e) => {
        if (modalUploadFiles.dataset.validated == "true" && modalUploadFiles.dataset.hasChanges == "true") {
            return true;
        }
        document.querySelectorAll('a.delete-tmp-file').forEach((button) => {
            button.click()
        })
        modalUploadFiles.dataset.hasChanges = false
    })

    let fileType, acceptedTypeMimes, acceptedExtensions
    document.querySelectorAll('.open-modal-upload-files-btn').forEach((button) => {
        button.addEventListener('click', (e) => {
            fileType = e.target.dataset.fileType
            acceptedTypeMimes = e.target.dataset.acceptedTypeMimes ?? null
            acceptedExtensions = e.target.dataset.acceptedExtensions ?? null
        })
    })

    modalUploadFiles.addEventListener('dsfr.disclose', (e) => {
        listContainer.innerHTML = '';
        modalUploadFiles.dataset.validated = false
        modalUploadFiles.dataset.hasChanges = false
        modalUploadFiles.querySelectorAll('.type-conditional').forEach((type) => {
            type.classList.add('fr-hidden')
        })
        modalUploadFiles.dataset.acceptedExtensions = acceptedExtensions    
        if (fileType == 'photo') {
            modalUploadFiles.dataset.fileType = 'photo'
            modalUploadFiles.querySelector('.type-photo').classList.remove('fr-hidden')
        } else {
            modalUploadFiles.dataset.fileType = 'document'
            modalUploadFiles.querySelector('.type-document').classList.remove('fr-hidden')
        }
        if (null !== acceptedTypeMimes) {
            fileSelectorInput.setAttribute('accept', acceptedTypeMimes)
        }else{
            fileSelectorInput.setAttribute('accept', '*/*')
        }
    })

    btnValidate.addEventListener('click', (e) => {
        modalUploadFiles.dataset.validated = true
    })
}

document.addEventListener("DOMContentLoaded", function() {
    if(window.location.hash) {
        elmnt = document.getElementById(window.location.hash.substring(1))
        elmnt.scrollIntoView({behavior:'instant'})
    }

    setInterval(function() {
        fetch('/keep-session-alive')
            .then(response => response.json());
    }, 600000);
})
const closeNoticeButtons = document.querySelectorAll('button[name="closeNotice"]');
closeNoticeButtons.forEach(button => {
    button.addEventListener('click', function() {
        const notice = this.parentNode.parentNode.parentNode;
        notice.parentNode.removeChild(notice);
    });
});

