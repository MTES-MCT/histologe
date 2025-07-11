btnSignalementFileDeleteAddEventListeners();

export function btnSignalementFileDeleteAddEventListeners() {
  document.querySelectorAll('.btn-signalement-file-delete').forEach((swbtn) => {
    swbtn.addEventListener('click', (evt) => {
      evt.preventDefault();
      const target = evt.target;
      document.querySelector('.fr-modal-file-delete-filename').textContent =
        target.getAttribute('data-filename');
      document.querySelector('#file-delete-fileid').value = target.getAttribute('data-file-id');
      document.querySelector('#file-delete-hash-src').value = window.location.hash.substring(1);
    });
  });
}
