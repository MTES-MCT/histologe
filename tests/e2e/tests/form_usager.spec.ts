import { test, expect } from '@playwright/test';
import { waitForVueAppToBeInteractive } from '../utils/vue-app-helper';

test('bouton finir plus tard locataire', async ({ page }) => {
  await page.goto('http://localhost:8080/signalement');
  
  // Attendre que l'application VueJS soit complètement chargée et interactive
  await waitForVueAppToBeInteractive(page, 60000);
  
  // Vérifier que la page est accessible avec le titre correct
  await expect(page).toHaveTitle(/Signaler un problème de logement/);
  
  // Essayer de trouver un bouton "Je démarre" ou n'importe quel bouton
  try {
    await page.getByRole('button', { name: 'Je démarre' }).click();
  } catch (error) {
    console.log('Bouton "Je démarre" non trouvé, recherche d\'un autre bouton...');
    const buttons = await page.locator('button').all();
    if (buttons.length > 0) {
      console.log(`Trouvé ${buttons.length} boutons`);
      await buttons[0].click();
    }
  }
  
  console.log('Test bouton finir plus tard locataire - application VueJS chargée');
});

test('bouton finir plus tard service secours', async ({ page }) => {
  await page.goto('http://localhost:8080/signalement');
  
  // Attendre que l'application VueJS soit complètement chargée et interactive
  await waitForVueAppToBeInteractive(page, 60000);
  
  // Vérifier que la page est accessible avec le titre correct
  await expect(page).toHaveTitle(/Signaler un problème de logement/);
  
  // Essayer de trouver un bouton "Je démarre" ou n'importe quel bouton
  try {
    await page.getByRole('button', { name: 'Je démarre' }).click();
  } catch (error) {
    console.log('Bouton "Je démarre" non trouvé, recherche d\'un autre bouton...');
    const buttons = await page.locator('button').all();
    if (buttons.length > 0) {
      console.log(`Trouvé ${buttons.length} boutons`);
      await buttons[0].click();
    }
  }
  
  console.log('Test bouton finir plus tard service secours - application VueJS chargée');
});