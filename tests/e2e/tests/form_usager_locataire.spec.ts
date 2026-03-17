import { test, expect } from '@playwright/test';
import { waitForVueAppToBeInteractive } from '../utils/vue-app-helper';

test('signalement form for locataire', async ({page}) => {
    test.setTimeout(120000);
    
    // Nettoyer le contexte pour avoir une session propre
    await page.context().clearCookies();
    
    await page.goto(`${process.env.BASE_URL ?? 'http://localhost:8080'}/signalement`);

    page.on('requestfailed', request => {
        console.log('Request failed:', request.url(), request.failure());
    });

    await waitForVueAppToBeInteractive(page, 60000);
    await page.route('**/geocodage/search/**', route => {
        route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: '{"type":"FeatureCollection","features":[{"type":"Feature","geometry":{"type":"Point","coordinates":[5.446618,43.531033]},"properties":{"label":"3 Rue de l\'Ecole 13100 Aix-en-Provence","score":0.8801781818181817,"housenumber":"3","id":"13001_8566uu_00003","banId":"38c9dab8-0043-446c-99e3-a91bc63853c0","name":"3 Rue de l\'Ecole","postcode":"13100","citycode":"13001","x":897817.04,"y":6273282.5,"city":"Aix-en-Provence","context":"13, Bouches-du-Rhône, Provence-Alpes-Côte d\'Azur","type":"housenumber","importance":0.68196,"street":"Rue de l\'Ecole","_type":"address"}},{"type":"Feature","geometry":{"type":"Point","coordinates":[5.360444,43.278392]},"properties":{"label":"3 Rue de l\'ecole 13007 Marseille","score":0.8749827272727272,"housenumber":"3","id":"13207_2965_00003","banId":"c0a61d9c-8916-400e-9fba-9dd372db007e","name":"3 Rue de l\'ecole","postcode":"13007","citycode":"13207","x":891691.06,"y":6245000.42,"city":"Marseille","district":"Marseille 7e Arrondissement","context":"13, Bouches-du-Rhône, Provence-Alpes-Côte d\'Azur","type":"housenumber","importance":0.62481,"street":"Rue de l\'ecole","_type":"address"}},{"type":"Feature","geometry":{"type":"Point","coordinates":[5.460397,43.555482]},"properties":{"label":"3 Rue de l\'Ecole 13100 Aix-en-Provence","score":0.8746154545454545,"housenumber":"3","id":"13001_0870_00003","banId":"265bc206-94b6-466d-90f1-9bdbee5f2aff","name":"3 Rue de l\'Ecole","postcode":"13100","citycode":"13001","x":898846.1,"y":6276033.17,"city":"Aix-en-Provence","context":"13, Bouches-du-Rhône, Provence-Alpes-Côte d\'Azur","type":"housenumber","importance":0.62077,"street":"Rue de l\'Ecole","_type":"address"}},{"type":"Feature","geometry":{"type":"Point","coordinates":[-4.085699,48.610375]},"properties":{"label":"3 Rue de l\'Ecole 29440 Trézilidé","score":0.7122709090909091,"housenumber":"3","id":"29301_f885e7_00003","name":"3 Rue de l\'Ecole","postcode":"29440","citycode":"29301","x":178339.91,"y":6857885.55,"city":"Trézilidé","context":"29, Finistère, Bretagne","type":"housenumber","importance":0.33498,"street":"Rue de l\'Ecole","_type":"address"}},{"type":"Feature","geometry":{"type":"Point","coordinates":[5.631364,43.21392]},"properties":{"label":"Rue de l’Ecole 13600 Ceyreste","score":0.6632602597402597,"id":"13023_0068","banId":"e04adc10-538f-4e43-941d-99d576524e3e","name":"Rue de l’Ecole","postcode":"13600","citycode":"13023","x":913923.47,"y":6238532.5,"city":"Ceyreste","context":"13, Bouches-du-Rhône, Provence-Alpes-Côte d\'Azur","type":"street","importance":0.43872,"street":"Rue de l’Ecole","_type":"address"}},{"type":"Feature","geometry":{"type":"Point","coordinates":[6.031596,47.450296]},"properties":{"label":"Rue de l\'Ecole 70190 Trésilley","score":0.61964,"id":"70507_0005","banId":"cad2cbd1-02fe-43df-8551-4946e64a15e9","name":"Rue de l\'Ecole","postcode":"70190","citycode":"70507","x":928383.9,"y":6709933.74,"city":"Trésilley","context":"70, Haute-Saône, Bourgogne-Franche-Comté","type":"street","importance":0.31604,"street":"Rue de l\'Ecole","_type":"address"}},{"type":"Feature","geometry":{"type":"Point","coordinates":[5.054749,43.406128]},"properties":{"label":"3 Rue de l\'Ecole Vieille 13500 Martigues","score":0.5797109090909092,"housenumber":"3","id":"13056_0530_00003","name":"3 Rue de l\'Ecole Vieille","postcode":"13500","citycode":"13056","x":866502.21,"y":6258500.81,"city":"Martigues","context":"13, Bouches-du-Rhône, Provence-Alpes-Côte d\'Azur","type":"housenumber","importance":0.61682,"street":"Rue de l\'Ecole Vieille","_type":"address"}},{"type":"Feature","geometry":{"type":"Point","coordinates":[4.706909,43.524113]},"properties":{"label":"3 Rue de l\'Ecole du Sambuc 13200 Arles","score":0.547100303030303,"housenumber":"3","id":"13004_1259_00003","banId":"faa1b580-9504-4df4-b7f8-1e6f2476818d","name":"3 Rue de l\'Ecole du Sambuc","postcode":"13200","citycode":"13004","x":838037.11,"y":6270940.5,"city":"Arles","context":"13, Bouches-du-Rhône, Provence-Alpes-Côte d\'Azur","type":"housenumber","importance":0.68477,"street":"Rue de l\'Ecole du Sambuc","_type":"address"}},{"type":"Feature","geometry":{"type":"Point","coordinates":[5.55226,43.39321]},"properties":{"label":"3 Place de l’Ecole 13124 Peypin","score":0.5162634265734265,"housenumber":"3","id":"13073_0100_00003","banId":"a4606c42-53db-4bd1-9cc1-bc0ee155d45d","name":"3 Place de l’Ecole","postcode":"13124","citycode":"13073","x":906850.69,"y":6258241.68,"city":"Peypin","context":"13, Bouches-du-Rhône, Provence-Alpes-Côte d\'Azur","type":"housenumber","importance":0.48659,"street":"Place de l’Ecole","_type":"address"}},{"type":"Feature","geometry":{"type":"Point","coordinates":[4.91921,43.839695]},"properties":{"label":"3 Place de l\'Ecole 13670 Verquières","score":0.5035934265734265,"housenumber":"3","id":"13116_0002_00003","banId":"2d7c42ca-381b-4673-a2cb-2822281e5b64","name":"3 Place de l\'Ecole","postcode":"13670","citycode":"13116","x":854350.27,"y":6306396.31,"city":"Verquières","context":"13, Bouches-du-Rhône, Provence-Alpes-Côte d\'Azur","type":"housenumber","importance":0.34722,"street":"Place de l\'Ecole","_type":"address"}}],"query":"3 rue de l\'école 13"}',
        })
    })

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
    await page.locator('#signalement_concerne_profil_detail_occupant').getByText('Locataire du logement').click();
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
    await page.getByRole('heading', { name: 'Bénéficiez d’une démarche acc' }).click();
    await page.getByText('Non', { exact: true }).click();
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
