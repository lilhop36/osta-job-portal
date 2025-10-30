<?php
// Database Migration Script for OSTA Job Portal Enhancements
require_once 'config/database.php';

try {
    echo "Starting database migration...\n";
    
    // Read the SQL file
    $sql_file = __DIR__ . '/database/osta_enhancements_v2.sql';
    
    if (!file_exists($sql_file)) {
        throw new Exception("SQL file not found: " . $sql_file);
    }
    
    $sql_content = file_get_contents($sql_file);
    
    if ($sql_content === false) {
        throw new Exception("Failed to read SQL file");
    }
    
    // Split SQL commands by semicolon (basic approach)
    $sql_commands = explode(';', $sql_content);
    
    $executed = 0;
    $errors = 0;
    
    foreach ($sql_commands as $command) {
        $command = trim($command);
        
        // Skip empty commands and comments
        if (empty($command) || strpos($command, '--') === 0 || strpos($command, '/*') === 0) {
            continue;
        }
        
        // Skip DELIMITER commands (MySQL specific)
        if (strpos($command, 'DELIMITER') === 0) {
            continue;
        }
        
        try {
            $pdo->exec($command);
            $executed++;
            echo "✓ Executed command " . substr($command, 0, 50) . "...\n";
        } catch (PDOException $e) {
            // Check if it's a "table already exists" error - we can ignore these
            if (strpos($e->getMessage(), 'already exists') !== false) {
                echo "⚠ Skipped (already exists): " . substr($command, 0, 50) . "...\n";
            } else {
                echo "✗ Error: " . $e->getMessage() . "\n";
                echo "Command: " . substr($command, 0, 100) . "...\n";
                $errors++;
            }
        }
    }
    
    echo "\nMigration completed!\n";
    echo "Commands executed: $executed\n";
    echo "Errors: $errors\n";
    
    // Test if key tables were created
    echo "\nVerifying tables...\n";
    $tables_to_check = [
        'centralized_applications',
        'application_documents',
        'eligibility_criteria',
        'application_eligibility_checks',
        'vacancy_requests',
        'exams',
        'interviews',
        'notification_templates'
    ];
    
    foreach ($tables_to_check as $table) {
        try {
            $stmt = $pdo->query("SELECT COUNT(*) FROM $table");
            echo "✓ Table '$table' exists\n";
        } catch (PDOException $e) {
            echo "✗ Table '$table' missing\n";
        }
    }
    
} catch (Exception $e) {
    echo "Migration failed: " . $e->getMessage() . "\n";
}
?>
