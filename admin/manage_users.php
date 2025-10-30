<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/security.php';

// Require admin role
require_role('admin', '../login.php');

// Set security headers
set_security_headers();

// Handle user actions
$action = isset($_GET['action']) ? $_GET['action'] : '';
$user_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;
$status = isset($_GET['status']) ? $_GET['status'] : '';

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $role = sanitize($_POST['role']);
    $department_id = isset($_POST['department_id']) ? (int)$_POST['department_id'] : null;
    $status = sanitize($_POST['status']);
    
    if ($action === 'edit') {
        // Update user
        if (!empty($_POST['password'])) {
            // Update password if provided
            $password = password_hash($_POST['password'], PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("
                UPDATE users 
                SET username = ?, email = ?, phone = ?, password = ?, role = ?, department_id = ?, status = ?
                WHERE id = ?
            ");
            $stmt->execute([$username, $email, $phone, $password, $role, $department_id, $status, $user_id]);
        } else {
            // Update without changing password
            $stmt = $pdo->prepare("
                UPDATE users 
                SET username = ?, email = ?, phone = ?, role = ?, department_id = ?, status = ?
                WHERE id = ?
            ");
            $stmt->execute([$username, $email, $phone, $role, $department_id, $status, $user_id]);
        }
        $_SESSION['success_message'] = "User updated successfully";
    } else {
        // Check if username already exists
        $check_stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $check_stmt->execute([$username]);
        
        if ($check_stmt->fetch()) {
            $_SESSION['error_message'] = "Username already exists. Please choose a different username.";
        } else {
            // Check if email already exists
            $check_stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
            $check_stmt->execute([$email]);
            
            if ($check_stmt->fetch()) {
                $_SESSION['error_message'] = "Email already exists. Please use a different email address.";
            } else {
                // Create new user with custom password or default
                $password_input = isset($_POST['password']) ? $_POST['password'] : '';
                if (empty($password_input)) {
                    $password_input = 'password123'; // Default password
                }
                $password = password_hash($password_input, PASSWORD_DEFAULT);
                
                $stmt = $pdo->prepare("
                    INSERT INTO users (username, email, phone, password, role, department_id, status)
                    VALUES (?, ?, ?, ?, ?, ?, ?)
                ");
                $stmt->execute([$username, $email, $phone, $password, $role, $department_id, 'active']);
                $_SESSION['success_message'] = "User created successfully with password: " . (isset($_POST['password']) && !empty($_POST['password']) ? 'Custom password set' : 'Default password (password123)');
            }
        }
    }
    
    header('Location: manage_users.php');
    exit();
}

// Handle deletion
if ($action === 'delete' && $user_id > 0) {
    // Check if user has applications
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM applications WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $has_applications = $stmt->fetch()['count'] > 0;
    
    if ($has_applications) {
        $_SESSION['error_message'] = "Cannot delete user. They have active applications.";
    } else {
        $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $_SESSION['success_message'] = "User deleted successfully";
    }
    
    header('Location: manage_users.php');
    exit();
}

// Get users with filtering
$query = "SELECT u.*, d.name as department_name 
          FROM users u 
          LEFT JOIN departments d ON u.department_id = d.id 
          WHERE 1 = 1 ";

$params = [];

if ($status) {
    $query .= " AND u.status = ? ";
    $params[] = $status;
}

$query .= " ORDER BY u.created_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$users = $stmt->fetchAll();

// Get user details if editing
$user = null;
if ($action === 'edit' && $user_id > 0) {
    $stmt = $pdo->prepare("
        SELECT u.*, d.name as department_name 
        FROM users u 
        LEFT JOIN departments d ON u.department_id = d.id 
        WHERE u.id = ?
    ");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();
}

// Get departments for dropdown
$departments = $pdo->query("SELECT id, name FROM departments ORDER BY name")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Manage Users - OSTA Job Portal</title>
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
                        <a href="manage_users.php" class="list-group-item list-group-item-action active">
                            <i class="bi bi-people me-2"></i> Manage Users
                        </a>
                        <a href="manage_departments.php" class="list-group-item list-group-item-action">
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

                <!-- User Form -->
                <div class="card mb-4">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="mb-0"><?php echo $action === 'edit' ? 'Edit User' : 'Add New User'; ?></h3>
                        <a href="manage_users.php" class="btn btn-outline-secondary">
                            <i class="bi bi-arrow-left me-1"></i> Back to List
                        </a>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="username" class="form-label">Username *</label>
                                    <input type="text" class="form-control" id="username" name="username" 
                                           value="<?php echo $user ? htmlspecialchars($user['username']) : ''; ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email *</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo $user ? htmlspecialchars($user['email']) : ''; ?>" required>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">Phone *</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" 
                                           value="<?php echo $user ? htmlspecialchars($user['phone']) : ''; ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="role" class="form-label">Role *</label>
                                    <select class="form-select" id="role" name="role" required>
                                        <option value="applicant" <?php echo $user && $user['role'] === 'applicant' ? 'selected' : ''; ?>>Applicant</option>
                                        <option value="employer" <?php echo $user && $user['role'] === 'employer' ? 'selected' : ''; ?>>Employer</option>
                                        <option value="admin" <?php echo $user && $user['role'] === 'admin' ? 'selected' : ''; ?>>Admin</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="password" class="form-label">Password <?php echo $action !== 'edit' ? '*' : ''; ?></label>
                                    <input type="password" class="form-control" id="password" name="password" 
                                           placeholder="<?php echo $action === 'edit' ? 'Leave blank to keep current password' : 'Enter password'; ?>">
                                    <?php if ($action === 'edit'): ?>
                                        <div class="form-text">Leave blank to keep current password</div>
                                    <?php endif; ?>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="department_id" class="form-label">Department</label>
                                    <select class="form-select" id="department_id" name="department_id">
                                        <option value="">Select Department</option>
                                        <?php foreach ($departments as $dept): ?>
                                            <option value="<?php echo $dept['id']; ?>" 
                                                    <?php echo $user && $user['department_id'] === $dept['id'] ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($dept['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="status" class="form-label">Status *</label>
                                    <select class="form-select" id="status" name="status" required>
                                        <option value="active" <?php echo $user && $user['status'] === 'active' ? 'selected' : ''; ?>>Active</option>
                                        <option value="pending" <?php echo $user && $user['status'] === 'pending' ? 'selected' : ''; ?>>Pending</option>
                                        <option value="inactive" <?php echo $user && $user['status'] === 'inactive' ? 'selected' : ''; ?>>Inactive</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <?php echo $action === 'edit' ? 'Update User' : 'Create User'; ?>
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Users List -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="mb-0">Users List</h3>
                        <div class="btn-group">
                            <a href="manage_users.php?action=add" class="btn btn-primary">
                                <i class="bi bi-plus-circle me-1"></i> Add New User
                            </a>
                            <div class="btn-group">
                                <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                    Filter Status
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="manage_users.php">All</a></li>
                                    <li><a class="dropdown-item" href="manage_users.php?status=active">Active</a></li>
                                    <li><a class="dropdown-item" href="manage_users.php?status=pending">Pending</a></li>
                                    <li><a class="dropdown-item" href="manage_users.php?status=inactive">Inactive</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover" id="usersTable">
                                <thead>
                                    <tr>
                                        <th>Username</th>
                                        <th>Email</th>
                                        <th>Role</th>
                                        <th>Department</th>
                                        <th>Status</th>
                                        <th>Created At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($users as $user): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($user['username']); ?></td>
                                            <td><?php echo htmlspecialchars($user['email']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $user['role'] === 'admin' ? 'primary' : 
                                                    ($user['role'] === 'employer' ? 'success' : 'info');
                                                ?>">
                                                    <?php echo ucfirst($user['role']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo $user['department_name'] ?? 'N/A'; ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $user['status'] === 'active' ? 'success' : 
                                                    ($user['status'] === 'pending' ? 'warning' : 'danger');
                                                ?>">
                                                    <?php echo ucfirst($user['status']); ?>
                                                </span>
                                            </td>
                                            <td><?php echo date('M j, Y H:i', strtotime($user['created_at'])); ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <a href="manage_users.php?action=edit&id=<?php echo $user['id']; ?>" 
                                                       class="btn btn-sm btn-primary">
                                                        <i class="bi bi-pencil"></i>
                                                    </a>
                                                    <a href="manage_users.php?action=delete&id=<?php echo $user['id']; ?>" 
                                                       class="btn btn-sm btn-danger" 
                                                       onclick="return confirm('Are you sure you want to delete this user?')">
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
            $('#usersTable').DataTable({
                pageLength: 10,
                order: [[5, 'desc']],
                columnDefs: [
                    { targets: [0, 1, 2, 3, 4, 5, 6], orderable: true }
                ]
            });
        });
    </script>
    <?php prevent_back_navigation(); ?>
</body>
</html>
