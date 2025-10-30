<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/security.php';
require_once '../includes/auth.php';
require_once '../includes/application_functions.php';

// Require authentication and applicant role
require_auth('applicant');

$user_id = $_SESSION['user_id'];

if (!isset($_GET['id']) || !is_numeric($_GET['id'])) {
    http_response_code(404);
    die('Document not found');
}

$document_id = $_GET['id'];

try {
    // Get document details and verify ownership
    $stmt = $pdo->prepare("SELECT ad.*, ca.user_id 
                          FROM application_documents ad 
                          JOIN centralized_applications ca ON ad.application_id = ca.id 
                          WHERE ad.id = ? AND ca.user_id = ?");
    $stmt->execute([$document_id, $user_id]);
    $document = $stmt->fetch();
    
    if (!$document) {
        http_response_code(404);
        die('Document not found or access denied');
    }
    
    // Check if file exists
    if (!file_exists($document['file_path'])) {
        http_response_code(404);
        die('File not found on server');
    }
    
    // Log the download
    log_audit_action($user_id, 'download', 'application_documents', $document_id, 
                   "Downloaded document: {$document['original_filename']}");
    
    // Set headers for file download
    header('Content-Type: ' . $document['mime_type']);
    header('Content-Disposition: attachment; filename="' . $document['original_filename'] . '"');
    header('Content-Length: ' . $document['file_size']);
    header('Cache-Control: no-cache, must-revalidate');
    header('Pragma: no-cache');
    
    // Output file content
    readfile($document['file_path']);
    exit;
    
} catch (Exception $e) {
    log_debug("Document download error: " . $e->getMessage());
    http_response_code(500);
    die('Error downloading document');
}
?>
