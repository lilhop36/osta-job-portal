<?php
declare(strict_types=1);

use App\Router;

require_once dirname(__DIR__) . '/vendor/autoload.php';
require_once dirname(__DIR__) . '/config/database.php';
require_once dirname(__DIR__) . '/includes/helpers.php';
require_once dirname(__DIR__) . '/includes/security.php';
require_once dirname(__DIR__) . '/includes/auth.php';

init_secure_session();

// ============================================================
// API route — serve public/api.php directly
// ============================================================
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = rtrim($uri, '/') ?: '/';

if (strpos($uri, '/osta%20job%20portal/api') === 0 || strpos($uri, '/osta job portal/api') === 0 ||
    strpos($uri, '/osta%20job%20portal/public/api') === 0 || strpos($uri, '/osta job portal/public/api') === 0) {
    require __DIR__ . '/api.php';
    exit;
}

$router = new Router();

// ============================================================
// Public routes
// ============================================================
$router->get('/', [App\Controllers\AboutController::class, 'index']);
$router->get('/about', [App\Controllers\AboutController::class, 'index']);

// ============================================================
// Legacy fallback — serve existing PHP files directly
// ============================================================
$uri = parse_url($_SERVER['REQUEST_URI'], PHP_URL_PATH);
$uri = rtrim($uri, '/') ?: '/';

// Map to filesystem
$legacyMap = [
    '/login'             => __DIR__ . '/../login.php',
    '/register'          => __DIR__ . '/../register.php',
    '/verify-email'      => __DIR__ . '/../verify_email.php',
    '/forgot-password'   => __DIR__ . '/../forgot_password.php',
    '/reset-password'    => __DIR__ . '/../reset_password.php',
    '/logout'            => __DIR__ . '/../logout.php',
    '/contact'           => __DIR__ . '/../contact.php',
    '/jobs'              => __DIR__ . '/../jobs.php',
    '/job-details'       => __DIR__ . '/../job_details.php',
    '/notifications'     => __DIR__ . '/../notifications.php',
    '/health'            => __DIR__ . '/../health.php',
];

if (isset($legacyMap[$uri]) && is_file($legacyMap[$uri])) {
    require $legacyMap[$uri];
    exit;
}

// Try direct file path
$filePath = __DIR__ . '/../' . ltrim($uri, '/');
if (is_file($filePath)) {
    require $filePath;
    exit;
}
if (is_file($filePath . '.php')) {
    require $filePath . '.php';
    exit;
}

// 404
http_response_code(404);
if (is_file(__DIR__ . '/../errors/404.php')) {
    require __DIR__ . '/../errors/404.php';
} else {
    echo '404 Not Found';
}
