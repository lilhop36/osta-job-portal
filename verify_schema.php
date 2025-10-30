<?php
/**
 * Database Schema Verification Script
 * Checks for missing columns and schema inconsistencies
 */

require_once 'config/database.php';

echo "<h2>🔍 Database Schema Verification</h2>\n";

// Define expected schema
$expected_schema = [
    'applications' => [
        'id', 'user_id', 'job_id', 'cover_letter', 'resume_path', 'status', 'created_at', 'updated_at'
    ],
    'users' => [
        'id', 'username', 'email', 'password', 'role', 'status', 'department_id', 'full_name', 'phone', 'created_at'
    ],
    'jobs' => [
        'id', 'department_id', 'title', 'description', 'requirements', 'location', 'employment_type', 
        'salary_range', 'deadline', 'status', 'created_by', 'created_at', 'updated_at'
    ],
    'departments' => [
        'id', 'name', 'description', 'created_at'
    ]
];

$issues_found = 0;

foreach ($expected_schema as $table => $expected_columns) {
    echo "<h3>Checking table: $table</h3>\n";
    
    try {
        // Check if table exists
        $tables = $pdo->query("SHOW TABLES LIKE '$table'")->fetchAll();
        if (empty($tables)) {
            echo "<p style='color: red;'>❌ Table '$table' does not exist</p>\n";
            $issues_found++;
            continue;
        }
        
        // Get actual columns
        $stmt = $pdo->query("SHOW COLUMNS FROM $table");
        $actual_columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
        
        // Check for missing columns
        $missing_columns = array_diff($expected_columns, $actual_columns);
        $extra_columns = array_diff($actual_columns, $expected_columns);
        
        if (empty($missing_columns) && empty($extra_columns)) {
            echo "<p style='color: green;'>✅ Schema matches expected structure</p>\n";
        } else {
            if (!empty($missing_columns)) {
                echo "<p style='color: red;'>❌ Missing columns: " . implode(', ', $missing_columns) . "</p>\n";
                $issues_found++;
            }
            if (!empty($extra_columns)) {
                echo "<p style='color: orange;'>⚠ Extra columns: " . implode(', ', $extra_columns) . "</p>\n";
            }
        }
        
        // Show actual structure
        echo "<details><summary>View actual columns</summary>\n";
        echo "<ul>\n";
        foreach ($actual_columns as $column) {
            echo "<li>$column</li>\n";
        }
        echo "</ul>\n";
        echo "</details>\n";
        
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ Error checking table '$table': " . $e->getMessage() . "</p>\n";
        $issues_found++;
    }
}

// Check for common query issues
echo "<h3>🔍 Query Compatibility Check</h3>\n";

$test_queries = [
    'applications_basic' => "SELECT a.id, a.user_id, a.job_id, a.status, a.created_at FROM applications a LIMIT 1",
    'applications_with_resume' => "SELECT a.id, a.resume_path FROM applications a WHERE a.resume_path IS NOT NULL LIMIT 1",
    'users_basic' => "SELECT u.id, u.username, u.email, u.role, u.full_name FROM users u LIMIT 1",
    'jobs_with_department' => "SELECT j.id, j.title, j.department_id, d.name as dept_name FROM jobs j JOIN departments d ON j.department_id = d.id LIMIT 1"
];

foreach ($test_queries as $test_name => $query) {
    try {
        $stmt = $pdo->query($query);
        $result = $stmt->fetch();
        echo "<p style='color: green;'>✅ $test_name query works</p>\n";
    } catch (Exception $e) {
        echo "<p style='color: red;'>❌ $test_name query failed: " . $e->getMessage() . "</p>\n";
        $issues_found++;
    }
}

// Summary
echo "<h3>📊 Summary</h3>\n";
if ($issues_found == 0) {
    echo "<p style='color: green; font-weight: bold;'>🎉 No schema issues found!</p>\n";
} else {
    echo "<p style='color: red; font-weight: bold;'>⚠ Found $issues_found schema issues</p>\n";
    echo "<p>Run the database fix script to resolve these issues.</p>\n";
}

// Generate SQL fixes for common issues
echo "<h3>🔧 Suggested Fixes</h3>\n";
echo "<pre style='background: #f8f9fa; padding: 10px; border-radius: 5px;'>\n";
echo "-- Add missing resume_path column if needed\n";
echo "ALTER TABLE applications ADD COLUMN resume_path VARCHAR(255) DEFAULT NULL;\n\n";
echo "-- Add missing applied_at column if needed (or use created_at)\n";
echo "ALTER TABLE applications ADD COLUMN applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP;\n\n";
echo "-- Update existing records to set applied_at = created_at\n";
echo "UPDATE applications SET applied_at = created_at WHERE applied_at IS NULL;\n";
echo "</pre>\n";
?>