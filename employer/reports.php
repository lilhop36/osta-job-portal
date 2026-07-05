<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/security.php';

require_role('employer', '../login.php');
set_security_headers();

$employer_id = (int)$_SESSION['user_id'];

try {
    // Total jobs posted
    $total_jobs = $pdo->prepare("SELECT COUNT(*) as total FROM jobs WHERE created_by = ?");
    $total_jobs->execute([$employer_id]);
    $total_jobs = $total_jobs->fetch()['total'];

    // Jobs by status
    $jobs_by_status = $pdo->prepare("SELECT status, COUNT(*) as count FROM jobs WHERE created_by = ? GROUP BY status");
    $jobs_by_status->execute([$employer_id]);
    $jobs_by_status = $jobs_by_status->fetchAll();

    // Total applications received
    $total_applications = $pdo->prepare("
        SELECT COUNT(*) as total FROM applications a JOIN jobs j ON a.job_id = j.id WHERE j.created_by = ?
    ");
    $total_applications->execute([$employer_id]);
    $total_applications = $total_applications->fetch()['total'];

    // Applications by status
    $apps_by_status = $pdo->prepare("
        SELECT a.status, COUNT(*) as count FROM applications a JOIN jobs j ON a.job_id = j.id WHERE j.created_by = ? GROUP BY a.status
    ");
    $apps_by_status->execute([$employer_id]);
    $apps_by_status = $apps_by_status->fetchAll();

    // Recent applications (last 30 days)
    $recent_applications = $pdo->prepare("
        SELECT COUNT(*) as count FROM applications a JOIN jobs j ON a.job_id = j.id WHERE j.created_by = ? AND a.created_at >= DATE_SUB(NOW(), INTERVAL 30 DAY)
    ");
    $recent_applications->execute([$employer_id]);
    $recent_applications = $recent_applications->fetch()['count'];

    // Top performing jobs
    $top_jobs = $pdo->prepare("
        SELECT j.title, j.created_at, COUNT(a.id) as application_count
        FROM jobs j LEFT JOIN applications a ON j.id = a.job_id WHERE j.created_by = ?
        GROUP BY j.id ORDER BY application_count DESC LIMIT 5
    ");
    $top_jobs->execute([$employer_id]);
    $top_jobs = $top_jobs->fetchAll();

    // Monthly application trends (last 6 months)
    $monthly_trends = $pdo->prepare("
        SELECT DATE_FORMAT(a.created_at, '%Y-%m') as month, COUNT(*) as count
        FROM applications a JOIN jobs j ON a.job_id = j.id
        WHERE j.created_by = ? AND a.created_at >= DATE_SUB(NOW(), INTERVAL 6 MONTH)
        GROUP BY DATE_FORMAT(a.created_at, '%Y-%m') ORDER BY month ASC
    ");
    $monthly_trends->execute([$employer_id]);
    $monthly_trends = $monthly_trends->fetchAll();

} catch (PDOException $e) {
    error_log("Reports error: " . $e->getMessage());
}

$avg_per_job = $total_jobs > 0 ? round(($total_applications / $total_jobs), 1) : 0;
?>

<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-3">
            <?php include __DIR__ . '/../includes/employer_sidebar.php'; ?>
        </div>

        <div class="col-md-9">
            <div class="card-header bg-white mb-3">
                <h5 class="mb-0"><i class="fas fa-chart-bar me-2"></i>Reports & Analytics</h5>
            </div>

            <!-- Summary Stats -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h3 class="text-primary"><?= $total_jobs ?></h3>
                            <small class="text-muted">Total Jobs</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h3 class="text-success"><?= $total_applications ?></h3>
                            <small class="text-muted">Total Applications</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h3 class="text-info"><?= $recent_applications ?></h3>
                            <small class="text-muted">Last 30 Days</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h3 class="text-warning"><?= $avg_per_job ?></h3>
                            <small class="text-muted">Avg per Job</small>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-white">
                            <h6 class="mb-0">Jobs by Status</h6>
                        </div>
                        <div class="card-body" style="height: 250px;">
                            <canvas id="jobStatusChart"></canvas>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header bg-white">
                            <h6 class="mb-0">Applications by Status</h6>
                        </div>
                        <div class="card-body" style="height: 250px;">
                            <canvas id="appStatusChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <div class="row mb-4">
                <div class="col-12">
                    <div class="card">
                        <div class="card-header bg-white">
                            <h6 class="mb-0">Application Trends (Last 6 Months)</h6>
                        </div>
                        <div class="card-body" style="height: 250px;">
                            <canvas id="trendsChart"></canvas>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Top Performing Jobs -->
            <div class="card mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h6 class="mb-0"><i class="fas fa-trophy me-2 text-warning"></i>Top Performing Jobs</h6>
                    <button class="btn btn-sm btn-outline-success" onclick="exportToCSV()"><i class="fas fa-download me-1"></i>Export CSV</button>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>Job Title</th>
                                    <th>Posted</th>
                                    <th>Applications</th>
                                    <th>Performance</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($top_jobs)): ?>
                                    <tr><td colspan="4" class="text-center text-muted">No jobs found</td></tr>
                                <?php else: ?>
                                    <?php foreach ($top_jobs as $job): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($job['title']) ?></td>
                                            <td><?= date('M j, Y', strtotime($job['created_at'])) ?></td>
                                            <td><span class="badge bg-primary"><?= $job['application_count'] ?></span></td>
                                            <td>
                                                <?php
                                                $count = $job['application_count'];
                                                if ($count >= 10) echo '<span class="badge bg-success">Excellent</span>';
                                                elseif ($count >= 5) echo '<span class="badge bg-info">Good</span>';
                                                elseif ($count >= 1) echo '<span class="badge bg-warning">Fair</span>';
                                                else echo '<span class="badge bg-secondary">No Applications</span>';
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

<script src="https://cdn.jsdelivr.net/npm/chart.js"></script>
<script>
document.addEventListener('DOMContentLoaded', function() {
    // Job Status Chart
    const jobStatusCtx = document.getElementById('jobStatusChart');
    if (jobStatusCtx) {
        new Chart(jobStatusCtx.getContext('2d'), {
            type: 'pie',
            data: {
                labels: [<?php foreach ($jobs_by_status as $s) echo "'" . ucfirst($s['status']) . "',"; ?>],
                datasets: [{
                    data: [<?php foreach ($jobs_by_status as $s) echo $s['count'] . ","; ?>],
                    backgroundColor: ['#007bff', '#28a745', '#ffc107', '#dc3545', '#6c757d']
                }]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });
    }

    // Application Status Chart
    const appStatusCtx = document.getElementById('appStatusChart');
    if (appStatusCtx) {
        new Chart(appStatusCtx.getContext('2d'), {
            type: 'doughnut',
            data: {
                labels: [<?php foreach ($apps_by_status as $s) echo "'" . ucfirst(str_replace('_', ' ', $s['status'])) . "',"; ?>],
                datasets: [{
                    data: [<?php foreach ($apps_by_status as $s) echo $s['count'] . ","; ?>],
                    backgroundColor: ['#17a2b8', '#28a745', '#ffc107', '#dc3545', '#6f42c1', '#fd7e14']
                }]
            },
            options: { responsive: true, maintainAspectRatio: false }
        });
    }

    // Trends Chart
    const trendsCtx = document.getElementById('trendsChart');
    if (trendsCtx) {
        new Chart(trendsCtx.getContext('2d'), {
            type: 'line',
            data: {
                labels: [<?php foreach ($monthly_trends as $t) echo "'" . date('M Y', strtotime($t['month'] . '-01')) . "',"; ?>],
                datasets: [{
                    label: 'Applications',
                    data: [<?php foreach ($monthly_trends as $t) echo $t['count'] . ","; ?>],
                    borderColor: '#007bff',
                    backgroundColor: 'rgba(0, 123, 255, 0.1)',
                    fill: true,
                    tension: 0.4
                }]
            },
            options: {
                responsive: true, maintainAspectRatio: false,
                scales: { y: { beginAtZero: true } }
            }
        });
    }
});

function exportToCSV() {
    const data = [
        ['Report Type', 'Value'],
        ['Total Jobs', '<?= $total_jobs ?>'],
        ['Total Applications', '<?= $total_applications ?>'],
        ['Recent Applications (30 days)', '<?= $recent_applications ?>'],
        ['Average per Job', '<?= $avg_per_job ?>']
    ];
    let csv = "data:text/csv;charset=utf-8,";
    data.forEach(r => csv += r.join(",") + "\r\n");
    const link = document.createElement("a");
    link.setAttribute("href", encodeURI(csv));
    link.setAttribute("download", "report_<?= date('Y-m-d') ?>.csv");
    document.body.appendChild(link);
    link.click();
    document.body.removeChild(link);
}
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
