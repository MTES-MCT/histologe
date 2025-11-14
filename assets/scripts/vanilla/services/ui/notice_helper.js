document.addEventListener('click', (event) => {
  const closeButton = event.target.closest('.fr-notice .fr-btn--close');
  if (closeButton) {
    const notice = closeButton.closest('.fr-notice');
    if (notice) {
      notice.remove();
    }
  }
});
