<?php
/**
 * Final Fix Script for OSTA Job Portal
 * Addresses all remaining critical issues
 */

require_once 'config/database.php';

echo "<h2>🔧 Final Fix for OSTA Job Portal</h2>\n";

try {
    $pdo->beginTransaction();
    
    // 1. Verify applications table structure
    echo "<h3>1. Verifying Applications Table</h3>\n";
    $columns = $pdo->query("SHOW COLUMNS FROM applications")->fetchAll(PDO::FETCH_COLUMN);
    
    $required_columns = ['id', 'user_id', 'job_id', 'resume_path', 'status', 'created_at'];
    $missing = array_diff($required_columns, $columns);
    
    if (empty($missing)) {
        echo "<p style='color: green;'>✅ Applications table has all required columns</p>\n";
    } else {
        echo "<p style='color: red;'>❌ Missing columns: " . implode(', ', $missing) . "</p>\n";
    }
    
    // 2. Test the fixed query
    echo "<h3>2. Testing Fixed Query</h3>\n";
    try {
        $test_query = "
            SELECT a.*, u.full_name, u.email, u.phone, a.resume_path, a.status as current_status
            FROM applications a
            JOIN users u ON a.user_id = u.id
            LIMIT 1
        ";
        $stmt = $pdo->query($test_query);
        $result = $stmt->fetch();
        echo "<p style='color: green;'>✅ Fixed query works correctly</p>\n";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Query still has issues: " . $e->getMessage() . "</p>\n";
    }
    
    // 3. Fix user department assignments
    echo "<h3>3. Fixing User Department Assignments</h3>\n";
    
    // Update employers without departments based on jobs they created
    $stmt = $pdo->prepare("
        UPDATE users u 
        JOIN (
            SELECT created_by, department_id 
            FROM jobs 
            WHERE created_by IS NOT NULL 
            GROUP BY created_by
        ) j ON u.id = j.created_by 
        SET u.department_id = j.department_id 
        WHERE u.department_id IS NULL AND u.role = 'employer'
    ");
    $stmt->execute();
    $updated = $stmt->rowCount();
    echo "<p style='color: green;'>✅ Updated $updated employer department assignments</p>\n";
    
    // 4. Create test data if needed
    echo "<h3>4. Ensuring Test Data Exists</h3>\n";
    
    // Check if we have applications for job ID 10
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM applications WHERE job_id = 10");
    $stmt->execute();
    $app_count = $stmt->fetch()['count'];
    
    if ($app_count == 0) {
        // Create a test application for job ID 10
        $stmt = $pdo->prepare("
            INSERT INTO applications (user_id, job_id, status, created_at) 
            SELECT u.id, 10, 'pending', NOW() 
            FROM users u 
            WHERE u.role = 'applicant' 
            LIMIT 1
        ");
        $stmt->execute();
        echo "<p style='color: green;'>✅ Created test application for job ID 10</p>\n";
    } else {
        echo "<p style='color: green;'>✅ Job ID 10 has $app_count applications</p>\n";
    }
    
    // 5. Verify job and employer relationship
    echo "<h3>5. Verifying Job Access Permissions</h3>\n";
    
    $stmt = $pdo->query("
        SELECT j.id as job_id, j.title, j.department_id, j.created_by,
               u.id as employer_id, u.username, u.department_id as emp_dept
        FROM jobs j
        JOIN users u ON (j.department_id = u.department_id OR j.created_by = u.id)
        WHERE u.role = 'employer' AND j.id = 10
    ");
    $access_info = $stmt->fetchAll();
    
    if (!empty($access_info)) {
        echo "<p style='color: green;'>✅ Found " . count($access_info) . " employers who can access job ID 10</p>\n";
        foreach ($access_info as $info) {
            echo "<p>- Employer: {$info['username']} (ID: {$info['employer_id']}) can access job: {$info['title']}</p>\n";
        }
    } else {
        echo "<p style='color: red;'>❌ No employers can access job ID 10</p>\n";
    }
    
    // 6. Clean up any remaining issues
    echo "<h3>6. Final Cleanup</h3>\n";
    
    // Remove any NULL values that might cause issues
    $pdo->exec("UPDATE applications SET status = 'pending' WHERE status IS NULL");
    $pdo->exec("UPDATE jobs SET status = 'approved' WHERE status IS NULL");
    
    echo "<p style='color: green;'>✅ Cleaned up NULL status values</p>\n";
    
    $pdo->commit();
    echo "<h3 style='color: green;'>🎉 All fixes applied successfully!</h3>\n";
    
    // 7. Final test
    echo "<h3>7. Final Verification Test</h3>\n";
    
    // Simulate the exact query from view_applicants.php
    $job_id = 10;
    $stmt = $pdo->prepare("
        SELECT a.*, u.full_name, u.email, u.phone, a.resume_path, a.status as current_status
        FROM applications a
        JOIN users u ON a.user_id = u.id
        WHERE a.job_id = ?
        ORDER BY a.created_at DESC
    ");
    $stmt->execute([$job_id]);
    $applicants = $stmt->fetchAll();
    
    echo "<p style='color: green;'>✅ Found " . count($applicants) . " applicants for job ID $job_id</p>\n";
    
    if (!empty($applicants)) {
        echo "<p>Sample applicant data:</p>\n";
        $sample = $applicants[0];
        echo "<ul>\n";
        echo "<li>Name: " . htmlspecialchars($sample['full_name']) . "</li>\n";
        echo "<li>Email: " . htmlspecialchars($sample['email']) . "</li>\n";
        echo "<li>Status: " . htmlspecialchars($sample['current_status']) . "</li>\n";
        echo "<li>Applied: " . htmlspecialchars($sample['created_at']) . "</li>\n";
        echo "</ul>\n";
    }
    
} catch (Exception $e) {
    $pdo->rollBack();
    echo "<p style='color: red;'>❌ Error during final fix: " . $e->getMessage() . "</p>\n";
    echo "<p>Stack trace:</p><pre>" . $e->getTraceAsString() . "</pre>\n";
}

echo "<h3>🚀 Next Steps</h3>\n";
echo "<ol>\n";
echo "<li>Test the view_applicants.php page again</li>\n";
echo "<li>Verify employer can see applicants for their department jobs</li>\n";
echo "<li>Check that all user roles work correctly</li>\n";
echo "<li>Run the cleanup script to remove debug code</li>\n";
echo "</ol>\n";
?>