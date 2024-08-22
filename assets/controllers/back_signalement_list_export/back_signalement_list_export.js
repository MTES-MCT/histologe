const checkAll = document.querySelector('#table-select-checkbox-all');
checkAll?.addEventListeners('click', (event) => {
  document.querySelectorAll('.checkbox-column').forEach(item => {
      item.checked = checkAll.checked;
  })
})