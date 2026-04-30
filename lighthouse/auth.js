/**
 * Script Puppeteer pour authentifier l'utilisateur avant les tests Lighthouse
 * Ce script se connecte avec les credentials des fixtures
 */
module.exports = async (browser, context) => {
  const page = await browser.newPage();

  // Se connecter
  await page.goto('http://localhost:8080/connexion', { waitUntil: 'networkidle2' });

  // Remplir le formulaire de connexion
  await page.type('#login-email', 'admin-01@signal-logement.fr');
  await page.type('#login-password', 'signallogement');

  // Soumettre le formulaire
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'networkidle2' }),
    page.click('button[type="submit"]'),
  ]);

  await page.close();
};
