const { defineConfig, devices } = require('@playwright/test');

module.exports = defineConfig({
  testDir: './tests/e2e',
  fullyParallel: false,
  forbidOnly: !!process.env.CI,
  retries: process.env.CI ? 1 : 0,
  workers: 1,
  timeout: 30000,
  expect: { timeout: 10000 },
  reporter: [
    ['list'],
    ['html', { outputFolder: 'tests/reports/playwright' }]
  ],
  use: {
    baseURL: process.env.BASE_URL || 'http://localhost/osta%20job%20portal',
    trace: 'on-first-retry',
    screenshot: 'only-on-failure',
    video: 'retain-on-failure',
    navigationTimeout: 15000,
    actionTimeout: 10000,
  },
  projects: [
    {
      name: 'chromium',
      use: { ...devices['Desktop Chrome'] },
    },
  ],
});
