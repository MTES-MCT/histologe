import { test } from '@playwright/test';

test('login for bailleur', async ({page}) => {
  await page.goto(`${process.env.BASE_URL ?? 'http://localhost:8080'}/login-bailleur`);
  await page.getByRole('textbox', { name: 'Référence du dossier' }).click();
  await page.getByRole('textbox', { name: 'Référence du dossier' }).fill('2025-12');
  await page.getByRole('textbox', { name: 'Code de connexion' }).click();
  await page.getByRole('textbox', { name: 'Code de connexion' }).fill('salutsalut');
  await page.getByRole('button', { name: 'Envoyer' }).click();
  await page.getByRole('heading', { name: 'Détails du dossier' }).click();
  await page.getByText('Monsieur Mulder Fox').click();
  await page.getByText('Oui avec aide').click();
});