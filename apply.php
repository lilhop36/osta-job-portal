<?php
session_start();
require_once 'config/database.php';
require_once 'includes/auth.php';
require_once 'includes/security.php';

// Get job ID from URL
$job_id = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;

// Fetch job details to ensure it's a valid job
$stmt = $pdo->prepare("SELECT * FROM jobs WHERE id = ? AND status = 'approved'");
$stmt->execute([$job_id]);
$job = $stmt->fetch();

// Redirect if job doesn't exist
if (!$job) {
    header('Location: index.php');
    exit();
}

// If user is not logged in, redirect to login page
if (!isset($_SESSION['user_id'])) {
    header('Location: login.php?redirect=apply.php&job_id=' . $job_id);
    exit();
}

// Check if user is an applicant
if ($_SESSION['role'] !== 'applicant') {
    $role = $_SESSION['role'];
    $_SESSION['error_message'] = "Only applicants can apply for jobs.";
    header("Location: {$role}/dashboard.php");
    exit();
}

$user_id = $_SESSION['user_id'];

// Check if user has a centralized application
$centralized_app_exists = false;
try {
    $app_stmt = $pdo->prepare("SELECT * FROM centralized_applications WHERE user_id = ?");
    $app_stmt->execute([$user_id]);
    $centralized_application = $app_stmt->fetch();
    $centralized_app_exists = ($centralized_application !== false);
} catch (PDOException $e) {
    // Table doesn't exist, use legacy system
    $centralized_app_exists = false;
}

// If centralized application exists and is submitted, show quick apply
if ($centralized_app_exists && $centralized_application['status'] !== 'draft') {
    // Quick apply using centralized application
    include 'includes/header_new.php';
    ?>
    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h4 class="mb-0"><i class="fas fa-briefcase me-2"></i>Apply for Job</h4>
                    </div>
                    <div class="card-body">
                        <div class="job-details mb-4">
                            <h5><?= htmlspecialchars($job['title']) ?></h5>
                            <p class="text-muted">
                                <i class="fas fa-building me-2"></i><?= htmlspecialchars($job['department']) ?><br>
                                <i class="fas fa-map-marker-alt me-2"></i><?= htmlspecialchars($job['location']) ?><br>
                                <i class="fas fa-clock me-2"></i><?= htmlspecialchars($job['employment_type']) ?>
                            </p>
                            <p><?= nl2br(htmlspecialchars($job['description'])) ?></p>
                        </div>
                        
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            You have a centralized application on file. Click below to apply using your existing profile.
                        </div>
                        
                        <form method="POST" action="applicant/apply_job.php">
                            <?= csrf_token_field() ?>
                            <input type="hidden" name="job_id" value="<?= $job_id ?>">
                            <input type="hidden" name="application_id" value="<?= $centralized_application['id'] ?>">
                            
                            <div class="mb-3">
                                <label class="form-label">Cover Letter (Optional)</label>
                                <textarea name="cover_letter" class="form-control" rows="4" 
                                          placeholder="Write a brief cover letter for this specific position..."></textarea>
                            </div>
                            
                            <div class="d-flex justify-content-between">
                                <a href="job_details.php?id=<?= $job_id ?>" class="btn btn-secondary">
                                    <i class="fas fa-arrow-left me-2"></i>Back to Job Details
                                </a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane me-2"></i>Submit Application
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php
    include 'includes/footer_new.php';
    exit();
}

// If no centralized application or it's in draft, redirect to create one
if (!$centralized_app_exists || $centralized_application['status'] === 'draft') {
    $_SESSION['apply_after_profile'] = $job_id;
    $_SESSION['info_message'] = "Please complete your centralized application profile first, then you can apply for jobs quickly.";
    header('Location: applicant/centralized_application.php');
    exit();
}
?>
