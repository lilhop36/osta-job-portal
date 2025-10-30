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

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Employer Profile - OSTA Job Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0/css/all.min.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <!-- Sidebar -->
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Employer Menu</h5>
                        <ul class="list-group list-group-flush">
                            <li class="list-group-item"><a href="dashboard.php"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
                            <li class="list-group-item"><a href="post_job.php"><i class="fas fa-plus me-2"></i>Post New Job</a></li>
                            <li class="list-group-item"><a href="manage_applications.php"><i class="fas fa-users me-2"></i>Manage Applications</a></li>
                            <li class="list-group-item active"><a href="profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                            <li class="list-group-item"><a href="change_password.php"><i class="fas fa-key me-2"></i>Change Password</a></li>
                            <li class="list-group-item"><a href="logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Main Content -->
            <div class="col-md-9">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2>Employer Profile</h2>
                    <a href="dashboard.php" class="btn btn-secondary">
                        <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                    </a>
                </div>

                <!-- Success/Error Messages -->
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

                <div class="row">
                    <!-- Profile Information -->
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-user me-2"></i>Profile Information</h5>
                            </div>
                            <div class="card-body">
                                <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                                    <div class="mb-3">
                                        <label for="full_name" class="form-label">Full Name *</label>
                                        <input type="text" class="form-control" id="full_name" name="full_name" 
                                               value="<?php echo htmlspecialchars($user['full_name']); ?>" required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="email" class="form-label">Email Address *</label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               value="<?php echo htmlspecialchars($user['email']); ?>" required>
                                    </div>

                                    <div class="mb-3">
                                        <label for="phone" class="form-label">Phone Number</label>
                                        <input type="tel" class="form-control" id="phone" name="phone" 
                                               value="<?php echo htmlspecialchars($user['phone']); ?>">
                                    </div>

                                    <div class="mb-3">
                                        <label for="address" class="form-label">Address</label>
                                        <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($user['address']); ?></textarea>
                                    </div>

                                    <div class="d-grid">
                                        <button type="submit" class="btn btn-primary">
                                            <i class="fas fa-save me-2"></i>Update Profile
                                        </button>
                                    </div>
                                </form>
                            </div>
                        </div>
                    </div>

                    <!-- Department Information -->
                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="mb-0"><i class="fas fa-building me-2"></i>Department Information</h5>
                            </div>
                            <div class="card-body">
                                <div class="mb-3">
                                    <label class="form-label fw-bold">Department Name</label>
                                    <p class="form-control-plaintext"><?php echo htmlspecialchars($user['department_name']); ?></p>
                                </div>

                                <?php if ($user['department_description']): ?>
                                    <div class="mb-3">
                                        <label class="form-label fw-bold">Description</label>
                                        <p class="form-control-plaintext"><?php echo htmlspecialchars($user['department_description']); ?></p>
                                    </div>
                                <?php endif; ?>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Role</label>
                                    <p class="form-control-plaintext">
                                        <span class="badge bg-primary">Employer</span>
                                    </p>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Username</label>
                                    <p class="form-control-plaintext"><?php echo htmlspecialchars($user['username']); ?></p>
                                </div>

                                <div class="mb-3">
                                    <label class="form-label fw-bold">Member Since</label>
                                    <p class="form-control-plaintext"><?php echo date('F j, Y', strtotime($user['created_at'])); ?></p>
                                </div>
                            </div>
                        </div>

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

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <?php prevent_back_navigation(); ?>
</body>
</html>
