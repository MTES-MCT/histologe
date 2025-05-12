import { loadWindowWithLocalStorage, updateLocalStorageWithFormParams, updateLocalStorageWithPaginationParams, updateLocalStorageOnEvent } from '../../services/ui/list_filter_helper'

const searchArchivedUsersForm = document.getElementById('search-archived-users-form')
if (searchArchivedUsersForm) {
  updateLocalStorageWithFormParams('search-archived-users-form')
}

loadWindowWithLocalStorage('click', '[data-filter-list-comptes-archives]', 'search-archived-users-form')
updateLocalStorageOnEvent('input', '#comptes-archives-input', 'back_link_comptes_archives')
updateLocalStorageOnEvent('change', '#comptes-archives-filters-territories', 'back_link_comptes_archives')
updateLocalStorageOnEvent('change', '#comptes-archives-filters-partners', 'back_link_comptes_archives')
updateLocalStorageWithPaginationParams('click', '#comptes-archives-paginatio a', 'back_link_comptes_archives')
