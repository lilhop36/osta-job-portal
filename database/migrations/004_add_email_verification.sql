-- Add email_verified column to users table
-- and create email_verifications table for OTP codes

ALTER TABLE users ADD COLUMN email_verified TINYINT(1) DEFAULT 0 AFTER status;

CREATE TABLE IF NOT EXISTS email_verifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    otp_code VARCHAR(6) NOT NULL,
    purpose ENUM('email_verify', 'password_reset') NOT NULL DEFAULT 'email_verify',
    expires_at DATETIME NOT NULL,
    used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
);

CREATE INDEX idx_otp_lookup ON email_verifications(user_id, purpose, used);
