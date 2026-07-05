<?php

require_once __DIR__ . '/env.php';

define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));
define('DB_NAME', env('DB_NAME', 'osta_job_portal'));

// Define Site URL
define('SITE_URL', env('SITE_URL', 'http://localhost/osta%20job%20portal'));

// Show errors only in development; hide them in production
$app_env = env('APP_ENV', 'development');
if ($app_env === 'production') {
    ini_set('display_errors', 0);
    error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);
} else {
    ini_set('display_errors', 1);
    error_reporting(E_ALL);
}
ini_set('log_errors', 1);

try {
    $pdo = new PDO("mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4", DB_USER, DB_PASS, [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
    ]);
} catch (PDOException $e) {
    error_log("Database connection failed: " . $e->getMessage());
    if ($app_env === 'production') {
        die("Database connection failed. Please contact the administrator.");
    }
    die("Connection failed: " . $e->getMessage());
}

// Function to sanitize input
function sanitize($data) {
    return htmlspecialchars(strip_tags(trim($data)));
}

// Password functions moved to includes/security.php for better security implementation
