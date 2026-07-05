<?php
$baseUrl = getenv('SMOKE_BASE_URL') ?: 'http://localhost/osta%20job%20portal';
$publicPages = [
    '/index.php',
    '/jobs.php',
    '/login.php',
    '/register.php',
    '/contact.php',
    '/health.php',
];
$protectedPages = [
    '/admin/dashboard.php',
    '/employer/dashboard.php',
    '/applicant/dashboard.php',
];
$failures = [];

function request_url(string $url): array {
    $headers = [];
    $context = stream_context_create([
        'http' => [
            'ignore_errors' => true,
            'follow_location' => 0,
            'timeout' => 10,
        ],
    ]);
    $body = @file_get_contents($url, false, $context);
    if (isset($http_response_header)) {
        $headers = $http_response_header;
    }
    $status = 0;
    if ($headers && preg_match('/HTTP\/\S+\s+(\d+)/', $headers[0], $match)) {
        $status = (int) $match[1];
    }
    return [$status, $body === false ? '' : $body, $headers];
}

foreach ($publicPages as $path) {
    [$status, $body] = request_url($baseUrl . $path);
    if ($status < 200 || $status >= 400) {
        $failures[] = "Expected 2xx/3xx for {$path}, got {$status}";
        continue;
    }
    if (preg_match('/PHP Warning|Fatal error|Notice:|SQLSTATE|Connection failed/i', $body)) {
        $failures[] = "Unexpected PHP/database error text on {$path}";
    }
}

foreach ($protectedPages as $path) {
    [$status] = request_url($baseUrl . $path);
    if ($status !== 302 && $status !== 301) {
        $failures[] = "Expected redirect for protected page {$path}, got {$status}";
    }
}

if ($failures) {
    foreach ($failures as $failure) {
        fwrite(STDERR, '[FAIL] ' . $failure . PHP_EOL);
    }
    exit(1);
}

echo 'Smoke checks passed for ' . $baseUrl . PHP_EOL;
