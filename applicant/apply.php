<?php
session_start();
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/functions.php';

// Require applicant role
require_auth('applicant');

// Get job ID from URL
$job_id = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;

// Validate job ID
if ($job_id <= 0) {
    $_SESSION['error_message'] = "Invalid job ID provided.";
    header('Location: ../applicant/dashboard.php');
    exit();
}

// Check if job exists and is active
$stmt = $pdo->prepare("SELECT j.*, d.name as department_name FROM jobs j 
                      JOIN departments d ON j.department_id = d.id 
                      WHERE j.id = ? AND j.status = 'approved' AND j.deadline >= CURDATE()");
$stmt->execute([$job_id]);
$job = $stmt->fetch();

if (!$job) {
    $_SESSION['error_message'] = "The job you're trying to apply for is no longer available.";
    header('Location: ../applicant/dashboard.php');
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if user has already applied
$stmt = $pdo->prepare("SELECT id FROM applications WHERE job_id = ? AND user_id = ?");
$stmt->execute([$job_id, $user_id]);
$has_applied = $stmt->fetch();

if ($has_applied) {
    $_SESSION['info_message'] = "You have already applied to this job.";
    header('Location: ../applicant/dashboard.php');
    exit();
}

try {
    // Create the job application
    $stmt = $pdo->prepare("INSERT INTO applications (user_id, job_id, status, created_at) VALUES (?, ?, 'pending', NOW())");
    $stmt->execute([$user_id, $job_id]);
    
    $application_id = $pdo->lastInsertId();
    
    // Log the application
    log_debug("User {$user_id} applied to job {$job_id} - Application ID: {$application_id}");
    
    $_SESSION['success_message'] = "Successfully applied to '{$job['title']}' at {$job['department_name']}!";
    
} catch (Exception $e) {
    log_debug("Job application error: " . $e->getMessage());
    $_SESSION['error_message'] = "Failed to submit application. Please try again.";
}

header('Location: ../applicant/dashboard.php');
exit();
?>
