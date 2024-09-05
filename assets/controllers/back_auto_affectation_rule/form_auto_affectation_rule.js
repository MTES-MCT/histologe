import { loadWindowWithLocalStorage, updateLocalStorageWithFormParams, updateLocalStorageWithPaginationParams} from '../list_filter_helper'

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

document?.querySelectorAll('[data-filter-list-auto-affectation-rule]').forEach(link => {
    link.addEventListener('click', (event) => loadWindowWithLocalStorage(event, 'back_link_autoaffectation_rule'));
  })
  
const territorySelect = document?.querySelector('#autoaffectation-rule-filters-territories');
if (territorySelect) {
    territorySelect.addEventListener('change', () => updateLocalStorageWithFormParams('back_link_autoaffectation_rule'));
}

const paginationLinks = document.querySelectorAll('#autoaffectation-rule-pagination a');
paginationLinks.forEach(link => {
    link.addEventListener('click', (event) => updateLocalStorageWithPaginationParams(event, 'back_link_autoaffectation_rule'));
});