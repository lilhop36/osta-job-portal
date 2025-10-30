<?php
/**
 * Password Change Functionality
 * Allows users to change their own passwords
 */

session_start();
require_once 'auth.php';
require_once '../config/database.php';

// Redirect to login if not logged in
if (!isset($_SESSION['user_id'])) {
    header('Location: ../login.php');
    exit();
}

$user_id = $_SESSION['user_id'];
$error_message = '';
$success_message = '';

// Handle password change
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    $current_password = $_POST['current_password'];
    $new_password = $_POST['new_password'];
    $confirm_password = $_POST['confirm_password'];
    
    // Validate input
    if (empty($current_password) || empty($new_password) || empty($confirm_password)) {
        $error_message = 'All fields are required.';
    } elseif ($new_password !== $confirm_password) {
        $error_message = 'New passwords do not match.';
    } elseif (strlen($new_password) < 6) {
        $error_message = 'New password must be at least 6 characters long.';
    } else {
        // Verify current password
        $stmt = $pdo->prepare("SELECT password FROM users WHERE id = ?");
        $stmt->execute([$user_id]);
        $user = $stmt->fetch();
        
        if ($user && password_verify($current_password, $user['password'])) {
            // Update password
            $hashed_password = password_hash($new_password, PASSWORD_DEFAULT);
            $stmt = $pdo->prepare("UPDATE users SET password = ? WHERE id = ?");
            $stmt->execute([$hashed_password, $user_id]);
            
            $success_message = 'Password changed successfully.';
        } else {
            $error_message = 'Current password is incorrect.';
        }
    }
}
?>
