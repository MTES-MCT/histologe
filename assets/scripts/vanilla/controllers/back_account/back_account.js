import { loadWindowWithLocalStorage, updateLocalStorageWithPaginationParams, updateLocalStorageOnEvent} from '../../services/list_filter_helper'

loadWindowWithLocalStorage('click', '[data-filter-list-comptes-archives]', 'back_link_comptes_archives');
updateLocalStorageOnEvent('input', '#comptes-archives-input', 'back_link_comptes_archives');
updateLocalStorageOnEvent('change', '#comptes-archives-filters-territories', 'back_link_comptes_archives');
updateLocalStorageOnEvent('change', '#comptes-archives-filters-partners', 'back_link_comptes_archives');
updateLocalStorageWithPaginationParams('click', '#comptes-archives-paginatio a', 'back_link_comptes_archives');