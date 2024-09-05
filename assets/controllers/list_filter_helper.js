export const updateLocalStorageWithFormParams = (localStorageName) => {
    const form = document.querySelector('#bo_filters_form');
    const params = new URLSearchParams(new FormData(form));    
    const currentPage = new URLSearchParams(window.location.search).get('page') || 1;
    params.set('page', currentPage);
    localStorage.setItem(localStorageName, params.toString());
}

export const updateLocalStorageWithPaginationParams = (event, localStorageName) => {
    const url = new URL(event.target.href);
    const params = url.searchParams.toString();
    localStorage.setItem(localStorageName, params);
}

export const loadWindowWithLocalStorage = (event, localStorageName) => {
    if (window.history.length > 1) {
        event.preventDefault()
        const backLinkQueryParams = localStorage.getItem(localStorageName)
        window.location.href = backLinkQueryParams?.length > 0
            ? `${event.target.href}?${backLinkQueryParams}`
            : event.target.href
    }
}