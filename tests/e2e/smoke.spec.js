const { test, expect } = require('@playwright/test');
const BASE = process.env.BASE_URL || 'http://localhost/osta%20job%20portal';

test.describe('Smoke Tests - Critical Pages', () => {

  const publicPages = [
    { path: '/index.php', title: /OSTA/i },
    { path: '/jobs.php', title: /OSTA/i },
    { path: '/about.php', title: /OSTA/i },
    { path: '/contact.php', title: /OSTA/i },
    { path: '/login.php', title: /OSTA/i },
    { path: '/register.php', title: /OSTA/i },
  ];

  for (const { path, title } of publicPages) {
    test(`${path} loads successfully`, async ({ page }) => {
      const resp = await page.goto(`${BASE}${path}`, { waitUntil: 'domcontentloaded' });
      expect(resp.status()).toBe(200);
      await expect(page).toHaveTitle(title);
    });
  }

  test('404 page returns 404 status', async ({ page }) => {
    const resp = await page.goto(`${BASE}/nonexistent-page.php`, { waitUntil: 'domcontentloaded' });
    expect(resp.status()).toBe(404);
  });

});
