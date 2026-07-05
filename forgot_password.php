<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/email_verification.php';

// If already logged in, redirect to dashboard
if (isset($_SESSION['user_id']) && isset($_SESSION['role'])) {
    redirect_to_dashboard();
}

$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = "Security token validation failed. Please try again.";
    } else {
        $email = strtolower(trim($_POST['email'] ?? ''));

        if (empty($email) || !filter_var($email, FILTER_VALIDATE_EMAIL)) {
            $error = "Please enter a valid email address.";
        } else {
            // Check if user exists
            global $pdo;
            $stmt = $pdo->prepare("SELECT id, username, email FROM users WHERE email = ? AND status = 'active'");
            $stmt->execute([$email]);
            $user = $stmt->fetch();

            if ($user) {
                // Generate and send OTP
                $otp = resend_otp($user['id'], 'password_reset');
                send_otp_email($user['id'], $user['email'], $user['username'], $otp);

                // Store for next step
                $_SESSION['reset_user_id'] = $user['id'];
                $_SESSION['reset_email'] = $user['email'];

                $_SESSION['success_message'] = "A password reset code has been sent to your email.";
                header('Location: reset_password.php');
                exit();
            } else {
                // Don't reveal if email exists — show success anyway
                $success = "If an account with that email exists, a reset code has been sent.";
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
                        <i class="fas fa-key"></i>
                    </div>
                    <h3 class="fw-bold mb-0">Forgot Password?</h3>
                    <p class="text-muted mb-0">Enter your email to receive a reset code</p>
                </div>
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?></div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email"
                                       placeholder="your@email.com" required
                                       value="<?php echo htmlspecialchars($_POST['email'] ?? ''); ?>">
                            </div>
                        </div>
                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-paper-plane me-2"></i>Send Reset Code
                            </button>
                        </div>
                    </form>

                    <div class="text-center auth-links">
                        <p class="mb-2">
                            <a href="login.php"><i class="fas fa-arrow-left me-1"></i>Back to Login</a>
                        </p>
                        <p class="mb-0">
                            Don't have an account? <a href="register.php">Register</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
