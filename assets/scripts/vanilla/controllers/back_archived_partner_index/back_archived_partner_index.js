const searchArchivedPartnerForm = document.getElementById('search-archived-partner-form')

if (searchArchivedPartnerForm) {
  searchArchivedPartnerForm.querySelectorAll('select').forEach((select) => {
    select.addEventListener('change', function () {
      document.getElementById('page').value = 1
      searchArchivedPartnerForm.submit()
    })
  })
  searchArchivedPartnerForm.querySelectorAll('.search-checkbox-container').forEach((select) => {
    select.addEventListener('searchCheckboxChange', function () {
      document.getElementById('page').value = 1
      searchArchivedPartnerForm.submit()
    })
  })
  searchArchivedPartnerForm.addEventListener('submit', function () {
    document.getElementById('page').value = 1
  })
}
