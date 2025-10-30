<?php
session_start();
require_once '../config/database.php';
require_once '../includes/functions.php';
require_once '../includes/security.php';
require_once '../includes/auth.php';
require_once '../includes/application_functions.php';

// Require authentication and employer role
require_auth('employer');

$user_id = $_SESSION['user_id'];
$error_message = '';
$success_message = '';

// Get user's department (assuming employers belong to departments)
$user_stmt = $pdo->prepare("SELECT department_id FROM users WHERE id = ?");
$user_stmt->execute([$user_id]);
$user_info = $user_stmt->fetch();
$user_department_id = $user_info['department_id'];

// Get departments for selection (if user is admin or has multiple department access)
$dept_stmt = $pdo->prepare("SELECT id, name FROM departments WHERE is_active = 1 ORDER BY name");
$dept_stmt->execute();
$departments = $dept_stmt->fetchAll();

// Handle form submission
if ($_POST && verify_csrf_token($_POST['csrf_token'])) {
    try {
        $pdo->beginTransaction();
        
        // Validate required fields
        $required_fields = [
            'department_id', 'position_title', 'position_description', 'number_of_positions',
            'employment_type', 'education_requirements', 'experience_requirements',
            'skills_requirements', 'business_justification'
        ];
        
        foreach ($required_fields as $field) {
            if (empty($_POST[$field])) {
                throw new Exception("Please fill in all required fields.");
            }
        }
        
        // Generate request number
        $year = date('Y');
        $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM vacancy_requests WHERE request_number LIKE ?");
        $count_stmt->execute(["VR-{$year}-%"]);
        $next_number = $count_stmt->fetchColumn() + 1;
        $request_number = "VR-{$year}-" . str_pad($next_number, 3, '0', STR_PAD_LEFT);
        
        // Insert vacancy request
        $sql = "INSERT INTO vacancy_requests (
                request_number, department_id, requested_by, position_title, position_description,
                number_of_positions, employment_type, salary_min, salary_max,
                education_requirements, experience_requirements, skills_requirements, other_requirements,
                business_justification, urgency_level, preferred_start_date, status
                ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, 'submitted')";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute([
            $request_number,
            $_POST['department_id'],
            $user_id,
            $_POST['position_title'],
            $_POST['position_description'],
            $_POST['number_of_positions'],
            $_POST['employment_type'],
            $_POST['salary_min'] ?: null,
            $_POST['salary_max'] ?: null,
            $_POST['education_requirements'],
            $_POST['experience_requirements'],
            $_POST['skills_requirements'],
            $_POST['other_requirements'] ?: null,
            $_POST['business_justification'],
            $_POST['urgency_level'],
            $_POST['preferred_start_date'] ?: null
        ]);
        
        $request_id = $pdo->lastInsertId();
        
        // Log the action
        log_audit_action($user_id, 'create', 'vacancy_requests', $request_id, 
                        "Created vacancy request: {$request_number}");
        
        $pdo->commit();
        $success_message = "Vacancy request submitted successfully! Request Number: {$request_number}";
        
    } catch (Exception $e) {
        $pdo->rollBack();
        $error_message = $e->getMessage();
        log_error("Vacancy request error: " . $e->getMessage());
    }
}

// Get existing requests for this user
$requests_stmt = $pdo->prepare("SELECT vr.*, d.name as department_name 
                               FROM vacancy_requests vr 
                               JOIN departments d ON vr.department_id = d.id 
                               WHERE vr.requested_by = ? 
                               ORDER BY vr.created_at DESC");
$requests_stmt->execute([$user_id]);
$existing_requests = $requests_stmt->fetchAll();

$page_title = "Vacancy Request";
include '../includes/header_new.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0">
                        <i class="fas fa-plus-circle me-2"></i>
                        Submit Vacancy Request
                    </h4>
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
                    
                    <form method="POST" class="needs-validation" novalidate>
                        <?= generate_csrf_token() ?>
                        
                        <!-- Basic Information -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="text-primary border-bottom pb-2">
                                    <i class="fas fa-info-circle me-2"></i>Basic Information
                                </h5>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="department_id" class="form-label">Department *</label>
                                <select class="form-select" id="department_id" name="department_id" required>
                                    <option value="">Select Department</option>
                                    <?php foreach ($departments as $dept): ?>
                                        <option value="<?= $dept['id'] ?>" <?= $dept['id'] == $user_department_id ? 'selected' : '' ?>>
                                            <?= htmlspecialchars($dept['name']) ?>
                                        </option>
                                    <?php endforeach; ?>
                                </select>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="position_title" class="form-label">Position Title *</label>
                                <input type="text" class="form-control" id="position_title" name="position_title" required>
                            </div>
                            
                            <div class="col-12 mb-3">
                                <label for="position_description" class="form-label">Position Description *</label>
                                <textarea class="form-control" id="position_description" name="position_description" rows="4" required></textarea>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="number_of_positions" class="form-label">Number of Positions *</label>
                                <input type="number" class="form-control" id="number_of_positions" name="number_of_positions" min="1" required>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="employment_type" class="form-label">Employment Type *</label>
                                <select class="form-select" id="employment_type" name="employment_type" required>
                                    <option value="">Select Type</option>
                                    <option value="full_time">Full Time</option>
                                    <option value="part_time">Part Time</option>
                                    <option value="contract">Contract</option>
                                    <option value="internship">Internship</option>
                                </select>
                            </div>
                            
                            <div class="col-md-4 mb-3">
                                <label for="urgency_level" class="form-label">Urgency Level</label>
                                <select class="form-select" id="urgency_level" name="urgency_level">
                                    <option value="medium">Medium</option>
                                    <option value="low">Low</option>
                                    <option value="high">High</option>
                                    <option value="critical">Critical</option>
                                </select>
                            </div>
                        </div>
                        
                        <!-- Salary Information -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="text-primary border-bottom pb-2">
                                    <i class="fas fa-dollar-sign me-2"></i>Salary Information
                                </h5>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="salary_min" class="form-label">Minimum Salary</label>
                                <input type="number" class="form-control" id="salary_min" name="salary_min" min="0" step="1000">
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="salary_max" class="form-label">Maximum Salary</label>
                                <input type="number" class="form-control" id="salary_max" name="salary_max" min="0" step="1000">
                            </div>
                        </div>
                        
                        <!-- Requirements -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="text-primary border-bottom pb-2">
                                    <i class="fas fa-clipboard-check me-2"></i>Requirements
                                </h5>
                            </div>
                            
                            <div class="col-12 mb-3">
                                <label for="education_requirements" class="form-label">Education Requirements *</label>
                                <textarea class="form-control" id="education_requirements" name="education_requirements" rows="3" required></textarea>
                            </div>
                            
                            <div class="col-12 mb-3">
                                <label for="experience_requirements" class="form-label">Experience Requirements *</label>
                                <textarea class="form-control" id="experience_requirements" name="experience_requirements" rows="3" required></textarea>
                            </div>
                            
                            <div class="col-12 mb-3">
                                <label for="skills_requirements" class="form-label">Skills & Competencies *</label>
                                <textarea class="form-control" id="skills_requirements" name="skills_requirements" rows="3" required></textarea>
                            </div>
                            
                            <div class="col-12 mb-3">
                                <label for="other_requirements" class="form-label">Other Requirements</label>
                                <textarea class="form-control" id="other_requirements" name="other_requirements" rows="2"></textarea>
                            </div>
                        </div>
                        
                        <!-- Justification -->
                        <div class="row mb-4">
                            <div class="col-12">
                                <h5 class="text-primary border-bottom pb-2">
                                    <i class="fas fa-file-alt me-2"></i>Justification
                                </h5>
                            </div>
                            
                            <div class="col-12 mb-3">
                                <label for="business_justification" class="form-label">Business Justification *</label>
                                <textarea class="form-control" id="business_justification" name="business_justification" rows="4" required 
                                          placeholder="Explain why this position is needed, how it aligns with department goals, and the impact of not filling this position."></textarea>
                            </div>
                            
                            <div class="col-md-6 mb-3">
                                <label for="preferred_start_date" class="form-label">Preferred Start Date</label>
                                <input type="date" class="form-control" id="preferred_start_date" name="preferred_start_date" min="<?= date('Y-m-d') ?>">
                            </div>
                        </div>
                        
                        <!-- Submit Button -->
                        <div class="row">
                            <div class="col-12">
                                <div class="d-flex justify-content-between">
                                    <a href="dashboard.php" class="btn btn-secondary">
                                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                                    </a>
                                    
                                    <button type="submit" class="btn btn-primary">
                                        <i class="fas fa-paper-plane me-2"></i>Submit Request
                                    </button>
                                </div>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Existing Requests -->
    <?php if (!empty($existing_requests)): ?>
        <div class="row mt-4">
            <div class="col-12">
                <div class="card shadow-sm">
                    <div class="card-header bg-secondary text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-list me-2"></i>
                            Your Vacancy Requests
                        </h5>
                    </div>
                    <div class="card-body">
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Request #</th>
                                        <th>Position</th>
                                        <th>Department</th>
                                        <th>Positions</th>
                                        <th>Status</th>
                                        <th>Submitted</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($existing_requests as $request): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($request['request_number']) ?></td>
                                            <td><?= htmlspecialchars($request['position_title']) ?></td>
                                            <td><?= htmlspecialchars($request['department_name']) ?></td>
                                            <td><?= $request['number_of_positions'] ?></td>
                                            <td>
                                                <span class="badge bg-<?= getRequestStatusColor($request['status']) ?>">
                                                    <?= ucfirst(str_replace('_', ' ', $request['status'])) ?>
                                                </span>
                                            </td>
                                            <td><?= date('M j, Y', strtotime($request['created_at'])) ?></td>
                                            <td>
                                                <a href="view_vacancy_request.php?id=<?= $request['id'] ?>" 
                                                   class="btn btn-sm btn-outline-primary">
                                                    <i class="fas fa-eye"></i> View
                                                </a>
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
    <?php endif; ?>
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

// Salary validation
document.getElementById('salary_max').addEventListener('change', function() {
    const minSalary = document.getElementById('salary_min').value;
    const maxSalary = this.value;
    
    if (minSalary && maxSalary && parseInt(maxSalary) < parseInt(minSalary)) {
        alert('Maximum salary cannot be less than minimum salary');
        this.value = '';
    }
});
</script>

<?php 
function getRequestStatusColor($status) {
    $colors = [
        'draft' => 'secondary',
        'submitted' => 'primary',
        'hr_review' => 'info',
        'approved' => 'success',
        'rejected' => 'danger',
        'published' => 'success',
        'closed' => 'dark'
    ];
    return isset($colors[$status]) ? $colors[$status] : 'secondary';
}

include '../includes/footer_new.php'; 
?>
