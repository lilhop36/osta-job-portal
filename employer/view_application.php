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

// Get application ID from query parameter
$application_id = isset($_GET['application_id']) ? (int)$_GET['application_id'] : 0;

// Initialize variables
$application = null;
$job = null;
$error = '';

if ($application_id > 0) {
    try {
        // Get employer's department
        $emp_stmt = $pdo->prepare("SELECT department_id FROM users WHERE id = ? AND role = 'employer'");
        $emp_stmt->execute([$employer_id]);
        $employer_dept = $emp_stmt->fetch();
        
        if (!$employer_dept) {
            $error = 'Your account is not properly configured with a department. Please contact the administrator.';
        } else {
            // Get application details with job and user info
            $stmt = $pdo->prepare("
                SELECT a.*, u.full_name, u.email, u.phone, u.address, u.skills,
                       j.title as job_title, j.description as job_description, j.department_id,
                       d.name as department_name
                FROM applications a
                JOIN users u ON a.user_id = u.id
                JOIN jobs j ON a.job_id = j.id
                JOIN departments d ON j.department_id = d.id
                WHERE a.id = ?
            ");
            $stmt->execute([$application_id]);
            $application = $stmt->fetch();
            
            if (!$application) {
                $error = 'Application not found.';
            } elseif ($application['department_id'] != $employer_dept['department_id'] && 
                     $application['created_by'] != $employer_id) {
                $error = 'You do not have permission to view this application.';
            }
        }
        
    } catch (Exception $e) {
        $error = 'An error occurred while loading application details: ' . $e->getMessage();
        error_log("Error in view_application.php: " . $e->getMessage());
    }
} else {
    $error = 'No application specified.';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>View Application - OSTA Job Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
    <style>
        .application-detail {
            background: #f8f9fa;
            padding: 15px;
            border-radius: 8px;
            margin-bottom: 20px;
        }
        .status-badge {
            font-size: 0.9rem;
            padding: 0.5em 1em;
        }
        .document-link {
            color: #0d6efd;
            text-decoration: none;
        }
        .document-link:hover {
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
                                <a class="nav-link" href="manage_jobs.php">Manage Jobs</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="manage_applications.php">Applications</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link active" href="#">View Application</a>
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
                            <?php echo $application ? 'Application Details' : 'View Application'; ?>
                        </h3>
                        <div>
                            <?php if ($application): ?>
                                <a href="view_applicants.php?job_id=<?php echo $application['job_id']; ?>" 
                                   class="btn btn-outline-secondary me-2">
                                    <i class="fas fa-arrow-left me-1"></i> Back to Applicants
                                </a>
                            <?php endif; ?>
                            <a href="dashboard.php" class="btn btn-outline-primary">
                                <i class="fas fa-home me-1"></i> Dashboard
                            </a>
                        </div>
                    </div>
                    <div class="card-body">
                        <?php if ($error): ?>
                            <div class="alert alert-danger">
                                <i class="fas fa-exclamation-circle me-2"></i>
                                <?php echo htmlspecialchars($error); ?>
                                <div class="mt-2">
                                    <a href="manage_applications.php" class="btn btn-sm btn-outline-primary">
                                        <i class="fas fa-arrow-left me-1"></i> Back to Applications
                                    </a>
                                </div>
                            </div>
                        <?php elseif ($application): ?>
                            <!-- Application Details -->
                            <div class="row">
                                <div class="col-md-8">
                                    <!-- Job Information -->
                                    <div class="application-detail">
                                        <h5><i class="fas fa-briefcase me-2 text-primary"></i>Job Information</h5>
                                        <div class="row">
                                            <div class="col-sm-4"><strong>Position:</strong></div>
                                            <div class="col-sm-8"><?php echo htmlspecialchars($application['job_title']); ?></div>
                                        </div>
                                        <div class="row">
                                            <div class="col-sm-4"><strong>Department:</strong></div>
                                            <div class="col-sm-8"><?php echo htmlspecialchars($application['department_name']); ?></div>
                                        </div>
                                    </div>

                                    <!-- Applicant Information -->
                                    <div class="application-detail">
                                        <h5><i class="fas fa-user me-2 text-success"></i>Applicant Information</h5>
                                        <div class="row mb-2">
                                            <div class="col-sm-4"><strong>Name:</strong></div>
                                            <div class="col-sm-8"><?php echo htmlspecialchars($application['full_name']); ?></div>
                                        </div>
                                        <div class="row mb-2">
                                            <div class="col-sm-4"><strong>Email:</strong></div>
                                            <div class="col-sm-8">
                                                <a href="mailto:<?php echo htmlspecialchars($application['email']); ?>">
                                                    <?php echo htmlspecialchars($application['email']); ?>
                                                </a>
                                            </div>
                                        </div>
                                        <?php if ($application['phone']): ?>
                                        <div class="row mb-2">
                                            <div class="col-sm-4"><strong>Phone:</strong></div>
                                            <div class="col-sm-8">
                                                <a href="tel:<?php echo htmlspecialchars($application['phone']); ?>">
                                                    <?php echo htmlspecialchars($application['phone']); ?>
                                                </a>
                                            </div>
                                        </div>
                                        <?php endif; ?>
                                        <?php if ($application['address']): ?>
                                        <div class="row mb-2">
                                            <div class="col-sm-4"><strong>Address:</strong></div>
                                            <div class="col-sm-8"><?php echo htmlspecialchars($application['address']); ?></div>
                                        </div>
                                        <?php endif; ?>
                                    </div>

                                    <!-- Cover Letter -->
                                    <?php if ($application['cover_letter']): ?>
                                    <div class="application-detail">
                                        <h5><i class="fas fa-file-alt me-2 text-info"></i>Cover Letter</h5>
                                        <div class="border p-3 bg-white rounded">
                                            <?php echo nl2br(htmlspecialchars($application['cover_letter'])); ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>

                                    <!-- Skills -->
                                    <?php if ($application['skills']): ?>
                                    <div class="application-detail">
                                        <h5><i class="fas fa-cogs me-2 text-warning"></i>Skills</h5>
                                        <div class="border p-3 bg-white rounded">
                                            <?php echo nl2br(htmlspecialchars($application['skills'])); ?>
                                        </div>
                                    </div>
                                    <?php endif; ?>
                                </div>

                                <div class="col-md-4">
                                    <!-- Status and Actions -->
                                    <div class="card">
                                        <div class="card-header">
                                            <h6 class="mb-0">Application Status</h6>
                                        </div>
                                        <div class="card-body">
                                            <div class="text-center mb-3">
                                                <?php
                                                $status_class = [
                                                    'pending' => 'bg-warning',
                                                    'shortlisted' => 'bg-info',
                                                    'rejected' => 'bg-danger',
                                                    'accepted' => 'bg-success'
                                                ][$application['status']] ?? 'bg-secondary';
                                                ?>
                                                <span class="badge <?php echo $status_class; ?> status-badge">
                                                    <?php echo ucfirst($application['status']); ?>
                                                </span>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <small class="text-muted">
                                                    <i class="fas fa-calendar me-1"></i>
                                                    Applied: <?php echo date('M j, Y g:i A', strtotime($application['created_at'])); ?>
                                                </small>
                                            </div>

                                            <!-- Documents -->
                                            <h6>Documents</h6>
                                            <?php if ($application['resume_path']): ?>
                                                <div class="mb-2">
                                                    <a href="../download_resume.php?id=<?php echo $application['id']; ?>" 
                                                       class="document-link">
                                                        <i class="fas fa-file-pdf me-1"></i> Resume
                                                    </a>
                                                </div>
                                            <?php else: ?>
                                                <div class="mb-2 text-muted">
                                                    <i class="fas fa-file me-1"></i> No resume uploaded
                                                </div>
                                            <?php endif; ?>

                                            <!-- Action Buttons -->
                                            <div class="mt-3">
                                                <div class="d-grid gap-2">
                                                    <button type="button" class="btn btn-success btn-sm" 
                                                            onclick="updateStatus('shortlisted')">
                                                        <i class="fas fa-check me-1"></i> Shortlist
                                                    </button>
                                                    <button type="button" class="btn btn-danger btn-sm" 
                                                            onclick="updateStatus('rejected')">
                                                        <i class="fas fa-times me-1"></i> Reject
                                                    </button>
                                                    <button type="button" class="btn btn-primary btn-sm" 
                                                            onclick="scheduleInterview()">
                                                        <i class="fas fa-calendar-plus me-1"></i> Schedule Interview
                                                    </button>
                                                </div>
                                            </div>
                                        </div>
                                    </div>
                                </div>
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
        function updateStatus(newStatus) {
            if (confirm('Are you sure you want to update the application status to ' + newStatus + '?')) {
                // Create a form and submit it
                const form = document.createElement('form');
                form.method = 'POST';
                form.action = 'update_application_status.php';
                
                const appIdInput = document.createElement('input');
                appIdInput.type = 'hidden';
                appIdInput.name = 'application_id';
                appIdInput.value = '<?php echo $application['id'] ?? ''; ?>';
                
                const statusInput = document.createElement('input');
                statusInput.type = 'hidden';
                statusInput.name = 'status';
                statusInput.value = newStatus;
                
                const csrfInput = document.createElement('input');
                csrfInput.type = 'hidden';
                csrfInput.name = 'csrf_token';
                csrfInput.value = '<?php echo generate_csrf_token(); ?>';
                
                form.appendChild(appIdInput);
                form.appendChild(statusInput);
                form.appendChild(csrfInput);
                
                document.body.appendChild(form);
                form.submit();
            }
        }
        
        function scheduleInterview() {
            alert('Interview scheduling feature coming soon!');
            // TODO: Implement interview scheduling
        }
    </script>
</body>
</html>