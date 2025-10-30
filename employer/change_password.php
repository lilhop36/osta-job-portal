<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/security.php';

// Require employer role
require_role('employer', '../login.php');

// Set security headers
set_security_headers();

$success_message = '';
$error_message = '';

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Verify CSRF token
    if (!verify_csrf_token($_POST['csrf_token'])) {
        $error_message = "Invalid CSRF token. Please try again.";
    } else {
        $current_password = $_POST['current_password'];
        $new_password = $_POST['new_password'];
        $confirm_password = $_POST['confirm_password'];
        
        // Validation
        $errors = [];
        
        if (empty($current_password)) {
            $errors[] = 'Current password is required';
        }
        
        if (empty($new_password)) {
            $errors[] = 'New password is required';
        } elseif (strlen($new_password) < 8) {
            $errors[] = 'New password must be at least 8 characters long';
        }
        
        if ($new_password !== $confirm_password) {
            $errors[] = 'New password and confirmation do not match';
        }
        
        if (empty($errors)) {
            try {
                // Get current user data
                $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch();
                
                if (!$user) {
                    $errors[] = 'User not found';
                } elseif (!password_verify($current_password, $user['password'])) {
                    $errors[] = 'Current password is incorrect';
                } else {
                    // Update password
                    $hashed_password = password_hash($new_password, PASSWORD_ARGON2ID);
                    $update_stmt = $pdo->prepare("UPDATE users SET password = ?, updated_at = NOW() WHERE id = ?");
                    
                    if ($update_stmt->execute([$hashed_password, $_SESSION['user_id']])) {
                        $success_message = 'Password changed successfully!';
                        
                        // Log the password change
                        error_log("Password changed for user ID: " . $_SESSION['user_id']);
                    } else {
                        $errors[] = 'Failed to update password. Please try again.';
                    }
                }
            } catch (PDOException $e) {
                error_log("Password change error: " . $e->getMessage());
                $errors[] = 'An error occurred while changing your password.';
            }
        }
        
        if (!empty($errors)) {
            $error_message = implode('<br>', $errors);
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Change Password - OSTA Job Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <div class="container-fluid mt-4">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3">
                <div class="card dashboard-card">
                    <div class="card-header bg-gradient-primary text-white">
                        <h3 class="mb-0"><i class="fas fa-building me-2"></i>Employer Menu</h3>
                    </div>
                    <div class="list-group list-group-flush dashboard-sidebar">
                        <a href="dashboard.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-tachometer-alt me-2"></i> Dashboard
                        </a>
                        <a href="post_job.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-plus-circle me-2"></i> Post Job
                        </a>
                        <a href="manage_jobs.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-briefcase me-2"></i> Manage Jobs
                        </a>
                        <a href="manage_applications.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-users me-2"></i> Applications
                        </a>
                        <a href="profile.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-user me-2"></i> Profile
                        </a>
                        <a href="change_password.php" class="list-group-item list-group-item-action active">
                            <i class="fas fa-key me-2"></i> Change Password
                        </a>
                        <a href="reports.php" class="list-group-item list-group-item-action">
                            <i class="fas fa-chart-bar me-2"></i> Reports
                        </a>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Change Password</h2>
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                    </a>
                </div>

                <!-- Messages -->
                <?php if ($success_message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="fas fa-check-circle me-2"></i><?php echo $success_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error_message): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="fas fa-exclamation-circle me-2"></i><?php echo $error_message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="row justify-content-center">
                    <div class="col-lg-6">
                        <div class="card dashboard-card">
                            <div class="card-header bg-white">
                                <h5 class="mb-0"><i class="fas fa-key me-2 text-primary"></i>Change Your Password</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="" id="changePasswordForm">
                                    <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                    
                                    <div class="mb-3">
                                        <label for="current_password" class="form-label">Current Password *</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="current_password" 
                                                   name="current_password" required>
                                            <button class="btn btn-outline-secondary" type="button" 
                                                    onclick="togglePassword('current_password')">
                                                <i class="fas fa-eye" id="current_password_icon"></i>
                                            </button>
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="new_password" class="form-label">New Password *</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="new_password" 
                                                   name="new_password" required minlength="8">
                                            <button class="btn btn-outline-secondary" type="button" 
                                                    onclick="togglePassword('new_password')">
                                                <i class="fas fa-eye" id="new_password_icon"></i>
                                            </button>
                                        </div>
                                        <div class="form-text">
                                            Password must be at least 8 characters long.
                                        </div>
                                    </div>

                                    <div class="mb-3">
                                        <label for="confirm_password" class="form-label">Confirm New Password *</label>
                                        <div class="input-group">
                                            <input type="password" class="form-control" id="confirm_password" 
                                                   name="confirm_password" required minlength="8">
                                            <button class="btn btn-outline-secondary" type="button" 
                                                    onclick="togglePassword('confirm_password')">
                                                <i class="fas fa-eye" id="confirm_password_icon"></i>
                                            </button>
                                        </div>
                                        <div id="password_match_message" class="form-text"></div>
                                    </div>

                                    <!-- Password Strength Indicator -->
                                    <div class="mb-3">
                                        <label class="form-label">Password Strength</label>
                                        <div class="progress" style="height: 5px;">
                                            <div class="progress-bar" id="password_strength_bar" role="progressbar" 
                                                 style="width: 0%" aria-valuenow="0" aria-valuemin="0" aria-valuemax="100"></div>
                                        </div>
                                        <small id="password_strength_text" class="form-text text-muted">Enter a password to see strength</small>
                                    </div>

                                    <div class="d-grid gap-2">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Change Password
                                        </button>
                                        <a href="profile.php" class="btn btn-outline-secondary">
                                            <i class="fas fa-times me-2"></i>Cancel
                                        </a>
                                    </div>
                                </form>
                            </div>
                        </div>

                        <!-- Security Tips -->
                        <div class="card dashboard-card mt-4">
                            <div class="card-header bg-white">
                                <h6 class="mb-0"><i class="fas fa-shield-alt me-2 text-success"></i>Password Security Tips</h6>
                            </div>
                            <div class="card-body">
                                <ul class="list-unstyled mb-0">
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Use at least 8 characters</li>
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Include uppercase and lowercase letters</li>
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Add numbers and special characters</li>
                                    <li class="mb-2"><i class="fas fa-check text-success me-2"></i>Avoid common words or personal information</li>
                                    <li class="mb-0"><i class="fas fa-check text-success me-2"></i>Don't reuse passwords from other accounts</li>
                                </ul>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    
    <script>
    function togglePassword(fieldId) {
        const field = document.getElementById(fieldId);
        const icon = document.getElementById(fieldId + '_icon');
        
        if (field.type === 'password') {
            field.type = 'text';
            icon.classList.remove('fa-eye');
            icon.classList.add('fa-eye-slash');
        } else {
            field.type = 'password';
            icon.classList.remove('fa-eye-slash');
            icon.classList.add('fa-eye');
        }
    }

    // Password strength checker
    document.getElementById('new_password').addEventListener('input', function() {
        const password = this.value;
        const strength = calculatePasswordStrength(password);
        updatePasswordStrengthIndicator(strength);
    });

    // Password match checker
    document.getElementById('confirm_password').addEventListener('input', function() {
        const newPassword = document.getElementById('new_password').value;
        const confirmPassword = this.value;
        const messageDiv = document.getElementById('password_match_message');
        
        if (confirmPassword === '') {
            messageDiv.textContent = '';
            messageDiv.className = 'form-text';
        } else if (newPassword === confirmPassword) {
            messageDiv.textContent = 'Passwords match';
            messageDiv.className = 'form-text text-success';
        } else {
            messageDiv.textContent = 'Passwords do not match';
            messageDiv.className = 'form-text text-danger';
        }
    });

    function calculatePasswordStrength(password) {
        let strength = 0;
        
        if (password.length >= 8) strength += 20;
        if (password.length >= 12) strength += 10;
        if (/[a-z]/.test(password)) strength += 15;
        if (/[A-Z]/.test(password)) strength += 15;
        if (/[0-9]/.test(password)) strength += 15;
        if (/[^A-Za-z0-9]/.test(password)) strength += 25;
        
        return Math.min(strength, 100);
    }

    function updatePasswordStrengthIndicator(strength) {
        const bar = document.getElementById('password_strength_bar');
        const text = document.getElementById('password_strength_text');
        
        bar.style.width = strength + '%';
        bar.setAttribute('aria-valuenow', strength);
        
        if (strength < 30) {
            bar.className = 'progress-bar bg-danger';
            text.textContent = 'Weak';
            text.className = 'form-text text-danger';
        } else if (strength < 60) {
            bar.className = 'progress-bar bg-warning';
            text.textContent = 'Fair';
            text.className = 'form-text text-warning';
        } else if (strength < 80) {
            bar.className = 'progress-bar bg-info';
            text.textContent = 'Good';
            text.className = 'form-text text-info';
        } else {
            bar.className = 'progress-bar bg-success';
            text.textContent = 'Strong';
            text.className = 'form-text text-success';
        }
    }

    // Form validation
    document.getElementById('changePasswordForm').addEventListener('submit', function(e) {
        const newPassword = document.getElementById('new_password').value;
        const confirmPassword = document.getElementById('confirm_password').value;
        
        if (newPassword !== confirmPassword) {
            e.preventDefault();
            alert('Passwords do not match. Please check and try again.');
            return false;
        }
        
        if (newPassword.length < 8) {
            e.preventDefault();
            alert('Password must be at least 8 characters long.');
            return false;
        }
    });
    </script>
    
    <?php prevent_back_navigation(); ?>
</body>
</html>
