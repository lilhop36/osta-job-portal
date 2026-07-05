<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/email_verification.php';

if (!isset($_SESSION['reset_user_id'])) {
    header('Location: forgot_password.php');
    exit();
}

$user_id = $_SESSION['reset_user_id'];
$email = $_SESSION['reset_email'];
$error = '';
$success = '';
$step = 'otp'; // otp or password

// Check if OTP was already verified (resuming flow)
if (isset($_SESSION['otp_verified']) && $_SESSION['otp_verified'] === true) {
    $step = 'password';
}

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $error = "Security token validation failed. Please try again.";
    } elseif ($step === 'otp') {
        // OTP verification step
        $otp = trim($_POST['otp_code'] ?? '');

        if (strlen($otp) !== 6 || !ctype_digit($otp)) {
            $error = "Please enter a valid 6-digit code.";
        } elseif (verify_otp($user_id, $otp, 'password_reset')) {
            $_SESSION['otp_verified'] = true;
            $step = 'password';
        } else {
            $error = "Invalid or expired reset code. Please try again.";
        }
    } else {
        // Password change step
        $new_password = $_POST['new_password'] ?? '';
        $confirm_password = $_POST['confirm_password'] ?? '';

        if (strlen($new_password) < 8) {
            $error = "Password must be at least 8 characters long.";
        } elseif ($new_password !== $confirm_password) {
            $error = "Passwords do not match.";
        } else {
            global $pdo;
            $hashed = hash_password($new_password);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed, $user_id]);

            // Mark OTP as used
            $stmt = $pdo->prepare("UPDATE email_verifications SET used = 1 WHERE user_id = ? AND purpose = 'password_reset' AND used = 0");
            $stmt->execute([$user_id]);

            // Clear session
            unset($_SESSION['reset_user_id'], $_SESSION['reset_email'], $_SESSION['otp_verified']);

            $_SESSION['success_message'] = "Password reset successful! Please log in with your new password.";
            header('Location: login.php');
            exit();
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
                        <i class="fas fa-<?php echo $step === 'otp' ? 'shield-alt' : 'lock'; ?>"></i>
                    </div>
                    <h3 class="fw-bold mb-0"><?php echo $step === 'otp' ? 'Enter Reset Code' : 'Set New Password'; ?></h3>
                    <p class="text-muted mb-0">
                        <?php echo $step === 'otp' ? "Enter the code sent to $email" : 'Choose a strong new password'; ?>
                    </p>
                </div>
                <div class="card-body">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?></div>
                    <?php endif; ?>

                    <?php if ($step === 'otp'): ?>
                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <div class="mb-4">
                            <label for="otp_code" class="form-label text-center d-block">Reset Code</label>
                            <input type="text" class="form-control otp-input"
                                   id="otp_code" name="otp_code"
                                   maxlength="6" pattern="[0-9]{6}"
                                   placeholder="000000" required
                                   autocomplete="one-time-code" inputmode="numeric">
                        </div>
                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-check-circle me-2"></i>Verify Code
                            </button>
                        </div>
                    </form>

                    <?php else: ?>
                    <form method="POST" action="" id="passwordForm">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <div class="mb-3">
                            <label for="new_password" class="form-label">New Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="new_password" name="new_password"
                                       minlength="8" required placeholder="Minimum 8 characters">
                            </div>
                            <div class="form-text">At least 8 characters long.</div>
                        </div>
                        <div class="mb-4">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password"
                                       minlength="8" required placeholder="Re-enter password">
                            </div>
                        </div>
                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-key me-2"></i>Reset Password
                            </button>
                        </div>
                    </form>
                    <?php endif; ?>

                    <div class="text-center auth-links">
                        <p class="mb-0">
                            <a href="login.php"><i class="fas fa-arrow-left me-1"></i>Back to Login</a>
                        </p>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
<?php if ($step === 'otp'): ?>
<script>
document.getElementById('otp_code').addEventListener('input', function(e) {
    this.value = this.value.replace(/[^0-9]/g, '');
    if (this.value.length === 6) {
        this.form.submit();
    }
});
</script>
<?php else: ?>
<script>
document.getElementById('passwordForm').addEventListener('submit', function(e) {
    var pw = document.getElementById('new_password').value;
    var cpw = document.getElementById('confirm_password').value;
    if (pw !== cpw) {
        e.preventDefault();
        alert('Passwords do not match.');
    }
});
</script>
<?php endif; ?>
