<?php
require_once __DIR__ . '/../includes/bootstrap.php';

// Require applicant role
require_role('applicant', '../login.php');

// Only process POST requests
if ($_SERVER['REQUEST_METHOD'] !== 'POST') {
    safe_redirect('../jobs.php', '../jobs.php');
}

if (isset($_POST['csrf_token']) && !verify_csrf_token($_POST['csrf_token'])) {
    $_SESSION['error_message'] = 'Invalid security token. Please try again.';
    safe_referer_redirect('../jobs.php');
}

$job_id = isset($_POST['job_id']) ? (int) $_POST['job_id'] : 0;
$user_id = (int) $_SESSION['user_id'];

if ($job_id <= 0) {
    $_SESSION['error_message'] = 'Invalid job ID';
    safe_referer_redirect('../jobs.php');
}

$stmt = $pdo->prepare("SELECT id FROM jobs WHERE id = ? AND status = 'approved' AND deadline >= CURDATE()");
$stmt->execute([$job_id]);
$job = $stmt->fetch();

if (!$job) {
    $_SESSION['error_message'] = 'Job not found or expired';
    safe_referer_redirect('../jobs.php');
}

$stmt = $pdo->prepare("SELECT id FROM saved_jobs WHERE job_id = ? AND user_id = ?");
$stmt->execute([$job_id, $user_id]);
$is_saved = $stmt->fetch();

try {
    if ($is_saved) {
        $stmt = $pdo->prepare("DELETE FROM saved_jobs WHERE job_id = ? AND user_id = ?");
        $stmt->execute([$job_id, $user_id]);
        $_SESSION['success_message'] = 'Job removed from saved jobs';
    } else {
        $stmt = $pdo->prepare("INSERT INTO saved_jobs (job_id, user_id) VALUES (?, ?)");
        $stmt->execute([$job_id, $user_id]);
        $_SESSION['success_message'] = 'Job saved successfully';
    }
} catch (PDOException $e) {
    error_log('Error saving job: ' . $e->getMessage());
    $_SESSION['error_message'] = 'An error occurred while processing your request';
}

safe_referer_redirect('../jobs.php');
