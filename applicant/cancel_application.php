<?php
// Enable error reporting for debugging
error_reporting(E_ALL);
ini_set('display_errors', 1);

session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security.php';

// Set JSON content type
header('Content-Type: application/json');

// Check if user is logged in and has correct role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'applicant') {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Unauthorized access.']);
    exit();
}

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    http_response_code(405);
    echo json_encode(['success' => false, 'message' => 'Invalid request method.']);
    exit();
}

// Get JSON input
$input = json_decode(file_get_contents('php://input'), true);

// Verify CSRF token
if (!isset($input['csrf_token']) || !verify_csrf_token($input['csrf_token'])) {
    http_response_code(403);
    echo json_encode(['success' => false, 'message' => 'Security token validation failed.']);
    exit();
}

try {
    // Clear the pending job application from session
    if (isset($_SESSION['pending_job_application'])) {
        unset($_SESSION['pending_job_application']);
    }
    
    // Return success response
    echo json_encode([
        'success' => true,
        'message' => 'Application cancelled successfully.'
    ]);
    
} catch (Exception $e) {
    // Log the error
    error_log("Error cancelling application: " . $e->getMessage());
    
    // Return error response
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'message' => 'An error occurred while cancelling the application.'
    ]);
}
?>
