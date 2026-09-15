import {
  loadWindowWithLocalStorage,
  updateLocalStorageWithFormParams,
} from '../../services/ui/list_filter_helper';
import { registerClickRoute } from '../../services/ui/click_dispatcher';

const searchTerritoryForm = document.getElementById('search-bailleur-form');

if (searchTerritoryForm) {
  registerClickRoute('.open-modal-bailleur-delete', (button, e) => {
    document.getElementById('fr-modal-bailleur-delete-bailleur-name').textContent =
      button.dataset.name;
    document.getElementById('bailleur_delete_form').action = button.dataset.url;
  });
  updateLocalStorageWithFormParams('search-bailleur-form');
}
loadWindowWithLocalStorage('click', '[data-filter-list-bailleur]', 'search-bailleur-form');
