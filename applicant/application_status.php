<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Application Status - OSTA Job Portal</title>
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

// Get application status history
$history_stmt = $pdo->prepare("SELECT ash.*, u.username as changed_by_name 
                              FROM application_status_history ash 
                              LEFT JOIN users u ON ash.changed_by = u.id 
                              WHERE ash.application_id = ? 
                              ORDER BY ash.created_at DESC");
$history_stmt->execute([$application['id']]);
$status_history = $history_stmt->fetchAll();

// Get eligibility check results
$eligibility_stmt = $pdo->prepare("SELECT aec.*, ec.criteria_name, ec.criteria_type, ec.is_mandatory 
                                  FROM application_eligibility_checks aec 
                                  JOIN eligibility_criteria ec ON aec.criteria_id = ec.id 
                                  WHERE aec.application_id = ? 
                                  ORDER BY ec.is_mandatory DESC, ec.criteria_name");
$eligibility_stmt->execute([$application['id']]);
$eligibility_checks = $eligibility_stmt->fetchAll();

// Exam feature removed
$upcoming_exams = [];

// Get upcoming interviews
$interview_stmt = $pdo->prepare("SELECT i.*, u.username as interviewer_name 
                                FROM interviews i 
                                LEFT JOIN users u ON i.primary_interviewer_id = u.id 
                                WHERE i.application_id = ? AND i.scheduled_date >= CURDATE() 
                                ORDER BY i.scheduled_date, i.start_time");
$interview_stmt->execute([$application['id']]);
$upcoming_interviews = $interview_stmt->fetchAll();

// Get document verification status
$doc_stmt = $pdo->prepare("SELECT document_type, verification_status, COUNT(*) as count 
                          FROM application_documents 
                          WHERE application_id = ? 
                          GROUP BY document_type, verification_status");
$doc_stmt->execute([$application['id']]);
$document_status = $doc_stmt->fetchAll();

// Calculate completion percentage
$completion_percentage = calculate_application_completion($application);

$page_title = "Application Status";
include '../includes/header_new.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <!-- Application Overview -->
        <div class="col-lg-8 mb-4">
            <div class="card shadow-sm">
                <div class="card-header bg-primary text-white">
                    <div class="d-flex justify-content-between align-items-center">
                        <h4 class="mb-0">
                            <i class="fas fa-clipboard-list me-2"></i>
                            Application Status
                        </h4>
                        <div>
                            <span class="badge bg-light text-dark">
                                <?= htmlspecialchars($application['application_number']) ?>
                            </span>
                        </div>
                    </div>
                </div>
                
                <div class="card-body">
                    <!-- Current Status -->
                    <div class="row mb-4">
                        <div class="col-md-6">
                            <div class="d-flex align-items-center mb-3">
                                <div class="status-icon bg-<?= get_status_color($application['status']) ?> text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
                                    <i class="fas fa-<?= getStatusIcon($application['status']) ?>"></i>
                                </div>
                                <div>
                                    <h5 class="mb-1">Current Status</h5>
                                    <span class="badge bg-<?= get_status_color($application['status']) ?> fs-6">
                                        <?= ucfirst(str_replace('_', ' ', $application['status'])) ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-md-6">
                            <div class="d-flex align-items-center mb-3">
                                <div class="status-icon bg-<?= $application['eligibility_status'] === 'eligible' ? 'success' : ($application['eligibility_status'] === 'not_eligible' ? 'danger' : 'warning') ?> text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 50px; height: 50px;">
                                    <i class="fas fa-<?= $application['eligibility_status'] === 'eligible' ? 'check' : ($application['eligibility_status'] === 'not_eligible' ? 'times' : 'clock') ?>"></i>
                                </div>
                                <div>
                                    <h5 class="mb-1">Eligibility Status</h5>
                                    <span class="badge bg-<?= $application['eligibility_status'] === 'eligible' ? 'success' : ($application['eligibility_status'] === 'not_eligible' ? 'danger' : 'warning') ?> fs-6">
                                        <?= ucfirst(str_replace('_', ' ', $application['eligibility_status'])) ?>
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <!-- Progress Timeline -->
                    <div class="mb-4">
                        <h5 class="text-primary border-bottom pb-2">
                            <i class="fas fa-route me-2"></i>Application Progress
                        </h5>
                        
                        <div class="timeline">
                            <?php 
                            $status_flow = [
                                'draft' => ['icon' => 'edit', 'title' => 'Draft Created', 'description' => 'Application form started'],
                                'submitted' => ['icon' => 'paper-plane', 'title' => 'Application Submitted', 'description' => 'Application submitted for review'],
                                'under_review' => ['icon' => 'search', 'title' => 'Under Review', 'description' => 'HR team reviewing application'],
                                'shortlisted' => ['icon' => 'star', 'title' => 'Shortlisted', 'description' => 'Selected for next round'],
                                'interview_scheduled' => ['icon' => 'users', 'title' => 'Interview Scheduled', 'description' => 'Interview with panel scheduled'],
                                'accepted' => ['icon' => 'check-circle', 'title' => 'Accepted', 'description' => 'Congratulations! You have been selected'],
                                'rejected' => ['icon' => 'times-circle', 'title' => 'Not Selected', 'description' => 'Application was not successful this time']
                            ];
                            
                            $current_status = $application['status'];
                            $status_keys = array_keys($status_flow);
                            $current_index = array_search($current_status, $status_keys);
                            
                            foreach ($status_flow as $status => $info):
                                $status_index = array_search($status, $status_keys);
                                $is_completed = $status_index <= $current_index;
                                $is_current = $status === $current_status;
                                $is_rejected = $current_status === 'rejected';
                                
                                // Skip rejected path if not rejected
                                if ($status === 'rejected' && !$is_rejected) continue;
                                // Skip accepted path if rejected
                                if ($status === 'accepted' && $is_rejected) continue;
                            ?>
                                <div class="timeline-item <?= $is_completed ? 'completed' : '' ?> <?= $is_current ? 'current' : '' ?>">
                                    <div class="timeline-marker">
                                        <i class="fas fa-<?= $info['icon'] ?>"></i>
                                    </div>
                                    <div class="timeline-content">
                                        <h6 class="mb-1"><?= $info['title'] ?></h6>
                                        <p class="text-muted mb-0"><?= $info['description'] ?></p>
                                        <?php if ($is_current && $application['updated_at']): ?>
                                            <small class="text-success">
                                                <i class="fas fa-clock me-1"></i>
                                                <?= date('M j, Y g:i A', strtotime($application['updated_at'])) ?>
                                            </small>
                                        <?php endif; ?>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                    
                    <!-- Application Details -->
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-primary">Application Information</h6>
                            <ul class="list-unstyled">
                                <li><strong>Submitted:</strong> <?= $application['submitted_at'] ? date('M j, Y g:i A', strtotime($application['submitted_at'])) : 'Not submitted' ?></li>
                                <li><strong>Last Updated:</strong> <?= date('M j, Y g:i A', strtotime($application['updated_at'])) ?></li>
                                <li><strong>Completion:</strong> <?= $completion_percentage ?>%</li>
                            </ul>
                        </div>
                        
                        <div class="col-md-6">
                            <h6 class="text-primary">Preferred Departments</h6>
                            <?php 
                            $preferred_departments = json_decode($application['preferred_departments'], true);
                            $dept_stmt = $pdo->prepare("SELECT name FROM departments WHERE id IN (" . implode(',', array_fill(0, count($preferred_departments), '?')) . ")");
                            $dept_stmt->execute($preferred_departments);
                            $departments = $dept_stmt->fetchAll();
                            ?>
                            <ul class="list-unstyled">
                                <?php foreach ($departments as $dept): ?>
                                    <li><i class="fas fa-building me-2 text-primary"></i><?= htmlspecialchars($dept['name']) ?></li>
                                <?php endforeach; ?>
                            </ul>
                        </div>
                    </div>
                </div>
            </div>
        </div>
        
        <!-- Sidebar Information -->
        <div class="col-lg-4">
            <!-- Upcoming Events -->
            <?php if (!empty($upcoming_interviews)): ?>
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-warning text-dark">
                        <h5 class="mb-0">
                            <i class="fas fa-calendar-alt me-2"></i>
                            Upcoming Events
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($upcoming_interviews as $interview): ?>
                            <div class="alert alert-success">
                                <h6 class="alert-heading">
                                    <i class="fas fa-users me-2"></i>
                                    Interview - <?= ucfirst($interview['interview_type']) ?>
                                </h6>
                                <p class="mb-1">
                                    <strong>Date:</strong> <?= date('M j, Y', strtotime($interview['scheduled_date'])) ?><br>
                                    <strong>Time:</strong> <?= date('g:i A', strtotime($interview['start_time'])) ?><br>
                                    <strong>Duration:</strong> <?= $interview['duration_minutes'] ?> minutes<br>
                                    <?php if ($interview['venue']): ?>
                                        <strong>Venue:</strong> <?= htmlspecialchars($interview['venue']) ?><br>
                                    <?php endif; ?>
                                    <?php if ($interview['meeting_link']): ?>
                                        <strong>Meeting Link:</strong> <a href="<?= htmlspecialchars($interview['meeting_link']) ?>" target="_blank">Join Meeting</a><br>
                                    <?php endif; ?>
                                </p>
                            </div>
                        <?php endforeach; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Eligibility Status -->
            <?php if (!empty($eligibility_checks)): ?>
                <div class="card shadow-sm mb-4">
                    <div class="card-header bg-info text-white">
                        <h5 class="mb-0">
                            <i class="fas fa-check-square me-2"></i>
                            Eligibility Checks
                        </h5>
                    </div>
                    <div class="card-body">
                        <?php foreach ($eligibility_checks as $check): ?>
                            <div class="d-flex justify-content-between align-items-center mb-2 p-2 border rounded">
                                <div class="flex-grow-1">
                                    <div class="fw-bold"><?= htmlspecialchars($check['criteria_name']) ?></div>
                                    <?php if ($check['is_mandatory']): ?>
                                        <small class="badge bg-danger">Mandatory</small>
                                    <?php endif; ?>
                                    <?php if ($check['notes']): ?>
                                        <small class="text-muted d-block"><?= htmlspecialchars($check['notes']) ?></small>
                                    <?php endif; ?>
                                </div>
                                <div class="ms-2">
                                    <span class="badge bg-<?= $check['check_result'] === 'pass' ? 'success' : ($check['check_result'] === 'fail' ? 'danger' : 'warning') ?>">
                                        <?= ucfirst($check['check_result']) ?>
                                    </span>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <?php if ($application['eligibility_notes']): ?>
                            <div class="mt-3 p-2 bg-light rounded">
                                <small class="text-muted"><?= htmlspecialchars($application['eligibility_notes']) ?></small>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            <?php endif; ?>
            
            <!-- Document Status -->
            <div class="card shadow-sm">
                <div class="card-header bg-secondary text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-file-check me-2"></i>
                        Document Status
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($document_status)): ?>
                        <p class="text-muted">No documents uploaded yet.</p>
                        <a href="document_upload.php" class="btn btn-primary btn-sm">
                            <i class="fas fa-upload me-2"></i>Upload Documents
                        </a>
                    <?php else: ?>
                        <?php 
                        $doc_summary = [];
                        foreach ($document_status as $doc) {
                            if (!isset($doc_summary[$doc['document_type']])) {
                                $doc_summary[$doc['document_type']] = [];
                            }
                            $doc_summary[$doc['document_type']][$doc['verification_status']] = $doc['count'];
                        }
                        ?>
                        
                        <?php foreach ($doc_summary as $type => $statuses): ?>
                            <div class="d-flex justify-content-between align-items-center mb-2">
                                <span class="fw-bold"><?= ucfirst(str_replace('_', ' ', $type)) ?></span>
                                <div>
                                    <?php foreach ($statuses as $status => $count): ?>
                                        <span class="badge bg-<?= getVerificationColor($status) ?> me-1">
                                            <?= $count ?> <?= ucfirst($status) ?>
                                        </span>
                                    <?php endforeach; ?>
                                </div>
                            </div>
                        <?php endforeach; ?>
                        
                        <div class="mt-3">
                            <a href="document_upload.php" class="btn btn-outline-primary btn-sm">
                                <i class="fas fa-plus me-2"></i>Add More Documents
                            </a>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
        
        <!-- Status History -->
        <div class="col-lg-4">
            <div class="card shadow-sm">
                <div class="card-header bg-success text-white">
                    <h5 class="mb-0">
                        <i class="fas fa-history me-2"></i>
                        Status History
                    </h5>
                </div>
                <div class="card-body">
                    <?php if (empty($status_history)): ?>
                        <p class="text-muted">No status changes yet.</p>
                    <?php else: ?>
                        <div class="timeline-history">
                            <?php foreach ($status_history as $history): ?>
                                <div class="timeline-history-item mb-3">
                                    <div class="d-flex align-items-start">
                                        <div class="timeline-marker-small bg-<?= get_status_color($history['new_status']) ?> text-white rounded-circle d-flex align-items-center justify-content-center me-3" style="width: 30px; height: 30px; flex-shrink: 0;">
                                            <i class="fas fa-<?= getStatusIcon($history['new_status']) ?> fa-sm"></i>
                                        </div>
                                        <div class="flex-grow-1">
                                            <div class="fw-bold">
                                                <?= ucfirst(str_replace('_', ' ', $history['new_status'])) ?>
                                            </div>
                                            <small class="text-muted">
                                                <?= date('M j, Y g:i A', strtotime($history['created_at'])) ?>
                                                <?php if ($history['changed_by_name']): ?>
                                                    by <?= htmlspecialchars($history['changed_by_name']) ?>
                                                <?php endif; ?>
                                            </small>
                                            <?php if ($history['notes']): ?>
                                                <div class="mt-1">
                                                    <small class="text-dark"><?= htmlspecialchars($history['notes']) ?></small>
                                                </div>
                                            <?php endif; ?>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
    
    <!-- Action Buttons -->
    <div class="row mt-4">
        <div class="col-12">
            <div class="d-flex justify-content-between">
                <a href="dashboard.php" class="btn btn-secondary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </a>
                
                <div>
                    <?php if ($application['status'] === 'draft'): ?>
                        <a href="centralized_application.php" class="btn btn-primary me-2">
                            <i class="fas fa-edit me-2"></i>Edit Application
                        </a>
                    <?php endif; ?>
                    
                    <a href="document_upload.php" class="btn btn-outline-primary">
                        <i class="fas fa-file-upload me-2"></i>Manage Documents
                    </a>
                </div>
            </div>
        </div>
    </div>
</div>

<style>
.timeline {
    position: relative;
    padding-left: 30px;
}

.timeline::before {
    content: '';
    position: absolute;
    left: 15px;
    top: 0;
    bottom: 0;
    width: 2px;
    background: #dee2e6;
}

.timeline-item {
    position: relative;
    margin-bottom: 30px;
}

.timeline-item.completed .timeline-marker {
    background-color: #28a745 !important;
}

.timeline-item.current .timeline-marker {
    background-color: #007bff !important;
    animation: pulse 2s infinite;
}

.timeline-marker {
    position: absolute;
    left: -22px;
    width: 30px;
    height: 30px;
    background-color: #6c757d;
    border-radius: 50%;
    display: flex;
    align-items: center;
    justify-content: center;
    color: white;
    font-size: 14px;
}

.timeline-content {
    background: #f8f9fa;
    padding: 15px;
    border-radius: 8px;
    border-left: 3px solid #dee2e6;
}

.timeline-item.completed .timeline-content {
    border-left-color: #28a745;
}

.timeline-item.current .timeline-content {
    border-left-color: #007bff;
    background: #e3f2fd;
}

.timeline-history-item {
    border-left: 3px solid #dee2e6;
    padding-left: 15px;
}

@keyframes pulse {
    0% { box-shadow: 0 0 0 0 rgba(0, 123, 255, 0.7); }
    70% { box-shadow: 0 0 0 10px rgba(0, 123, 255, 0); }
    100% { box-shadow: 0 0 0 0 rgba(0, 123, 255, 0); }
}
</style>

<?php 
// Helper functions
function getStatusIcon($status) {
    $icons = [
        'draft' => 'edit',
        'submitted' => 'paper-plane',
        'under_review' => 'search',
        'shortlisted' => 'star',
        'interview_scheduled' => 'users',
        'rejected' => 'times-circle',
        'accepted' => 'check-circle',
        'onboarding' => 'user-plus'
    ];
    return $icons[$status] ?? 'question';
}

function getVerificationColor($status) {
    $colors = [
        'pending' => 'warning',
        'verified' => 'success',
        'rejected' => 'danger'
    ];
    return $colors[$status] ?? 'secondary';
}

include '../includes/footer_new.php'; 
?>
