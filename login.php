<?php
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/includes/auth.php';

// If already logged in, redirect to appropriate dashboard
if (is_logged_in()) {
    redirect_to_dashboard();
}

$apply_job_id_for_register = isset($_GET['apply_job_id']) ? $_GET['apply_job_id'] : null;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    // CSRF protection
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = "Security token validation failed. Please try again.";
    } else {
        $email = sanitize_input($_POST['email']);
        $password = $_POST['password'];
        
        // Rate limiting
        $rate_limit_key = $_SERVER['REMOTE_ADDR'] . '_' . $email;
        if (!check_rate_limit($rate_limit_key)) {
            $error = "Too many login attempts. Please try again in 15 minutes.";
            log_security_event('rate_limit_exceeded', "Email: $email, IP: {$_SERVER['REMOTE_ADDR']}");
        } else {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && verify_password($password, $user['password'])) {
                // Set session variables with enhanced security
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['last_activity'] = time();
                $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
                
                // Log successful login
                log_security_event('login_success', "User: {$user['username']}, Role: {$user['role']}");
                
                // Regenerate session ID for security
                session_regenerate_id(true);
        
        // Handle pending job application or redirect
        $redirect = isset($_POST['redirect']) ? $_POST['redirect'] : (isset($_GET['redirect']) ? $_GET['redirect'] : null);
        $apply_job_id = isset($_POST['apply_job_id']) ? $_POST['apply_job_id'] : (isset($_GET['apply_job_id']) ? $_GET['apply_job_id'] : null);
        
        if ($user['role'] === 'applicant' && $apply_job_id) {
            $_SESSION['pending_job_application'] = (int)$apply_job_id;
            header('Location: ' . SITE_URL . '/applicant/dashboard.php');
            exit();
        } elseif ($redirect) {
            header('Location: ' . $redirect);
            exit();
        } else {
            redirect_to_dashboard();
        }
            } else {
                $error = "Invalid email or password";
                log_security_event('login_failed', "Email: $email, IP: {$_SERVER['REMOTE_ADDR']}");
            }
        }
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Login - OSTA Job Portal</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body class="bg-light">
    <?php include __DIR__ . '/includes/header.php'; ?>

    <div class="container">
        <div class="row justify-content-center mt-5">
            <div class="col-md-6">
                <div class="card">
                    <div class="card-header">
                        <h3 class="text-center mb-0">Login</h3>
                    </div>
                    <div class="card-body">
                        <?php if (isset($error)): ?>
                            <div class="alert alert-danger"><?php echo $error; ?></div>
                        <?php endif; ?>
                        
                        <?php
                        // Build query string for form action
                        $query_params = [];
                        if (isset($_GET['redirect'])) {
                            $query_params['redirect'] = $_GET['redirect'];
                        }
                        if (isset($_GET['apply_job_id'])) {
                            $query_params['apply_job_id'] = $_GET['apply_job_id'];
                        }
                        $query_string = !empty($query_params) ? '?' . http_build_query($query_params) : '';
                        ?>
                        <form method="POST" action="login.php<?php echo $query_string; ?>">
                            <!-- CSRF Token -->
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            <!-- Hidden fields to preserve redirect parameters -->
                            <?php if (isset($_GET['redirect'])): ?>
                                <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($_GET['redirect']); ?>">
                            <?php endif; ?>
                            <?php if (isset($_GET['apply_job_id'])): ?>
                                <input type="hidden" name="apply_job_id" value="<?php echo htmlspecialchars($_GET['apply_job_id']); ?>">
                            <?php endif; ?>
                            <div class="mb-3">
                                <label for="email" class="form-label">Email</label>
                                <input type="email" class="form-control" id="email" name="email" required>
                            </div>
                            <div class="mb-3">
                                <label for="password" class="form-label">Password</label>
                                <input type="password" class="form-control" id="password" name="password" required>
                            </div>
                            <div class="d-grid">
                                <button type="submit" class="btn btn-primary">Login</button>
                            </div>
                        </form>
                        <div class="text-center mt-3">
                            <a href="register.php<?php echo $apply_job_id_for_register ? '?apply_job_id=' . htmlspecialchars($apply_job_id_for_register) : ''; ?>">Don't have an account? Register here</a>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
