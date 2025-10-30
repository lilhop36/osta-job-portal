<?php
// Initialize session and security first
require_once 'config/database.php';
require_once 'includes/security.php';

// Initialize secure session
init_secure_session();

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // Sanitize all inputs
    $username = sanitize($_POST['username']);
    $email = sanitize($_POST['email']);
    $password = $_POST['password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate inputs
    $errors = [];
    
    if (empty($username) || empty($email) || empty($password)) {
        $errors[] = "All fields are required";
    }
    
    if (strlen($password) < 8) {
        $errors[] = "Password must be at least 8 characters";
    }
    
    if ($password !== $confirm_password) {
        $errors[] = "Passwords do not match";
    }
    
    if (!filter_var($email, FILTER_VALIDATE_EMAIL)) {
        $errors[] = "Invalid email format";
    }
    
    // Check if username already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
    $stmt->execute([$username]);
    if ($stmt->fetch()) {
        $errors[] = "Username is already taken. Please choose another.";
    }

    // Check if email already exists
    $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $stmt->execute([$email]);
    if ($stmt->fetch()) {
        $errors[] = "An account with this email already exists.";
    }
    
    if (empty($errors)) {
        // Hash password and insert user
        $hashed_password = hash_password($password);
        $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, status) VALUES (?, ?, ?, 'applicant', 'active')");
        $stmt->execute([$username, $email, $hashed_password]);
        
        // Log in the user
        $_SESSION['user_id'] = $pdo->lastInsertId();
        $_SESSION['role'] = 'applicant';
        $_SESSION['username'] = $username;
        
        // Handle pending job application
        if (isset($_POST['apply_job_id']) && !empty($_POST['apply_job_id'])) {
            $_SESSION['pending_job_application'] = (int)$_POST['apply_job_id'];
        }

        // Redirect to appropriate dashboard based on role
        $redirect_url = isset($_SESSION['pending_job_application']) ? 
            'apply.php?job_id=' . $_SESSION['pending_job_application'] : 
            $_SESSION['role'] . '/dashboard.php';
            
        // Clear any pending job application from session
        unset($_SESSION['pending_job_application']);
        
        // Ensure output buffering is off before redirect
        if (ob_get_level()) ob_end_clean();
        
        // Use absolute URL for redirect
        header('Location: ' . SITE_URL . '/' . $redirect_url);
        exit();
        exit();
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Register - OSTA Job Portal</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="bg-light">
    <?php include 'includes/header.php'; ?>

    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-center mb-0">Register</h3>
                    </div>
                    <div class="card-body">
                        <?php if (!empty($errors)): ?>
                            <?php foreach ($errors as $error): ?>
                                <div class="alert alert-danger"><?php echo $error; ?></div>
                            <?php endforeach; ?>
                        <?php endif; ?>
                        
                        <form method="POST" action="">
                            <input type="hidden" name="apply_job_id" value="<?php echo isset($_GET['apply_job_id']) ? htmlspecialchars($_GET['apply_job_id']) : ''; ?>">
                            <div class="mb-3">
                                <label for="username" class="form-label">Username</label>
                                <input type="text" class="form-control" id="username" name="username" required>
                            </div>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="mb-3">
                                <label for="confirm_password" class="form-label">Confirm Password</label>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" required>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Register</button>
                            </div>
                        </form>
                        <div class="text-center mt-3">
                            <a href="login.php">Already have an account? Login here</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
