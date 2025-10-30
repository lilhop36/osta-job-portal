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

// Get application ID
$application_id = $_GET['id'] ?? 0;

if (!$application_id) {
    $_SESSION['error_message'] = 'No application specified';
    header('Location: applications.php');
    exit;
}

// Fetch application details
$stmt = $pdo->prepare("
    SELECT a.*, CONCAT(u.first_name, ' ', u.last_name) as full_name, 
           d.name as department_name, u.email, u.phone
    FROM centralized_applications a
    JOIN users u ON a.user_id = u.id
    LEFT JOIN departments d ON a.department_id = d.id
    WHERE a.id = ?
");
$stmt->execute([$application_id]);
$application = $stmt->fetch();

if (!$application) {
    $_SESSION['error_message'] = 'Application not found';
    header('Location: applications.php');
    exit;
}

// Set page title
$page_title = 'Application: ' . $application['application_number'];

// Include header
include '../includes/header_new.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-3">
            <?php include '../includes/admin_sidebar.php'; ?>
        </div>
        
        <div class="col-md-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0">
                    <i class="fas fa-file-alt me-2"></i>Application #<?= htmlspecialchars($application['application_number']) ?>
                </h2>
                <div>
                    <a href="applications.php" class="btn btn-outline-secondary me-2">
                        <i class="fas fa-arrow-left me-1"></i> Back
                    </a>
                    <a href="update_application_status.php?id=<?= $application_id ?>" class="btn btn-primary">
                        <i class="fas fa-edit me-1"></i> Update Status
                    </a>
                </div>
            </div>
            
            <div class="alert alert-<?= get_status_color($application['status']) ?> mb-4">
                <strong>Status:</strong> <?= ucfirst(str_replace('_', ' ', $application['status'])) ?>
            </div>
            
            <div class="row">
                <div class="col-md-6">
                    <div class="card mb-4">
                        <div class="card-header">
                            <h5 class="mb-0">Applicant Information</h5>
                        </div>
                        <div class="card-body">
                            <p class="mb-2">
                                <strong>Name:</strong> <?= htmlspecialchars($application['full_name']) ?>
                            </p>
                            <p class="mb-2">
                                <strong>Email:</strong> <?= htmlspecialchars($application['email']) ?>
                            </p>
                            <p class="mb-2">
                                <strong>Phone:</strong> <?= htmlspecialchars($application['phone']) ?>
                            </p>
                            <p class="mb-0">
                                <strong>Department:</strong> 
                                <?= $application['department_name'] ? htmlspecialchars($application['department_name']) : 'N/A' ?>
                            </p>
                        </div>
                    </div>
                </div>
                
                <div class="col-md-6">
                    <div class="card">
                        <div class="card-header">
                            <h5 class="mb-0">Application Details</h5>
                        </div>
                        <div class="card-body">
                            <p class="mb-2">
                                <strong>Application Date:</strong> 
                                <?= date('M j, Y', strtotime($application['created_at'])) ?>
                            </p>
                            <p class="mb-2">
                                <strong>Last Updated:</strong> 
                                <?= date('M j, Y H:i', strtotime($application['updated_at'])) ?>
                            </p>
                            <p class="mb-0">
                                <strong>Preferred Positions:</strong><br>
                                <?= nl2br(htmlspecialchars($application['preferred_positions'] ?? 'Not specified')) ?>
                            </p>
                        </div>
                    </div>
                </div>
            </div>
            
            <div class="card mt-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0">Status History</h5>
                </div>
                <div class="card-body p-0">
                    <div class="list-group list-group-flush">
                        <?php
                        $history_stmt = $pdo->prepare("
                            SELECT h.*, CONCAT(u.first_name, ' ', u.last_name) as updated_by_name
                            FROM application_status_history h
                            LEFT JOIN users u ON h.updated_by = u.id
                            WHERE h.application_id = ?
                            ORDER BY h.changed_at DESC
                        ")->execute([$application_id]);
                        
                        while ($history = $history_stmt->fetch()): ?>
                            <div class="list-group-item">
                                <div class="d-flex w-100 justify-content-between">
                                    <h6 class="mb-1">
                                        Status changed to 
                                        <span class="badge bg-<?= get_status_color($history['status']) ?>">
                                            <?= ucfirst(str_replace('_', ' ', $history['status'])) ?>
                                        </span>
                                    </h6>
                                    <small><?= date('M j, Y H:i', strtotime($history['changed_at'])) ?></small>
                                </div>
                                <p class="mb-1"><?= nl2br(htmlspecialchars($history['notes'] ?: 'No notes provided')) ?></p>
                                <small class="text-muted">
                                    Updated by: <?= htmlspecialchars($history['updated_by_name'] ?: 'System') ?>
                                </small>
                            </div>
                        <?php endwhile; ?>
                        
                        <?php if ($history_stmt->rowCount() === 0): ?>
                            <div class="list-group-item text-muted text-center py-4">
                                No status history available
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer_new.php'; ?>
