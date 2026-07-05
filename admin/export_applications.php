<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/security.php';

// Ensure user is admin
if (!isset($_SESSION['user_id']) || $_SESSION['role'] !== 'admin') {
    header('Location: ' . SITE_URL . '/unauthorized.php');
    exit;
}

// Verify CSRF token
if ($_SERVER['REQUEST_METHOD'] === 'POST' && !validate_csrf_token($_POST['csrf_token'] ?? '')) {
    $_SESSION['error_message'] = 'Invalid CSRF token';
    header('Location: applications.php');
    exit;
}

// Get export format (default to CSV)
$format = $_POST['format'] ?? 'csv';
$status = $_POST['status'] ?? '';

// Get applications data
$query = "
    SELECT 
        a.application_number,
        CONCAT(u.first_name, ' ', u.last_name) as full_name,
        u.email,
        u.phone,
        d.name as department_name,
        a.preferred_positions,
        a.status,
        a.created_at as application_date
    FROM centralized_applications a
    JOIN users u ON a.user_id = u.id
    LEFT JOIN departments d ON a.department_id = d.id
";

$params = [];
if ($status) {
    $query .= " WHERE a.status = ?";
    $params[] = $status;
}
$query .= " ORDER BY a.updated_at DESC";

$stmt = $pdo->prepare($query);
$stmt->execute($params);
$applications = $stmt->fetchAll(PDO::FETCH_ASSOC);

// Set headers for CSV (simplest format)
header('Content-Type: text/csv');
header('Content-Disposition: attachment; filename="applications_' . date('Y-m-d') . '.csv"');

// Output CSV directly
$output = fopen('php://output', 'w');

// Headers
fputcsv($output, [
    'Application #',
    'Full Name',
    'Email',
    'Phone',
    'Department',
    'Preferred Positions',
    'Status',
    'Application Date'
]);

// Data rows
foreach ($applications as $app) {
    fputcsv($output, [
        $app['application_number'],
        $app['full_name'],
        $app['email'],
        $app['phone'],
        $app['department_name'] ?? 'N/A',
        $app['preferred_positions'],
        ucfirst(str_replace('_', ' ', $app['status'])),
        date('Y-m-d H:i:s', strtotime($app['application_date']))
    ]);
}

fclose($output);
exit;
