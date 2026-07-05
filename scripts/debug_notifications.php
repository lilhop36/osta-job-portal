<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/functions.php';

// Simulate a logged-in applicant (pick first applicant from DB)
$applicant = $pdo->query("SELECT id, username, role FROM users WHERE role = 'applicant' LIMIT 1")->fetch();
if (!$applicant) {
    die("No applicant user found in database");
}

$_SESSION['user_id'] = $applicant['id'];
$_SESSION['role'] = $applicant['role'];
$_SESSION['username'] = $applicant['username'];

echo "Testing as: {$applicant['username']} (id={$applicant['id']})\n\n";

// Test 1: Count all notifications
$all = $pdo->query("SELECT COUNT(*) FROM notifications")->fetchColumn();
echo "Total notifications in DB: $all\n";

// Test 2: Count notifications with target=all
$allTarget = $pdo->query("SELECT COUNT(*) FROM notifications WHERE target = 'all'")->fetchColumn();
echo "Notifications with target='all': $allTarget\n";

// Test 3: Count notifications matching this user (all targets)
$uid = $applicant['id'];
$stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications 
    WHERE (target = 'all' 
        OR (target = 'user' AND target_id = ?)
        OR (target = 'department' AND target_id IN (SELECT department_id FROM users WHERE id = ?))
        OR (target = 'job' AND target_id IN (SELECT job_id FROM applications WHERE user_id = ?)))");
$stmt->execute([$uid, $uid, $uid]);
$matching = $stmt->fetchColumn();
echo "Notifications matching this user: $matching\n";

// Test 4: Show all notifications
$allNotifs = $pdo->query("SELECT id, title, target, target_id, status FROM notifications ORDER BY id DESC LIMIT 10")->fetchAll();
echo "\nAll notifications:\n";
foreach ($allNotifs as $n) {
    echo "  [{$n['id']}] title={$n['title']} target={$n['target']} target_id={$n['target_id']} status={$n['status']}\n";
}
