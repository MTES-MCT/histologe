function openPanel(panelId) {
  const panel = document.getElementById(panelId);
  if (!panel) return;
  panel.showModal();
  document.querySelectorAll(`[data-panel-open="${panelId}"]`).forEach((btn) => {
    btn.setAttribute('aria-expanded', 'true');
  });
}

function closePanel(panel) {
  panel.close();
  document.querySelectorAll(`[data-panel-open="${panel.id}"]`).forEach((btn) => {
    btn.setAttribute('aria-expanded', 'false');
  });
}

document.addEventListener('click', (e) => {
  const openBtn = e.target.closest('[data-panel-open]');
  if (openBtn) {
    openPanel(openBtn.dataset.panelOpen);
    return;
  }

  const closeBtn = e.target.closest('.js-close-panel');
  if (closeBtn) {
    const panel = document.getElementById(closeBtn.dataset.panel);
    if (panel) closePanel(panel);
    return;
  }

  // ferme le panel au clic extérieur
  if (e.target.matches('dialog.side-panel')) {
    closePanel(e.target);
  }
});
