document.querySelectorAll('.btn-modal-prev, .btn-modal-next').forEach((button) => {
  button.addEventListener('click', (event) => {
    const currentModal = event.target.closest('dialog');
    const targetModalId = event.target.dataset.prevModal || event.target.dataset.nextModal;
    const targetModal = document.getElementById(targetModalId);

    if (targetModal) {
      dsfr(currentModal).modal.conceal();
      dsfr(targetModal).modal.disclose();
    }
  });
});

document.querySelectorAll('.btn-copy-request, .btn-copy-response').forEach((button) => {
  button.addEventListener('click', () => {
    const targetId = button.dataset.copyTarget;
    const targetElement = document.getElementById(targetId);
    if (targetElement) {
      const textToCopy = targetElement.innerText.trim();
      navigator.clipboard
        .writeText(textToCopy)
        .then(() => {
          const originalText = button.innerText;
          button.innerText = 'Copié !';
          button.classList.replace('fr-icon-clipboard-line', 'fr-icon-checkbox-circle-line');
          setTimeout(() => {
            button.innerText = originalText;
            button.classList.replace('fr-icon-checkbox-circle-line', 'fr-icon-clipboard-line');
          }, 2000);
        })
        .catch((err) => {
          console.error('Erreur lors de la copie : ', err);
        });
    }
  });
});
