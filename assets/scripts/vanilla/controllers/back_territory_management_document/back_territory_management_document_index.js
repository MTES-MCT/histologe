import {
  loadWindowWithLocalStorage,
  updateLocalStorageWithFormParams,
} from '../../services/ui/list_filter_helper';

const searchFilesForm = document.getElementById('search-territory-files-type-form');

if (searchFilesForm) {
  document.querySelectorAll('.open-modal-document-view').forEach((button) => {
    button.addEventListener('click', (e) => {
      document.getElementById('fr-modal-document-view-document-created-at').textContent = e.target.dataset.createdat;
      document.getElementById('fr-modal-document-view-document-created-by').textContent = e.target.dataset.createdby;
      document.getElementById('fr-modal-document-view-document-title').textContent = e.target.dataset.title;
      document.getElementById('fr-modal-document-view-document-description').innerText = e.target.dataset.description;
      document.getElementById('fr-modal-document-edit-btn-submit').href = e.target.dataset.url;
    });
  });
  document.querySelectorAll('.open-modal-document-delete').forEach((button) => {
    button.addEventListener('click', (e) => {
      document.getElementById('fr-modal-document-delete-document-title').textContent = e.target.dataset.title;
      document.getElementById('fr-modal-document-delete-document-title-reminder').textContent = e.target.dataset.title;
      document.getElementById('fr-modal-document-delete-btn-submit').href = e.target.dataset.url;
    });
  });
  updateLocalStorageWithFormParams('search-territory-files-type-form');
}
loadWindowWithLocalStorage('click', '[data-filter-list-territory-files]', 'search-territory-files-type-form');
