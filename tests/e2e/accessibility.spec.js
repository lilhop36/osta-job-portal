const { test, expect } = require('@playwright/test');
const BASE = process.env.BASE_URL || 'http://localhost/osta%20job%20portal';

test.describe('Accessibility Checks', () => {

  test('skip to content link exists on public pages', async ({ page }) => {
    await page.goto(`${BASE}/index.php`, { waitUntil: 'domcontentloaded' });
    const skipLink = page.locator('a:has-text("Skip to main content")');
    await expect(skipLink).toBeVisible();
  });

  test('skip to content link exists on login page', async ({ page }) => {
    await page.goto(`${BASE}/login.php`, { waitUntil: 'domcontentloaded' });
    const skipLink = page.locator('a:has-text("Skip to main content")');
    await expect(skipLink).toBeVisible();
  });

  test('navigation has aria-label', async ({ page }) => {
    await page.goto(`${BASE}/index.php`, { waitUntil: 'domcontentloaded' });
    const nav = page.locator('nav[aria-label="Main navigation"]');
    await expect(nav).toBeVisible();
  });

});
