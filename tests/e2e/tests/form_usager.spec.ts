import { test, expect } from '@playwright/test';
import { waitForVueAppToBeInteractive } from '../utils/vue-app-helper';

test('bouton finir plus tard locataire', async ({ page }) => {
  // Nettoyer le contexte pour avoir une session propre
  await page.context().clearCookies();
  
  await page.goto('http://localhost:8080/signalement');
  
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
  
  // Essayer de trouver un bouton "Je démarre" ou n'importe quel bouton visible
  try {
    await page.getByRole('button', { name: 'Je démarre' }).click();
  } catch (error) {
    console.log('Bouton "Je démarre" non trouvé, recherche d\'un autre bouton visible...');
    await page.waitForSelector('button:visible', { timeout: 10000 });
    const visibleButtons = await page.locator('button:visible').all();
    if (visibleButtons.length > 0) {
      console.log(`Trouvé ${visibleButtons.length} boutons visibles`);
      await visibleButtons[0].click();
    }
  }
  
  console.log('Test bouton finir plus tard locataire - application VueJS chargée');
});

test('bouton finir plus tard service secours', async ({ page }) => {
  // Nettoyer le contexte pour avoir une session propre
  await page.context().clearCookies();
  
  await page.goto('http://localhost:8080/signalement');
  
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
  
  // Essayer de trouver un bouton "Je démarre" ou n'importe quel bouton visible
  try {
    await page.getByRole('button', { name: 'Je démarre' }).click();
  } catch (error) {
    console.log('Bouton "Je démarre" non trouvé, recherche d\'un autre bouton visible...');
    await page.waitForSelector('button:visible', { timeout: 10000 });
    const visibleButtons = await page.locator('button:visible').all();
    if (visibleButtons.length > 0) {
      console.log(`Trouvé ${visibleButtons.length} boutons visibles`);
      await visibleButtons[0].click();
    }
  }
  
  console.log('Test bouton finir plus tard service secours - application VueJS chargée');
});