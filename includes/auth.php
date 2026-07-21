<?php
// Include enhanced security module
require_once __DIR__ . '/security.php';
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/helpers.php';

// Initialize secure session
init_secure_session();

/**
 * Check if user is logged in
 * @return bool True if user is logged in, false otherwise
 */
function is_logged_in() {
    return isset($_SESSION['user_id']) && isset($_SESSION['role']);
}

/**
 * Check if user has the required role
 * @param string $role Required role
 * @return bool True if user has the required role, false otherwise
 */
function has_role($role) {
    return is_logged_in() && $_SESSION['role'] === $role;
}

/**
 * Require user to be logged in with specific role
 * @param string $role Required role
 * @param string $redirect_url URL to redirect to if check fails
 */
function require_role($role, $redirect_url = '../login.php') {
    // Use enhanced authentication from security module
    require_auth($role, $redirect_url);
}

/**
 * Require user to be logged in
 * @param string $redirect_url URL to redirect to if not logged in
 */
function require_login($redirect_url = '../login.php') {
    // Use enhanced authentication from security module
    require_auth(null, $redirect_url);
}

/**
 * Redirect to role-specific dashboard
 */
function redirect_to_dashboard() {
    if (!is_logged_in()) {
        return;
    }
    
    $base_path = app_base_path();
    $role = $_SESSION['role'];
    
    switch ($role) {
        case ROLE_ADMIN:
            header('Location: ' . $base_path . '/admin/dashboard.php');
            break;
        case ROLE_EMPLOYER:
            header('Location: ' . $base_path . '/employer/dashboard.php');
            break;
        case ROLE_APPLICANT:
            header('Location: ' . $base_path . '/applicant/dashboard.php');
            break;
        default:
            header('Location: ' . $base_path . '/index.php');
    }
    exit();
}
?>

