import {
  loadWindowWithLocalStorage,
  updateLocalStorageWithFormParams,
} from '../../services/ui/list_filter_helper';

const searchTerritoryForm = document.getElementById('search-bailleur-form');

if (searchTerritoryForm) {
  document.addEventListener('click', (e) => {
    const button = e.target.closest('.open-modal-bailleur-delete');
    if (!button) return;

    document.getElementById('fr-modal-bailleur-delete-bailleur-name').textContent =
      button.dataset.name;
    document.getElementById('bailleur_delete_form').action = button.dataset.url;
  });
  updateLocalStorageWithFormParams('search-bailleur-form');
}
loadWindowWithLocalStorage('click', '[data-filter-list-bailleur]', 'search-bailleur-form');
