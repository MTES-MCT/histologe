const dropArea = document.querySelector('.modal-upload-drop-section')
const listSection = document.querySelector('.modal-upload-list-section')
const listContainer = document.querySelector('.modal-upload-list')
const fileSelector = document.querySelector('.modal-upload-file-selector')
const fileSelectorInput = document.querySelector('.modal-upload-file-selector-input')

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
    var div = document.createElement('div')
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
        <div class="fr-col-4">
            <select class="fr-select fr-grid-row--gutters">
                <option value="">Sélectionner un type</option>
            </select>
        </div>
    `
    listContainer.prepend(div)
    var http = new XMLHttpRequest()
    var data = new FormData()
    data.append('file', file)
    http.upload.onprogress = (e) => {
        var percent_complete = (e.loaded / e.total)*100
        div.querySelectorAll('span')[0].style.width = percent_complete + '%'
        div.querySelectorAll('span')[1].innerHTML = Math.round(percent_complete) + '%'
    }
    http.open('POST', 'sender.php', true)
    http.send(data)
}