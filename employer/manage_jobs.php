<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/security.php';

// Require employer role
require_role('employer', '../login.php');

// Set security headers
set_security_headers();

// Get user info and department
$stmt = $pdo->prepare("SELECT u.*, d.name as department_name FROM users u 
                       LEFT JOIN departments d ON u.department_id = d.id 
                       WHERE u.id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    $_SESSION['error'] = 'User or department not found';
    header('Location: ../login.php');
    exit();
}

$success_message = '';
$error_message = '';

// Handle job deletion
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['delete_job'])) {
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $error_message = "Invalid CSRF token. Please try again.";
    } else {
        $job_id = (int)$_POST['job_id'];
        
        try {
            // Check if job belongs to user's department
            $check_stmt = $pdo->prepare("SELECT id FROM jobs WHERE id = ? AND department_id = ?");
            $check_stmt->execute([$job_id, $user['department_id']]);
            
            if ($check_stmt->fetch()) {
                // Check if job has applications
                $app_check = $pdo->prepare("SELECT COUNT(*) as count FROM applications WHERE job_id = ?");
                $app_check->execute([$job_id]);
                $app_count = $app_check->fetch()['count'];
                
                if ($app_count > 0) {
                    $error_message = "Cannot delete job with existing applications. Please contact admin.";
                } else {
                    // Delete job
                    $delete_stmt = $pdo->prepare("DELETE FROM jobs WHERE id = ? AND department_id = ?");
                    if ($delete_stmt->execute([$job_id, $user['department_id']])) {
                        $success_message = "Job deleted successfully.";
                    } else {
                        $error_message = "Failed to delete job.";
                    }
                }
            } else {
                $error_message = "Job not found or access denied.";
            }
        } catch (PDOException $e) {
            error_log("Error deleting job: " . $e->getMessage());
            $error_message = "An error occurred while deleting the job.";
        }
    }
}

// Get department's jobs with application counts
$jobs_stmt = $pdo->prepare("
    SELECT j.*, 
           COUNT(a.id) as application_count,
           CASE 
               WHEN j.deadline < CURDATE() THEN 'expired'
               ELSE j.status
           END as display_status
    FROM jobs j 
    LEFT JOIN applications a ON j.id = a.job_id 
    WHERE j.department_id = ? 
    GROUP BY j.id 
    ORDER BY j.created_at DESC
");
$jobs_stmt->execute([$user['department_id']]);
$jobs = $jobs_stmt->fetchAll();
?>

<?php include __DIR__ . '/../includes/header.php'; ?>

    <div class="container-fluid mt-4">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3">
                <?php include __DIR__ . '/../includes/employer_sidebar.php'; ?>
            </div>

            <!-- Main Content -->
            <!-- Delete Confirmation Modal -->
    <div class="modal fade" id="deleteModal" tabindex="-1" aria-labelledby="deleteModalLabel" aria-hidden="true">
        <div class="modal-dialog">
            <div class="modal-content">
                <div class="modal-header">
                    <h5 class="modal-title" id="deleteModalLabel">Confirm Delete</h5>
                    <button type="button" class="btn-close" data-bs-dismiss="modal" aria-label="Close"></button>
                </div>
                <div class="modal-body">
                    <p>Are you sure you want to delete the job "<span id="jobTitle"></span>"?</p>
                    <p class="text-danger"><small>This action cannot be undone.</small></p>
                </div>
                <div class="modal-footer">
                    <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                    <form method="POST" style="display: inline;">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <input type="hidden" name="job_id" id="deleteJobId">
                        <input type="hidden" name="delete_job" value="1">
                        <button type="submit" class="btn btn-danger">Delete Job</button>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
<script>
    function confirmDelete(jobId, jobTitle) {
        document.getElementById('deleteJobId').value = jobId;
        document.getElementById('jobTitle').textContent = jobTitle;
        var modal = new bootstrap.Modal(document.getElementById('deleteModal'));
        modal.show();
    }
    </script>
    
    <?php prevent_back_navigation(); ?>