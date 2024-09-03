document?.querySelectorAll('[data-filter-list-comptes-archives]').forEach(link => {
    link.addEventListener('click', (event) => {
        if (window.history.length > 1) {
            event.preventDefault()
            const backLinkQueryParams = localStorage.getItem('back_link_comptes_archives')
            window.location.href = backLinkQueryParams?.length > 0
                ? `${event.target.href}?${backLinkQueryParams}`
                : event.target.href
        }
    })
})

function updateLocalStorageWithFormParams() {
    const form = document.querySelector('#bo_filters_form');
    const params = new URLSearchParams(new FormData(form));    
    const currentPage = new URLSearchParams(window.location.search).get('page') || 1;
    params.set('page', currentPage);
    localStorage.setItem('back_link_comptes_archives', params.toString());
}

const userSearchInput = document?.querySelector('#header-search-input');
userSearchInput?.addEventListener('input', updateLocalStorageWithFormParams);

const territorySelect = document?.querySelector('#bo-filters-territories');
territorySelect?.addEventListener('change', updateLocalStorageWithFormParams);

const partnerSelect = document?.querySelector('#bo-filters-partners');
partnerSelect?.addEventListener('change', updateLocalStorageWithFormParams);

const paginationLinks = document.querySelectorAll('.fr-pagination__list a');
paginationLinks.forEach(link => {
    link.addEventListener('click', function(event) {
        const url = new URL(event.target.href);
        const params = url.searchParams.toString();
        localStorage.setItem('back_link_comptes_archives', params);
    });
});