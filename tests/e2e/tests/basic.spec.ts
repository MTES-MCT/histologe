import { test, expect } from '@playwright/test';

test('connexion page loads with correct title', async ({ page }) => {
    await page.goto(`${process.env.BASE_URL ?? 'http://localhost:8080'}/connexion`);
    await expect(page).toHaveTitle("Connexion - Signal-Logement");
});
