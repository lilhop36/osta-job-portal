<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

// Require admin role
require_role('admin', '../login.php');

// Handle report generation
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $report_type = sanitize($_POST['report_type']);
    $start_date = sanitize($_POST['start_date']);
    $end_date = sanitize($_POST['end_date']);
    $format = sanitize($_POST['format']);
    
    // Generate report based on type
    switch ($report_type) {
        case 'jobs':
            $query = "SELECT j.*, d.name as department_name, 
                     (SELECT COUNT(*) FROM applications WHERE job_id = j.id) as application_count 
                     FROM jobs j 
                     JOIN departments d ON j.department_id = d.id 
                     WHERE j.created_at BETWEEN ? AND ?";
            break;
            
        case 'applications':
            $query = "SELECT a.*, u.username, j.title, d.name as department_name 
                     FROM applications a 
                     JOIN users u ON a.user_id = u.id 
                     JOIN jobs j ON a.job_id = j.id 
                     JOIN departments d ON j.department_id = d.id 
                     WHERE a.created_at BETWEEN ? AND ?";
            break;
            
        case 'users':
            $query = "SELECT u.*, d.name as department_name 
                     FROM users u 
                     LEFT JOIN departments d ON u.department_id = d.id 
                     WHERE u.created_at BETWEEN ? AND ?";
            break;
    }
    
    $stmt = $pdo->prepare($query);
    $stmt->execute([$start_date, $end_date]);
    $results = $stmt->fetchAll();
    
    // Generate CSV file
    if ($format === 'csv') {
        $filename = 'osta_job_portal_report_' . date('Y-m-d') . '.csv';
        header('Content-Type: text/csv');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        $output = fopen('php://output', 'w');
        
        switch ($report_type) {
            case 'jobs':
                fputcsv($output, ['Title', 'Department', 'Applications', 'Status']);
                foreach ($results as $row) {
                    fputcsv($output, [$row['title'], $row['department_name'], $row['application_count'], $row['status']]);
                }
                break;
                
            case 'applications':
                fputcsv($output, ['Applicant', 'Job Title', 'Department', 'Status', 'Date']);
                foreach ($results as $row) {
                    fputcsv($output, [$row['username'], $row['title'], $row['department_name'], $row['status'], date('M j, Y', strtotime($row['created_at']))]);
                }
                break;
                
            case 'users':
                fputcsv($output, ['Username', 'Email', 'Role', 'Department', 'Status']);
                foreach ($results as $row) {
                    fputcsv($output, [$row['username'], $row['email'], $row['role'], $row['department_name'], $row['status']]);
                }
                break;
        }
        
        fclose($output);
        exit();
    } elseif ($format === 'txt') {
        $filename = 'osta_job_portal_report_' . date('Y-m-d') . '.txt';
        header('Content-Type: text/plain');
        header('Content-Disposition: attachment; filename="' . $filename . '"');
        
        switch ($report_type) {
            case 'jobs':
                echo "OSTA Job Portal - Jobs Report\n";
                echo "Generated on: " . date('M j, Y') . "\n";
                echo "Period: " . date('M j, Y', strtotime($start_date)) . " to " . date('M j, Y', strtotime($end_date)) . "\n\n";
                echo str_pad("Title", 30) . str_pad("Department", 25) . str_pad("Applications", 15) . "Status\n";
                echo str_repeat("-", 85) . "\n";
                foreach ($results as $row) {
                    echo str_pad(substr($row['title'], 0, 28), 30) . 
                         str_pad(substr($row['department_name'], 0, 23), 25) . 
                         str_pad($row['application_count'], 15) . 
                         $row['status'] . "\n";
                }
                break;
                
            case 'applications':
                echo "OSTA Job Portal - Applications Report\n";
                echo "Generated on: " . date('M j, Y') . "\n";
                echo "Period: " . date('M j, Y', strtotime($start_date)) . " to " . date('M j, Y', strtotime($end_date)) . "\n\n";
                echo str_pad("Applicant", 25) . str_pad("Job Title", 30) . str_pad("Department", 20) . str_pad("Status", 15) . "Date\n";
                echo str_repeat("-", 100) . "\n";
                foreach ($results as $row) {
                    echo str_pad(substr($row['username'], 0, 23), 25) . 
                         str_pad(substr($row['title'], 0, 28), 30) . 
                         str_pad(substr($row['department_name'], 0, 18), 20) . 
                         str_pad($row['status'], 15) . 
                         date('M j, Y', strtotime($row['created_at'])) . "\n";
                }
                break;
                
            case 'users':
                echo "OSTA Job Portal - Users Report\n";
                echo "Generated on: " . date('M j, Y') . "\n";
                echo "Period: " . date('M j, Y', strtotime($start_date)) . " to " . date('M j, Y', strtotime($end_date)) . "\n\n";
                echo str_pad("Username", 20) . str_pad("Email", 30) . str_pad("Role", 15) . str_pad("Department", 20) . "Status\n";
                echo str_repeat("-", 90) . "\n";
                foreach ($results as $row) {
                    echo str_pad(substr($row['username'], 0, 18), 20) . 
                         str_pad(substr($row['email'], 0, 28), 30) . 
                         str_pad($row['role'], 15) . 
                         str_pad(substr($row['department_name'] ?? 'N/A', 0, 18), 20) . 
                         $row['status'] . "\n";
                }
                break;
        }
        exit();
    }
}

// Get statistics for dashboard
$stats = [
    'total_jobs' => $pdo->query("SELECT COUNT(*) as count FROM jobs")->fetch()['count'],
    'active_jobs' => $pdo->query("SELECT COUNT(*) as count FROM jobs WHERE status = 'approved' AND deadline >= CURDATE()")->fetch()['count'],
    'total_applications' => $pdo->query("SELECT COUNT(*) as count FROM applications")->fetch()['count'],
    'total_users' => $pdo->query("SELECT COUNT(*) as count FROM users")->fetch()['count'],
    'active_users' => $pdo->query("SELECT COUNT(*) as count FROM users WHERE status = 'active'")->fetch()['count'],
    'departments' => $pdo->query("SELECT COUNT(*) as count FROM departments")->fetch()['count']
];
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Reports - OSTA Job Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
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
                        <a href="reports.php" class="list-group-item list-group-item-action active">
                            <i class="bi bi-file-earmark-text me-2"></i> Reports
                        </a>
                        <a href="settings.php" class="list-group-item list-group-item-action">
                            <i class="bi bi-gear me-2"></i> Settings
                        </a>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9">
                <!-- Statistics Cards -->
                <div class="row mb-4">
                    <div class="col-md-3">
                        <div class="card bg-primary text-white">
                            <div class="card-body">
                                <h5 class="card-title">Total Jobs</h5>
                                <h2 class="mb-0"><?php echo $stats['total_jobs']; ?></h2>
                                <small class="text-white-50">Active: <?php echo $stats['active_jobs']; ?></small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-success text-white">
                            <div class="card-body">
                                <h5 class="card-title">Applications</h5>
                                <h2 class="mb-0"><?php echo $stats['total_applications']; ?></h2>
                                <small class="text-white-50">Total Applications</small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-info text-white">
                            <div class="card-body">
                                <h5 class="card-title">Users</h5>
                                <h2 class="mb-0"><?php echo $stats['total_users']; ?></h2>
                                <small class="text-white-50">Active: <?php echo $stats['active_users']; ?></small>
                            </div>
                        </div>
                    </div>
                    <div class="col-md-3">
                        <div class="card bg-warning text-white">
                            <div class="card-body">
                                <h5 class="card-title">Departments</h5>
                                <h2 class="mb-0"><?php echo $stats['departments']; ?></h2>
                                <small class="text-white-50">Registered Departments</small>
                            </div>
                        </div>
                    </div>
                </div>

                <!-- Error Messages -->
                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show mb-4" role="alert">
                        <?php 
                        echo $_SESSION['error_message'];
                        unset($_SESSION['error_message']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>

                <!-- Report Generator -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h3 class="mb-0">Generate Report</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-4 mb-3">
                                    <label for="report_type" class="form-label">Report Type *</label>
                                    <select class="form-select" id="report_type" name="report_type" required>
                                        <option value="jobs">Jobs Report</option>
                                        <option value="applications">Applications Report</option>
                                        <option value="users">Users Report</option>
                                    </select>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="start_date" class="form-label">Start Date *</label>
                                    <input type="date" class="form-control" id="start_date" name="start_date" 
                                           value="<?php echo date('Y-m-d', strtotime('-30 days')); ?>" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label for="end_date" class="form-label">End Date *</label>
                                    <input type="date" class="form-control" id="end_date" name="end_date" 
                                           value="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="format" class="form-label">Format *</label>
                                    <select class="form-select" id="format" name="format" required>
                                        <option value="csv">CSV (Excel compatible)</option>
                                        <option value="txt">Plain Text (TXT)</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="filename" class="form-label">Filename</label>
                                    <input type="text" class="form-control" id="filename" name="filename" 
                                           value="osta_job_portal_report" readonly>
                                </div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-download me-1"></i> Generate Report
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Recent Reports -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="mb-0">Recent Reports</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Report Type</th>
                                        <th>Period</th>
                                        <th>Format</th>
                                        <th>Created At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php
                                    // Get recent reports (in real implementation, store reports in database)
                                    $recent_reports = [
                                        ['type' => 'jobs', 'period' => '2023-01-01 - 2023-01-31', 'format' => 'pdf', 'created_at' => '2023-02-01 10:00:00'],
                                        ['type' => 'applications', 'period' => '2023-01-01 - 2023-01-31', 'format' => 'csv', 'created_at' => '2023-02-01 10:00:00'],
                                        ['type' => 'users', 'period' => '2023-01-01 - 2023-01-31', 'format' => 'pdf', 'created_at' => '2023-02-01 10:00:00']
                                    ];
                                    
                                    foreach ($recent_reports as $report):
                                    ?>
                                        <tr>
                                            <td><?php echo ucfirst($report['type']); ?></td>
                                            <td><?php echo $report['period']; ?></td>
                                            <td><?php echo strtoupper($report['format']); ?></td>
                                            <td><?php echo date('M j, Y H:i', strtotime($report['created_at'])); ?></td>
                                            <td>
                                                <a href="#" class="btn btn-sm btn-primary">
                                                    <i class="bi bi-download"></i> Download
                                                </a>
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

    <?php include '../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add event listener for date inputs to ensure valid range
        document.getElementById('start_date').addEventListener('change', function() {
            const endDate = document.getElementById('end_date');
            endDate.min = this.value;
        });
        
        document.getElementById('end_date').addEventListener('change', function() {
            const startDate = document.getElementById('start_date');
            startDate.max = this.value;
        });
    </script>
</body>
</html>
