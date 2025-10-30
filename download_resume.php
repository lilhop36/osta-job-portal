<?php
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/security.php';

// Require authentication
require_auth();

// Get application ID
$application_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

if ($application_id <= 0) {
    http_response_code(400);
    die('Invalid application ID');
}

try {
    // Get application details
    $stmt = $pdo->prepare("
        SELECT a.resume_path, a.user_id, u.full_name, j.title, j.department_id
        FROM applications a
        JOIN users u ON a.user_id = u.id
        JOIN jobs j ON a.job_id = j.id
        WHERE a.id = ?
    ");
    $stmt->execute([$application_id]);
    $application = $stmt->fetch();
    
    if (!$application) {
        http_response_code(404);
        die('Application not found');
    }
    
    // Check permissions
    $user_role = $_SESSION['role'];
    $user_id = $_SESSION['user_id'];
    
    if ($user_role === 'applicant') {
        // Applicants can only download their own resumes
        if ($application['user_id'] != $user_id) {
            http_response_code(403);
            die('Access denied');
        }
    } elseif ($user_role === 'employer') {
        // Employers can download resumes for applications in their department
        $emp_stmt = $pdo->prepare("SELECT department_id FROM users WHERE id = ? AND role = 'employer'");
        $emp_stmt->execute([$user_id]);
        $employer_dept = $emp_stmt->fetch();
        
        if (!$employer_dept || $employer_dept['department_id'] != $application['department_id']) {
            http_response_code(403);
            die('Access denied');
        }
    } elseif ($user_role !== 'admin') {
        http_response_code(403);
        die('Access denied');
    }
    
    // Check if resume file exists
    if (empty($application['resume_path'])) {
        http_response_code(404);
        die('No resume uploaded for this application');
    }
    
    // Construct file path
    $resume_path = $application['resume_path'];
    
    // Handle different path formats
    if (strpos($resume_path, 'uploads/resumes/') === 0) {
        $file_path = __DIR__ . '/' . $resume_path;
    } elseif (strpos($resume_path, '../uploads/resumes/') === 0) {
        $file_path = __DIR__ . '/' . substr($resume_path, 3);
    } else {
        $file_path = __DIR__ . '/uploads/resumes/' . $resume_path;
    }
    
    if (!file_exists($file_path)) {
        http_response_code(404);
        die('Resume file not found on server');
    }
    
    // Get file info
    $file_size = filesize($file_path);
    $file_name = basename($resume_path);
    $file_extension = strtolower(pathinfo($file_name, PATHINFO_EXTENSION));
    
    // Set appropriate content type
    $content_types = [
        'pdf' => 'application/pdf',
        'doc' => 'application/msword',
        'docx' => 'application/vnd.openxmlformats-officedocument.wordprocessingml.document',
        'txt' => 'text/plain'
    ];
    
    $content_type = $content_types[$file_extension] ?? 'application/octet-stream';
    
    // Generate a clean filename for download
    $clean_name = sanitize($application['full_name']) . '_Resume_' . sanitize($application['title']);
    $clean_name = preg_replace('/[^a-zA-Z0-9_-]/', '_', $clean_name);
    $download_name = $clean_name . '.' . $file_extension;
    
    // Set headers for file download
    header('Content-Type: ' . $content_type);
    header('Content-Disposition: attachment; filename="' . $download_name . '"');
    header('Content-Length: ' . $file_size);
    header('Cache-Control: private');
    header('Pragma: private');
    header('Expires: 0');
    
    // Output file
    readfile($file_path);
    exit();
    
} catch (Exception $e) {
    error_log("Resume download error: " . $e->getMessage());
    http_response_code(500);
    die('Error downloading resume');
}

function sanitize($string) {
    return htmlspecialchars(strip_tags(trim($string)));
}
?>