<?php
/**
 * Email Configuration
 * Configure SMTP settings for sending emails
 */

// SMTP Configuration
define('SMTP_HOST', 'smtp.gmail.com');  // Change to your SMTP server
define('SMTP_PORT', 587);               // SMTP port (587 for TLS, 465 for SSL)
define('SMTP_USERNAME', 'your-email@gmail.com');  // Your email address
define('SMTP_PASSWORD', 'your-app-password');     // Your email password or app password
define('SMTP_ENCRYPTION', 'tls');       // 'tls' or 'ssl'

// Email From Settings
define('SMTP_FROM_EMAIL', 'noreply@ostajobportal.com');
define('SMTP_FROM_NAME', 'OSTA Job Portal');

// Site Configuration
if (!defined('SITE_URL')) {
    define('SITE_URL', 'http://localhost/osta_job_portal/');
}

/**
 * Email Configuration Instructions:
 * 
 * For Gmail:
 * 1. Enable 2-factor authentication on your Gmail account
 * 2. Generate an "App Password" for this application
 * 3. Use the app password instead of your regular password
 * 4. Set SMTP_HOST to 'smtp.gmail.com'
 * 5. Set SMTP_PORT to 587
 * 6. Set SMTP_ENCRYPTION to 'tls'
 * 
 * For other email providers:
 * - Update SMTP_HOST, SMTP_PORT, and SMTP_ENCRYPTION accordingly
 * - Consult your email provider's documentation for SMTP settings
 */
?>
