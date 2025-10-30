<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/security.php';

// Require employer role
require_role('employer', '../login.php');

// Set security headers
set_security_headers();

// Check if this is a POST request
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: manage_applications.php');
    exit();
}

// Verify CSRF token
if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
    $_SESSION['error_message'] = 'Invalid security token. Please try again.';
    header('Location: manage_applications.php');
    exit();
}

// Get form data
$application_id = isset($_POST['application_id']) ? (int)$_POST['application_id'] : 0;
$new_status = isset($_POST['status']) ? sanitize($_POST['status']) : '';
$feedback = isset($_POST['feedback']) ? sanitize($_POST['feedback']) : '';

// Validate inputs
$allowed_statuses = ['pending', 'shortlisted', 'rejected', 'accepted'];
if (!in_array($new_status, $allowed_statuses)) {
    $_SESSION['error_message'] = 'Invalid status provided.';
    header('Location: manage_applications.php');
    exit();
}

if ($application_id <= 0) {
    $_SESSION['error_message'] = 'Invalid application ID.';
    header('Location: manage_applications.php');
    exit();
}

try {
    // Get employer's department
    $employer_id = $_SESSION['user_id'];
    $emp_stmt = $pdo->prepare("SELECT department_id FROM users WHERE id = ? AND role = 'employer'");
    $emp_stmt->execute([$employer_id]);
    $employer_dept = $emp_stmt->fetch();
    
    if (!$employer_dept) {
        $_SESSION['error_message'] = 'Your account is not properly configured. Please contact the administrator.';
        header('Location: manage_applications.php');
        exit();
    }
    
    // Verify the application belongs to employer's department
    $verify_stmt = $pdo->prepare("
        SELECT a.id, a.job_id, a.user_id, a.status as current_status, j.department_id, u.full_name, j.title
        FROM applications a
        JOIN jobs j ON a.job_id = j.id
        JOIN users u ON a.user_id = u.id
        WHERE a.id = ? AND j.department_id = ?
    ");
    $verify_stmt->execute([$application_id, $employer_dept['department_id']]);
    $application = $verify_stmt->fetch();
    
    if (!$application) {
        $_SESSION['error_message'] = 'Application not found or you do not have permission to modify it.';
        header('Location: manage_applications.php');
        exit();
    }
    
    // Update the application status
    $update_stmt = $pdo->prepare("
        UPDATE applications 
        SET status = ?, feedback = ?, updated_at = NOW(), updated_by = ?
        WHERE id = ?
    ");
    $update_stmt->execute([$new_status, $feedback, $employer_id, $application_id]);
    
    // Log the status change
    $log_stmt = $pdo->prepare("
        INSERT INTO application_history (application_id, old_status, new_status, changed_by, feedback, created_at)
        VALUES (?, ?, ?, ?, ?, NOW())
    ");
    $log_stmt->execute([
        $application_id, 
        $application['current_status'], 
        $new_status, 
        $employer_id, 
        $feedback
    ]);
    
    // Set success message
    $status_labels = [
        'pending' => 'Pending Review',
        'shortlisted' => 'Shortlisted',
        'rejected' => 'Rejected',
        'accepted' => 'Accepted'
    ];
    
    $_SESSION['success_message'] = "Application status updated to '{$status_labels[$new_status]}' for {$application['full_name']}.";
    
    // Redirect back to the application view or applicants list
    $redirect_url = isset($_POST['redirect']) ? $_POST['redirect'] : "view_applicants.php?job_id={$application['job_id']}";
    header("Location: $redirect_url");
    exit();
    
} catch (Exception $e) {
    error_log("Error updating application status: " . $e->getMessage());
    $_SESSION['error_message'] = 'An error occurred while updating the application status. Please try again.';
    header('Location: manage_applications.php');
    exit();
}
?>