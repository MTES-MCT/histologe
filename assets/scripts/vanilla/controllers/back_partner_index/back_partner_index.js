import { updateLocalStorageWithFormParams } from '../../services/ui/list_filter_helper';

const searchPartnerForm = document.getElementById('search-partner-form');

if (searchPartnerForm) {
  updateLocalStorageWithFormParams('search-partner-form');
}
