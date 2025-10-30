<?php
// Define application constant to allow access to included files
define('IN_OSTA', true);

require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/security.php';
require_once '../includes/logging.php';

// Require employer role
require_role('employer', '../login.php');

// Set security headers
set_security_headers();

// Initialize variables
$error_message = '';
$success_message = '';
$applications = [];
$allowed_statuses = ['pending', 'shortlisted', 'rejected', 'accepted'];

// Get employer's department ID from session with validation
if (!isset($_SESSION['user_id'])) {
    log_security_event('Unauthorized access attempt - no user_id in session');
    header('HTTP/1.1 403 Forbidden');
    die('Access denied. Please log in again.');
}

$employer_id = (int)$_SESSION['user_id'];
try {
    $stmt = $pdo->prepare("SELECT department_id FROM users WHERE id = ? AND role = 'employer'");
    $stmt->execute([$employer_id]);
    $employer = $stmt->fetch(PDO::FETCH_ASSOC);
    
    if (!$employer || !isset($employer['department_id'])) {
        log_security_event("Invalid employer access attempt - user_id: $employer_id");
        header('Location: ../unauthorized.php');
        exit();
    }
    
    $department_id = (int)$employer['department_id'];
} catch (PDOException $e) {
    log_error("Database error fetching employer data: " . $e->getMessage());
    $error_message = 'An error occurred while loading your information. Please try again later.';
}

// Handle application status update
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['update_status'])) {
    // Verify CSRF token first
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        log_security_event('CSRF token validation failed');
        $error_message = "Security validation failed. Please try again.";
    } else {
        try {
            // Validate and sanitize input
            $application_id = filter_input(INPUT_POST, 'application_id', FILTER_VALIDATE_INT);
            $status = filter_input(INPUT_POST, 'status', FILTER_SANITIZE_STRING);
            $feedback = filter_input(INPUT_POST, 'feedback', FILTER_SANITIZE_STRING);
            
            // Validate status against allowed values
            if (!in_array($status, $allowed_statuses, true)) {
                throw new Exception('Invalid application status');
            }
            
            if (!$application_id) {
                throw new Exception('Invalid application ID');
            }
            
            // Start transaction
            $pdo->beginTransaction();
            
            // Verify application belongs to employer's jobs with FOR UPDATE to prevent race conditions
            $check = $pdo->prepare("
                SELECT a.*, j.title as job_title, u.email, u.full_name 
                FROM applications a 
                JOIN jobs j ON a.job_id = j.id 
                JOIN users u ON a.user_id = u.id
                WHERE a.id = ? AND j.department_id = ?
                FOR UPDATE
            ");
            $check->execute([$application_id, $department_id]);
            $application = $check->fetch(PDO::FETCH_ASSOC);
            
            if (!$application) {
                throw new Exception('Application not found or access denied');
            }
            
            // Get current status before update
            $current_status = $application['status'];
            
            // Update application status with prepared statement
            $stmt = $pdo->prepare("
                UPDATE applications 
                SET status = :status, 
                    feedback = :feedback, 
                    updated_at = NOW(),
                    updated_by = :updated_by
                WHERE id = :id
            ");
            
            $update_result = $stmt->execute([
                ':status' => $status,
                ':feedback' => $feedback,
                ':updated_by' => $employer_id,
                ':id' => $application_id
            ]);
            
            if (!$update_result) {
                throw new PDOException('Failed to update application status');
            }
            
            // Log the status change
            log_application_event($application_id, $employer_id, "Status changed from $current_status to $status");
            
            // Send email notification if status changed
            if ($current_status !== $status) {
                require_once '../includes/mailer.php';
                
                try {
                    send_application_status_email($application_id, $status);
                    log_application_event($application_id, $employer_id, 'Status change notification sent');
                } catch (Exception $e) {
                    // Log email failure but don't fail the entire operation
                    log_error("Failed to send status email for application $application_id: " . $e->getMessage());
                }
            }
            
            // Commit transaction
            $pdo->commit();
            
            // Regenerate session ID after privilege level change
            session_regenerate_id(true);
            
            // Set success message and redirect to prevent form resubmission
            $_SESSION['success_message'] = 'Application status updated successfully.';
            header('Location: ' . $_SERVER['PHP_SELF'] . '?job_id=' . $application['job_id']);
            exit();
            
        } catch (PDOException $e) {
            $pdo->rollBack();
            log_error("Database error updating application status: " . $e->getMessage());
            $error_message = 'An error occurred while updating the application. Please try again.';
        } catch (Exception $e) {
            if ($pdo->inTransaction()) {
                $pdo->rollBack();
            }
            log_error("Error updating application: " . $e->getMessage());
            $error_message = $e->getMessage();
        }
    }
}

// Function to add page-specific scripts
function page_specific_scripts() {
    echo '<script>
    document.addEventListener("DOMContentLoaded", function() {
        // Initialize all modals
        var modals = document.querySelectorAll(".modal");
        modals.forEach(function(modal) {
            // Ensure modal backdrop is properly handled
            modal.addEventListener("show.bs.modal", function(e) {
                // Remove any existing backdrop
                var existingBackdrop = document.querySelector(".modal-backdrop");
                if (existingBackdrop) {
                    existingBackdrop.remove();
                }
            });
        });
        
        // Ensure form elements are properly initialized
        var forms = document.querySelectorAll("form");
        forms.forEach(function(form) {
            // Ensure select elements work properly
            var selects = form.querySelectorAll("select");
            selects.forEach(function(select) {
                select.addEventListener("change", function() {
                    // Force update the select value
                    this.dispatchEvent(new Event("input", { bubbles: true }));
                });
            });
            
            // Ensure textarea elements work properly
            var textareas = form.querySelectorAll("textarea");
            textareas.forEach(function(textarea) {
                textarea.addEventListener("input", function() {
                    // Force update the textarea value
                    this.dispatchEvent(new Event("change", { bubbles: true }));
                });
            });
        });
    });
    </script>';
}

// Fetch applications for the employer's department with job_id filter if provided
$job_id = null;
if (isset($_GET['job_id'])) {
    $job_id = filter_input(INPUT_GET, 'job_id', FILTER_VALIDATE_INT);
    if (!$job_id) {
        log_security_event("Invalid job_id parameter: " . $_GET['job_id']);
        $error_message = 'Invalid job ID specified.';
    }
}

try {
    $query = "
        SELECT a.*, j.title as job_title, u.email, u.full_name, 
               j.department_id, d.name as department_name
        FROM applications a 
        JOIN jobs j ON a.job_id = j.id 
        JOIN departments d ON j.department_id = d.id
        JOIN users u ON a.user_id = u.id
        WHERE j.department_id = :dept_id
    ";
    
    $params = [':dept_id' => $department_id];
    
    // Add job filter if specified
    if ($job_id) {
        $query .= " AND j.id = :job_id";
        $params[':job_id'] = $job_id;
    }
    
    $query .= " ORDER BY a.updated_at DESC, a.created_at DESC";
    
    $stmt = $pdo->prepare($query);
    $stmt->execute($params);
    $applications = $stmt->fetchAll(PDO::FETCH_ASSOC);
    
    // Log the query for audit purposes
    log_audit("Fetched applications", [
        'department_id' => $department_id,
        'job_id' => $job_id,
        'count' => count($applications)
    ]);
    
} catch (PDOException $e) {
    $error_msg = 'Error fetching applications';
    log_error($error_msg . ': ' . $e->getMessage());
    $error_message = 'An error occurred while loading applications. Please try again later.';
    $applications = [];
}

// Check for success message in session (from redirect after update)
if (isset($_SESSION['success_message'])) {
    $success_message = $_SESSION['success_message'];
    unset($_SESSION['success_message']);
}

// Include the header
include '../includes/header.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="card mb-4">
                <div class="card-header pb-0">
                    <h6>Manage Applications</h6>
                    <p class="text-sm mb-0">Review and update job applications for your department.</p>
                </div>
                <div class="card-body px-0 pt-0 pb-2">
                    <div class="table-responsive p-0">
                        <table class="table align-items-center mb-0">
                            <thead>
                                <tr>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Applicant</th>
                                    <th class="text-uppercase text-secondary text-xxs font-weight-bolder opacity-7 ps-2">Job Title</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Status</th>
                                    <th class="text-center text-uppercase text-secondary text-xxs font-weight-bolder opacity-7">Applied Date</th>
                                    <th class="text-secondary opacity-7"></th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($applications)): ?>
                                    <tr>
                                        <td colspan="5" class="text-center">No applications found.</td>
                                    </tr>
                                <?php else: ?>
                                    <?php foreach ($applications as $app): ?>
                                        <tr>
                                            <td>
                                                <div class="d-flex px-2 py-1">
                                                    <div class="d-flex flex-column justify-content-center">
                                                        <h6 class="mb-0 text-sm"><?= htmlspecialchars($app['full_name']) ?></h6>
                                                        <p class="text-xs text-secondary mb-0"><?= htmlspecialchars($app['email']) ?></p>
                                                    </div>
                                                </div>
                                            </td>
                                            <td>
                                                <p class="text-xs font-weight-bold mb-0"><?= htmlspecialchars($app['job_title']) ?></p>
                                            </td>
                                            <td class="align-middle text-center text-sm">
                                                <span class="badge badge-sm bg-gradient-<?= get_status_badge_class($app['status']) ?>"><?= ucfirst(str_replace('_', ' ', $app['status'])) ?></span>
                                            </td>
                                            <td class="align-middle text-center">
                                                <span class="text-secondary text-xs font-weight-bold"><?= date('M d, Y', strtotime($app['created_at'])) ?></span>
                                            </td>
                                            <td class="align-middle">
                                                <button type="button" class="btn btn-sm btn-primary" onclick="openUpdateModal(<?= $app['id'] ?>, '<?= $app['status'] ?>', '<?= htmlspecialchars($app['feedback'] ?? '', ENT_QUOTES) ?>')">
                                                    Update Status
                                                </button>
                                            </td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php
// Helper function to get status badge class
function get_status_badge_class($status) {
    switch ($status) {
        case 'pending':
            return 'secondary';
        case 'reviewed':
            return 'info';
        case 'shortlisted':
            return 'success';
        case 'interview':
            return 'warning';
        case 'rejected':
            return 'danger';
        case 'accepted':
            return 'primary';
        default:
            return 'secondary';
    }
}

?>

<!-- Single Update Status Modal -->
<div class="modal fade" id="updateStatusModal" tabindex="-1" aria-labelledby="updateStatusModalLabel" aria-hidden="true">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="modal-header">
                <h5 class="modal-title" id="updateStatusModalLabel">Update Application Status</h5>
                <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
            </div>
            <form method="POST" id="updateStatusForm">
                <div class="modal-body">
                    <input type="hidden" name="application_id" id="modal_application_id">
                    <input type="hidden" name="update_status" value="1">
                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                    
                    <div class="mb-3">
                        <label for="modal_status" class="form-label">Status</label>
                        <select class="form-select" id="modal_status" name="status" required>
                            <option value="pending">Pending Review</option>
                            <option value="shortlisted">Shortlisted</option>
                            <option value="accepted">Accepted</option>
                            <option value="rejected">Rejected</option>
                        </select>
                    </div>
                    
                    <div class="mb-3">
                        <label for="modal_feedback" class="form-label">Feedback (Optional)</label>
                        <textarea class="form-control" id="modal_feedback" name="feedback" rows="3" placeholder="Provide feedback for the applicant..."></textarea>
                    </div>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <button type="submit" class="btn btn-primary">Update Status</button>
                </div>
            </form>
        </div>
    </div>
</div>

<script>
function openUpdateModal(applicationId, currentStatus, currentFeedback) {
    // Set the application ID
    document.getElementById('modal_application_id').value = applicationId;
    
    // Set the current status
    document.getElementById('modal_status').value = currentStatus;
    
    // Set the current feedback
    document.getElementById('modal_feedback').value = currentFeedback || '';
    
    // Show the modal
    var modal = new bootstrap.Modal(document.getElementById('updateStatusModal'));
    modal.show();
}
</script>

<?php
// Include the footer
include '../includes/footer.php';
?>
