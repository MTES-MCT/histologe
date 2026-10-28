import { Page } from '@playwright/test';

export async function login(page: Page, email: string, password = 'signallogement') {
  await page.goto(`${process.env.BASE_URL ?? 'http://localhost:8080'}/connexion`);
  await page.waitForLoadState('networkidle');

  await page.getByRole('textbox', { name: 'Courriel Adresse utilisée' }).click();
  await page.getByRole('textbox', { name: 'Courriel Adresse utilisée' }).fill(email);
  await page.getByRole('textbox', { name: 'Mot de passe Mot de passe dé' }).click();
  await page.getByRole('textbox', { name: 'Mot de passe Mot de passe dé' }).fill(password);
  await page.getByRole('button', { name: 'Se connecter' }).click();

  // Attendre la navigation après connexion
  await page.waitForURL('**/bo/**', { timeout: 10000 });
}
