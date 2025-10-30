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

// Get upcoming interviews
$interview_stmt = $pdo->prepare("SELECT i.*, it.type_name as interview_type_name
                               FROM interviews i
                               JOIN interview_types it ON i.interview_type_id = it.id
                               WHERE i.application_id = ? AND i.interview_date >= CURDATE()
                               ORDER BY i.interview_date, i.start_time");
$interview_stmt->execute([$application['id']]);
$upcoming_interviews = $interview_stmt->fetchAll();

// Get past interviews
$past_interview_stmt = $pdo->prepare("SELECT i.*, it.type_name as interview_type_name, i.evaluation_score, i.evaluation_result
                                    FROM interviews i
                                    JOIN interview_types it ON i.interview_type_id = it.id
                                    WHERE i.application_id = ? AND i.interview_date < CURDATE()
                                    ORDER BY i.interview_date DESC");
$past_interview_stmt->execute([$application['id']]);
$past_interviews = $past_interview_stmt->fetchAll();

$page_title = "My Interviews";
include '../includes/header_new.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-lg-3 col-md-4">
            <?php include '../includes/applicant_sidebar.php'; ?>
        </div>
        
        <div class="col-lg-9 col-md-8">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-comments me-2"></i>My Interviews</h2>
            </div>
            
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php 
                        echo $_SESSION['success_message']; 
                        unset($_SESSION['success_message']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php 
                        echo $_SESSION['error_message']; 
                        unset($_SESSION['error_message']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <!-- Upcoming Interviews -->
            <div class="card mb-4">
                <div class="card-header bg-primary text-white">
                    <h4 class="mb-0"><i class="fas fa-calendar-check me-2"></i>Upcoming Interviews</h4>
                </div>
                <div class="card-body">
                    <?php if (empty($upcoming_interviews)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-comments fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No upcoming interviews scheduled</h5>
                            <p class="text-muted">You will be notified when interviews are scheduled for your applications.</p>
                        </div>
                    <?php else: ?>
                        <div class="row">
                            <?php foreach ($upcoming_interviews as $interview): ?>
                                <div class="col-md-6 mb-4">
                                    <div class="card border-primary h-100">
                                        <div class="card-body">
                                            <h5 class="card-title text-primary"><?= htmlspecialchars($interview['interview_type_name']) ?></h5>
                                            <p class="card-text"><?= htmlspecialchars($interview['notes'] ?? 'No additional notes') ?></p>
                                            
                                            <div class="row mb-3">
                                                <div class="col-6">
                                                    <small class="text-muted">Date</small>
                                                    <div class="fw-bold"><?= date('M j, Y', strtotime($interview['interview_date'])) ?></div>
                                                </div>
                                                <div class="col-6">
                                                    <small class="text-muted">Time</small>
                                                    <div class="fw-bold"><?= date('g:i A', strtotime($interview['start_time'])) ?> - <?= date('g:i A', strtotime($interview['end_time'])) ?></div>
                                                </div>
                                            </div>
                                            
                                            <div class="row mb-3">
                                                <div class="col-6">
                                                    <small class="text-muted">Venue</small>
                                                    <div class="fw-bold"><?= htmlspecialchars($interview['venue'] ?? 'To be announced') ?></div>
                                                </div>
                                                <div class="col-6">
                                                    <small class="text-muted">Panel Members</small>
                                                    <div class="fw-bold">
                                                        <?php 
                                                        $panel_members = json_decode($interview['panel_members'], true);
                                                        if (!empty($panel_members) && is_array($panel_members)) {
                                                            echo count($panel_members) . ' member(s)';
                                                        } else {
                                                            echo 'Not specified';
                                                        }
                                                        ?>
                                                    </div>
                                                </div>
                                            </div>
                                            
                                            <div class="mb-3">
                                                <small class="text-muted">Status</small><br>
                                                <span class="badge bg-<?= get_status_color($interview['status']) ?>">
                                                    <?= ucfirst(str_replace('_', ' ', $interview['status'])) ?>
                                                </span>
                                            </div>
                                        </div>
                                        <div class="card-footer text-muted">
                                            <small>Interview ID: <?= htmlspecialchars($interview['id']) ?></small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
            
            <!-- Past Interviews -->
            <div class="card">
                <div class="card-header bg-secondary text-white">
                    <h4 class="mb-0"><i class="fas fa-history me-2"></i>Past Interviews</h4>
                </div>
                <div class="card-body">
                    <?php if (empty($past_interviews)): ?>
                        <div class="text-center py-3">
                            <p class="text-muted mb-0">No past interviews found.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Interview Type</th>
                                        <th>Date</th>
                                        <th>Venue</th>
                                        <th>Score</th>
                                        <th>Result</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($past_interviews as $interview): ?>
                                        <tr>
                                            <td><?= htmlspecialchars($interview['interview_type_name']) ?></td>
                                            <td><?= date('M j, Y', strtotime($interview['interview_date'])) ?></td>
                                            <td><?= htmlspecialchars($interview['venue'] ?? 'Not specified') ?></td>
                                            <td>
                                                <?php if ($interview['evaluation_score'] !== null): ?>
                                                    <?= $interview['evaluation_score'] ?>/100
                                                <?php else: ?>
                                                    <span class="text-muted">Pending</span>
                                                <?php endif; ?>
                                            </td>
                                            <td>
                                                <?php if ($interview['evaluation_result'] !== null): ?>
                                                    <span class="badge bg-<?= $interview['evaluation_result'] === 'pass' ? 'success' : ($interview['evaluation_result'] === 'fail' ? 'danger' : 'warning') ?>">
                                                        <?= ucfirst($interview['evaluation_result']) ?>
                                                    </span>
                                                <?php else: ?>
                                                    <span class="text-muted">Pending</span>
                                                <?php endif; ?>
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

<?php include '../includes/footer_new.php'; ?>
