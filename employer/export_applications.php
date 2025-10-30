<?php
require_once '../config/database.php';
require_once '../includes/auth.php';

// Require employer role
require_role('employer', '../login.php');

// Check if job_id is provided
if (!isset($_GET['job_id']) || !is_numeric($_GET['job_id'])) {
    $_SESSION['error'] = 'Invalid job specified';
    header('Location: dashboard.php');
    exit();
}

$job_id = (int)$_GET['job_id'];
$format = isset($_GET['format']) && in_array($_GET['format'], ['csv', 'txt']) ? $_GET['format'] : 'csv';

// Get employer's department ID
$stmt = $pdo->prepare("SELECT department_id FROM users WHERE id = ?");
$stmt->execute([$_SESSION['user_id']]);
$employer = $stmt->fetch();
$department_id = $employer['department_id'];

// Verify job belongs to employer's department
$job_check = $pdo->prepare("SELECT id, title FROM jobs WHERE id = ? AND department_id = ?");
$job_check->execute([$job_id, $department_id]);
$job = $job_check->fetch();

if (!$job) {
    $_SESSION['error'] = 'Job not found or access denied';
    header('Location: dashboard.php');
    exit();
}

// Get applications for the job
$stmt = $pdo->prepare("
    SELECT 
        a.*,
        u.full_name as applicant_name,
        u.email,
        u.phone,
        u.address,
        u.skills,
        a.status as application_status,
        a.created_at as applied_at,
        a.updated_at as status_updated_at,
        a.feedback
    FROM applications a
    JOIN users u ON a.user_id = u.id
    WHERE a.job_id = ?
    ORDER BY a.created_at DESC
");
$stmt->execute([$job_id]);
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Set headers based on format
if ($format === 'csv') {
    header('Content-Type: text/csv; charset=utf-8');
    header('Content-Disposition: attachment; filename="applications_' . preg_replace('/[^a-z0-9_]/i', '_', strtolower($job['title'])) . '.csv"');
    
    // Create output stream
    $output = fopen('php://output', 'w');
    
    // Add BOM for proper UTF-8 encoding in Excel
    fputs($output, "\xEF\xBB\xBF");
    
    // Add headers
    fputcsv($output, [
        'Name', 'Email', 'Phone', 'Address', 'Skills', 
        'Status', 'Applied At', 'Last Updated', 'Feedback', 'Cover Letter', 'Resume'
    ]);
    
    // Add data rows
    foreach ($applications as $app) {
        fputcsv($output, [
            $app['applicant_name'],
            $app['email'],
            $app['phone'] ?? '',
            $app['address'] ?? '',
            $app['skills'] ?? '',
            ucfirst($app['application_status']),
            date('Y-m-d H:i', strtotime($app['applied_at'])),
            $app['status_updated_at'] ? date('Y-m-d H:i', strtotime($app['status_updated_at'])) : 'N/A',
            $app['feedback'] ?? '',
            $app['cover_letter'] ? 'Yes' : 'No',
            $app['resume_path'] ? 'Yes' : 'No'
        ]);
    }
    
    fclose($output);
    exit();
    
} else { // TXT format
    header('Content-Type: text/plain; charset=utf-8');
    header('Content-Disposition: attachment; filename="applications_' . preg_replace('/[^a-z0-9_]/i', '_', strtolower($job['title'])) . '.txt"');
    
    // Create output
    $output = [];
    $output[] = str_repeat('=', 80);
    $output[] = strtoupper("Applications for: " . $job['title']);
    $output[] = "Generated: " . date('Y-m-d H:i');
    $output[] = str_repeat('=', 80) . "\n";
    
    if (empty($applications)) {
        $output[] = "No applications found for this job.";
    } else {
        foreach ($applications as $index => $app) {
            $output[] = str_repeat('-', 40);
            $output[] = "APPLICANT #" . ($index + 1);
            $output[] = str_repeat('-', 40);
            $output[] = "Name: " . $app['applicant_name'];
            $output[] = "Email: " . $app['email'];
            $output[] = "Phone: " . ($app['phone'] ?? 'N/A');
            $output[] = "Status: " . ucfirst($app['application_status']);
            $output[] = "Applied: " . date('M j, Y H:i', strtotime($app['applied_at']));
            $output[] = "Last Updated: " . ($app['status_updated_at'] ? date('M j, Y H:i', strtotime($app['status_updated_at'])) : 'N/A');
            
            if (!empty($app['address'])) {
                $output[] = "\nAddress:" . str_repeat(' ', 11) . $app['address'];
            }
            
            if (!empty($app['skills'])) {
                $output[] = "Skills:" . str_repeat(' ', 13) . $app['skills'];
            }
            
            if (!empty($app['feedback'])) {
                $output[] = "\nFeedback:";
                $output[] = str_repeat('-', 10);
                $output[] = wordwrap($app['feedback'], 80, "\n");
            }
            
            $output[] = "\nAttachments:";
            $output[] = "- Cover Letter: " . ($app['cover_letter'] ? 'Yes' : 'No');
            $output[] = "- Resume: " . ($app['resume_path'] ? 'Yes' : 'No');
            $output[] = "\n";
        }
    }
    
    echo implode("\n", $output);
    exit();
}
