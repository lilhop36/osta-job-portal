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

$department_id = $user['department_id'];

// Get report data
try {
    // Total jobs posted
    $jobs_stmt = $pdo->prepare("SELECT COUNT(*) as total FROM jobs WHERE department_id = ?");
    $jobs_stmt->execute([$department_id]);
    $total_jobs = $jobs_stmt->fetch()['total'];

    // Jobs by status
    $status_stmt = $pdo->prepare("
        SELECT status, COUNT(*) as count 
        FROM jobs 
        WHERE department_id = ? 
        GROUP BY status
    ");
    $status_stmt->execute([$department_id]);
    $jobs_by_status = $status_stmt->fetchAll();

    // Total applications received
    $apps_stmt = $pdo->prepare("
        SELECT COUNT(*) as total 
        FROM applications a 
        JOIN jobs j ON a.job_id = j.id 
        WHERE j.department_id = ?
    ");
    $apps_stmt->execute([$department_id]);
    $total_applications = $apps_stmt->fetch()['total'];

    // Applications by status
    $app_status_stmt = $pdo->prepare("
        SELECT a.status, COUNT(*) as count 
        FROM applications a 
        JOIN jobs j ON a.job_id = j.id 
        WHERE j.department_id = ? 
        GROUP BY a.status
    ");
    $app_status_stmt->execute([$department_id]);
    $apps_by_status = $app_status_stmt->fetchAll();

    // Recent applications (last 30 days)
    $recent_apps_stmt = $pdo->prepare("
        SELECT COUNT(*) as count 
        FROM applications a 
        JOIN jobs j ON a.job_id = j.id 
        WHERE j.department_id = ? AND a.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ");
    $recent_apps_stmt->execute([$department_id]);
    $recent_applications = $recent_apps_stmt->fetch()['count'];

    // Top performing jobs (by application count)
    $top_jobs_stmt = $pdo->prepare("
        SELECT j.title, j.created_at, COUNT(a.id) as application_count
        FROM jobs j 
        LEFT JOIN applications a ON j.id = a.job_id 
        WHERE j.department_id = ? 
        GROUP BY j.id 
        ORDER BY application_count DESC 
        LIMIT 5
    ");
    $top_jobs_stmt->execute([$department_id]);
    $top_jobs = $top_jobs_stmt->fetchAll();

    // Monthly application trends (last 6 months)
    $trends_stmt = $pdo->prepare("
        SELECT 
            DATE_FORMAT(a.created_at, '%Y-%m') as month,
            COUNT(*) as count
        FROM applications a 
        JOIN jobs j ON a.job_id = j.id 
        WHERE j.department_id = ? 
        AND a.created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(a.created_at, '%Y-%m')
        ORDER BY month ASC
    ");
    $trends_stmt->execute([$department_id]);
    $monthly_trends = $trends_stmt->fetchAll();

} catch (PDOException $e) {
    error_log("Reports error: " . $e->getMessage());
    $error_message = "Error loading reports data.";
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - OSTA Job Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
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
                        <a href="manage_jobs.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-briefcase me-2"></i> Manage Jobs
                        </a>
                        <a href="manage_applications.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-users me-2"></i> Applications
                        </a>
                        <a href="profile.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-user me-2"></i> Profile
                        </a>
                        <a href="reports.php" class="list-group-item list-group-item-action active">
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
                        <h2 class="mb-1">Department Reports</h2>
                        <p class="text-muted mb-0">Department: <?php echo htmlspecialchars($user['department_name']); ?></p>
                    </div>
                    <div class="btn-group">
                        <button type="button" class="btn btn-outline-primary" onclick="window.print()">
                            <i class="fas fa-print me-2"></i>Print Report
                        </button>
                        <button type="button" class="btn btn-outline-success" onclick="exportToCSV()">
                            <i class="fas fa-download me-2"></i>Export CSV
                        </button>
                    </div>
                </div>

                <!-- Summary Cards -->
                <div class="row mb-4">
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="stat-card bg-white">
                            <div class="card-icon bg-gradient-primary text-white mx-auto">
                                <i class="fas fa-briefcase"></i>
                            </div>
                            <div class="stat-number text-primary"><?php echo $total_jobs; ?></div>
                            <div class="stat-label">Total Jobs Posted</div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="stat-card bg-white">
                            <div class="card-icon bg-gradient-success text-white mx-auto">
                                <i class="fas fa-users"></i>
                            </div>
                            <div class="stat-number text-success"><?php echo $total_applications; ?></div>
                            <div class="stat-label">Total Applications</div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="stat-card bg-white">
                            <div class="card-icon bg-gradient-info text-white mx-auto">
                                <i class="fas fa-calendar"></i>
                            </div>
                            <div class="stat-number text-info"><?php echo $recent_applications; ?></div>
                            <div class="stat-label">Last 30 Days</div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="stat-card bg-white">
                            <div class="card-icon bg-gradient-warning text-white mx-auto">
                                <i class="fas fa-percentage"></i>
                            </div>
                            <div class="stat-number text-warning">
                                <?php echo $total_jobs > 0 ? round(($total_applications / $total_jobs), 1) : 0; ?>
                            </div>
                            <div class="stat-label">Avg Apps per Job</div>
                        </div>
                    </div>
                </div>

                <!-- Charts Row -->
                <div class="row mb-4">
                    <!-- Job Status Chart -->
                    <div class="col-lg-6 mb-4">
                        <div class="card dashboard-card">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="fas fa-chart-pie me-2 text-primary"></i>Jobs by Status</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="jobStatusChart" width="400" height="300"></canvas>
                            </div>
                        </div>
                    </div>

                    <!-- Application Status Chart -->
                    <div class="col-lg-6 mb-4">
                        <div class="card dashboard-card">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="fas fa-chart-doughnut me-2 text-success"></i>Applications by Status</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="appStatusChart" width="400" height="300"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Application Trends -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card dashboard-card">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="fas fa-chart-line me-2 text-info"></i>Application Trends (Last 6 Months)</h5>
                            </div>
                            <div class="card-body">
                                <canvas id="trendsChart" width="400" height="200"></canvas>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Top Performing Jobs -->
                <div class="row mb-4">
                    <div class="col-12">
                        <div class="card dashboard-card">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="fas fa-trophy me-2 text-warning"></i>Top Performing Jobs</h5>
                            </div>
                            <div class="card-body">
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Job Title</th>
                                                <th>Posted Date</th>
                                                <th>Applications</th>
                                                <th>Performance</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php if (empty($top_jobs)): ?>
                                                <tr>
                                                    <td colspan="4" class="text-center text-muted">No jobs found</td>
                                                </tr>
                                            <?php else: ?>
                                                <?php foreach ($top_jobs as $job): ?>
                                                    <tr>
                                                        <td><?php echo htmlspecialchars($job['title']); ?></td>
                                                        <td><?php echo date('M j, Y', strtotime($job['created_at'])); ?></td>
                                                        <td>
                                                            <span class="badge bg-primary"><?php echo $job['application_count']; ?></span>
                                                        </td>
                                                        <td>
                                                            <?php
                                                            $performance = $job['application_count'];
                                                            if ($performance >= 10) {
                                                                echo '<span class="badge bg-success">Excellent</span>';
                                                            } elseif ($performance >= 5) {
                                                                echo '<span class="badge bg-info">Good</span>';
                                                            } elseif ($performance >= 1) {
                                                                echo '<span class="badge bg-warning">Fair</span>';
                                                            } else {
                                                                echo '<span class="badge bg-secondary">No Applications</span>';
                                                            }
                                                            ?>
                                                        </td>
                                                    </tr>
                                                <?php endforeach; ?>
                                            <?php endif; ?>
                                        </tbody>
                                    </table>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    // Job Status Chart
    const jobStatusCtx = document.getElementById('jobStatusChart').getContext('2d');
    const jobStatusChart = new Chart(jobStatusCtx, {
        type: 'pie',
        data: {
            labels: [<?php foreach ($jobs_by_status as $status): ?>'<?php echo ucfirst($status['status']); ?>',<?php endforeach; ?>],
            datasets: [{
                data: [<?php foreach ($jobs_by_status as $status): ?><?php echo $status['count']; ?>,<?php endforeach; ?>],
                backgroundColor: ['#007bff', '#28a745', '#ffc107', '#dc3545', '#6c757d']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    // Application Status Chart
    const appStatusCtx = document.getElementById('appStatusChart').getContext('2d');
    const appStatusChart = new Chart(appStatusCtx, {
        type: 'doughnut',
        data: {
            labels: [<?php foreach ($apps_by_status as $status): ?>'<?php echo ucfirst(str_replace('_', ' ', $status['status'])); ?>',<?php endforeach; ?>],
            datasets: [{
                data: [<?php foreach ($apps_by_status as $status): ?><?php echo $status['count']; ?>,<?php endforeach; ?>],
                backgroundColor: ['#17a2b8', '#28a745', '#ffc107', '#dc3545', '#6f42c1', '#fd7e14']
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false
        }
    });

    // Trends Chart
    const trendsCtx = document.getElementById('trendsChart').getContext('2d');
    const trendsChart = new Chart(trendsCtx, {
        type: 'line',
        data: {
            labels: [<?php foreach ($monthly_trends as $trend): ?>'<?php echo date('M Y', strtotime($trend['month'] . '-01')); ?>',<?php endforeach; ?>],
            datasets: [{
                label: 'Applications',
                data: [<?php foreach ($monthly_trends as $trend): ?><?php echo $trend['count']; ?>,<?php endforeach; ?>],
                borderColor: '#007bff',
                backgroundColor: 'rgba(0, 123, 255, 0.1)',
                fill: true,
                tension: 0.4
            }]
        },
        options: {
            responsive: true,
            maintainAspectRatio: false,
            scales: {
                y: {
                    beginAtZero: true
                }
            }
        }
    });

    // Export to CSV function
    function exportToCSV() {
        const data = [
            ['Report Type', 'Value'],
            ['Total Jobs', '<?php echo $total_jobs; ?>'],
            ['Total Applications', '<?php echo $total_applications; ?>'],
            ['Recent Applications (30 days)', '<?php echo $recent_applications; ?>'],
            ['Average Applications per Job', '<?php echo $total_jobs > 0 ? round(($total_applications / $total_jobs), 1) : 0; ?>'],
            [''],
            ['Job Status Breakdown', ''],
            <?php foreach ($jobs_by_status as $status): ?>
            ['<?php echo ucfirst($status['status']); ?>', '<?php echo $status['count']; ?>'],
            <?php endforeach; ?>
            [''],
            ['Application Status Breakdown', ''],
            <?php foreach ($apps_by_status as $status): ?>
            ['<?php echo ucfirst(str_replace('_', ' ', $status['status'])); ?>', '<?php echo $status['count']; ?>'],
            <?php endforeach; ?>
        ];

        let csvContent = "data:text/csv;charset=utf-8,";
        data.forEach(function(rowArray) {
            let row = rowArray.join(",");
            csvContent += row + "\r\n";
        });

        const encodedUri = encodeURI(csvContent);
        const link = document.createElement("a");
        link.setAttribute("href", encodedUri);
        link.setAttribute("download", "department_report_<?php echo date('Y-m-d'); ?>.csv");
        document.body.appendChild(link);
        link.click();
        document.body.removeChild(link);
    }
    </script>
    
    <?php prevent_back_navigation(); ?>
</body>
</html>
