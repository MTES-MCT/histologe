import {
  disableHeaderAndFooterButtonOfModal,
  enableHeaderAndFooterButtonOfModal,
} from '../../services/ui/modales_helper';

initializeUploadModal('#fr-modal-upload-files', false);

document?.querySelectorAll('.fr-modal-visites-upload-files')?.forEach((modalVisiteUpload) => {
  initializeUploadModal('#' + modalVisiteUpload.id, true);
});

function initializeUploadModal(modalSelector, isModalUploadVisite) {
  const modal = document?.querySelector(modalSelector);
  if (!modal) return;

  const selectTypeSituationToClone = document.querySelector('#select-type-situation-to-clone');
  const selectTypeProcedureToClone = document.querySelector('#select-type-procedure-to-clone');
  const dropArea = modal.querySelector('.modal-upload-drop-section');
  const listContainer = modal.querySelector('.modal-upload-list');
  const fileSelector = modal.querySelector('.modal-upload-files-selector');
  const fileSelectorInput = modal.querySelector('.modal-upload-files-selector-input');
  const addFileRoute = modal.dataset.addFileRoute;
  const addFileToken = modal.dataset.addFileToken;
  const waitingSuiviRoute = modal.dataset.waitingSuiviRoute;
  const deleteTmpFileRoute = modal.dataset.deleteTmpFileRoute;
  const editFileRoute = modal.dataset.editFileRoute;
  const editFileToken = modal.dataset.editFileToken;
  const btnValidate = modal.querySelector('#btn-validate-modal-upload-files');
  const ancre = modal.querySelector('#modal-upload-file-dynamic-content');

  let nbFilesProccessing = 0;

  fileSelector.onclick = () => fileSelectorInput.click();
  fileSelectorInput.onchange = () => {
    [...fileSelectorInput.files].forEach((file) => {
      if (typeValidation(file)) {
        uploadFile(file);
      }
    });
    ancre.scrollIntoView({ block: 'start' });
  };

  dropArea.ondragover = (e) => {
    e.preventDefault();
    [...e.dataTransfer.items].forEach(() => {
      dropArea.classList.add('drag-over-effect');
    });
  };

  dropArea.ondragleave = () => {
    dropArea.classList.remove('drag-over-effect');
  };

  dropArea.ondrop = (e) => {
    e.preventDefault();
    dropArea.classList.remove('drag-over-effect');
    if (e.dataTransfer.items) {
      [...e.dataTransfer.items].forEach((item) => {
        if (item.kind === 'file') {
          const file = item.getAsFile();
          if (typeValidation(file)) {
            uploadFile(file);
          }
        }
      });
    } else {
      [...e.dataTransfer.files].forEach((file) => {
        if (typeValidation(file)) {
          uploadFile(file);
        }
      });
    }
    ancre.scrollIntoView({ block: 'start' });
  };

  function typeValidation(file) {
    const acceptedType = fileSelectorInput.getAttribute('accept');
    const acceptedTypes = acceptedType.split(',').map((type) => type.trim());
    if (acceptedTypes.includes(file.type)) {
      return true;
    }
    const div = document.createElement('div');
    div.classList.add('fr-alert', 'fr-alert--error', 'fr-alert--sm');
    const message = document.createTextNode(
      "Impossible d'ajouter le fichier " +
        file.name +
        " car le format n'est pas pris en charge. Veuillez sélectionner un fichier au format " +
        modal.dataset.acceptedExtensions +
        '.'
    );
    div.appendChild(message);
    listContainer.prepend(div);
    return false;
  }

  function uploadFile(file) {
    nbFilesProccessing++;
    disableHeaderAndFooterButtonOfModal(modal);

    const div = document.createElement('div');
    div.classList.add(
      'fr-grid-row',
      'fr-grid-row--gutters',
      'fr-grid-row--middle',
      'fr-mb-2w',
      'modal-upload-list-item'
    );
    div.innerHTML = initInnerHtml(file);
    const btnDeleteTmpFile = div.querySelector('a.delete-tmp-file');
    addEventListenerDeleteTmpFile(btnDeleteTmpFile);
    listContainer.prepend(div);
    const http = new XMLHttpRequest();
    const data = new FormData();
    if (modal.dataset.fileFilter === 'procédure') {
      data.append('documentType', 'AUTRE_PROCEDURE');
    }
    data.append('signalement-add-file[]', file);
    data.append('_token', addFileToken);
    http.upload.onprogress = (e) => {
      const percentComplete = (e.loaded / e.total) * 100;
      div.querySelectorAll('span')[0].style.width = percentComplete + '%';
      div.querySelectorAll('span')[1].innerHTML = Math.round(percentComplete) + '%';
    };
    http.onreadystatechange = function () {
      if (this.readyState === XMLHttpRequest.DONE) {
        nbFilesProccessing--;
        if (nbFilesProccessing <= 0) {
          nbFilesProccessing = 0;
          enableHeaderAndFooterButtonOfModal(modal);
        }
        const response = JSON.parse(this.response);
        if (this.status === 200) {
          modal.dataset.hasChanges = true;
          if (!isModalUploadVisite) {
            let clone;
            if (modal.dataset.fileFilter === 'situation') {
              clone = selectTypeSituationToClone.cloneNode(true);
            } else {
              clone = selectTypeProcedureToClone.cloneNode(true);
            }
            clone.id = 'select-type-' + response.response;
            clone.dataset.fileId = response.response;
            if (clone.querySelectorAll('option').length === 1) {
              clone.remove();
            } else {
              div.querySelector('.select-container').appendChild(clone);
              addEventListenerSelectTypeDesordre(clone);
            }
          } else {
            const divFileId = div.querySelector('#file-id');
            divFileId.value = response.response;
            callEditFileRoute(div);
            addEventListenerDescription(divFileId);
          }
          btnDeleteTmpFile.href = btnDeleteTmpFile.href.replace('REPLACE', response.response);
          btnDeleteTmpFile.classList.remove('fr-hidden', 'delete-html');
        } else {
          div.querySelector('.file-error').innerHTML =
            '<div class="fr-alert fr-alert--error fr-alert--sm">' + response.response + '</div>';
          btnDeleteTmpFile.classList.remove('fr-hidden');
        }
      }
    };
    http.open('POST', addFileRoute, true);
    http.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    http.send(data);
  }

  function initInnerHtml(file) {
    let innerHTML = '<div class="fr-col-12 file-error"></div>';
    if (file.type == 'image/jpeg' || file.type == 'image/png' || file.type == 'image/gif') {
      innerHTML += `
            <div class="fr-col-2">
                <img class="fr-content-media__img" src="${URL.createObjectURL(file)}">
            </div>
            <div class="fr-col-6">`;
    } else {
      innerHTML += '<div class="fr-col-8">';
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
        `;
    if (modal.dataset.documentType === 'PHOTO_VISITE' && file.type !== 'application/pdf') {
      innerHTML += `
            <div class="fr-col-3">                
                <input type="text" id="file-description" name="file[description]"
                required="required" class="fr-input" placeholder="Description de l'image" maxlength=250>
                <input type="hidden" id="file-id" name="file[id]">
            </div>           
            `;
    } else {
      innerHTML += `<div class="fr-col-3 select-container">           
            </div>
            <input type="hidden" id="file-id" name="file[id]">`;
    }
    innerHTML += `<div class="fr-col-1">
            <a href="${deleteTmpFileRoute}" title="Supprimer" class="fr-btn fr-btn--sm fr-btn--secondary fr-background--white fr-fi-delete-line fr-hidden delete-tmp-file delete-html"></a>         
        </div>
        `;
    return innerHTML;
  }

  function addEventListenerSelectTypeDesordre(select) {
    select.addEventListener('change', function () {
      const selectField = this;
      const http = new XMLHttpRequest();
      const data = new FormData();
      data.append('file_id', selectField.dataset.fileId);
      data.append('documentType', selectField.value);
      data.append('_token', editFileToken);
      http.onreadystatechange = function () {
        if (this.readyState === XMLHttpRequest.DONE) {
          const response = JSON.parse(this.response);
          const parent = selectField.closest('.modal-upload-list-item');
          if (this.status === 200) {
            parent.querySelector('.file-error').innerHTML = '';
          } else {
            parent.querySelector('.file-error').innerHTML =
              '<div class="fr-alert fr-alert--error fr-alert--sm">' + response.response + '</div>';
          }
        }
      };
      http.open('POST', editFileRoute, true);
      http.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
      http.send(data);
    });
  }

  function addEventListenerDeleteTmpFile(button) {
    button.addEventListener('click', (e) => {
      e.preventDefault();
      button.setAttribute('disabled', '');
      if (button.classList.contains('delete-html')) {
        button.closest('.fr-grid-row').remove();
        return true;
      }
      fetch(button.href, { method: 'DELETE' })
        .then((response) => response.json())
        .then((data) => {
          if (data.success) {
            button.closest('.fr-grid-row').remove();
          } else {
            button.removeAttribute('disabled');
          }
        });
    });
  }

  function callEditFileRoute(divFileItem) {
    const httpEdit = new XMLHttpRequest();
    const dataEdit = new FormData();
    dataEdit.append('file_id', divFileItem.querySelector('#file-id')?.value);
    dataEdit.append('documentType', modal.dataset.documentType);
    dataEdit.append('interventionId', modal.dataset.interventionId);
    dataEdit.append('description', divFileItem.querySelector('#file-description')?.value);

    dataEdit.append('_token', editFileToken);
    httpEdit.onreadystatechange = function () {
      if (this.readyState === XMLHttpRequest.DONE) {
        const response = JSON.parse(this.response);
        const parent = divFileItem;
        if (this.status !== 200) {
          parent.querySelector('.file-error').innerHTML =
            '<div class="fr-alert fr-alert--error fr-alert--sm">' + response.response + '</div>';
        } else {
          parent.querySelector('.file-error').innerHTML = '';
        }
      }
    };
    httpEdit.open('POST', editFileRoute, true);
    httpEdit.setRequestHeader('X-Requested-With', 'XMLHttpRequest');
    httpEdit.send(dataEdit);
  }

  function addEventListenerDescription() {
    listContainer?.querySelectorAll('.fr-grid-row')?.forEach((divFileItem) => {
      divFileItem.querySelector('#file-description')?.addEventListener('change', () => {
        callEditFileRoute(divFileItem);
      });
    });
  }

  modal.addEventListener('dsfr.conceal', () => {
    if (modal.dataset.validated === 'true' && modal.dataset.hasChanges === 'true') {
      if (btnValidate.getAttribute('data-context') === 'form-bo-create') {
        document.querySelector('#bo-create-file-list').innerHTML = 'Sauvegarde des fichiers...';
      }

      fetch(waitingSuiviRoute).then(() => {
        if (btnValidate.getAttribute('data-context') === 'form-bo-create') {
          window.dispatchEvent(new Event('refreshUploadedFileList'));
        } else {
          window.location.reload();
          window.scrollTo(0, 0);
        }
      });
      return true;
    }
    document.querySelectorAll('a.delete-tmp-file').forEach((button) => {
      button.click();
    });
    modal.dataset.hasChanges = false;
  });

  let fileFilter, documentType, interventionId;

  window.addEventListener('refreshUploadButtonEvent', () => {
    document.querySelectorAll('.open-modal-upload-files-btn').forEach((button) => {
      button.addEventListener('click', (e) => {
        fileFilter = e.target.dataset.fileFilter ?? null;
        documentType = e.target.dataset.documentType ?? null;
        interventionId = e.target.dataset.interventionId ?? null;
      });
    });
  });

  window.dispatchEvent(new Event('refreshUploadButtonEvent'));

  modal.addEventListener('dsfr.disclose', () => {
    nbFilesProccessing = 0;
    enableHeaderAndFooterButtonOfModal(modal);
    listContainer.innerHTML = '';
    modal.dataset.validated = false;
    modal.dataset.hasChanges = false;
    modal.querySelectorAll('.filter-conditional').forEach((type) => {
      type.classList.add('fr-hidden');
    });
    modal.dataset.documentType = documentType;
    modal.dataset.fileFilter = fileFilter;
    modal.dataset.interventionId = interventionId;
    if (fileFilter === 'procédure') {
      modal.querySelector('.filter-procedure').classList.remove('fr-hidden');
    } else if (fileFilter === 'situation') {
      modal.querySelector('.filter-situation').classList.remove('fr-hidden');
    }
  });

  btnValidate.addEventListener('click', () => {
    modal.dataset.validated = true;
  });
}
