import * as Sentry from '@sentry/browser';

export default function initTabsLoader() {
  document.querySelectorAll('.fr-tabs__panel').forEach((panel) => {
    if (panel.classList.contains('fr-tabs__panel--selected')) {
      loadPanelContent(panel);
    }
  });

  document.querySelectorAll('.fr-tabs__tab').forEach((tab) => {
    tab.addEventListener('click', function () {
      const panelId = this.getAttribute('aria-controls');
      const panel = document.getElementById(panelId);
      loadPanelContent(panel);
    });
  });

  function loadPanelContent(panel) {
    const loaders = panel.querySelectorAll('[data-url]');
    const queryParams = window.location.search;
    if (!loaders.length) {
      const error = `Aucun data-url trouvé dans le panel dont l'id est ${panel.id}`;
      console.error(error);
      Sentry.captureException(new Error(error));
      return;
    }

    loaders.forEach((loader) => {
      let url = loader.dataset.url;
      if (!url) return;
      if (queryParams) {
        const separator = url.includes('?') ? '&' : '?';
        url += separator + queryParams.slice(1);
      }

      loader.innerHTML = '<div class="fr-mt-5v fr-text--center">Chargement...</div>';

      fetch(url, {
        headers: { Accept: 'text/html' },
      })
        .then((response) => {
          if (!response.ok) throw new Error('HTTP error ' + response.status);

          if (response.redirected) {
            const dashboard = document.getElementById('dashboard');
            window.location.href = dashboard?.dataset.urlLogin || '/connexion';
            return;
          }
          return response.text();
        })
        .then((html) => {
          loader.innerHTML = html;
        })
        .catch((err) => {
          loader.innerHTML = '<div class="fr-text--error">Erreur de chargement.</div>';
          console.error('Erreur chargement:', err);
          Sentry.captureException(`Erreur changement : ${err}`);
        });
    });
  }
}
