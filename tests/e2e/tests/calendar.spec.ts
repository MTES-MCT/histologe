import { test, expect, Page } from '@playwright/test';
import { login } from '../utils/auth-helper';

// Événements des fixtures à dates relatives :
// - J+2 : "Visite #2022-3" (territoire 13) et "Club Utilisateurs (RT)"
// - J+7 : "Club partenaires Communes/SCHS/EPCI/CCAS" (agents et admins partenaire des communes)
const VISITE_TITLE = 'Visite #2022-3';
const CLUB_RT_TITLE = 'Club Utilisateurs (RT)';
const CLUB_PARTNER_TITLE = 'Club partenaires Communes/SCHS/EPCI/CCAS';

function daysFromNow(days: number): Date {
  const date = new Date();
  date.setDate(date.getDate() + days);
  return date;
}

// Lundi de la semaine de la date (semaine du lundi au samedi dans le calendrier)
function mondayOf(date: Date): Date {
  const monday = new Date(date);
  monday.setHours(0, 0, 0, 0);
  monday.setDate(monday.getDate() - ((monday.getDay() + 6) % 7));
  return monday;
}

// Le dimanche est masqué dans le calendrier (hiddenDays)
function isSunday(date: Date): boolean {
  return date.getDay() === 0;
}

async function openCalendar(page: Page, email: string) {
  await login(page, email);
  await page.goto('/bo/calendrier/');
  await expect(page.getByRole('heading', { name: 'Calendrier', level: 1 })).toBeVisible();
  await acceptCguIfNeeded(page);
}

// Fenêtre des CGU affichée tant que l'utilisateur n'a pas accepté la dernière version (cas des agents des fixtures)
async function acceptCguIfNeeded(page: Page) {
  const cguModal = page.getByRole('dialog', { name: "Mise à jour des conditions d'utilisation" });
  if (!(await cguModal.isVisible())) {
    return;
  }
  await cguModal.getByText("J'ai pris connaissance des conditions d'utilisation du service.").click();
  await cguModal.getByRole('button', { name: 'Accepter et continuer' }).click();
  await expect(cguModal).toBeHidden();
}

// Affiche la vue demandée, sur la semaine qui contient la date
async function showWeekOf(page: Page, view: 'Semaine' | 'Liste', date: Date) {
  // Choix de la vue affiché sous forme d'onglets
  await page.getByRole('tab', { name: view, exact: true }).click();
  const weeksAhead = Math.round((mondayOf(date).getTime() - mondayOf(new Date()).getTime()) / (7 * 24 * 3600 * 1000));
  for (let i = 0; i < weeksAhead; i++) {
    await page.getByRole('button', { name: 'Suivant', exact: true }).click();
  }
}

test('calendar for admin', async ({ page, context }) => {
  const visiteDate = daysFromNow(2);
  test.skip(isSunday(visiteDate), 'Visite des fixtures un dimanche (jour masqué)');

  await context.clearCookies();
  await context.clearPermissions();

  await openCalendar(page, 'admin-01@signal-logement.fr');

  // Filtres : territoire, pas d'opérateur, "mes dossiers" désactivé
  await expect(page.getByLabel('Territoire')).toBeVisible();
  await expect(page.getByRole('group', { name: 'Opérateur' })).toHaveCount(0);
  await expect(page.getByRole('button', { name: 'Afficher uniquement mes dossiers' })).toHaveAttribute('aria-pressed', 'false');

  // Vue mois par défaut, puis semaine et liste
  await expect(page.locator('.calendar-app').getByText(VISITE_TITLE, { exact: true }).first()).toBeVisible();
  await showWeekOf(page, 'Semaine', visiteDate);
  await expect(page.locator('.calendar-app').getByText(VISITE_TITLE, { exact: true }).first()).toBeVisible();
  await showWeekOf(page, 'Liste', visiteDate);
  await expect(page.locator('.calendar-app').getByText(VISITE_TITLE, { exact: true }).first()).toBeVisible();
  await expect(page.getByRole('link', { name: 'Voir le dossier' }).first()).toBeVisible();

  await page.getByRole('link', { name: 'Se déconnecter' }).click();
});

test('calendar for RT', async ({ page, context }) => {
  const visiteDate = daysFromNow(2);
  test.skip(isSunday(visiteDate), 'Visite des fixtures un dimanche (jour masqué)');

  await context.clearCookies();
  await context.clearPermissions();

  await openCalendar(page, 'admin-territoire-13-01@signal-logement.fr');

  // Filtres : opérateurs, pas de territoire, "mes dossiers" désactivé
  await expect(page.getByRole('group', { name: 'Opérateur' })).toBeVisible();
  await expect(page.getByLabel('Territoire')).toHaveCount(0);
  await expect(page.getByRole('button', { name: 'Afficher uniquement mes dossiers' })).toHaveAttribute('aria-pressed', 'false');

  // Vue semaine : tous les événements du jour sont affichés (pas de lien "+ x autres")
  await showWeekOf(page, 'Semaine', visiteDate);
  await expect(page.locator('.calendar-app').getByText(CLUB_RT_TITLE, { exact: true }).first()).toBeVisible();

  // Popup de détail d'une visite, fermée avec Échap
  await page.locator('.calendar-app').getByText(VISITE_TITLE, { exact: true }).first().click();
  const popover = page.getByRole('dialog', { name: VISITE_TITLE });
  await expect(popover).toBeVisible();
  await expect(popover.getByRole('link', { name: 'Voir le dossier' })).toBeVisible();
  await page.keyboard.press('Escape');
  await expect(popover).toBeHidden();

  await page.getByRole('link', { name: 'Se déconnecter' }).click();
});

test('calendar for Agent', async ({ page, context }) => {
  const clubDate = daysFromNow(7);
  test.skip(isSunday(clubDate), 'Club des fixtures un dimanche (jour masqué)');

  await context.clearCookies();
  await context.clearPermissions();

  await openCalendar(page, 'user-13-05@signal-logement.fr');

  // Filtres : ni territoire, ni opérateur, "mes dossiers" activé par défaut
  await expect(page.getByLabel('Territoire')).toHaveCount(0);
  await expect(page.getByRole('group', { name: 'Opérateur' })).toHaveCount(0);
  await expect(page.getByRole('button', { name: 'Afficher uniquement mes dossiers' })).toHaveAttribute('aria-pressed', 'true');

  // Vue mois (J+2 toujours visible) : le club réservé aux RT n'est pas affiché
  await expect(page.getByRole('status')).toHaveText('');
  await expect(page.locator('.calendar-app').getByText(CLUB_RT_TITLE, { exact: true })).toHaveCount(0);

  // Club réservé aux partenaires des communes, avec lien d'inscription dans la popup
  await showWeekOf(page, 'Semaine', clubDate);
  await page.locator('.calendar-app').getByText(CLUB_PARTNER_TITLE, { exact: true }).first().click();
  const popover = page.getByRole('dialog', { name: CLUB_PARTNER_TITLE });
  await expect(popover).toBeVisible();
  await expect(popover.getByRole('link', { name: /S'inscrire/ })).toBeVisible();
  await page.keyboard.press('Escape');
  await expect(popover).toBeHidden();

  await page.getByRole('link', { name: 'Se déconnecter' }).click();
});
