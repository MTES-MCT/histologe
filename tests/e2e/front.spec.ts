import { test, expect } from '@playwright/test';

test('Histologe - Home', async ({ page }) => {
  await page.goto('/');
  await expect(page.getByRole('heading', { name: 'Sortez du mal-logement' })).toBeVisible();
  await expect(page.getByRole('button', { name: 'Aide' })).toBeVisible();
  await expect(page.getByRole('button', { name: 'Infos utiles' })).toBeVisible();
  await expect(page.getByRole('button', { name: 'Recevoir mon lien de suivi' })).toBeVisible();
  await expect(page.getByRole('link', { name: 'Voir les statistiques' })).toBeVisible();
});

test('Histologe - Qui sommes-nous ?', async ({ page }) => {
  await page.goto('/qui-sommes-nous');
  await expect(page.getByRole('heading', { name: 'Qui sommes-nous ?' })).toBeVisible();
  await expect(page.getByRole('heading', { name: 'Une solution reconnue' })).toBeVisible();
  await expect(page.locator('div').filter({ hasText: 'Voir le fil d’Ariane Accueil' })).toBeVisible();
});

test('Histologe - Contact', async ({ page }) => {
  await page.goto('/contact');

  await page.getByLabel('Adresse e-mail Renseignez l\'').click();
  await page.getByLabel('Adresse e-mail Renseignez l\'').fill('coucou@adresse.fr');
  await page.getByLabel('Adresse e-mail Renseignez l\'').press('Tab');
  await page.getByLabel('Adresse du logement Saisissez').fill('12 rue du port 13200');
  await page.getByText('Rue du Port 13200 Arles').click();
  await page.getByRole('button', { name: 'Recevoir mon lien de suivi' }).click();
  await expect(page.getByText('Si un signalement correspond')).toBeVisible();
});