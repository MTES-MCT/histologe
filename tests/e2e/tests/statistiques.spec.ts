import { test, expect, Page } from '@playwright/test';
import { login } from '../utils/auth-helper';

async function gotoStatistiques(page: Page) {
  const statsResponse = page.waitForResponse('**/bo/statistiques/filter');
  await page.goto(`${process.env.BASE_URL ?? 'http://localhost:8080'}/bo/statistiques/`);
  expect((await statsResponse).ok()).toBeTruthy();

  await expect(page.getByText('Initialisation des filtres...')).toBeHidden();
  await expect(page.getByRole('heading', { name: 'Statistiques globales' })).toBeVisible();
  await expect(page.getByRole('heading', { name: 'Statistiques détaillées' })).toBeVisible();
  await expect(page.getByText('Nb. signalements refusés')).toBeVisible();
  await expect(page.getByText('Délai validation moy.')).toBeVisible();
  await expect(page.getByText('Nombre total de signalements')).toBeVisible();
  await expect(page.getByText('Répartition par statut')).toBeVisible();
}

/**
 * Déclenche une modification de filtre et attend la mise à jour des statistiques
 */
async function changeFilterAndWaitForStats(page: Page, action: () => Promise<unknown>) {
  const statsResponse = page.waitForResponse('**/bo/statistiques/filter');
  await action();
  expect((await statsResponse).ok()).toBeTruthy();

  await expect(page.getByText('Mise à jour des statistiques...')).toBeHidden();
  await expect(page.getByText('Initialisation des filtres...')).toBeHidden();
  await expect(page.getByText('Répartition par statut')).toBeVisible();
}

async function selectFirstMultiSelectOption(page: Page, label: string) {
  const multiSelect = page.locator('.histo-multi-select').filter({ has: page.getByText(label, { exact: true }) });
  await multiSelect.locator('.selector').click();
  await changeFilterAndWaitForStats(page, () => multiSelect.locator('.selector-items-remaining li').first().click());
  await expect(multiSelect.locator('.selector')).toContainText('1');

  // Fermer la liste déroulante en cliquant à l'extérieur
  await page.getByRole('heading', { name: 'Statistiques détaillées' }).click();
  await expect(multiSelect.locator('.selector-items')).toBeHidden();
}

async function useCommonFilters(page: Page) {
  await changeFilterAndWaitForStats(page, () => page.getByLabel('Statut').selectOption('active'));
  await expect(page.getByLabel('Statut')).toHaveValue('active');

  await changeFilterAndWaitForStats(page, () => page.getByLabel('Type').selectOption('public'));
  await expect(page.getByLabel('Type')).toHaveValue('public');

  await changeFilterAndWaitForStats(page, () => page.getByText('Cocher la case pour comptabiliser les signalements refusés').click());
  await expect(page.locator('#count-refused')).toBeChecked();

  await selectFirstMultiSelectOption(page, 'Communes');

  await changeFilterAndWaitForStats(page, () => page.getByRole('link', { name: 'Tout réinitialiser' }).click());
  await expect(page.getByLabel('Statut')).toHaveValue('all');
  await expect(page.getByLabel('Type')).toHaveValue('all');
  await expect(page.locator('#count-refused')).not.toBeChecked();
}

test('statistiques page and filters for admin', async ({page, context}) => {
  await context.clearCookies();
  await context.clearPermissions();

  await login(page, 'admin-01@signal-logement.fr');
  await gotoStatistiques(page);

  // Le super admin peut filtrer par territoire et voir les signalements archivés
  await expect(page.getByLabel('Territoire')).toBeVisible();
  await expect(page.getByText('Cocher la case pour comptabiliser les signalements archivés')).toBeVisible();

  await changeFilterAndWaitForStats(page, () => page.getByLabel('Territoire').selectOption({ index: 1 }));
  await expect(page.getByLabel('Territoire')).not.toHaveValue('all');

  await useCommonFilters(page);

  await page.getByRole('link', { name: 'Se déconnecter' }).click();
});

test('statistiques page and filters for RT', async ({page, context}) => {
  await context.clearCookies();
  await context.clearPermissions();

  await login(page, 'admin-territoire-13-01@signal-logement.fr');
  await gotoStatistiques(page);

  // Le responsable de territoire n'a pas le filtre territoire mais voit la répartition par partenaires
  await expect(page.getByLabel('Territoire')).toBeHidden();
  await expect(page.getByText('Répartition par partenaires')).toBeVisible();

  await useCommonFilters(page);

  await page.getByRole('link', { name: 'Se déconnecter' }).click();
});
