import initFilterTerritoryHandler from './filter_territory_handler';
import initTabsLoader from './tabs_loader';
import * as Sentry from '@sentry/browser';

document.addEventListener('DOMContentLoaded', () => {
  let dashboard = document?.getElementById('app-dashboard');
  if (window.location.pathname !== '/bo/' || dashboard) {
    return;
  }

  dashboard = document?.getElementById('dashboard');
  if (!dashboard && window.location.pathname === '/bo/') {
    const error = "Erreur de chargement, merci de vérifier que l'élément #dashboard existe.";
    console.error(error);
    Sentry.captureException(new Error(error));
    return;
  }

  initFilterTerritoryHandler();
  initTabsLoader();
});
