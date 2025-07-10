const checkAll = document.querySelector('#table-select-checkbox-all');
checkAll?.addEventListeners('click', () => {
  document.querySelectorAll('.checkbox-column').forEach((item) => {
    item.checked = checkAll.checked;
  });
});
