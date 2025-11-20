import {
  loadWindowWithLocalStorage,
  updateLocalStorageWithFormParams,
  updateLocalStorageWithPaginationParams,
  updateLocalStorageOnEvent,
} from '../../services/ui/list_filter_helper';

document.addEventListener('click', (evt) => {
  const target = evt.target.closest('.btn-delete-autoaffectationrule');
  if (target) {
    document.querySelector('.fr-modal-autoaffectationrule-delete-description').textContent = target.getAttribute('data-autoaffectationrule-description');
    document.querySelector('#fr-modal-autoaffectationrule-delete-id').value = target.getAttribute('data-autoaffectationrule-id');
  }
});

const searchAutoAffectationRuleForm = document.getElementById('search-auto-affectation-rule-form');
if (searchAutoAffectationRuleForm) {
  updateLocalStorageWithFormParams('search-auto-affectation-rule-form');
}

loadWindowWithLocalStorage(
  'click',
  '[data-filter-list-auto-affectation-rule]',
  'search-auto-affectation-rule-form'
);
updateLocalStorageOnEvent(
  'change',
  '#autoaffectation-rule-filters-territories',
  'back_link_autoaffectation_rule'
);
updateLocalStorageWithPaginationParams(
  'click',
  '#autoaffectation-rule-pagination a',
  'back_link_autoaffectation_rule'
);
