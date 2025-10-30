<?php
/**
 * Database Export Script for OSTA Job Portal Deployment
 * 
 * This script will export your database structure and essential data
 * Run this script ONCE before deployment to create the SQL file
 */

require_once 'config/database.php';

// Set content type for download
header('Content-Type: application/sql');
header('Content-Disposition: attachment; filename="osta_job_portal_export_' . date('Y-m-d_H-i-s') . '.sql"');

echo "-- OSTA Job Portal Database Export\n";
echo "-- Generated on: " . date('Y-m-d H:i:s') . "\n";
echo "-- \n\n";

echo "SET SQL_MODE = \"NO_AUTO_VALUE_ON_ZERO\";\n";
echo "SET AUTOCOMMIT = 0;\n";
echo "START TRANSACTION;\n";
echo "SET time_zone = \"+00:00\";\n\n";

// Tables to export (structure + data)
$tables_with_data = [
    'departments',
    'users', 
    'jobs',
    'applications',
    'system_logs'
];

// Tables to export (structure only)
$tables_structure_only = [
    'application_audit_log',
    'audit_log',
    'sessions'
];

try {
    // Get all tables
    $tables = $pdo->query("SHOW TABLES")->fetchAll(PDO::FETCH_COLUMN);
    
    foreach ($tables as $table) {
        // Export table structure
        echo "-- \n";
        echo "-- Table structure for table `$table`\n";
        echo "-- \n\n";
        
        $create_table = $pdo->query("SHOW CREATE TABLE `$table`")->fetch();
        echo "DROP TABLE IF EXISTS `$table`;\n";
        echo $create_table['Create Table'] . ";\n\n";
        
        // Export data for specific tables
        if (in_array($table, $tables_with_data)) {
            echo "-- \n";
            echo "-- Dumping data for table `$table`\n";
            echo "-- \n\n";
            
            $rows = $pdo->query("SELECT * FROM `$table`")->fetchAll();
            
            if (!empty($rows)) {
                // Get column names
                $columns = array_keys($rows[0]);
                $column_list = '`' . implode('`, `', $columns) . '`';
                
                echo "INSERT INTO `$table` ($column_list) VALUES\n";
                
                $values = [];
                foreach ($rows as $row) {
                    $escaped_values = [];
                    foreach ($row as $value) {
                        if ($value === null) {
                            $escaped_values[] = 'NULL';
                        } else {
                            $escaped_values[] = "'" . addslashes($value) . "'";
                        }
                    }
                    $values[] = '(' . implode(', ', $escaped_values) . ')';
                }
                
                echo implode(",\n", $values) . ";\n\n";
            }
        }
    }
    
    echo "COMMIT;\n";
    echo "-- Export completed successfully\n";
    
} catch (Exception $e) {
    echo "-- ERROR: " . $e->getMessage() . "\n";
}
?>
