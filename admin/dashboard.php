<?php
require_once __DIR__ . '/../includes/bootstrap.php';

require_auth('admin');

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
<?php include '../includes/header.php'; ?>

<div class="container-fluid mt-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3">
            <?php include '../includes/admin_sidebar.php'; ?>
        </div>

        <!-- Main Content -->
        <div class="col-md-9">
            <!-- Single Greeting -->
            <div class="welcome-banner mb-4">
                <div class="d-flex justify-content-between align-items-center">
                    <div>
                        <h2 class="mb-1">Admin Dashboard</h2>
                        <p class="mb-0">System overview and management tools</p>
                    </div>
                    <div class="text-end">
                        <div class="badge bg-light text-dark fs-6">
                            <i class="fas fa-calendar me-1"></i> <?php echo date('M j, Y'); ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Stats: 4-column grid, every number labeled -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stat-card bg-white">
                        <div class="card-icon bg-gradient-primary text-white mx-auto">
                            <i class="fas fa-users"></i>
                        </div>
                        <div class="stat-number text-primary"><?php echo $stats['total_users']; ?></div>
                        <div class="stat-label">Total Users</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stat-card bg-white">
                        <div class="card-icon bg-gradient-success text-white mx-auto">
                            <i class="fas fa-briefcase"></i>
                        </div>
                        <div class="stat-number text-success"><?php echo $stats['total_jobs']; ?></div>
                        <div class="stat-label">Total Jobs</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stat-card bg-white">
                        <div class="card-icon bg-gradient-info text-white mx-auto">
                            <i class="fas fa-file-alt"></i>
                        </div>
                        <div class="stat-number text-info"><?php echo $stats['total_applications']; ?></div>
                        <div class="stat-label">Total Applications</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stat-card bg-white">
                        <div class="card-icon bg-gradient-warning text-white mx-auto">
                            <i class="fas fa-building"></i>
                        </div>
                        <div class="stat-number text-warning"><?php echo $stats['total_departments']; ?></div>
                        <div class="stat-label">Departments</div>
                    </div>
                </div>
            </div>

            <!-- Second row: pending & action metrics -->
            <div class="row mb-4">
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stat-card bg-white">
                        <div class="card-icon bg-gradient-danger text-white mx-auto">
                            <i class="fas fa-clock"></i>
                        </div>
                        <div class="stat-number text-danger"><?php echo $stats['pending_jobs']; ?></div>
                        <div class="stat-label">Pending Jobs</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stat-card bg-white">
                        <div class="card-icon bg-gradient-secondary text-white mx-auto">
                            <i class="fas fa-user-clock"></i>
                        </div>
                        <div class="stat-number text-secondary"><?php echo $stats['pending_users']; ?></div>
                        <div class="stat-label">Pending Users</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stat-card bg-white">
                        <div class="card-icon bg-gradient-success text-white mx-auto">
                            <i class="fas fa-calendar-check"></i>
                        </div>
                        <div class="stat-number text-success"><?php echo $stats['total_interviews']; ?></div>
                        <div class="stat-label">Interviews Scheduled</div>
                    </div>
                </div>
                <div class="col-lg-3 col-md-6 mb-3">
                    <div class="stat-card bg-white">
                        <div class="card-icon bg-gradient-info text-white mx-auto">
                            <i class="fas fa-chart-line"></i>
                        </div>
                        <div class="stat-number text-info"><?php echo $stats['recent_applications']; ?></div>
                        <div class="stat-label">Applications This Week</div>
                    </div>
                </div>
            </div>

            <!-- Quick Actions with metrics inside cards -->
            <div class="row mb-4">
                <div class="col-md-4 mb-3">
                    <a href="manage_departments.php?action=add" class="text-decoration-none">
                        <div class="card dashboard-card h-100 border-0 shadow-sm">
                            <div class="card-body text-center">
                                <div class="card-icon bg-gradient-primary text-white mx-auto mb-3">
                                    <i class="fas fa-building"></i>
                                </div>
                                <h5 class="card-title text-dark">Create Department</h5>
                                <p class="text-muted mb-2"><?php echo $stats['total_departments']; ?> departments exist</p>
                                <span class="btn btn-primary btn-sm">Create New</span>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-4 mb-3">
                    <a href="manage_jobs.php?status=pending" class="text-decoration-none">
                        <div class="card dashboard-card h-100 border-0 shadow-sm">
                            <div class="card-body text-center">
                                <div class="card-icon bg-gradient-warning text-white mx-auto mb-3">
                                    <i class="fas fa-hourglass-half"></i>
                                </div>
                                <h5 class="card-title text-dark">Approve Jobs</h5>
                                <p class="text-muted mb-2"><?php echo $stats['pending_jobs']; ?> awaiting approval</p>
                                <span class="btn btn-warning btn-sm">Review Now</span>
                            </div>
                        </div>
                    </a>
                </div>
                <div class="col-md-4 mb-3">
                    <a href="analytics.php" class="text-decoration-none">
                        <div class="card dashboard-card h-100 border-0 shadow-sm">
                            <div class="card-body text-center">
                                <div class="card-icon bg-gradient-info text-white mx-auto mb-3">
                                    <i class="fas fa-chart-bar"></i>
                                </div>
                                <h5 class="card-title text-dark">View Analytics</h5>
                                <p class="text-muted mb-2">System usage trends</p>
                                <span class="btn btn-info btn-sm">View Report</span>
                            </div>
                        </div>
                    </a>
                </div>
            </div>

            <!-- Recent Activity -->
            <div class="card dashboard-card mb-4">
                <div class="card-header bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-activity me-2 text-primary"></i>Recent Activity</h5>
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
                                                echo htmlspecialchars($dept['name'] ?? '');
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

            <!-- Pending Approvals -->
            <div class="card dashboard-card">
                <div class="card-header bg-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h5 class="mb-0"><i class="fas fa-check-circle me-2 text-warning"></i>Pending Approvals</h5>
                        <div class="btn-group">
                            <a href="manage_jobs.php?status=pending" class="btn btn-outline-warning btn-sm">
                                <i class="fas fa-briefcase me-1"></i> Jobs (<?php echo $stats['pending_jobs']; ?>)
                            </a>
                            <a href="manage_users.php?status=pending" class="btn btn-outline-info btn-sm">
                                <i class="fas fa-user me-1"></i> Users (<?php echo $stats['pending_users']; ?>)
                            </a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.24/css/dataTables.bootstrap5.min.css">
    <script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.24/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
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
