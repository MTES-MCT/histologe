import { test, expect } from '@playwright/test';
import { waitForVueAppToBeInteractive } from '../utils/vue-app-helper';

test('bouton finir plus tard locataire', async ({ page }) => {
    await page.goto(`${process.env.BASE_URL ?? 'http://localhost:8080'}/signalement`);

    page.on('requestfailed', request => {
        console.log('Request failed:', request.url(), request.failure());
    });
    await waitForVueAppToBeInteractive(page, 60000);
    await page.getByRole('button', { name: 'Je démarre'}).waitFor({ state: 'visible', timeout: 10000 });
    await expect(page.getByRole('button', { name: 'Je démarre' })).toBeVisible();
    await page.getByRole('button', { name: 'Je démarre' }).click();
    await page.getByRole('button', { name: 'C\'est parti' }).click();
    await page.getByRole('textbox', { name: 'Adresse du logement Format' }).click();
    await page.getByRole('textbox', { name: 'Adresse du logement Format' }).fill('8 La Bodini');
    await page.getByText('8 la bodiniere 44850 Saint-').click();
    await page.getByRole('button', { name: 'Suivant' }).click();
    await page.getByText('Pour vous-même', { exact: true }).click();
    await page.locator('#signalement_concerne_profil_detail_occupant').getByText('Locataire du logement').click();
    await page.locator('#signalement_concerne_logement_social_autre_tiers').getByText('Non').click();
    await page.getByRole('button', { name: 'Suivant' }).click();
    await page.getByText('Madame').click();
    await page.getByRole('textbox', { name: 'Nom de famille' }).click();
    await page.getByRole('textbox', { name: 'Nom de famille' }).fill('Sattler');
    await page.getByRole('textbox', { name: 'Prénom' }).click();
    await page.getByRole('textbox', { name: 'Prénom' }).fill('Ellie');
    await page.getByRole('textbox', { name: 'Adresse e-mail Format attendu' }).click();
    await page.getByRole('textbox', { name: 'Adresse e-mail Format attendu' }).fill('ellie.sattler.'+Date.now()+'@jurassic.park');// Add Date.now() to avoid double detection
    await page.locator('#vos_coordonnees_occupant_tel').click();
    await page.locator('#vos_coordonnees_occupant_tel').fill('0288997788');
    await page.getByRole('button', { name: 'Suivant' }).click();
    await page.getByRole('textbox', { name: 'Nom de famille ou de l\'organisme' }).click();
    await expect(page.getByRole('button', { name: 'Finir plus tard' })).toBeVisible();
    await page.getByRole('textbox', { name: 'Nom de famille ou de l\'organisme' }).fill('Mon super bailleur');
    await page.getByRole('button', { name: 'Finir plus tard' }).click();
    await expect(page.getByText('Un e-mail a été envoyé à')).toBeVisible();
    await expect(page.getByRole('link', { name: 'Retourner à l\'accueil' })).toBeVisible();
});