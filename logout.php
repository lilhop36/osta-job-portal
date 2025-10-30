<?php
require_once 'config/database.php';
require_once 'includes/security.php';
require_once 'includes/auth.php';

// Initialize secure session
init_secure_session();

// Log logout event (only if we have session data)
if (isset($_SESSION['user_id']) && isset($_SESSION['username']) && isset($_SESSION['role'])) {
    log_security_event('logout', "User: {$_SESSION['username']}, Role: {$_SESSION['role']}");
}

// Perform secure logout
secure_logout();

// Redirect to home page with security headers
header('Location: index.php');
exit();
?>
