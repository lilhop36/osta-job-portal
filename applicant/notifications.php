<?php
require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../includes/functions.php';
require_auth('applicant');

$page_title = "Notifications";
$success = '';
$error = '';

// Handle marking notifications as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['mark_as_read'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $error = 'Invalid security token.';
    } else {
        try {
            $pdo->beginTransaction();
            $stmt = $pdo->prepare("UPDATE notifications SET status = 'read' 
                                  WHERE (target = 'all' 
                                      OR (target = 'user' AND target_id = ?)
                                      OR (target = 'department' AND target_id IN (SELECT department_id FROM users WHERE id = ?))
                                      OR (target = 'job' AND target_id IN (SELECT job_id FROM applications WHERE user_id = ?)))
                                  AND status = 'unread'");
            $stmt->execute([$_SESSION['user_id'], $_SESSION['user_id'], $_SESSION['user_id']]);
            $pdo->commit();
            $success = 'All notifications marked as read.';
        } catch (Exception $e) {
            $pdo->rollBack();
            $error = 'Error updating notifications.';
            error_log('Notification error: ' . $e->getMessage());
        }
    }
}

// Pagination
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 10;
$offset = ($page - 1) * $per_page;

$user_id = $_SESSION['user_id'];

// Count total
$total_stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications 
    WHERE (target = 'all' 
        OR (target = 'user' AND target_id = ?)
        OR (target = 'department' AND target_id IN (SELECT department_id FROM users WHERE id = ?))
        OR (target = 'job' AND target_id IN (SELECT job_id FROM applications WHERE user_id = ?)))");
$total_stmt->execute([$user_id, $user_id, $user_id]);
$total_notifications = $total_stmt->fetchColumn();
$total_pages = ceil($total_notifications / $per_page);

// Get notifications
$stmt = $pdo->prepare("SELECT n.*, u.username as creator_name 
                      FROM notifications n 
                      LEFT JOIN users u ON n.created_by = u.id 
                      WHERE (n.target = 'all' 
                          OR (n.target = 'user' AND n.target_id = ?)
                          OR (n.target = 'department' AND n.target_id IN (SELECT department_id FROM users WHERE id = ?))
                          OR (n.target = 'job' AND n.target_id IN (SELECT job_id FROM applications WHERE user_id = ?)))
                      ORDER BY n.created_at DESC 
                      LIMIT ? OFFSET ?");
$stmt->execute([$user_id, $user_id, $user_id, $per_page, $offset]);
$notifications = $stmt->fetchAll();

// Count unread for badge
$unread_stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications 
    WHERE status = 'unread' 
    AND (target = 'all' 
        OR (target = 'user' AND target_id = ?)
        OR (target = 'department' AND target_id IN (SELECT department_id FROM users WHERE id = ?))
        OR (target = 'job' AND target_id IN (SELECT job_id FROM applications WHERE user_id = ?)))");
$unread_stmt->execute([$user_id, $user_id, $user_id]);
$unread_count = $unread_stmt->fetchColumn();

include __DIR__ . '/../includes/header.php';
?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-3">
            <?php include __DIR__ . '/../includes/applicant_sidebar.php'; ?>
        </div>

        <div class="col-md-9">
            <div class="card dashboard-card">
                <div class="card-header bg-white d-flex justify-content-between align-items-center">
                    <h4 class="mb-0">
                        <i class="fas fa-bell me-2 text-primary"></i>Notifications
                        <?php if ($unread_count > 0): ?>
                            <span class="badge bg-danger ms-2"><?php echo $unread_count; ?> new</span>
                        <?php endif; ?>
                    </h4>
                    <?php if ($total_notifications > 0): ?>
                        <form method="post" class="d-inline">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            <button type="submit" name="mark_as_read" class="btn btn-sm btn-outline-primary">
                                <i class="fas fa-check-double me-1"></i> Mark all read
                            </button>
                        </form>
                    <?php endif; ?>
                </div>
                <div class="card-body">
                    <?php if ($success): ?>
                        <div class="alert alert-success alert-dismissible fade show">
                            <i class="fas fa-check-circle me-2"></i><?php echo htmlspecialchars($success); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>
                    <?php if ($error): ?>
                        <div class="alert alert-danger alert-dismissible fade show">
                            <i class="fas fa-exclamation-circle me-2"></i><?php echo htmlspecialchars($error); ?>
                            <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                        </div>
                    <?php endif; ?>

                    <?php if (empty($notifications)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-bell-slash fa-3x text-muted mb-3"></i>
                            <h5 class="text-muted">No notifications yet</h5>
                            <p class="text-muted">You'll see notifications from admin here.</p>
                        </div>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach ($notifications as $n): ?>
                                <div class="list-group-item <?php echo $n['status'] === 'unread' ? 'border-start border-primary border-4 bg-light' : ''; ?>">
                                    <div class="d-flex justify-content-between align-items-start">
                                        <div class="flex-grow-1">
                                            <div class="d-flex align-items-center mb-1">
                                                <?php
                                                $type_icons = [
                                                    'info' => ['icon' => 'fas fa-info-circle', 'color' => 'text-primary'],
                                                    'warning' => ['icon' => 'fas fa-exclamation-triangle', 'color' => 'text-warning'],
                                                    'success' => ['icon' => 'fas fa-check-circle', 'color' => 'text-success'],
                                                    'error' => ['icon' => 'fas fa-times-circle', 'color' => 'text-danger'],
                                                ];
                                                $t = $type_icons[$n['type']] ?? $type_icons['info'];
                                                ?>
                                                <i class="<?php echo $t['icon']; ?> <?php echo $t['color']; ?> me-2"></i>
                                                <h6 class="mb-0 fw-bold"><?php echo htmlspecialchars($n['title']); ?></h6>
                                                <?php if ($n['status'] === 'unread'): ?>
                                                    <span class="badge bg-primary ms-2">New</span>
                                                <?php endif; ?>
                                            </div>
                                            <p class="mb-1 text-muted"><?php echo nl2br(htmlspecialchars($n['message'])); ?></p>
                                            <small class="text-muted">
                                                <i class="fas fa-clock me-1"></i><?php echo time_elapsed_string($n['created_at']); ?>
                                                <?php if (!empty($n['creator_name'])): ?>
                                                    &middot; <i class="fas fa-user me-1"></i><?php echo htmlspecialchars($n['creator_name']); ?>
                                                <?php endif; ?>
                                            </small>
                                        </div>
                                    </div>
                                </div>
                            <?php endforeach; ?>
                        </div>

                        <?php if ($total_pages > 1): ?>
                            <nav class="mt-3">
                                <ul class="pagination justify-content-center mb-0">
                                    <?php if ($page > 1): ?>
                                        <li class="page-item"><a class="page-link" href="?page=<?php echo $page - 1; ?>">&laquo;</a></li>
                                    <?php endif; ?>
                                    <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                        <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                            <a class="page-link" href="?page=<?php echo $i; ?>"><?php echo $i; ?></a>
                                        </li>
                                    <?php endfor; ?>
                                    <?php if ($page < $total_pages): ?>
                                        <li class="page-item"><a class="page-link" href="?page=<?php echo $page + 1; ?>">&raquo;</a></li>
                                    <?php endif; ?>
                                </ul>
                            </nav>
                        <?php endif; ?>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
<?php prevent_back_navigation(); ?>
