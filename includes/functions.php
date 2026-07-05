<?php
/**
 * General utility functions for OSTA Job Portal
 */

// Only define log_debug if it doesn't already exist
if (!function_exists('log_debug')) {
    /**
     * Log debug information to a file with timestamp and backtrace
     * 
     * @param string $message The debug message
     * @param array $data Optional additional data to log
     * @param string $logFile Optional custom log file path
     * @return bool True on success, false on failure
     */
    function log_debug($message, array $data = [], $logFile = null) {
        // Default log directory (one level up from includes)
        $logDir = __DIR__ . '/../logs';
        
        // Create logs directory if it doesn't exist
        if (!file_exists($logDir)) {
            mkdir($logDir, 0755, true);
        }
        
        // Default log file if none specified
        if ($logFile === null) {
            $logFile = $logDir . '/debug.log';
        } else {
            // Ensure custom log file is in the logs directory
            $logFile = $logDir . '/' . basename($logFile);
        }
        
        // Get backtrace to find the calling file and line
        $backtrace = debug_backtrace(DEBUG_BACKTRACE_IGNORE_ARGS, 1);
        $caller = !empty($backtrace[0]) ? $backtrace[0] : array();
        $file = !empty($caller['file']) ? $caller['file'] : 'unknown';
        $line = !empty($caller['line']) ? $caller['line'] : 0;
        
        // Format the log message
        $timestamp = date('Y-m-d H:i:s');
        $remoteAddr = isset($_SERVER['REMOTE_ADDR']) ? $_SERVER['REMOTE_ADDR'] : 'CLI';
        $requestUri = isset($_SERVER['REQUEST_URI']) ? $_SERVER['REQUEST_URI'] : 'CLI';
        
        $logMessage = "[$timestamp] [$remoteAddr] $requestUri\n";
        $logMessage .= "Debug: $message\n";
        $logMessage .= "File: $file (Line: $line)\n";
        
        // Add data if provided
        if (!empty($data)) {
            $logMessage .= "Data: " . print_r($data, true) . "\n";
        }
        
        $logMessage .= str_repeat("-", 80) . "\n";
        
        // Write to log file
        return file_put_contents($logFile, $logMessage, FILE_APPEND) !== false;
    }
}

// Only define sanitize if it doesn't already exist
if (!function_exists('sanitize')) {
    /**
     * Sanitize input data to prevent XSS and SQL injection
     * 
     * @param string $data The input string to sanitize
     * @return string Sanitized string
     */
    function sanitize($data) {
        // Remove whitespace from the beginning and end of the string
        $data = trim($data);
        // Remove backslashes
        $data = stripslashes($data);
        // Convert special characters to HTML entities
        $data = htmlspecialchars($data, ENT_QUOTES | ENT_HTML5, 'UTF-8');
        
        return $data;
    }
}

// Only define format_date if it doesn't already exist
if (!function_exists('format_date')) {
    /**
     * Format date to a more readable format
     * 
     * @param string $date Date string to format
     * @param string $format Output format (default: F j, Y)
     * @return string Formatted date
     */
    function format_date($date, $format = 'F j, Y') {
        if (empty($date) || $date === '0000-00-00 00:00:00') {
            return 'N/A';
        }
        
        $timestamp = strtotime($date);
        return $timestamp ? date($format, $timestamp) : 'N/A';
    }
}

// Only define truncate_text if it doesn't already exist
if (!function_exists('truncate_text')) {
    /**
     * Truncate text to a specific length and add ellipsis if needed
     * 
     * @param string $text The text to truncate
     * @param int $length Maximum length of the text
     * @param bool $ellipsis Whether to add ellipsis when text is truncated
     * @return string Truncated text
     */
    function truncate_text($text, $length = 100, $ellipsis = true) {
        if (mb_strlen($text) > $length) {
            $text = mb_substr($text, 0, $length);
            if ($ellipsis) {
                $text .= '...';
            }
        }
        return $text;
    }
}

// Only define generate_random_string if it doesn't already exist
if (!function_exists('generate_random_string')) {
    /**
     * Generate a random string of specified length
     * 
     * @param int $length Length of the random string
     * @return string Random string
     */
    function generate_random_string($length = 10) {
        $characters = '0123456789abcdefghijklmnopqrstuvwxyzABCDEFGHIJKLMNOPQRSTUVWXYZ';
        $randomString = '';
        
        for ($i = 0; $i < $length; $i++) {
            $randomString .= $characters[rand(0, strlen($characters) - 1)];
        }
        
        return $randomString;
    }
}

// Only define is_valid_email if it doesn't already exist
if (!function_exists('is_valid_email')) {
    /**
     * Check if a string is a valid email address
     * 
     * @param string $email Email address to validate
     * @return bool True if valid, false otherwise
     */
    function is_valid_email($email) {
        return filter_var($email, FILTER_VALIDATE_EMAIL) !== false;
    }
}

// Only define is_valid_url if it doesn't already exist
if (!function_exists('is_valid_url')) {
    /**
     * Check if a string is a valid URL
     * 
     * @param string $url URL to validate
     * @return bool True if valid, false otherwise
     */
    function is_valid_url($url) {
        return filter_var($url, FILTER_VALIDATE_URL) !== false;
    }
}

// Only define slugify if it doesn't already exist
if (!function_exists('time_elapsed_string')) {
    /**
     * Convert a timestamp to a human-readable time difference (e.g., "2 hours ago")
     * 
     * @param string $datetime Timestamp or date string
     * @param bool $full Whether to include all time components
     * @return string Human-readable time difference
     */
    function time_elapsed_string($datetime, $full = false) {
        $now = new DateTime;
        $ago = new DateTime($datetime);
        $diff = $now->diff($ago);

        $diff->w = floor($diff->d / 7);
        $diff->d -= $diff->w * 7;

        $string = array(
            'y' => 'year',
            'm' => 'month',
            'w' => 'week',
            'd' => 'day',
            'h' => 'hour',
            'i' => 'minute',
            's' => 'second',
        );
        
        foreach ($string as $k => &$v) {
            if ($diff->$k) {
                $v = $diff->$k . ' ' . $v . ($diff->$k > 1 ? 's' : '');
            } else {
                unset($string[$k]);
            }
        }

        if (!$full) $string = array_slice($string, 0, 1);
        return $string ? implode(', ', $string) . ' ago' : 'just now';
    }
}

if (!function_exists('slugify')) {
    /**
     * Generate a slug from a string
     * 
     * @param string $string String to convert to slug
     * @return string Generated slug
     */
    function slugify($string) {
        // Replace non-alphanumeric characters with dashes
        $string = preg_replace('/[^a-z0-9]+/i', '-', $string);
        // Convert to lowercase
        $string = strtolower($string);
        // Remove leading/trailing dashes
        $string = trim($string, '-');
        
        return $string;
    }
}
