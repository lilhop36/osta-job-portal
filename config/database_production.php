<?php
/**
 * Production Database Configuration for OSTA Job Portal
 * 
 * INSTRUCTIONS FOR DEPLOYMENT:
 * 1. Rename this file to 'database.php' after uploading to your hosting server
 * 2. Update the database credentials below with your hosting provider's details
 * 3. Update SITE_URL with your actual domain name
 */

// Database Configuration - UPDATE THESE VALUES
define('DB_HOST', 'localhost');           // Usually 'localhost' for most hosts
define('DB_NAME', 'your_database_name');  // Your database name from hosting provider
define('DB_USER', 'your_username');       // Your database username
define('DB_PASS', 'your_password');       // Your database password

// Site Configuration - UPDATE THIS URL
define('SITE_URL', 'https://yourdomain.com'); // Your actual domain (no trailing slash)

// Security Configuration
define('CSRF_TOKEN_EXPIRE', 3600); // 1 hour
define('SESSION_LIFETIME', 7200);   // 2 hours

// Application Configuration
define('UPLOAD_MAX_SIZE', 5242880); // 5MB in bytes
define('ALLOWED_FILE_TYPES', ['pdf', 'doc', 'docx']);

// Email Configuration (Optional - for notifications)
define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-app-password');
define('FROM_EMAIL', 'noreply@yourdomain.com');
define('FROM_NAME', 'OSTA Job Portal');

// Error Reporting (Set to false in production)
ini_set('display_errors', 0);
ini_set('log_errors', 1);
error_reporting(E_ALL & ~E_NOTICE & ~E_WARNING);

try {
    // Create PDO connection
    $dsn = "mysql:host=" . DB_HOST . ";dbname=" . DB_NAME . ";charset=utf8mb4";
    $options = [
        PDO::ATTR_ERRMODE            => PDO::ERRMODE_EXCEPTION,
        PDO::ATTR_DEFAULT_FETCH_MODE => PDO::FETCH_ASSOC,
        PDO::ATTR_EMULATE_PREPARES   => false,
        PDO::MYSQL_ATTR_INIT_COMMAND => "SET NAMES utf8mb4"
    ];
    
    $pdo = new PDO($dsn, DB_USER, DB_PASS, $options);
    
} catch (PDOException $e) {
    // Log error and show user-friendly message
    error_log("Database connection failed: " . $e->getMessage());
    die("Database connection failed. Please contact the administrator.");
}

// Define application constant for security
define('IN_OSTA', true);
?>
