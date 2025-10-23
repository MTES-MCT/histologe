import { test } from '@playwright/test';

test('login for admin', async ({page}) => {
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
});