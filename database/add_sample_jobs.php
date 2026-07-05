<?php
require_once '../config/database.php';

try {
    // Check if jobs already exist
    $stmt = $pdo->query("SELECT COUNT(*) FROM jobs");
    $count = $stmt->fetchColumn();
    
    if ($count < 5) {  // Only add if we have less than 5 jobs
        // Insert additional jobs
        $jobs = [
            [
                'department_id' => 1,
                'title' => 'Senior Research Scientist',
                'description' => 'We are seeking an experienced Senior Research Scientist to lead our research initiatives.',
                'requirements' => 'PhD in relevant field, 8+ years experience, strong research background, leadership skills',
                'employment_type' => 'full_time',
                'location' => 'Addis Ababa',
                'salary_range' => 'ETB 70,000 - 90,000',
                'deadline' => '2025-09-15',
                'status' => 'approved',
                'created_by' => 5
            ],
            [
                'department_id' => 2,
                'title' => 'Senior Technology Transfer Specialist',
                'description' => 'Senior specialist to manage complex technology commercialization projects.',
                'requirements' => 'MSc in Engineering, 5+ years in technology transfer, MBA preferred',
                'employment_type' => 'full_time',
                'location' => 'Addis Ababa',
                'salary_range' => 'ETB 60,000 - 80,000',
                'deadline' => '2025-09-10',
                'status' => 'approved',
                'created_by' => 6
            ],
            [
                'department_id' => 3,
                'title' => 'Senior QA Manager',
                'description' => 'Senior Quality Assurance Manager to oversee our QA department.',
                'requirements' => 'BSc in Engineering, 5+ years QA experience, management experience',
                'employment_type' => 'full_time',
                'location' => 'Addis Ababa',
                'salary_range' => 'ETB 55,000 - 75,000',
                'deadline' => '2025-09-20',
                'status' => 'approved',
                'created_by' => 7
            ],
            [
                'department_id' => 1,
                'title' => 'Data Analyst',
                'description' => 'Data Analyst needed to support our research projects.',
                'requirements' => 'BSc in Statistics or related field, 2+ years experience with data analysis tools',
                'employment_type' => 'full_time',
                'location' => 'Addis Ababa',
                'salary_range' => 'ETB 30,000 - 45,000',
                'deadline' => '2025-09-30',
                'status' => 'approved',
                'created_by' => 5
            ],
            [
                'department_id' => 2,
                'title' => 'IP Specialist',
                'description' => 'Intellectual Property Specialist to manage patent applications.',
                'requirements' => 'Law degree with IP specialization, 3+ years IP experience',
                'employment_type' => 'full_time',
                'location' => 'Addis Ababa',
                'salary_range' => 'ETB 45,000 - 65,000',
                'deadline' => '2025-09-25',
                'status' => 'approved',
                'created_by' => 6
            ]
        ];
        
        $stmt = $pdo->prepare("INSERT INTO jobs (department_id, title, description, requirements, employment_type, location, salary_range, deadline, status, created_by) VALUES (?, ?, ?, ?, ?, ?, ?, ?, ?, ?)");
        
        foreach ($jobs as $job) {
            $stmt->execute([
                $job['department_id'],
                $job['title'],
                $job['description'],
                $job['requirements'],
                $job['employment_type'],
                $job['location'],
                $job['salary_range'],
                $job['deadline'],
                $job['status'],
                $job['created_by']
            ]);
            echo "Added job: " . $job['title'] . "\n";
        }
        
        echo "Successfully added " . count($jobs) . " new jobs to the database.\n";
    } else {
        echo "Database already has sufficient jobs. No new jobs added.\n";
    }
} catch (Exception $e) {
    echo "Error: " . $e->getMessage() . "\n";
}
?>
