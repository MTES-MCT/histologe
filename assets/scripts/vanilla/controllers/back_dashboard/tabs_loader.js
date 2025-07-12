import * as Sentry from '@sentry/browser';
import sortHandler from './sort_handler';

export default function initTabsLoader() {
  if (window.location.hash) {
    const hash = window.location.hash.substring(1);
    const targetPanel = document.getElementById(hash);

    if (targetPanel) {
      const targetTabId = targetPanel.getAttribute('aria-labelledby');
      const targetTab = document.getElementById(targetTabId);

      if (targetTab) {
        targetTab.click();
      }
    }
  }

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
  async function loadPanelContent(panelOrBody) {
    let loaders = [];
    if (panelOrBody.dataset?.url) {
      loaders = [panelOrBody];
    } else {
      loaders = panelOrBody.querySelectorAll('[data-url]');
    }

    const queryParams = window.location.search;
    if (!loaders.length) {
      const error = `Aucun data-url trouvé dans le panel dont l'id est ${panelOrBody.id}`;
      console.error(error);
      Sentry.captureException(new Error(error));
      return;
    }

    for (const loader of loaders) {
      let url = loader.dataset.url;
      if (!url) continue;

      if (queryParams) {
        const separator = url.includes('?') ? '&' : '?';
        url += separator + queryParams.slice(1);
      }

      loader.innerHTML = '<div class="fr-mt-5v fr-text--center">Chargement...</div>';

      try {
        const response = await fetch(url, {
          headers: { Accept: 'text/html' },
        });

        if (!response.ok) {
          throw new Error('HTTP error ' + response.status);
        }

        if (response.redirected) {
          const dashboard = document.getElementById('dashboard');
          window.location.href = dashboard?.dataset.urlLogin || '/connexion';
          return;
        }

        loader.innerHTML = await response.text();
      } catch (err) {
        if (err.message.includes('403')) {
          loader.innerHTML =
            '<div class="fr-text--error">Accès refusé, merci de contacter un administrateur.</div>';
        }
        console.error('Erreur chargement:', err);
        Sentry.captureException(err);
      }
    }
  }
  sortHandler(loadPanelContent);
}
