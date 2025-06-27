import { chromium, FullConfig } from '@playwright/test';

async function globalSetup(config: FullConfig) {
  const browser = await chromium.launch();
  const page = await browser.newPage();
  
  // Vérifier que l'application est accessible
  try {
    await page.goto('http://localhost:8080/signalement');
    await page.waitForSelector('#app-signalement-form-container', { timeout: 60000 });
    console.log('✅ Application is ready for testing');
  } catch (error) {
    console.error('❌ Application is not ready:', error);
    throw error;
  } finally {
    await browser.close();
  }
}

export default globalSetup; 