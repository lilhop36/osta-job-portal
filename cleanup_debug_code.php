<?php
/**
 * Debug Code Cleanup Script for OSTA Job Portal
 * Removes debug statements and improves security
 */

echo "<h2>🧹 Cleaning Up Debug Code and Security Issues</h2>\n";

$files_to_clean = [
    'employer/edit_job.php' => [
        'error_log("Attempting to edit job ID: " . $job_id . " for user ID: " . $_SESSION[\'user_id\']);',
        'error_log("Job found: " . print_r($job, true));'
    ],
    'admin/manage_jobs.php' => [
        'error_log("Jobs Query: " . $query);',
        'error_log("Query Params: " . print_r($params, true));',
        'error_log("Number of jobs found: " . count($jobs));',
        'error_log("First job: " . print_r($jobs[0], true));'
    ],
    'applicant/centralized_application.php' => [
        'error_log("POST csrf_token: " . ($_POST[\'csrf_token\'] ?? \'NOT SET\'));',
        'error_log("SESSION csrf_token: " . ($_SESSION[\'csrf_token\'] ?? \'NOT SET\'));',
        '$error_message = \'Invalid security token. Please try again. Debug: POST=\' . ($_POST[\'csrf_token\'] ?? \'missing\') . \', SESSION=\' . ($_SESSION[\'csrf_token\'] ?? \'missing\');'
    ],
    'applicant/cancel_application.php' => [
        'error_reporting(E_ALL);',
        'ini_set(\'display_errors\', 1);'
    ]
];

$cleaned_files = 0;
$total_removals = 0;

foreach ($files_to_clean as $file => $debug_lines) {
    if (file_exists($file)) {
        echo "<h3>Cleaning: $file</h3>\n";
        $content = file_get_contents($file);
        $original_content = $content;
        
        foreach ($debug_lines as $debug_line) {
            if (strpos($content, $debug_line) !== false) {
                $content = str_replace($debug_line, '// Debug code removed', $content);
                echo "<p style='color: green;'>✓ Removed debug statement</p>\n";
                $total_removals++;
            }
        }
        
        if ($content !== $original_content) {
            file_put_contents($file, $content);
            $cleaned_files++;
            echo "<p style='color: green;'>✅ File cleaned successfully</p>\n";
        } else {
            echo "<p style='color: orange;'>⚠ No debug code found to remove</p>\n";
        }
    } else {
        echo "<p style='color: red;'>✗ File not found: $file</p>\n";
    }
}

echo "<h3>Summary</h3>\n";
echo "<p>Files cleaned: $cleaned_files</p>\n";
echo "<p>Debug statements removed: $total_removals</p>\n";

// Additional security improvements
echo "<h3>🔒 Security Improvements</h3>\n";

// Create a secure error handler
$error_handler_content = '<?php
/**
 * Secure Error Handler for OSTA Job Portal
 */

// Set error reporting based on environment
if (defined(\'ENVIRONMENT\') && ENVIRONMENT === \'production\') {
    error_reporting(0);
    ini_set(\'display_errors\', 0);
    ini_set(\'log_errors\', 1);
    ini_set(\'error_log\', __DIR__ . \'/logs/error.log\');
} else {
    error_reporting(E_ALL);
    ini_set(\'display_errors\', 1);
}

/**
 * Custom error handler that logs errors securely
 */
function secure_error_handler($errno, $errstr, $errfile, $errline) {
    $error_types = [
        E_ERROR => \'Fatal Error\',
        E_WARNING => \'Warning\',
        E_PARSE => \'Parse Error\',
        E_NOTICE => \'Notice\',
        E_CORE_ERROR => \'Core Error\',
        E_CORE_WARNING => \'Core Warning\',
        E_COMPILE_ERROR => \'Compile Error\',
        E_COMPILE_WARNING => \'Compile Warning\',
        E_USER_ERROR => \'User Error\',
        E_USER_WARNING => \'User Warning\',
        E_USER_NOTICE => \'User Notice\',
        E_STRICT => \'Strict Notice\',
        E_RECOVERABLE_ERROR => \'Recoverable Error\',
        E_DEPRECATED => \'Deprecated\',
        E_USER_DEPRECATED => \'User Deprecated\'
    ];
    
    $error_type = $error_types[$errno] ?? \'Unknown Error\';
    $timestamp = date(\'Y-m-d H:i:s\');
    $user_ip = $_SERVER[\'REMOTE_ADDR\'] ?? \'Unknown\';
    
    // Log error securely (without exposing sensitive data)
    $log_message = "[$timestamp] [$user_ip] $error_type: $errstr in " . basename($errfile) . " on line $errline\n";
    
    // Ensure logs directory exists
    $log_dir = __DIR__ . \'/logs\';
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    error_log($log_message, 3, $log_dir . \'/application.log\');
    
    // Don\'t execute PHP internal error handler
    return true;
}

// Set custom error handler
set_error_handler(\'secure_error_handler\');

/**
 * Log security events
 */
function log_security_event($event_type, $details = \'\') {
    $timestamp = date(\'Y-m-d H:i:s\');
    $user_ip = $_SERVER[\'REMOTE_ADDR\'] ?? \'Unknown\';
    $user_id = $_SESSION[\'user_id\'] ?? \'Anonymous\';
    
    $log_message = "[$timestamp] [SECURITY] [$user_ip] User: $user_id, Event: $event_type";
    if ($details) {
        $log_message .= ", Details: $details";
    }
    $log_message .= "\n";
    
    $log_dir = __DIR__ . \'/logs\';
    if (!is_dir($log_dir)) {
        mkdir($log_dir, 0755, true);
    }
    
    error_log($log_message, 3, $log_dir . \'/security.log\');
}
?>';

file_put_contents('includes/error_handler.php', $error_handler_content);
echo "<p style='color: green;'>✓ Created secure error handler</p>\n";

// Create environment configuration
$env_config = '<?php
/**
 * Environment Configuration
 */

// Define environment (change to \'production\' for live site)
define(\'ENVIRONMENT\', \'development\');

// Load appropriate configuration
if (ENVIRONMENT === \'production\') {
    // Production settings
    define(\'DEBUG_MODE\', false);
    define(\'SHOW_ERRORS\', false);
    define(\'LOG_ERRORS\', true);
} else {
    // Development settings
    define(\'DEBUG_MODE\', true);
    define(\'SHOW_ERRORS\', true);
    define(\'LOG_ERRORS\', true);
}
?>';

file_put_contents('config/environment.php', $env_config);
echo "<p style='color: green;'>✓ Created environment configuration</p>\n";

echo "<h3>✅ Cleanup Complete!</h3>\n";
echo "<p>Next steps:</p>\n";
echo "<ol>\n";
echo "<li>Include the error handler in your main files</li>\n";
echo "<li>Set ENVIRONMENT to 'production' when deploying</li>\n";
echo "<li>Test all functionality after cleanup</li>\n";
echo "<li>Monitor the new log files for any issues</li>\n";
echo "</ol>\n";
?>