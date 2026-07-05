<?php
/**
 * Email Verification Service
 * Handles OTP generation, storage, sending, and verification.
 */

/**
 * Generate a 6-digit OTP and store it in the database.
 * Returns the OTP code.
 */
function generate_otp(int $user_id, string $purpose = 'email_verify'): string {
    global $pdo;

    $otp = str_pad(random_int(100000, 999999), 6, '0', STR_PAD_LEFT);

    $stmt = $pdo->prepare("INSERT INTO email_verifications (user_id, otp_code, purpose, expires_at) VALUES (?, ?, ?, DATE_ADD(NOW(), INTERVAL 15 MINUTE))");
    $stmt->execute([$user_id, $otp, $purpose]);

    return $otp;
}

/**
 * Verify an OTP code. Returns true if valid, false otherwise.
 */
function verify_otp(int $user_id, string $otp, string $purpose = 'email_verify'): bool {
    global $pdo;

    $stmt = $pdo->prepare("SELECT id FROM email_verifications 
                           WHERE user_id = ? AND otp_code = ? AND purpose = ? AND used = 0 AND expires_at > NOW()
                           ORDER BY id DESC LIMIT 1");
    $stmt->execute([$user_id, $otp, $purpose]);
    $record = $stmt->fetch();

    if (!$record) {
        return false;
    }

    $stmt = $pdo->prepare("UPDATE email_verifications SET used = 1 WHERE id = ?");
    $stmt->execute([$record['id']]);

    return true;
}

/**
 * Send OTP email to user.
 * In development, also logs OTP to error_log for testing.
 */
function send_otp_email(int $user_id, string $email, string $username, string $otp): bool {
    require_once __DIR__ . '/mailer.php';

    $subject = "Your OSTA Job Portal Verification Code";
    $body = "
    <div style='font-family: Arial, sans-serif; max-width: 600px; margin: 0 auto;'>
        <h2 style='color: #0d6efd;'>Email Verification</h2>
        <p>Hello <strong>" . htmlspecialchars($username) . "</strong>,</p>
        <p>Thank you for registering with OSTA Job Portal. Please use the following verification code to confirm your email address:</p>
        <div style='background: #f8f9fa; border: 2px dashed #0d6efd; padding: 20px; text-align: center; margin: 20px 0; border-radius: 8px;'>
            <span style='font-size: 32px; font-weight: bold; letter-spacing: 8px; color: #0d6efd;'>" . $otp . "</span>
        </div>
        <p>This code expires in <strong>15 minutes</strong>.</p>
        <p>If you did not register for an account, please ignore this email.</p>
        <hr style='border: 1px solid #dee2e6;'>
        <p style='color: #6c757d; font-size: 12px;'>OSTA Job Portal - Oromia Science and Technology Authority</p>
    </div>";

    // Log OTP for development testing
    error_log("=== EMAIL VERIFICATION OTP ===");
    error_log("To: $email");
    error_log("OTP: $otp");
    error_log("Expires: " . date('Y-m-d H:i:s', strtotime('+15 minutes')));
    error_log("=============================");

    // Store OTP in session for dev display on verify page
    $_SESSION['_dev_otp'] = $otp;

    return send_email($email, $subject, $body);
}

/**
 * Mark user's email as verified.
 */
function mark_email_verified(int $user_id): bool {
    global $pdo;

    $stmt = $pdo->prepare("UPDATE users SET email_verified = 1 WHERE id = ?");
    return $stmt->execute([$user_id]);
}

/**
 * Check if user's email is verified.
 */
function is_email_verified(int $user_id): bool {
    global $pdo;

    $stmt = $pdo->prepare("SELECT email_verified FROM users WHERE id = ?");
    $stmt->execute([$user_id]);
    $user = $stmt->fetch();

    return $user && $user['email_verified'] == 1;
}

/**
 * Resend OTP — invalidates old unused codes and generates a new one.
 */
function resend_otp(int $user_id, string $purpose = 'email_verify'): string {
    global $pdo;

    // Invalidate old unused codes
    $stmt = $pdo->prepare("UPDATE email_verifications SET used = 1 WHERE user_id = ? AND purpose = ? AND used = 0");
    $stmt->execute([$user_id, $purpose]);

    return generate_otp($user_id, $purpose);
}
