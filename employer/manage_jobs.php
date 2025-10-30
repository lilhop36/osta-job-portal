<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/security.php';

// Require employer role
require_role('employer', '../login.php');

// Set security headers
set_security_headers();

// Get user info and department
$stmt = $pdo->prepare("SELECT u.*, d.name as department_name FROM users u 
                       JOIN departments d ON u.department_id = d.id 
                       WHERE u.id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    $_SESSION['error'] = 'User or department not found';
    header('Location: ../login.php');
    exit();
}

$success_message = '';
$error_message = '';

// Handle job deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_job'])) {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $error_message = "Invalid CSRF token. Please try again.";
    } else {
        $job_id = (int)$_POST['job_id'];
        
        try {
            // Check if job belongs to user's department
            $check_stmt = $pdo->prepare("SELECT id FROM jobs WHERE id = ? AND department_id = ?");
            $check_stmt->execute([$job_id, $user['department_id']]);
            
            if ($check_stmt->fetch()) {
                // Check if job has applications
                $app_check = $pdo->prepare("SELECT COUNT(*) as count FROM applications WHERE job_id = ?");
                $app_check->execute([$job_id]);
                $app_count = $app_check->fetch()['count'];
                
                if ($app_count > 0) {
                    $error_message = "Cannot delete job with existing applications. Please contact admin.";
                } else {
                    // Delete job
                    $delete_stmt = $pdo->prepare("DELETE FROM jobs WHERE id = ? AND department_id = ?");
                    if ($delete_stmt->execute([$job_id, $user['department_id']])) {
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

// Get department's jobs with application counts
$jobs_stmt = $pdo->prepare("
    SELECT j.*, 
           COUNT(a.id) as application_count,
           CASE 
               WHEN j.deadline < CURDATE() THEN 'expired'
               ELSE j.status
           END as display_status
    FROM jobs j 
    LEFT JOIN applications a ON j.id = a.job_id 
    WHERE j.department_id = ? 
    GROUP BY j.id 
    ORDER BY j.created_at DESC
");
$jobs_stmt->execute([$user['department_id']]);
$jobs = $jobs_stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Jobs - OSTA Job Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <div class="container-fluid mt-4">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3">
                <div class="card dashboard-card">
                    <div class="card-header bg-gradient-primary text-white">
                        <h3 class="mb-0"><i class="fas fa-building me-2"></i>Employer Menu</h3>
                    </div>
                    <div class="list-group list-group-flush dashboard-sidebar">
                        <a href="dashboard.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                        </a>
                        <a href="post_job.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-plus-circle me-2"></i> Post Job
                        </a>
                        <a href="manage_jobs.php" class="list-group-item list-group-item-action active">
                            <i class="fas fa-briefcase me-2"></i> Manage Jobs
                        </a>
                        <a href="manage_applications.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-users me-2"></i> Applications
                        </a>
                        <a href="profile.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-user me-2"></i> Profile
                        </a>
                        <a href="reports.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-chart-bar me-2"></i> Reports
                        </a>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9">
                <!-- Header -->
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <div>
                        <h2 class="mb-1">Manage Jobs</h2>
                        <p class="text-muted mb-0">Department: <?php echo htmlspecialchars($user['department_name']); ?></p>
                    </div>
                    <a href="post_job.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Post New Job
                    </a>
                </div>

                <!-- Messages -->
                <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Jobs Table -->
                <div class="card dashboard-card">
                    <div class="card-header bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h3 class="mb-0"><i class="fas fa-briefcase me-2 text-primary"></i>Your Job Posts</h3>
                            <span class="badge bg-primary"><?php echo count($jobs); ?> jobs</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (empty($jobs)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-briefcase fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No jobs posted yet</h5>
                                <p class="text-muted">Start by posting your first job opening.</p>
                                <a href="post_job.php" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>Post Your First Job
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Job Title</th>
                                            <th>Status</th>
                                            <th>Applications</th>
                                            <th>Posted Date</th>
                                            <th>Deadline</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($jobs as $job): ?>
                                            <tr>
                                                <td>
                                                    <div>
                                                        <h6 class="mb-0"><?php echo htmlspecialchars($job['title']); ?></h6>
                                                        <small class="text-muted"><?php echo htmlspecialchars($job['employment_type']); ?></small>
                                                    </div>
                                                </td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        switch ($job['display_status']) {
                                                            case 'pending': echo 'warning'; break;
                                                            case 'approved': echo 'success'; break;
                                                            case 'expired': echo 'danger'; break;
                                                            case 'rejected': echo 'danger'; break;
                                                            default: echo 'secondary';
                                                        }
                                                    ?>">
                                                        <i class="fas fa-<?php 
                                                            switch ($job['display_status']) {
                                                                case 'pending': echo 'clock'; break;
                                                                case 'approved': echo 'check-circle'; break;
                                                                case 'expired': echo 'times-circle'; break;
                                                                case 'rejected': echo 'ban'; break;
                                                                default: echo 'question-circle';
                                                            }
                                                        ?> me-1"></i>
                                                        <?php echo ucfirst($job['display_status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <span class="badge bg-info"><?php echo $job['application_count']; ?></span>
                                                </td>
                                                <td><?php echo date('M j, Y', strtotime($job['created_at'])); ?></td>
                                                <td>
                                                    <?php 
                                                    $deadline = strtotime($job['deadline']);
                                                    $now = time();
                                                    $class = $deadline < $now ? 'text-danger' : 'text-dark';
                                                    ?>
                                                    <span class="<?php echo $class; ?>">
                                                        <?php echo date('M j, Y', $deadline); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="../job_details.php?id=<?php echo $job['id']; ?>" 
                                                           class="btn btn-outline-info" target="_blank" title="View Job">
                                                            <i class="fas fa-eye"></i>
                                                        </a>
                                                        <a href="edit_job.php?id=<?php echo $job['id']; ?>" 
                                                           class="btn btn-outline-primary" title="Edit">
                                                            <i class="fas fa-edit"></i>
                                                        </a>
                                                        <a href="view_applicants.php?job_id=<?php echo $job['id']; ?>" 
                                                           class="btn btn-outline-success" title="View Applicants">
                                                            <i class="fas fa-users"></i>
                                                        </a>
                                                        <button type="button" 
                                                                class="btn btn-outline-danger delete-job" 
                                                                data-job-id="<?php echo $job['id']; ?>"
                                                                title="Delete">
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

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    function confirmDelete(jobId, jobTitle) {
        document.getElementById('deleteJobId').value = jobId;
        document.getElementById('jobTitle').textContent = jobTitle;
        var modal = new bootstrap.Modal(document.getElementById('deleteModal'));
        modal.show();
    }
    </script>
    
    <?php prevent_back_navigation(); ?>
</body>
</html>
