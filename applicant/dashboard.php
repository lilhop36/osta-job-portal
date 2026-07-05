<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/application_functions.php';

require_auth('applicant');

// Check if user is logged in and has a valid user_id
if (!isset($_SESSION['user_id'])) {
    header('Location: ' . SITE_URL . '/login.php');
    exit();
}

// Initialize variables
$user_id = (int)$_SESSION['user_id'];
$error = '';
$success = '';

// Get user info with error handling
try {
    $stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
    
    if (!$user) {
        throw new Exception("User not found");
    }
    
    // Get centralized application
    $centralized_application = false;
    try {
        $app_stmt = $pdo->prepare("SELECT * FROM centralized_applications WHERE user_id = ?");
        $app_stmt->execute([$user_id]);
        $centralized_application = $app_stmt->fetch();
    } catch (PDOException $e) {
        error_log("Error fetching centralized application: " . $e->getMessage());
        $centralized_application = false;
    }

    // Get application statistics with error handling
    $app_stats = [
        'draft' => 0,
        'submitted' => 0,
        'under_review' => 0,
        'shortlisted' => 0,
        'interview_scheduled' => 0,
        'rejected' => 0,
        'accepted' => 0,
        'onboarding' => 0
    ];

    if ($centralized_application) {
        try {
            $app_stats = get_application_statistics($user_id);
            if (!is_array($app_stats)) {
                throw new Exception("Invalid application statistics data");
            }
        } catch (Exception $e) {
            error_log("Error getting application statistics: " . $e->getMessage());
            // Use default stats if there's an error
        }
    }

    // Get upcoming events with pagination
    $upcoming_events = [];
    $upcoming_interviews = [];
    $page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
    $limit = 5;
    $offset = ($page - 1) * $limit;

    if ($centralized_application) {
        try {
            // Get total count for pagination
            $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM interviews WHERE application_id = ? AND scheduled_date >= CURDATE()");
            $count_stmt->execute([$centralized_application['id']]);
            $total_events = $count_stmt->fetchColumn();
            $total_pages = ceil($total_events / $limit);

            // Get paginated results
            $interview_stmt = $pdo->prepare("SELECT * FROM interviews 
                                          WHERE application_id = ? AND scheduled_date >= CURDATE() 
                                          ORDER BY scheduled_date, start_time 
                                          LIMIT ? OFFSET ?");
            $interview_stmt->execute([$centralized_application['id'], $limit, $offset]);
            $upcoming_interviews = $interview_stmt->fetchAll();
        } catch (PDOException $e) {
            error_log("Error fetching upcoming interviews: " . $e->getMessage());
            $upcoming_interviews = [];
            $total_pages = 1;
        }
    }
    
    $upcoming_events = $upcoming_interviews;

    // Get recent notifications with error handling
    $recent_notifications = [];
    try {
        $notif_stmt = $pdo->prepare("SELECT * FROM notifications 
                                    WHERE user_id = ? 
                                    ORDER BY created_at DESC 
                                    LIMIT 5");
        $notif_stmt->execute([$user_id]);
        $recent_notifications = $notif_stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error fetching notifications: " . $e->getMessage());
    }

    // Get saved jobs with error handling
    $saved_jobs = [];
    $applied_jobs = [];
    
    try {
        // Get saved jobs
        $saved_stmt = $pdo->prepare("SELECT j.* FROM jobs j 
                                    JOIN saved_jobs sj ON j.id = sj.job_id 
                                    WHERE sj.user_id = ?
                                    ORDER BY sj.created_at DESC 
                                    LIMIT 5");
        $saved_stmt->execute([$user_id]);
        $saved_jobs = $saved_stmt->fetchAll();

        // Get applied jobs with status
        $applied_stmt = $pdo->prepare("SELECT j.*, a.status, a.applied_at 
                                      FROM jobs j
                                      JOIN applications a ON j.id = a.job_id
                                      WHERE a.user_id = ?
                                      ORDER BY a.applied_at DESC 
                                      LIMIT 5");
        $applied_stmt->execute([$user_id]);
        $applied_jobs = $applied_stmt->fetchAll();
    } catch (PDOException $e) {
        error_log("Error fetching saved/applied jobs: " . $e->getMessage());
    }

} catch (Exception $e) {
    error_log("Dashboard error: " . $e->getMessage());
    $error = "An error occurred while loading the dashboard. Please try again later.";
}

// Ensure CSRF token is generated
generate_csrf_token();
$page_title = "Dashboard";
?>

<?php include '../includes/header.php'; ?>

    <div class="container-fluid mt-4">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3">
                <?php include '../includes/applicant_sidebar.php'; ?>
            </div>

            <!-- Main Content -->
            <div class="col-md-9">
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>
                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['error_message']; unset($_SESSION['error_message']); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <!-- Single Greeting -->
                <div class="welcome-banner mb-4">
                    <div class="d-flex flex-column flex-md-row justify-content-between align-items-start align-items-md-center">
                        <div class="mb-3 mb-md-0">
                            <h2 class="mb-1">Welcome back, <?= htmlspecialchars($user['name'] ?? $user['username'] ?? 'User') ?>!</h2>
                            <p class="mb-0">Manage your OSTA job applications and track your progress</p>
                        </div>
                        <div class="text-start text-md-end">
                            <?php if ($centralized_application): ?>
                                <div class="mb-2">
                                    <span class="badge bg-light text-dark fs-6">
                                        Application: <?= htmlspecialchars($centralized_application['application_number']) ?>
                                    </span>
                                </div>
                                <?php
                                $status_colors = [
                                    'draft' => 'secondary', 'submitted' => 'primary', 'under_review' => 'info',
                                    'shortlisted' => 'success', 'rejected' => 'danger', 'accepted' => 'success'
                                ];
                                $status_color = $status_colors[$centralized_application['status']] ?? 'secondary';
                                ?>
                                <span class="badge bg-light text-<?= $status_color ?> fs-6 px-3 py-2">
                                    <?= ucfirst(str_replace('_', ' ', $centralized_application['status'])) ?>
                                </span>
                            <?php else: ?>
                                <a href="centralized_application.php" class="btn btn-primary">
                                    <i class="fas fa-plus me-2"></i>Start Application
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>

                <!-- Draft warning -->
                <?php if (!$centralized_application): ?>
                    <div class="alert alert-info d-flex align-items-center mb-4">
                        <i class="fas fa-info-circle me-3 fa-lg"></i>
                        <div class="flex-grow-1">
                            <strong>Get Started:</strong> Apply once and be considered for multiple positions across all OSTA departments.
                            <a href="centralized_application.php" class="alert-link ms-2">Create Application &rarr;</a>
                        </div>
                    </div>
                <?php elseif ($centralized_application['status'] === 'draft'): ?>
                    <div class="alert alert-warning d-flex align-items-center mb-4">
                        <i class="fas fa-exclamation-triangle me-3 fa-lg"></i>
                        <div class="flex-grow-1">
                            <strong>Complete Your Application:</strong> Your application is saved as draft.
                            <a href="centralized_application.php" class="alert-link ms-2">Complete & Submit &rarr;</a>
                        </div>
                    </div>
                <?php endif; ?>

                <!-- Stats: unified 4-column grid, every number labeled -->
                <div class="row mb-4">
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="stat-card bg-white">
                            <div class="card-icon bg-gradient-primary text-white mx-auto">
                                <i class="fas fa-file-alt"></i>
                            </div>
                            <div class="stat-number text-primary"><?= $centralized_application ? 1 : 0 ?></div>
                            <div class="stat-label">Centralized Application</div>
                            <?php if ($centralized_application): ?>
                                <small class="badge bg-<?= get_status_color($centralized_application['status']) ?>">
                                    <?= ucfirst(str_replace('_', ' ', $centralized_application['status'])) ?>
                                </small>
                            <?php endif; ?>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="stat-card bg-white">
                            <div class="card-icon bg-gradient-success text-white mx-auto">
                                <i class="fas fa-check-circle"></i>
                            </div>
                            <div class="stat-number text-success"><?= $app_stats['submitted'] + $app_stats['under_review'] + $app_stats['shortlisted'] + $app_stats['accepted'] ?></div>
                            <div class="stat-label">Applications Submitted</div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="stat-card bg-white">
                            <div class="card-icon bg-gradient-warning text-white mx-auto">
                                <i class="fas fa-clock"></i>
                            </div>
                            <div class="stat-number text-warning"><?= $app_stats['under_review'] + $app_stats['shortlisted'] ?></div>
                            <div class="stat-label">Under Review</div>
                        </div>
                    </div>
                    <div class="col-lg-3 col-md-6 mb-3">
                        <div class="stat-card bg-white">
                            <div class="card-icon bg-gradient-info text-white mx-auto">
                                <i class="fas fa-star"></i>
                            </div>
                            <div class="stat-number text-info"><?= $app_stats['shortlisted'] + $app_stats['interview_scheduled'] ?></div>
                            <div class="stat-label">Shortlisted</div>
                        </div>
                    </div>
                </div>

                <!-- Quick Actions with actionable metrics -->
                <div class="row mb-4">
                    <div class="col-md-4 mb-3">
                        <a href="../jobs.php" class="text-decoration-none">
                            <div class="card dashboard-card h-100 border-0 shadow-sm">
                                <div class="card-body text-center">
                                    <div class="card-icon bg-gradient-success text-white mx-auto mb-3">
                                        <i class="fas fa-search"></i>
                                    </div>
                                    <h5 class="card-title text-dark">Browse Jobs</h5>
                                    <p class="text-muted mb-2">Explore all available opportunities</p>
                                    <span class="btn btn-success btn-sm">Find Jobs</span>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-4 mb-3">
                        <a href="document_upload.php" class="text-decoration-none">
                            <div class="card dashboard-card h-100 border-0 shadow-sm">
                                <div class="card-body text-center">
                                    <div class="card-icon bg-gradient-primary text-white mx-auto mb-3">
                                        <i class="fas fa-upload"></i>
                                    </div>
                                    <h5 class="card-title text-dark">Upload Documents</h5>
                                    <p class="text-muted mb-2">Keep your files up to date</p>
                                    <span class="btn btn-primary btn-sm">Upload</span>
                                </div>
                            </div>
                        </a>
                    </div>
                    <div class="col-md-4 mb-3">
                        <a href="alerts.php" class="text-decoration-none">
                            <div class="card dashboard-card h-100 border-0 shadow-sm">
                                <div class="card-body text-center">
                                    <div class="card-icon bg-gradient-warning text-white mx-auto mb-3">
                                        <i class="fas fa-bell"></i>
                                    </div>
                                    <h5 class="card-title text-dark">Job Alerts</h5>
                                    <p class="text-muted mb-2">Get notified about new openings</p>
                                    <span class="btn btn-warning btn-sm">Manage Alerts</span>
                                </div>
                            </div>
                        </a>
                    </div>
                </div>

                <!-- Recent Applications -->
                <div class="card dashboard-card mb-4">
                    <div class="card-header bg-white">
                        <div class="d-flex justify-content-between align-items-center">
                            <h5 class="mb-0"><i class="fas fa-file-alt me-2 text-primary"></i>Recent Applications</h5>
                            <a href="applications.php" class="btn btn-sm btn-outline-primary">View All</a>
                        </div>
                    </div>
                    <div class="card-body p-0">
                        <div class="table-responsive">
                            <table class="table table-hover mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th class="ps-3">Job Title</th>
                                        <th>Department</th>
                                        <th>Status</th>
                                        <th>Applied On</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php if (empty($applied_jobs)): ?>
                                        <tr><td colspan="4" class="text-center py-4 text-muted">No applications yet</td></tr>
                                    <?php else: ?>
                                        <?php
                                        $dept_ids = array_column($applied_jobs, 'department_id');
                                        $dept_names = [];
                                        if (!empty($dept_ids)) {
                                            $dept_stmt = $pdo->prepare("SELECT id, name FROM departments WHERE id IN (" .
                                                implode(',', array_fill(0, count($dept_ids), '?')) . ")");
                                            $dept_stmt->execute($dept_ids);
                                            $dept_names = $dept_stmt->fetchAll(PDO::FETCH_KEY_PAIR);
                                        }
                                        foreach ($applied_jobs as $job):
                                            $dept_name = $dept_names[$job['department_id']] ?? 'N/A';
                                        ?>
                                            <tr>
                                                <td class="ps-3"><a href="job_details.php?id=<?= $job['id'] ?>"><?= htmlspecialchars($job['title']) ?></a></td>
                                                <td><?= htmlspecialchars($dept_name) ?></td>
                                                <td>
                                                    <?php
                                                    $status = strtolower($job['status']);
                                                    $badge_class = match($status) {
                                                        'pending' => 'warning', 'under_review' => 'info',
                                                        'shortlisted' => 'primary', 'accepted' => 'success',
                                                        'rejected' => 'danger', default => 'secondary'
                                                    };
                                                    ?>
                                                    <span class="badge bg-<?= $badge_class ?>"><?= ucwords(str_replace('_', ' ', $status)) ?></span>
                                                </td>
                                                <td><?= date('M j, Y', strtotime($job['applied_at'])) ?></td>
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

<?php include '../includes/footer.php'; ?>
