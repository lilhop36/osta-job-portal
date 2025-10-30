<?php
// Start session and include required files
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/functions.php';
require_once __DIR__ . '/../includes/application_functions.php';

// Initialize secure session
init_secure_session();

// Require authentication and applicant role
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

// Generate CSRF token if not exists
if (empty($_SESSION['csrf_token'])) {
    $_SESSION['csrf_token'] = bin2hex(random_bytes(32));
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta http-equiv="Content-Security-Policy" content="default-src * 'self' data: blob:; script-src 'self' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; style-src 'self' 'unsafe-inline' https://cdn.jsdelivr.net https://cdnjs.cloudflare.com; font-src 'self' https://cdnjs.cloudflare.com data:;">
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Dashboard - OSTA Job Portal</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <?php $page_title = "Dashboard"; include '../includes/header_new.php'; ?>

    <div class="container-fluid py-4">
        <div class="row">
            <!-- Sidebar Navigation -->
            <div class="col-md-3 col-lg-2">
                <div class="card">
                    <div class="card-header bg-primary text-white">
                        <h6 class="mb-0"><i class="fas fa-tachometer-alt me-2"></i>Dashboard Menu</h6>
                    </div>
                    <div class="list-group list-group-flush">
                        <a href="dashboard.php" class="list-group-item list-group-item-action active">
                            <i class="fas fa-home me-2"></i>Overview
                        </a>
                        <a href="centralized_application.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-user-edit me-2"></i>My Profile
                        </a>
                        <a href="applications.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-briefcase me-2"></i>Job Applications
                        </a>
                        <a href="application_status.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-chart-line me-2"></i>Application Status
                        </a>
                        <a href="document_upload.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-upload me-2"></i>Documents
                        </a>
                        <a href="eligibility_checker.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-check-circle me-2"></i>Eligibility Check
                        </a>
                        <a href="../jobs.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-search me-2"></i>Browse Jobs
                        </a>
                    </div>
                </div>
            </div>
            
            <!-- Main Content -->
            <div class="col-md-9 col-lg-10">
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php 
                            echo $_SESSION['success_message']; 
                            unset($_SESSION['success_message']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
                
                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php 
                            echo $_SESSION['error_message']; 
                            unset($_SESSION['error_message']);
                        ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                <?php endif; ?>
        
        <!-- Welcome Section -->
        <div class="row mb-4">
            <div class="col-12">
                <div class="welcome-banner">
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
                                    'draft' => 'secondary',
                                    'submitted' => 'primary',
                                    'under_review' => 'info',
                                    'shortlisted' => 'success',
                                    'rejected' => 'danger',
                                    'accepted' => 'success'
                                ];
                                $status_color = $status_colors[$centralized_application['status']] ?? 'secondary';
                                ?>
                                <span class="badge bg-light text-<?= $status_color ?> fs-6 px-3 py-2">
                                    <?= ucfirst(str_replace('_', ' ', $centralized_application['status'])) ?>
                                </span>
                            <?php else: ?>
                                <a href="centralized_application.php" class="btn btn-light btn-lg">
                                    <i class="fas fa-plus me-2"></i>Start Application
                                </a>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
            </div>
        </div>

        <!-- Quick Actions -->
        <?php if (!$centralized_application): ?>
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card border-info">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-info-circle fa-2x text-info"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h5 class="card-title mb-1">Get Started with OSTA Centralized Application</h5>
                                    <p class="mb-3">Apply once and be considered for multiple positions across all OSTA departments.</p>
                                    <a href="centralized_application.php" class="btn btn-primary">
                                        <i class="fas fa-file-alt me-2"></i>Create Application
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php elseif ($centralized_application['status'] === 'draft'): ?>
            <div class="row mb-4">
                <div class="col-12">
                    <div class="card border-warning">
                        <div class="card-body">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-exclamation-triangle fa-2x text-warning"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h5 class="card-title mb-1">Complete Your Application</h5>
                                    <p class="mb-3">Your application is saved as draft. Complete and submit it to be considered for positions.</p>
                                    <div class="d-flex flex-wrap gap-2">
                                        <a href="centralized_application.php" class="btn btn-warning">
                                            <i class="fas fa-edit me-2"></i>Complete Application
                                        </a>
                                        <a href="document_upload.php" class="btn btn-outline-primary">
                                            <i class="fas fa-upload me-2"></i>Upload Documents
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        <?php endif; ?>

        <!-- Application Statistics -->
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

        <!-- Recent Applications -->
        <h3 class="h4 mb-3">Recent Applications</h3>
        <div class="card mb-4">
            <div class="card-header">
                <div class="d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Your Latest Submissions</h5>
                    <a href="applications.php" class="btn btn-sm btn-outline-primary">
                        <i class="fas fa-list-alt me-1"></i> View All
                    </a>
                </div>
            </div>
            <div class="card-body p-0">
                <div class="table-responsive">
                    <table class="table table-hover mb-0">
                        <thead class="table-light">
                            <tr>
                                <th scope="col" class="ps-3">Job Title</th>
                                <th scope="col">Department</th>
                                <th scope="col">Status</th>
                                <th scope="col">Applied On</th>
                            </tr>
                        </thead>
                        <tbody>
                            <?php if (empty($applied_jobs)): ?>
                                <tr>
                                    <td colspan="4" class="text-center py-4">No applications found</td>
                                </tr>
                            <?php else: ?>
                                <?php 
                                // Pre-fetch all department names to avoid N+1 query
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
                                        <td class="ps-3">
                                            <a href="job_details.php?id=<?= $job['id'] ?>">
                                                <?= htmlspecialchars($job['title']) ?>
                                            </a>
                                        </td>
                                        <td><?= htmlspecialchars($dept_name) ?></td>
                                        <td>
                                            <?php
                                            $status = strtolower($job['status']);
                                            $badge_class = match($status) {
                                                'pending' => 'warning',
                                                'under_review' => 'info',
                                                'shortlisted' => 'primary',
                                                'accepted' => 'success',
                                                'rejected' => 'danger',
                                                default => 'secondary'
                                            };
                                            ?>
                                            <span class="badge bg-<?= $badge_class ?>">
                                                <?= ucwords(str_replace('_', ' ', $status)) ?>
                                            </span>
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

        <!-- Job Alerts & Other Actions -->
        <div class="row">
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="fas fa-bell fa-3x text-primary"></i>
                        </div>
                        <h4>Job Alerts</h4>
                        <p>Get notified about new job openings that match your skills.</p>
                        <a href="alerts.php" class="btn btn-primary">Manage Job Alerts</a>
                    </div>
                </div>
            </div>
            <div class="col-md-6 mb-4">
                <div class="card h-100">
                    <div class="card-body text-center">
                        <div class="mb-3">
                            <i class="fas fa-search fa-3x text-success"></i>
                        </div>
                        <h4>Browse Jobs</h4>
                        <p>Explore all available job opportunities across different departments.</p>
                        <a href="../jobs.php" class="btn btn-success">Find Your Next Job</a>
                    </div>
                </div>
            </div>
        </div>

    </main>

    <?php include '../includes/footer.php'; ?>

    <!-- Only one Bootstrap JS include, just before </body> -->
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>

    <script>
    document.addEventListener('DOMContentLoaded', function() {
        const applicationForm = document.getElementById('job-application-form');
        if (applicationForm) {
            applicationForm.addEventListener('submit', function(e) {
                e.preventDefault();
                // Add CSRF token to form data
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = 'csrf_token';
                csrfInput.value = '<?= $_SESSION['csrf_token'] ?>';
                this.appendChild(csrfInput);
                // Submit form
                this.submit();
            });
        }
        // Add AJAX for status updates
        const statusUpdateLinks = document.querySelectorAll('.update-status');
        statusUpdateLinks.forEach(link => {
            link.addEventListener('click', function(e) {
                e.preventDefault();
                if (confirm('Are you sure you want to update this application status?')) {
                    const applicationId = this.dataset.id;
                    const newStatus = this.dataset.status;
                    fetch('update_application_status.php', {
                        method: 'POST',
                        headers: {
                            'Content-Type': 'application/x-www-form-urlencoded',
                        },
                        body: `id=${applicationId}&status=${newStatus}&csrf_token=<?= $_SESSION['csrf_token'] ?>`
                    })
                    .then(response => response.json())
                    .then(data => {
                        if (data.success) {
                            location.reload();
                        } else {
                            alert('Error updating status: ' + (data.message || 'Unknown error'));
                        }
                    })
                    .catch(error => {
                        console.error('Error:', error);
                        alert('An error occurred while updating the status');
                    });
                }
            });
        });

    // (Test click handlers removed to restore Bootstrap dropdown functionality)
    });
    </script>
</body>
</html>
