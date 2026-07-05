<?php
/**
 * Migration to add application_count column to jobs table
 */

require_once __DIR__ . '/../../config/database.php';

try {
    // Check if the column already exists
    $stmt = $pdo->query("SHOW COLUMNS FROM jobs LIKE 'application_count'");
    $columnExists = $stmt->rowCount() > 0;
    
    if (!$columnExists) {
        // Add the column if it doesn't exist
        $pdo->exec("ALTER TABLE jobs ADD COLUMN application_count INT UNSIGNED NOT NULL DEFAULT 0");
        echo "Successfully added application_count column to jobs table.\n";
    } else {
        echo "application_count column already exists in jobs table.\n";
    }
    
    // Verify the table structure
    $stmt = $pdo->query("DESCRIBE jobs");
    $columns = $stmt->fetchAll(PDO::FETCH_COLUMN);
    echo "Current columns in jobs table: " . implode(', ', $columns) . "\n";
    
} catch (PDOException $e) {
    die("Migration failed: " . $e->getMessage() . "\n");
}

echo "Migration completed successfully.\n";
