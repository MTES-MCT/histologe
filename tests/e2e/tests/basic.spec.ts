import { test, expect } from '@playwright/test';
import { waitForVueAppToBeInteractive, waitForSpecificElement } from '../utils/vue-app-helper';

test('connexion page loads with correct title', async ({ page }) => {
    await page.goto(`${process.env.BASE_URL ?? 'http://localhost:8080'}/connexion`);
    await expect(page).toHaveTitle("Connexion - Signal-Logement");
});

test('signalement form for locataire', async ({page}) => {
    test.setTimeout(120000);
    
    // Nettoyer le contexte pour avoir une session propre
    await page.context().clearCookies();
    
    await page.goto(`${process.env.BASE_URL ?? 'http://localhost:8080'}/signalement`);

    page.on('requestfailed', request => {
        console.log('Request failed:', request.url(), request.failure());
    });

    await waitForVueAppToBeInteractive(page, 60000);

    // Log tous les boutons visibles avant de cliquer
    const visibleButtonsDebug = await page.locator('button:visible').all();
    for (const btn of visibleButtonsDebug) {
      const text = await btn.textContent();
      const name = await btn.getAttribute('name');
      const id = await btn.getAttribute('id');
      console.log(`Bouton visible: text='${text}', name='${name}', id='${id}'`);
    }
    // console.log(await page.content());
    await page.getByRole('button', { name: 'Je démarre'}).waitFor({ state: 'visible', timeout: 10000 });
    await expect(page.getByRole('button', { name: 'Je démarre' })).toBeVisible();
    await page.getByRole('button', { name: 'Je démarre' }).click();
    


    await page.getByRole('heading', { name: 'Adresse et coordonnées', exact: true }).waitFor({ state: 'visible', timeout: 10000 });
    await expect(page.getByRole('heading', { name: 'Adresse et coordonnées', exact: true })).toBeVisible();
    await page.getByRole('heading', { name: 'Adresse et coordonnées', exact: true }).click();


/*    
    // Attendre que l'application VueJS soit complètement chargée et interactive
    await waitForVueAppToBeInteractive(page, 60000);
    
    // Vérifier que la page est accessible avec le titre correct
    await expect(page).toHaveTitle(/Signaler un problème de logement/);
    
    // Attendre que la page soit complètement chargée
    await page.waitForLoadState('networkidle');
    
    // Vérifier si on est sur la page de reprise ou de nouveau signalement
    const pageContent = await page.content();
    if (pageContent.includes('Mon signalement') || pageContent.includes('Reprendre la saisie')) {
        console.log('Page de reprise détectée, cliquer sur "Non, faire un nouveau signalement"');
        try {
            await page.getByRole('button', { name: 'Non, faire un nouveau signalement' }).click();
            await page.waitForLoadState('networkidle');
        } catch (error) {
            console.log('Bouton "Non, faire un nouveau signalement" non trouvé');
        }
    }
    
    // Attendre spécifiquement pour le titre ou un bouton
    try {
        await waitForSpecificElement(page, 'h1:has-text("Signaler un problème de")', 10000);
        await page.getByRole('heading', { name: 'Signaler un problème de' }).click();
    } catch (error) {
        // Si le titre n'est pas trouvé, essayer de trouver un bouton visible
        console.log('Titre non trouvé, recherche d\'un bouton visible...');
        await page.waitForSelector('button:visible', { timeout: 10000 });
        const visibleButtons = await page.locator('button:visible').all();
        if (visibleButtons.length > 0) {
            console.log(`Trouvé ${visibleButtons.length} boutons visibles`);
            await visibleButtons[0].click();
        }
    }
    */
    // Le reste du test est simplifié pour l'instant
    console.log('Test de base réussi - application VueJS chargée');

});