<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

// Start session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Get job ID from URL
$job_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Fetch job details
$stmt = $pdo->prepare("SELECT j.*, d.name as department_name 
                       FROM jobs j 
                       LEFT JOIN departments d ON j.department_id = d.id 
                       WHERE j.id = ? AND j.status = 'approved'");
$stmt->execute([$job_id]);
$job = $stmt->fetch();

// Redirect if job doesn't exist
if (!$job) {
    header('Location: index.php');
    exit();
}

// Check if user is logged in
$is_logged_in = is_logged_in();
$role = $is_logged_in ? $_SESSION['role'] : null;
$user_id = $is_logged_in ? $_SESSION['user_id'] : null;
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <title><?php echo htmlspecialchars($job['title']); ?> - Job Details</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>

<?php include __DIR__ . '/includes/header.php'; ?>

<div class="container mt-5">
    <div class="row">
        <!-- Left Column -->
        <div class="col-md-8">
            <div class="card">
                <div class="card-body">
                    <h2><?php echo htmlspecialchars($job['title']); ?></h2>
                    <p>
                        <span class="badge bg-primary">Department: <?php echo htmlspecialchars($job['department_name']); ?></span>
                        <span class="badge bg-success">Deadline: <?php echo date('F j, Y', strtotime($job['deadline'])); ?></span>
                    </p>

                    <h4>Description</h4>
                    <p><?php echo nl2br(htmlspecialchars($job['description'])); ?></p>

                    <h4>Requirements</h4>
                    <ul>
                        <?php foreach (explode("\n", $job['requirements']) as $req): ?>
                            <?php if (trim($req)) echo '<li>' . htmlspecialchars(trim($req)) . '</li>'; ?>
                        <?php endforeach; ?>
                    </ul>

                    <h4>Responsibilities</h4>
                    <ul>
                        <?php foreach (explode("\n", $job['responsibilities']) as $res): ?>
                            <?php if (trim($res)) echo '<li>' . htmlspecialchars(trim($res)) . '</li>'; ?>
                        <?php endforeach; ?>
                    </ul>
                </div>
            </div>
        </div>

        <!-- Right Column -->
        <div class="col-md-4">
            <div class="card">
                <div class="card-body">
                    <h4>Next Step</h4>

                    <?php if (!$is_logged_in): ?>
                        <a href="login.php?redirect=job/apply.php&job_id=<?php echo $job_id; ?>" class="btn btn-primary w-100">Login to Apply</a>
                    <?php elseif ($role === 'applicant'): ?>
                        <?php
                        // Check if already applied
                        $stmt = $pdo->prepare("SELECT 1 FROM applications WHERE job_id = ? AND user_id = ?");
                        $stmt->execute([$job_id, $user_id]);
                        $has_applied = $stmt->fetch();

                        if ($has_applied): ?>
                            <div class="alert alert-success">You have already applied for this job.</div>
                        <?php else: ?>
                            <a href="<?php echo SITE_URL; ?>/applicant/apply.php?job_id=<?php echo $job_id; ?>" class="btn btn-success w-100 mb-2">Apply for Job</a>
                        <?php endif; ?>

                        <?php
                        // Check if job is saved
                        $stmt = $pdo->prepare("SELECT 1 FROM saved_jobs WHERE job_id = ? AND user_id = ?");
                        $stmt->execute([$job_id, $user_id]);
                        $is_saved = $stmt->fetch();
                        ?>
                        <form method="post" action="<?php echo SITE_URL; ?>/applicant/save_job.php">
                            <input type="hidden" name="job_id" value="<?php echo $job_id; ?>">
                            <button class="btn btn-outline-secondary w-100" type="submit">
                                <i class="fas <?php echo $is_saved ? 'fa-bookmark' : 'fa-bookmark-o'; ?> me-1"></i>
                                <?php echo $is_saved ? 'Unsave Job' : 'Save Job'; ?>
                            </button>
                        </form>
                    <?php else: ?>
                        <div class="alert alert-info">Only applicants can apply for jobs.</div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
<scri
