<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/security.php';

// Require employer role
require_role('employer', '../login.php');

// Set security headers
set_security_headers();

// Get employer's user ID
$employer_id = $_SESSION['user_id'];

// Get job ID from query parameter
$job_id = isset($_GET['job_id']) ? (int)$_GET['job_id'] : 0;

// Initialize variables
$job = null;
$applicants = [];
$error = '';

// Debug: Log the job_id and employer_id
error_log("Debug - Job ID: $job_id, Employer ID: $employer_id");

// Get job details to verify ownership
if ($job_id > 0) {
    try {
        // First, check if job exists and get basic info
        $stmt = $pdo->prepare("
            SELECT j.*, d.name as department_name, u.id as creator_id
            FROM jobs j 
            JOIN departments d ON j.department_id = d.id
            LEFT JOIN users u ON j.created_by = u.id 
            WHERE j.id = ?
        ");
        $stmt->execute([$job_id]);
        $job = $stmt->fetch();
        
        error_log("Debug - Job found: " . ($job ? 'Yes' : 'No'));
        if ($job) {
            error_log("Debug - Job creator: " . ($job['created_by'] ?? 'Not set') . ", Current user: $employer_id");
        }

        // Get employer's department
        $emp_stmt = $pdo->prepare("SELECT department_id FROM users WHERE id = ? AND role = 'employer'");
        $emp_stmt->execute([$employer_id]);
        $employer_dept = $emp_stmt->fetch();
        
        // Verify access: employer can view applicants for jobs in their department OR jobs they created
        if ($job && $employer_dept && 
            ($job['department_id'] == $employer_dept['department_id'] || $job['created_by'] == $employer_id)) {
            
            // Get applicants for this job - Fixed query with correct column references
            $stmt = $pdo->prepare("
                SELECT a.*, u.full_name, u.email, u.phone, a.resume_path, a.status as current_status
                FROM applications a
                JOIN users u ON a.user_id = u.id
                WHERE a.job_id = ?
                ORDER BY a.created_at DESC
            ");
            $stmt->execute([$job_id]);
            $applicants = $stmt->fetchAll();
            
        } else {
            if (empty($job)) {
                $error = 'Job not found. The job may have been removed or never existed.';
            } elseif (empty($employer_dept)) {
                $error = 'Your account is not properly configured with a department. Please contact the administrator.';
            } else {
                $error = 'You do not have permission to view applicants for this job. You can only view applicants for jobs in your department.';
                error_log("Access denied - Job ID: $job_id, Job Dept: {$job['department_id']}, User ID: $employer_id, User Dept: " . ($employer_dept['department_id'] ?? 'NULL'));
            }
        }
        
    } catch (Exception $e) {
        $error = 'An error occurred while loading job details: ' . $e->getMessage();
        error_log("Error in view_applicants.php: " . $e->getMessage());
    }
} else {
    // No job ID specified - show available jobs for this employer
    try {
        $emp_stmt = $pdo->prepare("SELECT department_id FROM users WHERE id = ? AND role = 'employer'");
        $emp_stmt->execute([$employer_id]);
        $employer_dept = $emp_stmt->fetch();
        
        if ($employer_dept) {
            $jobs_stmt = $pdo->prepare("
                SELECT j.id, j.title, COUNT(a.id) as application_count
                FROM jobs j 
                LEFT JOIN applications a ON j.id = a.job_id 
                WHERE j.department_id = ? AND j.status = 'approved'
                GROUP BY j.id 
                ORDER BY j.created_at DESC
            ");
            $jobs_stmt->execute([$employer_dept['department_id']]);
            $available_jobs = $jobs_stmt->fetchAll();
        }
    } catch (Exception $e) {
        error_log("Error fetching available jobs: " . $e->getMessage());
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Applicants - OSTA Job Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/styles.css">
    <style>
        .applicant-card {
            transition: transform 0.2s;
            margin-bottom: 20px;
        }
        .applicant-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 4px 15px rgba(0,0,0,0.1);
        }
        .status-badge {
            font-size: 0.8rem;
            padding: 0.35em 0.65em;
        }
        .resume-link {
            color: #0d6efd;
            text-decoration: none;
        }
        .resume-link:hover {
            text-decoration: underline;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Employer Menu</h5>
                        <ul class="nav flex-column">
                            <li class="nav-item">
                                <a class="nav-link" href="dashboard.php">Dashboard</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="post_job.php">Post New Job</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link active" href="#">View Applicants</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="reports.php">Reports</a>
                            </li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9">
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="mb-0">
                            <?php echo $job ? 'Applicants for: ' . htmlspecialchars($job['title']) : 'View Applicants'; ?>
                        </h3>
                        <a href="dashboard.php" class="btn btn-outline-primary">
                            <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
                        </a>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <?php echo htmlspecialchars($error); ?>
                                <?php if (strpos($error, 'permission') !== false): ?>
                                    <div class="mt-2">
                                        <a href="dashboard.php" class="btn btn-sm btn-outline-primary">
                                            <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
                                        </a>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php elseif ($job_id == 0 && isset($available_jobs)): ?>
                            <!-- Show available jobs when no job_id specified -->
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                Please select a job to view its applicants.
                            </div>
                            
                            <?php if (!empty($available_jobs)): ?>
                                <h5>Your Department's Jobs:</h5>
                                <div class="table-responsive">
                                    <table class="table table-hover">
                                        <thead>
                                            <tr>
                                                <th>Job Title</th>
                                                <th>Applications</th>
                                                <th>Action</th>
                                            </tr>
                                        </thead>
                                        <tbody>
                                            <?php foreach ($available_jobs as $available_job): ?>
                                                <tr>
                                                    <td><?php echo htmlspecialchars($available_job['title']); ?></td>
                                                    <td>
                                                        <span class="badge bg-info"><?php echo $available_job['application_count']; ?></span>
                                                    </td>
                                                    <td>
                                                        <a href="view_applicants.php?job_id=<?php echo $available_job['id']; ?>" 
                                                           class="btn btn-sm btn-primary">
                                                            <i class="fas fa-users me-1"></i> View Applicants
                                                        </a>
                                                    </td>
                                                </tr>
                                            <?php endforeach; ?>
                                        </tbody>
                                    </table>
                                </div>
                            <?php else: ?>
                                <div class="text-center py-4">
                                    <i class="fas fa-briefcase fa-3x text-muted mb-3"></i>
                                    <h5 class="text-muted">No jobs found</h5>
                                    <p class="text-muted">You don't have any active jobs in your department.</p>
                                    <a href="manage_jobs.php" class="btn btn-primary">
                                        <i class="fas fa-briefcase me-1"></i> Manage Jobs
                                    </a>
                                </div>
                            <?php endif; ?>
                        <?php elseif (empty($applicants)): ?>
                            <div class="alert alert-info">
                                <i class="fas fa-info-circle me-2"></i>
                                No applicants have applied for this job yet.
                            </div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Name</th>
                                            <th>Email</th>
                                            <th>Phone</th>
                                            <th>Applied On</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($applicants as $applicant): 
                                            $status_class = [
                                                'submitted' => 'bg-primary',
                                                'under_review' => 'bg-info',
                                                'shortlisted' => 'bg-warning',
                                                'interview_scheduled' => 'bg-primary',
                                                'hired' => 'bg-success',
                                                'rejected' => 'bg-danger'
                                            ][$applicant['current_status']] ?? 'bg-secondary';
                                        ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($applicant['full_name']); ?></td>
                                                <td><?php echo htmlspecialchars($applicant['email']); ?></td>
                                                <td><?php echo htmlspecialchars($applicant['phone'] ?? 'N/A'); ?></td>
                                                <td><?php echo date('M d, Y', strtotime($applicant['created_at'])); ?></td>
                                                <td>
                                                    <span class="badge rounded-pill <?php echo $status_class; ?>">
                                                        <?php echo ucwords(str_replace('_', ' ', $applicant['current_status'])); ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <?php if (!empty($applicant['resume_path'])): ?>
                                                        <a href="../download_resume.php?id=<?php echo $applicant['id']; ?>" 
                                                           class="btn btn-sm btn-outline-primary me-1" 
                                                           title="Download Resume">
                                                            <i class="fas fa-download"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                    <a href="view_application.php?application_id=<?php echo $applicant['id']; ?>" 
                                                       class="btn btn-sm btn-primary"
                                                       title="View Application">
                                                        <i class="fas fa-eye"></i> View
                                                    </a>
                                                </td>
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

    <?php include '../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add any necessary JavaScript here
        document.addEventListener('DOMContentLoaded', function() {
            // Initialize tooltips
            var tooltipTriggerList = [].slice.call(document.querySelectorAll('[data-bs-toggle="tooltip"]'));
            var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
                return new bootstrap.Tooltip(tooltipTriggerEl);
            });
        });
    </script>
</body>
</html>
