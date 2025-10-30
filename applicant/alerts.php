<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/security.php';

// Require applicant role
require_role('applicant', SITE_URL . '/login.php');

// Set security headers
set_security_headers();

$user_id = $_SESSION['user_id'];

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (isset($_POST['update_alert'])) {
        // Update alert settings
        $alert_id = (int)$_POST['alert_id'];
        $is_active = isset($_POST['is_active']) ? 1 : 0;
        $frequency = sanitize($_POST['frequency']);
        
        $stmt = $pdo->prepare("UPDATE job_alerts SET is_active = ?, frequency = ? WHERE id = ? AND user_id = ?");
        $stmt->execute([$is_active, $frequency, $alert_id, $user_id]);
        
        $_SESSION['success_message'] = 'Alert updated successfully';
    } 
    elseif (isset($_POST['delete_alert'])) {
        // Delete alert
        $alert_id = (int)$_POST['alert_id'];
        
        $stmt = $pdo->prepare("DELETE FROM job_alerts WHERE id = ? AND user_id = ?");
        $stmt->execute([$alert_id, $user_id]);
        
        $_SESSION['success_message'] = 'Alert deleted successfully';
    }
    elseif (isset($_POST['create_alert'])) {
        // Create new alert
        $keywords = sanitize($_POST['keywords']);
        $location = sanitize($_POST['location']);
        $job_type = sanitize($_POST['job_type']);
        $frequency = sanitize($_POST['frequency']);
        $is_active = 1;
        
        $stmt = $pdo->prepare("INSERT INTO job_alerts (user_id, keywords, location, job_type, frequency, is_active) 
                             VALUES (?, ?, ?, ?, ?, ?)");
        $stmt->execute([$user_id, $keywords, $location, $job_type, $frequency, $is_active]);
        
        $_SESSION['success_message'] = 'Job alert created successfully';
    }
    
    // Redirect to prevent form resubmission
    header('Location: ' . SITE_URL . '/applicant/alerts.php');
    exit();
}

// Get user's job alerts
$stmt = $pdo->prepare("SELECT * FROM job_alerts WHERE user_id = ? ORDER BY created_at DESC");
$stmt->execute([$user_id]);
$alerts = $stmt->fetchAll();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Alerts - OSTA Job Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Profile Menu</h5>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item"><a href="<?php echo SITE_URL; ?>/applicant/profile.php">Profile Information</a></li>
                            <li class="list-group-item"><a href="<?php echo SITE_URL; ?>/applicant/change_password.php">Change Password</a></li>
                            <li class="list-group-item"><a href="<?php echo SITE_URL; ?>/applicant/saved_jobs.php">Saved Jobs</a></li>
                            <li class="list-group-item"><a href="<?php echo SITE_URL; ?>/applicant/dashboard.php">My Applications</a></li>
                            <li class="list-group-item active"><a href="<?php echo SITE_URL; ?>/applicant/alerts.php" class="text-dark fw-bold text-decoration-none">Job Alerts</a></li>
                            <li class="list-group-item"><a href="<?php echo SITE_URL; ?>/applicant/export.php">Export Data</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9">
                <?php if (isset($_SESSION['success_message'])): ?>
                    <div class="alert alert-success"><?php echo $_SESSION['success_message']; unset($_SESSION['success_message']); ?></div>
                <?php endif; ?>

                <div class="card mb-4">
                    <div class="card-header">
                        <h3 class="mb-0">Create New Job Alert</h3>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="keywords" class="form-label">Keywords</label>
                                    <input type="text" class="form-control" id="keywords" name="keywords" 
                                           placeholder="e.g. Software Engineer, Remote">
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="location" class="form-label">Location</label>
                                    <input type="text" class="form-control" id="location" name="location" 
                                           placeholder="e.g. Addis Ababa, Remote">
                                </div>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="job_type" class="form-label">Job Type</label>
                                    <select class="form-select" id="job_type" name="job_type">
                                        <option value="">Any Type</option>
                                        <option value="full_time">Full Time</option>
                                        <option value="part_time">Part Time</option>
                                        <option value="contract">Contract</option>
                                        <option value="internship">Internship</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="frequency" class="form-label">Alert Frequency</label>
                                    <select class="form-select" id="frequency" name="frequency" required>
                                        <option value="daily">Daily</option>
                                        <option value="weekly" selected>Weekly</option>
                                        <option value="instant">Instant (as soon as posted)</option>
                                    </select>
                                </div>
                            </div>
                            <button type="submit" name="create_alert" class="btn btn-primary">
                                <i class="fas fa-bell me-1"></i> Create Alert
                            </button>
                        </form>
                    </div>
                </div>

                <div class="card">
                    <div class="card-header">
                        <h3 class="mb-0">My Job Alerts</h3>
                    </div>
                    <div class="card-body">
                        <?php if (empty($alerts)): ?>
                            <div class="alert alert-info">You don't have any job alerts yet. Create one above.</div>
                        <?php else: ?>
                            <div class="table-responsive">
                                <table class="table table-hover">
                                    <thead>
                                        <tr>
                                            <th>Keywords</th>
                                            <th>Location</th>
                                            <th>Job Type</th>
                                            <th>Frequency</th>
                                            <th>Status</th>
                                            <th>Actions</th>
                                        </tr>
                                    </thead>
                                    <tbody>
                                        <?php foreach ($alerts as $alert): ?>
                                            <tr>
                                                <td><?php echo htmlspecialchars($alert['keywords'] ?: 'Any'); ?></td>
                                                <td><?php echo htmlspecialchars($alert['location'] ?: 'Any'); ?></td>
                                                <td><?php 
                                                    $job_types = [
                                                        '' => 'Any',
                                                        'full_time' => 'Full Time',
                                                        'part_time' => 'Part Time',
                                                        'contract' => 'Contract',
                                                        'internship' => 'Internship'
                                                    ];
                                                    echo $job_types[$alert['job_type']] ?? 'Any';
                                                ?></td>
                                                <td><?php echo ucfirst($alert['frequency']); ?></td>
                                                <td>
                                                    <span class="badge bg-<?php echo $alert['is_active'] ? 'success' : 'secondary'; ?>">
                                                        <?php echo $alert['is_active'] ? 'Active' : 'Inactive'; ?>
                                                    </span>
                                                </td>
                                                <td>
                                                    <form method="POST" action="" class="d-inline">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                                        <input type="hidden" name="alert_id" value="<?php echo $alert['id']; ?>">
                                                        <input type="hidden" name="is_active" value="<?php echo $alert['is_active'] ? '0' : '1'; ?>">
                                                        <input type="hidden" name="frequency" value="<?php echo $alert['frequency']; ?>">
                                                        <button type="submit" name="update_alert" class="btn btn-sm btn-outline-<?php echo $alert['is_active'] ? 'warning' : 'success'; ?> me-1" 
                                                                title="<?php echo $alert['is_active'] ? 'Deactivate' : 'Activate'; ?>">
                                                            <i class="fas fa-<?php echo $alert['is_active'] ? 'bell-slash' : 'bell'; ?>"></i>
                                                        </button>
                                                    </form>
                                                    <button type="button" class="btn btn-sm btn-outline-primary me-1" 
                                                            data-bs-toggle="modal" data-bs-target="#editAlertModal<?php echo $alert['id']; ?>"
                                                            title="Edit">
                                                        <i class="fas fa-edit"></i>
                                                    </button>
                                                    <form method="POST" action="" class="d-inline" 
                                                          onsubmit="return confirm('Are you sure you want to delete this alert?');">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                                        <input type="hidden" name="alert_id" value="<?php echo $alert['id']; ?>">
                                                        <button type="submit" name="delete_alert" class="btn btn-sm btn-outline-danger" title="Delete">
                                                            <i class="fas fa-trash"></i>
                                                        </button>
                                                    </form>

                                                    <!-- Edit Alert Modal -->
                                                    <div class="modal fade" id="editAlertModal<?php echo $alert['id']; ?>" tabindex="-1" 
                                                         aria-labelledby="editAlertModalLabel<?php echo $alert['id']; ?>" aria-hidden="true">
                                                        <div class="modal-dialog">
                                                            <div class="modal-content">
                                                                <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                                                    <div class="modal-header">
                                                                        <h5 class="modal-title" id="editAlertModalLabel<?php echo $alert['id']; ?>">Edit Job Alert</h5>
                                                                        <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                                                                    </div>
                                                                    <div class="modal-body">
                                                                        <div class="mb-3">
                                                                            <label for="keywords_<?php echo $alert['id']; ?>" class="form-label">Keywords</label>
                                                                            <input type="text" class="form-control" id="keywords_<?php echo $alert['id']; ?>" 
                                                                                   name="keywords" value="<?php echo htmlspecialchars($alert['keywords']); ?>">
                                                                        </div>
                                                                        <div class="mb-3">
                                                                            <label for="location_<?php echo $alert['id']; ?>" class="form-label">Location</label>
                                                                            <input type="text" class="form-control" id="location_<?php echo $alert['id']; ?>" 
                                                                                   name="location" value="<?php echo htmlspecialchars($alert['location']); ?>">
                                                                        </div>
                                                                        <div class="mb-3">
                                                                            <label for="job_type_<?php echo $alert['id']; ?>" class="form-label">Job Type</label>
                                                                            <select class="form-select" id="job_type_<?php echo $alert['id']; ?>" name="job_type">
                                                                                <option value="">Any Type</option>
                                                                                <option value="full_time" <?php echo $alert['job_type'] === 'full_time' ? 'selected' : ''; ?>>Full Time</option>
                                                                                <option value="part_time" <?php echo $alert['job_type'] === 'part_time' ? 'selected' : ''; ?>>Part Time</option>
                                                                                <option value="contract" <?php echo $alert['job_type'] === 'contract' ? 'selected' : ''; ?>>Contract</option>
                                                                                <option value="internship" <?php echo $alert['job_type'] === 'internship' ? 'selected' : ''; ?>>Internship</option>
                                                                            </select>
                                                                        </div>
                                                                        <div class="mb-3">
                                                                            <label for="frequency_<?php echo $alert['id']; ?>" class="form-label">Alert Frequency</label>
                                                                            <select class="form-select" id="frequency_<?php echo $alert['id']; ?>" name="frequency" required>
                                                                                <option value="daily" <?php echo $alert['frequency'] === 'daily' ? 'selected' : ''; ?>>Daily</option>
                                                                                <option value="weekly" <?php echo $alert['frequency'] === 'weekly' ? 'selected' : ''; ?>>Weekly</option>
                                                                                <option value="instant" <?php echo $alert['frequency'] === 'instant' ? 'selected' : ''; ?>>Instant (as soon as posted)</option>
                                                                            </select>
                                                                        </div>
                                                                        <div class="form-check form-switch mb-3">
                                                                            <input class="form-check-input" type="checkbox" id="is_active_<?php echo $alert['id']; ?>" 
                                                                                   name="is_active" value="1" <?php echo $alert['is_active'] ? 'checked' : ''; ?>>
                                                                            <label class="form-check-label" for="is_active_<?php echo $alert['id']; ?>">Active</label>
                                                                        </div>
                                                                    </div>
                                                                    <div class="modal-footer">
                                                                        <input type="hidden" name="alert_id" value="<?php echo $alert['id']; ?>">
                                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                                        <button type="submit" name="update_alert" class="btn btn-primary">Save Changes</button>
                                                                    </div>
                                                                </form>
                                                            </div>
                                                        </div>
                                                    </div>
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

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Enable Bootstrap tooltips
        var tooltipTriggerList = [].slice.call(document.querySelectorAll('[title]'));
        var tooltipList = tooltipTriggerList.map(function (tooltipTriggerEl) {
            return new bootstrap.Tooltip(tooltipTriggerEl);
        });
    </script>
    <?php prevent_back_navigation(); ?>
</body>
</html>
