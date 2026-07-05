const { test, expect } = require('@playwright/test');
const BASE = process.env.BASE_URL || 'http://localhost/osta%20job%20portal';

async function loginAsAdmin(page) {
  await page.goto(`${BASE}/login.php`, { waitUntil: 'domcontentloaded' });
  await page.fill('#email', 'admin@gmail.com');
  await page.fill('#password', 'admin123');
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'domcontentloaded' }),
    page.click('button[type="submit"]'),
  ]);
}

test.describe('Admin Panel', () => {

  test('admin dashboard loads', async ({ page }) => {
    await loginAsAdmin(page);
    await expect(page).toHaveURL(/dashboard/);
  });

  test('manage users page loads', async ({ page }) => {
    await loginAsAdmin(page);
    await page.goto(`${BASE}/admin/manage_users.php`, { waitUntil: 'domcontentloaded' });
    await expect(page.locator('body')).toBeVisible();
  });

  test('manage jobs page loads', async ({ page }) => {
    await loginAsAdmin(page);
    await page.goto(`${BASE}/admin/manage_jobs.php`, { waitUntil: 'domcontentloaded' });
    await expect(page.locator('body')).toBeVisible();
  });

  test('system health page loads', async ({ page }) => {
    await loginAsAdmin(page);
    await page.goto(`${BASE}/admin/system_health.php`, { waitUntil: 'domcontentloaded' });
    await expect(page.locator('body')).toBeVisible();
  });

  test('audit log page loads', async ({ page }) => {
    await loginAsAdmin(page);
    await page.goto(`${BASE}/admin/audit_log.php`, { waitUntil: 'domcontentloaded' });
    await expect(page.locator('body')).toBeVisible();
  });

});
