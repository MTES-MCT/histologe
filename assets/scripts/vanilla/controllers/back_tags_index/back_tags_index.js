const searchTagForm = document.getElementById('search-tag-form')

if (searchTagForm) {
  searchTagForm.querySelectorAll('select').forEach((select) => {
    select.addEventListener('change', function () {
      document.getElementById('page').value = 1
      searchTagForm.submit()
    })
  })
  searchTagForm.querySelectorAll('.search-checkbox-container').forEach((select) => {
    select.addEventListener('searchCheckboxChange', function () {
      document.getElementById('page').value = 1
      searchTagForm.submit()
    })
  })
  searchTagForm.addEventListener('submit', function () {
    document.getElementById('page').value = 1
  })
}
