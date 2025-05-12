import { loadWindowWithLocalStorage, updateLocalStorageWithFormParams } from '../../services/ui/list_filter_helper'

const searchTerritoryForm = document.getElementById('search-bailleur-form')

if (searchTerritoryForm) {
  document.querySelectorAll('.open-modal-bailleur-delete').forEach((button) => {
    button.addEventListener('click', (e) => {
      document.getElementById('fr-modal-bailleur-delete-bailleur-name').textContent = e.target.dataset.name
      document.getElementById('fr-modal-bailleur-delete-btn-submit').href = e.target.dataset.url
    })
  })
  updateLocalStorageWithFormParams('search-bailleur-form')
}
loadWindowWithLocalStorage('click', '[data-filter-list-bailleur]', 'search-bailleur-form')
