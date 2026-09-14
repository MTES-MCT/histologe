import {
  loadWindowWithLocalStorage,
  updateLocalStorageWithFormParams,
} from '../../services/ui/list_filter_helper';
import { registerClickRoute } from '../../services/ui/click_dispatcher';

const searchZoneForm = document.getElementById('search-zone-form');

if (searchZoneForm) {
  registerClickRoute('.open-modal-zone-delete', (button, e) => {
    document.getElementById('fr-modal-zone-delete-zone-name').textContent = button.dataset.name;
    document.getElementById('zone_delete_form').action = button.dataset.url;
  });
  updateLocalStorageWithFormParams('search-zone-form');
}
loadWindowWithLocalStorage('click', '[data-filter-list-zone]', 'search-zone-form');
