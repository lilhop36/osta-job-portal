<?php
require_once 'config/database.php';

echo "<h1>Creating Sessions Table</h1>";

try {
    // Create sessions table for session management
    $sql = "CREATE TABLE IF NOT EXISTS sessions (
        id VARCHAR(128) NOT NULL PRIMARY KEY,
        user_id INT(11) DEFAULT NULL,
        ip_address VARCHAR(45) DEFAULT NULL,
        user_agent TEXT DEFAULT NULL,
        payload LONGTEXT NOT NULL,
        last_activity INT(11) NOT NULL,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
        INDEX idx_user_id (user_id),
        INDEX idx_last_activity (last_activity),
        FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
    ) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci";
    
    $pdo->exec($sql);
    echo "<p style='color: green; font-weight: bold;'>✅ Sessions table created successfully!</p>";
    
    // Check if table exists
    $stmt = $pdo->query("SHOW TABLES LIKE 'sessions'");
    if ($stmt->fetch()) {
        echo "<p style='color: green;'>✅ Sessions table verified to exist</p>";
        
        // Show table structure
        $stmt = $pdo->query("DESCRIBE sessions");
        echo "<h3>Sessions Table Structure:</h3>";
        echo "<table border='1' style='border-collapse: collapse;'>";
        echo "<tr style='background-color: #f0f0f0;'><th>Field</th><th>Type</th><th>Null</th><th>Key</th><th>Default</th><th>Extra</th></tr>";
        while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
            echo "<tr>";
            echo "<td>" . $row['Field'] . "</td>";
            echo "<td>" . $row['Type'] . "</td>";
            echo "<td>" . $row['Null'] . "</td>";
            echo "<td>" . $row['Key'] . "</td>";
            echo "<td>" . ($row['Default'] ?? 'NULL') . "</td>";
            echo "<td>" . $row['Extra'] . "</td>";
            echo "</tr>";
        }
        echo "</table>";
    } else {
        echo "<p style='color: red;'>❌ Sessions table creation failed</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red; font-weight: bold;'>❌ Error creating sessions table: " . $e->getMessage() . "</p>";
}

echo "<h2>Done</h2>";
echo "<p><a href='admin/manage_interviews.php'>← Back to Interview Management</a></p>";
?>
