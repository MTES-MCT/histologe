document.addEventListener('click', (e) => {
  const openBtn = e.target.closest('[data-panel-open]');
  if (openBtn) {
    document.getElementById(openBtn.dataset.panelOpen)?.showModal();
    return;
  }

  const closeBtn = e.target.closest('.js-close-panel');
  if (closeBtn) {
    document.getElementById(closeBtn.dataset.panel)?.close();
    return;
  }

  //ferme le panel au clic exterieur
  if (e.target.matches('dialog.side-panel')) {
    e.target.close();
  }
});
