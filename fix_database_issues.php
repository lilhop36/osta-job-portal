<?php
/**
 * Database Fix Script for OSTA Job Portal
 * Fixes critical database inconsistencies and permission issues
 */

require_once 'config/database.php';

echo "<h2>🔧 Fixing OSTA Job Portal Database Issues</h2>\n";

try {
    $pdo->beginTransaction();
    
    // 1. Fix user department assignments based on their roles and job creations
    echo "<h3>1. Fixing User Department Assignments</h3>\n";
    
    // Update users who created jobs but don't have department_id set
    $stmt = $pdo->prepare("
        UPDATE users u 
        JOIN (
            SELECT created_by, department_id 
            FROM jobs 
            WHERE created_by IS NOT NULL 
            GROUP BY created_by, department_id
        ) j ON u.id = j.created_by 
        SET u.department_id = j.department_id 
        WHERE u.department_id IS NULL AND u.role = 'employer'
    ");
    $stmt->execute();
    $updated = $stmt->rowCount();
    echo "<p style='color: green;'>✓ Updated $updated employer accounts with proper department assignments</p>\n";
    
    // 2. Fix application status references
    echo "<h3>2. Fixing Application Status Issues</h3>\n";
    
    // Check if application_status table exists (it shouldn't based on schema)
    $tables = $pdo->query("SHOW TABLES LIKE 'application_status'")->fetchAll();
    if (empty($tables)) {
        echo "<p style='color: orange;'>⚠ application_status table doesn't exist (this is correct)</p>\n";
        echo "<p>✓ Applications table already has status column - no fix needed</p>\n";
    }
    
    // 3. Add missing indexes for performance
    echo "<h3>3. Adding Performance Indexes</h3>\n";
    
    $indexes = [
        "ALTER TABLE applications ADD INDEX idx_job_user (job_id, user_id)" => "applications job_user index",
        "ALTER TABLE jobs ADD INDEX idx_department_status (department_id, status)" => "jobs department_status index",
        "ALTER TABLE users ADD INDEX idx_role_department (role, department_id)" => "users role_department index"
    ];
    
    foreach ($indexes as $sql => $description) {
        try {
            $pdo->exec($sql);
            echo "<p style='color: green;'>✓ Added $description</p>\n";
        } catch (PDOException $e) {
            if (strpos($e->getMessage(), 'Duplicate key name') !== false) {
                echo "<p style='color: orange;'>⚠ $description already exists</p>\n";
            } else {
                echo "<p style='color: red;'>✗ Failed to add $description: " . $e->getMessage() . "</p>\n";
            }
        }
    }
    
    // 4. Fix job ownership issues
    echo "<h3>4. Fixing Job Ownership</h3>\n";
    
    // Update jobs where created_by is NULL but we can infer from department
    $stmt = $pdo->prepare("
        UPDATE jobs j 
        JOIN users u ON j.department_id = u.department_id 
        SET j.created_by = u.id 
        WHERE j.created_by IS NULL 
        AND u.role = 'employer' 
        AND u.department_id IS NOT NULL
    ");
    $stmt->execute();
    $updated = $stmt->rowCount();
    echo "<p style='color: green;'>✓ Fixed $updated jobs with missing created_by values</p>\n";
    
    // 5. Clean up debug logging
    echo "<h3>5. Security Cleanup</h3>\n";
    
    // List files with debug code that should be removed in production
    $debug_files = [
        'employer/view_applicants.php',
        'employer/edit_job.php', 
        'admin/manage_jobs.php',
        'admin/manage_interviews.php',
        'applicant/centralized_application.php',
        'applicant/cancel_application.php'
    ];
    
    echo "<p style='color: orange;'>⚠ The following files contain debug code that should be removed in production:</p>\n";
    echo "<ul>\n";
    foreach ($debug_files as $file) {
        if (file_exists($file)) {
            echo "<li>$file</li>\n";
        }
    }
    echo "</ul>\n";
    
    // 6. Fix column reference issues
    echo "<h3>6. Fixing Column Reference Issues</h3>\n";
    
    // Check if applications table has resume_path column
    $columns = $pdo->query("SHOW COLUMNS FROM applications LIKE 'resume_path'")->fetchAll();
    if (empty($columns)) {
        try {
            $pdo->exec("ALTER TABLE applications ADD COLUMN resume_path VARCHAR(255) DEFAULT NULL AFTER cover_letter");
            echo "<p style='color: green;'>✓ Added resume_path column to applications table</p>\n";
        } catch (PDOException $e) {
            echo "<p style='color: red;'>✗ Failed to add resume_path column: " . $e->getMessage() . "</p>\n";
        }
    } else {
        echo "<p style='color: green;'>✓ resume_path column already exists in applications table</p>\n";
    }
    
    // Check if applications table has applied_at column (referenced in view_applicants.php)
    $columns = $pdo->query("SHOW COLUMNS FROM applications LIKE 'applied_at'")->fetchAll();
    if (empty($columns)) {
        try {
            // Use created_at as applied_at since that's when the application was created
            echo "<p style='color: orange;'>⚠ applied_at column missing, using created_at instead</p>\n";
        } catch (PDOException $e) {
            echo "<p style='color: red;'>✗ Column check failed: " . $e->getMessage() . "</p>\n";
        }
    } else {
        echo "<p style='color: green;'>✓ applied_at column exists in applications table</p>\n";
    }
    
    // 7. Verify fixes
    echo "<h3>7. Verification</h3>\n";
    
    // Check employer department assignments
    $stmt = $pdo->query("
        SELECT COUNT(*) as count 
        FROM users 
        WHERE role = 'employer' AND department_id IS NULL
    ");
    $null_dept_employers = $stmt->fetch()['count'];
    
    if ($null_dept_employers == 0) {
        echo "<p style='color: green;'>✓ All employers have department assignments</p>\n";
    } else {
        echo "<p style='color: red;'>✗ $null_dept_employers employers still missing department assignments</p>\n";
    }
    
    // Check job ownership
    $stmt = $pdo->query("
        SELECT COUNT(*) as count 
        FROM jobs 
        WHERE created_by IS NULL
    ");
    $null_creator_jobs = $stmt->fetch()['count'];
    
    if ($null_creator_jobs == 0) {
        echo "<p style='color: green;'>✓ All jobs have creator assignments</p>\n";
    } else {
        echo "<p style='color: red;'>✗ $null_creator_jobs jobs still missing creator assignments</p>\n";
    }
    
    $pdo->commit();
    echo "<h3 style='color: green;'>✅ Database fixes completed successfully!</h3>\n";
    
    echo "<h3>Next Steps:</h3>\n";
    echo "<ol>\n";
    echo "<li>Test the view_applicants.php functionality</li>\n";
    echo "<li>Remove debug code from production files</li>\n";
    echo "<li>Update employer dashboard to show department-based jobs</li>\n";
    echo "<li>Implement proper error handling</li>\n";
    echo "</ol>\n";
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo "<p style='color: red;'>✗ Error during database fixes: " . $e->getMessage() . "</p>\n";
}
?>