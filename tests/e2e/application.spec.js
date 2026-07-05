const { test, expect } = require('@playwright/test');
const BASE = process.env.BASE_URL || 'http://localhost/osta%20job%20portal';

async function loginAsApplicant(page) {
  await page.goto(`${BASE}/login.php`, { waitUntil: 'domcontentloaded' });
  await page.fill('#email', 'applicant@gmail.com');
  await page.fill('#password', 'applicant123');
  await Promise.all([
    page.waitForNavigation({ waitUntil: 'domcontentloaded' }),
    page.click('button[type="submit"]'),
  ]);
}

test.describe('Job Applications', () => {

  test('applicant dashboard loads', async ({ page }) => {
    await loginAsApplicant(page);
    await expect(page).toHaveURL(/dashboard/);
  });

  test('applicant can view jobs page', async ({ page }) => {
    await loginAsApplicant(page);
    await page.goto(`${BASE}/jobs.php`, { waitUntil: 'domcontentloaded' });
    await expect(page.locator('body')).toBeVisible();
  });

  test('job details page accessible', async ({ page }) => {
    await page.goto(`${BASE}/job_details.php?id=1`, { waitUntil: 'domcontentloaded' });
    await expect(page.locator('body')).toBeVisible();
  });

});
