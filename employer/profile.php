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

// Get current user info with department details
$stmt = $pdo->prepare("
    SELECT u.*, d.name as department_name, d.description as department_description 
    FROM users u 
    LEFT JOIN departments d ON u.department_id = d.id 
    WHERE u.id = ?
");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

if (!$user) {
    $_SESSION['error'] = 'User not found';
    header('Location: ../login.php');
    exit();
}

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $full_name = sanitize($_POST['full_name']);
    $email = sanitize($_POST['email']);
    $phone = sanitize($_POST['phone']);
    $address = sanitize($_POST['address']);
    
    // Validation
    $errors = [];
    
    if (empty($full_name)) {
        $errors[] = 'Full name is required';
    }
    
    if (empty($email)) {
        $errors[] = 'Email is required';
    } elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = 'Please enter a valid email address';
    }
    
    // Check if email is already taken by another user
    if ($email !== $user['email']) {
        $email_check = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $email_check->execute([$email, $_SESSION['user_id']]);
        if ($email_check->fetch()) {
            $errors[] = 'Email address is already taken';
        }
    }
    
    if (empty($errors)) {
        try {
            $update_stmt = $pdo->prepare("
                UPDATE users 
                SET full_name = ?, email = ?, phone = ?, address = ?, updated_at = NOW()
                WHERE id = ?
            ");
            
            if ($update_stmt->execute([$full_name, $email, $phone, $address, $_SESSION['user_id']])) {
                $success_message = 'Profile updated successfully!';
                
                // Refresh user data
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch();
            } else {
                $error_message = 'Failed to update profile. Please try again.';
            }
        } catch (PDOException $e) {
            error_log("Profile update error: " . $e->getMessage());
            $error_message = 'An error occurred while updating your profile.';
        }
    } else {
        $error_message = implode('<br>', $errors);
    }
}
?>

<?php include __DIR__ . '/../includes/header.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3">
                <?php include __DIR__ . '/../includes/employer_sidebar.php'; ?>
            </div>

            <!-- Main Content -->
            <!-- Quick Actions -->
                        <div class="card mt-4">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-bolt me-2"></i>Quick Actions</h5>
                            </div>
                            <div class="card-body">
                                <div class="d-grid gap-2">
                                    <a href="change_password.php" class="btn btn-outline-primary btn-sm">
                                        <i class="fas fa-key me-2"></i>Change Password
                                    </a>
                                    <a href="post_job.php" class="btn btn-outline-success btn-sm">
                                        <i class="fas fa-plus me-2"></i>Post New Job
                                    </a>
                                    <a href="manage_applications.php" class="btn btn-outline-info btn-sm">
                                        <i class="fas fa-users me-2"></i>View Applications
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/../includes/footer.php'; ?>
<?php prevent_back_navigation(); ?>