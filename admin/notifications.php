<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/security.php';

require_role('admin', '../login.php');
set_security_headers();

// Handle notification actions
$action = isset($_GET['action']) ? $_GET['action'] : '';
$notification_id = isset($_GET['id']) ? (int)$_GET['id'] : 0;

// Handle form submissions
if ($_SERVER['REQUEST_METHOD'] === 'POST') {
    if (!isset($_POST['csrf_token']) || !verify_csrf_token($_POST['csrf_token'])) {
        $_SESSION['error_message'] = "Security token validation failed.";
        header('Location: notifications.php');
        exit();
    }
    $title = sanitize($_POST['title']);
    $message = sanitize($_POST['message']);
    $type = sanitize($_POST['type']);
    $target = sanitize($_POST['target']);
    $target_id = null;
    if (!empty($_POST['target_id'])) {
        $parts = explode('_', $_POST['target_id']);
        $target_id = isset($parts[1]) ? (int)$parts[1] : null;
    }

    $stmt = $pdo->prepare("INSERT INTO notifications (title, message, type, target, target_id, created_by, status) VALUES (?, ?, ?, ?, ?, ?, 'unread')");
    $stmt->execute([$title, $message, $type, $target, $target_id, $_SESSION['user_id']]);

    $_SESSION['success_message'] = "Notification sent successfully.";
    header('Location: notifications.php');
    exit();
}

// Handle deletion
if ($action === 'delete' && $notification_id > 0) {
    if (!isset($_GET['csrf_token']) || !verify_csrf_token($_GET['csrf_token'])) {
        $_SESSION['error_message'] = "Security token validation failed.";
        header('Location: notifications.php');
        exit();
    }
    $stmt = $pdo->prepare("DELETE FROM notifications WHERE id = ?");
    $stmt->execute([$notification_id]);
    $_SESSION['success_message'] = "Notification deleted.";
    header('Location: notifications.php');
    exit();
}

// Get notifications with type filter
$type_filter = $_GET['type'] ?? '';
$query = "SELECT n.*, u.username as created_by_name FROM notifications n LEFT JOIN users u ON n.created_by = u.id";
$params = [];
if ($type_filter && in_array($type_filter, ['info', 'warning', 'success', 'error'])) {
    $query .= " WHERE n.type = ?";
    $params[] = $type_filter;
}
$notif_stmt = $pdo->prepare($query . " ORDER BY n.created_at DESC");
$notif_stmt->execute($params);
$notifications = $notif_stmt->fetchAll();

// Preload target names to avoid N+1 queries
$user_ids = array_filter(array_unique(array_map(fn($n) => $n['target'] === 'user' ? (int)$n['target_id'] : 0, $notifications)));
$dept_ids = array_filter(array_unique(array_map(fn($n) => $n['target'] === 'department' ? (int)$n['target_id'] : 0, $notifications)));
$job_ids  = array_filter(array_unique(array_map(fn($n) => $n['target'] === 'job' ? (int)$n['target_id'] : 0, $notifications)));

$user_map = [];
if ($user_ids) {
    $u = $pdo->prepare("SELECT id, username FROM users WHERE id IN (" . implode(',', $user_ids) . ")");
    $u->execute();
    foreach ($u->fetchAll() as $r) $user_map[$r['id']] = $r['username'];
}
$dept_map = [];
if ($dept_ids) {
    $d = $pdo->prepare("SELECT id, name FROM departments WHERE id IN (" . implode(',', $dept_ids) . ")");
    $d->execute();
    foreach ($d->fetchAll() as $r) $dept_map[$r['id']] = $r['name'];
}
$job_map = [];
if ($job_ids) {
    $j = $pdo->prepare("SELECT id, title FROM jobs WHERE id IN (" . implode(',', $job_ids) . ")");
    $j->execute();
    foreach ($j->fetchAll() as $r) $job_map[$r['id']] = $r['title'];
}

// Get data for create form dropdowns
$users = $pdo->query("SELECT id, username, role FROM users WHERE role != 'admin' ORDER BY role, username")->fetchAll();
$jobs = $pdo->query("SELECT id, title FROM jobs WHERE status = 'approved' ORDER BY title")->fetchAll();
$departments = $pdo->query("SELECT id, name FROM departments ORDER BY name")->fetchAll();

function resolveTarget($n, $user_map, $dept_map, $job_map) {
    return match($n['target']) {
        'all' => 'All Users',
        'user' => 'User: ' . ($user_map[(int)$n['target_id']] ?? 'Unknown'),
        'department' => 'Dept: ' . ($dept_map[(int)$n['target_id']] ?? 'Unknown'),
        'job' => 'Job: ' . ($job_map[(int)$n['target_id']] ?? 'Unknown'),
        default => $n['target'],
    };
}
?>

<?php include '../includes/header.php'; ?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-3">
            <?php include '../includes/admin_sidebar.php'; ?>
        </div>

        <div class="col-md-9">
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success alert-dismissible fade show">
                    <?= $_SESSION['success_message'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['success_message']); ?>
            <?php endif; ?>
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger alert-dismissible fade show">
                    <?= $_SESSION['error_message'] ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert"></button>
                </div>
                <?php unset($_SESSION['error_message']); ?>
            <?php endif; ?>

            <!-- Notifications List -->
            <div class="card mb-4">
                <div class="card-header d-flex justify-content-between align-items-center">
                    <h5 class="mb-0"><i class="fas fa-bell me-2"></i>Notifications</h5>
                    <div class="d-flex gap-2">
                        <div class="btn-group btn-group-sm">
                            <a href="notifications.php" class="btn btn-outline-secondary <?= !$type_filter ? 'active' : '' ?>">All</a>
                            <a href="notifications.php?type=info" class="btn btn-outline-info <?= $type_filter === 'info' ? 'active' : '' ?>">Info</a>
                            <a href="notifications.php?type=warning" class="btn btn-outline-warning <?= $type_filter === 'warning' ? 'active' : '' ?>">Warning</a>
                            <a href="notifications.php?type=success" class="btn btn-outline-success <?= $type_filter === 'success' ? 'active' : '' ?>">Success</a>
                            <a href="notifications.php?type=error" class="btn btn-outline-danger <?= $type_filter === 'error' ? 'active' : '' ?>">Error</a>
                        </div>
                        <button class="btn btn-success btn-sm" type="button" data-bs-toggle="collapse" data-bs-target="#createForm">
                            <i class="fas fa-plus me-1"></i>New
                        </button>
                    </div>
                </div>
                <div class="card-body p-0">
                    <?php if (empty($notifications)): ?>
                        <div class="text-center py-5 text-muted">
                            <i class="fas fa-bell-slash fa-2x mb-2"></i>
                            <p>No notifications found.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle mb-0">
                                <thead class="table-light">
                                    <tr>
                                        <th>Title</th>
                                        <th>Message</th>
                                        <th>Type</th>
                                        <th>Target</th>
                                        <th>By</th>
                                        <th>When</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($notifications as $n): ?>
                                        <tr>
                                            <td class="fw-bold"><?= htmlspecialchars($n['title']) ?></td>
                                            <td class="text-muted" style="max-width:250px; overflow:hidden; text-overflow:ellipsis; white-space:nowrap;">
                                                <?= htmlspecialchars($n['message']) ?>
                                            </td>
                                            <td>
                                                <?php
                                                $badge = match($n['type']) {
                                                    'info' => 'info',
                                                    'warning' => 'warning',
                                                    'success' => 'success',
                                                    'error' => 'danger',
                                                    default => 'secondary'
                                                };
                                                ?>
                                                <span class="badge bg-<?= $badge ?>"><?= ucfirst($n['type']) ?></span>
                                            </td>
                                            <td><small><?= resolveTarget($n, $user_map, $dept_map, $job_map) ?></small></td>
                                            <td><small><?= htmlspecialchars($n['created_by_name']) ?></small></td>
                                            <td><small><?= date('M j, g:i A', strtotime($n['created_at'])) ?></small></td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <button class="btn btn-outline-primary" data-bs-toggle="modal" data-bs-target="#viewModal<?= $n['id'] ?>" title="View">
                                                        <i class="fas fa-eye"></i>
                                                    </button>
                                                    <a href="notifications.php?action=delete&id=<?= $n['id'] ?>&csrf_token=<?= urlencode(generate_csrf_token()) ?>"
                                                       class="btn btn-outline-danger" title="Delete"
                                                       onclick="return confirm('Delete this notification?')">
                                                        <i class="fas fa-trash"></i>
                                                    </a>
                                                </div>
                                            </td>
                                        </tr>

                                        <!-- View Modal -->
                                        <div class="modal fade" id="viewModal<?= $n['id'] ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title"><?= htmlspecialchars($n['title']) ?></h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <p><?= nl2br(htmlspecialchars($n['message'])) ?></p>
                                                        <hr>
                                                        <div class="row text-sm">
                                                            <div class="col-6"><strong>Type:</strong> <?= ucfirst($n['type']) ?></div>
                                                            <div class="col-6"><strong>Target:</strong> <?= resolveTarget($n, $user_map, $dept_map, $job_map) ?></div>
                                                            <div class="col-6"><strong>From:</strong> <?= htmlspecialchars($n['created_by_name']) ?></div>
                                                            <div class="col-6"><strong>Date:</strong> <?= date('M j, Y g:i A', strtotime($n['created_at'])) ?></div>
                                                        </div>
                                                    </div>
                                                    <div class="modal-footer">
                                                        <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Close</button>
                                                    </div>
                                                </div>
                                            </div>
                                        </div>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                        </div>
                    <?php endif; ?>
                </div>
            </div>

            <!-- Create Notification (Collapsible) -->
            <div class="collapse" id="createForm">
                <div class="card">
                    <div class="card-header">
                        <h6 class="mb-0"><i class="fas fa-paper-plane me-2"></i>Send New Notification</h6>
                    </div>
                    <div class="card-body">
                        <form method="POST">
                            <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                            <div class="row">
                                <div class="col-md-8 mb-3">
                                    <label class="form-label">Title *</label>
                                    <input type="text" class="form-control" name="title" required>
                                </div>
                                <div class="col-md-4 mb-3">
                                    <label class="form-label">Type *</label>
                                    <select class="form-select" name="type" required>
                                        <option value="info">Info</option>
                                        <option value="warning">Warning</option>
                                        <option value="success">Success</option>
                                        <option value="error">Error</option>
                                    </select>
                                </div>
                            </div>
                            <div class="mb-3">
                                <label class="form-label">Message *</label>
                                <textarea class="form-control" name="message" rows="3" required></textarea>
                            </div>
                            <div class="row">
                                <div class="col-md-6 mb-3">
                                    <label class="form-label">Send To *</label>
                                    <select class="form-select" name="target" id="target" required>
                                        <option value="all">All Users</option>
                                        <option value="user">Specific User</option>
                                        <option value="department">Department</option>
                                        <option value="job">Job Applicants</option>
                                    </select>
                                </div>
                                <div class="col-md-6 mb-3" id="target_id_wrap">
                                    <label class="form-label">Select Recipient</label>
                                    <select class="form-select" id="target_id_user" name="target_id" style="display:none;">
                                        <option value="">-- Select User --</option>
                                        <?php foreach ($users as $u): ?>
                                            <option value="user_<?= $u['id'] ?>"><?= htmlspecialchars($u['username']) ?> (<?= ucfirst($u['role']) ?>)</option>
                                        <?php endforeach; ?>
                                    </select>
                                    <select class="form-select" id="target_id_dept" name="target_id" style="display:none;">
                                        <option value="">-- Select Department --</option>
                                        <?php foreach ($departments as $d): ?>
                                            <option value="dept_<?= $d['id'] ?>"><?= htmlspecialchars($d['name']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <select class="form-select" id="target_id_job" name="target_id" style="display:none;">
                                        <option value="">-- Select Job --</option>
                                        <?php foreach ($jobs as $j): ?>
                                            <option value="job_<?= $j['id'] ?>"><?= htmlspecialchars($j['title']) ?></option>
                                        <?php endforeach; ?>
                                    </select>
                                    <small class="text-muted" id="target_id_hint">Select a send-to option first</small>
                                </div>
                            </div>
                            <button type="submit" class="btn btn-success">
                                <i class="fas fa-paper-plane me-1"></i>Send Notification
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>

<script>
document.addEventListener('DOMContentLoaded', function() {
    var selects = {
        user: document.getElementById('target_id_user'),
        department: document.getElementById('target_id_dept'),
        job: document.getElementById('target_id_job')
    };
    var hint = document.getElementById('target_id_hint');
    var target = document.getElementById('target');

    function updateTargetId() {
        var val = target.value;
        for (var k in selects) {
            selects[k].style.display = 'none';
            selects[k].removeAttribute('name');
        }
        if (selects[val]) {
            selects[val].style.display = '';
            selects[val].setAttribute('name', 'target_id');
            hint.style.display = 'none';
        } else {
            hint.style.display = '';
        }
    }

    target.addEventListener('change', updateTargetId);
    updateTargetId();
});
</script>
