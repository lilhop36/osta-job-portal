<?php
require_once __DIR__ . '/../includes/bootstrap.php';

require_auth('employer');

// Get user info and department
$stmt = $pdo->prepare("SELECT u.*, d.name as department_name, d.description as department_description FROM users u 
                       LEFT JOIN departments d ON u.department_id = d.id 
                       WHERE u.id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Check if user exists and has department
if (!$user) {
    $_SESSION['error'] = 'User or department not found';
    header('Location: ../login.php');
    exit();
}

// Get department's jobs
$jobs_stmt = $pdo->prepare("SELECT * FROM jobs WHERE department_id = ? ORDER BY created_at DESC LIMIT 10");
$jobs_stmt->execute([$user['department_id']]);
$jobs = $jobs_stmt->fetchAll();

// Get total applicants for this department
$applicants_stmt = $pdo->prepare("SELECT COUNT(DISTINCT a.user_id) as total_applicants 
                                 FROM applications a 
                                 JOIN jobs j ON a.job_id = j.id 
                                 WHERE j.department_id = ?");
$applicants_stmt->execute([$user['department_id']]);
$total_applicants_result = $applicants_stmt->fetch();
$total_applicants = $total_applicants_result ? $total_applicants_result['total_applicants'] : 0;
?>

<?php include __DIR__ . '/../includes/header.php'; ?>

    <div class="container-fluid mt-4">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3">
                <?php include __DIR__ . '/../includes/employer_sidebar.php'; ?>
            </div>

            <!-- Main Content -->
            <div class="col-md-9">
                <!-- Single Greeting -->
                <div class="welcome-banner mb-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="mb-1">Employer Dashboard</h2>
                            <p class="mb-0">Welcome back, <?php echo htmlspecialchars($user['full_name']); ?>!</p>
                            <p class="mb-0 text-muted">Department: <?php echo htmlspecialchars($user['department_name'] ?? 'Not assigned'); ?></p>
                        </div>
                        <div class="text-end">
                            <div class="badge bg-light text-dark fs-6">
                                <i class="fas fa-calendar me-1"></i> <?php echo date('M j, Y'); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Stats: unified 4-column grid, every number labeled -->
                <div class="row mb-4">
                    <?php
                    $active = 0;
                    $pending = 0;
                    $expired = 0;
                    foreach ($jobs as $job) {
                        if ($job['status'] === 'approved') $active++;
                        elseif ($job['status'] === 'pending') $pending++;
                        elseif ($job['status'] === 'expired') $expired++;
                    }
                    ?>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="stat-card bg-white">
                            <div class="card-icon bg-gradient-success text-white mx-auto">
                                <i class="fas fa-briefcase"></i>
                            </div>
                            <div class="stat-number text-success"><?php echo $active; ?></div>
                            <div class="stat-label">Active Jobs</div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="stat-card bg-white">
                            <div class="card-icon bg-gradient-primary text-white mx-auto">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-number text-primary"><?php echo $total_applicants; ?></div>
                            <div class="stat-label">Total Applicants</div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="stat-card bg-white">
                            <div class="card-icon bg-gradient-warning text-white mx-auto">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stat-number text-warning"><?php echo $pending; ?></div>
                            <div class="stat-label">Pending Approval</div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="stat-card bg-white">
                            <div class="card-icon bg-gradient-danger text-white mx-auto">
                                <i class="fas fa-calendar-times"></i>
                            </div>
                            <div class="stat-number text-danger"><?php echo $expired; ?></div>
                            <div class="stat-label">Expired Jobs</div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions with actionable metrics -->
                <div class="row mb-4">
                    <div class="col-md-4 mb-3">
                        <a href="post_job.php" class="text-decoration-none">
                            <div class="card dashboard-card h-100 border-0 shadow-sm">
                                <div class="card-body text-center">
                                    <div class="card-icon bg-gradient-primary text-white mx-auto mb-3">
                                        <i class="fas fa-plus-circle"></i>
                                    </div>
                                    <h5 class="card-title text-dark">Post New Job</h5>
                                    <p class="text-muted mb-2"><?php echo $active; ?> active listings</p>
                                    <span class="btn btn-primary btn-sm">Post Job</span>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-4 mb-3">
                        <a href="manage_applications.php" class="text-decoration-none">
                            <div class="card dashboard-card h-100 border-0 shadow-sm">
                                <div class="card-body text-center">
                                    <div class="card-icon bg-gradient-success text-white mx-auto mb-3">
                                        <i class="fas fa-users"></i>
                                    </div>
                                    <h5 class="card-title text-dark">Manage Applications</h5>
                                    <p class="text-muted mb-2"><?php echo $total_applicants; ?> applicants total</p>
                                    <span class="btn btn-success btn-sm">Review</span>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-4 mb-3">
                        <a href="reports.php" class="text-decoration-none">
                            <div class="card dashboard-card h-100 border-0 shadow-sm">
                                <div class="card-body text-center">
                                    <div class="card-icon bg-gradient-info text-white mx-auto mb-3">
                                        <i class="fas fa-chart-bar"></i>
                                    </div>
                                    <h5 class="card-title text-dark">View Reports</h5>
                                    <p class="text-muted mb-2">Hiring analytics</p>
                                    <span class="btn btn-info btn-sm">View Report</span>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- Recent Job Posts -->
                <div class="card dashboard-card mb-4">
                    <div class="card-header bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-list me-2 text-primary"></i>Recent Job Posts</h5>
                            <span class="badge bg-primary"><?php echo count($jobs); ?> jobs</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Job Title</th>
                                        <th>Status</th>
                                        <th>Applications</th>
                                        <th>Deadline</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($jobs as $job): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($job['title']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    switch ($job['status']) {
                                                        case 'pending': echo 'warning'; break;
                                                        case 'approved': echo 'success'; break;
                                                        case 'expired': echo 'danger'; break;
                                                        default: echo 'secondary';
                                                    }
                                                ?>">
                                                    <?php echo ucfirst($job['status']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php
                                                $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM applications WHERE job_id = ?");
                                                $stmt->execute([$job['id']]);
                                                $app_count = $stmt->fetch()['count'];
                                                ?>
                                                <span class="badge bg-info"><?php echo $app_count; ?></span>
                                            </td>
                                            <td><?php echo date('M j, Y', strtotime($job['deadline'])); ?></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="edit_job.php?id=<?php echo $job['id']; ?>" class="btn btn-outline-primary">
                                                        <i class="fas fa-edit"></i>
                                                    </a>
                                                    <a href="manage_applications.php?job_id=<?php echo $job['id']; ?>" class="btn btn-outline-success">
                                                        <i class="fas fa-users"></i> <?php echo $app_count; ?>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
<?php prevent_back_navigation(); ?>