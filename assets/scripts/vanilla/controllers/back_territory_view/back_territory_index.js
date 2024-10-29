import { loadWindowWithLocalStorage, updateLocalStorageWithFormParams } from '../../services/list_filter_helper'

const searchTerritoryForm = document.getElementById('search-territory-form')

if (searchTerritoryForm) {
  searchTerritoryForm.querySelectorAll('select').forEach((select) => {
    select.addEventListener('change', function () {
      document.getElementById('page').value = 1
      searchTerritoryForm.submit()
    })
  })
  searchTerritoryForm.addEventListener('submit', function (event) {
    document.getElementById('page').value = 1
  })
  updateLocalStorageWithFormParams('search-territory-form')
}
loadWindowWithLocalStorage('click', '[data-filter-list-territory]', 'search-territory-form')
