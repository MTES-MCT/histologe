import { test, expect } from '@playwright/test';
import { waitForVueAppToBeInteractive, waitForSpecificElement } from '../utils/vue-app-helper';

test('connexion page loads with correct title', async ({ page }) => {
    await page.goto(`${process.env.BASE_URL ?? 'http://localhost:8080'}/connexion`);
    // console.log(await page.content());
    await expect(page).toHaveTitle("Connexion - Signal-Logement");
});

test('signalement form for locataire', async ({page}) => {
    test.setTimeout(120000);
    
    // Nettoyer le contexte pour avoir une session propre
    await page.context().clearCookies();
    
    await page.goto(`${process.env.BASE_URL ?? 'http://localhost:8080'}/signalement`);

    page.on('requestfailed', request => {
        console.log('Request failed:', request.url(), request.failure());
    });

    await waitForVueAppToBeInteractive(page, 60000);

    // Log tous les boutons visibles avant de cliquer
    /* const visibleButtonsDebug = await page.locator('button:visible').all();
    for (const btn of visibleButtonsDebug) {
      const text = await btn.textContent();
      const name = await btn.getAttribute('name');
      const id = await btn.getAttribute('id');
      console.log(`Bouton visible: text='${text}', name='${name}', id='${id}'`);
    } */
    //console.log(await page.content());
    await page.getByRole('button', { name: 'Je démarre'}).waitFor({ state: 'visible', timeout: 10000 });
    await expect(page.getByRole('button', { name: 'Je démarre' })).toBeVisible();
    await page.getByRole('button', { name: 'Je démarre' }).click();
    


    await page.getByRole('heading', { name: 'Adresse et coordonnées', exact: true }).waitFor({ state: 'visible', timeout: 10000 });
    await expect(page.getByRole('heading', { name: 'Adresse et coordonnées', exact: true })).toBeVisible();
    await page.getByRole('heading', { name: 'Adresse et coordonnées', exact: true }).click();


    await page.getByRole('button', { name: 'C\'est parti' }).click();
    await page.getByRole('heading', { name: 'Commençons par l\'adresse du' }).click();
    await page.getByRole('textbox', { name: 'Adresse du logement Format' }).click();
    await page.getByRole('textbox', { name: 'Adresse du logement Format' }).fill('3 rue de l\'école 13');
    await page.getByText('Rue de l\'ecole 13007 Marseille').click();
    await page.getByRole('button', { name: 'Suivant' }).click();
    await page.getByText('Pour vous-même', { exact: true }).click();
    await page.getByText('Locataire du logement').click();
    await page.locator('#signalement_concerne_logement_social_autre_tiers').getByText('Non').click();
    await page.getByRole('button', { name: 'Suivant' }).click();
    await page.getByText('Monsieur').click();
    await page.getByRole('textbox', { name: 'Nom de famille' }).click();
    await page.getByRole('textbox', { name: 'Nom de famille' }).fill('Fragione');
    await page.getByRole('textbox', { name: 'Nom de famille' }).press('Tab');
    await page.getByRole('textbox', { name: 'Prénom' }).fill('Philippe');
    await page.getByRole('textbox', { name: 'Prénom' }).press('Tab');
    await page.getByRole('textbox', { name: 'Adresse e-mail Format attendu' }).fill('phil'+Date.now()+'@iam.fr'); // Add Date.now() to avoid double detection
    await page.getByRole('textbox', { name: 'Adresse e-mail Format attendu' }).dblclick();
    await page.locator('#vos_coordonnees_occupant_tel').click();
    await page.locator('#vos_coordonnees_occupant_tel').fill('0607080906');
    await page.getByRole('button', { name: 'Suivant' }).click();
    await page.getByRole('textbox', { name: 'Nom de famille ou de l\'' }).fill('Imperial Asiatic Men');
    await page.getByRole('button', { name: 'Suivant' }).click();
    await page.getByText('Oui', { exact: true }).click();
    await page.locator('#info_procedure_bail_moyen-select').selectOption('courrier');
    await page.getByRole('textbox', { name: 'Quand avez-vous prévenu votre' }).click();
    await page.getByRole('textbox', { name: 'Quand avez-vous prévenu votre' }).fill('09/2023');
    await page.getByRole('textbox', { name: 'Quand avez-vous prévenu votre' }).press('Tab');
    await page.getByRole('textbox', { name: 'Quelle a été la réponse de' }).fill('Demain c\'est loin');
    await page.getByRole('button', { name: 'Suivant' }).click();
    await page.getByText('Le bâtiment (et / ou parties').click();
    await page.locator('#zone_concernee_debut_desordres-select').selectOption('less_1_month');
    await page.getByRole('button', { name: 'Suivant' }).click();
    await page.getByRole('heading', { name: 'Type et composition du' }).click();
    await page.getByRole('button', { name: 'C\'est parti' }).click();
    await page.getByText('Votre logement est…').click();
    await page.getByText('Une maison seule').click();
    await page.getByRole('button', { name: 'Suivant' }).click();
    await page.getByText('Votre logement est composé d\'').click();
    await page.getByText('Une pièce unique', { exact: true }).click();
    await page.getByRole('textbox', { name: 'Quelle est la superficie de' }).click();
    await page.getByRole('textbox', { name: 'Quelle est la superficie de' }).fill('66');
    await page.getByText('Oui', { exact: true }).click();
    await page.getByRole('button', { name: 'Suivant' }).click();
    await page.getByText('Est-ce qu\'au moins une des pi').click();
    await page.getByText('Je ne sais pas').click();
    await page.getByLabel('Avez-vous une cuisine ou un').getByText('Oui').click();
    await page.getByLabel('Avez-vous une salle de bain,').getByText('Oui').click();
    await page.getByLabel('Avez-vous des toilettes (WC) ?').getByText('Oui').click();
    await page.getByLabel('Est-ce que les toilettes (WC').getByText('Non').click();
    await page.getByRole('button', { name: 'Suivant' }).click();
    await page.getByRole('textbox', { name: 'Combien de personnes vivent' }).fill('4');
    await page.getByRole('textbox', { name: 'Dont combien d\'enfants ?' }).click();
    await page.getByRole('textbox', { name: 'Dont combien d\'enfants ?' }).fill('2');
    await page.getByText('Non', { exact: true }).click();
    await page.getByRole('button', { name: 'Suivant' }).click();
    await page.getByText('A quelle date avez-vous emmé').click();
    await page.getByRole('textbox', { name: 'A quelle date avez-vous emmé' }).fill('2023-01-10');
    await page.getByLabel('Avez-vous reçu un bail ?').getByText('Oui').click();
    await page.getByLabel('Avez-vous fait un état des').getByText('Oui').click();
    await page.getByLabel('Avez-vous reçu le DPE du').getByText('Oui').click();
    await page.locator('#bail_dpe_classe_energetique-select').selectOption('C');
    await page.getByText('A partir de').click();
    await page.getByRole('button', { name: 'Suivant' }).click();
    await page.getByRole('heading', { name: 'Votre situation' }).click();
    await page.getByRole('button', { name: 'Suivant' }).click();
    // await page.getByText('Oui').first().click(); : Playwright used this selector, but it conflicts with another "Oui" that is in a hidden modal
    await page.getByLabel('Avez-vous fait une demande').getByText('Oui').first().click();
    await page.getByText('Signal Logement ne permet pas').click();
    await page.getByLabel('Recevez-vous une aide ou').getByText('Non').click();
    await page.getByRole('button', { name: 'Suivant' }).click();
    await page.getByLabel('Souhaitez-vous ou avez-vous').getByText('Non').click();
    await page.getByLabel('Est-ce qu\'un travailleur ou').getByText('Non').click();
    await page.getByRole('button', { name: 'Suivant' }).click();
    await page.getByRole('heading', { name: 'Les désordres' }).click();
    await page.getByRole('button', { name: 'Suivant' }).click();
    await page.getByRole('heading', { name: 'Le bâtiment (et / ou parties' }).click();
    await page.getByText('Eau et évacuation').click();
    await page.getByRole('button', { name: 'Valider ma sélection' }).click();
    await page.getByRole('heading', { name: 'Eau et évacuation dans le bâ' }).click();
    await page.getByText('Il n\'y a pas d\'eau potable').click();
    await page.getByRole('button', { name: 'Suivant' }).click();
    await page.getByRole('heading', { name: 'Précisions sur votre situation' }).click();
    await page.getByRole('textbox', { name: 'Votre message ici :' }).click();
    await page.getByRole('textbox', { name: 'Votre message ici :' }).fill('On est pas nés sous la même étoile');
    await page.getByRole('button', { name: 'Suivant' }).click();
    await page.getByRole('heading', { name: 'Les désordres renseignés' }).click();
    await page.getByRole('button', { name: 'Suivant' }).click();
    await page.getByRole('heading', { name: 'La procédure' }).click();
    await page.getByRole('button', { name: 'J\'y suis presque !' }).click();
    await page.getByText('Oui', { exact: true }).click();
    await page.getByRole('textbox', { name: 'Quelle a été la réponse de' }).click();
    await page.getByRole('textbox', { name: 'Quelle a été la réponse de' }).fill('Personne ne joue avec les mêmes cartes');
    await page.getByText('Oui, je veux rester dans mon').click();
    await page.getByRole('button', { name: 'Suivant' }).click();
    await page.getByText('Je comprends que Signal Logement va prévenir le bailleur (propriétaire) de mon').click();
    await page.getByText('Je comprends qu\'une visite du').click();
    await page.getByText('Je comprends que Signal Logement ne permet pas de faire une demande de logement').click();
    await page.getByText('Je certifie ne pas avoir').click();
    await page.getByText('Je certifie avoir pris').click();
    await page.getByRole('button', { name: 'Suivant' }).click();
    await page.getByRole('heading', { name: 'Validation du signalement' }).click();
    await page.getByRole('button', { name: 'Valider mon signalement' }).click();
});