<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/security.php';

// Require applicant role
require_role('applicant', SITE_URL . '/login.php');

// Set security headers
set_security_headers();

// Get saved jobs
$stmt = $pdo->prepare("SELECT j.* FROM jobs j JOIN saved_jobs sj ON j.id = sj.job_id WHERE sj.user_id = ? ORDER BY sj.created_at DESC");
$stmt->execute([$_SESSION['user_id']]);
$saved_jobs = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Saved Jobs - OSTA Job Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <div class="container mt-4">
        <h2 class="mb-4">Saved Jobs</h2>

        <?php if (empty($saved_jobs)): ?>
            <div class="alert alert-info">You have not saved any jobs yet.</div>
        <?php else: ?>
            <div class="list-group">
                <?php 
                    foreach ($saved_jobs as $job): 
                        // Check if user has already applied to this job
                        $applied_stmt = $pdo->prepare("SELECT 1 FROM applications WHERE job_id = ? AND user_id = ?");
                        $applied_stmt->execute([$job['id'], $_SESSION['user_id']]);
                        $has_applied = $applied_stmt->fetch();
                    ?>
                    <div class="list-group-item">
                        <div class="d-flex w-100 justify-content-between">
                            <h5 class="mb-1">
                                <a href="<?php echo SITE_URL; ?>/job_details.php?id=<?php echo $job['id']; ?>">
                                    <?php echo htmlspecialchars($job['title']); ?>
                                </a>
                            </h5>
                            <small class="text-muted">
                                Deadline: <?php echo date('M j, Y', strtotime($job['deadline'])); ?>
                            </small>
                        </div>
                        <p class="mb-1">
                            <i class="fas fa-map-marker-alt me-1"></i> 
                            <?php echo htmlspecialchars($job['location']); ?>
                        </p>
                        <div class="mt-2 d-flex justify-content-between align-items-center">
                            <div>
                                <a href="<?php echo SITE_URL; ?>/job_details.php?id=<?php echo $job['id']; ?>" class="btn btn-sm btn-outline-primary">
                                    <i class="fas fa-eye me-1"></i> View Details
                                </a>
                                <form action="save_job.php" method="post" class="d-inline">
                                    <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                    <button type="submit" class="btn btn-sm btn-outline-danger">
                                        <i class="fas fa-bookmark"></i> Remove
                                    </button>
                                </form>
                            </div>
                            <?php if ($has_applied): ?>
                                <span class="badge bg-success">
                                    <i class="fas fa-check me-1"></i> Applied
                                </span>
                            <?php else: ?>
                                <a href="<?php echo SITE_URL; ?>/applicant/apply.php?job_id=<?php echo $job['id']; ?>" 
                                   class="btn btn-sm btn-primary">
                                    <i class="fas fa-paper-plane me-1"></i> Apply Now
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                <?php endforeach; ?>
            </div>
        <?php endif; ?>
        <a href="<?php echo SITE_URL; ?>/applicant/dashboard.php" class="btn btn-secondary mt-4">Back to Dashboard</a>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <?php prevent_back_navigation(); ?>
</body>
</html>
