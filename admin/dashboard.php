<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/security.php';

// Require admin role
require_role('admin', '../login.php');

// Set security headers
set_security_headers();

// Get system statistics
$stats = [
    'total_users' => $pdo->query("SELECT COUNT(*) as count FROM users")->fetch()['count'],
    'total_jobs' => $pdo->query("SELECT COUNT(*) as count FROM jobs")->fetch()['count'],
    'total_applications' => $pdo->query("SELECT COUNT(*) as count FROM applications")->fetch()['count'],
    'total_departments' => $pdo->query("SELECT COUNT(*) as count FROM departments")->fetch()['count'],
    'pending_jobs' => $pdo->query("SELECT COUNT(*) as count FROM jobs WHERE status = 'pending'")->fetch()['count'],
    'pending_users' => $pdo->query("SELECT COUNT(*) as count FROM users WHERE status = 'pending'")->fetch()['count'],
    'recent_applications' => $pdo->query("SELECT COUNT(*) as count FROM applications WHERE created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)")->fetch()['count'],
    'total_interviews' => $pdo->query("SELECT COUNT(*) as count FROM interviews")->fetch()['count']
];

// Get recent activity
$recent_activity = $pdo->query("
    SELECT 
        'user' as type,
        u.id,
        u.username,
        u.role,
        u.created_at as timestamp,
        'Registered' as action
    FROM users u
    WHERE u.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    UNION
    SELECT 
        'job' as type,
        j.id,
        j.title,
        j.department_id,
        j.created_at as timestamp,
        'Posted' as action
    FROM jobs j
    WHERE j.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    UNION
    SELECT 
        'application' as type,
        a.id,
        u.username,
        j.department_id,
        a.created_at as timestamp,
        'Applied' as action
    FROM applications a
    JOIN users u ON a.user_id = u.id
    JOIN jobs j ON a.job_id = j.id
    WHERE a.created_at >= DATE_SUB(NOW(), INTERVAL 7 DAY)
    ORDER BY timestamp DESC
    LIMIT 20
")->fetchAll();

// Get pending approvals
$pending_jobs = $pdo->query("
    SELECT j.*, d.name as department_name 
    FROM jobs j 
    JOIN departments d ON j.department_id = d.id 
    WHERE j.status = 'pending'
    ORDER BY j.created_at DESC
")->fetchAll();

$pending_users = $pdo->query("
    SELECT u.*, d.name as department_name 
    FROM users u 
    LEFT JOIN departments d ON u.department_id = d.id 
    WHERE u.status = 'pending'
    ORDER BY u.created_at DESC
")->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Admin Dashboard - OSTA Job Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.24/css/dataTables.bootstrap5.min.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container-fluid mt-4">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3">
                <div class="card dashboard-card">
                    <div class="card-header bg-gradient-primary text-white">
                        <h3 class="mb-0"><i class="bi bi-list me-2"></i>Admin Menu</h3>
                    </div>
                    <div class="list-group list-group-flush dashboard-sidebar">
                        <a href="dashboard.php" class="list-group-item list-group-item-action active">
                            <i class="bi bi-speedometer2 me-2"></i> Dashboard
                        </a>
                        <a href="manage_users.php" class="list-group-item list-group-item-action">
                            <i class="bi bi-people me-2"></i> Manage Users
                        </a>
                        <a href="manage_departments.php" class="list-group-item list-group-item-action">
                            <i class="bi bi-building me-2"></i> Manage Departments
                        </a>
                        <a href="manage_jobs.php" class="list-group-item list-group-item-action">
                            <i class="bi bi-briefcase me-2"></i> Manage Jobs
                        </a>
                        <a href="reports.php" class="list-group-item list-group-item-action">
                            <i class="bi bi-file-earmark-text me-2"></i> Reports
                        </a>
                        <a href="settings.php" class="list-group-item list-group-item-action">
                            <i class="bi bi-gear me-2"></i> Settings
                        </a>
                        <a href="notifications.php" class="list-group-item list-group-item-action">
                            <i class="bi bi-bell me-2"></i> Notifications
                        </a>
                        <a href="analytics.php" class="list-group-item list-group-item-action">
                            <i class="bi bi-graph-up me-2"></i> Analytics
                        </a>
                        <a href="manage_interviews.php" class="list-group-item list-group-item-action">
                            <i class="bi bi-person-lines-fill me-2"></i> Manage Interviews
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
                            <h2 class="mb-1">Admin Dashboard</h2>
                            <p class="mb-0">System overview and management tools</p>
                        </div>
                        <div class="text-end">
                            <div class="badge bg-light text-dark fs-6">
                                <i class="bi bi-calendar me-1"></i> <?php echo date('M j, Y'); ?>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Quick Stats -->
                <div class="row mb-4">
                    <div class="col-lg-2 col-md-4 col-6 mb-3">
                        <div class="stat-card bg-white">
                            <div class="card-icon bg-gradient-primary text-white mx-auto">
                                <i class="bi bi-people"></i>
                            </div>
                            <div class="stat-number text-primary"><?php echo $stats['total_users']; ?></div>
                            <div class="stat-label">Total Users</div>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 col-6 mb-3">
                        <div class="stat-card bg-white">
                            <div class="card-icon bg-gradient-success text-white mx-auto">
                                <i class="bi bi-briefcase"></i>
                            </div>
                            <div class="stat-number text-success"><?php echo $stats['total_jobs']; ?></div>
                            <div class="stat-label">Active Jobs</div>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 col-6 mb-3">
                        <div class="stat-card bg-white">
                            <div class="card-icon bg-gradient-info text-white mx-auto">
                                <i class="bi bi-file-text"></i>
                            </div>
                            <div class="stat-number text-info"><?php echo $stats['total_applications']; ?></div>
                            <div class="stat-label">Applications</div>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 col-6 mb-3">
                        <div class="stat-card bg-white">
                            <div class="card-icon bg-gradient-warning text-white mx-auto">
                                <i class="bi bi-building"></i>
                            </div>
                            <div class="stat-number text-warning"><?php echo $stats['total_departments']; ?></div>
                            <div class="stat-label">Departments</div>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 col-6 mb-3">
                        <div class="stat-card bg-white">
                            <div class="card-icon bg-gradient-secondary text-white mx-auto">
                                <i class="bi bi-calendar-event"></i>
                            </div>
                            <div class="stat-number text-secondary"><?php echo $stats['total_interviews']; ?></div>
                            <div class="stat-label">Interviews</div>
                        </div>
                    </div>
                    <div class="col-lg-2 col-md-4 col-6 mb-3">
                        <div class="stat-card bg-white">
                            <div class="card-icon bg-gradient-danger text-white mx-auto">
                                <i class="bi bi-clock"></i>
                            </div>
                            <div class="stat-number text-danger"><?php echo $stats['recent_applications']; ?></div>
                            <div class="stat-label">Recent Apps</div>
                        </div>
                    </div>
                </div>

                <!-- Pending Approvals -->
                <div class="card dashboard-card mb-4">
                    <div class="card-header bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h3 class="mb-0"><i class="bi bi-check-circle me-2 text-warning"></i>Pending Approvals</h3>
                            <div class="btn-group">
                                <a href="manage_jobs.php?status=pending" class="btn btn-outline-warning btn-sm">
                                    <i class="bi bi-briefcase me-1"></i> View Jobs
                                </a>
                                <a href="manage_users.php?status=pending" class="btn btn-outline-info btn-sm">
                                    <i class="bi bi-person-plus me-1"></i> View Users
                                </a>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <div class="card bg-warning bg-opacity-10 border-0">
                                    <div class="card-body text-center">
                                        <div class="stat-number text-warning"><?php echo $stats['pending_jobs']; ?></div>
                                        <div class="stat-label">Pending Jobs</div>
                                        <p class="mb-0 mt-2">Awaiting approval</p>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-6 mb-3">
                                <div class="card bg-info bg-opacity-10 border-0">
                                    <div class="card-body text-center">
                                        <div class="stat-number text-info"><?php echo $stats['pending_users']; ?></div>
                                        <div class="stat-label">Pending Users</div>
                                        <p class="mb-0 mt-2">Awaiting approval</p>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Recent Activity -->
                <div class="card dashboard-card mb-4">
                    <div class="card-header bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h3 class="mb-0"><i class="bi bi-activity me-2 text-primary"></i>Recent Activity</h3>
                            <span class="badge bg-primary"><?php echo count($recent_activity); ?> items</span>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Type</th>
                                        <th>Item</th>
                                        <th>Department</th>
                                        <th>Action</th>
                                        <th>Timestamp</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($recent_activity as $activity): ?>
                                        <tr>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $activity['type'] === 'user' ? 'primary' : 
                                                    ($activity['type'] === 'job' ? 'success' : 'info');
                                                ?>">
                                                    <i class="bi <?php 
                                                        echo $activity['type'] === 'user' ? 'bi-person' : 
                                                        ($activity['type'] === 'job' ? 'bi-briefcase' : 'bi-file-text');
                                                    ?>"></i>
                                                    <?php echo ucfirst($activity['type']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo htmlspecialchars($activity['username'] ?? $activity['title']); ?></td>
                                            <td><?php 
                                                if (isset($activity['department_name'])) {
                                                    echo htmlspecialchars($activity['department_name']);
                                                } elseif (isset($activity['department_id'])) {
                                                    $stmt = $pdo->prepare("SELECT name FROM departments WHERE id = ?");
                                                    $stmt->execute([$activity['department_id']]);
                                                    $dept = $stmt->fetch();
                                                    echo htmlspecialchars($dept['name']);
                                                }
                                            ?></td>
                                            <td><?php echo htmlspecialchars($activity['action']); ?></td>
                                            <td><?php echo date('M j, Y H:i', strtotime($activity['timestamp'])); ?></td>
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
                        <h3 class="mb-0"><i class="bi bi-lightning me-2 text-success"></i>Quick Actions</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4 mb-3">
                                <div class="quick-action-card card bg-light border-0">
                                    <div class="card-body text-center">
                                        <div class="card-icon bg-gradient-primary text-white mb-3">
                                            <i class="bi bi-building"></i>
                                        </div>
                                        <h5 class="card-title">Create Department</h5>
                                        <p class="card-text">Add a new department/employer account.</p>
                                        <a href="create_department.php" class="btn btn-primary btn-sm">Create</a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="quick-action-card card bg-light border-0">
                                    <div class="card-body text-center">
                                        <div class="card-icon bg-gradient-success text-white mb-3">
                                            <i class="bi bi-file-bar-graph"></i>
                                        </div>
                                        <h5 class="card-title">Generate Reports</h5>
                                        <p class="card-text">Export data in PDF/CSV format.</p>
                                        <a href="reports.php" class="btn btn-success btn-sm">Generate</a>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4 mb-3">
                                <div class="quick-action-card card bg-light border-0">
                                    <div class="card-body text-center">
                                        <div class="card-icon bg-gradient-info text-white mb-3">
                                            <i class="bi bi-graph-up"></i>
                                        </div>
                                        <h5 class="card-title">View Analytics</h5>
                                        <p class="card-text">View system usage statistics and trends.</p>
                                        <a href="analytics.php" class="btn btn-info btn-sm">View</a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.24/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            // Initialize DataTables for activity table
            $('.table').DataTable({
                pageLength: 10,
                order: [[4, 'desc']],
                columnDefs: [
                    { targets: [0, 1, 2, 3, 4], orderable: true }
                ]
            });
        });
    </script>
    <?php prevent_back_navigation(); ?>
</body>
</html>
