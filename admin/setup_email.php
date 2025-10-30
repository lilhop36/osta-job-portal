<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/security.php';

// Require admin role
require_role('admin', '../login.php');

// Set security headers
set_security_headers();

$message = '';
$error = '';

// Handle form submission
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['test_email'])) {
    $test_email = sanitize($_POST['email']);
    
    if (filter_var($test_email, FILTER_VALIDATE_EMAIL)) {
        // Test email sending
        require_once '../includes/mailer.php';
        
        $subject = 'OSTA Job Portal - Email Configuration Test';
        $body = '
        <html>
        <body style="font-family: Arial, sans-serif; line-height: 1.6; color: #333;">
            <div style="max-width: 600px; margin: 0 auto; padding: 20px;">
                <h2 style="color: #007bff;">Email Configuration Test</h2>
                <p>Congratulations! Your email configuration is working correctly.</p>
                <p>This test email was sent from your OSTA Job Portal system at ' . date('Y-m-d H:i:s') . '.</p>
                <p>You can now use the notification system to send emails to applicants.</p>
                <hr style="margin: 20px 0;">
                <p style="font-size: 12px; color: #666;">This is an automated test message from OSTA Job Portal.</p>
            </div>
        </body>
        </html>';
        
        if (send_email($test_email, $subject, $body)) {
            $message = 'Test email sent successfully to ' . htmlspecialchars($test_email) . '!';
        } else {
            $error = 'Failed to send test email. Please check your email configuration.';
        }
    } else {
        $error = 'Please enter a valid email address.';
    }
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Email Setup - OSTA Job Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdn.jsdelivr.net/npm/bootstrap-icons@1.7.2/font/bootstrap-icons.css">
    <link rel="stylesheet" href="../assets/css/style.css">
</head>
<body>
    <?php include '../includes/admin_navbar.php'; ?>
    
    <div class="container-fluid">
        <div class="row">
            <?php include '../includes/admin_sidebar.php'; ?>
            
            <main class="col-md-9 ms-sm-auto col-lg-10 px-md-4">
                <div class="d-flex justify-content-between flex-wrap flex-md-nowrap align-items-center pt-3 pb-2 mb-3 border-bottom">
                    <h1 class="h2">Email Configuration Setup</h1>
                </div>

                <?php if ($message): ?>
                    <div class="alert alert-success alert-dismissible fade show" role="alert">
                        <i class="bi bi-check-circle me-2"></i><?php echo $message; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <?php if ($error): ?>
                    <div class="alert alert-danger alert-dismissible fade show" role="alert">
                        <i class="bi bi-exclamation-triangle me-2"></i><?php echo $error; ?>
                        <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                    </div>
                <?php endif; ?>

                <div class="row">
                    <div class="col-lg-8">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-envelope-gear me-2"></i>Email Configuration Instructions
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="alert alert-info">
                                    <h6><i class="bi bi-info-circle me-2"></i>Configuration Required</h6>
                                    <p class="mb-0">To enable email notifications, you need to configure your SMTP settings in the email configuration file.</p>
                                </div>

                                <h6>Step 1: Configure Email Settings</h6>
                                <p>Edit the file: <code>config/email.php</code></p>
                                
                                <h6>For Gmail (Recommended):</h6>
                                <ol>
                                    <li>Enable 2-factor authentication on your Gmail account</li>
                                    <li>Generate an "App Password" for this application:
                                        <ul>
                                            <li>Go to Google Account settings</li>
                                            <li>Security → 2-Step Verification → App passwords</li>
                                            <li>Generate a password for "Mail"</li>
                                        </ul>
                                    </li>
                                    <li>Update the configuration:
                                        <pre class="bg-light p-3 rounded"><code>define('SMTP_HOST', 'smtp.gmail.com');
define('SMTP_PORT', 587);
define('SMTP_USERNAME', 'your-email@gmail.com');
define('SMTP_PASSWORD', 'your-16-character-app-password');
define('SMTP_ENCRYPTION', 'tls');</code></pre>
                                    </li>
                                </ol>

                                <h6>For Other Email Providers:</h6>
                                <p>Consult your email provider's documentation for SMTP settings and update the configuration accordingly.</p>

                                <div class="alert alert-warning">
                                    <h6><i class="bi bi-exclamation-triangle me-2"></i>Security Note</h6>
                                    <p class="mb-0">Never use your regular email password. Always use app-specific passwords or API keys provided by your email service.</p>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="col-lg-4">
                        <div class="card">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-envelope-check me-2"></i>Test Email Configuration
                                </h5>
                            </div>
                            <div class="card-body">
                                <p>After configuring your email settings, test the configuration by sending a test email.</p>
                                
                                <form method="POST">
                                    <?php echo csrf_token_field(); ?>
                                    <div class="mb-3">
                                        <label for="email" class="form-label">Test Email Address</label>
                                        <input type="email" class="form-control" id="email" name="email" 
                                               placeholder="Enter email to test" required>
                                        <div class="form-text">We'll send a test email to this address.</div>
                                    </div>
                                    <button type="submit" name="test_email" class="btn btn-primary">
                                        <i class="bi bi-send me-2"></i>Send Test Email
                                    </button>
                                </form>
                            </div>
                        </div>

                        <div class="card mt-3">
                            <div class="card-header">
                                <h5 class="card-title mb-0">
                                    <i class="bi bi-info-circle me-2"></i>Current Status
                                </h5>
                            </div>
                            <div class="card-body">
                                <div class="d-flex align-items-center mb-2">
                                    <span class="badge bg-<?php echo defined('SMTP_HOST') ? 'success' : 'warning'; ?> me-2">
                                        <?php echo defined('SMTP_HOST') ? 'Configured' : 'Not Configured'; ?>
                                    </span>
                                    SMTP Host
                                </div>
                                <div class="d-flex align-items-center mb-2">
                                    <span class="badge bg-<?php echo class_exists('PHPMailer\\PHPMailer\\PHPMailer') ? 'success' : 'warning'; ?> me-2">
                                        <?php echo class_exists('PHPMailer\\PHPMailer\\PHPMailer') ? 'Available' : 'Not Available'; ?>
                                    </span>
                                    PHPMailer
                                </div>
                                <div class="d-flex align-items-center">
                                    <span class="badge bg-<?php echo function_exists('mail') ? 'success' : 'danger'; ?> me-2">
                                        <?php echo function_exists('mail') ? 'Available' : 'Not Available'; ?>
                                    </span>
                                    PHP Mail Function
                                </div>
                            </div>
                        </div>
                    </div>
                </div>
            </main>
        </div>
    </div>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <?php prevent_back_navigation(); ?>
</body>
</html>
