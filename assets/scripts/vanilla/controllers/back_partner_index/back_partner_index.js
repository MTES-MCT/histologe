import { updateLocalStorageWithFormParams } from '../../services/list_filter_helper'

const searchPartnerForm = document.getElementById('search-partner-form')

if (searchPartnerForm) {
  updateLocalStorageWithFormParams('search-partner-form')
}
