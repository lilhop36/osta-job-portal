<?php
require_once '../config/database.php';

try {
    echo "Fixing application numbers...\n";
    
    // Find applications with empty or duplicate application numbers
    $stmt = $pdo->query("SELECT id, user_id, application_number, created_at FROM centralized_applications ORDER BY id");
    $applications = $stmt->fetchAll();
    
    $fixed_count = 0;
    $used_numbers = [];
    
    foreach ($applications as $app) {
        $needs_fix = false;
        
        // Check if application number is empty or already used
        if (empty($app['application_number']) || in_array($app['application_number'], $used_numbers)) {
            $needs_fix = true;
        }
        
        if ($needs_fix) {
            // Generate new unique application number
            $year = date('Y', strtotime($app['created_at']));
            $counter = 1;
            
            do {
                $new_number = sprintf("OSTA-%s-%03d", $year, $counter);
                $counter++;
            } while (in_array($new_number, $used_numbers));
            
            // Update the application
            $update_stmt = $pdo->prepare("UPDATE centralized_applications SET application_number = ? WHERE id = ?");
            $update_stmt->execute([$new_number, $app['id']]);
            
            echo "Fixed application ID {$app['id']}: '{$app['application_number']}' -> '{$new_number}'\n";
            $used_numbers[] = $new_number;
            $fixed_count++;
        } else {
            $used_numbers[] = $app['application_number'];
        }
    }
    
    echo "Fixed {$fixed_count} application numbers.\n";
    echo "All application numbers are now unique and valid.\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
