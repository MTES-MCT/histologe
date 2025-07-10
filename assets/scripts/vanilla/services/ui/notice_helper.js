document.querySelectorAll('.fr-notice .fr-btn--close').forEach((closeButtonElement) => {
  closeButtonElement.addEventListener('click', (event) => {
    const notice = event.target.parentNode.parentNode.parentNode;
    notice.parentNode.removeChild(notice);
  });
});
