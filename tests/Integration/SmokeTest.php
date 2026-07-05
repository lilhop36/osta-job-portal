<?php

use PHPUnit\Framework\TestCase;

class SmokeTest extends TestCase
{
    private string $baseUrl;

    protected function setUp(): void
    {
        $this->baseUrl = getenv('BASE_URL') ?: 'http://localhost/osta%20job%20portal';
    }

    /** @dataProvider publicPageProvider */
    public function testPublicPageReturns200(string $path): void
    {
        $url = $this->baseUrl . '/' . ltrim($path, '/');
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_NOBODY => true,
        ]);
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $this->assertEquals(200, $httpCode, "{$url} did not return 200");
    }

    public function publicPageProvider(): array
    {
        return [
            'Home' => ['/index.php'],
            'Jobs' => ['/jobs.php'],
            'About' => ['/about.php'],
            'Contact' => ['/contact.php'],
            'Login' => ['/login.php'],
            'Register' => ['/register.php'],
        ];
    }

    /** @dataProvider adminPageProvider */
    public function testAdminPageReturns200(string $path): void
    {
        // Login first to get session cookie
        $loginUrl = $this->baseUrl . '/login.php';
        $ch = curl_init($loginUrl);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_POST => true,
            CURLOPT_POSTFIELDS => http_build_query([
                'email' => 'admin@gmail.com',
                'password' => 'admin123',
            ]),
            CURLOPT_HEADER => true,
        ]);
        $response = curl_exec($ch);
        preg_match('/^Set-Cookie: ([^=]+=[^;]+)/mi', $response, $matches);
        $cookie = $matches[1] ?? '';
        curl_close($ch);

        $url = $this->baseUrl . '/' . ltrim($path, '/');
        $ch = curl_init($url);
        curl_setopt_array($ch, [
            CURLOPT_RETURNTRANSFER => true,
            CURLOPT_TIMEOUT => 10,
            CURLOPT_FOLLOWLOCATION => true,
            CURLOPT_NOBODY => true,
            CURLOPT_COOKIE => $cookie,
        ]);
        curl_exec($ch);
        $httpCode = curl_getinfo($ch, CURLINFO_HTTP_CODE);
        curl_close($ch);

        $this->assertEquals(200, $httpCode, "{$url} did not return 200");
    }

    public function adminPageProvider(): array
    {
        return [
            'Dashboard' => ['/admin/dashboard.php'],
            'Manage Users' => ['/admin/manage_users.php'],
            'Manage Jobs' => ['/admin/manage_jobs.php'],
            'System Health' => ['/admin/system_health.php'],
            'Audit Log' => ['/admin/audit_log.php'],
        ];
    }
}
