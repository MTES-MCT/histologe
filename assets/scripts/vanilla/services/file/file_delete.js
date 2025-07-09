document.querySelectorAll('.btn-signalement-file-delete').forEach((swbtn) => {
  swbtn.addEventListener('click', (evt) => {
    evt.preventDefault();
    const target = evt.target;
    if (target.getAttribute('data-type') === 'photo') {
      document.querySelectorAll('.fr-modal-file-delete-type').forEach((typeItem) => {
        typeItem.textContent = 'la photo';
      });
    } else {
      document.querySelectorAll('.fr-modal-file-delete-type').forEach((typeItem) => {
        typeItem.textContent = 'le document';
      });
    }
    document.querySelector('.fr-modal-file-delete-filename').textContent =
      target.getAttribute('data-filename');
    document.querySelector('#file-delete-fileid').value = target.getAttribute('data-file-id');
  });
});
