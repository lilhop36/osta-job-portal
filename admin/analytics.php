<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

// Require admin role
require_role('admin', '../login.php');

// Get analytics data
// User statistics
$user_stats = [
    'total_users' => $pdo->query("SELECT COUNT(*) as count FROM users")->fetch()['count'],
    'active_users' => $pdo->query("SELECT COUNT(*) as count FROM users WHERE status = 'active'")->fetch()['count'],
    'pending_users' => $pdo->query("SELECT COUNT(*) as count FROM users WHERE status = 'pending'")->fetch()['count'],
    'applicants' => $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'applicant'")->fetch()['count'],
    'employers' => $pdo->query("SELECT COUNT(*) as count FROM users WHERE role = 'employer'")->fetch()['count']
];

// Job statistics
$job_stats = [
    'total_jobs' => $pdo->query("SELECT COUNT(*) as count FROM jobs")->fetch()['count'],
    'active_jobs' => $pdo->query("SELECT COUNT(*) as count FROM jobs WHERE status = 'approved' AND deadline >= CURDATE()")->fetch()['count'],
    'expired_jobs' => $pdo->query("SELECT COUNT(*) as count FROM jobs WHERE status = 'approved' AND deadline < CURDATE()")->fetch()['count'],
    'pending_jobs' => $pdo->query("SELECT COUNT(*) as count FROM jobs WHERE status = 'pending'")->fetch()['count']
];

// Application statistics
$app_stats = [
    'total_applications' => $pdo->query("SELECT COUNT(*) as count FROM applications")->fetch()['count'],
    'pending_applications' => $pdo->query("SELECT COUNT(*) as count FROM applications WHERE status = 'pending'")->fetch()['count'],
    'shortlisted_applications' => $pdo->query("SELECT COUNT(*) as count FROM applications WHERE status = 'shortlisted'")->fetch()['count'],
    'rejected_applications' => $pdo->query("SELECT COUNT(*) as count FROM applications WHERE status = 'rejected'")->fetch()['count']
];

// Department statistics
$dept_stats = [
    'total_departments' => $pdo->query("SELECT COUNT(*) as count FROM departments")->fetch()['count'],
    'departments_with_jobs' => $pdo->query("
        SELECT COUNT(DISTINCT department_id) as count 
        FROM jobs 
        WHERE status = 'approved'
    ")->fetch()['count']
];

// Get recent trends
$recent_trends = $pdo->query("
    SELECT 
        DATE(created_at) as date,
        COUNT(*) as count
    FROM applications
    WHERE created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY DATE(created_at)
    ORDER BY date
")->fetchAll(PDO::FETCH_ASSOC);

// Get top departments by applications
$top_departments = $pdo->query("
    SELECT 
        d.name as department_name,
        COUNT(a.id) as application_count
    FROM applications a
    JOIN jobs j ON a.job_id = j.id
    JOIN departments d ON j.department_id = d.id
    WHERE a.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY d.name
    ORDER BY application_count DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);

// Get top jobs by applications
$top_jobs = $pdo->query("
    SELECT 
        j.title,
        COUNT(a.id) as application_count
    FROM applications a
    JOIN jobs j ON a.job_id = j.id
    WHERE a.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    GROUP BY j.title
    ORDER BY application_count DESC
    LIMIT 5
")->fetchAll(PDO::FETCH_ASSOC);
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Analytics - OSTA Job Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container-fluid mt-4">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h3 class="mb-0">Admin Menu</h3>
                    </div>
                    <div class="list-group list-group-flush">
                        <a href="dashboard.php" class="list-group-item list-group-item-action">
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
                        <a href="analytics.php" class="list-group-item list-group-item-action active">
                            <i class="bi bi-graph-up me-2"></i> Analytics
                        </a>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9">
                <!-- Overview Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Users</h5>
                                <h2 class="mb-0"><?php echo $user_stats['total_users']; ?></h2>
                                <small class="text-white-50">Active: <?php echo $user_stats['active_users']; ?></small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title">Active Jobs</h5>
                                <h2 class="mb-0"><?php echo $job_stats['active_jobs']; ?></h2>
                                <small class="text-white-50">Total: <?php echo $job_stats['total_jobs']; ?></small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h5 class="card-title">Applications</h5>
                                <h2 class="mb-0"><?php echo $app_stats['total_applications']; ?></h2>
                                <small class="text-white-50">Pending: <?php echo $app_stats['pending_applications']; ?></small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <h5 class="card-title">Departments</h5>
                                <h2 class="mb-0"><?php echo $dept_stats['total_departments']; ?></h2>
                                <small class="text-white-50">With Jobs: <?php echo $dept_stats['departments_with_jobs']; ?></small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Application Trends Chart -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h3 class="mb-0">Application Trends (Last 30 Days)</h3>
                    </div>
                    <div class="card-body">
                        <canvas id="applicationTrendsChart"></canvas>
                    </div>
                </div>

                <!-- Top Departments -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h3 class="mb-0">Top Departments by Applications</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Department</th>
                                        <th>Applications</th>
                                        <th>Percentage</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($top_departments as $dept): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($dept['department_name']); ?></td>
                                            <td><?php echo $dept['application_count']; ?></td>
                                            <td>
                                                <?php 
                                                $percentage = ($dept['application_count'] / $app_stats['total_applications']) * 100;
                                                echo number_format($percentage, 1) . '%';
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- Top Jobs -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h3 class="mb-0">Top Jobs by Applications</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Job Title</th>
                                        <th>Applications</th>
                                        <th>Percentage</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($top_jobs as $job): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($job['title']); ?></td>
                                            <td><?php echo $job['application_count']; ?></td>
                                            <td>
                                                <?php 
                                                $percentage = ($job['application_count'] / $app_stats['total_applications']) * 100;
                                                echo number_format($percentage, 1) . '%';
                                                ?>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    </div>
                </div>

                <!-- User Statistics -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h3 class="mb-0">User Statistics</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h5 class="card-title">User Types</h5>
                                        <div class="progress">
                                            <div class="progress-bar bg-primary" 
                                                 role="progressbar" 
                                                 style="width: <?php echo ($user_stats['applicants'] / $user_stats['total_users']) * 100; ?>%">
                                                Applicants: <?php echo $user_stats['applicants']; ?>
                                            </div>
                                            <div class="progress-bar bg-success" 
                                                 role="progressbar" 
                                                 style="width: <?php echo ($user_stats['employers'] / $user_stats['total_users']) * 100; ?>%">
                                                Employers: <?php echo $user_stats['employers']; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h5 class="card-title">User Status</h5>
                                        <div class="progress">
                                            <div class="progress-bar bg-success" 
                                                 role="progressbar" 
                                                 style="width: <?php echo ($user_stats['active_users'] / $user_stats['total_users']) * 100; ?>%">
                                                Active: <?php echo $user_stats['active_users']; ?>
                                            </div>
                                            <div class="progress-bar bg-warning" 
                                                 role="progressbar" 
                                                 style="width: <?php echo ($user_stats['pending_users'] / $user_stats['total_users']) * 100; ?>%">
                                                Pending: <?php echo $user_stats['pending_users']; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-4">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h5 class="card-title">Job Status</h5>
                                        <div class="progress">
                                            <div class="progress-bar bg-success" 
                                                 role="progressbar" 
                                                 style="width: <?php echo ($job_stats['active_jobs'] / $job_stats['total_jobs']) * 100; ?>%">
                                                Active: <?php echo $job_stats['active_jobs']; ?>
                                            </div>
                                            <div class="progress-bar bg-danger" 
                                                 role="progressbar" 
                                                 style="width: <?php echo ($job_stats['expired_jobs'] / $job_stats['total_jobs']) * 100; ?>%">
                                                Expired: <?php echo $job_stats['expired_jobs']; ?>
                                            </div>
                                            <div class="progress-bar bg-warning" 
                                                 role="progressbar" 
                                                 style="width: <?php echo ($job_stats['pending_jobs'] / $job_stats['total_jobs']) * 100; ?>%">
                                                Pending: <?php echo $job_stats['pending_jobs']; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Application Statistics -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="mb-0">Application Statistics</h3>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-4">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h5 class="card-title">Application Status</h5>
                                        <div class="progress">
                                            <div class="progress-bar bg-warning" 
                                                 role="progressbar" 
                                                 style="width: <?php echo ($app_stats['pending_applications'] / $app_stats['total_applications']) * 100; ?>%">
                                                Pending: <?php echo $app_stats['pending_applications']; ?>
                                            </div>
                                            <div class="progress-bar bg-success" 
                                                 role="progressbar" 
                                                 style="width: <?php echo ($app_stats['shortlisted_applications'] / $app_stats['total_applications']) * 100; ?>%">
                                                Shortlisted: <?php echo $app_stats['shortlisted_applications']; ?>
                                            </div>
                                            <div class="progress-bar bg-danger" 
                                                 role="progressbar" 
                                                 style="width: <?php echo ($app_stats['rejected_applications'] / $app_stats['total_applications']) * 100; ?>%">
                                                Rejected: <?php echo $app_stats['rejected_applications']; ?>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                            <div class="col-md-8">
                                <div class="card bg-light">
                                    <div class="card-body">
                                        <h5 class="card-title">Application Status Distribution</h5>
                                        <canvas id="applicationStatusChart"></canvas>
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
    <script>
        // Application Trends Chart
        const applicationTrendsCtx = document.getElementById('applicationTrendsChart').getContext('2d');
        new Chart(applicationTrendsCtx, {
            type: 'line',
            data: {
                labels: <?php echo json_encode(array_column($recent_trends, 'date')); ?>,
                datasets: [{
                    label: 'Applications',
                    data: <?php echo json_encode(array_column($recent_trends, 'count')); ?>,
                    borderColor: 'rgb(75, 192, 192)',
                    tension: 0.1
                }]
            },
            options: {
                responsive: true,
                scales: {
                    y: {
                        beginAtZero: true
                    }
                }
            }
        });

        // Application Status Distribution Chart
        const applicationStatusCtx = document.getElementById('applicationStatusChart').getContext('2d');
        new Chart(applicationStatusCtx, {
            type: 'doughnut',
            data: {
                labels: ['Pending', 'Shortlisted', 'Rejected'],
                datasets: [{
                    data: [
                        <?php echo $app_stats['pending_applications']; ?>,
                        <?php echo $app_stats['shortlisted_applications']; ?>,
                        <?php echo $app_stats['rejected_applications']; ?>
                    ],
                    backgroundColor: [
                        'rgb(255, 193, 7)',
                        'rgb(75, 192, 192)',
                        'rgb(255, 99, 132)'
                    ]
                }]
            },
            options: {
                responsive: true,
                plugins: {
                    legend: {
                        position: 'top',
                    }
                }
            }
        });
    </script>
</body>
</html>
