document?.querySelectorAll('.select-search-filter-form')?.forEach(select => {
    select.addEventListener(
        "change",
        () => {
            setBadge(select);
        },
        false,
      );
})

document?.querySelectorAll('[data-removable="true"]')?.forEach(removale => {
    removale.addEventListener('click', () => {
        removeBadge(removale);
    })
})