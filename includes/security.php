<?php
/**
 * Enhanced Security Module for OSTA Job Portal
 * Handles session security, CSRF protection, and browser navigation control
 */

// Prevent direct access
if (!defined('SECURITY_INCLUDED')) {
    define('SECURITY_INCLUDED', true);
}

/**
 * Initialize secure session with enhanced security measures
 */
function init_secure_session() {
    // Configure secure session settings only if session is not active
    if (session_status() === PHP_SESSION_NONE) {
        ini_set('session.cookie_httponly', 1);
        ini_set('session.cookie_secure', isset($_SERVER['HTTPS']) ? 1 : 0);
        ini_set('session.use_strict_mode', 1);
        ini_set('session.cookie_samesite', 'Strict');
        
        // Set session cookie lifetime (24 hours)
        ini_set('session.cookie_lifetime', 86400);
        
        // Start session
        session_start();
    }
    
    // Regenerate session ID periodically for security
    if (!isset($_SESSION['last_regeneration'])) {
        $_SESSION['last_regeneration'] = time();
        session_regenerate_id(true);
    } elseif (time() - $_SESSION['last_regeneration'] > 1800) { // 30 minutes
        $_SESSION['last_regeneration'] = time();
        session_regenerate_id(true);
    }
    
    // Set security headers to prevent caching and back navigation
    set_security_headers();
}

/**
 * Set comprehensive security headers
 */
function set_security_headers() {
    // Prevent browser caching of sensitive pages
    header("Cache-Control: no-store, no-cache, must-revalidate, max-age=0");
    header("Cache-Control: post-check=0, pre-check=0", false);
    header("Pragma: no-cache");
    header("Expires: Sat, 26 Jul 1997 05:00:00 GMT"); // Date in the past
    
    // Security headers
    header("X-Content-Type-Options: nosniff");
    header("X-Frame-Options: DENY");
    header("X-XSS-Protection: 1; mode=block");
    header("Referrer-Policy: strict-origin-when-cross-origin");
    
    // Content Security Policy
    $csp = [
        "default-src 'self' data: blob:",
        "script-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com",
        "style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com",
        "font-src 'self' https://cdnjs.cloudflare.com data:",
        "img-src 'self' data: https: http:",
        "connect-src *",
        "frame-ancestors 'none'",
        "form-action 'self'"
    ];
    
    header("Content-Security-Policy: " . implode('; ', $csp));
}

/**
 * Generate CSRF token
 */
function generate_csrf_token() {
    if (!isset($_SESSION['csrf_token'])) {
        $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
    }
    return $_SESSION['csrf_token'];
}

/**
 * Generate CSRF token HTML input field
 */
function csrf_token_field() {
    $token = generate_csrf_token();
    return '<input type="hidden" name="csrf_token" value="' . htmlspecialchars($token) . '">';
}

/**
 * Verify CSRF token
 */
function verify_csrf_token($token) {
    if (!isset($_SESSION['csrf_token']) || $token === null || !hash_equals($_SESSION['csrf_token'], $token)) {
        return false;
    }
    return true;
}

/**
 * Enhanced session validation
 */
function validate_session() {
    // Check if session exists and is valid
    if (!isset($_SESSION['user_id']) || !isset($_SESSION['role'])) {
        return false;
    }
    
    // Check session timeout (4 hours)
    if (isset($_SESSION['last_activity']) && (time() - $_SESSION['last_activity'] > 14400)) {
        session_destroy();
        return false;
    }
    
    // Update last activity time
    $_SESSION['last_activity'] = time();
    
    // Validate session against database
    return validate_session_in_database();
}

/**
 * Validate session against database
 */
function validate_session_in_database() {
    try {
        global $pdo;
        if (!isset($pdo)) {
            require_once __DIR__ . '/../config/database.php';
        }
        
        $stmt = $pdo->prepare("SELECT id, role, status FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if (!$user || $user['status'] !== 'active' || $user['role'] !== $_SESSION['role']) {
            session_destroy();
            return false;
        }
        
        return true;
    } catch (Exception $e) {
        error_log("Session validation error: " . $e->getMessage());
        return false;
    }
}

/**
 * Secure logout with session cleanup
 */
function secure_logout() {
    // Clear all session variables
    $_SESSION = array();
    
    // Delete session cookie
    if (ini_get("session.use_cookies")) {
        $params = session_get_cookie_params();
        setcookie(session_name(), '', time() - 42000,
            $params["path"], $params["domain"],
            $params["secure"], $params["httponly"]
        );
    }
    
    // Destroy session
    session_destroy();
    
    // Set headers to prevent back navigation after logout
    set_security_headers();
}

/**
 * Check if user is authenticated and has required role
 */
function require_auth($required_role = null, $redirect_url = '/login.php') {
    init_secure_session();
    
    if (!validate_session()) {
        // Clear any remaining session data
        session_destroy();
        
        // Redirect to login with security headers
        set_security_headers();
        header("Location: " . $redirect_url);
        exit();
    }
    
    // Check role if specified
    if ($required_role && $_SESSION['role'] !== $required_role) {
        // Unauthorized access attempt
        set_security_headers();
        header("Location: /unauthorized.php");
        exit();
    }
    
    return true;
}

/**
 * Prevent back navigation after logout
 */
function prevent_back_navigation() {
    // JavaScript to prevent back navigation
    echo '<script type="text/javascript">
        // Prevent back navigation
        history.pushState(null, null, location.href);
        window.onpopstate = function () {
            history.go(1);
        };
        
        // Clear browser cache on page load
        window.onload = function() {
            if (performance.navigation.type === 2) {
                location.reload(true);
            }
        };
        
        // Prevent right-click context menu on sensitive pages
        document.addEventListener("contextmenu", function(e) {
            e.preventDefault();
        });
        
        // Prevent F12, Ctrl+Shift+I, Ctrl+U
        document.addEventListener("keydown", function(e) {
            if (e.key === "F12" || 
                (e.ctrlKey && e.shiftKey && e.key === "I") ||
                (e.ctrlKey && e.key === "u")) {
                e.preventDefault();
            }
        });
    </script>';
}

/**
 * Log security events
 */
function log_security_event($event_type, $details = '') {
    try {
        global $pdo;
        if (!isset($pdo)) {
            require_once __DIR__ . '/../config/database.php';
        }
        
        // Only log if we have a valid database connection
        if (!$pdo) {
            error_log("Cannot log security event - no database connection");
            return;
        }
        
        $user_id = isset($_SESSION['user_id']) ? $_SESSION['user_id'] : null;
        $ip_address = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'unknown';
        $user_agent = isset($_SERVER['HTTP_USER_AGENT']) ? $_SERVER['HTTP_USER_AGENT'] : 'unknown';
        
        $stmt = $pdo->prepare("INSERT INTO system_logs (user_id, event_type, details, ip_address, user_agent, created_at) VALUES (?, ?, ?, ?, ?, NOW())");
        $stmt->execute([$user_id, $event_type, $details, $ip_address, $user_agent]);
    } catch (Exception $e) {
        error_log("Failed to log security event: " . $e->getMessage());
    }
}

/**
 * Rate limiting for login attempts
 */
function check_rate_limit($identifier, $max_attempts = 5, $time_window = 900) { // 15 minutes
    $cache_key = 'rate_limit_' . md5($identifier);
    
    if (!isset($_SESSION[$cache_key])) {
        $_SESSION[$cache_key] = ['count' => 0, 'first_attempt' => time()];
    }
    
    $data = $_SESSION[$cache_key];
    
    // Reset if time window has passed
    if (time() - $data['first_attempt'] > $time_window) {
        $_SESSION[$cache_key] = ['count' => 1, 'first_attempt' => time()];
        return true;
    }
    
    // Check if limit exceeded
    if ($data['count'] >= $max_attempts) {
        return false;
    }
    
    // Increment counter
    $_SESSION[$cache_key]['count']++;
    return true;
}

/**
 * Sanitize input data
 */
function sanitize_input($data) {
    if (is_array($data)) {
        return array_map('sanitize_input', $data);
    }
    
    $data = trim($data);
    $data = stripslashes($data);
    $data = htmlspecialchars($data, ENT_QUOTES, 'UTF-8');
    return $data;
}

/**
 * Generate secure random password
 */
function generate_secure_password($length = 12) {
    $chars = 'abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ0123456789!@#$%^&*';
    return substr(str_shuffle(str_repeat($chars, ceil($length / strlen($chars)))), 0, $length);
}

/**
 * Hash password securely
 */
function hash_password($password) {
    return password_hash($password, PASSWORD_ARGON2ID, [
        'memory_cost' => 65536, // 64 MB
        'time_cost' => 4,       // 4 iterations
        'threads' => 3,         // 3 threads
    ]);
}

/**
 * Verify password
 */
function verify_password($password, $hash) {
    return password_verify($password, $hash);
}
