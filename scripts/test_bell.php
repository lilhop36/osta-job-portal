<?php
require_once __DIR__ . '/../includes/bootstrap.php';

// Simulate logged-in applicant
$applicant = $pdo->query("SELECT id, username, role FROM users WHERE role = 'applicant' LIMIT 1")->fetch();
$_SESSION['user_id'] = $applicant['id'];
$_SESSION['role'] = $applicant['role'];
$_SESSION['username'] = $applicant['username'];
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Test Notifications</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <span class="navbar-brand">Test</span>
        <ul class="navbar-nav ms-auto">
            <li class="nav-item dropdown">
                <a class="nav-link position-relative" href="#" id="notificationsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                    <i class="fas fa-bell"></i>
                </a>
                <div class="dropdown-menu dropdown-menu-end" aria-labelledby="notificationsDropdown">
                    <div class="card">
                        <div class="card-header"><h6 class="mb-0">Notifications</h6></div>
                        <div class="card-body p-0">
                            <?php
                            $uid = $_SESSION['user_id'];
                            $stmt = $pdo->prepare("SELECT n.*, u.username as creator_name 
                                  FROM notifications n 
                                  LEFT JOIN users u ON n.created_by = u.id 
                                  WHERE (n.target = 'all' 
                                      OR (n.target = 'user' AND n.target_id = ?)
                                      OR (n.target = 'department' AND n.target_id IN (SELECT department_id FROM users WHERE id = ?))
                                      OR (n.target = 'job' AND n.target_id IN (SELECT job_id FROM applications WHERE user_id = ?)))
                                  ORDER BY n.created_at DESC 
                                  LIMIT 5");
                            $stmt->execute([$uid, $uid, $uid]);
                            $notifications = $stmt->fetchAll();
                            echo "<p>Found " . count($notifications) . " notifications</p>";
                            foreach ($notifications as $n) {
                                echo '<div class="list-group-item">';
                                echo '<strong>' . htmlspecialchars($n['title']) . '</strong><br>';
                                echo '<small>' . htmlspecialchars($n['message']) . '</small>';
                                echo '</div>';
                            }
                            ?>
                        </div>
                    </div>
                </div>
            </li>
        </ul>
    </div>
</nav>
<p class="p-3">Click the bell icon above.</p>
<script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
