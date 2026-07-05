<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/security.php';
require_once '../includes/application_functions.php';

// Require authentication and applicant role
require_auth('applicant');

$user_id = $_SESSION['user_id'];
$error_message = '';
$success_message = '';

// Handle form submission
if ($_POST && isset($_POST['csrf_token']) && verify_csrf_token($_POST['csrf_token'])) {
    try {
        $pdo->beginTransaction();
        
        // Get form data
        $job_id = isset($_POST['job_id']) ? (int)$_POST['job_id'] : 0;
        $application_id = isset($_POST['application_id']) ? (int)$_POST['application_id'] : 0;
        $cover_letter = isset($_POST['cover_letter']) ? trim($_POST['cover_letter']) : '';
        
        // Validate inputs
        if (empty($job_id)) {
            throw new Exception("Invalid job ID.");
        }
        
        if (empty($application_id)) {
            throw new Exception("Invalid application ID.");
        }
        
        // Check if job exists and is approved
        $stmt = $pdo->prepare("SELECT * FROM jobs WHERE id = ? AND status = 'approved'");
        $stmt->execute([$job_id]);
        $job = $stmt->fetch();
        
        if (!$job) {
            throw new Exception("The job you are applying for is no longer available.");
        }
        
        // Check if centralized application exists and belongs to user
        $stmt = $pdo->prepare("SELECT * FROM centralized_applications WHERE id = ? AND user_id = ?");
        $stmt->execute([$application_id, $user_id]);
        $centralized_app = $stmt->fetch();
        
        if (!$centralized_app) {
            throw new Exception("Invalid application profile.");
        }
        
        // Check if user has already applied for this job
        $stmt = $pdo->prepare("SELECT id FROM applications WHERE job_id = ? AND user_id = ?");
        $stmt->execute([$job_id, $user_id]);
        $existing_application = $stmt->fetch();
        
        if ($existing_application) {
            throw new Exception("You have already applied for this job.");
        }
        
        // Insert job application using centralized application data
        $stmt = $pdo->prepare("INSERT INTO applications (job_id, user_id, cover_letter, status, created_at) 
                              VALUES (?, ?, ?, 'pending', NOW())");
        $stmt->execute([$job_id, $user_id, $cover_letter]);
        
        $new_application_id = $pdo->lastInsertId();
        
        // Update job application count
        $stmt = $pdo->prepare("UPDATE jobs SET application_count = IFNULL(application_count, 0) + 1 WHERE id = ?");
        $stmt->execute([$job_id]);
        
        // Log audit action
        log_audit_action($user_id, 'job_application_submitted', 'applications', $new_application_id, [
            'job_id' => $job_id,
            'job_title' => $job['title']
        ]);
        
        // Queue notification
        queue_notification($user_id, 'JOB_APPLICATION_SUBMITTED', [
            'job_title' => $job['title'],
            'application_id' => $new_application_id
        ]);
        
        $pdo->commit();
        
        $success_message = "Application submitted successfully! You can track your application status in your dashboard.";
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_message = $e->getMessage();
    }
}

// If there's a success message, redirect to dashboard
if ($success_message) {
    $_SESSION['success_message'] = $success_message;
    header('Location: dashboard.php');
    exit();
}
?>
