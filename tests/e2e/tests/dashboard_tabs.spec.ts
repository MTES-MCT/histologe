import { test } from '@playwright/test';

test('dashboard tabs for admin', async ({page}) => {
    // Nettoyer le contexte pour avoir une session propre
    await page.context().clearCookies();

  await page.goto(`${process.env.BASE_URL ?? 'http://localhost:8080'}/connexion`);
  await page.getByRole('textbox', { name: 'Courriel Adresse utilisée' }).click();
  await page.getByRole('textbox', { name: 'Courriel Adresse utilisée' }).fill('admin-01@signal-logement.fr');
  await page.getByRole('textbox', { name: 'Mot de passe Mot de passe dé' }).click();
  await page.getByRole('textbox', { name: 'Mot de passe Mot de passe dé' }).fill('signallogement');
  await page.getByRole('button', { name: 'Connexion' }).click();
  await page.getByRole('tab', { name: 'Accueil' }).click();
  await page.getByRole('heading', { name: 'Vos dernières actions' }).click();
  await page.getByRole('tab', { name: 'Nouveaux dossiers' }).click();
  await page.getByRole('heading', { name: 'Dossiers déposés depuis le formulaire usager' }).click();
  await page.evaluate(() => window.scrollTo(0, 0));
  await page.getByRole('tab', { name: 'A fermer' }).click();
  await page.getByRole('heading', { name: 'Dossiers fermés par tous les' }).click();
  await page.evaluate(() => window.scrollTo(0, 0));
  await page.getByRole('tab', { name: 'Messages usagers' }).click();
  await page.getByRole('heading', { name: 'Nouveaux messages' }).click();
  await page.evaluate(() => window.scrollTo(0, 0));
  await page.getByRole('tab', { name: 'A vérifier' }).click();
  await page.getByRole('heading', { name: 'Dossier sans activité' }).click();
  await page.evaluate(() => window.scrollTo(0, 0));
  await page.getByRole('tab', { name: 'Activité récente' }).click();
  await page.locator('#dossiers-activite-recente').getByRole('heading', { name: 'Activité récente' }).click();
});

test('dashboard tabs for RT', async ({page}) => {
    // Nettoyer le contexte pour avoir une session propre
    await page.context().clearCookies();

  await page.goto(`${process.env.BASE_URL ?? 'http://localhost:8080'}/connexion`);
  await page.getByRole('textbox', { name: 'Courriel Adresse utilisée' }).click();
  await page.getByRole('textbox', { name: 'Courriel Adresse utilisée' }).fill('admin-territoire-13-01@signal-logement.fr');
  await page.getByRole('textbox', { name: 'Courriel Adresse utilisée' }).press('Tab');
  await page.getByRole('textbox', { name: 'Mot de passe Mot de passe dé' }).fill('signallogement');
  await page.getByRole('button', { name: 'Connexion' }).click();
  await page.getByRole('tab', { name: 'Accueil' }).click();
  await page.getByRole('heading', { name: 'Vos dernières actions' }).click();
  await page.getByRole('tab', { name: 'Nouveaux dossiers' }).click();
  await page.getByRole('heading', { name: 'Dossiers déposés depuis le formulaire usager' }).click();
  await page.getByRole('tab', { name: 'A fermer' }).click();
  await page.getByRole('heading', { name: 'Dossiers fermés par tous les' }).click();
  await page.getByRole('tab', { name: 'Messages usagers' }).click();
  await page.getByRole('heading', { name: 'Nouveaux messages' }).click();
  await page.getByRole('tab', { name: 'A vérifier' }).click();
  await page.getByRole('heading', { name: 'Dossier sans activité' }).click();
  await page.getByRole('tab', { name: 'Activité récente' }).click();
  await page.locator('#dossiers-activite-recente').getByRole('heading', { name: 'Activité récente' }).click();
});


test('dashboard tabs for Agent', async ({page}) => {
    // Nettoyer le contexte pour avoir une session propre
    await page.context().clearCookies();

  await page.goto(`${process.env.BASE_URL ?? 'http://localhost:8080'}/connexion`);
  await page.getByRole('textbox', { name: 'Courriel Adresse utilisée' }).click();
  await page.getByRole('textbox', { name: 'Courriel Adresse utilisée' }).fill('user-13-01@signal-logement.fr');
  await page.getByRole('textbox', { name: 'Courriel Adresse utilisée' }).press('Tab');
  await page.getByRole('textbox', { name: 'Mot de passe Mot de passe dé' }).fill('signallogement');
  await page.getByRole('button', { name: 'Connexion' }).click();
  await page.getByRole('tab', { name: 'Accueil' }).click();
  await page.getByRole('heading', { name: 'Vos dernières actions' }).click();
  await page.getByRole('tab', { name: 'Nouveaux dossiers' }).click();
  await page.getByRole('heading', { name: 'Nouveaux dossiers' }).click();
  await page.getByRole('tab', { name: 'Messages usagers' }).click();
  await page.getByRole('heading', { name: 'Nouveaux messages' }).click();
  await page.getByRole('tab', { name: 'A vérifier' }).click();
  await page.getByRole('heading', { name: 'Dossier sans activité' }).click();
  await page.getByRole('tab', { name: 'Activité récente' }).click();
  await page.locator('#dossiers-activite-recente').getByRole('heading', { name: 'Activité récente' }).click();
});