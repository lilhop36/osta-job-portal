<?php
require_once __DIR__ . '/includes/bootstrap.php';

$checks = [];
$checks['php_version'] = PHP_VERSION;
$checks['environment'] = defined('SITE_URL') ? SITE_URL : 'not configured';
$checks['database'] = 'unknown';
$checks['session'] = session_status() === PHP_SESSION_ACTIVE ? 'active' : 'inactive';
$checks['session_save_path'] = session_save_path();
$checks['session_path_writable'] = is_writable(session_save_path()) ? 'yes' : 'no';
$checks['uploads_directory'] = is_dir(__DIR__ . '/uploads') ? 'present' : 'missing';
$checks['uploads_protection'] = is_file(__DIR__ . '/uploads/.htaccess') ? 'present' : 'missing';

try {
    $pdo->query('SELECT 1');
    $checks['database'] = 'connected';
} catch (Throwable $e) {
    $checks['database'] = 'failed';
    error_log('Health check database failure: ' . $e->getMessage());
}

$isOk = $checks['database'] === 'connected'
    && $checks['session'] === 'active'
    && $checks['uploads_directory'] === 'present'
    && $checks['uploads_protection'] === 'present';

http_response_code($isOk ? 200 : 503);
header('Content-Type: application/json');
echo json_encode([
    'status' => $isOk ? 'ok' : 'needs_attention',
    'checks' => $checks,
], JSON_PRETTY_PRINT);
