<?php
require_once __DIR__ . '/../includes/bootstrap.php';

require_auth('employer');

// Get user info
$stmt = $pdo->prepare("SELECT u.*, d.name as department_name FROM users u 
                       LEFT JOIN departments d ON u.department_id = d.id 
                       WHERE u.id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    $_SESSION['error'] = 'User not found';
    header('Location: ../login.php');
    exit();
}

// Get jobs posted by this employer
$jobs_stmt = $pdo->prepare("SELECT j.*, d.name as department_name 
                           FROM jobs j 
                           LEFT JOIN departments d ON j.department_id = d.id 
                           WHERE j.created_by = ? 
                           ORDER BY j.created_at DESC LIMIT 10");
$jobs_stmt->execute([$_SESSION['user_id']]);
$jobs = $jobs_stmt->fetchAll();

// Get stats
$stats_stmt = $pdo->prepare("SELECT 
    COUNT(*) as total_jobs,
    SUM(CASE WHEN status = 'pending' THEN 1 ELSE 0 END) as pending_jobs,
    SUM(CASE WHEN status = 'approved' THEN 1 ELSE 0 END) as active_jobs,
    SUM(CASE WHEN status = 'expired' THEN 1 ELSE 0 END) as expired_jobs
    FROM jobs WHERE created_by = ?");
$stats_stmt->execute([$_SESSION['user_id']]);
$stats = $stats_stmt->fetch();

// Get total applicants for employer's jobs
$app_stmt = $pdo->prepare("SELECT COUNT(DISTINCT a.user_id) as total 
                           FROM applications a 
                           JOIN jobs j ON a.job_id = j.id 
                           WHERE j.created_by = ?");
$app_stmt->execute([$_SESSION['user_id']]);
$total_applicants = $app_stmt->fetch()['total'] ?? 0;
?>

<?php include __DIR__ . '/../includes/header.php'; ?>

    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-md-3">
                <?php include __DIR__ . '/../includes/employer_sidebar.php'; ?>
            </div>

            <div class="col-md-9">
                <!-- Welcome Banner -->
                <div class="welcome-banner mb-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="mb-1">Employer Dashboard</h2>
                            <p class="mb-0">Welcome back, <?php echo htmlspecialchars($user['username']); ?></p>
                        </div>
                        <a href="post_job.php" class="btn btn-primary">
                            <i class="fas fa-plus me-1"></i> Post New Job
                        </a>
                    </div>
                </div>

                <!-- Stats Row -->
                <div class="row mb-4">
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="stat-card bg-white">
                            <div class="card-icon bg-gradient-success text-white mx-auto">
                                <i class="fas fa-briefcase"></i>
                            </div>
                            <div class="stat-number text-success"><?php echo $stats['total_jobs'] ?? 0; ?></div>
                            <div class="stat-label">Total Jobs</div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="stat-card bg-white">
                            <div class="card-icon bg-gradient-warning text-white mx-auto">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stat-number text-warning"><?php echo $stats['pending_jobs'] ?? 0; ?></div>
                            <div class="stat-label">Pending</div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="stat-card bg-white">
                            <div class="card-icon bg-gradient-primary text-white mx-auto">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stat-number text-primary"><?php echo $stats['active_jobs'] ?? 0; ?></div>
                            <div class="stat-label">Active</div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="stat-card bg-white">
                            <div class="card-icon bg-gradient-info text-white mx-auto">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-number text-info"><?php echo $total_applicants; ?></div>
                            <div class="stat-label">Applicants</div>
                        </div>
                    </div>
                </div>

                <!-- Recent Job Posts -->
                <div class="card dashboard-card mb-4">
                    <div class="card-header bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-list me-2 text-primary"></i>Your Job Posts</h5>
                            <a href="manage_jobs.php" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_SESSION['success_message'])): ?>
                            <div class="alert alert-success alert-dismissible fade show">
                                <i class="fas fa-check-circle me-2"></i>
                                <?php echo htmlspecialchars($_SESSION['success_message'], ENT_QUOTES, 'UTF-8'); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                            <?php unset($_SESSION['success_message']); ?>
                        <?php endif; ?>

                        <?php if (empty($jobs)): ?>
                            <div class="text-center py-5">
                                <i class="fas fa-briefcase fa-3x text-muted mb-3"></i>
                                <h5 class="text-muted">No jobs posted yet</h5>
                                <p class="text-muted">Start by posting your first job.</p>
                                <a href="post_job.php" class="btn btn-primary">
                                    <i class="fas fa-plus me-1"></i> Post Your First Job
                                </a>
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Job Title</th>
                                            <th>Department</th>
                                            <th>Status</th>
                                            <th>Applications</th>
                                            <th>Deadline</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($jobs as $job): ?>
                                            <tr>
                                                <td>
                                                    <strong><?php echo htmlspecialchars($job['title']); ?></strong>
                                                </td>
                                                <td><?php echo htmlspecialchars($job['department_name'] ?? 'N/A'); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php 
                                                        match($job['status']) {
                                                            'pending' => 'warning',
                                                            'approved' => 'success',
                                                            'expired' => 'danger',
                                                            default => 'secondary'
                                                        }
                                                    ?>">
                                                        <?php echo ucfirst($job['status']); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php
                                                    $count_stmt = $pdo->prepare("SELECT COUNT(*) as count FROM applications WHERE job_id = ?");
                                                    $count_stmt->execute([$job['id']]);
                                                    $app_count = $count_stmt->fetch()['count'];
                                                    ?>
                                                    <span class="badge bg-info"><?php echo $app_count; ?></span>
                                                </td>
                                                <td><?php echo date('M j, Y', strtotime($job['deadline'])); ?></td>
                                                <td>
                                                    <div class="btn-group btn-group-sm">
                                                        <a href="manage_applications.php?job_id=<?php echo $job['id']; ?>" 
                                                           class="btn btn-outline-success" title="View Applications">
                                                            <i class="fas fa-users"></i>
                                                        </a>
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

<?php include __DIR__ . '/../includes/footer.php'; ?>
<?php prevent_back_navigation(); ?>
