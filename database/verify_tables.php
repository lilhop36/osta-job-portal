<?php
/**
 * Script to verify and fix database structure
 */

require_once __DIR__ . '/../config/database.php';

// Enable error reporting
error_reporting(E_ALL);
ini_set('display_errors', 1);

// Check if jobs table has all required columns
function verifyJobsTable($pdo) {
    echo "Verifying jobs table...\n";
    
    $requiredColumns = [
        'id', 'title', 'description', 'requirements', 'department_id',
        'location', 'job_type', 'salary_range', 'status', 'deadline',
        'application_count', 'created_at', 'updated_at'
    ];
    
    try {
        // Check if table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'jobs'");
        if ($stmt->rowCount() === 0) {
            die("Error: Jobs table does not exist. Please run your database migrations.\n");
        }
        
        // Get existing columns
        $stmt = $pdo->query("DESCRIBE jobs");
        $existingColumns = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $existingColumns[] = $row['Field'];
        }
        
        echo "Existing columns: " . implode(', ', $existingColumns) . "\n";
        
        // Check for missing columns
        $missingColumns = array_diff($requiredColumns, $existingColumns);
        
        if (!empty($missingColumns)) {
            echo "Missing columns: " . implode(', ', $missingColumns) . "\n";
            
            // Add missing application_count if needed
            if (in_array('application_count', $missingColumns)) {
                echo "Adding application_count column...\n";
                $pdo->exec("ALTER TABLE jobs ADD COLUMN application_count INT UNSIGNED NOT NULL DEFAULT 0");
                echo "Added application_count column.\n";
            }
            
            // Add other missing columns if needed
            // ...
        } else {
            echo "All required columns exist in jobs table.\n";
        }
        
        return true;
        
    } catch (PDOException $e) {
        die("Error verifying jobs table: " . $e->getMessage() . "\n");
    }
}

// Check if applications table has all required columns
function verifyApplicationsTable($pdo) {
    echo "\nVerifying applications table...\n";
    
    $requiredColumns = [
        'id', 'job_id', 'user_id', 'cover_letter', 'resume_path',
        'status', 'created_at', 'updated_at'
    ];
    
    try {
        // Check if table exists
        $stmt = $pdo->query("SHOW TABLES LIKE 'applications'");
        if ($stmt->rowCount() === 0) {
            die("Error: Applications table does not exist. Please run your database migrations.\n");
        }
        
        // Get existing columns
        $stmt = $pdo->query("DESCRIBE applications");
        $existingColumns = [];
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            $existingColumns[] = $row['Field'];
        }
        
        echo "Existing columns: " . implode(', ', $existingColumns) . "\n";
        
        // Check for missing columns
        $missingColumns = array_diff($requiredColumns, $existingColumns);
        
        if (!empty($missingColumns)) {
            echo "Missing columns: " . implode(', ', $missingColumns) . "\n";
            // Add code to add missing columns if needed
        } else {
            echo "All required columns exist in applications table.\n";
        }
        
        return true;
        
    } catch (PDOException $e) {
        die("Error verifying applications table: " . $e->getMessage() . "\n");
    }
}

// Main execution
try {
    echo "=== Database Structure Verification ===\n";
    
    // Verify tables
    verifyJobsTable($pdo);
    verifyApplicationsTable($pdo);
    
    echo "\nVerification complete.\n";
    
} catch (Exception $e) {
    die("Error: " . $e->getMessage() . "\n");
}
