<?php
require_once __DIR__ . '/../../config/database.php';
require_once __DIR__ . '/../../includes/auth.php';
require_once __DIR__ . '/../../includes/security.php';
require_once __DIR__ . '/../../includes/application_functions.php';

// Require admin role and set security headers
require_role('admin', '../../login.php');
set_security_headers();

header('Content-Type: application/json');

try {
    // Interview types
    $types_stmt = $pdo->query("SELECT id, name FROM interview_types ORDER BY name");
    $interview_types = $types_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Applications without interviews, and in eligible statuses
    $apps_sql = "SELECT ca.id, ca.application_number, ca.created_at, ca.first_name, ca.last_name, ca.preferred_positions, u.email
                 FROM centralized_applications ca
                 JOIN users u ON ca.user_id = u.id
                 WHERE ca.id NOT IN (SELECT application_id FROM interviews WHERE application_id IS NOT NULL)
                 AND ca.status IN ('submitted', 'shortlisted', 'under_review')
                 ORDER BY ca.created_at DESC";
    $apps_stmt = $pdo->query($apps_sql);
    $applications = $apps_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Interviewers (admins and HR staff)
    $interviewers_stmt = $pdo->query("SELECT id, first_name, last_name, email FROM users WHERE role IN ('admin', 'hr') ORDER BY first_name, last_name");
    $interviewers = $interviewers_stmt->fetchAll(PDO::FETCH_ASSOC);

    // Default generated interview code
    $default_code = generate_interview_code();

    echo json_encode([
        'success' => true,
        'data' => [
            'applications' => $applications,
            'interview_types' => $interview_types,
            'interviewers' => $interviewers,
            'default_interview_code' => $default_code
        ]
    ]);
} catch (Exception $e) {
    http_response_code(500);
    echo json_encode([
        'success' => false,
        'error' => 'Failed to fetch schedule interview data',
        'message' => $e->getMessage()
    ]);
}
?>

