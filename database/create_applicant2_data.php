<?php
require_once '../config/database.php';

try {
    // Get user ID for applicant2@gmail.com
    $stmt = $pdo->prepare("SELECT id, full_name FROM users WHERE email = ?");
    $stmt->execute(['applicant2@gmail.com']);
    $user = $stmt->fetch();
    
    if (!$user) {
        throw new Exception("User applicant2@gmail.com not found");
    }
    
    $user_id = $user['id'];
    echo "Found user: {$user['full_name']} (ID: {$user_id})\n";
    
    // Check if application already exists
    $check_stmt = $pdo->prepare("SELECT id FROM centralized_applications WHERE user_id = ?");
    $check_stmt->execute([$user_id]);
    $existing = $check_stmt->fetch();
    
    if ($existing) {
        echo "Application already exists for this user (ID: {$existing['id']})\n";
        echo "Updating existing application...\n";
        $action = 'update';
        $app_id = $existing['id'];
    } else {
        echo "Creating new application...\n";
        $action = 'create';
    }
    
    // Generate unique application number
    $year = date('Y');
    $count_stmt = $pdo->prepare("SELECT COUNT(*) FROM centralized_applications WHERE application_number LIKE ?");
    $count_stmt->execute(["OSTA-{$year}-%"]);
    $count = $count_stmt->fetchColumn() + 1;
    $application_number = sprintf("OSTA-%s-%03d", $year, $count);
    
    // Sample application data for Jane Smith (applicant2)
    $application_data = [
        'user_id' => $user_id,
        'application_number' => $application_number,
        'first_name' => 'Jane',
        'last_name' => 'Smith',
        'email' => 'applicant2@gmail.com',
        'phone' => '+251 91 234 5678',
        'national_id' => 'ID123456789',
        'date_of_birth' => '1990-05-15',
        'gender' => 'female',
        'address' => '123 Bole Road, Addis Ababa',
        'city' => 'Addis Ababa',
        'region' => 'Addis Ababa',
        'education_level' => 'master',
        'field_of_study' => 'Business Administration',
        'institution' => 'Addis Ababa University',
        'graduation_year' => 2015,
        'gpa' => 3.75,
        'years_of_experience' => 8,
        'current_position' => 'Technology Transfer Specialist',
        'current_employer' => 'Ethiopian Science and Technology Agency',
        'preferred_departments' => json_encode([2, 1]), // Technology Transfer, R&D
        'preferred_positions' => json_encode(['Senior Technology Transfer Specialist', 'Business Development Manager']),
        'willing_to_relocate' => 1,
        'expected_salary_min' => 55000,
        'expected_salary_max' => 75000,
        'status' => 'submitted',
        'eligibility_status' => 'eligible',
        'eligibility_notes' => 'Meets all requirements for Technology Transfer positions',
        'submitted_at' => date('Y-m-d H:i:s')
    ];
    
    if ($action === 'create') {
        // Insert new application
        $sql = "INSERT INTO centralized_applications (
            user_id, application_number, first_name, last_name, email, phone, national_id,
            date_of_birth, gender, address, city, region, education_level, field_of_study,
            institution, graduation_year, gpa, years_of_experience, current_position,
            current_employer, preferred_departments, preferred_positions, willing_to_relocate,
            expected_salary_min, expected_salary_max, status, eligibility_status,
            eligibility_notes, submitted_at
        ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?, ?)";
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute(array_values($application_data));
        $app_id = $pdo->lastInsertId();
        
    } else {
        // Update existing application
        $sql = "UPDATE centralized_applications SET 
            application_number = ?, first_name = ?, last_name = ?, email = ?, phone = ?, national_id = ?,
            date_of_birth = ?, gender = ?, address = ?, city = ?, region = ?, education_level = ?,
            field_of_study = ?, institution = ?, graduation_year = ?, gpa = ?, years_of_experience = ?,
            current_position = ?, current_employer = ?, preferred_departments = ?, preferred_positions = ?,
            willing_to_relocate = ?, expected_salary_min = ?, expected_salary_max = ?, status = ?,
            eligibility_status = ?, eligibility_notes = ?, submitted_at = ?, updated_at = NOW()
            WHERE id = ?";
        
        $update_data = array_values($application_data);
        array_shift($update_data); // Remove user_id
        $update_data[] = $app_id; // Add app_id at the end
        
        $stmt = $pdo->prepare($sql);
        $stmt->execute($update_data);
    }
    
    echo "Application {$action}d successfully! Application ID: {$app_id}\n";
    echo "Application Number: {$application_number}\n";
    
    // Add some sample documents
    $documents = [
        [
            'document_type' => 'resume',
            'original_filename' => 'Jane_Smith_Resume.pdf',
            'stored_filename' => 'resume_' . $user_id . '_' . time() . '.pdf',
            'file_path' => '../uploads/resumes/resume_' . $user_id . '_' . time() . '.pdf',
            'file_size' => 245760,
            'mime_type' => 'application/pdf',
            'is_required' => 1,
            'verification_status' => 'verified'
        ],
        [
            'document_type' => 'diploma',
            'original_filename' => 'MBA_Diploma.pdf',
            'stored_filename' => 'diploma_' . $user_id . '_' . time() . '.pdf',
            'file_path' => '../uploads/certificates/diploma_' . $user_id . '_' . time() . '.pdf',
            'file_size' => 189440,
            'mime_type' => 'application/pdf',
            'is_required' => 1,
            'verification_status' => 'verified'
        ],
        [
            'document_type' => 'certificate',
            'original_filename' => 'Project_Management_Certificate.pdf',
            'stored_filename' => 'cert_' . $user_id . '_' . time() . '.pdf',
            'file_path' => '../uploads/certificates/cert_' . $user_id . '_' . time() . '.pdf',
            'file_size' => 156780,
            'mime_type' => 'application/pdf',
            'is_required' => 0,
            'verification_status' => 'verified'
        ]
    ];
    
    // Insert documents
    $doc_sql = "INSERT INTO application_documents (
        application_id, document_type, original_filename, stored_filename, file_path,
        file_size, mime_type, is_required, verification_status, verified_at
    ) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, NOW())";
    
    $doc_stmt = $pdo->prepare($doc_sql);
    
    foreach ($documents as $doc) {
        $doc_stmt->execute([
            $app_id,
            $doc['document_type'],
            $doc['original_filename'],
            $doc['stored_filename'],
            $doc['file_path'],
            $doc['file_size'],
            $doc['mime_type'],
            $doc['is_required'],
            $doc['verification_status']
        ]);
        echo "Added document: {$doc['original_filename']}\n";
    }
    
    // Add application status history
    $status_sql = "INSERT INTO application_status_history (
        application_id, old_status, new_status, changed_by, notes
    ) VALUES (?, ?, ?, ?, ?)";
    
    $status_stmt = $pdo->prepare($status_sql);
    
    // Draft to submitted
    $status_stmt->execute([
        $app_id, 'draft', 'submitted', 1, 'Application submitted by applicant'
    ]);
    
    echo "Added status history entries\n";
    
    // Add some eligibility check results
    $eligibility_sql = "INSERT INTO application_eligibility_checks (
        application_id, criteria_id, check_result, actual_value, score, notes
    ) VALUES (?, ?, ?, ?, ?, ?)";
    
    $elig_stmt = $pdo->prepare($eligibility_sql);
    
    // Sample eligibility checks (assuming some criteria exist)
    $eligibility_checks = [
        [1, 'pass', 'Master\'s Degree', 10, 'Education requirement met'],
        [2, 'pass', '8 years', 8, 'Experience requirement exceeded'],
        [3, 'pass', 'Technology Transfer', 5, 'Relevant field experience']
    ];
    
    foreach ($eligibility_checks as $check) {
        try {
            $elig_stmt->execute([
                $app_id, $check[0], $check[1], $check[2], $check[3], $check[4]
            ]);
        } catch (Exception $e) {
            // Criteria might not exist, skip
            echo "Skipped eligibility check (criteria may not exist)\n";
        }
    }
    
    echo "\n=== APPLICATION DATA CREATED SUCCESSFULLY ===\n";
    echo "User: Jane Smith (applicant2@gmail.com)\n";
    echo "Application Number: {$application_number}\n";
    echo "Status: Submitted\n";
    echo "Eligibility: Eligible\n";
    echo "Documents: 3 uploaded and verified\n";
    echo "Preferred Departments: Technology Transfer, R&D\n";
    echo "Experience: 8 years in Technology Transfer\n";
    echo "Expected Salary: ETB 55,000 - 75,000\n";
    
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
