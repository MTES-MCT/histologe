// n'est plus utilisé que pour supprimer des suivis ou des partenaires affectés dans la fiche signalement
// essayer de s'en séparer
document?.querySelectorAll('[data-delete]')?.forEach((actionBtn) => {
  actionBtn.addEventListeners('click touchdown', (event) => {
    event.preventDefault();
    let className;
    if (event.target.classList.contains('partner-row-delete')) {
      className = '.partner-row';
    }
    if (confirm('Voulez-vous vraiment supprimer cet élément ?')) {
      const formData = new FormData();
      formData.append('_token', actionBtn.getAttribute('data-token'));
      const value = actionBtn.getAttribute('data-value') ?? null;
      if (value) {
        (formData.append('item', 'Tag'), formData.append('value', value));
      }
      fetch(actionBtn.getAttribute('data-delete'), {
        method: 'POST',
        body: formData,
      }).then((r) => {
        if (r.ok) {
          if (className && className !== undefined && className !== null) {
            actionBtn?.closest(className).remove();
          }
          if (
            event.target.classList.contains('partner-row-delete') ||
            event.target.classList.contains('suivi-row-delete')
          ) {
            window.location.reload(true);
          }
        }
      });
    }
  });
});
