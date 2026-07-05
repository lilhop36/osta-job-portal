<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\SavedJob;

require_role('applicant', '../login.php');

$userId = (int) $_SESSION['user_id'];
$savedModel = new SavedJob();

// Handle note update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['error_message'] = 'Invalid security token.';
        header('Location: saved_jobs.php');
        exit;
    }

    if ($_POST['action'] === 'update_note') {
        $jobId = (int) $_POST['job_id'];
        $notes = sanitize($_POST['notes'] ?? '');
        $stmt = $pdo->prepare("UPDATE saved_jobs SET notes = ? WHERE user_id = ? AND job_id = ?");
        $stmt->execute([$notes, $userId, $jobId]);
        $_SESSION['success_message'] = 'Notes updated.';
    } elseif ($_POST['action'] === 'remove') {
        $jobId = (int) $_POST['job_id'];
        $stmt = $pdo->prepare("DELETE FROM saved_jobs WHERE user_id = ? AND job_id = ?");
        $stmt->execute([$userId, $jobId]);
        $_SESSION['success_message'] = 'Job removed from saved list.';
    }

    header('Location: saved_jobs.php');
    exit;
}

$savedJobs = $savedModel->getByUser($userId);
$pageTitle = 'Saved Jobs';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0"><i class="fas fa-bookmark me-2" style="color: var(--osta-green);"></i>Saved Jobs</h4>
        <a href="dashboard.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>Dashboard</a>
    </div>

    <?php if (empty($savedJobs)): ?>
        <div class="text-center py-5">
            <i class="fas fa-bookmark fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">No saved jobs yet</h5>
            <p class="text-muted">Browse jobs and click the bookmark icon to save them here.</p>
            <a href="../jobs.php" class="btn" style="background: var(--osta-green); color: white;">Browse Jobs</a>
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($savedJobs as $job): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm" style="border-radius: 12px;">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h6 class="fw-bold mb-0">
                                <a href="../job_details.php?id=<?php echo $job['job_id']; ?>" class="text-decoration-none" style="color: var(--osta-dark);">
                                    <?php echo htmlspecialchars($job['title']); ?>
                                </a>
                            </h6>
                            <span class="badge bg-<?php echo match($job['employment_type'] ?? '') {
                                'full_time' => 'success',
                                'part_time' => 'info',
                                'contract' => 'warning',
                                default => 'secondary'
                            }; ?>">
                                <?php echo str_replace('_', ' ', ucfirst($job['employment_type'] ?? 'N/A')); ?>
                            </span>
                        </div>
                        <div class="small text-muted mb-2">
                            <div><i class="fas fa-building me-1"></i><?php echo htmlspecialchars($job['department_name'] ?? 'N/A'); ?></div>
                            <div><i class="fas fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($job['location'] ?? 'N/A'); ?></div>
                        </div>

                        <?php if (!empty($job['notes'])): ?>
                            <div class="bg-light rounded p-2 mb-2">
                                <small class="text-muted"><i class="fas fa-sticky-note me-1"></i><?php echo htmlspecialchars($job['notes']); ?></small>
                            </div>
                        <?php endif; ?>

                        <form method="POST" class="mt-2">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            <input type="hidden" name="action" value="update_note">
                            <input type="hidden" name="job_id" value="<?php echo $job['job_id']; ?>">
                            <div class="input-group input-group-sm">
                                <input type="text" name="notes" class="form-control" placeholder="Add a note..." value="<?php echo htmlspecialchars($job['notes'] ?? ''); ?>">
                                <button type="submit" class="btn btn-outline-secondary"><i class="fas fa-save"></i></button>
                            </div>
                        </form>
                    </div>
                    <div class="card-footer bg-white border-top-0 d-flex justify-content-between">
                        <small class="text-muted"><i class="fas fa-calendar me-1"></i><?php echo date('M d, Y', strtotime($job['deadline'])); ?></small>
                        <form method="POST" class="d-inline" onsubmit="return confirm('Remove from saved?');">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            <input type="hidden" name="action" value="remove">
                            <input type="hidden" name="job_id" value="<?php echo $job['job_id']; ?>">
                            <button type="submit" class="btn btn-sm btn-outline-danger"><i class="fas fa-trash"></i></button>
                        </form>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
