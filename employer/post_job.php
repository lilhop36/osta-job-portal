<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/security.php';

// Require employer role
require_role('employer', '../login.php');

// Set security headers
set_security_headers();

// Get user's department
$stmt = $pdo->prepare("SELECT department_id FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Handle job posting
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verify_csrf_token(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '')) {
        $errors[] = "Invalid CSRF token. Please try again.";
    } else {
        // Sanitize inputs
        $title = sanitize(isset($_POST['title']) ? $_POST['title'] : '');
        $description = sanitize(isset($_POST['description']) ? $_POST['description'] : '');
        $requirements = sanitize(isset($_POST['requirements']) ? $_POST['requirements'] : '');
        $responsibilities = sanitize(isset($_POST['responsibilities']) ? $_POST['responsibilities'] : '');
        $deadline = sanitize(isset($_POST['deadline']) ? $_POST['deadline'] : '');
        $salary = sanitize($_POST['salary'] ?? '');
        $location = sanitize($_POST['location'] ?? '');
        $employment_type = sanitize($_POST['employment_type'] ?? '');
        
        // Validate inputs
        $errors = [];
        
        // Required fields validation
        if (empty($title)) $errors[] = "Job title is required";
        if (empty($description)) $errors[] = "Job description is required";
        if (empty($requirements)) $errors[] = "Job requirements are required";
        if (empty($deadline)) $errors[] = "Application deadline is required";
        if (empty($location)) $errors[] = "Job location is required";
        if (empty($employment_type)) $errors[] = "Employment type is required";
        
        // Date validation
        if (!empty($deadline) && strtotime($deadline) < time()) {
            $errors[] = "Deadline must be in the future";
        }
    }
    
    if (empty($errors)) {
        try {
            // Start transaction
            $pdo->beginTransaction();
            
            // Insert job
            $stmt = $pdo->prepare("INSERT INTO jobs (
                title, description, requirements, responsibilities, 
                deadline, salary, location, employment_type, 
                department_id, status, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)");
            
            $stmt->execute([
                $title, $description, $requirements, $responsibilities,
                $deadline, $salary, $location, $employment_type,
                $user['department_id'], $_SESSION['user_id']
            ]);
            
            $job_id = $pdo->lastInsertId();
            
            // Create uploads directory if it doesn't exist
            $upload_dir = "../uploads/job_attachments/";
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            // Handle attachments if any
            if (isset($_FILES['attachments']) && !empty($_FILES['attachments']['name'][0])) {
                $allowed_types = ['pdf', 'doc', 'docx'];
                $max_size = 5 * 1024 * 1024; // 5MB
                
                foreach ($_FILES['attachments']['name'] as $key => $name) {
                    if ($_FILES['attachments']['error'][$key] === UPLOAD_ERR_OK) {
                        $file_info = pathinfo($name);
                        $extension = strtolower($file_info['extension'] ?? '');
                        
                        if (!in_array($extension, $allowed_types)) {
                            $errors[] = "Invalid file type for '{$name}'. Only PDF, DOC, and DOCX are allowed";
                            continue;
                        }
                        
                        if ($_FILES['attachments']['size'][$key] > $max_size) {
                            $errors[] = "File '{$name}' is too large. Maximum size is 5MB";
                            continue;
                        }
                        
                        $safe_filename = uniqid() . '.' . $extension;
                        $target_file = $upload_dir . $safe_filename;
                        
                        if (move_uploaded_file($_FILES['attachments']['tmp_name'][$key], $target_file)) {
                            $stmt = $pdo->prepare("INSERT INTO job_attachments (job_id, file_path, original_name) VALUES (?, ?, ?)");
                            $stmt->execute([$job_id, $safe_filename, $name]);
                        } else {
                            $errors[] = "Failed to upload file '{$name}'";
                        }
                    } elseif ($_FILES['attachments']['error'][$key] !== UPLOAD_ERR_NO_FILE) {
                        $errors[] = "Error uploading file '{$name}': " . get_upload_error_message($_FILES['attachments']['error'][$key]);
                    }
                }
            }
        
            if (empty($errors)) {
                $pdo->commit();
                $_SESSION['success_message'] = "Job posted successfully. Waiting for admin approval.";
                header('Location: dashboard.php');
                exit();
            } else {
                // If there were file upload errors, rollback the transaction
                $pdo->rollBack();
                // Delete any uploaded files if transaction fails
                if (isset($target_file) && file_exists($target_file)) {
                    unlink($target_file);
                }
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "An error occurred while posting the job: " . $e->getMessage();
            // Log the error
            error_log("Job posting error: " . $e->getMessage());
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Post Job - OSTA Job Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
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
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item"><a href="dashboard.php">Dashboard</a></li>
                            <li class="list-group-item active">Post Job</li>
                            <li class="list-group-item"><a href="manage_jobs.php">Manage Jobs</a></li>
                            <li class="list-group-item"><a href="view_applicants.php">View Applicants</a></li>
                            <li class="list-group-item"><a href="manage_applications.php">Manage Applications</a></li>
                            <li class="list-group-item"><a href="change_password.php">Change Password</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Job Posting Form -->
            <div class="col-md-9">
                <div class="card">
                    <div class="card-header">
                        <h3 class="mb-0">Post New Job</h3>
                    </div>
                    <div class="card-body">
                            <?php if (isset($_SESSION['success_message'])): ?>
                                <div class="alert alert-success">
                                    <i class="fas fa-check-circle me-2"></i>
                                    <?php echo $_SESSION['success_message']; ?>
                                </div>
                                <?php unset($_SESSION['success_message']); ?>
                            <?php endif; ?>
                            
                            <?php if (!empty($errors)): ?>
                                <div class="alert alert-danger">
                                    <h5 class="alert-heading">Please fix the following errors:</h5>
                                    <ul class="mb-0">
                                        <?php foreach ($errors as $error): ?>
                                            <li><?php echo htmlspecialchars($error); ?></li>
                                        <?php endforeach; ?>
                                    </ul>
                                </div>
                            <?php endif; ?>
                        
                        <form method="POST" action="" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            <div class="mb-3">
                                <label for="title" class="form-label">Job Title *</label>
                                <input type="text" class="form-control" id="title" name="title" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label">Job Description *</label>
                                <textarea class="form-control" id="description" name="description" rows="5" required></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="requirements" class="form-label">Requirements *</label>
                                <textarea class="form-control" id="requirements" name="requirements" rows="5" 
                                          placeholder="List each requirement on a new line" required></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="responsibilities" class="form-label">Responsibilities *</label>
                                <textarea class="form-control" id="responsibilities" name="responsibilities" rows="5" 
                                          placeholder="List each responsibility on a new line" required></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="deadline" class="form-label">Application Deadline *</label>
                                    <input type="date" class="form-control" id="deadline" name="deadline" 
                                           min="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="salary" class="form-label">Salary Range</label>
                                    <input type="text" class="form-control" id="salary" name="salary" 
                                           placeholder="e.g., 50,000 - 100,000 ETB">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="location" class="form-label">Location *</label>
                                    <input type="text" class="form-control" id="location" name="location" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="employment_type" class="form-label">Employment Type *</label>
                                    <select class="form-control" id="employment_type" name="employment_type" required>
                                        <option value="">Select Employment Type</option>
                                        <option value="full_time">Full Time</option>
                                        <option value="part_time">Part Time</option>
                                        <option value="contract">Contract</option>
                                        <option value="internship">Internship</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="mb-3">
                                <label for="attachments" class="form-label">Job Attachments (Optional)</label>
                                <input type="file" class="form-control" id="attachments" name="attachments[]" 
                                       accept=".pdf,.doc,.docx" multiple>
                                <div class="form-text">You can upload job description documents, requirement specifications, etc.</div>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end mt-4">
                                <a href="dashboard.php" class="btn btn-outline-secondary me-md-2">Cancel</a>
                                <button type="submit" class="btn btn-primary">
                                    <i class="fas fa-paper-plane me-1"></i> Post Job
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <?php prevent_back_navigation(); ?>
</body>
</html>
