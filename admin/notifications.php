<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

// Require admin role
require_role('admin', '../login.php');

// Handle notification actions
$action = isset($_GET['action']) ? $_GET['action'] : '';
$notification_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $title = sanitize($_POST['title']);
    $message = sanitize($_POST['message']);
    $type = sanitize($_POST['type']);
    $target = sanitize($_POST['target']);
    $target_id = isset($_POST['target_id']) ? (int)$_POST['target_id'] : null;
    
    // Create notification
    $stmt = $pdo->prepare("
        INSERT INTO notifications (title, message, type, target, target_id, created_by, status)
        VALUES (?, ?, ?, ?, ?, ?, 'unread')
    ");
    $stmt->execute([$title, $message, $type, $target, $target_id, $_SESSION['user_id']]);
    
    $_SESSION['success_message'] = "Notification created successfully";
    header('Location: notifications.php');
    exit();
}

// Handle deletion
if ($action === 'delete' && $notification_id > 0) {
    $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ?");
    $stmt->execute([$notification_id]);
    $_SESSION['success_message'] = "Notification deleted successfully";
    header('Location: notifications.php');
    exit();
}

// Get notifications
$notifications = $pdo->query("
    SELECT n.*, u.username as created_by_name 
    FROM notifications n 
    LEFT JOIN users u ON n.created_by = u.id 
    ORDER BY n.created_at DESC
")->fetchAll();

// Get users for target selection
$users = $pdo->query("
    SELECT id, username, role 
    FROM users 
    WHERE role != 'admin'
    ORDER BY role, username
")->fetchAll();

// Get jobs for target selection
$jobs = $pdo->query("
    SELECT id, title 
    FROM jobs 
    WHERE status = 'approved'
    ORDER BY title
")->fetchAll();

// Get departments for target selection
$departments = $pdo->query("
    SELECT id, name 
    FROM departments 
    ORDER BY name
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Notifications - OSTA Job Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
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
                        <a href="notifications.php" class="list-group-item list-group-item-action active">
                            <i class="bi bi-bell me-2"></i> Notifications
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

                <!-- Create Notification Form -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h3 class="mb-0">Create Notification</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="title" class="form-label">Title *</label>
                                    <input type="text" class="form-control" id="title" name="title" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="type" class="form-label">Type *</label>
                                    <select class="form-select" id="type" name="type" required>
                                        <option value="info">Information</option>
                                        <option value="warning">Warning</option>
                                        <option value="success">Success</option>
                                        <option value="error">Error</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="message" class="form-label">Message *</label>
                                <textarea class="form-control" id="message" name="message" rows="4" required></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="target" class="form-label">Target *</label>
                                    <select class="form-select" id="target" name="target" required>
                                        <option value="all">All Users</option>
                                        <option value="user">Specific User</option>
                                        <option value="department">Department</option>
                                        <option value="job">Job Applicants</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="target_id" class="form-label">Target ID</label>
                                    <select class="form-select" id="target_id" name="target_id">
                                        <option value="">Select Target</option>
                                        <?php foreach ($users as $user): ?>
                                            <option value="user_<?php echo $user['id']; ?>" 
                                                    data-target="user">
                                                <?php echo htmlspecialchars($user['username']); ?> (<?php echo ucfirst($user['role']); ?>)
                                            </option>
                                        <?php endforeach; ?>
                                        <?php foreach ($departments as $dept): ?>
                                            <option value="dept_<?php echo $dept['id']; ?>" 
                                                    data-target="department">
                                                <?php echo htmlspecialchars($dept['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                        <?php foreach ($jobs as $job): ?>
                                            <option value="job_<?php echo $job['id']; ?>" 
                                                    data-target="job">
                                                <?php echo htmlspecialchars($job['title']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">
                                    <i class="bi bi-bell me-1"></i> Send Notification
                                </button>
                            </div>
                        </form>
                    </div>
                </div>

                <!-- Notifications List -->
                <div class="card">
                    <div class="card-header d-flex justify-content-between align-items-center">
                        <h3 class="mb-0">Notifications</h3>
                        <div class="btn-group">
                            <a href="notifications.php" class="btn btn-outline-secondary">
                                <i class="bi bi-filter me-1"></i> All
                            </a>
                            <div class="btn-group">
                                <button type="button" class="btn btn-outline-secondary dropdown-toggle" data-bs-toggle="dropdown">
                                    Filter Type
                                </button>
                                <ul class="dropdown-menu">
                                    <li><a class="dropdown-item" href="notifications.php?type=info">Information</a></li>
                                    <li><a class="dropdown-item" href="notifications.php?type=warning">Warning</a></li>
                                    <li><a class="dropdown-item" href="notifications.php?type=success">Success</a></li>
                                    <li><a class="dropdown-item" href="notifications.php?type=error">Error</a></li>
                                </ul>
                            </div>
                        </div>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Title</th>
                                        <th>Type</th>
                                        <th>Target</th>
                                        <th>Created By</th>
                                        <th>Created At</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($notifications as $notification): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($notification['title']); ?></td>
                                            <td>
                                                <span class="badge bg-<?php 
                                                    echo $notification['type'] === 'info' ? 'info' : 
                                                    ($notification['type'] === 'warning' ? 'warning' : 
                                                    ($notification['type'] === 'success' ? 'success' : 'danger'));
                                                ?>">
                                                    <?php echo ucfirst($notification['type']); ?>
                                                </span>
                                            </td>
                                            <td>
                                                <?php
                                                if ($notification['target'] === 'all') {
                                                    echo 'All Users';
                                                } elseif ($notification['target'] === 'user') {
                                                    echo 'Specific User';
                                                } elseif ($notification['target'] === 'department') {
                                                    echo 'Department';
                                                } elseif ($notification['target'] === 'job') {
                                                    echo 'Job Applicants';
                                                }
                                                ?>
                                            </td>
                                            <td><?php echo htmlspecialchars($notification['created_by_name']); ?></td>
                                            <td><?php echo date('M j, Y H:i', strtotime($notification['created_at'])); ?></td>
                                            <td>
                                                <div class="btn-group">
                                                    <button type="button" class="btn btn-sm btn-primary" 
                                                            data-bs-toggle="modal" data-bs-target="#viewModal<?php echo $notification['id']; ?>">
                                                        <i class="bi bi-eye"></i>
                                                    </button>
                                                    <a href="notifications.php?action=delete&id=<?php echo $notification['id']; ?>" 
                                                       class="btn btn-sm btn-danger" 
                                                       onclick="return confirm('Are you sure you want to delete this notification?')">
                                                        <i class="bi bi-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>

                                        <!-- Modal for viewing notification details -->
                                        <div class="modal fade" id="viewModal<?php echo $notification['id']; ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title"><?php echo htmlspecialchars($notification['title']); ?></h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p><?php echo nl2br(htmlspecialchars($notification['message'])); ?></p>
                                                        <hr>
                                                        <div class="row">
                                                            <div class="col-md-6">
                                                                <strong>Type:</strong> <?php echo ucfirst($notification['type']); ?>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <strong>Target:</strong> <?php 
                                                                    if ($notification['target'] === 'all') {
                                                                        echo 'All Users';
                                                                    } elseif ($notification['target'] === 'user') {
                                                                        echo 'Specific User';
                                                                    } elseif ($notification['target'] === 'department') {
                                                                        echo 'Department';
                                                                    } elseif ($notification['target'] === 'job') {
                                                                        echo 'Job Applicants';
                                                                    }
                                                                ?>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <strong>Created By:</strong> <?php echo htmlspecialchars($notification['created_by_name']); ?>
                                                            </div>
                                                            <div class="col-md-6">
                                                                <strong>Created At:</strong> <?php echo date('M j, Y H:i', strtotime($notification['created_at'])); ?>
                                                            </div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
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
    <script>
        // Update target_id options based on selected target
        document.getElementById('target').addEventListener('change', function() {
            const targetId = document.getElementById('target_id');
            const target = this.value;
            
            // Clear current options
            while (targetId.firstChild) {
                targetId.removeChild(targetId.firstChild);
            }
            
            // Add default option
            const defaultOption = document.createElement('option');
            defaultOption.value = '';
            defaultOption.textContent = 'Select Target';
            targetId.appendChild(defaultOption);
            
            // Add appropriate options based on target
            const options = targetId.querySelectorAll('option');
            options.forEach(option => {
                if (option.dataset.target === target || !option.dataset.target) {
                    targetId.appendChild(option);
                }
            });
        });
    </script>
</body>
</html>
