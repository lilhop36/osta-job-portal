<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/security.php';
require_once '../includes/functions.php';

// Require employer role
require_role('employer', '../login.php');

// Set security headers
set_security_headers();

// Get departments for dropdown
$departments_stmt = $pdo->query("SELECT id, name FROM departments ORDER BY name");
$departments = $departments_stmt->fetchAll();

// Handle job posting
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verify_csrf_token(isset($_POST['csrf_token']) ? $_POST['csrf_token'] : '')) {
        $errors[] = "Invalid CSRF token. Please try again.";
    } else {
        // Sanitize inputs
        $title = sanitize($_POST['title'] ?? '');
        $description = sanitize($_POST['description'] ?? '');
        $requirements = sanitize($_POST['requirements'] ?? '');
        $responsibilities = sanitize($_POST['responsibilities'] ?? '');
        $deadline = sanitize($_POST['deadline'] ?? '');
        $salary = sanitize($_POST['salary'] ?? '');
        $location = sanitize($_POST['location'] ?? '');
        $employment_type = sanitize($_POST['employment_type'] ?? '');
        $department_id = !empty($_POST['department_id']) ? (int)$_POST['department_id'] : null;
        
        // Validate inputs
        $errors = [];
        
        if (empty($title)) $errors[] = "Job title is required";
        if (empty($description)) $errors[] = "Job description is required";
        if (empty($requirements)) $errors[] = "Job requirements are required";
        if (empty($deadline)) $errors[] = "Application deadline is required";
        if (empty($location)) $errors[] = "Job location is required";
        if (empty($employment_type)) $errors[] = "Employment type is required";
        
        if (!empty($deadline) && strtotime($deadline) < time()) {
            $errors[] = "Deadline must be in the future";
        }
    }
    
    if (empty($errors)) {
        try {
            $pdo->beginTransaction();
            
            $stmt = $pdo->prepare("INSERT INTO jobs (
                title, description, requirements, responsibilities, 
                deadline, salary, location, employment_type, 
                department_id, status, created_by
            ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, 'pending', ?)");
            
            $stmt->execute([
                $title, $description, $requirements, $responsibilities,
                $deadline, $salary, $location, $employment_type,
                $department_id, $_SESSION['user_id']
            ]);
            
            $job_id = $pdo->lastInsertId();
            
            $upload_dir = "../uploads/job_attachments/";
            if (!file_exists($upload_dir)) {
                mkdir($upload_dir, 0777, true);
            }
            
            if (isset($_FILES['attachments']) && !empty($_FILES['attachments']['name'][0])) {
                $allowed_types = ['pdf', 'doc', 'docx'];
                $max_size = 5 * 1024 * 1024;
                
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
                        $errors[] = "Error uploading file '{$name}'";
                    }
                }
            }
        
            if (empty($errors)) {
                $pdo->commit();

                // Notify all admins that a new job is pending approval
                $admin_stmt = $pdo->prepare("SELECT id FROM users WHERE role = 'admin'");
                $admin_stmt->execute();
                while ($admin = $admin_stmt->fetch()) {
                    create_notification(
                        'New Job Pending Approval',
                        'A new job "' . $title . '" has been submitted and is waiting for your approval.',
                        'warning',
                        'user',
                        $admin['id'],
                        $_SESSION['user_id']
                    );
                }

                $_SESSION['success_message'] = "Job posted successfully. Waiting for admin approval.";
                header('Location: dashboard.php');
                exit();
            } else {
                $pdo->rollBack();
                if (isset($target_file) && file_exists($target_file)) {
                    unlink($target_file);
                }
            }
        } catch (Exception $e) {
            $pdo->rollBack();
            $errors[] = "An error occurred while posting the job: " . $e->getMessage();
            error_log("Job posting error: " . $e->getMessage());
        }
    }
}
?>

<?php include '../includes/header.php'; ?>

    <div class="container-fluid mt-4">
        <div class="row">
            <div class="col-md-3">
                <?php include __DIR__ . '/../includes/employer_sidebar.php'; ?>
            </div>

            <div class="col-md-9">
                <div class="card dashboard-card">
                    <div class="card-header bg-white">
                        <h4 class="mb-0"><i class="fas fa-plus-circle me-2 text-primary"></i>Post New Job</h4>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_SESSION['success_message'])): ?>
                            <div class="alert alert-success alert-dismissible fade show">
                                <i class="fas fa-check-circle me-2"></i>
                                <?php echo htmlspecialchars($_SESSION['success_message'], ENT_QUOTES, 'UTF-8'); ?>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                            <?php unset($_SESSION['success_message']); ?>
                        <?php endif; ?>
                        
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger alert-dismissible fade show">
                                <h5 class="alert-heading"><i class="fas fa-exclamation-triangle me-2"></i>Please fix the following errors:</h5>
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                                <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                            </div>
                        <?php endif; ?>
                    
                        <form method="POST" action="" enctype="multipart/form-data">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            
                            <div class="mb-3">
                                <label for="title" class="form-label fw-bold">Job Title *</label>
                                <input type="text" class="form-control" id="title" name="title" 
                                       value="<?php echo htmlspecialchars($title ?? ''); ?>" required>
                            </div>
                            
                            <div class="mb-3">
                                <label for="description" class="form-label fw-bold">Job Description *</label>
                                <textarea class="form-control" id="description" name="description" rows="5" required><?php echo htmlspecialchars($description ?? ''); ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="requirements" class="form-label fw-bold">Requirements *</label>
                                <textarea class="form-control" id="requirements" name="requirements" rows="5" 
                                          placeholder="List each requirement on a new line" required><?php echo htmlspecialchars($requirements ?? ''); ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="responsibilities" class="form-label fw-bold">Responsibilities *</label>
                                <textarea class="form-control" id="responsibilities" name="responsibilities" rows="5" 
                                          placeholder="List each responsibility on a new line" required><?php echo htmlspecialchars($responsibilities ?? ''); ?></textarea>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="department_id" class="form-label fw-bold">Department</label>
                                    <select class="form-select" id="department_id" name="department_id">
                                        <option value="">Select Department (Optional)</option>
                                        <?php foreach ($departments as $dept): ?>
                                            <option value="<?php echo $dept['id']; ?>" <?php echo (($department_id ?? '') == $dept['id']) ? 'selected' : ''; ?>>
                                                <?php echo htmlspecialchars($dept['name']); ?>
                                            </option>
                                        <?php endforeach; ?>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="employment_type" class="form-label fw-bold">Employment Type *</label>
                                    <select class="form-select" id="employment_type" name="employment_type" required>
                                        <option value="">Select Type</option>
                                        <option value="full_time" <?php echo (($employment_type ?? '') === 'full_time') ? 'selected' : ''; ?>>Full Time</option>
                                        <option value="part_time" <?php echo (($employment_type ?? '') === 'part_time') ? 'selected' : ''; ?>>Part Time</option>
                                        <option value="contract" <?php echo (($employment_type ?? '') === 'contract') ? 'selected' : ''; ?>>Contract</option>
                                        <option value="internship" <?php echo (($employment_type ?? '') === 'internship') ? 'selected' : ''; ?>>Internship</option>
                                    </select>
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="location" class="form-label fw-bold">Location *</label>
                                    <input type="text" class="form-control" id="location" name="location" 
                                           value="<?php echo htmlspecialchars($location ?? ''); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="salary" class="form-label fw-bold">Salary Range</label>
                                    <input type="text" class="form-control" id="salary" name="salary" 
                                           value="<?php echo htmlspecialchars($salary ?? ''); ?>"
                                           placeholder="e.g., 15,000 - 25,000 ETB">
                                </div>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="deadline" class="form-label fw-bold">Application Deadline *</label>
                                    <input type="date" class="form-control" id="deadline" name="deadline" 
                                           value="<?php echo htmlspecialchars($deadline ?? ''); ?>"
                                           min="<?php echo date('Y-m-d'); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="attachments" class="form-label fw-bold">Attachments (Optional)</label>
                                    <input type="file" class="form-control" id="attachments" name="attachments[]" 
                                           accept=".pdf,.doc,.docx" multiple>
                                    <div class="form-text">PDF, DOC, DOCX — max 5MB each</div>
                                </div>
                            </div>
                            
                            <div class="d-flex justify-content-end gap-2 mt-4 pt-3 border-top">
                                <a href="dashboard.php" class="btn btn-outline-secondary">
                                    <i class="fas fa-times me-1"></i>Cancel
                                </a>
                                <button type="submit" class="btn btn-primary px-4">
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
<?php prevent_back_navigation(); ?>
