<?php
/**
 * Email Sending Helper
 * Handles sending of all application emails
 */

require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../config/email.php';

// Include PHPMailer if available
if (file_exists(__DIR__ . '/../vendor/autoload.php')) {
    require_once __DIR__ . '/../vendor/autoload.php';
}

/**
 * Send an email using the configured SMTP settings
 * 
 * @param string $to Recipient email address
 * @param string $subject Email subject
 * @param string $body Email body (HTML)
 * @param string $alt_body Plain text version of the email
 * @return bool True on success, false on failure
 */
function send_email($to, $subject, $body, $alt_body = '') {
    // Validate email address
    if (!filter_var($to, FILTER_VALIDATE_EMAIL)) {
        error_log("Invalid email address: $to");
        return false;
    }
    
    // Use PHPMailer if available, otherwise fall back to mail()
    if (class_exists('PHPMailer\PHPMailer\PHPMailer')) {
        return send_email_phpmailer($to, $subject, $body, $alt_body);
    } else {
        // Log that we're using fallback method
        error_log("PHPMailer not available, using PHP mail() function");
        return send_email_mail($to, $subject, $body, $alt_body);
    }
}

/**
 * Send email using PHPMailer with SMTP
 */
function send_email_phpmailer($to, $subject, $body, $alt_body = '') {
    $mail = new PHPMailer\PHPMailer\PHPMailer(true);
    
    try {
        // Server settings
        $mail->isSMTP();
        $mail->Host = SMTP_HOST;
        $mail->SMTPAuth = true;
        $mail->Username = SMTP_USERNAME;
        $mail->Password = SMTP_PASSWORD;
        $mail->SMTPSecure = SMTP_ENCRYPTION;
        $mail->Port = SMTP_PORT;
        
        // Recipients
        $mail->setFrom(SMTP_FROM_EMAIL, SMTP_FROM_NAME);
        $mail->addAddress($to);
        
        // Content
        $mail->isHTML(true);
        $mail->Subject = $subject;
        $mail->Body = $body;
        $mail->AltBody = $alt_body ?: strip_tags($body);
        
        $mail->send();
        return true;
    } catch (Exception $e) {
        error_log("Message could not be sent. Mailer Error: {$mail->ErrorInfo}");
        return false;
    }
}

/**
 * Fallback email sending using PHP's mail() function
 */
function send_email_mail($to, $subject, $body, $alt_body = '') {
    $headers = [];
    $headers[] = 'MIME-Version: 1.0';
    $headers[] = 'Content-type: text/html; charset=utf-8';
    $headers[] = 'From: ' . SMTP_FROM_NAME . ' <' . SMTP_FROM_EMAIL . '>';
    $headers[] = 'Reply-To: ' . SMTP_FROM_EMAIL;
    $headers[] = 'X-Mailer: PHP/' . phpversion();
    
    $alt_body = $alt_body ?: strip_tags($body);
    
    // Create a boundary for the email
    $boundary = md5(uniqid(time()));
    
    // Headers for the email
    $headers = [
        'MIME-Version: 1.0',
        'From: ' . SMTP_FROM_NAME . ' <' . SMTP_FROM_EMAIL . '>',
        'Content-Type: multipart/alternative; boundary="' . $boundary . '"',
        'X-Mailer: PHP/' . phpversion(),
    ];
    
    // Plain text version
    $message = "--$boundary\r\n" .
               "Content-Type: text/plain; charset=utf-8\r\n" .
               "Content-Transfer-Encoding: 7bit\r\n\r\n" .
               $alt_body . "\r\n";
    
    // HTML version
    $message .= "--$boundary\r\n" .
                "Content-Type: text/html; charset=utf-8\r\n" .
                "Content-Transfer-Encoding: 7bit\r\n\r\n" .
                $body . "\r\n";
    
    // End boundary
    $message .= "--$boundary--";
    
    // Suppress mail warnings in development and provide fallback
    $result = @mail($to, $subject, $message, implode("\r\n", $headers));
    
    // Log the result for debugging
    if (!$result) {
        error_log("Failed to send email to $to. This is normal in a local development environment without a configured mail server.");
        
        // In production, you might want to throw an exception or handle this differently
        // For now, we'll just return true to avoid breaking the application flow
        // return false;
    }
    
    return $result;
}

/**
 * Send application status update email to applicant
 * 
 * @param int $application_id The ID of the application
 * @param string $new_status The new status of the application
 * @return bool True on success, false on failure
 */
function send_application_status_email($application_id, $new_status) {
    global $pdo;
    
    try {
        // Get application details
        $stmt = $pdo->prepare("SELECT a.*, j.title as job_title, u.email, u.first_name, u.last_name 
                              FROM applications a 
                              JOIN jobs j ON a.job_id = j.id 
                              JOIN users u ON a.user_id = u.id 
                              WHERE a.id = ?");
        $stmt->execute([$application_id]);
        $application = $stmt->fetch(PDO::FETCH_ASSOC);
        
        if (!$application) {
            return false;
        }
        
        // Status display text
        $status_texts = [
            'pending' => 'Pending Review',
            'shortlisted' => 'Shortlisted',
            'interview' => 'Selected for Interview',
            'accepted' => 'Accepted',
            'rejected' => 'Not Selected',
            'withdrawn' => 'Withdrawn'
        ];
        
        $status_text = $status_texts[strtolower($new_status)] ?? ucfirst($new_status);
        
        // Email subject
        $subject = "Application Update: {$application['job_title']} - $status_text";
        
        // Email body
        $name = trim("{$application['first_name']} {$application['last_name']}");
        $job_title = htmlspecialchars($application['job_title']);
        
        $body = "
        <!DOCTYPE html>
        <html>
        <head>
            <style>
                body { font-family: Arial, sans-serif; line-height: 1.6; color: #333; }
                .container { max-width: 600px; margin: 0 auto; padding: 20px; }
                .header { background-color: #f8f9fa; padding: 20px; text-align: center; }
                .content { padding: 20px; }
                .status { 
                    display: inline-block; 
                    padding: 5px 15px; 
                    border-radius: 20px; 
                    font-weight: bold; 
                    margin: 10px 0; 
                }
                .status-pending { background-color: #fff3cd; color: #856404; }
                .status-shortlisted { background-color: #cce5ff; color: #004085; }
                .status-interview { background-color: #d4edda; color: #155724; }
                .status-accepted { background-color: #d4edda; color: #155724; }
                .status-rejected { background-color: #f8d7da; color: #721c24; }
                .status-withdrawn { background-color: #e2e3e5; color: #383d41; }
                .footer { margin-top: 30px; font-size: 12px; color: #6c757d; text-align: center; }
            </style>
        </head>
        <body>
            <div class='container'>
                <div class='header'>
                    <h2>OSTA Job Portal</h2>
                </div>
                <div class='content'>
                    <p>Dear $name,</p>
                    <p>The status of your application for <strong>$job_title</strong> has been updated:</p>
                    
                    <div class='status status-" . strtolower($new_status) . "'>$status_text</div>
                    
                    <p>";
        
        // Add custom message based on status
        switch (strtolower($new_status)) {
            case 'shortlisted':
                $body .= "Congratulations! Your application has been shortlisted. We will contact you soon with the next steps in the recruitment process.";
                break;
                
            case 'interview':
                $body .= "Congratulations! You have been selected for an interview. Please check your email for further details.";
                break;
                
            case 'accepted':
                $body .= "Congratulations! We are pleased to inform you that your application has been accepted. Welcome to our team!";
                break;
                
            case 'rejected':
                $body .= "Thank you for your interest in this position. After careful consideration, we have decided to move forward with other candidates whose qualifications more closely match our requirements.";
                break;
                
            case 'withdrawn':
                $body .= "Your application has been withdrawn as requested. Thank you for your interest in this position.";
                break;
                
            default:
                $body .= "The status of your application has been updated. Please log in to your account for more details.";
        }
        
        $body .= "</p>
                    <p>You can view the status of all your applications by logging into your account.</p>
                    <p>
                        <a href='" . SITE_URL . "applicant/dashboard.php' style='display: inline-block; padding: 10px 20px; background-color: #007bff; color: #ffffff; text-decoration: none; border-radius: 4px;'>View My Applications</a>
                    </p>
                </div>
                <div class='footer'>
                    <p>This is an automated message, please do not reply to this email.</p>
                    <p>&copy; " . date('Y') . " OSTA Job Portal. All rights reserved.</p>
                </div>
            </div>
        </body>
        </html>";
        
        // Send the email
        $result = send_email($application['email'], $subject, $body);
        
        // Log if email failed to send (normal in development)
        if (!$result) {
            error_log("Failed to send application status email to {$application['email']}. This is normal in a local development environment.");
        }
        
        return $result;
        
    } catch (Exception $e) {
        error_log("Error sending application status email: " . $e->getMessage());
        return false;
    }
}
