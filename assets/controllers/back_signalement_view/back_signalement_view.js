const modalUploadFiles = document?.querySelector('#fr-modal-upload-files')
if(modalUploadFiles){
    const dropArea = document.querySelector('.modal-upload-drop-section')
    const listContainer = document.querySelector('.modal-upload-list')
    const fileSelector = document.querySelector('.modal-upload-files-selector')
    const fileSelectorInput = document.querySelector('.modal-upload-files-selector-input')
    const addFileRoute = modalUploadFiles.dataset.addFileRoute
    const addFileToken = modalUploadFiles.dataset.addFileToken
    const selectTypeToClone = document.querySelector('#select-type-to-clone')
    const selectDesordreToClone = document.querySelector('#select-desordre-to-clone')
    const editFileRoute = modalUploadFiles.dataset.editFileRoute
    const editFileToken = modalUploadFiles.dataset.editFileToken

    fileSelector.onclick = () => fileSelectorInput.click()
    fileSelectorInput.onchange = () => {
        [...fileSelectorInput.files].forEach((file) => {
            uploadFile(file)
        })
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
        if(e.dataTransfer.items){
            [...e.dataTransfer.items].forEach((item) => {
                if(item.kind === 'file'){
                    const file = item.getAsFile()
                    uploadFile(file)
                }
            })
        }else{
            [...e.dataTransfer.files].forEach((file) => {
                uploadFile(file)
            })
        }
    }

    function uploadFile(file){
        let div = document.createElement('div')
        div.classList.add('fr-grid-row', 'fr-grid-row--gutters', 'fr-grid-row--middle', 'fr-mb-2w')
        div.innerHTML = initInnerHtml(file)
        listContainer.prepend(div)
        let http = new XMLHttpRequest()
        let data = new FormData()
        if(modalUploadFiles.dataset.fileType == 'photo'){
            data.append('signalement-add-file[photos][]', file)
        }else{
            data.append('signalement-add-file[documents][]', file)
        }
        data.append('_token', addFileToken)
        http.upload.onprogress = (e) => {
            let percent_complete = (e.loaded / e.total)*100
            div.querySelectorAll('span')[0].style.width = percent_complete + '%'
            div.querySelectorAll('span')[1].innerHTML = Math.round(percent_complete) + '%'
        }
        http.onreadystatechange = function() {
            if (this.readyState == XMLHttpRequest.DONE) {
                let response = JSON.parse(this.response)
                if (this.status == 200) {
                    modalUploadFiles.dataset.hasChanges = true
                    if(modalUploadFiles.dataset.fileType == 'photo'){
                        var clone = selectDesordreToClone.cloneNode(true)
                        clone.id = 'select-desordre-'+response.response
                    }else{
                        var clone = selectTypeToClone.cloneNode(true)
                        clone.id = 'select-type-'+response.response
                    }
                    clone.dataset.fileId = response.response
                    div.querySelector('.select-container').appendChild(clone) 
                    addEventListenerSelectTypeDesordre(clone)
                } else {
                    div.querySelector('.file-error').innerHTML = '<div class="fr-alert fr-alert--error fr-alert--sm">'+response.response+'</div>'
                }
            }
        }
        http.open('POST', addFileRoute, true)
        http.setRequestHeader("X-Requested-With", "XMLHttpRequest")
        http.send(data)
    }

    function initInnerHtml(file){
        var innerHTML;
        if(modalUploadFiles.dataset.fileType == 'photo'){
            innerHTML = `
            <div class="fr-col-2">
                <img class="fr-content-media__img" src="${URL.createObjectURL(file)}">
            </div>
            <div class="fr-col-6">`
        }else{
            innerHTML = `<div class="fr-col-8">`
        }
        innerHTML += `
            <div class="file-name">
                <div class="name">${file.name}</div>
            </div>
            <div class="file-progress">
                <span></span>
            </div>
            <div class="file-size">
                <div class="size">${(file.size/(1024*1024)).toFixed(2)} MB</div>
                <span>0%</span>
            </div>
        </div>
        <div class="fr-col-4 select-container">           
        </div>
        <div class="fr-col-12 file-error">
        </div>
        `
        return innerHTML
    }

    function addEventListenerSelectTypeDesordre(select){
        select.addEventListener('change', function(){
            let selectField = this
            let http = new XMLHttpRequest()
            let data = new FormData()
            data.append('file_id', selectField.dataset.fileId)
            if(modalUploadFiles.dataset.fileType == 'photo'){
                data.append('documentType', 'SITUATION')
                data.append('desordreSlug', selectField.value)
            }else{
                data.append('documentType', selectField.value)
            }
            data.append('_token', editFileToken)
            http.onreadystatechange = function() {
                if (this.readyState == XMLHttpRequest.DONE) {
                    let response = JSON.parse(this.response)
                    if (this.status != 200) {
                        let parent = selectField.closest('.modal-upload-list')
                        parent.querySelector('.file-error').innerHTML = '<div class="fr-alert fr-alert--error fr-alert--sm">'+response.response+'</div>'
                    }
                }
            }
            http.open('POST', editFileRoute, true)
            http.setRequestHeader("X-Requested-With", "XMLHttpRequest")
            http.send(data)
        })
    }

    modalUploadFiles.addEventListener('dsfr.conceal', (e) => {
        document.querySelector('.modal-upload-list').innerHTML = ''
        if(modalUploadFiles.dataset.hasChanges == "true"){
            window.location.reload()
        }
    })

    let fileType
    document.querySelectorAll('.open-modal-upload-files-btn').forEach((button) => {
        button.addEventListener('click', (e) => {
            fileType = e.target.dataset.fileType
        })
    })

    modalUploadFiles.addEventListener('dsfr.disclose', (e) => {
        modalUploadFiles.querySelectorAll('.type-conditional').forEach((type) => {
            type.classList.add('fr-hidden')
        })
        if(fileType == 'photo'){
            modalUploadFiles.dataset.fileType = 'photo'
            modalUploadFiles.querySelector('.type-photo').classList.remove('fr-hidden')
            fileSelectorInput.setAttribute('accept', 'image/*')
        }else{
            modalUploadFiles.dataset.fileType = 'document'
            modalUploadFiles.querySelector('.type-document').classList.remove('fr-hidden')
            fileSelectorInput.setAttribute('accept', 'application/*')
        }
    })
}