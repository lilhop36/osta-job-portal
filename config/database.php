<?php

declare(strict_types=1);

require_once __DIR__ . '/../vendor/autoload.php';

use Dotenv\Dotenv;
use App\Database\Connection;

// Load .env via vlucas/phpdotenv (replaces custom load_env)
$dotenv = Dotenv::createImmutable(__DIR__ . '/..');
$dotenv->safeLoad();

// Legacy env() helper — still used by some files
if (!function_exists('env')) {
    function env(string $key, $default = null) {
        $value = $_ENV[$key] ?? getenv($key);
        return ($value === false || $value === '') ? $default : $value;
    }
}

// Define constants for backward compatibility
define('DB_HOST', env('DB_HOST', 'localhost'));
define('DB_USER', env('DB_USER', 'root'));
define('DB_PASS', env('DB_PASS', ''));
define('DB_NAME', env('DB_NAME', 'osta_job_portal'));
define('SITE_URL', env('SITE_URL', 'http://localhost/osta%20job%20portal'));

// Create singleton PDO connection via Connection class
$pdo = Connection::getInstance()->getPdo();

// Canonical sanitize function — trim, stripslashes, htmlspecialchars
// Handles both strings and arrays (recursive)
function sanitize($data) {
    if (is_array($data)) {
        return array_map('sanitize', $data);
    }
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES | ENT_SUBSTITUTE, 'UTF-8');
    return $data;
}
