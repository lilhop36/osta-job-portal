<?php
require_once '../includes/bootstrap.php';

// Require applicant role
require_role('applicant', '../login.php');

$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = "Security token validation failed. Please try again.";
    } else {
        $password = $_POST['password'] ?? '';
        $confirm = $_POST['confirm'] ?? '';
        
        if (empty($password)) {
            $error = 'Password is required';
        } elseif ($confirm !== 'DELETE') {
            $error = 'Please type DELETE to confirm account deletion';
        } else {
            $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
            $stmt->execute([$_SESSION['user_id']]);
            $user = $stmt->fetch();
            
            if ($user && verify_password($password, $user['password'])) {
                $pdo->beginTransaction();
                
                try {
                    $tables = [
                        'applications' => 'user_id',
                        'saved_jobs' => 'user_id',
                        'applicant_documents' => 'user_id',
                        'notifications' => 'user_id'
                    ];
                    
                    foreach ($tables as $table => $column) {
                        $stmt = $pdo->prepare("DELETE FROM {$table} WHERE {$column} = ?");
                        $stmt->execute([$_SESSION['user_id']]);
                    }
                    
                    $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                    $stmt->execute([$_SESSION['user_id']]);
                    
                    $pdo->commit();
                    
                    session_destroy();
                    header('Location: ../index.php');
                    exit();
                    
                } catch (Exception $e) {
                    $pdo->rollBack();
                    $error = 'An error occurred while deleting your account. Please try again.';
                    error_log('Account deletion error: ' . $e->getMessage());
                }
            } else {
                $error = 'Incorrect password';
            }
        }
    }
}
?>
<?php include '../includes/header.php'; ?>

<div class="container">
    <div class="row justify-content-center" style="padding-top: 60px; padding-bottom: 60px;">
        <div class="col-md-6">
            <a href="dashboard.php" class="btn btn-outline-secondary btn-sm mb-3">
                <i class="fas fa-arrow-left me-1"></i> Back to Dashboard
            </a>
            <div class="card">
                <div class="card-header bg-danger text-white text-center">
                    <i class="fas fa-exclamation-triangle fa-2x mb-2"></i>
                    <h4 class="mb-0">Delete Your Account</h4>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    
                    <div class="alert alert-warning">
                        <h6><i class="fas fa-exclamation-triangle me-1"></i> This action cannot be undone</h6>
                        <p class="mb-0">Deleting your account will permanently remove all your data, including profile information, job applications, saved jobs, and uploaded documents.</p>
                    </div>
                    
                    <form method="POST" action="" onsubmit="return confirm('Are you absolutely sure? This cannot be undone.');">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <div class="mb-3">
                            <label for="password" class="form-label">Enter your password *</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        
                        <div class="mb-4">
                            <label for="confirm" class="form-label">Type <strong>DELETE</strong> to confirm *</label>
                            <input type="text" class="form-control" id="confirm" name="confirm" placeholder="Type DELETE" required>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="profile.php" class="btn btn-secondary me-md-2">Cancel</a>
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-trash-alt me-1"></i> Delete Account
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
