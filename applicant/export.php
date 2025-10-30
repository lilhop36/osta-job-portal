<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/security.php';

// Require applicant role
require_role('applicant', SITE_URL . '/login.php');

// Set security headers
set_security_headers();

// Set headers for file download
header('Content-Type: application/json');
header('Content-Disposition: attachment; filename="osta_job_portal_export_' . date('Y-m-d') . '.json"');
header('Pragma: no-cache');
header('Expires: 0');

$user_id = $_SESSION['user_id'];
$export_data = [];

try {
    // Start transaction
    $pdo->beginTransaction();
    
    // 1. Get user's basic information
    $stmt = $pdo->prepare("SELECT id, username, email, first_name, last_name, phone, address, created_at, updated_at 
                          FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $export_data['user'] = $stmt->fetch(PDO::FETCH_ASSOC);
    
    // 2. Get user's applications
    $stmt = $pdo->prepare("SELECT j.title, j.employment_type, j.location, j.salary_range, 
                                  a.cover_letter, a.status, a.created_at as applied_at
                           FROM applications a
                           JOIN jobs j ON a.job_id = j.id
                           WHERE a.user_id = ?");
    $stmt->execute([$user_id]);
    $export_data['applications'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 3. Get saved jobs
    $stmt = $pdo->prepare("SELECT j.title, j.employment_type, j.location, j.salary_range, 
                                  j.deadline, sj.created_at as saved_at
                           FROM saved_jobs sj
                           JOIN jobs j ON sj.job_id = j.id
                           WHERE sj.user_id = ?");
    $stmt->execute([$user_id]);
    $export_data['saved_jobs'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 4. Get job alerts
    $stmt = $pdo->prepare("SELECT keywords, location, job_type, frequency, is_active, created_at
                           FROM job_alerts 
                           WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $export_data['job_alerts'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // 5. Get documents info (without file content)
    $stmt = $pdo->prepare("SELECT document_type, file_path, created_at 
                           FROM applicant_documents 
                           WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $export_data['documents'] = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Commit transaction
    $pdo->commit();
    
    // Output JSON
    echo json_encode($export_data, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    
} catch (Exception $e) {
    // Rollback transaction on error
    if ($pdo->inTransaction()) {
        $pdo->rollBack();
    }
    
    // Log the error
    error_log('Export error: ' . $e->getMessage());
    
    // Return error response
    header('HTTP/1.1 500 Internal Server Error');
    echo json_encode(['error' => 'An error occurred while generating the export. Please try again later.']);
}

exit();
