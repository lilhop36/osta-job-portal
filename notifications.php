<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>My Notifications - OSTA Job Portal</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/styles.css">
</head>
<body>
<?php
// Load configuration
require_once __DIR__ . '/config/database.php';
require_once __DIR__ . '/config/email.php';

// Load core files
require_once __DIR__ . '/includes/auth.php';
require_once __DIR__ . '/includes/functions.php';

// Initialize session if not already started
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// Ensure user is logged in
if (!isset($_SESSION['user_id'])) {
    $login_url = (isset($_SERVER['HTTPS']) && $_SERVER['HTTPS'] === 'on' ? "https" : "http") . "://" . $_SERVER['HTTP_HOST'] . "/login.php";
    header('Location: ' . $login_url);
    exit();
}

$page_title = "My Notifications";
$success = '';
$error = '';

// Handle marking notifications as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_as_read'])) {
    try {
        $pdo->beginTransaction();
        
        // Mark all notifications as read for this user
        $stmt = $pdo->prepare("UPDATE notifications SET status = 'read' 
                              WHERE (target = 'all' OR (target = 'user' AND target_id = CAST(? AS CHAR))) 
                              AND status = 'unread'");
        $stmt->execute([(string)$_SESSION['user_id']]);
        
        // If a specific notification was marked as read
        if (isset($_POST['notification_id'])) {
            $stmt = $pdo->prepare("UPDATE notifications SET status = 'read' 
                                  WHERE id = ? 
                                  AND (target = 'all' OR (target = 'user' AND target_id = CAST(? AS CHAR)))");
            $stmt->execute([(int)$_POST['notification_id'], (string)$_SESSION['user_id']]);
            
            // If this was an AJAX request, just return success
            if (!empty($_SERVER['HTTP_X_REQUESTED_WITH']) && strtolower($_SERVER['HTTP_X_REQUESTED_WITH']) == 'xmlhttprequest') {
                header('Content-Type: application/json');
                echo json_encode(['success' => true]);
                exit();
            }
            
            $success = 'Notification marked as read.';
        } else {
            $success = 'All notifications marked as read.';
        }
        
        $pdo->commit();
    } catch (Exception $e) {
        $pdo->rollBack();
        $error = 'Error updating notification status: ' . $e->getMessage();
        error_log($error);
    }
}

// Get pagination parameters
$page = isset($_GET['page']) ? (int)$_GET['page'] : 1;
$per_page = 10;
$offset = ($page - 1) * $per_page;

// Get total number of notifications for this user
$total_stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications WHERE (target = 'all' OR (target = 'user' AND target_id = CAST(? AS CHAR)))");
$total_stmt->execute([(string)$_SESSION['user_id']]);
$total_notifications = $total_stmt->fetchColumn();
$total_pages = ceil($total_notifications / $per_page);

// Get paginated notifications for this user
$stmt = $pdo->prepare("SELECT n.*, u.username as creator_name 
                      FROM notifications n 
                      JOIN users u ON n.created_by = u.id 
                      WHERE (n.target = 'all' OR (n.target = 'user' AND n.target_id = CAST(? AS CHAR)))
                      ORDER BY n.created_at DESC 
                      LIMIT ? OFFSET ?");
$stmt->bindValue(1, (string)$_SESSION['user_id'], PDO::PARAM_STR);
$stmt->bindValue(2, $per_page, PDO::PARAM_INT);
$stmt->bindValue(3, $offset, PDO::PARAM_INT);
$stmt->execute();
$notifications = $stmt->fetchAll();

// Include header
include __DIR__ . '/includes/header_new.php';
?>

<div class="container py-5">
    <div class="row justify-content-center">
        <div class="col-lg-8">
            <div class="card shadow-sm mb-4">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h1 class="h4 mb-0">My Notifications</h1>
                    <?php if ($total_notifications > 0): ?>
                        <form method="post" class="d-inline">
                            <button type="submit" name="mark_as_read" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-check-circle me-1"></i> Mark all as read
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
                <div class="card-body p-0">
                    <?php if (!empty($error)): ?>
                        <div class="alert alert-danger m-3"><?php echo htmlspecialchars($error); ?></div>
                    <?php elseif (!empty($success)): ?>
                        <div class="alert alert-success m-3"><?php echo htmlspecialchars($success); ?></div>
                    <?php endif; ?>
                    
                    <?php if (count($notifications) > 0): ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($notifications as $notification): ?>
                                <div class="list-group-item list-group-item-action <?php echo $notification['status'] === 'unread' ? 'bg-light' : ''; ?>" 
                                     id="notification-<?php echo $notification['id']; ?>">
                                    <div class="d-flex w-100 justify-content-between">
                                        <h6 class="mb-1">
                                            <?php if ($notification['status'] === 'unread'): ?>
                                                <span class="badge bg-primary me-2">New</span>
                                            <?php endif; ?>
                                            <?php echo htmlspecialchars($notification['title']); ?>
                                        </h6>
                                        <small class="text-muted"><?php echo time_elapsed_string($notification['created_at']); ?></small>
                                    </div>
                                    <p class="mb-1"><?php echo nl2br(htmlspecialchars($notification['message'])); ?></p>
                                    <?php if ($notification['status'] === 'unread'): ?>
                                        <form method="post" class="mt-2 mark-as-read-form" data-notification-id="<?php echo $notification['id']; ?>">
                                            <input type="hidden" name="notification_id" value="<?php echo $notification['id']; ?>">
                                            <button type="submit" name="mark_as_read" class="btn btn-sm btn-outline-secondary">
                                                <i class="fas fa-check me-1"></i> Mark as read
                                            </button>
                                        </form>
                                    <?php endif; ?>
                                </div>
                            <?php endforeach; ?>
                        </div>
                        
                        <?php if ($total_pages > 1): ?>
                            <nav class="p-3">
                                <ul class="pagination justify-content-center mb-0">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page - 1; ?>" aria-label="Previous">
                                                <span aria-hidden="true">&laquo;</span>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                    
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    
                                    <?php if ($page < $total_pages): ?>
                                        <li class="page-item">
                                            <a class="page-link" href="?page=<?php echo $page + 1; ?>" aria-label="Next">
                                                <span aria-hidden="true">&raquo;</span>
                                            </a>
                                        </li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                        
                    <?php else: ?>
                        <div class="text-center p-5">
                            <div class="mb-3">
                                <i class="fas fa-bell-slash fa-3x text-muted"></i>
                            </div>
                            <h5>No notifications yet</h5>
                            <p class="text-muted">When you have notifications, they'll appear here.</p>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<script>
// AJAX form submission for marking notifications as read
document.addEventListener('DOMContentLoaded', function() {
    const forms = document.querySelectorAll('.mark-as-read-form');
    
    forms.forEach(form => {
        form.addEventListener('submit', function(e) {
            e.preventDefault();
            
            const formData = new FormData(form);
            const notificationId = form.dataset.notificationId;
            
            fetch('notifications.php', {
                method: 'POST',
                body: formData,
                headers: {
                    'X-Requested-With': 'XMLHttpRequest'
                }
            })
            .then(response => response.json())
            .then(data => {
                if (data.success) {
                    // Update the UI
                    const notificationEl = document.getElementById(`notification-${notificationId}`);
                    if (notificationEl) {
                        // Remove the 'New' badge and mark as read
                        const newBadge = notificationEl.querySelector('.badge');
                        if (newBadge) newBadge.remove();
                        
                        // Remove the mark as read button
                        const form = notificationEl.querySelector('form');
                        if (form) form.remove();
                        
                        // Update the background
                        notificationEl.classList.remove('bg-light');
                    }
                    
                    // Update the unread count in the header
                    updateUnreadCount();
                }
            })
            .catch(error => {
                console.error('Error:', error);
                alert('An error occurred while marking the notification as read.');
            });
        });
    });
    
    // Function to update the unread count in the header
    function updateUnreadCount() {
        const badge = document.querySelector('.navbar .fa-bell').parentElement.querySelector('.badge');
        if (badge) {
            const count = parseInt(badge.textContent) - 1;
            if (count > 0) {
                badge.textContent = count;
            } else {
                badge.remove();
            }
        }
    }
});
</script>

<?php include __DIR__ . '/includes/footer.php'; ?>
