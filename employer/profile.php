<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/security.php';

require_role('employer', '../login.php');
set_security_headers();

$success_message = '';
$error_message = '';

// Get current user info
$stmt = $pdo->prepare("SELECT u.*, d.name as department_name FROM users u LEFT JOIN departments d ON u.department_id = d.id WHERE u.id = ?");
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

    $errors = [];
    if (empty($full_name)) $errors[] = 'Full name is required';
    if (empty($email)) $errors[] = 'Email is required';
    elseif (!filter_var($email, FILTER_VALIDATE_EMAIL)) $errors[] = 'Please enter a valid email address';

    if ($email !== $user['email']) {
        $email_check = $pdo->prepare("SELECT id FROM users WHERE email = ? AND id != ?");
        $email_check->execute([$email, $_SESSION['user_id']]);
        if ($email_check->fetch()) $errors[] = 'Email address is already taken';
    }

    if (empty($errors)) {
        try {
            $update_stmt = $pdo->prepare("UPDATE users SET full_name = ?, email = ?, phone = ?, address = ?, updated_at = NOW() WHERE id = ?");
            if ($update_stmt->execute([$full_name, $email, $phone, $address, $_SESSION['user_id']])) {
                $success_message = 'Profile updated successfully!';
                $stmt->execute([$_SESSION['user_id']]);
                $user = $stmt->fetch();
            } else {
                $error_message = 'Failed to update profile.';
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
        <div class="col-md-3">
            <?php include __DIR__ . '/../includes/employer_sidebar.php'; ?>
        </div>

        <div class="col-md-9">
            <?php if (!empty($success_message)): ?>
                <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?= htmlspecialchars($success_message) ?></div>
            <?php endif; ?>
            <?php if (!empty($error_message)): ?>
                <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?= $error_message ?></div>
            <?php endif; ?>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-user me-2"></i>My Profile</h5>
                </div>
                <div class="card-body">
                    <form method="POST">
                        <div class="row">
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Full Name</label>
                                <input type="text" class="form-control" name="full_name" value="<?= htmlspecialchars($user['full_name'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Email Address</label>
                                <input type="email" class="form-control" name="email" value="<?= htmlspecialchars($user['email'] ?? '') ?>" required>
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Phone</label>
                                <input type="text" class="form-control" name="phone" value="<?= htmlspecialchars($user['phone'] ?? '') ?>">
                            </div>
                            <div class="col-md-6 mb-3">
                                <label class="form-label fw-bold">Username</label>
                                <input type="text" class="form-control" value="<?= htmlspecialchars($user['username'] ?? '') ?>" disabled>
                            </div>
                            <div class="col-md-12 mb-3">
                                <label class="form-label fw-bold">Address</label>
                                <textarea class="form-control" name="address" rows="2"><?= htmlspecialchars($user['address'] ?? '') ?></textarea>
                            </div>
                        </div>
                        <button type="submit" class="btn btn-success"><i class="fas fa-save me-1"></i>Save Changes</button>
                    </form>
                </div>
            </div>

            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-info-circle me-2"></i>Account Info</h5>
                </div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6 mb-2">
                            <strong>Role:</strong> <span class="badge bg-gradient-success">Employer</span>
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>Department:</strong> <?= htmlspecialchars($user['department_name'] ?? 'Not assigned') ?>
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>Member Since:</strong> <?= date('M d, Y', strtotime($user['created_at'])) ?>
                        </div>
                        <div class="col-md-6 mb-2">
                            <strong>Status:</strong> <span class="badge bg-<?= $user['status'] === 'active' ? 'success' : 'danger' ?>"><?= ucfirst($user['status']) ?></span>
                        </div>
                    </div>
                </div>
            </div>

            <div class="card mb-4">
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

<?php include __DIR__ . '/../includes/footer.php'; ?>
