<?php
// Simple table creation script for OSTA Job Portal
require_once 'config/database.php';

echo "<h2>Creating OSTA Job Portal Tables</h2>";

$tables = [
    'centralized_applications' => "
        CREATE TABLE IF NOT EXISTS centralized_applications (
            id INT PRIMARY KEY AUTO_INCREMENT,
            user_id INT NOT NULL,
            application_number VARCHAR(20) UNIQUE NOT NULL,
            first_name VARCHAR(100) NOT NULL,
            last_name VARCHAR(100) NOT NULL,
            email VARCHAR(100) NOT NULL,
            phone VARCHAR(20) NOT NULL,
            national_id VARCHAR(20) NOT NULL,
            date_of_birth DATE NOT NULL,
            gender ENUM('male', 'female', 'other') NOT NULL,
            address TEXT NOT NULL,
            city VARCHAR(100) NOT NULL,
            region VARCHAR(100) NOT NULL,
            education_level ENUM('high_school', 'diploma', 'bachelor', 'master', 'phd') NOT NULL,
            field_of_study VARCHAR(200) NOT NULL,
            institution VARCHAR(200) NOT NULL,
            graduation_year YEAR NOT NULL,
            gpa DECIMAL(3,2),
            years_of_experience INT DEFAULT 0,
            current_position VARCHAR(200),
            current_employer VARCHAR(200),
            preferred_departments JSON,
            preferred_positions JSON,
            willing_to_relocate BOOLEAN DEFAULT FALSE,
            expected_salary_min INT,
            expected_salary_max INT,
            status ENUM('draft', 'submitted', 'under_review', 'shortlisted', 'interview_scheduled', 'rejected', 'accepted', 'onboarding') DEFAULT 'draft',
            eligibility_status ENUM('pending', 'eligible', 'not_eligible') DEFAULT 'pending',
            eligibility_notes TEXT,
            submitted_at TIMESTAMP NULL,
            reviewed_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
        )",
    
    'application_documents' => "
        CREATE TABLE IF NOT EXISTS application_documents (
            id INT PRIMARY KEY AUTO_INCREMENT,
            application_id INT NOT NULL,
            document_type ENUM('resume', 'cover_letter', 'national_id', 'passport', 'diploma', 'transcript', 'certificate', 'recommendation_letter', 'other') NOT NULL,
            original_filename VARCHAR(255) NOT NULL,
            stored_filename VARCHAR(255) NOT NULL,
            file_path VARCHAR(500) NOT NULL,
            file_size INT NOT NULL,
            mime_type VARCHAR(100) NOT NULL,
            is_required BOOLEAN DEFAULT FALSE,
            verification_status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
            verification_notes TEXT,
            verified_by INT NULL,
            verified_at TIMESTAMP NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (application_id) REFERENCES centralized_applications(id) ON DELETE CASCADE,
            FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL
        )",
    
    'eligibility_criteria' => "
        CREATE TABLE IF NOT EXISTS eligibility_criteria (
            id INT PRIMARY KEY AUTO_INCREMENT,
            department_id INT NULL,
            position_type VARCHAR(200) NULL,
            criteria_name VARCHAR(200) NOT NULL,
            criteria_type ENUM('education_level', 'field_of_study', 'years_experience', 'age_range', 'certification', 'skill', 'other') NOT NULL,
            operator ENUM('equals', 'greater_than', 'less_than', 'greater_equal', 'less_equal', 'contains', 'in_list') NOT NULL,
            required_value TEXT NOT NULL,
            is_mandatory BOOLEAN DEFAULT TRUE,
            weight INT DEFAULT 1,
            is_active BOOLEAN DEFAULT TRUE,
            created_by INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
            FOREIGN KEY (created_by) REFERENCES users(id)
        )",
    
    'application_eligibility_checks' => "
        CREATE TABLE IF NOT EXISTS application_eligibility_checks (
            id INT PRIMARY KEY AUTO_INCREMENT,
            application_id INT NOT NULL,
            criteria_id INT NOT NULL,
            check_result ENUM('pass', 'fail', 'pending') NOT NULL,
            actual_value TEXT,
            score INT DEFAULT 0,
            notes TEXT,
            checked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            FOREIGN KEY (application_id) REFERENCES centralized_applications(id) ON DELETE CASCADE,
            FOREIGN KEY (criteria_id) REFERENCES eligibility_criteria(id) ON DELETE CASCADE,
            UNIQUE KEY unique_application_criteria (application_id, criteria_id)
        )",
    
    'notification_templates' => "
        CREATE TABLE IF NOT EXISTS notification_templates (
            id INT PRIMARY KEY AUTO_INCREMENT,
            template_code VARCHAR(50) UNIQUE NOT NULL,
            template_name VARCHAR(200) NOT NULL,
            notification_type ENUM('email', 'sms', 'system') NOT NULL,
            subject VARCHAR(255),
            message_template TEXT NOT NULL,
            variables JSON,
            is_active BOOLEAN DEFAULT TRUE,
            created_by INT NOT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (created_by) REFERENCES users(id)
        )",
    
    'vacancy_requests' => "
        CREATE TABLE IF NOT EXISTS vacancy_requests (
            id INT PRIMARY KEY AUTO_INCREMENT,
            request_number VARCHAR(20) UNIQUE NOT NULL,
            department_id INT NOT NULL,
            requested_by INT NOT NULL,
            position_title VARCHAR(200) NOT NULL,
            position_description TEXT NOT NULL,
            number_of_positions INT NOT NULL DEFAULT 1,
            employment_type ENUM('full_time', 'part_time', 'contract', 'internship') NOT NULL,
            salary_min INT,
            salary_max INT,
            education_requirements TEXT NOT NULL,
            experience_requirements TEXT NOT NULL,
            skills_requirements TEXT NOT NULL,
            other_requirements TEXT,
            business_justification TEXT NOT NULL,
            urgency_level ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
            preferred_start_date DATE,
            status ENUM('draft', 'submitted', 'hr_review', 'approved', 'rejected', 'published', 'closed') DEFAULT 'draft',
            hr_reviewer_id INT NULL,
            hr_review_notes TEXT,
            hr_reviewed_at TIMESTAMP NULL,
            final_approver_id INT NULL,
            final_approval_notes TEXT,
            approved_at TIMESTAMP NULL,
            job_id INT NULL,
            created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
            updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
            FOREIGN KEY (department_id) REFERENCES departments(id),
            FOREIGN KEY (requested_by) REFERENCES users(id),
            FOREIGN KEY (hr_reviewer_id) REFERENCES users(id) ON DELETE SET NULL,
            FOREIGN KEY (final_approver_id) REFERENCES users(id) ON DELETE SET NULL,
            FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE SET NULL
        )"
];

$success_count = 0;
$error_count = 0;

foreach ($tables as $table_name => $sql) {
    try {
        $pdo->exec($sql);
        echo "<p style='color: green;'>✓ Created table: $table_name</p>";
        $success_count++;
    } catch (PDOException $e) {
        if (strpos($e->getMessage(), 'already exists') !== false) {
            echo "<p style='color: orange;'>⚠ Table already exists: $table_name</p>";
        } else {
            echo "<p style='color: red;'>✗ Error creating $table_name: " . $e->getMessage() . "</p>";
            $error_count++;
        }
    }
}

// Insert some default data
echo "<h3>Inserting Default Data</h3>";

try {
    // Check if notification templates exist
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM notification_templates");
    $stmt->execute();
    $template_count = $stmt->fetchColumn();
    
    if ($template_count == 0) {
        $templates = [
            ['APP_SUBMITTED', 'Application Submitted', 'email', 'Application Submitted Successfully', 'Dear {{first_name}}, your application {{application_number}} has been submitted successfully.', '["first_name", "application_number"]'],
            ['APP_UNDER_REVIEW', 'Application Under Review', 'email', 'Application Under Review', 'Dear {{first_name}}, your application {{application_number}} is now under review.', '["first_name", "application_number"]'],
            ['APP_SHORTLISTED', 'Application Shortlisted', 'email', 'Congratulations! You have been shortlisted', 'Dear {{first_name}}, congratulations! Your application {{application_number}} has been shortlisted.', '["first_name", "application_number"]']
        ];
        
        $stmt = $pdo->prepare("INSERT INTO notification_templates (template_code, template_name, notification_type, subject, message_template, variables, created_by) VALUES (?, ?, ?, ?, ?, ?, 1)");
        
        foreach ($templates as $template) {
            $stmt->execute($template);
        }
        
        echo "<p style='color: green;'>✓ Inserted default notification templates</p>";
    } else {
        echo "<p style='color: orange;'>⚠ Notification templates already exist</p>";
    }
    
    // Check if eligibility criteria exist
    $stmt = $pdo->prepare("SELECT COUNT(*) FROM eligibility_criteria");
    $stmt->execute();
    $criteria_count = $stmt->fetchColumn();
    
    if ($criteria_count == 0) {
        $criteria = [
            ['Minimum Education', 'education_level', 'greater_equal', '"bachelor"', 1],
            ['Minimum Age', 'age_range', 'greater_equal', '18', 1],
            ['Maximum Age', 'age_range', 'less_equal', '35', 1],
            ['Minimum Experience', 'years_experience', 'greater_equal', '0', 0]
        ];
        
        $stmt = $pdo->prepare("INSERT INTO eligibility_criteria (criteria_name, criteria_type, operator, required_value, is_mandatory, created_by) VALUES (?, ?, ?, ?, ?, 1)");
        
        foreach ($criteria as $criterion) {
            $stmt->execute($criterion);
        }
        
        echo "<p style='color: green;'>✓ Inserted default eligibility criteria</p>";
    } else {
        echo "<p style='color: orange;'>⚠ Eligibility criteria already exist</p>";
    }
    
} catch (PDOException $e) {
    echo "<p style='color: red;'>✗ Error inserting default data: " . $e->getMessage() . "</p>";
}

echo "<h3>Summary</h3>";
echo "<p>Tables created successfully: $success_count</p>";
echo "<p>Errors: $error_count</p>";

if ($error_count == 0) {
    echo "<p style='color: green; font-weight: bold;'>✓ Database setup completed successfully!</p>";
    echo "<p><a href='applicant/dashboard.php'>Test the Dashboard</a></p>";
} else {
    echo "<p style='color: red; font-weight: bold;'>⚠ Some errors occurred during setup</p>";
}
?>
