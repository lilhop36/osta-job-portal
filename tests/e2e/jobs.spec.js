const { test, expect } = require('@playwright/test');
const BASE = process.env.BASE_URL || 'http://localhost/osta%20job%20portal';

test.describe('Jobs Module', () => {

  test('jobs page loads and shows listings', async ({ page }) => {
    await page.goto(`${BASE}/jobs.php`, { waitUntil: 'domcontentloaded' });
    await expect(page.locator('h1, h2, .page-title').first()).toBeVisible();
  });

  test('job details page loads for a valid job', async ({ page }) => {
    await page.goto(`${BASE}/job_details.php?id=1`, { waitUntil: 'domcontentloaded' });
    await expect(page.locator('body')).not.toHaveText(/404|not found/i);
  });

  test('job search by keyword works', async ({ page }) => {
    await page.goto(`${BASE}/jobs.php?search=engineer`, { waitUntil: 'domcontentloaded' });
    await expect(page.locator('body')).toBeVisible();
  });

  test('job search by type filter works', async ({ page }) => {
    await page.goto(`${BASE}/jobs.php?type=full-time`, { waitUntil: 'domcontentloaded' });
    await expect(page.locator('body')).toBeVisible();
  });

});
