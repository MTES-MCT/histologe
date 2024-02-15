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
    var li = document.createElement('li')
    li.innerHTML = `
        <div class="col">
            <div class="file-name">
                <div class="name">${file.name}</div>
                <span>0%</span>
            </div>
            <div class="file-progress">
                <span></span>
            </div>
        </div>
    `
    listContainer.prepend(li)
    var http = new XMLHttpRequest()
    var data = new FormData()
    data.append('file', file)
    http.upload.onprogress = (e) => {
        var percent_complete = (e.loaded / e.total)*100
        li.querySelectorAll('span')[0].innerHTML = Math.round(percent_complete) + '%'
        li.querySelectorAll('span')[1].style.width = percent_complete + '%'
    }
    http.open('POST', 'sender.php', true)
    http.send(data)
}