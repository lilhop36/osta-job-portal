<?php
/**
 * Test script for the new view_application.php functionality
 */

require_once 'config/database.php';

echo "<h2>🧪 Testing View Application Functionality</h2>\n";

try {
    // Find applications with their job and user details
    $stmt = $pdo->query("
        SELECT a.id as application_id, a.status, a.created_at,
               u.full_name, u.email, 
               j.id as job_id, j.title as job_title, j.department_id,
               d.name as department_name
        FROM applications a
        JOIN users u ON a.user_id = u.id
        JOIN jobs j ON a.job_id = j.id
        JOIN departments d ON j.department_id = d.id
        ORDER BY a.created_at DESC
        LIMIT 10
    ");
    $applications = $stmt->fetchAll();
    
    if (empty($applications)) {
        echo "<p style='color: orange;'>⚠ No applications found in database</p>\n";
        
        // Create a test application
        $applicant = $pdo->query("SELECT id FROM users WHERE role = 'applicant' LIMIT 1")->fetch();
        $job = $pdo->query("SELECT id FROM jobs WHERE status = 'approved' LIMIT 1")->fetch();
        
        if ($applicant && $job) {
            $stmt = $pdo->prepare("
                INSERT INTO applications (user_id, job_id, status, created_at) 
                VALUES (?, ?, 'pending', NOW())
            ");
            $stmt->execute([$applicant['id'], $job['id']]);
            $new_app_id = $pdo->lastInsertId();
            echo "<p style='color: green;'>✅ Created test application ID: $new_app_id</p>\n";
            
            // Re-fetch applications
            $stmt = $pdo->query("
                SELECT a.id as application_id, a.status, a.created_at,
                       u.full_name, u.email, 
                       j.id as job_id, j.title as job_title, j.department_id,
                       d.name as department_name
                FROM applications a
                JOIN users u ON a.user_id = u.id
                JOIN jobs j ON a.job_id = j.id
                JOIN departments d ON j.department_id = d.id
                ORDER BY a.created_at DESC
                LIMIT 10
            ");
            $applications = $stmt->fetchAll();
        }
    }
    
    if (!empty($applications)) {
        echo "<h3>📋 Available Applications</h3>\n";
        echo "<table style='border-collapse: collapse; width: 100%; border: 1px solid #ddd;'>\n";
        echo "<tr style='background: #f8f9fa; border: 1px solid #ddd;'>\n";
        echo "<th style='padding: 8px; border: 1px solid #ddd;'>Application ID</th>\n";
        echo "<th style='padding: 8px; border: 1px solid #ddd;'>Applicant</th>\n";
        echo "<th style='padding: 8px; border: 1px solid #ddd;'>Job</th>\n";
        echo "<th style='padding: 8px; border: 1px solid #ddd;'>Department</th>\n";
        echo "<th style='padding: 8px; border: 1px solid #ddd;'>Status</th>\n";
        echo "<th style='padding: 8px; border: 1px solid #ddd;'>Test Link</th>\n";
        echo "</tr>\n";
        
        foreach ($applications as $app) {
            echo "<tr style='border: 1px solid #ddd;'>\n";
            echo "<td style='padding: 8px; border: 1px solid #ddd;'>{$app['application_id']}</td>\n";
            echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($app['full_name']) . "</td>\n";
            echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($app['job_title']) . "</td>\n";
            echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . htmlspecialchars($app['department_name']) . "</td>\n";
            echo "<td style='padding: 8px; border: 1px solid #ddd;'>" . ucfirst($app['status']) . "</td>\n";
            echo "<td style='padding: 8px; border: 1px solid #ddd;'>\n";
            echo "<a href='employer/view_application.php?application_id={$app['application_id']}' target='_blank'>View Application</a>\n";
            echo "</td>\n";
            echo "</tr>\n";
        }
        echo "</table>\n";
    }
    
    // Check employers and their departments
    echo "<h3>👥 Employers and Departments</h3>\n";
    $employers = $pdo->query("
        SELECT u.id, u.username, u.department_id, d.name as department_name
        FROM users u
        LEFT JOIN departments d ON u.department_id = d.id
        WHERE u.role = 'employer'
    ")->fetchAll();
    
    echo "<ul>\n";
    foreach ($employers as $emp) {
        echo "<li>Employer: {$emp['username']} (ID: {$emp['id']}) - Department: " . 
             ($emp['department_name'] ?? 'Not assigned') . " (ID: {$emp['department_id']})</li>\n";
    }
    echo "</ul>\n";
    
    echo "<h3>🎯 Test Instructions</h3>\n";
    echo "<ol>\n";
    echo "<li>Login as an employer</li>\n";
    echo "<li>Use one of the 'View Application' links above</li>\n";
    echo "<li>Or navigate: Dashboard → Manage Jobs → View Applicants → View (eye icon)</li>\n";
    echo "<li>Test the status update buttons</li>\n";
    echo "<li>Test the resume download (if available)</li>\n";
    echo "</ol>\n";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Error: " . $e->getMessage() . "</p>\n";
}
?>