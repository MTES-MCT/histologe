import { loadWindowWithLocalStorage, updateLocalStorageWithFormParams, updateLocalStorageWithPaginationParams} from '../list_filter_helper'
document?.querySelectorAll('[data-filter-list-comptes-archives]').forEach(link => {
    link.addEventListener('click', (event) => loadWindowWithLocalStorage(event, 'back_link_comptes_archives'));
})

const userSearchInput = document?.querySelector('#comptes-archives-input');
userSearchInput?.addEventListener('input', () => updateLocalStorageWithFormParams('back_link_comptes_archives'));

const territorySelect = document?.querySelector('#comptes-archives-filters-territories');
territorySelect?.addEventListener('change', () => updateLocalStorageWithFormParams('back_link_comptes_archives'));

const partnerSelect = document?.querySelector('#comptes-archives-filters-partners');
partnerSelect?.addEventListener('change', () => updateLocalStorageWithFormParams('back_link_comptes_archives'));

const paginationLinks = document.querySelectorAll('#comptes-archives-pagination a');
paginationLinks.forEach(link => {
    link.addEventListener('click', (event) => updateLocalStorageWithPaginationParams(event, 'back_link_comptes_archives'));
});