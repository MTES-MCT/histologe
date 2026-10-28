import { test, Page } from '@playwright/test';
import { login } from '../utils/auth-helper';

async function clickTabAndTriggerDisclose(page: Page, tabName: string) {
  const tab = page.getByRole('tab', { name: tabName });
  await tab.click();

  // Attendre que le DSFR ait changé le panel actif
  const panelId = await tab.getAttribute('aria-controls');
  
  // Dispatcher l'événement sur le bon panel et vérifier
  await page.evaluate((id) => {
    const panel = document.getElementById(id);
    if (panel) {
      panel.dispatchEvent(new Event('dsfr.disclose', { bubbles: true }));
      return true;
    }
    return false;
  }, panelId);
}

test('dashboard tabs for admin', async ({page, context}) => {
  await context.clearCookies();
  await context.clearPermissions();

  await login(page, 'admin-01@signal-logement.fr');
  await clickTabAndTriggerDisclose(page, 'Accueil');
  await page.getByRole('heading', { name: 'Vos dernières actions' }).click();
  await clickTabAndTriggerDisclose(page, 'Nouveaux dossiers');
  await page.getByRole('heading', { name: 'Dossiers déposés depuis le formulaire usager' }).click();
  await page.evaluate(() => window.scrollTo(0, 0));
  await clickTabAndTriggerDisclose(page, 'A fermer');
  await page.getByRole('heading', { name: 'Dossiers fermés par tous les' }).click();
  await page.evaluate(() => window.scrollTo(0, 0));
  await clickTabAndTriggerDisclose(page, 'Messages usagers');
  await page.getByRole('heading', { name: 'Nouveaux messages' }).click();
  await page.evaluate(() => window.scrollTo(0, 0));
  await clickTabAndTriggerDisclose(page, 'A vérifier');
  await page.getByRole('heading', { name: 'Dossier sans activité' }).click();
  await page.evaluate(() => window.scrollTo(0, 0));
  await clickTabAndTriggerDisclose(page, 'Activité récente');
  await page.locator('#dossiers-activite-recente').getByRole('heading', { name: 'Activité récente' }).click();

  await page.getByRole('link', { name: 'Se déconnecter' }).click();
});

test('dashboard tabs for RT', async ({page, context}) => {
  await context.clearCookies();
  await context.clearPermissions();

  await login(page, 'admin-territoire-13-01@signal-logement.fr');
  await clickTabAndTriggerDisclose(page, 'Accueil');
  await page.getByRole('heading', { name: 'Vos dernières actions' }).click();
  await clickTabAndTriggerDisclose(page, 'Nouveaux dossiers');
  await page.getByRole('heading', { name: 'Dossiers déposés depuis le formulaire usager' }).click();
  await clickTabAndTriggerDisclose(page, 'A fermer');
  await page.getByRole('heading', { name: 'Dossiers fermés par tous les' }).click();
  await clickTabAndTriggerDisclose(page, 'Messages usagers');
  await page.getByRole('heading', { name: 'Nouveaux messages' }).click();
  await clickTabAndTriggerDisclose(page, 'A vérifier');
  await page.getByRole('heading', { name: 'Dossier sans activité' }).click();
  await clickTabAndTriggerDisclose(page, 'Activité récente');
  await page.locator('#dossiers-activite-recente').getByRole('heading', { name: 'Activité récente' }).click();

  await page.getByRole('link', { name: 'Se déconnecter' }).click();
});


test('dashboard tabs for Agent', async ({page, context}) => {
  await context.clearCookies();
  await context.clearPermissions();

  await login(page, 'user-13-01@signal-logement.fr');
  await clickTabAndTriggerDisclose(page, 'Accueil');
  await page.getByRole('heading', { name: 'Vos dernières actions' }).click();
  await clickTabAndTriggerDisclose(page, 'Nouveaux dossiers');
  await page.getByRole('heading', { name: 'Nouveaux dossiers' }).click();
  await clickTabAndTriggerDisclose(page, 'Messages usagers');
  await page.getByRole('heading', { name: 'Nouveaux messages' }).click();
  await clickTabAndTriggerDisclose(page, 'A vérifier');
  await page.getByRole('heading', { name: 'Dossier sans activité' }).click();
  await clickTabAndTriggerDisclose(page, 'Activité récente');
  await page.locator('#dossiers-activite-recente').getByRole('heading', { name: 'Activité récente' }).click();

  await page.getByRole('link', { name: 'Se déconnecter' }).click();
});
