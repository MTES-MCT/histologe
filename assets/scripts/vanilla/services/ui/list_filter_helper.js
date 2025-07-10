export const updateLocalStorageWithFormParams = (localStorageName) => {
  let form = document.querySelector('#bo_filters_form');
  if (document.querySelector('#bo_filters_form') === null) {
    form = document.querySelector('#' + localStorageName);
  }
  const params = new URLSearchParams(new FormData(form));
  const currentPage = new URLSearchParams(window.location.search).get('page') || 1;
  params.set('page', currentPage);
  localStorage.setItem(localStorageName, params.toString());
};

export const updateLocalStorageWithPaginationParams = (eventType, idItem, localStorageName) => {
  const paginationLinks = document.querySelectorAll(idItem);
  paginationLinks.forEach((link) => {
    link.addEventListener(eventType, (event) => {
      const url = new URL(event.target.href);
      const params = url.searchParams.toString();
      localStorage.setItem(localStorageName, params);
    });
  });
};

export const loadWindowWithLocalStorage = (eventType, idItem, localStorageName) => {
  document?.querySelectorAll(idItem).forEach((link) => {
    link.addEventListener(eventType, (event) => {
      if (window.history.length > 1) {
        event.preventDefault();
        const backLinkQueryParams = localStorage.getItem(localStorageName);
        window.location.href =
          backLinkQueryParams?.length > 0
            ? `${event.target.href}?${backLinkQueryParams}`
            : event.target.href;
      }
    });
  });
};

export const updateLocalStorageOnEvent = (eventType, idItem, localStorageName) => {
  const item = document?.querySelector(idItem);
  item?.addEventListener(eventType, () => updateLocalStorageWithFormParams(localStorageName));
};

const filterForms = document.getElementsByClassName('bo-filter-form');
if (filterForms.length > 0) {
  for (const filterForm of filterForms) {
    filterForm.querySelectorAll('select').forEach((select) => {
      select.addEventListener('change', function () {
        if (select.name === 'territory') {
          filterForm.querySelectorAll('input[name="partners[]"]').forEach((input) => {
            input.checked = false;
          });
        }
        document.getElementById('page').value = 1;
        filterForm.submit();
      });
    });
    filterForm.querySelectorAll('.search-checkbox-container').forEach((select) => {
      select.addEventListener('searchCheckboxChange', function () {
        document.getElementById('page').value = 1;
        filterForm.submit();
      });
    });
    filterForm.addEventListener('submit', function () {
      document.getElementById('page').value = 1;
    });
  }
}
