import { disableHeaderAndFooterButtonOfModal, enableHeaderAndFooterButtonOfModal } from '../../services/ui/modales_helper'

const fieldsetVisitorType = document?.querySelector('#fieldset-visitor-type')
if (fieldsetVisitorType) {
  document.querySelectorAll('#radio-visitor-type-occupant, #radio-visitor-type-declarant').forEach(element => {
    element.addEventListener('change', (event) => {
      refreshLoginFields()
    })
  })

  function refreshLoginFields() {
    const listVisibleOccupant = document.querySelectorAll('.visible-if-occupant')
    const listVisibleDeclarant = document.querySelectorAll('.visible-if-declarant')
    if (document.querySelector('#radio-visitor-type-occupant').checked) {
      listVisibleOccupant.forEach(element => {
        element.classList.remove('fr-hidden')
      })
      listVisibleDeclarant.forEach(element => {
        element.classList.add('fr-hidden')
      })
    } else {
      listVisibleOccupant.forEach(element => {
        element.classList.add('fr-hidden')
      })
      listVisibleDeclarant.forEach(element => {
        element.classList.remove('fr-hidden')
      })
    }
  }
}

const modalUploadFiles = document?.querySelector('#fr-modal-upload-files-usager')
if (modalUploadFiles) {
  const dropArea = document.querySelector('.modal-upload-drop-section')
  const listContainer = document.querySelector('.modal-upload-list')
  const fileSelector = document.querySelector('.modal-upload-files-selector')
  const fileSelectorInput = document.querySelector('.modal-upload-files-selector-input')
  const addFileRoute = modalUploadFiles.dataset.addFileRoute
  const addFileToken = modalUploadFiles.dataset.addFileToken
  const deleteTmpFileRoute = modalUploadFiles.dataset.deleteTmpFileRoute
  const selectTypeSituationToClone = document.querySelector('#select-type-situation-to-clone')
  const selectDesordreToClone = document.querySelector('#select-desordre-to-clone')
  const editFileRoute = modalUploadFiles.dataset.editFileRoute
  const editFileToken = modalUploadFiles.dataset.editFileToken
  const btnValidate = document.querySelector('#btn-validate-modal-upload-files')
  const ancre = document.querySelector('#modal-upload-file-dynamic-content')
  let nbFilesProccessing = 0;

  fileSelector.onclick = () => fileSelectorInput.click()
  fileSelectorInput.onchange = () => {
    [...fileSelectorInput.files].forEach((file) => {
      if (typeValidation(file)) {
        uploadFile(file)
      }
    })
    ancre.scrollIntoView({ block: 'start' })
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
    e.preventDefault()
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
    ancre.scrollIntoView({ block: 'start' })
  }

  function typeValidation (file) {
    const acceptedType = fileSelectorInput.getAttribute('accept')
    if (acceptedType === '*/*') {
      return true
    }
    const acceptedTypes = acceptedType.split(',').map((type) => type.trim())
    if (acceptedTypes.includes(file.type)) {
      return true
    }
    const div = document.createElement('div')
    div.classList.add('fr-alert', 'fr-alert--error', 'fr-alert--sm')
    const message = document.createTextNode('Impossible d\'ajouter le fichier ' + file.name + ' car le format n\'est pas pris en charge. Veuillez sélectionner un fichier au format ' + modalUploadFiles.dataset.acceptedExtensions + '.')
    div.appendChild(message)
    listContainer.prepend(div)
    return false
  }

  function uploadFile (file) {
    nbFilesProccessing++
    disableHeaderAndFooterButtonOfModal(modalUploadFiles)

    const div = document.createElement('div')
    div.classList.add('fr-grid-row', 'fr-grid-row--gutters', 'fr-grid-row--middle', 'fr-mb-2w', 'modal-upload-list-item')
    div.innerHTML = initInnerHtml(file)
    const btnDeleteTmpFile = div.querySelector('a.delete-tmp-file')
    addEventListenerDeleteTmpFile(btnDeleteTmpFile)
    listContainer.prepend(div)
    const http = new XMLHttpRequest()
    const data = new FormData()
    if (modalUploadFiles.dataset.fileType === 'photo') {
      data.append('signalement-add-file[photos][]', file)
    } else {
      data.append('signalement-add-file[documents][]', file)
    }
    data.append('_token', addFileToken)
    http.upload.onprogress = (e) => {
      const percentComplete = (e.loaded / e.total) * 100
      div.querySelectorAll('span')[0].style.width = percentComplete + '%'
      div.querySelectorAll('span')[1].innerHTML = Math.round(percentComplete) + '%'
    }
    http.onreadystatechange = function () {
      if (this.readyState === XMLHttpRequest.DONE) {
        nbFilesProccessing--
        if (nbFilesProccessing <= 0) {
          nbFilesProccessing = 0
          enableHeaderAndFooterButtonOfModal(modalUploadFiles)
        }
        const response = JSON.parse(this.response)
        if (this.status === 200) {
          modalUploadFiles.dataset.hasChanges = true
          let clone
          if (modalUploadFiles.dataset.fileType === 'photo') {
            clone = selectDesordreToClone.cloneNode(true)
            clone.id = 'select-desordre-' + response.response
          } else {
            clone = selectTypeSituationToClone.cloneNode(true)
            clone.id = 'select-type-' + response.response
          }
          clone.dataset.fileId = response.response
          if (clone.querySelectorAll('option').length === 1) {
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
    http.setRequestHeader('X-Requested-With', 'XMLHttpRequest')
    http.send(data)
  }

  function addHtmlFile (file, response) {
    const uploadedFilesList = document.querySelector('#uploaded-files-list')
    const div = document.createElement('div')
    div.id = 'uploaded-file-' + response.response
    div.classList.add('fr-grid-row', 'fr-grid-row--middle', 'fr-mb-2v')    
    const divTitre = document.createElement('div')
    divTitre.classList.add('fr-col-9')
    const textNode = document.createTextNode(file.name)
    divTitre.appendChild(textNode)
    div.appendChild(divTitre)
    const divButton = document.createElement('div')
    divButton.classList.add('fr-col-3', 'fr-text--right')
    const deleteFileLink = document.createElement('a')
    deleteFileLink.href = deleteTmpFileRoute + '?file_id=' + response.response
    deleteFileLink.classList.add('uploaded-file-delete', 'fr-ml-2v', 'fr-btn', 'fr-btn--tertiary', 'fr-btn--icon-left', 'fr-icon-delete-line')
    deleteFileLink.title = 'Supprimer'
    deleteFileLink.innerHTML = 'Supprimer'
    divButton.appendChild(deleteFileLink)
    div.appendChild(divButton)
    uploadedFilesList.appendChild(div)
    deleteFileLink.addEventListener('click', (e) => {
      e.preventDefault()
      deleteFileLink.style.display = 'none'
      fetch(deleteFileLink.href, { method: 'DELETE' })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            document.getElementById( 'uploaded-file-' + data.fileId).remove()
          } else {
            deleteFileLink.style.display = 'inline'
          }
        })
    })
  }

  function initInnerHtml (file) {
    let innerHTML = '<div class="fr-col-12 file-error"></div>'
    if (modalUploadFiles.dataset.fileType === 'photo' && file.type !== 'application/pdf') {
      innerHTML += `
            <div class="fr-col-2">
                <img class="fr-content-media__img" src="${URL.createObjectURL(file)}">
            </div>
            <div class="fr-col-6">`
    } else {
      innerHTML += '<div class="fr-col-8">'
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

  function addEventListenerSelectTypeDesordre (select) {
    select.addEventListener('change', function () {
      const selectField = this
      const http = new XMLHttpRequest()
      const data = new FormData()
      data.append('file_id', selectField.dataset.fileId)
      if (modalUploadFiles.dataset.fileType === 'photo') {
        data.append('documentType', 'PHOTO_SITUATION')
        data.append('desordreSlug', selectField.value)
      } else {
        data.append('documentType', selectField.value)
      }
      data.append('_token', editFileToken)
      http.onreadystatechange = function () {
        if (this.readyState === XMLHttpRequest.DONE) {
          const response = JSON.parse(this.response)
          const parent = selectField.closest('.modal-upload-list-item')
          if (this.status === 200) {
            parent.querySelector('.file-error').innerHTML = ''
          } else {
            parent.querySelector('.file-error').innerHTML = '<div class="fr-alert fr-alert--error fr-alert--sm">' + response.response + '</div>'
          }
        }
      }
      http.open('POST', editFileRoute, true)
      http.setRequestHeader('X-Requested-With', 'XMLHttpRequest')
      http.send(data)
    })
  }

  function addEventListenerDeleteTmpFile (button) {
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
    if (modalUploadFiles.dataset.validated === 'true' && modalUploadFiles.dataset.hasChanges === 'true') {
      return true
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
    nbFilesProccessing = 0
    listContainer.innerHTML = ''
    modalUploadFiles.dataset.validated = false
    modalUploadFiles.dataset.hasChanges = false
    modalUploadFiles.querySelectorAll('.type-conditional').forEach((type) => {
      type.classList.add('fr-hidden')
    })
    modalUploadFiles.dataset.acceptedExtensions = acceptedExtensions
    if (fileType === 'photo') {
      modalUploadFiles.dataset.fileType = 'photo'
      modalUploadFiles.querySelector('.type-photo').classList.remove('fr-hidden')
    } else {
      modalUploadFiles.dataset.fileType = 'document'
      modalUploadFiles.querySelector('.type-document').classList.remove('fr-hidden')
    }
    if (acceptedTypeMimes !== null) {
      fileSelectorInput.setAttribute('accept', acceptedTypeMimes)
    } else {
      fileSelectorInput.setAttribute('accept', '*/*')
    }
  })

  btnValidate.addEventListener('click', (e) => {
    modalUploadFiles.dataset.validated = true
  })
}

document.addEventListener('DOMContentLoaded', function () {
  if (window.location.hash) {
    const elmnt = document.getElementById(window.location.hash.substring(1))
    elmnt?.scrollIntoView({ behavior: 'instant' })
  }
})
const closeNoticeButtons = document.querySelectorAll('button[name="closeNotice"]')
closeNoticeButtons.forEach(button => {
  button.addEventListener('click', function () {
    const notice = this.parentNode.parentNode.parentNode
    notice.parentNode.removeChild(notice)
  })
})
