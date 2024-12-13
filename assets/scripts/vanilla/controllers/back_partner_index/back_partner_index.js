const searchPartnerForm = document.getElementById('search-partner-form')

if (searchPartnerForm) {
  searchPartnerForm.querySelectorAll('select').forEach((select) => {
    select.addEventListener('change', function () {
      document.getElementById('page').value = 1
      searchPartnerForm.submit()
    })
  })
  searchPartnerForm.querySelectorAll('.search-checkbox-container').forEach((select) => {
    select.addEventListener('searchCheckboxChange', function () {
      document.getElementById('page').value = 1
      searchPartnerForm.submit()
    })
  })
  searchPartnerForm.addEventListener('submit', function () {
    document.getElementById('page').value = 1
  })
}
