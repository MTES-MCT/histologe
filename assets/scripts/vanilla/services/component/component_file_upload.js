const uploadedFilesByContainer = new WeakMap();

function initComponentFileUpload() {
  const containers = document.querySelectorAll('.component-upload-container');

  containers.forEach((container) => {
    const dropArea = container.querySelector('.component-upload-drop-section');
    const fileSelector = container.querySelector('.component-upload-files-selector');
    const fileSelectorInput = container.querySelector('.component-upload-files-selector-input');

    fileSelector.onclick = () => {
      fileSelectorInput.value = '';
      fileSelectorInput.click();
    };
    fileSelectorInput.onchange = () => {
      [...fileSelectorInput.files].forEach((file) => {
        if (typeValidation(file, container)) {
          handleFile(file, container);
        }
      });
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
            if (typeValidation(file, container)) {
              handleFile(file, container);
            }
          }
        });
      } else {
        [...e.dataTransfer.files].forEach((file) => {
          if (typeValidation(file, container)) {
            handleFile(file, container);
          }
        });
      }
    };
  });
}

function typeValidation(file, container) {
  const listContainerErrors = container.querySelector('.component-upload-list-errors');
  const fileSelectorInput = container.querySelector('.component-upload-files-selector-input');
  const acceptedType = fileSelectorInput.getAttribute('accept');
  const acceptedTypes = acceptedType ? acceptedType.split(',').map((type) => type.trim()) : [];
  if (!acceptedTypes.length || acceptedTypes.includes(file.type)) {
    return true;
  }
  const notice = document.createElement('div');
  notice.classList.add('fr-notice', 'fr-notice--alert', 'fr-mb-5v');

  const containerDiv = document.createElement('div');
  containerDiv.classList.add('fr-container');

  const body = document.createElement('div');
  body.classList.add('fr-notice__body');

  const p = document.createElement('p');

  const span = document.createElement('span');
  span.classList.add('fr-notice__desc');
  span.textContent =
    'Impossible d\'ajouter le fichier "' + file.name + '" car le format n\'est pas pris en charge.';

  const btnClose = document.createElement('button');
  btnClose.classList.add('fr-btn--close', 'fr-btn');
  btnClose.setAttribute('title', 'Masquer le message');
  btnClose.textContent = 'Masquer le message';

  p.appendChild(span);
  body.appendChild(p);
  body.appendChild(btnClose);
  containerDiv.appendChild(body);
  notice.appendChild(containerDiv);

  listContainerErrors.prepend(notice);

  return false;
}

function handleFile(file, container) {
  const listContainer = container.querySelector('.component-upload-list');
  const storedFiles = getStoredFiles(container);

  storedFiles.push(file);
  syncInputFiles(container);

  const fileItem = document.createElement('div');
  fileItem.classList.add('component-upload-list-item');
  fileItem.textContent = file.name;

  const deleteButton = document.createElement('button');
  deleteButton.classList.add(
    'fr-link',
    'fr-icon-close-circle-line',
    'fr-link--icon-left',
    'fr-link--error',
    'fr-pl-2w'
  );
  deleteButton.setAttribute('aria-label', 'Supprimer le fichier ' + file.name);
  deleteButton.setAttribute('title', 'Supprimer le fichier ' + file.name);
  deleteButton.textContent = 'Supprimer';
  deleteButton.onclick = () => {
    const index = storedFiles.findIndex((storedFile) => storedFile === file);
    if (index !== -1) {
      storedFiles.splice(index, 1);
      syncInputFiles(container);
    }

    if (listContainer.contains(fileItem)) {
      listContainer.removeChild(fileItem);
    }
  };

  fileItem.appendChild(deleteButton);
  listContainer.appendChild(fileItem);
}

function getStoredFiles(container) {
  if (!uploadedFilesByContainer.has(container)) {
    uploadedFilesByContainer.set(container, []);
  }

  return uploadedFilesByContainer.get(container);
}

function syncInputFiles(container) {
  const fileSelectorInput = container.querySelector('.component-upload-files-selector-input');
  const dataTransfer = new DataTransfer();

  getStoredFiles(container).forEach((file) => {
    dataTransfer.items.add(file);
  });

  fileSelectorInput.files = dataTransfer.files;
}

initComponentFileUpload();
