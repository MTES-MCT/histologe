import {
  loadWindowWithLocalStorage,
  updateLocalStorageWithFormParams,
} from '../../services/ui/list_filter_helper';

const searchZoneForm = document.getElementById('search-zone-form');

if (searchZoneForm) {
  document.addEventListener('click', (e) => {
    const button = e.target.closest('.open-modal-zone-delete');
    if (!button) return;
    document.getElementById('fr-modal-zone-delete-zone-name').textContent = button.dataset.name;
    document.getElementById('zone_delete_form').action = button.dataset.url;
  });
  updateLocalStorageWithFormParams('search-zone-form');
}
loadWindowWithLocalStorage('click', '[data-filter-list-zone]', 'search-zone-form');
