<?php
/**
 * Email Configuration
 * Configure SMTP settings for sending emails
 */

// Load from .env if available
if (file_exists(__DIR__ . '/../.env')) {
    $env = parse_ini_file(__DIR__ . '/../.env');
} else {
    $env = [];
}

// SMTP Configuration
define('SMTP_HOST', $env['SMTP_HOST'] ?? 'smtp.gmail.com');
define('SMTP_PORT', (int)($env['SMTP_PORT'] ?? 587));
define('SMTP_USERNAME', $env['SMTP_USERNAME'] ?? 'your-email@gmail.com');
define('SMTP_PASSWORD', $env['SMTP_PASSWORD'] ?? 'your-app-password');
define('SMTP_ENCRYPTION', 'tls');

// Email From Settings
define('SMTP_FROM_EMAIL', $env['FROM_EMAIL'] ?? 'noreply@ostajobportal.com');
define('SMTP_FROM_NAME', $env['FROM_NAME'] ?? 'OSTA Job Portal');

// Site Configuration
if (!defined('SITE_URL')) {
    define('SITE_URL', $env['SITE_URL'] ?? 'http://localhost/osta%20job%20portal');
}
?>
