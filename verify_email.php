<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/email_verification.php';

if (!isset($_SESSION['verify_user_id'])) {
    header('Location: register.php');
    exit();
}

$user_id = $_SESSION['verify_user_id'];
$email = $_SESSION['verify_email'];
$username = $_SESSION['verify_username'];
$otp_display = '';
$error = '';
$success = '';

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = "Security token validation failed. Please try again.";
    } else {
        $otp = trim($_POST['otp_code'] ?? '');

        if (strlen($otp) !== 6 || !ctype_digit($otp)) {
            $error = "Please enter a valid 6-digit code.";
        } elseif (verify_otp($user_id, $otp)) {
            mark_email_verified($user_id);

            unset($_SESSION['verify_user_id'], $_SESSION['verify_email'], $_SESSION['verify_username']);

            $_SESSION['user_id'] = $user_id;
            $_SESSION['role'] = 'applicant';
            $_SESSION['username'] = $username;
            session_regenerate_id(true);

            $_SESSION['success_message'] = "Email verified successfully! Welcome to OSTA Job Portal.";
            header('Location: applicant/dashboard.php');
            exit();
        } else {
            $error = "Invalid or expired verification code. Please try again.";
        }
    }
}

if (isset($_GET['action']) && $_GET['action'] === 'resend') {
    if (!isset($_GET['csrf_token']) || !verify_csrf_token($_GET['csrf_token'])) {
        $error = "Security token validation failed.";
    } else {
        $otp = resend_otp($user_id);
        send_otp_email($user_id, $email, $username, $otp);
        $success = "A new verification code has been sent to your email.";
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
    .otp-input {
        font-size: 1.8rem;
        letter-spacing: 12px;
        text-align: center;
        padding: 14px;
        border: 2px solid #e9ecef;
        border-radius: 12px;
        font-weight: 700;
    }
    .otp-input:focus {
        border-color: var(--osta-green);
        box-shadow: 0 0 0 3px rgba(34,139,34,0.1);
    }
</style>

<div class="container">
    <div class="row justify-content-center" style="padding-top: 80px; padding-bottom: 80px;">
        <div class="col-md-5">
            <div class="card auth-card">
                <div class="card-header">
                    <div class="auth-icon">
                        <i class="fas fa-envelope-open-text"></i>
                    </div>
                    <h3 class="fw-bold mb-0">Verify Your Email</h3>
                    <p class="text-muted mb-0">Enter the code sent to your inbox</p>
                </div>
                <div class="card-body">
                    <p class="text-muted text-center mb-3">
                        We've sent a 6-digit code to<br>
                        <strong style="color: var(--osta-dark);"><?php echo htmlspecialchars($email); ?></strong>
                    </p>

                    <?php if (isset($_SESSION['_dev_otp'])): ?>
                        <div class="alert alert-warning text-center">
                            <i class="fas fa-code me-1"></i> <strong>Dev Mode:</strong> <code style="font-size:18px;letter-spacing:4px;"><?php echo $_SESSION['_dev_otp']; ?></code>
                        </div>
                    <?php endif; ?>

                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>
                    <?php if (!empty($success)): ?>
                        <div class="alert alert-success"><i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?></div>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <div class="mb-4">
                            <label for="otp_code" class="form-label text-center d-block">Verification Code</label>
                            <input type="text" class="form-control otp-input" 
                                   id="otp_code" name="otp_code" 
                                   maxlength="6" pattern="[0-9]{6}" 
                                   placeholder="000000" required
                                   autocomplete="one-time-code" inputmode="numeric">
                        </div>
                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-check-circle me-2"></i>Verify Email
                            </button>
                        </div>
                    </form>

                    <div class="text-center auth-links">
                        <p class="mb-2">
                            Didn't receive the code? 
                            <a href="verify_email.php?action=resend&csrf_token=<?php echo urlencode(generate_csrf_token()); ?>">
                                <i class="fas fa-redo me-1"></i>Resend
                            </a>
                        </p>
                        <p class="mb-0">
                            <a href="register.php"><i class="fas fa-arrow-left me-1"></i>Register with a different email</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
<script>
document.getElementById('otp_code').addEventListener('input', function(e) {
    this.value = this.value.replace(/[^0-9]/g, '');
    if (this.value.length === 6) {
        this.form.submit();
    }
});
</script>
