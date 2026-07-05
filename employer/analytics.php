<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../vendor/autoload.php';

require_role('employer', '../login.php');

$userId = (int) $_SESSION['user_id'];

// Get stats for employer's jobs
$stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM jobs WHERE created_by = ?");
$stmt->execute([$userId]);
$totalJobs = (int) $stmt->fetch()['cnt'];

$stmt = $pdo->prepare("SELECT COUNT(*) as cnt FROM jobs WHERE created_by = ? AND status = 'approved'");
$stmt->execute([$userId]);
$approvedJobs = (int) $stmt->fetch()['cnt'];

$stmt = $pdo->prepare("SELECT COUNT(DISTINCT a.id) as cnt FROM applications a INNER JOIN jobs j ON a.job_id = j.id WHERE j.created_by = ?");
$stmt->execute([$userId]);
$totalApplications = (int) $stmt->fetch()['cnt'];

$stmt = $pdo->prepare("SELECT COUNT(DISTINCT a.id) as cnt FROM applications a INNER JOIN jobs j ON a.job_id = j.id WHERE j.created_by = ? AND a.status = 'shortlisted'");
$stmt->execute([$userId]);
$shortlisted = (int) $stmt->fetch()['cnt'];

// Applications by status
$stmt = $pdo->prepare("SELECT a.status, COUNT(*) as cnt FROM applications a INNER JOIN jobs j ON a.job_id = j.id WHERE j.created_by = ? GROUP BY a.status");
$stmt->execute([$userId]);
$statusData = $stmt->fetchAll();

// Jobs by type
$stmt = $pdo->prepare("SELECT j.employment_type, COUNT(*) as cnt FROM jobs j WHERE j.created_by = ? GROUP BY j.employment_type");
$stmt->execute([$userId]);
$typeData = $stmt->fetchAll();

// Recent applications
$stmt = $pdo->prepare("SELECT a.*, u.username, j.title as job_title
                       FROM applications a
                       INNER JOIN jobs j ON a.job_id = j.id
                       INNER JOIN users u ON a.user_id = u.id
                       WHERE j.created_by = ?
                       ORDER BY a.created_at DESC LIMIT 10");
$stmt->execute([$userId]);
$recentApps = $stmt->fetchAll();

$pageTitle = 'Employer Analytics';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0"><i class="fas fa-chart-bar me-2" style="color: var(--osta-green);"></i>Analytics</h4>
        <a href="dashboard.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>Dashboard</a>
    </div>

    <!-- Stat Cards -->
    <div class="row g-3 mb-4">
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center p-3" style="border-radius: 12px;">
                <div class="fw-bold" style="font-size: 2rem; color: var(--osta-green);"><?php echo $totalJobs; ?></div>
                <div class="text-muted small">Total Jobs</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center p-3" style="border-radius: 12px;">
                <div class="fw-bold" style="font-size: 2rem; color: #28a745;"><?php echo $approvedJobs; ?></div>
                <div class="text-muted small">Active Jobs</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center p-3" style="border-radius: 12px;">
                <div class="fw-bold" style="font-size: 2rem; color: #007bff;"><?php echo $totalApplications; ?></div>
                <div class="text-muted small">Total Applications</div>
            </div>
        </div>
        <div class="col-md-3">
            <div class="card border-0 shadow-sm text-center p-3" style="border-radius: 12px;">
                <div class="fw-bold" style="font-size: 2rem; color: #ffc107;"><?php echo $shortlisted; ?></div>
                <div class="text-muted small">Shortlisted</div>
            </div>
        </div>
    </div>

    <div class="row g-4">
        <!-- Application Status Chart -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm" style="border-radius: 12px;">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Applications by Status</h6>
                    <canvas id="statusChart" height="250"></canvas>
                </div>
            </div>
        </div>

        <!-- Jobs by Type Chart -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm" style="border-radius: 12px;">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Jobs by Employment Type</h6>
                    <canvas id="typeChart" height="250"></canvas>
                </div>
            </div>
        </div>

        <!-- Recent Applications -->
        <div class="col-12">
            <div class="card border-0 shadow-sm" style="border-radius: 12px;">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Recent Applications</h6>
                    <?php if (empty($recentApps)): ?>
                        <p class="text-muted text-center py-3">No applications yet.</p>
                    <?php else: ?>
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr><th>Applicant</th><th>Job</th><th>Status</th><th>Date</th></tr>
                            </thead>
                            <tbody>
                                <?php foreach ($recentApps as $app): ?>
                                <tr>
                                    <td><?php echo htmlspecialchars($app['username']); ?></td>
                                    <td><?php echo htmlspecialchars($app['job_title']); ?></td>
                                    <td><span class="badge bg-<?php echo match($app['status']) {
                                        'shortlisted' => 'success',
                                        'rejected' => 'danger',
                                        'accepted' => 'primary',
                                        default => 'secondary'
                                    }; ?>"><?php echo ucfirst($app['status']); ?></span></td>
                                    <td><?php echo date('M d, Y', strtotime($app['created_at'])); ?></td>
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

<script src="https://cdn.jsdelivr.net/npm/chart.js@4.4.0/dist/chart.umd.min.js"></script>
<script>
const statusLabels = <?php echo json_encode(array_column($statusData, 'status')); ?>;
const statusCounts = <?php echo json_encode(array_map('intval', array_column($statusData, 'cnt'))); ?>;
const typeLabels = <?php echo json_encode(array_column($typeData, 'employment_type')); ?>;
const typeCounts = <?php echo json_encode(array_map('intval', array_column($typeData, 'cnt'))); ?>;

new Chart(document.getElementById('statusChart'), {
    type: 'doughnut',
    data: {
        labels: statusLabels.map(s => s.charAt(0).toUpperCase() + s.slice(1)),
        datasets: [{ data: statusCounts, backgroundColor: ['#6c757d','#007bff','#17a2b8','#28a745','#dc3545','#ffc107'] }]
    }
});

new Chart(document.getElementById('typeChart'), {
    type: 'pie',
    data: {
        labels: typeLabels.map(t => t.replace('_', ' ').replace(/\b\w/g, l => l.toUpperCase())),
        datasets: [{ data: typeCounts, backgroundColor: ['#28a745','#17a2b8','#ffc107','#007bff'] }]
    }
});
</script>

<?php include __DIR__ . '/../includes/footer.php'; ?>
