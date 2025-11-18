import {
  loadWindowWithLocalStorage,
  updateLocalStorageWithFormParams,
} from '../../services/ui/list_filter_helper';

const searchFilesForm = document.getElementById('search-territory-files-type-form');

if (searchFilesForm) {
  document.querySelectorAll('#form-field-type-tag-list .fr-tag').forEach((tag) => {
    tag.addEventListener('click', (e) => {
      document.querySelectorAll('#form-field-type-tag-list .fr-tag').forEach((otherTag) => {
        if (otherTag !== e.target) {
          otherTag.setAttribute('aria-pressed', 'false');
        }
      });
      const input = document.getElementById('form-field-type-value');
      if (input) {
        if (input.value === e.target.dataset.value) {
          input.value = '';
        } else {
          input.value = e.target.dataset.value;
        }
      }
      searchFilesForm.submit();
    });
  });

  document.addEventListener('click', (e) => {
    if (e.target.closest('.open-modal-document-view')) {
      const button = e.target.closest('.open-modal-document-view');
      document.getElementById('fr-modal-document-view-document-created-at').textContent =
        button.dataset.createdat;
      document.getElementById('fr-modal-document-view-document-created-by').textContent =
        button.dataset.createdby;
      document.getElementById('fr-modal-document-view-document-title').textContent =
        button.dataset.title;
      document.getElementById('fr-modal-document-view-document-description').innerText =
        button.dataset.description;
      document.getElementById('fr-modal-document-view-document-partner-type').innerHTML =
        button.dataset.partnertype;
      document.getElementById('fr-modal-document-view-document-partner-competence').innerHTML =
        button.dataset.partnercompetence;
      document.getElementById('fr-modal-document-edit-btn-submit').href = button.dataset.url;
    }
  });
  document.addEventListener('click', (e) => {
    if (e.target.closest('.open-modal-document-delete')) {
      const button = e.target.closest('.open-modal-document-delete');
      document.getElementById('fr-modal-document-delete-document-title').textContent =
        button.dataset.title;
      document.getElementById('fr-modal-document-delete-document-territoire').textContent =
        button.dataset.territoire;
      document.getElementById('fr-modal-document-delete-document-title-reminder').textContent =
        button.dataset.title;
      document.getElementById('fr-modal-document-delete-form').action = button.dataset.url;
    }
  });
  updateLocalStorageWithFormParams('search-territory-files-type-form');
}

const titleInput = document.getElementById('territory_file_title');
let isTitleEdited = false;
if (titleInput) {
  titleInput.addEventListener('input', () => {
    isTitleEdited = true;
  });
}

const fileReplacementToggle = document.getElementById('file-replacement-toggle');

const fileElement = document.getElementById('territory_file_file');
const previewElement = document.getElementById('form-add-file-preview');
const previewImageElement = document.querySelector('#form-add-file-preview embed');
const noPreviewElement = document.getElementById('form-add-file-no-preview');
if (fileElement && previewElement) {
  fileElement.addEventListener('change', (e) => {
    const [file] = e.target.files;
    // preview if image file
    if (file && (file.type.startsWith('image/') || file.type === 'application/pdf')) {
      previewImageElement.src = URL.createObjectURL(file);
      previewElement.classList.remove('fr-hidden');
      noPreviewElement.classList.add('fr-hidden');
    } else {
      previewImageElement.src = '';
      previewElement.classList.add('fr-hidden');
      noPreviewElement.classList.remove('fr-hidden');
    }

    if (!isTitleEdited && !fileReplacementToggle && file) {
      // set title to filename without extension
      const filename = file.name;
      const lastDotIndex = filename.lastIndexOf('.');
      if (lastDotIndex !== -1) {
        titleInput.value = filename.substring(0, lastDotIndex);
      } else {
        titleInput.value = filename;
      }
    }
  });
}

const fileReplacementContainer = document.getElementById('file-replacement-container');
if (fileReplacementToggle && fileReplacementContainer) {
  fileReplacementToggle.addEventListener('change', () => {
    if (fileReplacementToggle.checked) {
      fileReplacementContainer.classList.remove('fr-hidden');
    } else {
      fileReplacementContainer.classList.add('fr-hidden');
      // clear file input
      const fileInput = fileReplacementContainer.querySelector('input[type="file"]');
      if (fileInput) {
        fileInput.value = '';
        // hide preview
        previewImageElement.src = '';
        previewElement.classList.add('fr-hidden');
        noPreviewElement.classList.add('fr-hidden');
      }
    }
  });
}

const partnerToggle = document.getElementById('partner-visibility-toggle');
const partnerType = document.getElementById('partner-visibility-type');
const partnerCompetence = document.getElementById('partner-visibility-competence');

function clearFieldValues(container) {
  container.querySelectorAll('input[type="checkbox"]').forEach((checkbox) => {
    checkbox.checked = false;
  });

  container.querySelectorAll('select').forEach((select) => {
    select.selectedIndex = -1;
  });
}

if (partnerToggle) {
  partnerToggle.addEventListener('change', () => {
    if (partnerToggle.checked) {
      partnerType.classList.remove('fr-hidden');
      partnerCompetence.classList.remove('fr-hidden');
    } else {
      partnerType.classList.add('fr-hidden');
      partnerCompetence.classList.add('fr-hidden');
      clearFieldValues(partnerType);
      clearFieldValues(partnerCompetence);
    }
  });
}
loadWindowWithLocalStorage(
  'click',
  '[data-filter-list-territory-files]',
  'search-territory-files-type-form'
);
