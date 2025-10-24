import { test } from '@playwright/test';

test('login for usager', async ({page, context}) => {
  await context.clearCookies();
  await context.clearPermissions();

  await page.goto(`${process.env.BASE_URL ?? 'http://localhost:8080'}/authentification/2db341e0d1fd76a6221666f2155328b8e16783d1619ab1343fae7c87b2bc5886`);
  await page.getByRole('textbox', { name: 'Première lettre de votre pré' }).click();
  await page.getByRole('textbox', { name: 'Première lettre de votre pré' }).fill('n');
  await page.getByRole('textbox', { name: 'Première lettre de votre nom' }).click();
  await page.getByRole('textbox', { name: 'Première lettre de votre nom' }).fill('m');
  await page.getByRole('button', { name: 'Accéder au signalement' }).click();
  await page.getByText('Veuillez saisir le code').click();
  await page.getByRole('textbox', { name: 'Première lettre de votre pré' }).click();
  await page.getByRole('button', { name: 'Accéder au signalement' }).click();
  await page.getByText('Veuillez saisir la première').click();
  await page.getByRole('textbox', { name: 'Première lettre de votre pré' }).click();
  await page.getByRole('textbox', { name: 'Première lettre de votre pré' }).fill('n');
  await page.getByRole('textbox', { name: 'Première lettre de votre nom' }).click();
  await page.getByRole('textbox', { name: 'Première lettre de votre nom' }).fill('m');
  await page.getByRole('textbox', { name: 'Code postal du logement' }).click();
  await page.getByRole('textbox', { name: 'Code postal du logement' }).fill('44850');
  await page.getByRole('button', { name: 'Accéder au signalement' }).click();
  await page.getByText('Noëlle Mamère').click();
  await page.getByText('nouveau').click();
  await page.getByRole('link', { name: 'Quitter' }).click();
});

test('login for bailleur', async ({page, context}) => {
  await context.clearCookies();
  await context.clearPermissions();

  const baseUrl = process.env.BASE_URL ?? 'http://localhost:8080';
  console.log(`[TEST] Starting bailleur login test - Base URL: ${baseUrl}`);

  await page.goto(`${baseUrl}/login-bailleur`);
  console.log(`[TEST] Navigated to login-bailleur, current URL: ${page.url()}`);

  // Attendre que la page soit complètement chargée
  await page.waitForLoadState('networkidle');
  console.log(`[TEST] Page loaded, taking screenshot before form fill`);
  await page.screenshot({ path: '/tmp/bailleur-before-login.png', fullPage: true });

  await page.getByRole('textbox', { name: 'Référence du dossier' }).click();
  await page.getByRole('textbox', { name: 'Référence du dossier' }).fill('2025-12');
  await page.getByRole('textbox', { name: 'Code de connexion' }).click();
  await page.getByRole('textbox', { name: 'Code de connexion' }).fill('salutsalut');

  console.log(`[TEST] Form filled, clicking submit button`);
  await page.getByRole('button', { name: 'Envoyer' }).click();

  // Attendre explicitement la navigation après le submit
  console.log(`[TEST] Waiting for navigation to bailleur page`);
  try {
    await page.waitForURL('**/bailleur/**', { timeout: 10000 });
    console.log(`[TEST] Navigation successful, current URL: ${page.url()}`);
  } catch (e) {
    console.error(`[TEST] Navigation failed! Current URL: ${page.url()}`);
    await page.screenshot({ path: '/tmp/bailleur-after-submit-error.png', fullPage: true });
    throw e;
  }

  await page.getByRole('heading', { name: 'Détails du dossier' }).click();
  await page.getByText('Monsieur Mulder Fox').click();
  await page.getByText('Oui avec aide').click();
  await page.getByRole('link', { name: 'Quitter' }).click();
});

test('login for admin', async ({page, context}) => {
  await context.clearCookies();
  await context.clearPermissions();

  await page.goto(`${process.env.BASE_URL ?? 'http://localhost:8080'}/connexion`);
  await page.waitForLoadState('networkidle');

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

  // Attendre la navigation après connexion réussie
  await page.waitForURL('**/tableau-de-bord', { timeout: 10000 });

  await page.getByRole('link', { name: 'Tableau de bord' }).click();
  await page.getByRole('link', { name: 'Se déconnecter' }).click();
});