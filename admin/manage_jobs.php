<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/security.php';
require_once '../includes/functions.php';

// Require admin role
require_role('admin', '../login.php');

// Set security headers
set_security_headers();

// Handle job actions
$action = isset($_GET['action']) ? $_GET['action'] : '';
$job_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$status = isset($_GET['status']) ? $_GET['status'] : '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $_SESSION['error_message'] = "Security token validation failed. Please try again.";
        header('Location: manage_jobs.php');
        exit();
    }
    $title = sanitize($_POST['title']);
    $department_id = (int)$_POST['department_id'];
    $location = sanitize($_POST['location']);
    $employment_type = sanitize($_POST['employment_type']);
    $description = sanitize($_POST['description']);
    $requirements = sanitize($_POST['requirements']);
    $deadline = sanitize($_POST['deadline']);
    $status = sanitize($_POST['status']);
    
    if ($action === 'edit') {
        // Get current job data before update (to check status change and notify employer)
        $old_stmt = $pdo->prepare("SELECT created_by, status as old_status, title FROM jobs WHERE id = ?");
        $old_stmt->execute([$job_id]);
        $old_job = $old_stmt->fetch();

        // Update job
        $stmt = $pdo->prepare("
            UPDATE jobs 
            SET title = ?, department_id = ?, location = ?, employment_type = ?, 
                description = ?, requirements = ?, deadline = ?, status = ?
            WHERE id = ?
        ");
        $stmt->execute([$title, $department_id, $location, $employment_type, 
                        $description, $requirements, $deadline, $status, $job_id]);

        // Notify employer if status changed (approved/rejected)
        if ($old_job && $old_job['old_status'] !== $status && !empty($old_job['created_by'])) {
            $statusLabel = ucfirst($status);
            $notifType = $status === 'approved' ? 'success' : ($status === 'rejected' ? 'error' : 'info');
            create_notification(
                'Job ' . $statusLabel,
                'Your job "' . $old_job['title'] . '" has been ' . $status . ' by an administrator.',
                $notifType,
                'user',
                $old_job['created_by']
            );
        }

        $_SESSION['success_message'] = "Job updated successfully";
    } else {
        // Create new job
        $stmt = $pdo->prepare("
            INSERT INTO jobs (title, department_id, location, employment_type, 
                            description, requirements, deadline, status, created_by)
            VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?)
        ");
        $stmt->execute([$title, $department_id, $location, $employment_type, 
                        $description, $requirements, $deadline, 'approved', $_SESSION['user_id']]);
        $_SESSION['success_message'] = "Job created successfully";
    }
    
    header('Location: manage_jobs.php');
    exit();
}

// Handle deletion
if ($action === 'delete' && $job_id > 0) {
    if (!isset($_GET['csrf_token']) || !verify_csrf_token($_GET['csrf_token'])) {
        $_SESSION['error_message'] = "Security token validation failed. Please try again.";
        header('Location: manage_jobs.php');
        exit();
    }
    // Check if job has applications
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM applications WHERE job_id = ?");
    $stmt->execute([$job_id]);
    $has_applications = $stmt->fetch()['count'] > 0;
    
    if ($has_applications) {
        $_SESSION['error_message'] = "Cannot delete job. It has active applications.";
    } else {
        $stmt = $pdo->prepare("DELETE FROM jobs WHERE id = ?");
        $stmt->execute([$job_id]);
        $_SESSION['success_message'] = "Job deleted successfully";
    }
    
    header('Location: manage_jobs.php');
    exit();
}

// Get jobs with filtering
$query = "SELECT j.*, d.name as department_name, u.username as created_by_name 
          FROM jobs j 
          JOIN departments d ON j.department_id = d.id 
          JOIN users u ON j.created_by = u.id 
          WHERE 1 = 1 ";

$params = [];

if ($status) {
    $query .= " AND j.status = ? ";
    $params[] = $status;
}

$query .= " ORDER BY j.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$jobs = $stmt->fetchAll();

// Debug: Log the number of jobs found

// Debug: Log the first job if any
if (!empty($jobs)) {
}

// Get job details if editing
$job = null;
if ($action === 'edit' && $job_id > 0) {
    $stmt = $pdo->prepare("
        SELECT j.*, d.name as department_name 
        FROM jobs j 
        JOIN departments d ON j.department_id = d.id 
        WHERE j.id = ?
    ");
    $stmt->execute([$job_id]);
    $job = $stmt->fetch();
}

// Get departments for dropdown
$departments = $pdo->query("SELECT id, name FROM departments ORDER BY name")->fetchAll();
?>

<?php include '../includes/header.php'; ?>

    <div class="container-fluid mt-4">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3">
                <div class="card dashboard-card">
                    <div class="card-header bg-gradient-primary text-white">
                        <h3 class="mb-0"><i class="fas fa-cog me-2"></i>Admin Menu</h3>
                    </div>
                    <div class="list-group list-group-flush dashboard-sidebar">
                        <a href="dashboard.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                        </a>
                        <a href="manage_users.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-users me-2"></i> Manage Users
                        </a>
                        <a href="manage_departments.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-building me-2"></i> Manage Departments
                        </a>
                        <a href="manage_jobs.php" class="list-group-item list-group-item-action active">
                            <i class="fas fa-briefcase me-2"></i> Manage Jobs
                        </a>
                        <a href="reports.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-file-alt me-2"></i> Reports
                        </a>
                        <a href="settings.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-cog me-2"></i> Settings
                        </a>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9">
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($_SESSION['success_message'], ENT_QUOTES, 'UTF-8'); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['success_message']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo htmlspecialchars($_SESSION['error_message'], ENT_QUOTES, 'UTF-8'); ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['error_message']); ?>
                <?php endif; ?>

                <?php if (!empty($status)): ?>
                    <div class="alert alert-info alert-dismissible fade show mb-4" role="alert">
                        <i class="bi bi-funnel me-2"></i>
                        Showing jobs with status: <strong><?php echo ucfirst($status); ?></strong>
                        <a href="manage_jobs.php" class="btn-close" aria-label="Clear filter"></a>
                    </div>
                <?php endif; ?>

                <!-- Job Form -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="mb-0"><?php echo $action === 'edit' ? 'Edit Job' : 'Add New Job'; ?></h3>
                        <a href="manage_jobs.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Back to List
                        </a>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="title" class="form-label">Job Title *</label>
                                    <input type="text" class="form-control" id="title" name="title" 
                                           value="<?php echo $job ? htmlspecialchars($job['title']) : ''; ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="department_id" class="form-label">Department *</label>
                                    <select class="form-select" id="department_id" name="department_id" required>
                                        <option value="">Select Department</option>
                                        <?php foreach ($departments as $dept): ?>
                                            <option value="<?php echo $dept['id']; ?>" 
                                                    <?php echo $job && $job['department_id'] === $dept['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($dept['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="location" class="form-label">Location *</label>
                                    <input type="text" class="form-control" id="location" name="location" 
                                           value="<?php echo $job ? htmlspecialchars($job['location']) : ''; ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="employment_type" class="form-label">Employment Type *</label>
                                    <select class="form-select" id="employment_type" name="employment_type" required>
                                        <option value="full_time" <?php echo $job && $job['employment_type'] === 'full_time' ? 'selected' : ''; ?>>Full Time</option>
                                        <option value="part_time" <?php echo $job && $job['employment_type'] === 'part_time' ? 'selected' : ''; ?>>Part Time</option>
                                        <option value="contract" <?php echo $job && $job['employment_type'] === 'contract' ? 'selected' : ''; ?>>Contract</option>
                                        <option value="internship" <?php echo $job && $job['employment_type'] === 'internship' ? 'selected' : ''; ?>>Internship</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Job Description *</label>
                                <textarea class="form-control" id="description" name="description" rows="5" required><?php 
                                    echo $job ? htmlspecialchars($job['description']) : '';
                                ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="requirements" class="form-label">Requirements *</label>
                                <textarea class="form-control" id="requirements" name="requirements" rows="5" required><?php 
                                    echo $job ? htmlspecialchars($job['requirements']) : '';
                                ?></textarea>
                                <div class="form-text">Enter each requirement on a new line</div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="deadline" class="form-label">Application Deadline *</label>
                                    <input type="date" class="form-control" id="deadline" name="deadline" 
                                           value="<?php echo $job ? htmlspecialchars($job['deadline']) : date('Y-m-d', strtotime('+30 days')); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="status" class="form-label">Status *</label>
                                    <select class="form-select" id="status" name="status" required>
                                        <option value="pending" <?php echo $job && $job['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="approved" <?php echo $job && $job['status'] === 'approved' ? 'selected' : ''; ?>>Approved</option>
                                        <option value="expired" <?php echo $job && $job['status'] === 'expired' ? 'selected' : ''; ?>>Expired</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <?php echo $action === 'edit' ? 'Update Job' : 'Create Job'; ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Jobs List -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="mb-0">Jobs List</h3>
                        <div class="btn-group">
                            <a href="manage_jobs.php?action=add" class="btn btn-primary">
                                <i class="bi bi-plus-circle me-1"></i> Add New Job
                            </a>
                            <div class="btn-group">
                                <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                    Filter Status
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="manage_jobs.php">All</a></li>
                                    <li><a class="dropdown-item" href="manage_jobs.php?status=pending">Pending</a></li>
                                    <li><a class="dropdown-item" href="manage_jobs.php?status=approved">Approved</a></li>
                                    <li><a class="dropdown-item" href="manage_jobs.php?status=expired">Expired</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="jobsTable">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Department</th>
                                        <th>Location</th>
                                        <th>Type</th>
                                        <th>Status</th>
                                        <th>Deadline</th>
                                        <th>Created By</th>
                                        <th>Created At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($jobs as $job): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($job['title']); ?></td>
                                            <td><?php echo htmlspecialchars($job['department_name']); ?></td>
                                            <td><?php echo htmlspecialchars($job['location']); ?></td>
                                            <td><?php echo ucfirst($job['employment_type']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $job['status'] === 'approved' ? 'success' : 
                                                    ($job['status'] === 'pending' ? 'warning' : 'danger');
                                                ?>">
                                                    <?php echo ucfirst($job['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M j, Y', strtotime($job['deadline'])); ?></td>
                                            <td><?php echo htmlspecialchars($job['created_by_name']); ?></td>
                                            <td><?php echo date('M j, Y H:i', strtotime($job['created_at'])); ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="manage_jobs.php?action=edit&id=<?php echo $job['id']; ?>" 
                                                       class="btn btn-sm btn-primary">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <a href="manage_jobs.php?action=delete&id=<?php echo $job['id']; ?>&csrf_token=<?php echo urlencode(generate_csrf_token()); ?>" 
                                                       class="btn btn-sm btn-danger" 
                                                       onclick="return confirm('Are you sure you want to delete this job?')">
                                                        <i class="bi bi-trash"></i>
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

    <?php include '../includes/footer.php'; ?>
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.24/css/dataTables.bootstrap5.min.css">
    <script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.24/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#jobsTable').DataTable({
                pageLength: 10,
                order: [[7, 'desc']],
                columnDefs: [
                    { targets: [0, 1, 2, 3, 4, 5, 6, 7, 8], orderable: true }
                ]
            });
        });
    </script>
    <?php prevent_back_navigation(); ?>