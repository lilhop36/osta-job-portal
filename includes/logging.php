<?php
/**
 * Logging functionality for OSTA Job Portal
 * This file extends the logging functionality and provides additional logging methods
 */

// Ensure this file is being included from a valid entry point
if (!defined('IN_OSTA')) {
    die('Direct access not permitted');
}

// Include security functions if not already included
if (!function_exists('log_security_event')) {
    require_once __DIR__ . '/security.php';
}

// Only define log_error if it doesn't exist
if (!function_exists('log_error')) {
    /**
     * Log an error message to the error log
     * 
     * @param string $message The error message to log
     * @param array|string $context Additional context data or message details
     * @return bool True if logging was successful, false otherwise
     */
    function log_error($message, $context = []) {
        // Convert array context to string for security event logging
        $details = is_array($context) ? json_encode($context) : (string)$context;
        
        // Log as a security event with ERROR type
        return log_security_event('ERROR', $message . ($details ? ' | ' . $details : ''));
    }
}

/**
 * Log an application event
 * 
 * @param int $application_id The ID of the application
 * @param int $user_id The ID of the user performing the action
 * @param string $action Description of the action performed
 * @param array $context Additional context data
 * @return bool True if logging was successful, false otherwise
 */
function log_application_event($application_id, $user_id, $action, array $context = []) {
    global $pdo;
    
    try {
        $stmt = $pdo->prepare("
            INSERT INTO application_audit_log 
            (application_id, user_id, action, context, created_at)
            VALUES (?, ?, ?, ?, NOW())
        
        ");
        
        $context_json = !empty($context) ? json_encode($context) : null;
        
        return $stmt->execute([
            (int)$application_id,
            (int)$user_id,
            $action,
            $context_json
        ]);
    } catch (PDOException $e) {
        // Fallback to file logging if database logging fails
        return write_log('ERROR', "Failed to log application event: " . $e->getMessage(), [
            'application_id' => $application_id,
            'user_id' => $user_id,
            'action' => $action,
            'context' => $context
        ]);
    }
}

/**
 * Write a log entry to the system log
 * 
 * @param string $level The log level (ERROR, INFO, DEBUG, etc.)
 * @param string $message The log message
 * @param array $context Additional context data
 * @return bool True if logging was successful, false otherwise
 */
function write_log($level, $message, array $context = []) {
    $log_dir = dirname(__DIR__) . '/logs';
    
    // Create logs directory if it doesn't exist
    if (!file_exists($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    $log_file = $log_dir . '/application_' . date('Y-m-d') . '.log';
    
    // Format the log entry
    $timestamp = date('Y-m-d H:i:s');
    $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
    $user_id = $_SESSION['user_id'] ?? 'guest';
    
    $log_entry = sprintf(
        "[%s] %s: %s | IP: %s | User: %s",
        $timestamp,
        strtoupper($level),
        $message,
        $ip,
        $user_id
    );
    
    // Add context data if provided
    if (!empty($context)) {
        $log_entry .= ' | ' . json_encode($context, JSON_UNESCAPED_SLASHES | JSON_UNESCAPED_UNICODE);
    }
    
    // Add newline
    $log_entry .= PHP_EOL;
    
    // Write to log file
    return (bool)file_put_contents($log_file, $log_entry, FILE_APPEND | LOCK_EX);
}

/**
 * Log an audit trail entry
 * 
 * @param string $action The action being performed
 * @param array $data Additional data related to the action
 * @return bool True if logging was successful, false otherwise
 */
function log_audit($action, array $data = []) {
    global $pdo;
    
    try {
        $user_id = $_SESSION['user_id'] ?? null;
        $ip = $_SERVER['REMOTE_ADDR'] ?? 'unknown';
        $user_agent = $_SERVER['HTTP_USER_AGENT'] ?? 'unknown';
        $request_uri = $_SERVER['REQUEST_URI'] ?? 'unknown';
        
        $stmt = $pdo->prepare("
            INSERT INTO audit_log 
            (user_id, action, data, ip_address, user_agent, request_uri, created_at)
            VALUES (?, ?, ?, ?, ?, ?, NOW())
        
        ");
        
        $data_json = !empty($data) ? json_encode($data) : null;
        
        return $stmt->execute([
            $user_id,
            $action,
            $data_json,
            $ip,
            $user_agent,
            $request_uri
        ]);
    } catch (PDOException $e) {
        // Fallback to file logging if database logging fails
        return write_log('ERROR', "Failed to write audit log: " . $e->getMessage(), [
            'action' => $action,
            'data' => $data
        ]);
    }
}

// End of logging.php
