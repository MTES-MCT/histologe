import { Page } from '@playwright/test';

export async function waitForVueAppToLoad(page: Page, timeout = 30000) {
  // Attendre que l'application VueJS soit initialisée
  await page.waitForSelector('#app-signalement-form-container', { timeout });
  
  // Attendre que le contenu soit chargé (l'application VueJS a fini de se charger)
  await page.waitForFunction(() => {
    const container = document.querySelector('#app-signalement-form-container');
    return container && 
           container.children.length > 0 && 
           !container.textContent?.includes('Initialisation') &&
           !container.textContent?.includes('Erreur lors de l\'initialisation');
  }, { timeout });
  
  // Attendre un peu plus pour s'assurer que tout est bien rendu
  await page.waitForTimeout(1000);
}

export async function waitForVueAppToBeInteractive(page: Page, timeout = 30000) {
  await waitForVueAppToLoad(page, timeout);
  
  // Attendre que les éléments interactifs soient disponibles
  await page.waitForFunction(() => {
    const buttons = document.querySelectorAll('button');
    const inputs = document.querySelectorAll('input, select, textarea');
    return buttons.length > 0 || inputs.length > 0;
  }, { timeout });
} 