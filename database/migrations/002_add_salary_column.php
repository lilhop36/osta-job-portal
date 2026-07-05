<?php
/**
 * Migration to add salary column to jobs table
 */

require_once __DIR__ . '/../../config/database.php';

function run_migration() {
    $pdo = getDBConnection();
    
    try {
        // Start transaction
        $pdo->beginTransaction();
        
        // Check if salary column already exists
        $check = $pdo->query("SHOW COLUMNS FROM jobs LIKE 'salary'");
        if ($check->rowCount() == 0) {
            // Add salary column
            $pdo->exec("ALTER TABLE jobs 
                        ADD COLUMN salary VARCHAR(100) NULL DEFAULT NULL AFTER location,
                        COMMENT 'Salary range or amount for the job'");
            
            echo "Successfully added 'salary' column to jobs table.\n";
        } else {
            echo "'salary' column already exists in jobs table.\n";
        }
        
        // Commit transaction
        $pdo->commit();
        return true;
        
    } catch (PDOException $e) {
        // Rollback transaction on error
        if ($pdo->inTransaction()) {
            $pdo->rollBack();
        }
        
        echo "Migration failed: " . $e->getMessage() . "\n";
        return false;
    }
}

// Run the migration
run_migration();
?>
