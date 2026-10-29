import { test, expect, Page } from '@playwright/test';

async function gotoFrontStatistiques(page: Page) {
  const statsResponse = page.waitForResponse('**/statistiques-filter?**');
  await page.goto(`${process.env.BASE_URL ?? 'http://localhost:8080'}/iframe/stats`);
  expect((await statsResponse).ok()).toBeTruthy();

  await expect(page.getByText('Initialisation des statistiques...')).toBeHidden();
  await expect(page.getByText('foyers sortis du mal-logement')).toBeVisible();
  await expect(page.getByText('taux de signalements refusés')).toBeVisible();
  await expect(page.getByRole('heading', { name: 'Répartition des signalements par département' })).toBeVisible();
  await expect(page.getByRole('heading', { name: 'Données historiques' })).toBeVisible();
  await expect(page.getByRole('heading', { name: 'Evolution territoriale du mal-logement' })).toBeVisible();
}

function territoryItem(page: Page, title: string) {
  return page.locator('.front-stats-territory-item').filter({ hasText: title });
}

test('front statistiques page', async ({page, context}) => {
  await context.clearCookies();
  await context.clearPermissions();

  await gotoFrontStatistiques(page);

  // Basculer entre l'année en cours et le total
  const currentYear = new Date().getFullYear().toString();
  const perMonth = territoryItem(page, 'Nombre de signalements déposés');
  await expect(perMonth.getByRole('button', { name: currentYear })).toHaveClass(/selected/);
  await perMonth.getByRole('button', { name: 'Total' }).click();
  await expect(perMonth.getByRole('button', { name: 'Total' })).toHaveClass(/selected/);
  await expect(perMonth.getByRole('button', { name: currentYear })).not.toHaveClass(/selected/);
  await expect(perMonth.locator('canvas')).toBeVisible();
  await perMonth.getByRole('button', { name: currentYear }).click();
  await expect(perMonth.getByRole('button', { name: currentYear })).toHaveClass(/selected/);

  // Les désordres les plus courants ne sont disponibles que pour l'année en cours
  const perLogementDesordres = territoryItem(page, 'Logement : désordres les plus courants');
  await expect(perLogementDesordres.getByRole('button', { name: currentYear })).toBeVisible();
  await expect(perLogementDesordres.getByRole('button', { name: 'Total' })).toHaveCount(0);

  // Filtrer par territoire
  const territoireSelect = page.locator('#filter-territoires');
  const territoireId = await territoireSelect.locator('option').nth(1).getAttribute('value');
  const statsResponse = page.waitForResponse(`**/statistiques-filter?territoire=${territoireId}`);
  await territoireSelect.selectOption({ index: 1 });
  expect((await statsResponse).ok()).toBeTruthy();

  await expect(page.getByText('Mise à jour des statistiques...')).toBeHidden();
  await expect(page.locator('#filter-territoires')).toHaveValue(territoireId ?? '');
  await expect(page.locator('#filter-territoires option:checked')).toContainText('Territoire : ');
  await expect(territoryItem(page, 'Répartition des signalements par statut').locator('canvas')).toBeVisible();
});
