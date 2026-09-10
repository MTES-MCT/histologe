function openPanel(panelId) {
  /** @type {HTMLDialogElement|null} */
  const panel = document.getElementById(panelId);
  if (!panel) return;
  panel.showModal();
  panel.dispatchEvent(new CustomEvent('panel:open', { bubbles: true, detail: { panelId } }));
  document.querySelectorAll(`[data-panel-open="${panelId}"]`).forEach((btn) => {
    btn.setAttribute('aria-expanded', 'true');
  });
}

function closePanel(panel, { resetForm = false } = {}) {
  panel.close();
  if (resetForm) {
    resetPanelForms(panel);
  }
  panel.dispatchEvent(
    new CustomEvent('panel:close', { bubbles: true, detail: { panelId: panel.id } })
  );
  document.querySelectorAll(`[data-panel-open="${panel.id}"]`).forEach((btn) => {
    btn.setAttribute('aria-expanded', 'false');
  });
}

function resetPanelForms(panel) {
  panel.querySelectorAll('form').forEach((form) => {
    form.reset();
  });
  if (panel.querySelector('.search-checkbox-container')) {
    window.dispatchEvent(new Event('refreshSearchCheckboxContainerEvent'));
  }
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
    if (panel) {
      const resetForm =
        closeBtn.hasAttribute('data-panel-reset-form') ||
        panel.hasAttribute('data-panel-reset-form');
      closePanel(panel, { resetForm });
    }
    return;
  }

  // ferme le panel au clic extérieur
  if (e.target.matches('dialog.side-panel')) {
    closePanel(e.target, { resetForm: e.target.hasAttribute('data-panel-reset-form') });
  }
});
