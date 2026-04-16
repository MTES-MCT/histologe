import { test, Page } from '@playwright/test';

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

  await page.goto(`${process.env.BASE_URL ?? 'http://localhost:8080'}/connexion`);
  await page.waitForLoadState('networkidle');

  await page.getByRole('textbox', { name: 'Courriel Adresse utilisée' }).click();
  await page.getByRole('textbox', { name: 'Courriel Adresse utilisée' }).fill('admin-01@signal-logement.fr');
  await page.getByRole('textbox', { name: 'Mot de passe Mot de passe dé' }).click();
  await page.getByRole('textbox', { name: 'Mot de passe Mot de passe dé' }).fill('signallogement');
  await page.getByRole('button', { name: 'Se connecter' }).click();

  // Attendre la navigation après connexion
  await page.waitForURL('**/bo/**', { timeout: 10000 });
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

  await page.goto(`${process.env.BASE_URL ?? 'http://localhost:8080'}/connexion`);
  await page.waitForLoadState('networkidle');

  await page.getByRole('textbox', { name: 'Courriel Adresse utilisée' }).click();
  await page.getByRole('textbox', { name: 'Courriel Adresse utilisée' }).fill('admin-territoire-13-01@signal-logement.fr');
  await page.getByRole('textbox', { name: 'Courriel Adresse utilisée' }).press('Tab');
  await page.getByRole('textbox', { name: 'Mot de passe Mot de passe dé' }).fill('signallogement');
  await page.getByRole('button', { name: 'Se connecter' }).click();

  // Attendre la navigation après connexion
  await page.waitForURL('**/bo/**', { timeout: 10000 });
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

  await page.goto(`${process.env.BASE_URL ?? 'http://localhost:8080'}/connexion`);
  await page.waitForLoadState('networkidle');

  await page.getByRole('textbox', { name: 'Courriel Adresse utilisée' }).click();
  await page.getByRole('textbox', { name: 'Courriel Adresse utilisée' }).fill('user-13-01@signal-logement.fr');
  await page.getByRole('textbox', { name: 'Courriel Adresse utilisée' }).press('Tab');
  await page.getByRole('textbox', { name: 'Mot de passe Mot de passe dé' }).fill('signallogement');
  await page.getByRole('button', { name: 'Se connecter' }).click();

  // Attendre la navigation après connexion
  await page.waitForURL('**/bo/**', { timeout: 10000 });
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
