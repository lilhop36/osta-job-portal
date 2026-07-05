<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/email_verification.php';

if (is_logged_in()) {
    redirect_to_dashboard();
}

$apply_job_id_for_register = isset($_GET['apply_job_id']) ? $_GET['apply_job_id'] : null;
$error = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = "Security token validation failed. Please try again.";
    } else {
        $email = sanitize_input($_POST['email']);
        $password = $_POST['password'];
        
        $rate_limit_key = $_SERVER['REMOTE_ADDR'] . '_' . $email;
        if (!check_rate_limit($rate_limit_key)) {
            $error = "Too many login attempts. Please try again in 15 minutes.";
            log_security_event('rate_limit_exceeded', "Email: $email, IP: {$_SERVER['REMOTE_ADDR']}");
        } else {
            $stmt = $pdo->prepare("SELECT * FROM users WHERE email = ? AND status = 'active'");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user && verify_password($password, $user['password'])) {
                $_SESSION['user_id'] = $user['id'];
                $_SESSION['role'] = $user['role'];
                $_SESSION['username'] = $user['username'];
                $_SESSION['last_activity'] = time();
                $_SESSION['ip_address'] = $_SERVER['REMOTE_ADDR'];
                
                log_security_event('login_success', "User: {$user['username']}, Role: {$user['role']}");
                session_regenerate_id(true);
        
                $redirect = isset($_POST['redirect']) ? $_POST['redirect'] : (isset($_GET['redirect']) ? $_GET['redirect'] : null);
                $apply_job_id = isset($_POST['apply_job_id']) ? $_POST['apply_job_id'] : (isset($_GET['apply_job_id']) ? $_GET['apply_job_id'] : null);
                
                if ($user['role'] === 'applicant' && $apply_job_id) {
                    $_SESSION['pending_job_application'] = (int)$apply_job_id;
                    header('Location: ' . SITE_URL . '/applicant/dashboard.php');
                    exit();
                } elseif ($redirect && is_safe_internal_redirect($redirect)) {
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
<?php include __DIR__ . '/includes/header.php'; ?>

<style>
    body {
        background: linear-gradient(160deg, var(--osta-dark) 0%, #0f3d0f 50%, var(--osta-green-dark) 100%);
        min-height: 100vh;
    }
    .auth-card {
        border: none;
        border-radius: 16px;
        box-shadow: 0 20px 60px rgba(0,0,0,0.3);
        overflow: hidden;
    }
    .auth-card .card-header {
        background: white;
        border-bottom: none;
        padding: 2rem 2rem 0;
        text-align: center;
    }
    .auth-card .card-body {
        padding: 2rem;
    }
    .auth-icon {
        width: 70px;
        height: 70px;
        background: linear-gradient(135deg, rgba(34,139,34,0.1), rgba(34,139,34,0.05));
        border-radius: 50%;
        display: flex;
        align-items: center;
        justify-content: center;
        margin: 0 auto 1rem;
        font-size: 2rem;
        color: var(--osta-green);
    }
    .auth-card .btn-primary {
        background: linear-gradient(135deg, var(--osta-green), var(--osta-green-dark));
        border: none;
        padding: 12px;
        border-radius: 10px;
        font-weight: 700;
        font-size: 1.05rem;
    }
    .auth-card .btn-primary:hover {
        transform: translateY(-2px);
        box-shadow: 0 8px 25px rgba(34,139,34,0.4);
    }
    .auth-links a {
        color: var(--osta-green);
        font-weight: 600;
    }
</style>

<div class="container">
    <div class="row justify-content-center" style="padding-top: 80px; padding-bottom: 80px;">
        <div class="col-md-5">
            <div class="card auth-card">
                <div class="card-header">
                    <div class="auth-icon">
                        <i class="fas fa-sign-in-alt"></i>
                    </div>
                    <h3 class="fw-bold mb-0">Welcome Back</h3>
                    <p class="text-muted mb-0">Sign in to your account</p>
                </div>
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    
                    <?php
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
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <?php if (isset($_GET['redirect'])): ?>
                            <input type="hidden" name="redirect" value="<?php echo htmlspecialchars($_GET['redirect']); ?>">
                        <?php endif; ?>
                        <?php if (isset($_GET['apply_job_id'])): ?>
                            <input type="hidden" name="apply_job_id" value="<?php echo htmlspecialchars($_GET['apply_job_id']); ?>">
                        <?php endif; ?>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email" placeholder="you@example.com" required>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Enter your password" required>
                            </div>
                            <div class="text-end mt-1">
                                <a href="forgot_password.php" class="text-muted" style="font-size: 0.85rem;">
                                    <i class="fas fa-key me-1"></i>Forgot Password?
                                </a>
                            </div>
                        </div>
                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-sign-in-alt me-2"></i>Login
                            </button>
                        </div>
                    </form>
                    <div class="text-center auth-links">
                        <a href="register.php<?php echo $apply_job_id_for_register ? '?apply_job_id=' . htmlspecialchars($apply_job_id_for_register) : ''; ?>">
                            <i class="fas fa-user-plus me-1"></i> Don't have an account? Register
                        </a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
