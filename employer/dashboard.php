<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/security.php';

// Require employer role
require_role('employer', '../login.php');

// Set security headers
set_security_headers();

// Get user info and department
$stmt = $pdo->prepare("SELECT u.*, d.name as department_name, d.description as department_description FROM users u 
                       JOIN departments d ON u.department_id = d.id 
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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employer Dashboard - OSTA Job Portal</title>
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
                        <a href="dashboard.php" class="list-group-item list-group-item-action active">
                            <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                        </a>
                        <a href="post_job.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-plus-circle me-2"></i> Post Job
                        </a>
                        <a href="manage_jobs.php" class="list-group-item list-group-item-action">
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
                <!-- Welcome Section -->
                <div class="welcome-banner mb-4">
                    <div class="d-flex justify-content-between align-items-center">
                        <div>
                            <h2 class="mb-1">Employer Dashboard</h2>
                            <p class="mb-0">Welcome back, <?php echo htmlspecialchars($user['full_name']); ?>!</p>
                            <p class="mb-0 text-muted">Department: <?php echo htmlspecialchars($user['department_name']); ?></p>
                        </div>
                        <div class="text-end">
                            <div class="badge bg-light text-dark fs-6">
                                <i class="fas fa-calendar me-1"></i> <?php echo date('M j, Y'); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Action Buttons -->
                <div class="d-flex flex-wrap gap-2 mb-4">
                    <a href="post_job.php" class="btn btn-primary">
                        <i class="fas fa-plus me-2"></i>Post New Job
                    </a>
                    <a href="manage_applications.php" class="btn btn-success">
                        <i class="fas fa-users me-2"></i>Manage Applications
                    </a>
                    <a href="profile.php" class="btn btn-info">
                        <i class="fas fa-user me-2"></i>Profile Settings
                    </a>
                    <a href="reports.php" class="btn btn-warning">
                        <i class="fas fa-chart-bar me-2"></i>View Reports
                    </a>
                </div>

        <!-- Department Overview -->
        <div class="row mb-4">
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stat-card bg-white">
                    <div class="card-icon bg-gradient-success text-white mx-auto">
                        <i class="fas fa-briefcase"></i>
                    </div>
                    <div class="stat-number text-success"><?php 
                        $active = 0;
                        foreach ($jobs as $job) {
                            if ($job['status'] === 'approved') {
                                $active++;
                            }
                        }
                        echo $active;
                    ?></div>
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
                    <div class="stat-number text-warning"><?php 
                        $pending = 0;
                        foreach ($jobs as $job) {
                            if ($job['status'] === 'pending') {
                                $pending++;
                            }
                        }
                        echo $pending;
                    ?></div>
                    <div class="stat-label">Pending Approval</div>
                </div>
            </div>
            <div class="col-lg-3 col-md-6 mb-3">
                <div class="stat-card bg-white">
                    <div class="card-icon bg-gradient-danger text-white mx-auto">
                        <i class="fas fa-calendar-times"></i>
                    </div>
                    <div class="stat-number text-danger"><?php 
                        $expired = 0;
                        foreach ($jobs as $job) {
                            if ($job['status'] === 'expired') {
                                $expired++;
                            }
                        }
                        echo $expired;
                    ?></div>
                    <div class="stat-label">Expired Jobs</div>
                </div>
            </div>
        </div>

        <!-- Recent Job Posts -->
        <div class="card dashboard-card mb-4">
            <div class="card-header bg-white">
                <div class="d-flex justify-content-between align-items-center">
                    <h3 class="mb-0"><i class="fas fa-list me-2 text-primary"></i>Recent Job Posts</h3>
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
                                            <i class="fas fa-<?php 
                                                switch ($job['status']) {
                                                    case 'pending': echo 'clock'; break;
                                                    case 'approved': echo 'check-circle'; break;
                                                    case 'expired': echo 'times-circle'; break;
                                                    default: echo 'question-circle';
                                                }
                                            ?> me-1"></i>
                                            <?php echo ucfirst($job['status']); ?>
                                        </span>
                                    </td>
                                    <td>
                                        <?php
                                        $stmt = $pdo->prepare("SELECT COUNT(*) as count 
                                                              FROM applications 
                                                              WHERE job_id = ?");
                                        $stmt->execute([$job['id']]);
                                        $app_count = $stmt->fetch()['count'];
                                        ?>
                                        <span class="badge bg-info"><?php echo $app_count; ?></span>
                                    </td>
                                    <td><?php echo date('M j, Y', strtotime($job['deadline'])); ?></td>
                                    <td>
                                        <div class="btn-group btn-group-sm">
                                            <a href="edit_job.php?id=<?php echo $job['id']; ?>" 
                                               class="btn btn-outline-primary">
                                                <i class="fas fa-edit"></i>
                                            </a>
                                            <a href="manage_applications.php?job_id=<?php echo $job['id']; ?>" 
                                               class="btn btn-outline-success">
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

        <!-- Quick Actions -->
        <div class="card dashboard-card">
            <div class="card-header bg-white">
                <h3 class="mb-0"><i class="fas fa-bolt me-2 text-success"></i>Quick Actions</h3>
            </div>
            <div class="card-body">
                <div class="row">
                    <div class="col-md-4 mb-3">
                        <div class="quick-action-card card bg-light border-0">
                            <div class="card-body text-center">
                                <div class="card-icon bg-gradient-primary text-white mb-3">
                                    <i class="fas fa-plus-circle"></i>
                                </div>
                                <h5 class="card-title">Post New Job</h5>
                                <p class="card-text">Create a new job listing for your department.</p>
                                <a href="post_job.php" class="btn btn-primary btn-sm">Post Job</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="quick-action-card card bg-light border-0">
                            <div class="card-body text-center">
                                <div class="card-icon bg-gradient-info text-white mb-3">
                                    <i class="fas fa-user"></i>
                                </div>
                                <h5 class="card-title">Profile Settings</h5>
                                <p class="card-text">Update your profile and change password.</p>
                                <a href="profile.php" class="btn btn-info btn-sm">Manage Profile</a>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-4 mb-3">
                        <div class="quick-action-card card bg-light border-0">
                            <div class="card-body text-center">
                                <div class="card-icon bg-gradient-success text-white mb-3">
                                    <i class="fas fa-users"></i>
                                </div>
                                <h5 class="card-title">View Applications</h5>
                                <p class="card-text">Review and manage job applications.</p>
                                <a href="manage_applications.php" class="btn btn-success btn-sm">View Applications</a>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <?php prevent_back_navigation(); ?>
</body>
</html>
