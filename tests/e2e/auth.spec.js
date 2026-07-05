const { test, expect } = require('@playwright/test');
const BASE = process.env.BASE_URL || 'http://localhost/osta%20job%20portal';

test.describe('Authentication', () => {

  test('login page loads and shows form', async ({ page }) => {
    await page.goto(`${BASE}/login.php`, { waitUntil: 'domcontentloaded' });
    await expect(page.locator('#email')).toBeVisible();
    await expect(page.locator('#password')).toBeVisible();
    await expect(page.locator('button[type="submit"]')).toBeVisible();
  });

  test('register page loads and shows form', async ({ page }) => {
    await page.goto(`${BASE}/register.php`, { waitUntil: 'domcontentloaded' });
    await expect(page.locator('input[name="username"]')).toBeVisible();
    await expect(page.locator('input[name="email"]')).toBeVisible();
    await expect(page.locator('input[name="password"]')).toBeVisible();
  });

  test('login with valid credentials redirects to dashboard', async ({ page }) => {
    await page.goto(`${BASE}/login.php`, { waitUntil: 'domcontentloaded' });
    await page.fill('#email', 'admin@gmail.com');
    await page.fill('#password', 'admin123');
    await Promise.all([
      page.waitForNavigation({ waitUntil: 'domcontentloaded' }),
      page.click('button[type="submit"]'),
    ]);
    await expect(page).toHaveURL(/dashboard/);
  });

  test('login with invalid credentials shows error', async ({ page }) => {
    await page.goto(`${BASE}/login.php`, { waitUntil: 'domcontentloaded' });
    await page.fill('#email', 'wrong@example.com');
    await page.fill('#password', 'badpassword');
    await Promise.all([
      page.waitForNavigation({ waitUntil: 'domcontentloaded' }),
      page.click('button[type="submit"]'),
    ]);
    await expect(page.locator('.alert-danger, .error, .alert')).toBeVisible();
  });

  test('logout works and redirects to login', async ({ page }) => {
    await page.goto(`${BASE}/login.php`, { waitUntil: 'domcontentloaded' });
    await page.fill('#email', 'admin@gmail.com');
    await page.fill('#password', 'admin123');
    await Promise.all([
      page.waitForNavigation({ waitUntil: 'domcontentloaded' }),
      page.click('button[type="submit"]'),
    ]);
    await page.goto(`${BASE}/logout.php`, { waitUntil: 'domcontentloaded' });
    await expect(page).toHaveURL(/(login|index)/);
  });

});
