const { test, expect } = require('@playwright/test');
const BASE = 'http://localhost/osta%20job%20portal/api';

test.describe('API Endpoint Smoke Tests', () => {

  test('GET /api returns API info', async ({ request }) => {
    const resp = await request.get(BASE + '/');
    expect(resp.status()).toBe(200);
    const body = await resp.json();
    expect(body).toHaveProperty('name', 'OSTA Job Portal API');
    expect(body).toHaveProperty('version');
  });

  test('GET /api/jobs returns job list', async ({ request }) => {
    const resp = await request.get(BASE + '/jobs');
    expect(resp.status()).toBe(200);
    const body = await resp.json();
    expect(body).toHaveProperty('data');
    expect(Array.isArray(body.data)).toBe(true);
    expect(body).toHaveProperty('pagination');
  });

  test('GET /api/jobs/1 returns a single job', async ({ request }) => {
    const resp = await request.get(BASE + '/jobs/1');
    if (resp.status() === 200) {
      const body = await resp.json();
      expect(body).toHaveProperty('id');
      expect(body).toHaveProperty('title');
    }
  });

  test('POST /api/auth/login with valid credentials returns token', async ({ request }) => {
    const resp = await request.post(BASE + '/auth/login', {
      data: {
        email: 'admin@gmail.com',
        password: 'admin123'
      }
    });
    expect(resp.status()).toBe(200);
    const body = await resp.json();
    expect(body).toHaveProperty('token');
  });

  test('GET /api/applications without auth returns 401', async ({ request }) => {
    const resp = await request.get(BASE + '/applications');
    expect(resp.status()).toBe(401);
  });

  test('GET /api/nonexistent returns 404', async ({ request }) => {
    const resp = await request.get(BASE + '/nonexistent');
    expect(resp.status()).toBe(404);
  });

});
