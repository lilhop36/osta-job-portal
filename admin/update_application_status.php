<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/security.php';
require_once '../includes/application_functions.php';

// Ensure user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ' . SITE_URL . '/unauthorized.php');
    exit;
}

// Set security headers
set_security_headers();

$success_message = '';
$error_message = '';

// Handle status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    if (!validate_csrf_token($_POST['csrf_token'])) {
        $error_message = 'Invalid CSRF token. Please try again.';
    } else {
        try {
            $application_id = filter_input(INPUT_POST, 'application_id', FILTER_VALIDATE_INT);
            $new_status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);
            $notes = filter_input(INPUT_POST, 'notes', FILTER_SANITIZE_STRING);
            
            if (!$application_id) {
                throw new Exception('Invalid application ID');
            }
            
            // Valid statuses
            $valid_statuses = [
                'draft', 'submitted', 'under_review', 'shortlisted', 
                'interview_scheduled', 'interviewed', 'offered', 'hired', 'rejected', 'withdrawn'
            ];
            
            if (!in_array($new_status, $valid_statuses)) {
                throw new Exception('Invalid status');
            }
            
            // Update status and notify applicant
            if (update_application_status($application_id, $new_status, $notes, $_SESSION['user_id'])) {
                $success_message = 'Application status updated successfully.';
            } else {
                throw new Exception('Failed to update application status');
            }
            
        } catch (Exception $e) {
            $error_message = 'Error: ' . $e->getMessage();
            error_log('Error updating application status: ' . $e->getMessage());
        }
    }
}

// Get application details
$application = null;
$status_history = [];

if (isset($_GET['id'])) {
    $application_id = filter_input(INPUT_GET, 'id', FILTER_VALIDATE_INT);
    
    if ($application_id) {
        try {
            // Get application details
            $stmt = $pdo->prepare("
                SELECT a.*, u.username, u.email, 
                       CONCAT(u.first_name, ' ', u.last_name) as full_name
                FROM centralized_applications a
                JOIN users u ON a.user_id = u.id
                WHERE a.id = ?
            ");
            $stmt->execute([$application_id]);
            $application = $stmt->fetch();
            
            if ($application) {
                // Get status history
                $history_stmt = $pdo->prepare("
                    SELECT h.*, u.username as changed_by_name
                    FROM application_status_history h
                    LEFT JOIN users u ON h.changed_by = u.id
                    WHERE h.application_id = ?
                    ORDER BY h.created_at DESC
                ");
                $history_stmt->execute([$application_id]);
                $status_history = $history_stmt->fetchAll();
            }
        } catch (Exception $e) {
            $error_message = 'Error loading application details: ' . $e->getMessage();
            error_log('Error loading application: ' . $e->getMessage());
        }
    }
}

// Set page title
$page_title = $application ? 
    "Update Application Status - " . htmlspecialchars($application['application_number']) : 
    'Update Application Status';

// Include header
include '../includes/header_new.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <!-- Sidebar -->
        <div class="col-md-3">
            <?php include '../includes/admin_sidebar.php'; ?>
        </div>
        
        <!-- Main Content -->
        <div class="col-md-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">
                    <i class="fas fa-tasks me-2"></i>Update Application Status
                </h2>
                <a href="applications.php" class="btn btn-outline-secondary">
                    <i class="fas fa-arrow-left me-1"></i> Back to Applications
                </a>
            </div>
            
            <?php if ($error_message): ?>
                <div class="alert alert-danger"><?= htmlspecialchars($error_message) ?></div>
            <?php endif; ?>
            
            <?php if ($success_message): ?>
                <div class="alert alert-success"><?= htmlspecialchars($success_message) ?></div>
            <?php endif; ?>
            
            <?php if ($application): ?>
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Application Details</h5>
                    </div>
                    <div class="card-body">
                        <div class="row">
                            <div class="col-md-6">
                                <p><strong>Application #:</strong> <?= htmlspecialchars($application['application_number']) ?></p>
                                <p><strong>Applicant:</strong> <?= htmlspecialchars($application['full_name']) ?></p>
                                <p><strong>Email:</strong> <?= htmlspecialchars($application['email']) ?></p>
                            </div>
                            <div class="col-md-6">
                                <p><strong>Current Status:</strong> 
                                    <span class="badge bg-<?= get_status_color($application['status']) ?>">
                                        <?= ucfirst(str_replace('_', ' ', $application['status'])) ?>
                                    </span>
                                </p>
                                <p><strong>Submitted On:</strong> <?= date('M j, Y g:i A', strtotime($application['created_at'])) ?></p>
                                <?php if ($application['updated_at']): ?>
                                    <p><strong>Last Updated:</strong> <?= date('M j, Y g:i A', strtotime($application['updated_at'])) ?></p>
                                <?php endif; ?>
                            </div>
                        </div>
                    </div>
                </div>
                
                <div class="card mb-4">
                    <div class="card-header bg-primary text-white">
                        <h5 class="mb-0">Update Status</h5>
                    </div>
                    <div class="card-body">
                        <form method="POST" action="update_application_status.php">
                            <?= csrf_token_field() ?>
                            <input type="hidden" name="application_id" value="<?= $application['id'] ?>">
                            
                            <div class="mb-3">
                                <label for="status" class="form-label">New Status</label>
                                <select class="form-select" id="status" name="status" required>
                                    <option value="">Select a status</option>
                                    <option value="under_review" <?= $application['status'] === 'under_review' ? 'selected' : '' ?>>Under Review</option>
                                    <option value="shortlisted" <?= $application['status'] === 'shortlisted' ? 'selected' : '' ?>>Shortlisted</option>
                                    <option value="interview_scheduled" <?= $application['status'] === 'interview_scheduled' ? 'selected' : '' ?>>Interview Scheduled</option>
                                    <option value="interviewed" <?= $application['status'] === 'interviewed' ? 'selected' : '' ?>>Interview Completed</option>
                                    <option value="offered" <?= $application['status'] === 'offered' ? 'selected' : '' ?>>Job Offered</option>
                                    <option value="hired" <?= $application['status'] === 'hired' ? 'selected' : '' ?>>Hired</option>
                                    <option value="rejected" <?= $application['status'] === 'rejected' ? 'selected' : '' ?>>Rejected</option>
                                    <option value="withdrawn" <?= $application['status'] === 'withdrawn' ? 'selected' : '' ?>>Withdrawn</option>
                                </select>
                            </div>
                            
                            <div class="mb-3">
                                <label for="notes" class="form-label">Notes (Optional)</label>
                                <textarea class="form-control" id="notes" name="notes" rows="3" 
                                          placeholder="Add any notes about this status change..."></textarea>
                                <div class="form-text">This will be included in the notification to the applicant.</div>
                            </div>
                            
                            <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                                <a href="applications.php" class="btn btn-outline-secondary me-md-2">Cancel</a>
                                <button type="submit" name="update_status" class="btn btn-primary">
                                    <i class="fas fa-save me-1"></i> Update Status
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
                
                <?php if (!empty($status_history)): ?>
                    <div class="card">
                        <div class="card-header bg-light">
                            <h5 class="mb-0">Status History</h5>
                        </div>
                        <div class="card-body p-0">
                            <div class="list-group list-group-flush">
                                <?php foreach ($status_history as $history): ?>
                                    <div class="list-group-item">
                                        <div class="d-flex w-100 justify-content-between">
                                            <h6 class="mb-1">
                                                Status changed from 
                                                <span class="badge bg-<?= get_status_color($history['old_status']) ?>">
                                                    <?= ucfirst(str_replace('_', ' ', $history['old_status'])) ?>
                                                </span> 
                                                to 
                                                <span class="badge bg-<?= get_status_color($history['new_status']) ?>">
                                                    <?= ucfirst(str_replace('_', ' ', $history['new_status'])) ?>
                                                </span>
                                            </h6>
                                            <small class="text-muted">
                                                <?= date('M j, Y g:i A', strtotime($history['created_at'])) ?>
                                            </small>
                                        </div>
                                        <?php if ($history['changed_by_name']): ?>
                                            <p class="mb-1">
                                                <small class="text-muted">
                                                    Changed by: <?= htmlspecialchars($history['changed_by_name']) ?>
                                                </small>
                                            </p>
                                        <?php endif; ?>
                                        <?php if (!empty($history['notes'])): ?>
                                            <p class="mb-0">
                                                <strong>Notes:</strong> <?= nl2br(htmlspecialchars($history['notes'])) ?>
                                            </p>
                                        <?php endif; ?>
                                    </div>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                <?php endif; ?>
                
            <?php else: ?>
                <div class="alert alert-warning">
                    <i class="fas fa-exclamation-triangle me-2"></i>
                    Application not found or you don't have permission to view it.
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php
// Include footer
include '../includes/footer_new.php';
?>
