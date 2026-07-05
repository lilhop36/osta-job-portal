<?php
require_once __DIR__ . '/vendor/autoload.php';
require_once __DIR__ . '/config/database.php';

function make_hash(string $pw): string {
    return password_hash($pw, PASSWORD_ARGON2ID, [
        'memory_cost' => 65536,
        'time_cost' => 4,
        'threads' => 3,
    ]);
}

$users = [
    ['admin@gmail.com', 'admin_user', 'admin', 'admin', 'active', 'System Admin'],
    ['employer@gmail.com', 'employer_user', 'employer', 'employer', 'active', 'Test Employer'],
    ['applicant@gmail.com', 'applicant_user', 'applicant', 'applicant', 'active', 'Test Applicant'],
];

foreach ([$users[0], $users[1], $users[2]] as [$email, $username, $first, $role, $status, $full]) {
    $check = $pdo->prepare("SELECT id FROM users WHERE email = ?");
    $check->execute([$email]);
    if ($check->fetch()) {
        echo "SKIP: $email already exists\n";
        continue;
    }
    $hash = make_hash($role === 'admin' ? 'admin123' : ($role === 'employer' ? 'employer123' : 'applicant123'));
    $stmt = $pdo->prepare("INSERT INTO users (username, email, password, role, status, full_name, first_name, last_name, email_verified) VALUES (?, ?, ?, ?, ?, ?, ?, ?, 1)");
    $stmt->execute([$username, $email, $hash, $role, $status, $full, $first, 'User']);
    echo "CREATED: $email / " . ($role === 'admin' ? 'admin123' : ($role === 'employer' ? 'employer123' : 'applicant123')) . " (role=$role)\n";
}

// Also fix the seed admin user's password
$fixHash = make_hash('admin123');
$stmt = $pdo->prepare("UPDATE users SET password = ? WHERE email = 'admin@osta.gov.et'");
$stmt->execute([$fixHash]);
echo "Fixed password for admin@osta.gov.et\n";

echo "\n=== Test Credentials ===\n";
echo "admin@gmail.com / admin123\n";
echo "employer@gmail.com / employer123\n";
echo "applicant@gmail.com / applicant123\n";
echo "admin@osta.gov.et / admin123\n";
