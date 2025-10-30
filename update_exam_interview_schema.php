<?php
require_once 'config/database.php';

echo "Updating exam and interview schema...\n";

try {
    // Update exams table
    $sql = "ALTER TABLE exams 
        ADD COLUMN IF NOT EXISTS exam_code VARCHAR(20) UNIQUE NOT NULL,
        ADD COLUMN IF NOT EXISTS exam_type ENUM('written', 'practical', 'online', 'oral') NOT NULL DEFAULT 'written',
        ADD COLUMN IF NOT EXISTS department_id INT NULL,
        ADD COLUMN IF NOT EXISTS total_marks INT NOT NULL DEFAULT 100,
        ADD COLUMN IF NOT EXISTS passing_marks INT NOT NULL DEFAULT 50,
        ADD COLUMN IF NOT EXISTS max_candidates INT NULL,
        ADD FOREIGN KEY IF NOT EXISTS (department_id) REFERENCES departments(id) ON DELETE SET NULL";
    
    $pdo->exec($sql);
    echo "Exams table updated successfully.\n";
    
    // Drop and recreate exam_registrations table
    $sql = "DROP TABLE IF EXISTS exam_registrations";
    $pdo->exec($sql);
    
    $sql = "CREATE TABLE exam_registrations (
        id INT PRIMARY KEY AUTO_INCREMENT,
        exam_id INT NOT NULL,
        application_id INT NOT NULL,
        registration_number VARCHAR(20) UNIQUE NOT NULL,
        seat_number VARCHAR(10),
        marks_obtained INT NULL,
        result ENUM('pass', 'fail', 'absent', 'disqualified') NULL,
        result_notes TEXT,
        status ENUM('registered', 'confirmed', 'appeared', 'completed') DEFAULT 'registered',
        registered_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (exam_id) REFERENCES exams(id) ON DELETE CASCADE,
        FOREIGN KEY (application_id) REFERENCES centralized_applications(id) ON DELETE CASCADE,
        UNIQUE KEY unique_exam_application (exam_id, application_id),
        INDEX idx_exam_registrations (exam_id, status),
        INDEX idx_registration_number (registration_number)
    )";
    
    $pdo->exec($sql);
    echo "Exam registrations table recreated successfully.\n";
    
    // Update interviews table
    $sql = "ALTER TABLE interviews
        ADD COLUMN IF NOT EXISTS interview_code VARCHAR(20) UNIQUE NOT NULL,
        ADD COLUMN IF NOT EXISTS interview_type ENUM('phone', 'video', 'in_person', 'panel') NOT NULL DEFAULT 'in_person',
        ADD COLUMN IF NOT EXISTS duration_minutes INT DEFAULT 60,
        ADD COLUMN IF NOT EXISTS meeting_link VARCHAR(500),
        ADD COLUMN IF NOT EXISTS primary_interviewer_id INT NOT NULL,
        ADD COLUMN IF NOT EXISTS overall_rating ENUM('excellent', 'good', 'average', 'below_average', 'poor') NULL,
        ADD COLUMN IF NOT EXISTS recommendation ENUM('strongly_recommend', 'recommend', 'neutral', 'not_recommend', 'strongly_not_recommend') NULL,
        ADD FOREIGN KEY IF NOT EXISTS (primary_interviewer_id) REFERENCES users(id)";
    
    $pdo->exec($sql);
    echo "Interviews table updated successfully.\n";
    
    // Create interview panel members table
    $sql = "CREATE TABLE IF NOT EXISTS interview_panel_members (
        id INT AUTO_INCREMENT PRIMARY KEY,
        interview_id INT NOT NULL,
        user_id INT NOT NULL,
        role ENUM('chairperson', 'member', 'observer') DEFAULT 'member',
        assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (interview_id) REFERENCES interviews(id) ON DELETE CASCADE,
        FOREIGN KEY (user_id) REFERENCES users(id),
        UNIQUE KEY unique_interview_member (interview_id, user_id)
    )";
    
    $pdo->exec($sql);
    echo "Interview panel members table created successfully.\n";
    
    // Create interview criteria table
    $sql = "CREATE TABLE IF NOT EXISTS interview_criteria (
        id INT AUTO_INCREMENT PRIMARY KEY,
        name VARCHAR(100) NOT NULL,
        description TEXT,
        max_score DECIMAL(5,2) DEFAULT 10.00,
        category VARCHAR(50),
        is_active BOOLEAN DEFAULT TRUE,
        created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
    )";
    
    $pdo->exec($sql);
    echo "Interview criteria table created successfully.\n";
    
    // Insert default interview criteria
    $sql = "INSERT IGNORE INTO interview_criteria (id, name, description, max_score, category) VALUES 
        (1, 'Technical Knowledge', 'Assessment of technical skills and knowledge', 20.00, 'Technical'),
        (2, 'Problem Solving', 'Ability to analyze and solve problems', 15.00, 'Technical'),
        (3, 'Communication Skills', 'Clarity and effectiveness of communication', 15.00, 'Behavioral'),
        (4, 'Teamwork', 'Ability to work effectively in a team', 10.00, 'Behavioral'),
        (5, 'Leadership', 'Leadership and initiative-taking abilities', 15.00, 'Behavioral'),
        (6, 'Adaptability', 'Flexibility and ability to adapt to change', 10.00, 'Behavioral'),
        (7, 'Cultural Fit', 'Alignment with organizational values and culture', 15.00, 'Behavioral')";
    
    $pdo->exec($sql);
    echo "Default interview criteria inserted successfully.\n";
    
    // Create interview evaluations table
    $sql = "CREATE TABLE IF NOT EXISTS interview_evaluations (
        id INT AUTO_INCREMENT PRIMARY KEY,
        interview_id INT NOT NULL,
        criteria_id INT NOT NULL,
        score DECIMAL(5,2),
        remarks TEXT,
        evaluated_by INT,
        evaluated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
        FOREIGN KEY (interview_id) REFERENCES interviews(id) ON DELETE CASCADE,
        FOREIGN KEY (criteria_id) REFERENCES interview_criteria(id),
        FOREIGN KEY (evaluated_by) REFERENCES users(id),
        UNIQUE KEY unique_interview_criteria (interview_id, criteria_id)
    )";
    
    $pdo->exec($sql);
    echo "Interview evaluations table created successfully.\n";
    
    // Add indexes
    $sql = "CREATE INDEX IF NOT EXISTS idx_exams_date ON exams(exam_date)";
    $pdo->exec($sql);
    
    $sql = "CREATE INDEX IF NOT EXISTS idx_exam_registrations_status ON exam_registrations(status)";
    $pdo->exec($sql);
    
    $sql = "CREATE INDEX IF NOT EXISTS idx_interviews_date ON interviews(scheduled_date)";
    $pdo->exec($sql);
    
    $sql = "CREATE INDEX IF NOT EXISTS idx_interviews_status ON interviews(status)";
    $pdo->exec($sql);
    
    echo "Indexes created successfully.\n";
    
    echo "\nSchema update completed successfully!\n";
    
} catch (Exception $e) {
    echo "Error updating schema: " . $e->getMessage() . "\n";
}
?>
