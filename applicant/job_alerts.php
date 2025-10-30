<?php
// Start session at the very beginning
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../includes/functions.php';

// Initialize secure session and check authentication
init_secure_session();

// Check if user is logged in and has correct role
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'applicant') {
    header('Location: ' . SITE_URL . '/login.php?redirect=' . urlencode($_SERVER['REQUEST_URI']));
    exit();
}

$user_id = $_SESSION['user_id'];
$success_message = '';
$error_message = '';

// Handle form submission for creating/updating alerts
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error_message = 'Security token validation failed.';
    } else {
        try {
            $keywords = isset($_POST['keywords']) ? sanitize($_POST['keywords']) : '';
            $location = isset($_POST['location']) ? sanitize($_POST['location']) : '';
            $job_type = isset($_POST['job_type']) ? sanitize($_POST['job_type']) : '';
            $frequency = isset($_POST['frequency']) ? sanitize($_POST['frequency']) : 'daily';
            $is_active = isset($_POST['is_active']) ? 1 : 0;
            
            // Check if alert already exists for this user
            $stmt = $pdo->prepare("SELECT id FROM job_alerts WHERE user_id = ?");
            $stmt->execute([$user_id]);
            
            if ($stmt->rowCount() > 0) {
                // Update existing alert
                $stmt = $pdo->prepare("
                    UPDATE job_alerts 
                    SET keywords = ?, location = ?, job_type = ?, frequency = ?, is_active = ?, updated_at = NOW()
                    WHERE user_id = ?
                ");
                $stmt->execute([$keywords, $location, $job_type, $frequency, $is_active, $user_id]);
            } else {
                // Create new alert
                $stmt = $pdo->prepare("
                    INSERT INTO job_alerts 
                    (user_id, keywords, location, job_type, frequency, is_active, created_at, updated_at)
                    VALUES (?, ?, ?, ?, ?, ?, NOW(), NOW())
                ");
                $stmt->execute([$user_id, $keywords, $location, $job_type, $frequency, $is_active]);
            }
            
            $success_message = 'Job alert preferences saved successfully!';
            
        } catch (PDOException $e) {
            $error_message = 'An error occurred while saving your alert preferences. Please try again.';
            error_log("Job Alert Error: " . $e->getMessage());
        }
    }
}

// Get current alert settings
try {
    $stmt = $pdo->prepare("SELECT * FROM job_alerts WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $alert = $stmt->fetch(PDO::FETCH_ASSOC);
} catch (PDOException $e) {
    $error_message = 'Error retrieving alert settings.';
    error_log("Job Alert Error: " . $e->getMessage());
}

// Generate CSRF token
$csrf_token = generate_csrf_token();
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Alerts - OSTA Job Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/styles.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <div class="container py-5">
        <div class="row justify-content-center">
            <div class="col-md-8">
                <div class="card shadow">
                    <div class="card-header bg-primary text-white">
                        <h2 class="h5 mb-0">Job Alerts</h2>
                        <p class="mb-0">Set up email alerts for jobs matching your criteria</p>
                    </div>
                    
                    <div class="card-body">
                        <?php if ($success_message): ?>
                            <div class="alert alert-success"><?php echo $success_message; ?></div>
                        <?php endif; ?>
                        
                        <?php if ($error_message): ?>
                            <div class="alert alert-danger"><?php echo $error_message; ?></div>
                        <?php endif; ?>
                        
                        <form method="post" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo $csrf_token; ?>">
                            
                            <div class="mb-3">
                                <label for="keywords" class="form-label">Keywords</label>
                                <input type="text" class="form-control" id="keywords" name="keywords" 
                                       value="<?php echo htmlspecialchars($alert['keywords'] ?? ''); ?>" 
                                       placeholder="e.g., Software Engineer, Marketing Manager">
                                <div class="form-text">Separate multiple keywords with commas</div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="location" class="form-label">Location</label>
                                    <input type="text" class="form-control" id="location" name="location" 
                                           value="<?php echo htmlspecialchars($alert['location'] ?? ''); ?>" 
                                           placeholder="e.g., Nairobi, Remote">
                                </div>
                                
                                <div class="col-md-6 mb-3">
                                    <label for="job_type" class="form-label">Job Type</label>
                                    <select class="form-select" id="job_type" name="job_type">
                                        <option value="">Any Type</option>
                                        <option value="full_time" <?php echo (isset($alert['job_type']) && $alert['job_type'] === 'full_time') ? 'selected' : ''; ?>>Full-time</option>
                                        <option value="part_time" <?php echo (isset($alert['job_type']) && $alert['job_type'] === 'part_time') ? 'selected' : ''; ?>>Part-time</option>
                                        <option value="contract" <?php echo (isset($alert['job_type']) && $alert['job_type'] === 'contract') ? 'selected' : ''; ?>>Contract</option>
                                        <option value="internship" <?php echo (isset($alert['job_type']) && $alert['job_type'] === 'internship') ? 'selected' : ''; ?>>Internship</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="frequency" class="form-label">Alert Frequency</label>
                                <select class="form-select" id="frequency" name="frequency">
                                    <option value="daily" <?php echo (!isset($alert['frequency']) || $alert['frequency'] === 'daily') ? 'selected' : ''; ?>>Daily</option>
                                    <option value="weekly" <?php echo (isset($alert['frequency']) && $alert['frequency'] === 'weekly') ? 'selected' : ''; ?>>Weekly</option>
                                    <option value="monthly" <?php echo (isset($alert['frequency']) && $alert['frequency'] === 'monthly') ? 'selected' : ''; ?>>Monthly</option>
                                </select>
                            </div>
                            
                            <div class="form-check form-switch mb-4">
                                <input class="form-check-input" type="checkbox" id="is_active" name="is_active" 
                                       <?php echo (!isset($alert['is_active']) || $alert['is_active']) ? 'checked' : ''; ?>>
                                <label class="form-check-label" for="is_active">Enable email alerts</label>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="<?php echo SITE_URL; ?>/applicant/dashboard.php" class="btn btn-outline-secondary me-md-2">Back to Dashboard</a>
                                <button type="submit" class="btn btn-primary">Save Alert Preferences</button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
    
    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Add any client-side validation or functionality here
        document.addEventListener('DOMContentLoaded', function() {
            // Example: Add input validation or dynamic behavior
        });
    </script>
</body>
</html>
