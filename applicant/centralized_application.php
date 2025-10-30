<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Centralized Application - OSTA Job Portal</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/security.php';
require_once '../includes/auth.php';
require_once '../includes/application_functions.php';

// Ensure CSRF token is generated early
generate_csrf_token();

// Require authentication and applicant role
require_auth('applicant');

$user_id = $_SESSION['user_id'];
$error_message = '';
$success_message = '';

// Check if user already has a centralized application
$existing_application = false;
try {
    $stmt = $pdo->prepare("SELECT * FROM centralized_applications WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $existing_application = $stmt->fetch();
} catch (PDOException $e) {
    // Table doesn't exist yet - redirect to table creation
    header('Location: ../create_tables.php');
    exit;
}

// Get departments for selection
$dept_stmt = $pdo->prepare("SELECT id, name, description FROM departments ORDER BY name");
$dept_stmt->execute();
$departments = $dept_stmt->fetchAll();

// Get user info for pre-filling
$user_stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$user_stmt->execute([$user_id]);
$user_info = $user_stmt->fetch();

// Handle form submission
if ($_POST) {
    // Debug CSRF token
    error_log("POST csrf_token: " . ($_POST['csrf_token'] ?? 'NOT SET'));
    error_log("SESSION csrf_token: " . ($_SESSION['csrf_token'] ?? 'NOT SET'));
    
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error_message = 'Invalid security token. Please try again. Debug: POST=' . ($_POST['csrf_token'] ?? 'missing') . ', SESSION=' . ($_SESSION['csrf_token'] ?? 'missing');
    } else {
    try {
        $pdo->beginTransaction();
        
        // Validate required fields
        $required_fields = [
            'first_name', 'last_name', 'email', 'phone', 'national_id',
            'date_of_birth', 'gender', 'address', 'city', 'region',
            'education_level', 'field_of_study', 'institution', 'graduation_year'
        ];
        
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Please fill in all required fields.");
            }
        }
        
        // Validate preferred departments
        if (empty($_POST['preferred_departments'])) {
            throw new Exception("Please select at least one preferred department.");
        }
        
        // Prepare data
        $preferred_departments = json_encode($_POST['preferred_departments']);
        $preferred_positions = isset($_POST['preferred_positions']) ? json_encode($_POST['preferred_positions']) : json_encode([]);
        
        if ($existing_application) {
            // Update existing application
            $sql = "UPDATE centralized_applications SET 
                    first_name = ?, last_name = ?, email = ?, phone = ?, national_id = ?,
                    date_of_birth = ?, gender = ?, address = ?, city = ?, region = ?,
                    education_level = ?, field_of_study = ?, institution = ?, graduation_year = ?,
                    gpa = ?, years_of_experience = ?, current_position = ?, current_employer = ?,
                    preferred_departments = ?, preferred_positions = ?, willing_to_relocate = ?,
                    expected_salary_min = ?, expected_salary_max = ?, updated_at = NOW()
                    WHERE user_id = ?";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $_POST['first_name'], $_POST['last_name'], $_POST['email'], $_POST['phone'], $_POST['national_id'],
                $_POST['date_of_birth'], $_POST['gender'], $_POST['address'], $_POST['city'], $_POST['region'],
                $_POST['education_level'], $_POST['field_of_study'], $_POST['institution'], $_POST['graduation_year'],
                $_POST['gpa'] ?: null, $_POST['years_of_experience'] ?: 0, $_POST['current_position'] ?: null, $_POST['current_employer'] ?: null,
                $preferred_departments, $preferred_positions, isset($_POST['willing_to_relocate']) ? 1 : 0,
                $_POST['expected_salary_min'] ?: null, $_POST['expected_salary_max'] ?: null, $user_id
            ]);
            
            $application_id = $existing_application['id'];
            $success_message = "Application updated successfully!";
        } else {
            // Generate unique application number
            $year = date('Y');
            $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM centralized_applications WHERE application_number LIKE ?");
            $count_stmt->execute(["OSTA-{$year}-%"]);
            $count = $count_stmt->fetchColumn() + 1;
            $application_number = sprintf("OSTA-%s-%03d", $year, $count);
            
            // Create new application
            $sql = "INSERT INTO centralized_applications (
                    user_id, application_number, first_name, last_name, email, phone, national_id,
                    date_of_birth, gender, address, city, region,
                    education_level, field_of_study, institution, graduation_year,
                    gpa, years_of_experience, current_position, current_employer,
                    preferred_departments, preferred_positions, willing_to_relocate,
                    expected_salary_min, expected_salary_max, status
                    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'draft')";
            
            $stmt = $pdo->prepare($sql);
            $stmt->execute([
                $user_id, $application_number, $_POST['first_name'], $_POST['last_name'], $_POST['email'], $_POST['phone'], $_POST['national_id'],
                $_POST['date_of_birth'], $_POST['gender'], $_POST['address'], $_POST['city'], $_POST['region'],
                $_POST['education_level'], $_POST['field_of_study'], $_POST['institution'], $_POST['graduation_year'],
                $_POST['gpa'] ?: null, $_POST['years_of_experience'] ?: 0, $_POST['current_position'] ?: null, $_POST['current_employer'] ?: null,
                $preferred_departments, $preferred_positions, isset($_POST['willing_to_relocate']) ? 1 : 0,
                $_POST['expected_salary_min'] ?: null, $_POST['expected_salary_max'] ?: null
            ]);
            
            $application_id = $pdo->lastInsertId();
            $success_message = "Application created successfully!";
        }
        
        // Log the action
        log_audit_action($user_id, 'create_application', 'centralized_applications', $application_id, 
                        'Created/updated centralized application');
        
        $pdo->commit();
        
        // Refresh application data
        $stmt = $pdo->prepare("SELECT * FROM centralized_applications WHERE user_id = ?");
        $stmt->execute([$user_id]);
        $existing_application = $stmt->fetch();
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_message = $e->getMessage();
        log_debug("Application creation error: " . $e->getMessage());
    }
}

// Handle application submission
if (isset($_POST['submit_application']) && verify_csrf_token($_POST['csrf_token'])) {
    if ($existing_application && $existing_application['status'] === 'draft') {
        try {
            // Run eligibility checks before submission
            $eligibility_result = run_eligibility_checks($existing_application['id']);
            
            // Update application status
            $stmt = $pdo->prepare("UPDATE centralized_applications SET status = 'submitted', submitted_at = NOW() WHERE id = ?");
            $stmt->execute([$existing_application['id']]);
            
            // Send notification
            queue_notification($user_id, 'APP_SUBMITTED', [
                'first_name' => $existing_application['first_name'],
                'application_number' => $existing_application['application_number']
            ]);
            
            $success_message = "Application submitted successfully! Application Number: " . $existing_application['application_number'];
            
            // Check if user was trying to apply for a specific job
            if (isset($_SESSION['apply_after_profile'])) {
                $job_id = $_SESSION['apply_after_profile'];
                unset($_SESSION['apply_after_profile']);
                $_SESSION['success_message'] = $success_message . " You can now apply for the job.";
                header('Location: ../apply.php?job_id=' . $job_id);
                exit();
            }
            
            // Refresh data
            $stmt = $pdo->prepare("SELECT * FROM centralized_applications WHERE user_id = ?");
            $stmt->execute([$user_id]);
            $existing_application = $stmt->fetch();
            
        } catch (Exception $e) {
            $pdo->rollBack();
            $error_message = "Error submitting application: " . $e->getMessage();
        }
    }
    }
}

$page_title = "Centralized Application";
include '../includes/header_new.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-file-alt me-2"></i>
                        OSTA Centralized Job Application
                    </h4>
                    <?php if ($existing_application): ?>
                        <small>Application Number: <?= htmlspecialchars($existing_application['application_number']) ?></small>
                        <span class="badge bg-<?= get_status_color($existing_application['status']) ?> ms-2">
                            <?= ucfirst(str_replace('_', ' ', $existing_application['status'])) ?>
                        </span>
                    <?php endif; ?>
                </div>
                
                <div class="card-body">
                    <?php if ($error_message): ?>
                        <div class="alert alert-danger">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            <?= htmlspecialchars($error_message) ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($success_message): ?>
                        <div class="alert alert-success">
                            <i class="fas fa-check-circle me-2"></i>
                            <?= htmlspecialchars($success_message) ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if ($existing_application && $existing_application['status'] !== 'draft'): ?>
                        <div class="alert alert-info">
                            <i class="fas fa-info-circle me-2"></i>
                            Your application has been submitted and is currently <strong><?= ucfirst(str_replace('_', ' ', $existing_application['status'])) ?></strong>.
                            You can view your application status in the <a href="dashboard.php">dashboard</a>.
                        </div>
                    <?php endif; ?>
                    
                    <form method="POST" enctype="multipart/form-data" class="needs-validation" novalidate>
                        <?= csrf_token_field() ?>
                        
                        <!-- Personal Information -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="text-primary border-bottom pb-2">
                                    <i class="fas fa-user me-2"></i>Personal Information
                                </h5>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="first_name" class="form-label">First Name *</label>
                                <input type="text" class="form-control" id="first_name" name="first_name" 
                                       value="<?= htmlspecialchars($existing_application['first_name'] ?? '') ?>" 
                                       required <?= ($existing_application && $existing_application['status'] !== 'draft') ? 'readonly' : '' ?>>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="last_name" class="form-label">Last Name *</label>
                                <input type="text" class="form-control" id="last_name" name="last_name" 
                                       value="<?= htmlspecialchars($existing_application['last_name'] ?? '') ?>" 
                                       required <?= ($existing_application && $existing_application['status'] !== 'draft') ? 'readonly' : '' ?>>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="email" class="form-label">Email Address *</label>
                                <input type="email" class="form-control" id="email" name="email" 
                                       value="<?= htmlspecialchars($existing_application['email'] ?? $user_info['email']) ?>" 
                                       required <?= ($existing_application && $existing_application['status'] !== 'draft') ? 'readonly' : '' ?>>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="phone" class="form-label">Phone Number *</label>
                                <input type="tel" class="form-control" id="phone" name="phone" 
                                       value="<?= htmlspecialchars($existing_application['phone'] ?? '') ?>" 
                                       required <?= ($existing_application && $existing_application['status'] !== 'draft') ? 'readonly' : '' ?>>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="national_id" class="form-label">National ID *</label>
                                <input type="text" class="form-control" id="national_id" name="national_id" 
                                       value="<?= htmlspecialchars($existing_application['national_id'] ?? '') ?>" 
                                       required <?= ($existing_application && $existing_application['status'] !== 'draft') ? 'readonly' : '' ?>>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="date_of_birth" class="form-label">Date of Birth *</label>
                                <input type="date" class="form-control" id="date_of_birth" name="date_of_birth" 
                                       value="<?= htmlspecialchars($existing_application['date_of_birth'] ?? '') ?>" 
                                       required <?= ($existing_application && $existing_application['status'] !== 'draft') ? 'readonly' : '' ?>>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="gender" class="form-label">Gender *</label>
                                <select class="form-select" id="gender" name="gender" required 
                                        <?= ($existing_application && $existing_application['status'] !== 'draft') ? 'disabled' : '' ?>>
                                    <option value="">Select Gender</option>
                                    <option value="male" <?= ($existing_application['gender'] ?? '') === 'male' ? 'selected' : '' ?>>Male</option>
                                    <option value="female" <?= ($existing_application['gender'] ?? '') === 'female' ? 'selected' : '' ?>>Female</option>
                                    <option value="other" <?= ($existing_application['gender'] ?? '') === 'other' ? 'selected' : '' ?>>Other</option>
                                </select>
                            </div>
                            
                            <div class="col-12 mb-3">
                                <label for="address" class="form-label">Address *</label>
                                <textarea class="form-control" id="address" name="address" rows="2" required 
                                          <?= ($existing_application && $existing_application['status'] !== 'draft') ? 'readonly' : '' ?>><?= htmlspecialchars($existing_application['address'] ?? '') ?></textarea>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="city" class="form-label">City *</label>
                                <input type="text" class="form-control" id="city" name="city" 
                                       value="<?= htmlspecialchars($existing_application['city'] ?? '') ?>" 
                                       required <?= ($existing_application && $existing_application['status'] !== 'draft') ? 'readonly' : '' ?>>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="region" class="form-label">Region *</label>
                                <input type="text" class="form-control" id="region" name="region" 
                                       value="<?= htmlspecialchars($existing_application['region'] ?? '') ?>" 
                                       required <?= ($existing_application && $existing_application['status'] !== 'draft') ? 'readonly' : '' ?>>
                            </div>
                        </div>
                        
                        <!-- Education Information -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="text-primary border-bottom pb-2">
                                    <i class="fas fa-graduation-cap me-2"></i>Education Information
                                </h5>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="education_level" class="form-label">Education Level *</label>
                                <select class="form-select" id="education_level" name="education_level" required 
                                        <?= ($existing_application && $existing_application['status'] !== 'draft') ? 'disabled' : '' ?>>
                                    <option value="">Select Education Level</option>
                                    <option value="high_school" <?= ($existing_application['education_level'] ?? '') === 'high_school' ? 'selected' : '' ?>>High School</option>
                                    <option value="diploma" <?= ($existing_application['education_level'] ?? '') === 'diploma' ? 'selected' : '' ?>>Diploma</option>
                                    <option value="bachelor" <?= ($existing_application['education_level'] ?? '') === 'bachelor' ? 'selected' : '' ?>>Bachelor's Degree</option>
                                    <option value="master" <?= ($existing_application['education_level'] ?? '') === 'master' ? 'selected' : '' ?>>Master's Degree</option>
                                    <option value="phd" <?= ($existing_application['education_level'] ?? '') === 'phd' ? 'selected' : '' ?>>PhD</option>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="field_of_study" class="form-label">Field of Study *</label>
                                <input type="text" class="form-control" id="field_of_study" name="field_of_study" 
                                       value="<?= htmlspecialchars($existing_application['field_of_study'] ?? '') ?>" 
                                       required <?= ($existing_application && $existing_application['status'] !== 'draft') ? 'readonly' : '' ?>>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="institution" class="form-label">Institution *</label>
                                <input type="text" class="form-control" id="institution" name="institution" 
                                       value="<?= htmlspecialchars($existing_application['institution'] ?? '') ?>" 
                                       required <?= ($existing_application && $existing_application['status'] !== 'draft') ? 'readonly' : '' ?>>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="graduation_year" class="form-label">Graduation Year *</label>
                                <input type="number" class="form-control" id="graduation_year" name="graduation_year" 
                                       min="1950" max="<?= date('Y') ?>" 
                                       value="<?= htmlspecialchars($existing_application['graduation_year'] ?? '') ?>" 
                                       required <?= ($existing_application && $existing_application['status'] !== 'draft') ? 'readonly' : '' ?>>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="gpa" class="form-label">GPA (Optional)</label>
                                <input type="number" class="form-control" id="gpa" name="gpa" 
                                       min="0" max="4" step="0.01"
                                       value="<?= htmlspecialchars($existing_application['gpa'] ?? '') ?>" 
                                       <?= ($existing_application && $existing_application['status'] !== 'draft') ? 'readonly' : '' ?>>
                            </div>
                        </div>
                        
                        <!-- Experience Information -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="text-primary border-bottom pb-2">
                                    <i class="fas fa-briefcase me-2"></i>Experience Information
                                </h5>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="years_of_experience" class="form-label">Years of Experience</label>
                                <input type="number" class="form-control" id="years_of_experience" name="years_of_experience" 
                                       min="0" max="50"
                                       value="<?= htmlspecialchars($existing_application['years_of_experience'] ?? '0') ?>" 
                                       <?= ($existing_application && $existing_application['status'] !== 'draft') ? 'readonly' : '' ?>>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="current_position" class="form-label">Current Position</label>
                                <input type="text" class="form-control" id="current_position" name="current_position" 
                                       value="<?= htmlspecialchars($existing_application['current_position'] ?? '') ?>" 
                                       <?= ($existing_application && $existing_application['status'] !== 'draft') ? 'readonly' : '' ?>>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="current_employer" class="form-label">Current Employer</label>
                                <input type="text" class="form-control" id="current_employer" name="current_employer" 
                                       value="<?= htmlspecialchars($existing_application['current_employer'] ?? '') ?>" 
                                       <?= ($existing_application && $existing_application['status'] !== 'draft') ? 'readonly' : '' ?>>
                            </div>
                        </div>
                        
                        <!-- Preferences -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="text-primary border-bottom pb-2">
                                    <i class="fas fa-heart me-2"></i>Job Preferences
                                </h5>
                            </div>
                            
                            <div class="col-12 mb-3">
                                <label class="form-label">Preferred Departments * (Select at least one)</label>
                                <div class="row">
                                    <?php 
                                    $selected_departments = $existing_application ? json_decode($existing_application['preferred_departments'], true) : [];
                                    foreach ($departments as $dept): 
                                    ?>
                                        <div class="col-md-6 col-lg-4 mb-2">
                                            <div class="form-check">
                                                <input class="form-check-input" type="checkbox" 
                                                       id="dept_<?= $dept['id'] ?>" name="preferred_departments[]" 
                                                       value="<?= $dept['id'] ?>"
                                                       <?= in_array($dept['id'], $selected_departments) ? 'checked' : '' ?>
                                                       <?= ($existing_application && $existing_application['status'] !== 'draft') ? 'disabled' : '' ?>>
                                                <label class="form-check-label" for="dept_<?= $dept['id'] ?>">
                                                    <?= htmlspecialchars($dept['name']) ?>
                                                </label>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="expected_salary_min" class="form-label">Expected Salary (Min)</label>
                                <input type="number" class="form-control" id="expected_salary_min" name="expected_salary_min" 
                                       min="0" step="1000"
                                       value="<?= htmlspecialchars($existing_application['expected_salary_min'] ?? '') ?>" 
                                       <?= ($existing_application && $existing_application['status'] !== 'draft') ? 'readonly' : '' ?>>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="expected_salary_max" class="form-label">Expected Salary (Max)</label>
                                <input type="number" class="form-control" id="expected_salary_max" name="expected_salary_max" 
                                       min="0" step="1000"
                                       value="<?= htmlspecialchars($existing_application['expected_salary_max'] ?? '') ?>" 
                                       <?= ($existing_application && $existing_application['status'] !== 'draft') ? 'readonly' : '' ?>>
                            </div>
                            
                            <div class="col-12 mb-3">
                                <div class="form-check">
                                    <input class="form-check-input" type="checkbox" id="willing_to_relocate" 
                                           name="willing_to_relocate" value="1"
                                           <?= ($existing_application['willing_to_relocate'] ?? false) ? 'checked' : '' ?>
                                           <?= ($existing_application && $existing_application['status'] !== 'draft') ? 'disabled' : '' ?>>
                                    <label class="form-check-label" for="willing_to_relocate">
                                        I am willing to relocate for the right opportunity
                                    </label>
                                </div>
                            </div>
                        </div>
                        
                        <!-- Action Buttons -->
                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex justify-content-between">
                                    <a href="dashboard.php" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                                    </a>
                                    
                                    <?php if (!$existing_application || $existing_application['status'] === 'draft'): ?>
                                        <div>
                                            <button type="submit" class="btn btn-primary me-2">
                                                <i class="fas fa-save me-2"></i>Save Draft
                                            </button>
                                            
                                            <?php if ($existing_application): ?>
                                                <button type="submit" name="submit_application" class="btn btn-success">
                                                    <i class="fas fa-paper-plane me-2"></i>Submit Application
                                                </button>
                                            <?php endif; ?>
                                        </div>
                                    <?php endif; ?>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// Form validation
(function() {
    'use strict';
    window.addEventListener('load', function() {
        var forms = document.getElementsByClassName('needs-validation');
        var validation = Array.prototype.filter.call(forms, function(form) {
            form.addEventListener('submit', function(event) {
                if (form.checkValidity() === false) {
                    event.preventDefault();
                    event.stopPropagation();
                }
                form.classList.add('was-validated');
            }, false);
        });
    }, false);
})();

// Department selection validation
document.addEventListener('DOMContentLoaded', function() {
    const form = document.querySelector('form');
    const departmentCheckboxes = document.querySelectorAll('input[name="preferred_departments[]"]');
    
    form.addEventListener('submit', function(e) {
        const checkedDepartments = document.querySelectorAll('input[name="preferred_departments[]"]:checked');
        
        if (checkedDepartments.length === 0) {
            e.preventDefault();
            alert('Please select at least one preferred department.');
            return false;
        }
    });
});
</script>

<?php include '../includes/footer_new.php'; ?>
