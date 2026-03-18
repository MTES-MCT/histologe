import { test, expect } from '@playwright/test';
import { waitForVueAppToBeInteractive } from '../utils/vue-app-helper';

test('bouton finir plus tard locataire', async ({ page }) => {
    await page.goto(`${process.env.BASE_URL ?? 'http://localhost:8080'}/signalement`);

    page.on('requestfailed', request => {
        console.log('Request failed:', request.url(), request.failure());
    });

    await page.route('**/geocodage/search/**', route => {
        route.fulfill({
            status: 200,
            contentType: 'application/json',
            body: '{"type":"FeatureCollection","features":[{"type":"Feature","geometry":{"type":"Point","coordinates":[-1.415839,46.904057]},"properties":{"label":"8 la bodinière 85260 Montréverd","score":0.8633009090909091,"housenumber":"8","id":"85197_ipz92q_00008","banId":"cb810140-ff7c-4ab7-9de6-b20d24024bff","name":"8 la bodinière","postcode":"85260","citycode":"85197","oldcitycode":"85197","x":364035.12,"y":6654271.28,"city":"Montréverd","oldcity":"SAINT-ANDRE-TREIZE-VOIES","context":"85, Vendée, Pays de la Loire","type":"housenumber","importance":0.49631,"street":"la bodinière","_type":"address"}},{"type":"Feature","geometry":{"type":"Point","coordinates":[-1.446559,47.349607]},"properties":{"label":"8 la bodiniere 44850 Saint-Mars-du-Désert","score":0.8622718181818181,"housenumber":"8","id":"44179_xarfhv_00008","banId":"3e67546d-3179-4fe2-9e09-e803c00607f0","name":"8 la bodiniere","postcode":"44850","citycode":"44179","x":364485.73,"y":6703813.17,"city":"Saint-Mars-du-Désert","context":"44, Loire-Atlantique, Pays de la Loire","type":"housenumber","importance":0.48499,"street":"la bodiniere","_type":"address"}},{"type":"Feature","geometry":{"type":"Point","coordinates":[-0.59581,46.797285]},"properties":{"label":"8 la Bodinière 79440 Courlay","score":0.8567218181818181,"housenumber":"8","id":"79103_spk9vh_00008","banId":"424e89e6-ae47-4ebb-8e74-d4d9c5f3600a","name":"8 la Bodinière","postcode":"79440","citycode":"79103","x":425836.5,"y":6639259.68,"city":"Courlay","context":"79, Deux-Sèvres, Nouvelle-Aquitaine","type":"housenumber","importance":0.42394,"street":"la Bodinière","_type":"address"}},{"type":"Feature","geometry":{"type":"Point","coordinates":[0.353316,47.554646]},"properties":{"label":"8 La Bodiniere 37330 Couesmes","score":0.85556,"housenumber":"8","id":"37084_9wks90_00008","name":"8 La Bodiniere","postcode":"37330","citycode":"37084","x":500990.11,"y":6720476.99,"city":"Couesmes","context":"37, Indre-et-Loire, Centre-Val de Loire","type":"housenumber","importance":0.41116,"street":"La Bodiniere","_type":"address"}},{"type":"Feature","geometry":{"type":"Point","coordinates":[-1.333595,46.546723]},"properties":{"label":"8 la bodiniere 85310 Le Tablier","score":0.8527854545454545,"housenumber":"8","id":"85285_zs5pd1_00008","banId":"ee8b3dd2-94c3-45da-90ba-0471ab6d8476","name":"8 la bodiniere","postcode":"85310","citycode":"85285","x":368109.12,"y":6614298.53,"city":"Le Tablier","context":"85, Vendée, Pays de la Loire","type":"housenumber","importance":0.38064,"street":"la bodiniere","_type":"address"}},{"type":"Feature","geometry":{"type":"Point","coordinates":[-1.30441,46.925661]},"properties":{"label":"la bodinière 85600 Montaigu-Vendée","score":0.5541537762237762,"id":"85146_azqka8","banId":"edd32a96-05e7-405f-ab00-134603f1f10c","name":"la bodinière","postcode":"85600","citycode":"85146","oldcitycode":"85146","x":372635.07,"y":6656199.07,"city":"Montaigu-Vendée","oldcity":"MONTAIGU","context":"85, Vendée, Pays de la Loire","type":"street","importance":0.55723,"street":"la bodinière","_type":"address"}},{"type":"Feature","geometry":{"type":"Point","coordinates":[-0.978336,47.134113]},"properties":{"label":"La Bodinière 49450 Sèvremoine","score":0.5496637762237762,"id":"49301_ehguxo","banId":"6ec9b38f-d454-4d3a-962c-7a0ace747e0c","name":"La Bodinière","postcode":"49450","citycode":"49301","oldcitycode":"49301","x":398578.26,"y":6678021.84,"city":"Sèvremoine","oldcity":"St Macaire-en-Mauges","context":"49, Maine-et-Loire, Pays de la Loire","type":"street","importance":0.50784,"street":"La Bodinière","_type":"address"}},{"type":"Feature","geometry":{"type":"Point","coordinates":[-1.30912,47.181967]},"properties":{"label":"la bodiniere 44330 Vallet","score":0.5489928671328671,"id":"44212_xbrj44","banId":"72291cd5-3e4b-4e32-8a8b-adf8a6de1108","name":"la bodiniere","postcode":"44330","citycode":"44212","x":373830,"y":6684644.47,"city":"Vallet","context":"44, Loire-Atlantique, Pays de la Loire","type":"street","importance":0.50046,"street":"la bodiniere","_type":"address"}},{"type":"Feature","geometry":{"type":"Point","coordinates":[0.565353,47.425392]},"properties":{"label":"La Bodiniere 37230 Fondettes","score":0.545761958041958,"id":"37109_zp45ye","banId":"6bc149d7-4d89-4fdd-8707-d49435dc8e7c","name":"La Bodiniere","postcode":"37230","citycode":"37109","x":516485.69,"y":6705611.87,"city":"Fondettes","context":"37, Indre-et-Loire, Centre-Val de Loire","type":"street","importance":0.46492,"street":"La Bodiniere","_type":"address"}},{"type":"Feature","geometry":{"type":"Point","coordinates":[-1.116211,48.510459]},"properties":{"label":"La Bodinière 35420 Louvigné-du-Désert","score":0.5435483216783217,"id":"35162_ypywfn","banId":"3ffd8a09-20a4-4aad-8d30-66d16b18a094","name":"La Bodinière","postcode":"35420","citycode":"35162","x":396109.78,"y":6831279.39,"city":"Louvigné-du-Désert","context":"35, Ille-et-Vilaine, Bretagne","type":"street","importance":0.44057,"street":"La Bodinière","_type":"address"}}],"query":"8 La Bodini"}',
        })
    })
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