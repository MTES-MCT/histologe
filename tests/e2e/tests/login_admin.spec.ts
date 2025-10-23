import { test } from '@playwright/test';

test('login for admin', async ({page}) => {
  await page.goto(`${process.env.BASE_URL ?? 'http://localhost:8080'}/connexion`);
  await page.getByRole('button', { name: 'Connexion' }).click();
  await page.getByText('Connexion impossible').click();
  await page.getByRole('textbox', { name: 'Courriel Adresse utilisée' }).click();
  await page.getByRole('textbox', { name: 'Courriel Adresse utilisée' }).fill('bla');
  await page.getByRole('button', { name: 'Connexion' }).click();
  await page.getByText('Veuillez saisir votre mot de').click();
  await page.getByRole('textbox', { name: 'Courriel Adresse utilisée' }).fill('bla');
  await page.getByRole('textbox', { name: 'Mot de passe Mot de passe dé' }).fill('bla');
  await page.getByRole('button', { name: 'Connexion' }).click();
  await page.getByText('Identifiants invalides.').click();
  await page.getByText('Adresse utilisée lors de la').click();
  await page.getByRole('textbox', { name: 'Courriel Adresse utilisée' }).fill('admin-01@signal-logement.fr');
  await page.getByRole('textbox', { name: 'Mot de passe Mot de passe dé' }).fill('signallogement');
  await page.getByRole('button', { name: 'Connexion' }).click();
  await page.getByRole('link', { name: 'Tableau de bord' }).click();
});