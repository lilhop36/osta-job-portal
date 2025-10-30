<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

// Require applicant role
require_role('applicant', '../login.php');

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $password = $_POST['password'] ?? '';
    $confirm = $_POST['confirm'] ?? '';
    
    // Validate inputs
    if (empty($password)) {
        $error = 'Password is required';
    } elseif ($confirm !== 'DELETE') {
        $error = 'Please type DELETE to confirm account deletion';
    } else {
        // Verify password
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$_SESSION['user_id']]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($password, $user['password'])) {
            // Start transaction
            $pdo->beginTransaction();
            
            try {
                // Delete related records first
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
                
                // Delete user
                $stmt = $pdo->prepare("DELETE FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                
                // Log out the user
                session_destroy();
                
                // Commit transaction
                $pdo->commit();
                
                // Redirect to home page with success message
                $_SESSION['success_message'] = 'Your account has been successfully deleted.';
                header('Location: ../index.php');
                exit();
                
            } catch (Exception $e) {
                // Rollback transaction on error
                $pdo->rollBack();
                $error = 'An error occurred while deleting your account. Please try again.';
                error_log('Account deletion error: ' . $e->getMessage());
            }
        } else {
            $error = 'Incorrect password';
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Delete Account - OSTA Job Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
    <style>
        .delete-confirm {
            max-width: 600px;
            margin: 40px auto;
        }
        .delete-warning {
            border-left: 4px solid #dc3545;
            padding: 15px;
            margin-bottom: 20px;
            background-color: #f8f9fa;
        }
    </style>
</head>
<body>
    <?php include '../includes/header.php'; ?>

    <div class="container">
        <div class="delete-confirm">
            <div class="card">
                <div class="card-header bg-danger text-white">
                    <h4 class="mb-0">Delete Your Account</h4>
                </div>
                <div class="card-body">
                    <?php if ($error): ?>
                        <div class="alert alert-danger"><?php echo $error; ?></div>
                    <?php endif; ?>
                    
                    <div class="delete-warning">
                        <h5><i class="fas fa-exclamation-triangle text-danger"></i> Warning: This action cannot be undone</h5>
                        <p class="mb-0">Deleting your account will permanently remove all your data, including:</p>
                        <ul>
                            <li>Your profile information</li>
                            <li>All job applications</li>
                            <li>Saved jobs</li>
                            <li>Uploaded resumes and certificates</li>
                        </ul>
                    </div>
                    
                    <form method="POST" action="" onsubmit="return confirm('Are you absolutely sure you want to delete your account? This cannot be undone.');">
                        <div class="mb-3">
                            <label for="password" class="form-label">Enter your password to confirm *</label>
                            <input type="password" class="form-control" id="password" name="password" required>
                        </div>
                        
                        <div class="mb-4">
                            <label for="confirm" class="form-label">Type <strong>DELETE</strong> to confirm *</label>
                            <input type="text" class="form-control" id="confirm" name="confirm" required>
                        </div>
                        
                        <div class="d-grid gap-2 d-md-flex justify-content-md-end">
                            <a href="profile.php" class="btn btn-secondary me-md-2">Cancel</a>
                            <button type="submit" class="btn btn-danger">
                                <i class="fas fa-trash-alt me-1"></i> Permanently Delete My Account
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <?php include '../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
