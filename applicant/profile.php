<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Profile - OSTA Job Portal</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/security.php';

// Require applicant role
require_role('applicant', SITE_URL . '/login.php');

// Set security headers
set_security_headers();

// Handle profile update
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize inputs
    $full_name = sanitize($_POST['full_name']);
    $phone = sanitize($_POST['phone']);
    $address = sanitize($_POST['address']);
    $skills = sanitize($_POST['skills']);
    
    // Validate inputs
    $errors = [];
    
    if (empty($full_name) || empty($phone)) {
        $errors[] = "Full name and phone are required";
    }
    
    if (!preg_match("/^\+?[\d\s\-\(\)]{10,20}$/", $phone)) {
        $errors[] = "Invalid phone number format";
    }
    
    if (empty($errors)) {
        // Update user information (matching actual database schema)
        $stmt = $pdo->prepare("UPDATE users 
                             SET full_name = ?, phone = ?, address = ?, skills = ? 
                             WHERE id = ?");
        $stmt->execute([$full_name, $phone, $address, $skills, $_SESSION['user_id']]);
        
        $_SESSION['success_message'] = "Profile updated successfully";
        header('Location: ' . SITE_URL . '/applicant/profile.php');
        exit();
    }
}

// Get user info
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$user = $stmt->fetch();

// Note: Document management can be added later if needed
// For now, we'll focus on basic profile information
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Profile - OSTA Job Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include __DIR__ . '/../includes/header.php'; ?>

    <div class="container mt-4">
        <div class="row">
            <!-- Profile Sidebar -->
            <div class="col-md-3">
                <div class="card">
                    <div class="card-body">
                        <h5 class="card-title">Profile Menu</h5>
                        <ul class="list-group">
                            <li class="list-group-item active"><a href="<?php echo SITE_URL; ?>/applicant/profile.php" class="text-white text-decoration-none">Profile Information</a></li>
                            <li class="list-group-item"><a href="<?php echo SITE_URL; ?>/applicant/change_password.php">Change Password</a></li>
                            <li class="list-group-item"><a href="<?php echo SITE_URL; ?>/applicant/saved_jobs.php">Saved Jobs</a></li>
                            <li class="list-group-item"><a href="<?php echo SITE_URL; ?>/applicant/dashboard.php">My Applications</a></li>
                            <li class="list-group-item"><a href="<?php echo SITE_URL; ?>/applicant/alerts.php">Job Alerts</a></li>
                            <li class="list-group-item"><a href="<?php echo SITE_URL; ?>/applicant/export.php">Export Data</a></li>
                            <li class="list-group-item"><a href="<?php echo SITE_URL; ?>/applicant/delete_account.php" class="text-danger"><i class="fas fa-trash-alt me-1"></i> Delete Account</a></li>
                        </ul>
                    </div>
                </div>
            </div>

            <!-- Profile Content -->
            <div class="col-md-9">
                <div class="card">
                    <div class="card-header">
                        <h3 class="mb-0">Profile Information</h3>
                    </div>
                    <div class="card-body">
                        <?php if (isset($_SESSION['success_message'])): ?>
                            <div class="alert alert-success"><?php echo $_SESSION['success_message']; ?></div>
                            <?php unset($_SESSION['success_message']); ?>
                        <?php endif; ?>
                        
                        <?php if (!empty($errors)): ?>
                            <div class="alert alert-danger">
                                <ul class="mb-0">
                                    <?php foreach ($errors as $error): ?>
                                        <li><?php echo htmlspecialchars($error); ?></li>
                                    <?php endforeach; ?>
                                </ul>
                            </div>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            <div class="mb-3">
                                <label for="full_name" class="form-label">Full Name *</label>
                                <input type="text" class="form-control" id="full_name" name="full_name" 
                                       value="<?php echo htmlspecialchars($user['full_name'] ?? ''); ?>" required>
                            </div>
                            
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label for="phone" class="form-label">Phone *</label>
                                    <input type="tel" class="form-control" id="phone" name="phone" 
                                           value="<?php echo htmlspecialchars($user['phone'] ?? ''); ?>" required>
                                </div>
                                <div class="col-md-6 mb-3">
                                    <label for="email" class="form-label">Email</label>
                                    <input type="email" class="form-control" id="email" name="email" 
                                           value="<?php echo htmlspecialchars($user['email']); ?>" disabled>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label for="address" class="form-label">Address</label>
                                <textarea class="form-control" id="address" name="address" rows="3"><?php echo htmlspecialchars($user['address'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="mb-3">
                                <label for="skills" class="form-label">Skills & Experience</label>
                                <textarea class="form-control" id="skills" name="skills" rows="4" placeholder="List your skills, experience, and qualifications..."><?php echo htmlspecialchars($user['skills'] ?? ''); ?></textarea>
                            </div>
                            
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Update Profile</button>
                            </div>
                        </form>
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
