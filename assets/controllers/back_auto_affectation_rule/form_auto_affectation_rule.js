document.querySelectorAll('.btn-delete-autoaffectationrule').forEach(swbtn => {
    swbtn.addEventListener('click', evt => {
        const target = evt.target
        document.querySelector('.fr-modal-autoaffectationrule-delete-description').innerHTML = target.getAttribute('data-autoaffectationrule-description')
        document.querySelector('#fr-modal-autoaffectationrule-delete-id').value = target.getAttribute('data-autoaffectationrule-id')
        document.querySelector('#autoaffectationrule_delete_form').addEventListener('submit', (e) => {
            document.querySelector('#autoaffectationrule_delete_form_submit').innerHTML = 'Suppression en cours...'
            document.querySelector('#autoaffectationrule_delete_form_submit').disabled = true
        })
    })
})

document?.querySelector('[data-filter-list-auto-affectation-rule]')?.addEventListener('click', (event) => {
    if (window.history.length > 1) {
        event.preventDefault()
        const backLinkQueryParams = localStorage.getItem('back_link_autoaffectation_rule')
        window.location.href = backLinkQueryParams?.length > 0
            ? `${event.target.href}?${backLinkQueryParams}`
            : event.target.href
    }
})

const territorySelect = document?.querySelector('#bo-filters-territories');

if (territorySelect) {
    territorySelect.addEventListener('change', function() {
        const currentPage = new URLSearchParams(window.location.search).get('page') || 1;
        const params = new URLSearchParams(new FormData(this.form));
        params.set('page', currentPage);
        localStorage.setItem('back_link_autoaffectation_rule', params.toString());
    });
}

const paginationLinks = document.querySelectorAll('.fr-pagination__list a');

paginationLinks.forEach(link => {
    link.addEventListener('click', function(event) {
        const url = new URL(event.target.href);
        const params = url.searchParams.toString();
        localStorage.setItem('back_link_autoaffectation_rule', params);
    });
});