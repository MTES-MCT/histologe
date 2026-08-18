document.addEventListener('click', (event) => {
  const closeButton = event.target.closest(
    '.fr-notice .fr-btn--close, .fr-notice .fr-icon-close-circle-fill'
  );

  if (closeButton) {
    if (closeButton.dataset.closeUrl) {
      fetch(closeButton.dataset.closeUrl, { method: 'POST' }).catch(() => {});
    }

    const notice = closeButton.closest('.fr-notice');
    if (notice) {
      notice.remove();
    }
  }
});
