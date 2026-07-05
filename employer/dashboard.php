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