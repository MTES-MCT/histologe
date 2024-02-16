const modalAddDocument = document?.querySelector('#fr-modal-add-file-documents')
if(modalAddDocument){
    const dropArea = document.querySelector('.modal-upload-drop-section')
    const listContainer = document.querySelector('.modal-upload-list')
    const fileSelector = document.querySelector('.modal-upload-file-selector')
    const fileSelectorInput = document.querySelector('.modal-upload-file-selector-input')
    const modalAddFileData = document.querySelector('#fr-modal-add-file-documents-title');
    const addFileRoute = modalAddFileData.dataset.addFileRoute;
    const addFileToken = modalAddFileData.dataset.addFileToken;
    const selectTypeToClone = document.querySelector('#select-type-document-to-clone');
    const editFileRoute = modalAddFileData.dataset.editFileRoute;
    const editFileToken = modalAddFileData.dataset.editFileToken;

    //UPLOAD DOCUMENT
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
                    const file = item.getAsFile();
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
        div.innerHTML = `
            <div class="fr-col-8">
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
        listContainer.prepend(div)
        let http = new XMLHttpRequest()
        let data = new FormData()
        data.append('signalement-add-file[documents][]', file)
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
                    modalAddFileData.dataset.hasChanges = true;
                    let clone = selectTypeToClone.cloneNode(true);
                    clone.id = 'select-type-document-'+response.response;
                    clone.dataset.fileId = response.response;
                    div.querySelector('.select-container').appendChild(clone);  
                    addEventListenerSelectType(clone);             
                } else {
                    div.querySelector('.file-error').innerHTML = '<div class="fr-alert fr-alert--error fr-alert--sm">'+response.response+'</div>'
                }
            }
        };
        http.open('POST', addFileRoute, true)
        http.setRequestHeader("X-Requested-With", "XMLHttpRequest");
        http.send(data)
    }

    //UPDATE TYPE DOCUMENT
    function addEventListenerSelectType(select){
        select.addEventListener('change', function(){
            let selectField = this;
            let http = new XMLHttpRequest()
            let data = new FormData()
            data.append('file_id', selectField.dataset.fileId)
            data.append('documentType', selectField.value)
            data.append('_token', editFileToken)
            http.onreadystatechange = function() {
                if (this.readyState == XMLHttpRequest.DONE) {
                    let response = JSON.parse(this.response)
                    if (this.status != 200) {
                        let parent = selectField.closest('.modal-upload-list');
                        parent.querySelector('.file-error').innerHTML = '<div class="fr-alert fr-alert--error fr-alert--sm">'+response.response+'</div>'
                    }
                }
            };
            http.open('POST', editFileRoute, true)
            http.setRequestHeader("X-Requested-With", "XMLHttpRequest");
            http.send(data)
        })
    }

    //CLEAN MODAL
    modalAddDocument.addEventListener('dsfr.conceal', (e) => {
        document.querySelector('.modal-upload-list').innerHTML = '';
        if(modalAddFileData.dataset.hasChanges == "true"){
            window.location.reload();
        }
    })
}