import initTerritoiresSelect from './territoires_select.js';
import initTabsLoader from "./tabs_loader";
import * as Sentry from '@sentry/browser'

document.addEventListener('DOMContentLoaded', () => {
    const currentDashboard = document.getElementById('app-dashboard');
    if (currentDashboard) {
        return;
    }
    const dashboard = document.getElementById('dashboard');
    if (!dashboard) {
        const error = 'Erreur de chargement, merci de vérifier que l\'élément #dashboard existe.';
        console.error(error);
        Sentry.captureException(new Error(error));
        return;
    }

    initTerritoiresSelect();
    initTabsLoader();
});
