<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/security.php';

require_role('employer', '../login.php');
set_security_headers();

$employer_id = (int)$_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Handle job deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_job'])) {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $error_message = "Invalid CSRF token. Please try again.";
    } else {
        $job_id = (int)$_POST['job_id'];
        try {
            $check_stmt = $pdo->prepare("SELECT id FROM jobs WHERE id = ? AND created_by = ?");
            $check_stmt->execute([$job_id, $employer_id]);

            if ($check_stmt->fetch()) {
                $app_check = $pdo->prepare("SELECT COUNT(*) as count FROM applications WHERE job_id = ?");
                $app_check->execute([$job_id]);
                $app_count = $app_check->fetch()['count'];

                if ($app_count > 0) {
                    $error_message = "Cannot delete job with existing applications. Please contact admin.";
                } else {
                    $delete_stmt = $pdo->prepare("DELETE FROM jobs WHERE id = ? AND created_by = ?");
                    if ($delete_stmt->execute([$job_id, $employer_id])) {
                        $success_message = "Job deleted successfully.";
                    } else {
                        $error_message = "Failed to delete job.";
                    }
                }
            } else {
                $error_message = "Job not found or access denied.";
            }
        } catch (PDOException $e) {
            error_log("Error deleting job: " . $e->getMessage());
            $error_message = "An error occurred while deleting the job.";
        }
    }
}

// Get employer's jobs with application counts
$jobs_stmt = $pdo->prepare("
    SELECT j.*, 
           COUNT(a.id) as application_count,
           CASE 
               WHEN j.deadline < CURDATE() THEN 'expired'
               ELSE j.status
           END as display_status
    FROM jobs j 
    LEFT JOIN applications a ON j.id = a.job_id 
    WHERE j.created_by = ?
    GROUP BY j.id 
    ORDER BY j.created_at DESC
");
$jobs_stmt->execute([$employer_id]);
$jobs = $jobs_stmt->fetchAll();
?>

<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-3">
            <?php include __DIR__ . '/../includes/employer_sidebar.php'; ?>
        </div>

        <div class="col-md-9">
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-briefcase me-2"></i>Manage Jobs</h5>
                    <a href="post_job.php" class="btn btn-sm btn-success"><i class="fas fa-plus me-1"></i>Post New Job</a>
                </div>
                <div class="card-body">
                    <?php if (!empty($success_message)): ?>
                        <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success_message) ?></div>
                    <?php endif; ?>
                    <?php if (!empty($error_message)): ?>
                        <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?= htmlspecialchars($error_message) ?></div>
                    <?php endif; ?>

                    <?php if (empty($jobs)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-briefcase fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No jobs posted yet</h5>
                            <p class="text-muted">Start by posting your first job.</p>
                            <a href="post_job.php" class="btn btn-success"><i class="fas fa-plus me-1"></i>Post Job</a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table align-items-center mb-0">
                                <thead>
                                    <tr>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Job Title</th>
                                        <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Type</th>
                                        <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Applications</th>
                                        <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Status</th>
                                        <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Deadline</th>
                                        <th class="text-secondary opacity-7"></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($jobs as $job): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex px-2 py-1">
                                                    <div class="d-flex flex-column justify-content-center">
                                                        <h6 class="mb-0 text-sm"><?= htmlspecialchars($job['title']) ?></h6>
                                                        <p class="text-xs text-secondary mb-0"><?= htmlspecialchars($job['location'] ?? 'N/A') ?></p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <span class="badge bg-gradient-info"><?= htmlspecialchars(ucfirst($job['job_type'] ?? '')) ?></span>
                                            </td>
                                            <td class="align-middle text-center">
                                                <span class="text-secondary text-xs font-weight-bold"><?= (int)($job['application_count']) ?></span>
                                            </td>
                                            <td class="align-middle text-center">
                                                <?php
                                                $status = $job['display_status'] ?? $job['status'];
                                                $badge = match($status) {
                                                    'active' => 'success',
                                                    'expired' => 'secondary',
                                                    'closed' => 'danger',
                                                    'draft' => 'warning',
                                                    default => 'secondary'
                                                };
                                                ?>
                                                <span class="badge bg-gradient-<?= $badge ?>"><?= ucfirst($status) ?></span>
                                            </td>
                                            <td class="align-middle text-center">
                                                <span class="text-secondary text-xs font-weight-bold">
                                                    <?= $job['deadline'] ? date('M d, Y', strtotime($job['deadline'])) : 'N/A' ?>
                                                </span>
                                            </td>
                                            <td class="align-middle">
                                                <div class="btn-group btn-group-sm">
                                                    <a href="edit_job.php?id=<?= $job['id'] ?>" class="btn btn-outline-primary" title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-outline-danger" title="Delete"
                                                        onclick="confirmDelete(<?= $job['id'] ?>, '<?= htmlspecialchars($job['title'], ENT_QUOTES) ?>')">
                                                        <i class="fas fa-trash"></i>
                                                    </button>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<!-- Delete Confirmation Modal -->
<div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
    <div class="modal-dialog">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <div class="modal-body">
                <p>Are you sure you want to delete the job "<span id="jobTitle"></span>"?</p>
                <p class="text-danger"><small>This action cannot be undone.</small></p>
            </div>
            <div class="modal-footer">
                <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                <form method="POST" style="display: inline;">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <input type="hidden" name="job_id" id="deleteJobId">
                    <input type="hidden" name="delete_job" value="1">
                    <button type="submit" class="btn btn-danger">Delete Job</button>
                </form>
            </div>
        </div>
    </div>
</div>

<script>
function confirmDelete(jobId, jobTitle) {
    document.getElementById('deleteJobId').value = jobId;
    document.getElementById('jobTitle').textContent = jobTitle;
    var modal = new bootstrap.Modal(document.getElementById('deleteModal'));
    modal.show();
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
