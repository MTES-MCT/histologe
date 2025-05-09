import { loadWindowWithLocalStorage, updateLocalStorageWithFormParams } from '../../services/ui/list_filter_helper'

const searchTerritoryForm = document.getElementById('search-territory-form')

if (searchTerritoryForm) {
  updateLocalStorageWithFormParams('search-territory-form')
}
loadWindowWithLocalStorage('click', '[data-filter-list-territory]', 'search-territory-form')
