const searchArchivedAccountForm = document.getElementById('search-archived-account-form')

if (searchArchivedAccountForm) {
  searchArchivedAccountForm.querySelectorAll('select').forEach((select) => {
    select.addEventListener('change', function () {
      document.getElementById('page').value = 1
      searchArchivedAccountForm.submit()
    })
  })
  searchArchivedAccountForm.querySelectorAll('.search-checkbox-container').forEach((select) => {
    select.addEventListener('searchCheckboxChange', function () {
      document.getElementById('page').value = 1
      searchArchivedAccountForm.submit()
    })
  })
  searchArchivedAccountForm.addEventListener('submit', function () {
    document.getElementById('page').value = 1
  })
}
