<?php
require_once __DIR__ . '/includes/bootstrap.php';

if (isset($_SESSION['user_id']) && isset($_SESSION['username']) && isset($_SESSION['role'])) {
    log_security_event('logout', "User: {$_SESSION['username']}, Role: {$_SESSION['role']}");
}

secure_logout();

header('Location: index.php');
exit();
