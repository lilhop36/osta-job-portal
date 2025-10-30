<?php
/**
 * Quick test for view_applicants.php functionality
 */

require_once 'config/database.php';

echo "<h2>🧪 Testing View Applicants Functionality</h2>\n";

// Test 1: Check if we have jobs and applications
echo "<h3>1. Database Content Check</h3>\n";

try {
    // Check jobs
    $jobs = $pdo->query("SELECT id, title, department_id, created_by FROM jobs WHERE status = 'approved' ORDER BY id")->fetchAll();
    echo "<p><strong>Available Jobs:</strong></p>\n";
    echo "<ul>\n";
    foreach ($jobs as $job) {
        echo "<li>Job ID {$job['id']}: {$job['title']} (Dept: {$job['department_id']}, Creator: {$job['created_by']})</li>\n";
    }
    echo "</ul>\n";
    
    // Check applications
    $apps = $pdo->query("SELECT job_id, COUNT(*) as count FROM applications GROUP BY job_id")->fetchAll();
    echo "<p><strong>Applications by Job:</strong></p>\n";
    echo "<ul>\n";
    foreach ($apps as $app) {
        echo "<li>Job ID {$app['job_id']}: {$app['count']} applications</li>\n";
    }
    echo "</ul>\n";
    
    // Check employers
    $employers = $pdo->query("SELECT id, username, department_id FROM users WHERE role = 'employer'")->fetchAll();
    echo "<p><strong>Employers:</strong></p>\n";
    echo "<ul>\n";
    foreach ($employers as $emp) {
        echo "<li>Employer ID {$emp['id']}: {$emp['username']} (Dept: {$emp['department_id']})</li>\n";
    }
    echo "</ul>\n";
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>\n";
}

// Test 2: Test the permission logic
echo "<h3>2. Permission Logic Test</h3>\n";

try {
    // Find a job and an employer in the same department
    $test_case = $pdo->query("
        SELECT j.id as job_id, j.title, j.department_id, u.id as employer_id, u.username
        FROM jobs j
        JOIN users u ON j.department_id = u.department_id
        WHERE u.role = 'employer' AND j.status = 'approved'
        LIMIT 1
    ")->fetch();
    
    if ($test_case) {
        echo "<p><strong>Test Case:</strong></p>\n";
        echo "<ul>\n";
        echo "<li>Job: {$test_case['title']} (ID: {$test_case['job_id']})</li>\n";
        echo "<li>Department: {$test_case['department_id']}</li>\n";
        echo "<li>Employer: {$test_case['username']} (ID: {$test_case['employer_id']})</li>\n";
        echo "</ul>\n";
        
        echo "<p><strong>Test URLs:</strong></p>\n";
        echo "<ul>\n";
        echo "<li><a href='employer/view_applicants.php?job_id={$test_case['job_id']}' target='_blank'>View Applicants for Job {$test_case['job_id']}</a></li>\n";
        echo "<li><a href='employer/view_applicants.php' target='_blank'>View Applicants (no job_id)</a></li>\n";
        echo "<li><a href='employer/manage_jobs.php' target='_blank'>Manage Jobs</a></li>\n";
        echo "</ul>\n";
        
    } else {
        echo "<p style='color: orange;'>No test case found - no employer/job in same department</p>\n";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error: " . $e->getMessage() . "</p>\n";
}

// Test 3: Create test data if needed
echo "<h3>3. Test Data Creation</h3>\n";

try {
    // Check if job 10 has applications
    $stmt = $pdo->prepare("SELECT COUNT(*) as count FROM applications WHERE job_id = 10");
    $stmt->execute();
    $app_count = $stmt->fetch()['count'];
    
    if ($app_count == 0) {
        // Create a test application
        $applicant = $pdo->query("SELECT id FROM users WHERE role = 'applicant' LIMIT 1")->fetch();
        if ($applicant) {
            $stmt = $pdo->prepare("INSERT INTO applications (user_id, job_id, status, created_at) VALUES (?, 10, 'pending', NOW())");
            $stmt->execute([$applicant['id']]);
            echo "<p style='color: green;'>✅ Created test application for job 10</p>\n";
        }
    } else {
        echo "<p style='color: green;'>✅ Job 10 already has $app_count applications</p>\n";
    }
    
} catch (Exception $e) {
    echo "<p style='color: red;'>Error creating test data: " . $e->getMessage() . "</p>\n";
}

echo "<h3>🎯 Next Steps</h3>\n";
echo "<ol>\n";
echo "<li>Login as an employer</li>\n";
echo "<li>Go to 'Manage Jobs' from the employer dashboard</li>\n";
echo "<li>Click the 'View Applicants' button (users icon) for any job</li>\n";
echo "<li>Or use the direct links above</li>\n";
echo "</ol>\n";
?>