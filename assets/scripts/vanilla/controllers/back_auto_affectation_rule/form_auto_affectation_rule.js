import { loadWindowWithLocalStorage, updateLocalStorageWithFormParams, updateLocalStorageWithPaginationParams, updateLocalStorageOnEvent } from '../../services/list_filter_helper'

document.querySelectorAll('.btn-delete-autoaffectationrule').forEach(swbtn => {
  swbtn.addEventListener('click', evt => {
    const target = evt.target
    document.querySelector('.fr-modal-autoaffectationrule-delete-description').textContent = target.getAttribute('data-autoaffectationrule-description')
    document.querySelector('#fr-modal-autoaffectationrule-delete-id').value = target.getAttribute('data-autoaffectationrule-id')
    document.querySelector('#autoaffectationrule_delete_form').addEventListener('submit', (e) => {
      document.querySelector('#autoaffectationrule_delete_form_submit').textContent = 'Suppression en cours...'
      document.querySelector('#autoaffectationrule_delete_form_submit').disabled = true
    })
  })
})

const searchArchivedUsersForm = document.getElementById('search-auto-affectation-rule-form')
if (searchArchivedUsersForm) {
  updateLocalStorageWithFormParams('search-auto-affectation-rule-form')
}

loadWindowWithLocalStorage('click', '[data-filter-list-auto-affectation-rule]', 'search-auto-affectation-rule-form')
updateLocalStorageOnEvent('change', '#autoaffectation-rule-filters-territories', 'back_link_autoaffectation_rule')
updateLocalStorageWithPaginationParams('click', '#autoaffectation-rule-pagination a', 'back_link_autoaffectation_rule')
