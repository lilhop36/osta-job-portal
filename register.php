<?php
require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/includes/email_verification.php';

if (is_logged_in()) {
    redirect_to_dashboard();
}

$errors = [];

if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $errors[] = "Security token validation failed. Please try again.";
    } else {
        $username = sanitize($_POST['username']);
        $email = sanitize($_POST['email']);
        $password = $_POST['password'];
        $confirm_password = $_POST['confirm_password'];

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

        $stmt = $pdo->prepare("SELECT id FROM users WHERE username = ?");
        $stmt->execute([$username]);
        if ($stmt->fetch()) {
            $errors[] = "Username is already taken. Please choose another.";
        }

        $stmt = $pdo->prepare("SELECT id FROM users WHERE email = ?");
        $stmt->execute([$email]);
        if ($stmt->fetch()) {
            $errors[] = "An account with this email already exists.";
        }

        if (empty($errors)) {
            $hashed_password = hash_password($password);
            $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, status, email_verified) VALUES (?, ?, ?, 'applicant', 'active', 0)");
            $stmt->execute([$username, $email, $hashed_password]);

            $new_user_id = $pdo->lastInsertId();

            $otp = generate_otp($new_user_id);
            send_otp_email($new_user_id, $email, $username, $otp);

            $_SESSION['verify_user_id'] = $new_user_id;
            $_SESSION['verify_email'] = $email;
            $_SESSION['verify_username'] = $username;

            header('Location: verify_email.php');
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
</style>

<div class="container">
    <div class="row justify-content-center" style="padding-top: 60px; padding-bottom: 60px;">
        <div class="col-md-5">
            <div class="card auth-card">
                <div class="card-header">
                    <div class="auth-icon">
                        <i class="fas fa-user-plus"></i>
                    </div>
                    <h3 class="fw-bold mb-0">Create Account</h3>
                    <p class="text-muted mb-0">Join OSTA Job Portal today</p>
                </div>
                <div class="card-body">
                    <?php if (!empty($errors)): ?>
                        <?php foreach ($errors as $error): ?>
                            <div class="alert alert-danger"><i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?></div>
                        <?php endforeach; ?>
                    <?php endif; ?>

                    <form method="POST" action="">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <input type="hidden" name="apply_job_id" value="<?php echo isset($_GET['apply_job_id']) ? (int)$_GET['apply_job_id'] : ''; ?>">
                        <div class="mb-3">
                            <label for="username" class="form-label">Username</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-user"></i></span>
                                <input type="text" class="form-control" id="username" name="username" placeholder="Choose a username" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="email" class="form-label">Email Address</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-envelope"></i></span>
                                <input type="email" class="form-control" id="email" name="email" placeholder="you@example.com" required>
                            </div>
                        </div>
                        <div class="mb-3">
                            <label for="password" class="form-label">Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="password" name="password" placeholder="Min. 8 characters" required>
                            </div>
                        </div>
                        <div class="mb-4">
                            <label for="confirm_password" class="form-label">Confirm Password</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-lock"></i></span>
                                <input type="password" class="form-control" id="confirm_password" name="confirm_password" placeholder="Re-enter password" required>
                            </div>
                        </div>
                        <div class="d-grid mb-3">
                            <button type="submit" class="btn btn-primary">
                                <i class="fas fa-user-plus me-2"></i>Register
                            </button>
                        </div>
                    </form>
                    <div class="text-center auth-links">
                        <a href="login.php"><i class="fas fa-sign-in-alt me-1"></i> Already have an account? Login</a>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
