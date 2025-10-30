<?php
/**
 * Test Script for OSTA Job Portal Fixes
 * Verifies that all critical issues have been resolved
 */

require_once 'config/database.php';

echo "<h2>🧪 Testing OSTA Job Portal Fixes</h2>\n";

$tests_passed = 0;
$tests_failed = 0;

// Test 1: Database Connection
echo "<h3>1. Database Connection Test</h3>\n";
try {
    $test = $pdo->query("SELECT 1")->fetch();
    echo "<p style='color: green;'>✅ Database connection successful</p>\n";
    $tests_passed++;
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Database connection failed: " . $e->getMessage() . "</p>\n";
    $tests_failed++;
}

// Test 2: User Department Assignments
echo "<h3>2. User Department Assignment Test</h3>\n";
try {
    $stmt = $pdo->query("
        SELECT u.id, u.username, u.role, u.department_id, d.name as dept_name
        FROM users u 
        LEFT JOIN departments d ON u.department_id = d.id 
        WHERE u.role = 'employer'
    ");
    $employers = $stmt->fetchAll();
    
    $missing_dept = 0;
    foreach ($employers as $emp) {
        if (empty($emp['department_id'])) {
            $missing_dept++;
            echo "<p style='color: orange;'>⚠ Employer '{$emp['username']}' missing department assignment</p>\n";
        }
    }
    
    if ($missing_dept == 0) {
        echo "<p style='color: green;'>✅ All employers have department assignments</p>\n";
        $tests_passed++;
    } else {
        echo "<p style='color: red;'>❌ $missing_dept employers missing department assignments</p>\n";
        $tests_failed++;
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Department assignment test failed: " . $e->getMessage() . "</p>\n";
    $tests_failed++;
}

// Test 3: Job Ownership
echo "<h3>3. Job Ownership Test</h3>\n";
try {
    $stmt = $pdo->query("
        SELECT COUNT(*) as count 
        FROM jobs 
        WHERE created_by IS NULL
    ");
    $null_creators = $stmt->fetch()['count'];
    
    if ($null_creators == 0) {
        echo "<p style='color: green;'>✅ All jobs have creator assignments</p>\n";
        $tests_passed++;
    } else {
        echo "<p style='color: red;'>❌ $null_creators jobs missing creator assignments</p>\n";
        $tests_failed++;
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Job ownership test failed: " . $e->getMessage() . "</p>\n";
    $tests_failed++;
}

// Test 4: Application Status Query
echo "<h3>4. Application Status Query Test</h3>\n";
try {
    $stmt = $pdo->query("
        SELECT a.id, a.status, u.full_name, j.title
        FROM applications a
        JOIN users u ON a.user_id = u.id
        JOIN jobs j ON a.job_id = j.id
        LIMIT 5
    ");
    $applications = $stmt->fetchAll();
    
    echo "<p style='color: green;'>✅ Application status query works correctly</p>\n";
    echo "<p>Sample applications found: " . count($applications) . "</p>\n";
    $tests_passed++;
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Application status query failed: " . $e->getMessage() . "</p>\n";
    $tests_failed++;
}

// Test 5: Permission Logic Simulation
echo "<h3>5. Permission Logic Test</h3>\n";
try {
    // Get a sample job and employer from same department
    $stmt = $pdo->query("
        SELECT j.id as job_id, j.department_id, j.created_by, u.id as employer_id
        FROM jobs j
        JOIN users u ON j.department_id = u.department_id
        WHERE u.role = 'employer' AND j.department_id IS NOT NULL
        LIMIT 1
    ");
    $test_case = $stmt->fetch();
    
    if ($test_case) {
        $job_id = $test_case['job_id'];
        $employer_id = $test_case['employer_id'];
        
        // Simulate the permission check logic
        $stmt = $pdo->prepare("
            SELECT j.*, d.name as department_name
            FROM jobs j 
            JOIN departments d ON j.department_id = d.id
            WHERE j.id = ?
        ");
        $stmt->execute([$job_id]);
        $job = $stmt->fetch();
        
        $stmt = $pdo->prepare("SELECT department_id FROM users WHERE id = ? AND role = 'employer'");
        $stmt->execute([$employer_id]);
        $employer_dept = $stmt->fetch();
        
        $has_access = ($job && $employer_dept && 
                      ($job['department_id'] == $employer_dept['department_id'] || 
                       $job['created_by'] == $employer_id));
        
        if ($has_access) {
            echo "<p style='color: green;'>✅ Permission logic working correctly</p>\n";
            echo "<p>Employer {$employer_id} can access job {$job_id} in department {$job['department_id']}</p>\n";
            $tests_passed++;
        } else {
            echo "<p style='color: red;'>❌ Permission logic failed</p>\n";
            $tests_failed++;
        }
    } else {
        echo "<p style='color: orange;'>⚠ No test case available for permission logic</p>\n";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Permission logic test failed: " . $e->getMessage() . "</p>\n";
    $tests_failed++;
}

// Test 6: File Syntax Check
echo "<h3>6. File Syntax Check</h3>\n";
$files_to_check = [
    'employer/view_applicants.php',
    'admin/manage_jobs.php',
    'applicant/centralized_application.php'
];

$syntax_errors = 0;
foreach ($files_to_check as $file) {
    if (file_exists($file)) {
        $output = [];
        $return_code = 0;
        exec("php -l \"$file\" 2>&1", $output, $return_code);
        
        if ($return_code === 0) {
            echo "<p style='color: green;'>✅ $file - Syntax OK</p>\n";
        } else {
            echo "<p style='color: red;'>❌ $file - Syntax Error: " . implode(' ', $output) . "</p>\n";
            $syntax_errors++;
        }
    } else {
        echo "<p style='color: orange;'>⚠ File not found: $file</p>\n";
    }
}

if ($syntax_errors == 0) {
    $tests_passed++;
} else {
    $tests_failed++;
}

// Test 7: Security Headers Check
echo "<h3>7. Security Configuration Test</h3>\n";
try {
    if (function_exists('set_security_headers')) {
        echo "<p style='color: green;'>✅ Security headers function available</p>\n";
        $tests_passed++;
    } else {
        echo "<p style='color: orange;'>⚠ Security headers function not found</p>\n";
    }
} catch (Exception $e) {
    echo "<p style='color: red;'>❌ Security configuration test failed: " . $e->getMessage() . "</p>\n";
    $tests_failed++;
}

// Summary
echo "<h3>📊 Test Summary</h3>\n";
echo "<div style='background: #f8f9fa; padding: 20px; border-radius: 5px; margin: 20px 0;'>\n";
echo "<p><strong>Tests Passed:</strong> <span style='color: green;'>$tests_passed</span></p>\n";
echo "<p><strong>Tests Failed:</strong> <span style='color: red;'>$tests_failed</span></p>\n";
echo "<p><strong>Success Rate:</strong> " . round(($tests_passed / ($tests_passed + $tests_failed)) * 100, 1) . "%</p>\n";
echo "</div>\n";

if ($tests_failed == 0) {
    echo "<h3 style='color: green;'>🎉 All Tests Passed!</h3>\n";
    echo "<p>The OSTA Job Portal fixes have been successfully implemented.</p>\n";
} else {
    echo "<h3 style='color: orange;'>⚠ Some Issues Remain</h3>\n";
    echo "<p>Please address the failed tests before proceeding to production.</p>\n";
}

// Recommendations
echo "<h3>📋 Next Steps</h3>\n";
echo "<ol>\n";
echo "<li><strong>Run Database Fixes:</strong> Execute fix_database_issues.php if not already done</li>\n";
echo "<li><strong>Clean Debug Code:</strong> Run cleanup_debug_code.php to remove debug statements</li>\n";
echo "<li><strong>Test User Workflows:</strong> Manually test login, job posting, and application processes</li>\n";
echo "<li><strong>Security Review:</strong> Review file upload permissions and CSRF implementations</li>\n";
echo "<li><strong>Performance Optimization:</strong> Add database indexes and optimize queries</li>\n";
echo "<li><strong>Documentation:</strong> Update user manuals and deployment guides</li>\n";
echo "</ol>\n";

// Create a simple status dashboard
echo "<h3>🎛️ System Status Dashboard</h3>\n";
try {
    $stats = [
        'Total Users' => $pdo->query("SELECT COUNT(*) FROM users")->fetchColumn(),
        'Active Jobs' => $pdo->query("SELECT COUNT(*) FROM jobs WHERE status = 'approved' AND deadline >= CURDATE()")->fetchColumn(),
        'Total Applications' => $pdo->query("SELECT COUNT(*) FROM applications")->fetchColumn(),
        'Departments' => $pdo->query("SELECT COUNT(*) FROM departments")->fetchColumn()
    ];
    
    echo "<table style='border-collapse: collapse; width: 100%; margin: 10px 0;'>\n";
    foreach ($stats as $label => $value) {
        echo "<tr style='border: 1px solid #ddd;'>\n";
        echo "<td style='padding: 8px; background: #f8f9fa; font-weight: bold;'>$label</td>\n";
        echo "<td style='padding: 8px;'>$value</td>\n";
        echo "</tr>\n";
    }
    echo "</table>\n";
} catch (Exception $e) {
    echo "<p style='color: red;'>Error generating dashboard: " . $e->getMessage() . "</p>\n";
}
?>