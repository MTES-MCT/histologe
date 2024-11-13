import { loadWindowWithLocalStorage, updateLocalStorageWithFormParams } from '../../services/list_filter_helper'

const searchZoneForm = document.getElementById('search-zone-form')

if (searchZoneForm) {
  searchZoneForm.querySelectorAll('select').forEach((select) => {
    select.addEventListener('change', function () {
      document.getElementById('page').value = 1
      searchZoneForm.submit()
    })
  })
  searchZoneForm.addEventListener('submit', function () {
    document.getElementById('page').value = 1
  })
  document.querySelectorAll('.open-modal-zone-delete').forEach((button) => {
    button.addEventListener('click', (e) => {
      document.getElementById('fr-modal-zone-delete-zone-name').textContent = e.target.dataset.name
      document.getElementById('fr-modal-zone-delete-btn-submit').href = e.target.dataset.url
    })
  })
  updateLocalStorageWithFormParams('search-zone-form')
}
loadWindowWithLocalStorage('click', '[data-filter-list-zone]', 'search-zone-form')