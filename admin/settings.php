<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/security.php';

// Initialize security
init_secure_session();
set_security_headers();

// Require admin role
require_role('admin', '../login.php');

// Handle settings updates
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['error_message'] = "Invalid security token. Please try again.";
        header('Location: settings.php');
        exit();
    }
    // Update general settings
    $site_title = sanitize($_POST['site_title']);
    $site_email = sanitize($_POST['site_email']);
    $site_phone = sanitize($_POST['site_phone']);
    $site_address = sanitize($_POST['site_address']);
    $site_description = sanitize($_POST['site_description']);
    
    // Update email settings
    $smtp_host = sanitize($_POST['smtp_host']);
    $smtp_port = (int)$_POST['smtp_port'];
    $smtp_user = sanitize($_POST['smtp_user']);
    $smtp_pass = sanitize($_POST['smtp_pass']);
    $smtp_from = sanitize($_POST['smtp_from']);
    
    // Update file upload settings
    $max_resume_size = (int)$_POST['max_resume_size'];
    $max_cover_letter_size = (int)$_POST['max_cover_letter_size'];
    $allowed_resume_types = sanitize($_POST['allowed_resume_types']);
    $allowed_cover_letter_types = sanitize($_POST['allowed_cover_letter_types']);
    
    // Update notification settings
    $notification_email = sanitize($_POST['notification_email']);
    $notification_phone = sanitize($_POST['notification_phone']);
    
    // Update system settings
    $maintenance_mode = isset($_POST['maintenance_mode']) ? '1' : '0';
    $allow_self_registration = isset($_POST['allow_self_registration']) ? '1' : '0';
    
    // Update database
    $stmt = $pdo->prepare("
        INSERT INTO settings (
            setting_name, setting_value, created_at, updated_at
        ) VALUES 
            ('site_title', ?, NOW(), NOW()),
            ('site_email', ?, NOW(), NOW()),
            ('site_phone', ?, NOW(), NOW()),
            ('site_address', ?, NOW(), NOW()),
            ('site_description', ?, NOW(), NOW()),
            ('smtp_host', ?, NOW(), NOW()),
            ('smtp_port', ?, NOW(), NOW()),
            ('smtp_user', ?, NOW(), NOW()),
            ('smtp_pass', ?, NOW(), NOW()),
            ('smtp_from', ?, NOW(), NOW()),
            ('max_resume_size', ?, NOW(), NOW()),
            ('max_cover_letter_size', ?, NOW(), NOW()),
            ('allowed_resume_types', ?, NOW(), NOW()),
            ('allowed_cover_letter_types', ?, NOW(), NOW()),
            ('notification_email', ?, NOW(), NOW()),
            ('notification_phone', ?, NOW(), NOW()),
            ('maintenance_mode', ?, NOW(), NOW()),
            ('allow_self_registration', ?, NOW(), NOW())
        ON DUPLICATE KEY UPDATE 
            setting_value = VALUES(setting_value),
            updated_at = VALUES(updated_at)
    ");
    
    $stmt->execute([
        $site_title, $site_email, $site_phone, $site_address, $site_description,
        $smtp_host, $smtp_port, $smtp_user, $smtp_pass, $smtp_from,
        $max_resume_size, $max_cover_letter_size, $allowed_resume_types, $allowed_cover_letter_types,
        $notification_email, $notification_phone, $maintenance_mode, $allow_self_registration
    ]);
    
    $_SESSION['success_message'] = "Settings updated successfully";
    header('Location: settings.php');
    exit();
}

// Get current settings
$stmt = $pdo->query("
    SELECT setting_name, setting_value 
    FROM settings
");
$settings = $stmt->fetchAll(PDO::FETCH_KEY_PAIR);

// Get audit log
$audit_log = $pdo->query("
    SELECT a.*, u.username 
    FROM audit_log a 
    LEFT JOIN users u ON a.user_id = u.id 
    ORDER BY a.created_at DESC 
    LIMIT 20
")->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Settings - OSTA Job Portal</title>
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
                        <a href="settings.php" class="list-group-item list-group-item-action active">
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

                <!-- Settings Form -->
                <form method="POST" action="">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    
                    <!-- General Settings -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h3 class="mb-0">General Settings</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="site_title" class="form-label">Site Title *</label>
                                    <input type="text" class="form-control" id="site_title" name="site_title" 
                                           value="<?php echo $settings['site_title'] ?? 'OSTA Job Portal'; ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="site_email" class="form-label">Site Email *</label>
                                    <input type="email" class="form-control" id="site_email" name="site_email" 
                                           value="<?php echo $settings['site_email'] ?? 'info@osta.org.et'; ?>" required>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="site_phone" class="form-label">Site Phone *</label>
                                    <input type="tel" class="form-control" id="site_phone" name="site_phone" 
                                           value="<?php echo $settings['site_phone'] ?? '+251 11 123 4567'; ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="site_address" class="form-label">Site Address *</label>
                                    <input type="text" class="form-control" id="site_address" name="site_address" 
                                           value="<?php echo $settings['site_address'] ?? 'Addis Ababa, Ethiopia'; ?>" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="site_description" class="form-label">Site Description</label>
                                <textarea class="form-control" id="site_description" name="site_description" rows="3"><?php 
                                    echo $settings['site_description'] ?? 'OSTA Job Portal is a platform for job seekers and employers to connect.';
                                ?></textarea>
                            </div>
                        </div>
                    </div>

                    <!-- Email Settings -->
                <div class="card mb-4">
                    <div class="card-header">
                        <h3 class="mb-0">Email Settings</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="smtp_host" class="form-label">SMTP Host *</label>
                                    <input type="text" class="form-control" id="smtp_host" name="smtp_host" 
                                           value="<?php echo $settings['smtp_host'] ?? 'smtp.gmail.com'; ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="smtp_port" class="form-label">SMTP Port *</label>
                                    <input type="number" class="form-control" id="smtp_port" name="smtp_port" 
                                           value="<?php echo $settings['smtp_port'] ?? '587'; ?>" required>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="smtp_user" class="form-label">SMTP Username *</label>
                                    <input type="text" class="form-control" id="smtp_user" name="smtp_user" 
                                           value="<?php echo $settings['smtp_user'] ?? ''; ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="smtp_pass" class="form-label">SMTP Password *</label>
                                    <input type="password" class="form-control" id="smtp_pass" name="smtp_pass" required>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="smtp_from" class="form-label">From Email *</label>
                                <input type="email" class="form-control" id="smtp_from" name="smtp_from" 
                                       value="<?php echo $settings['smtp_from'] ?? 'noreply@osta.org.et'; ?>" required>
                            </div>
                        </div>
                    </div>

                    <!-- File Upload Settings -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h3 class="mb-0">File Upload Settings</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="max_resume_size" class="form-label">Maximum Resume Size (MB) *</label>
                                    <input type="number" class="form-control" id="max_resume_size" name="max_resume_size" 
                                           value="<?php echo $settings['max_resume_size'] ?? '5'; ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="max_cover_letter_size" class="form-label">Maximum Cover Letter Size (MB) *</label>
                                    <input type="number" class="form-control" id="max_cover_letter_size" name="max_cover_letter_size" 
                                           value="<?php echo $settings['max_cover_letter_size'] ?? '2'; ?>" required>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="allowed_resume_types" class="form-label">Allowed Resume Types *</label>
                                    <input type="text" class="form-control" id="allowed_resume_types" name="allowed_resume_types" 
                                           value="<?php echo $settings['allowed_resume_types'] ?? 'pdf,doc,docx'; ?>" required>
                                    <div class="form-text">Separate file types with commas (e.g., pdf,doc,docx)</div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="allowed_cover_letter_types" class="form-label">Allowed Cover Letter Types *</label>
                                    <input type="text" class="form-control" id="allowed_cover_letter_types" name="allowed_cover_letter_types" 
                                           value="<?php echo $settings['allowed_cover_letter_types'] ?? 'pdf,doc,docx'; ?>" required>
                                    <div class="form-text">Separate file types with commas (e.g., pdf,doc,docx)</div>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- Notification Settings -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h3 class="mb-0">Notification Settings</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="notification_email" class="form-label">Notification Email *</label>
                                    <input type="email" class="form-control" id="notification_email" name="notification_email" 
                                           value="<?php echo $settings['notification_email'] ?? 'admin@osta.org.et'; ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="notification_phone" class="form-label">Notification Phone *</label>
                                    <input type="tel" class="form-control" id="notification_phone" name="notification_phone" 
                                           value="<?php echo $settings['notification_phone'] ?? '+251 11 123 4567'; ?>" required>
                                </div>
                            </div>
                        </div>
                    </div>

                    <!-- System Settings -->
                    <div class="card mb-4">
                        <div class="card-header">
                            <h3 class="mb-0">System Settings</h3>
                        </div>
                        <div class="card-body">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="maintenance_mode" name="maintenance_mode" 
                                               <?php echo (isset($settings['maintenance_mode']) && $settings['maintenance_mode'] === '1') ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="maintenance_mode">
                                            Enable Maintenance Mode
                                        </label>
                                    </div>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <div class="form-check">
                                        <input class="form-check-input" type="checkbox" id="allow_self_registration" name="allow_self_registration" 
                                               <?php echo (isset($settings['allow_self_registration']) && $settings['allow_self_registration'] === '1') ? 'checked' : ''; ?>>
                                        <label class="form-check-label" for="allow_self_registration">
                                            Allow Self Registration
                                        </label>
                                    </div>
                                </div>
                            </div>
                            
                        </div>
                    </div>
                    
                    <!-- Submit Button -->
                    <div class="card mb-4">
                        <div class="card-body">
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary btn-lg">
                                    <i class="bi bi-save me-1"></i> Save All Settings
                                </button>
                            </div>
                        </div>
                    </div>
                </form>

                <!-- Audit Log -->
                <div class="card">
                    <div class="card-header">
                        <h3 class="mb-0">Audit Log</h3>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>User</th>
                                        <th>Action</th>
                                        <th>Details</th>
                                        <th>Timestamp</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($audit_log as $log): ?>
                                        <tr>
                                            <td><?php echo htmlspecialchars($log['username'] ?? 'System'); ?></td>
                                            <td><?php echo htmlspecialchars($log['action']); ?></td>
                                            <td><?php echo htmlspecialchars($log['details']); ?></td>
                                            <td><?php echo date('M j, Y H:i', strtotime($log['created_at'])); ?></td>
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
    
    <script>
        // Prevent back navigation
        prevent_back_navigation();
    </script>
</body>
</html>
