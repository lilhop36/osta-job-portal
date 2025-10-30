<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/security.php';

// Require admin role
require_role('admin', '../login.php');

// Set security headers
set_security_headers();

// Handle department actions
$action = isset($_GET['action']) ? $_GET['action'] : '';
$department_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $name = sanitize($_POST['name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $location = sanitize($_POST['location']);
    $description = sanitize($_POST['description']);
    
    if ($action === 'edit') {
        // Update department
        $stmt = $pdo->prepare("
            UPDATE departments 
            SET name = ?, email = ?, phone = ?, location = ?, description = ?
            WHERE id = ?
        ");
        $stmt->execute([$name, $email, $phone, $location, $description, $department_id]);
        $_SESSION['success_message'] = "Department updated successfully";
    } else {
        // Create new department
        $stmt = $pdo->prepare("
            INSERT INTO departments (name, email, phone, location, description)
            VALUES (?, ?, ?, ?, ?)
        ");
        $stmt->execute([$name, $email, $phone, $location, $description]);
        $_SESSION['success_message'] = "Department created successfully";
    }
    
    header('Location: manage_departments.php');
    exit();
}

// Handle deletion
if ($action === 'delete' && $department_id > 0) {
    // Check if department has active jobs or users
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM jobs WHERE department_id = ?");
    $stmt->execute([$department_id]);
    $has_jobs = $stmt->fetch()['count'] > 0;
    
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM users WHERE department_id = ?");
    $stmt->execute([$department_id]);
    $has_users = $stmt->fetch()['count'] > 0;
    
    if ($has_jobs || $has_users) {
        $_SESSION['error_message'] = "Cannot delete department. It has active jobs or users.";
    } else {
        $stmt = $pdo->prepare("DELETE FROM departments WHERE id = ?");
        $stmt->execute([$department_id]);
        $_SESSION['success_message'] = "Department deleted successfully";
    }
    
    header('Location: manage_departments.php');
    exit();
}

// Get departments
$stmt = $pdo->query("
    SELECT d.*, 
           (SELECT COUNT(*) FROM jobs WHERE department_id = d.id) as job_count,
           (SELECT COUNT(*) FROM users WHERE department_id = d.id) as user_count
    FROM departments d
    ORDER BY d.name
");
$departments = $stmt->fetchAll();

// Get department details if editing
$department = null;
if ($action === 'edit' && $department_id > 0) {
    $stmt = $pdo->prepare("SELECT * FROM departments WHERE id = ?");
    $stmt->execute([$department_id]);
    $department = $stmt->fetch();
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Departments - OSTA Job Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
    <link rel="stylesheet" href="https://cdn.datatables.net/1.10.24/css/dataTables.bootstrap5.min.css">
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
                        <a href="manage_departments.php" class="list-group-item list-group-item-action active">
                            <i class="bi bi-building me-2"></i> Manage Departments
                        </a>
                        <a href="manage_jobs.php" class="list-group-item list-group-item-action">
                            <i class="bi bi-briefcase me-2"></i> Manage Jobs
                        </a>
                        <a href="reports.php" class="list-group-item list-group-item-action">
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
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['success_message']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['success_message']); ?>
                <?php endif; ?>

                <?php if (isset($_SESSION['error_message'])): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <?php echo $_SESSION['error_message']; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                    </div>
                    <?php unset($_SESSION['error_message']); ?>
                <?php endif; ?>

                <!-- Department Form -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="mb-0"><?php echo $action === 'edit' ? 'Edit Department' : 'Add New Department'; ?></h3>
                        <a href="manage_departments.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Back to List
                        </a>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="name" class="form-label">Department Name *</label>
                                    <input type="text" class="form-control" id="name" name="name" required
                                           value="<?php echo isset($department['name']) ? htmlspecialchars($department['name']) : ''; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email *</label>
                                    <input type="email" class="form-control" id="email" name="email" required
                                           value="<?php echo isset($department['email']) ? htmlspecialchars($department['email']) : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">Phone *</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" required
                                           value="<?php echo isset($department['phone']) ? htmlspecialchars($department['phone']) : ''; ?>">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="location" class="form-label">Location *</label>
                                    <input type="text" class="form-control" id="location" name="location" required
                                           value="<?php echo isset($department['location']) ? htmlspecialchars($department['location']) : ''; ?>">
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Description</label>
                                <textarea class="form-control" id="description" name="description" rows="3"><?php 
                                    echo $department ? htmlspecialchars($department['description']) : '';
                                ?></textarea>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <?php echo $action === 'edit' ? 'Update Department' : 'Create Department'; ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Departments List -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="mb-0">Departments List</h3>
                        <a href="manage_departments.php?action=add" class="btn btn-primary">
                            <i class="bi bi-plus-circle me-1"></i> Add New Department
                        </a>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="departmentsTable">
                                <thead>
                                    <tr>
                                        <th>Name</th>
                                        <th>Email</th>
                                        <th>Location</th>
                                        <th>Jobs</th>
                                        <th>Users</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($departments as $dept): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($dept['name']); ?></td>
                                            <td><?php echo isset($dept['email']) ? htmlspecialchars($dept['email']) : 'N/A'; ?></td>
                                            <td><?php echo isset($dept['location']) ? htmlspecialchars($dept['location']) : 'N/A'; ?></td>
                                            <td><?php echo $dept['job_count']; ?></td>
                                            <td><?php echo $dept['user_count']; ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="manage_departments.php?action=edit&id=<?php echo $dept['id']; ?>" 
                                                       class="btn btn-sm btn-primary">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <a href="manage_departments.php?action=delete&id=<?php echo $dept['id']; ?>" 
                                                       class="btn btn-sm btn-danger" onclick="return confirm('Are you sure you want to delete this department?')">
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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.24/js/jquery.dataTables.min.js"></script>
    <script src="https://cdn.datatables.net/1.10.24/js/dataTables.bootstrap5.min.js"></script>
    <script>
        $(document).ready(function() {
            $('#departmentsTable').DataTable({
                pageLength: 10,
                order: [[0, 'asc']],
                columnDefs: [
                    { targets: [0, 1, 2, 3, 4, 5], orderable: true }
                ]
            });
        });
    </script>
    <?php prevent_back_navigation(); ?>
</body>
</html>
