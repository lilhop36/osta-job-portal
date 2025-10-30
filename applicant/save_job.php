<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

// Require applicant role
require_role('applicant', '../login.php');

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    header('Location: ../jobs.php');
    exit();
}

// Get job ID from POST data
$job_id = isset($_POST['job_id']) ? (int)$_POST['job_id'] : 0;
$user_id = $_SESSION['user_id'];

// Validate job ID
if ($job_id <= 0) {
    $_SESSION['error_message'] = 'Invalid job ID';
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../jobs.php'));
    exit();
}

// Check if job exists and is active
$stmt = $pdo->prepare("SELECT id FROM jobs WHERE id = ? AND status = 'approved' AND deadline >= CURDATE()");
$stmt->execute([$job_id]);
$job = $stmt->fetch();

if (!$job) {
    $_SESSION['error_message'] = 'Job not found or expired';
    header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../jobs.php'));
    exit();
}

// Check if job is already saved
$stmt = $pdo->prepare("SELECT id FROM saved_jobs WHERE job_id = ? AND user_id = ?");
$stmt->execute([$job_id, $user_id]);
$is_saved = $stmt->fetch();

try {
    if ($is_saved) {
        // Unsave the job
        $stmt = $pdo->prepare("DELETE FROM saved_jobs WHERE job_id = ? AND user_id = ?");
        $stmt->execute([$job_id, $user_id]);
        $_SESSION['success_message'] = 'Job removed from saved jobs';
    } else {
        // Save the job
        $stmt = $pdo->prepare("INSERT INTO saved_jobs (job_id, user_id) VALUES (?, ?)");
        $stmt->execute([$job_id, $user_id]);
        $_SESSION['success_message'] = 'Job saved successfully';
    }
} catch (PDOException $e) {
    error_log('Error saving job: ' . $e->getMessage());
    $_SESSION['error_message'] = 'An error occurred while processing your request';
}

// Redirect back to the previous page
header('Location: ' . ($_SERVER['HTTP_REFERER'] ?? '../jobs.php'));
exit();
