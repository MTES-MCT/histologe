import {
  loadWindowWithLocalStorage,
  updateLocalStorageWithFormParams,
} from '../../services/ui/list_filter_helper';

const searchFilesForm = document.getElementById('search-territory-files-type-form');

if (searchFilesForm) {
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
