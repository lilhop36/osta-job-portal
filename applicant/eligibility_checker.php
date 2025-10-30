<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Eligibility Check - OSTA Job Portal</title>
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

// Require authentication and applicant role
require_auth('applicant');

$user_id = $_SESSION['user_id'];

// Get user's centralized application
$stmt = $pdo->prepare("SELECT * FROM centralized_applications WHERE user_id = ?");
$stmt->execute([$user_id]);
$application = $stmt->fetch();

if (!$application) {
    header('Location: centralized_application.php');
    exit;
}

// Run eligibility checks if requested
$eligibility_result = null;
if (isset($_POST['run_checks']) && isset($_POST['csrf_token']) && verify_csrf_token($_POST['csrf_token'])) {
    $eligibility_result = run_eligibility_checks($application['id']);
    
    // Refresh application data
    $stmt = $pdo->prepare("SELECT * FROM centralized_applications WHERE user_id = ?");
    $stmt->execute([$user_id]);
    $application = $stmt->fetch();
}

// Get eligibility criteria and results
$preferred_departments = json_decode($application['preferred_departments'] ?? '[]', true);
if (!empty($preferred_departments)) {
    $criteria_sql = "SELECT ec.*, aec.check_result, aec.actual_value, aec.score, aec.notes as check_notes
                    FROM eligibility_criteria ec 
                    LEFT JOIN application_eligibility_checks aec ON ec.id = aec.criteria_id AND aec.application_id = ?
                    WHERE ec.is_active = 1 AND (ec.department_id IS NULL OR ec.department_id IN (" . 
                    implode(',', array_fill(0, count($preferred_departments), '?')) . "))
                    ORDER BY ec.is_mandatory DESC, ec.criteria_name";
    
    $params = array_merge([$application['id']], $preferred_departments);
    $criteria_stmt = $pdo->prepare($criteria_sql);
    $criteria_stmt->execute($params);
    $criteria_list = $criteria_stmt->fetchAll();
} else {
    $criteria_list = [];
}

$page_title = "Eligibility Checker";
include '../includes/header_new.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">
                            <i class="fas fa-clipboard-check me-2"></i>
                            Eligibility Checker
                        </h4>
                        <div>
                            <span class="badge bg-light text-dark">
                                Application: <?= htmlspecialchars($application['application_number']) ?>
                            </span>
                            <span class="badge bg-<?= $application['eligibility_status'] === 'eligible' ? 'success' : ($application['eligibility_status'] === 'not_eligible' ? 'danger' : 'warning') ?>">
                                <?= ucfirst(str_replace('_', ' ', $application['eligibility_status'])) ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="card-body">
                    <?php if ($eligibility_result): ?>
                        <div class="alert alert-<?= $eligibility_result['eligible'] ? 'success' : 'warning' ?>">
                            <h5 class="alert-heading">
                                <i class="fas fa-<?= $eligibility_result['eligible'] ? 'check-circle' : 'exclamation-triangle' ?> me-2"></i>
                                Eligibility Check Complete
                            </h5>
                            <p class="mb-1">
                                <strong>Result:</strong> <?= $eligibility_result['eligible'] ? 'Eligible' : 'Not Eligible' ?><br>
                                <strong>Score:</strong> <?= $eligibility_result['score'] ?>/<?= $eligibility_result['max_score'] ?> 
                                (<?= $eligibility_result['percentage'] ?>%)
                            </p>
                            <?php if (!$eligibility_result['eligible']): ?>
                                <hr>
                                <p class="mb-0">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Please review the failed criteria below and update your application if possible.
                                </p>
                            <?php endif; ?>
                        </div>
                    <?php endif; ?>
                    
                    <?php if (empty($preferred_departments)): ?>
                        <div class="alert alert-warning">
                            <i class="fas fa-exclamation-triangle me-2"></i>
                            Please complete your <a href="centralized_application.php">application form</a> and select preferred departments before running eligibility checks.
                        </div>
                    <?php else: ?>
                        <!-- Eligibility Overview -->
                        <div class="row mb-4">
                            <div class="col-md-8">
                                <h5 class="text-primary">
                                    <i class="fas fa-info-circle me-2"></i>
                                    Eligibility Requirements
                                </h5>
                                <p class="text-muted">
                                    Based on your preferred departments, here are the eligibility criteria that will be evaluated:
                                </p>
                            </div>
                            <div class="col-md-4 text-end">
                                <form method="POST">
                                    <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                    <button type="submit" name="run_checks" class="btn btn-success">
                                        <i class="fas fa-play me-2"></i>Run Eligibility Check
                                    </button>
                                </form>
                            </div>
                        </div>
                        
                        <!-- Criteria List -->
                        <div class="row">
                            <?php if (empty($criteria_list)): ?>
                                <div class="col-12">
                                    <div class="alert alert-info">
                                        <i class="fas fa-info-circle me-2"></i>
                                        No specific eligibility criteria found for your selected departments.
                                    </div>
                                </div>
                            <?php else: ?>
                                <?php foreach ($criteria_list as $criteria): ?>
                                    <div class="col-lg-6 mb-3">
                                        <div class="card h-100 <?= $criteria['check_result'] === 'pass' ? 'border-success' : ($criteria['check_result'] === 'fail' ? 'border-danger' : 'border-warning') ?>">
                                            <div class="card-header d-flex justify-content-between align-items-center">
                                                <h6 class="mb-0">
                                                    <?= htmlspecialchars($criteria['criteria_name']) ?>
                                                    <?php if ($criteria['is_mandatory']): ?>
                                                        <span class="badge bg-danger ms-2">Mandatory</span>
                                                    <?php endif; ?>
                                                </h6>
                                                
                                                <?php if ($criteria['check_result']): ?>
                                                    <span class="badge bg-<?= $criteria['check_result'] === 'pass' ? 'success' : ($criteria['check_result'] === 'fail' ? 'danger' : 'warning') ?>">
                                                        <?= ucfirst($criteria['check_result']) ?>
                                                    </span>
                                                <?php endif; ?>
                                            </div>
                                            <div class="card-body">
                                                <div class="mb-2">
                                                    <strong>Requirement:</strong>
                                                    <?= formatCriteriaRequirement($criteria) ?>
                                                </div>
                                                
                                                <?php if ($criteria['actual_value']): ?>
                                                    <div class="mb-2">
                                                        <strong>Your Value:</strong>
                                                        <span class="text-<?= $criteria['check_result'] === 'pass' ? 'success' : 'danger' ?>">
                                                            <?= htmlspecialchars($criteria['actual_value']) ?>
                                                        </span>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <?php if ($criteria['check_notes']): ?>
                                                    <div class="small text-muted">
                                                        <i class="fas fa-comment me-1"></i>
                                                        <?= htmlspecialchars($criteria['check_notes']) ?>
                                                    </div>
                                                <?php endif; ?>
                                                
                                                <?php if ($criteria['score'] !== null): ?>
                                                    <div class="mt-2">
                                                        <div class="d-flex justify-content-between align-items-center">
                                                            <small>Score:</small>
                                                            <small class="fw-bold"><?= $criteria['score'] ?>/<?= $criteria['weight'] ?></small>
                                                        </div>
                                                        <div class="progress" style="height: 5px;">
                                                            <div class="progress-bar bg-<?= $criteria['check_result'] === 'pass' ? 'success' : 'danger' ?>" 
                                                                 style="width: <?= $criteria['weight'] > 0 ? ($criteria['score'] / $criteria['weight'] * 100) : 0 ?>%"></div>
                                                        </div>
                                                    </div>
                                                <?php endif; ?>
                                            </div>
                                        </div>
                                    </div>
                                <?php endforeach; ?>
                            <?php endif; ?>
                        </div>
                        
                        <!-- Improvement Suggestions -->
                        <?php if ($eligibility_result && !$eligibility_result['eligible']): ?>
                            <div class="row mt-4">
                                <div class="col-12">
                                    <div class="card border-warning">
                                        <div class="card-header bg-warning text-dark">
                                            <h5 class="mb-0">
                                                <i class="fas fa-lightbulb me-2"></i>
                                                Improvement Suggestions
                                            </h5>
                                        </div>
                                        <div class="card-body">
                                            <p>To improve your eligibility, consider the following:</p>
                                            <ul>
                                                <?php foreach ($criteria_list as $criteria): ?>
                                                    <?php if ($criteria['check_result'] === 'fail' && $criteria['is_mandatory']): ?>
                                                        <li class="text-danger">
                                                            <strong><?= htmlspecialchars($criteria['criteria_name']) ?>:</strong>
                                                            <?= getImprovementSuggestion($criteria) ?>
                                                        </li>
                                                    <?php endif; ?>
                                                <?php endforeach; ?>
                                            </ul>
                                            
                                            <div class="mt-3">
                                                <a href="centralized_application.php" class="btn btn-primary">
                                                    <i class="fas fa-edit me-2"></i>Update Application
                                                </a>
                                            </div>
                                        </div>
                                    </div>
                                </div>
                            </div>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Navigation -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="d-flex justify-content-between">
                <a href="dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </a>
                
                <div>
                    <a href="centralized_application.php" class="btn btn-outline-primary me-2">
                        <i class="fas fa-edit me-2"></i>Edit Application
                    </a>
                    <a href="application_status.php" class="btn btn-primary">
                        <i class="fas fa-chart-line me-2"></i>View Status
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<?php 
// Helper functions
function formatCriteriaRequirement($criteria) {
    $required_value = json_decode($criteria['required_value'], true);
    
    switch ($criteria['criteria_type']) {
        case 'education_level':
            $levels = ['high_school' => 'High School', 'diploma' => 'Diploma', 'bachelor' => "Bachelor's", 'master' => "Master's", 'phd' => 'PhD'];
            return "Minimum " . ($levels[$required_value] ?? $required_value);
            
        case 'years_experience':
            return $criteria['operator'] === 'greater_equal' ? "At least {$required_value} years" : "{$required_value} years";
            
        case 'age_range':
            return $criteria['operator'] === 'greater_equal' ? "Minimum {$required_value} years old" : "Maximum {$required_value} years old";
            
        case 'field_of_study':
            if (is_array($required_value)) {
                return "Field must contain: " . implode(' OR ', $required_value);
            }
            return "Field must contain: {$required_value}";
            
        default:
            return is_array($required_value) ? implode(', ', $required_value) : $required_value;
    }
}

function getImprovementSuggestion($criteria) {
    switch ($criteria['criteria_type']) {
        case 'education_level':
            return "Consider pursuing higher education or providing additional certifications.";
            
        case 'years_experience':
            return "Gain more relevant work experience in your field.";
            
        case 'age_range':
            return "This is an age-related requirement that cannot be changed.";
            
        case 'field_of_study':
            return "Consider additional training or certification in the required field.";
            
        default:
            return "Review the requirement and update your application accordingly.";
    }
}

include '../includes/footer_new.php'; 
?>
