<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/security.php';

// Require employer role
require_role('employer', '../login.php');

// Set security headers
set_security_headers();

// Get job ID from URL
$job_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Debug: Log the job ID and user ID
error_log("Attempting to edit job ID: " . $job_id . " for user ID: " . $_SESSION['user_id']);

// Get current user's department
$user_stmt = $pdo->prepare("SELECT department_id FROM users WHERE id = ?");
$user_stmt->execute([$_SESSION['user_id']]);
$user_data = $user_stmt->fetch();
$user_department_id = $user_data['department_id'];

// Get job details - employer can edit jobs from their department
$stmt = $pdo->prepare("SELECT * FROM jobs WHERE id = ? AND department_id = ?");
$stmt->execute([$job_id, $user_department_id]);
$job = $stmt->fetch();

// Debug: Log the job data found
if ($job) {
    error_log("Job found: " . print_r($job, true));
} else {
    error_log("Job not found or permission denied for job ID: " . $job_id . " and user department: " . $user_department_id);
    // Check if job exists at all (without permission check)
    $stmt = $pdo->prepare("SELECT * FROM jobs WHERE id = ?");
    $stmt->execute([$job_id]);
    $any_job = $stmt->fetch();
    if ($any_job) {
        error_log("Job exists but not in user's department. Job details: " . print_r($any_job, true));
    } else {
        error_log("Job with ID " . $job_id . " does not exist in the database");
    }
}

// Verify job exists and belongs to employer
if (!$job) {
    $_SESSION['error'] = 'Job not found or you do not have permission to edit it. Job ID: ' . $job_id;
    header('Location: dashboard.php');
    exit();
}

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize input
    $title = sanitize($_POST['title']);
    $description = sanitize($_POST['description']);
    $requirements = sanitize($_POST['requirements']);
    $employment_type = sanitize($_POST['employment_type']);
    $location = sanitize($_POST['location']);
    $salary_range = sanitize($_POST['salary_range']);
    $deadline = sanitize($_POST['deadline']);
    
    // Validate required fields
    $errors = [];
    if (empty($title)) $errors[] = 'Title is required';
    if (empty($description)) $errors[] = 'Description is required';
    if (empty($requirements)) $errors[] = 'Requirements are required';
    if (empty($location)) $errors[] = 'Location is required';
    if (empty($deadline)) {
        $errors[] = 'Deadline is required';
    } elseif (strtotime($deadline) < strtotime('today')) {
        $errors[] = 'Deadline must be in the future';
    }
    
    // If no errors, update job
    if (empty($errors)) {
        try {
            $stmt = $pdo->prepare("UPDATE jobs SET 
                title = ?, 
                description = ?, 
                requirements = ?, 
                employment_type = ?, 
                location = ?, 
                salary_range = ?, 
                deadline = ?,
                updated_at = NOW()
                WHERE id = ? AND department_id = ?");
                
            $success = $stmt->execute([
                $title,
                $description,
                $requirements,
                $employment_type,
                $location,
                $salary_range,
                $deadline,
                $job_id,
                $user_department_id
            ]);
            
            if ($success) {
                $_SESSION['success'] = 'Job updated successfully';
                header('Location: dashboard.php');
                exit();
            } else {
                $errors[] = 'Failed to update job. Please try again.';
            }
        } catch (PDOException $e) {
            error_log("Error updating job: " . $e->getMessage());
            $errors[] = 'An error occurred while updating the job. Please try again.';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Edit Job - OSTA Job Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container mt-4">
        <div class="d-flex justify-content-between align-items-center mb-4">
            <h2>Edit Job</h2>
            <a href="dashboard.php" class="btn btn-secondary">Back to Dashboard</a>
        </div>

        <?php if (!empty($errors)): ?>
            <div class="alert alert-danger">
                <ul class="mb-0">
                    <?php foreach ($errors as $error): ?>
                        <li><?php echo htmlspecialchars($error); ?></li>
                    <?php endforeach; ?>
                </ul>
            </div>
        <?php endif; ?>

        <div class="card">
            <div class="card-body">
                <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    <div class="mb-3">
                        <label for="title" class="form-label">Job Title</label>
                        <input type="text" class="form-control" id="title" name="title" 
                               value="<?php echo htmlspecialchars($job['title']); ?>" required>
                    </div>
                    
                    <div class="mb-3">
                        <label for="description" class="form-label">Job Description</label>
                        <textarea class="form-control" id="description" name="description" 
                                  rows="5" required><?php echo htmlspecialchars($job['description']); ?></textarea>
                    </div>
                    
                    <div class="mb-3">
                        <label for="requirements" class="form-label">Requirements</label>
                        <textarea class="form-control" id="requirements" name="requirements" 
                                  rows="5" required><?php echo htmlspecialchars($job['requirements']); ?></textarea>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="employment_type" class="form-label">Employment Type</label>
                            <select class="form-select" id="employment_type" name="employment_type" required>
                                <option value="full_time" <?php echo $job['employment_type'] === 'full_time' ? 'selected' : ''; ?>>Full Time</option>
                                <option value="part_time" <?php echo $job['employment_type'] === 'part_time' ? 'selected' : ''; ?>>Part Time</option>
                                <option value="contract" <?php echo $job['employment_type'] === 'contract' ? 'selected' : ''; ?>>Contract</option>
                            </select>
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="location" class="form-label">Location</label>
                            <input type="text" class="form-control" id="location" name="location" 
                                   value="<?php echo htmlspecialchars($job['location']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="row">
                        <div class="col-md-6 mb-3">
                            <label for="salary_range" class="form-label">Salary Range</label>
                            <input type="text" class="form-control" id="salary_range" name="salary_range" 
                                   value="<?php echo htmlspecialchars($job['salary_range']); ?>">
                        </div>
                        
                        <div class="col-md-6 mb-3">
                            <label for="deadline" class="form-label">Application Deadline</label>
                            <input type="date" class="form-control" id="deadline" name="deadline" 
                                   value="<?php echo htmlspecialchars($job['deadline']); ?>" required>
                        </div>
                    </div>
                    
                    <div class="d-grid">
                        <button type="submit" class="btn btn-primary">Update Job</button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Set minimum date to today for deadline field
        document.getElementById('deadline').min = new Date().toISOString().split('T')[0];
    </script>
    <?php prevent_back_navigation(); ?>
</body>
</html>
