import initTerritoiresSelect from './territoires_select.js';
import initTabsLoader from "./tabs_loader";

document.addEventListener('DOMContentLoaded', () => {
    const dashboard = document.getElementById('dashboard');
    if (!dashboard) {
        console.error('Erreur de chargement, merci de vérifier que l\'élément #dashboard existe.');
        return;
    }

    initTerritoiresSelect();
    initTabsLoader();
});
