<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/security.php';

init_secure_session();
set_security_headers();
require_role('admin', '../login.php');

// Handle export
if (isset($_GET['export']) && $_GET['export'] === 'csv') {
    $where = "WHERE 1=1";
    $params = [];

    if (!empty($_GET['action_filter'])) {
        $where .= " AND a.action LIKE ?";
        $params[] = '%' . $_GET['action_filter'] . '%';
    }
    if (!empty($_GET['user_filter'])) {
        $where .= " AND u.username LIKE ?";
        $params[] = '%' . $_GET['user_filter'] . '%';
    }
    if (!empty($_GET['date_from'])) {
        $where .= " AND a.created_at >= ?";
        $params[] = $_GET['date_from'] . ' 00:00:00';
    }
    if (!empty($_GET['date_to'])) {
        $where .= " AND a.created_at <= ?";
        $params[] = $_GET['date_to'] . ' 23:59:59';
    }

    header('Content-Type: text/csv');
    header('Content-Disposition: attachment; filename="audit_log_' . date('Y-m-d') . '.csv"');
    $output = fopen('php://output', 'w');
    fputcsv($output, ['ID', 'User', 'Action', 'Details', 'IP Address', 'Request URI', 'Timestamp']);

    $sql = "SELECT a.*, u.username 
            FROM audit_log a 
            LEFT JOIN users u ON a.user_id = u.id 
            $where 
            ORDER BY a.created_at DESC";
    $stmt = $pdo->prepare($sql);
    $stmt->execute($params);
    while ($row = $stmt->fetch(PDO::FETCH_ASSOC)) {
        fputcsv($output, [
            $row['id'],
            $row['username'] ?? 'System',
            $row['action'],
            $row['details'] ?? '',
            $row['ip_address'] ?? '',
            $row['request_uri'] ?? '',
            $row['created_at']
        ]);
    }
    fclose($output);
    exit();
}

// Filters
$action_filter = $_GET['action_filter'] ?? '';
$user_filter = $_GET['user_filter'] ?? '';
$date_from = $_GET['date_from'] ?? '';
$date_to = $_GET['date_to'] ?? '';
$page = max(1, (int)($_GET['page'] ?? 1));
$per_page = 25;
$offset = ($page - 1) * $per_page;

$where = "WHERE 1=1";
$params = [];

if ($action_filter) {
    $where .= " AND a.action LIKE ?";
    $params[] = '%' . $action_filter . '%';
}
if ($user_filter) {
    $where .= " AND u.username LIKE ?";
    $params[] = '%' . $user_filter . '%';
}
if ($date_from) {
    $where .= " AND a.created_at >= ?";
    $params[] = $date_from . ' 00:00:00';
}
if ($date_to) {
    $where .= " AND a.created_at <= ?";
    $params[] = $date_to . ' 23:59:59';
}

$count_stmt = $pdo->prepare("SELECT COUNT(*) as total FROM audit_log a LEFT JOIN users u ON a.user_id = u.id $where");
$count_stmt->execute($params);
$total = $count_stmt->fetch()['total'];
$total_pages = max(1, ceil($total / $per_page));

$stmt = $pdo->prepare("
    SELECT a.*, u.username 
    FROM audit_log a 
    LEFT JOIN users u ON a.user_id = u.id 
    $where 
    ORDER BY a.created_at DESC 
    LIMIT $per_page OFFSET $offset
");
$stmt->execute($params);
$logs = $stmt->fetchAll();

// Distinct actions for filter dropdown
$actions = $pdo->query("SELECT DISTINCT action FROM audit_log ORDER BY action")->fetchAll(PDO::FETCH_COLUMN);
?>

<?php include '../includes/header.php'; ?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-3">
            <?php include '../includes/admin_sidebar.php'; ?>
        </div>

        <div class="col-md-9">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2 class="mb-0"><i class="fas fa-shield-alt me-2 text-primary"></i>Audit Log</h2>
                <a href="?<?php echo http_build_query(array_merge($_GET, ['export' => 'csv'])); ?>" class="btn btn-success">
                    <i class="fas fa-download me-1"></i> Export CSV
                </a>
            </div>

            <!-- Filters -->
            <div class="card mb-4">
                <div class="card-body">
                    <form method="GET" class="row g-3">
                        <div class="col-md-3">
                            <label class="form-label">Action</label>
                            <select name="action_filter" class="form-select">
                                <option value="">All Actions</option>
                                <?php foreach ($actions as $act): ?>
                                    <option value="<?php echo htmlspecialchars($act); ?>" <?php echo $action_filter === $act ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($act); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">User</label>
                            <input type="text" name="user_filter" class="form-control" placeholder="Username..." value="<?php echo htmlspecialchars($user_filter); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">From</label>
                            <input type="date" name="date_from" class="form-control" value="<?php echo htmlspecialchars($date_from); ?>">
                        </div>
                        <div class="col-md-2">
                            <label class="form-label">To</label>
                            <input type="date" name="date_to" class="form-control" value="<?php echo htmlspecialchars($date_to); ?>">
                        </div>
                        <div class="col-md-2 d-flex align-items-end gap-2">
                            <button type="submit" class="btn btn-primary"><i class="fas fa-filter me-1"></i> Filter</button>
                            <a href="audit_log.php" class="btn btn-outline-secondary">Clear</a>
                        </div>
                    </form>
                </div>
            </div>

            <!-- Stats -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card text-center">
                        <div class="card-body">
                            <h3 class="text-primary"><?php echo number_format($total); ?></h3>
                            <p class="text-muted mb-0">Total Entries</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-center">
                        <div class="card-body">
                            <h3 class="text-success"><?php echo $page; ?> / <?php echo $total_pages; ?></h3>
                            <p class="text-muted mb-0">Current Page</p>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card text-center">
                        <div class="card-body">
                            <h3 class="text-info"><?php echo count($logs); ?></h3>
                            <p class="text-muted mb-0">Entries Shown</p>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Log Table -->
            <div class="card">
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover">
                            <thead>
                                <tr>
                                    <th>ID</th>
                                    <th>User</th>
                                    <th>Action</th>
                                    <th>Details</th>
                                    <th>IP Address</th>
                                    <th>URI</th>
                                    <th>Timestamp</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php if (empty($logs)): ?>
                                    <tr><td colspan="7" class="text-center text-muted py-4">No audit log entries found.</td></tr>
                                <?php else: ?>
                                    <?php foreach ($logs as $log): ?>
                                        <tr>
                                            <td><?php echo $log['id']; ?></td>
                                            <td><?php echo htmlspecialchars($log['username'] ?? 'System'); ?></td>
                                            <td>
                                                <?php
                                                $badge = 'secondary';
                                                if (stripos($log['action'], 'create') !== false || stripos($log['action'], 'insert') !== false) $badge = 'success';
                                                elseif (stripos($log['action'], 'update') !== false || stripos($log['action'], 'edit') !== false) $badge = 'warning';
                                                elseif (stripos($log['action'], 'delete') !== false) $badge = 'danger';
                                                elseif (stripos($log['action'], 'login') !== false) $badge = 'info';
                                                ?>
                                                <span class="badge bg-<?php echo $badge; ?>"><?php echo htmlspecialchars($log['action']); ?></span>
                                            </td>
                                            <td><?php echo htmlspecialchars(mb_strimwidth($log['details'] ?? '', 0, 80, '...')); ?></td>
                                            <td><code><?php echo htmlspecialchars($log['ip_address'] ?? ''); ?></code></td>
                                            <td><?php echo htmlspecialchars(mb_strimwidth($log['request_uri'] ?? '', 0, 40, '...')); ?></td>
                                            <td><?php echo date('M j, Y H:i:s', strtotime($log['created_at'])); ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                <?php endif; ?>
                            </tbody>
                        </table>
                    </div>

                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <nav class="mt-3">
                            <ul class="pagination justify-content-center">
                                <?php
                                $base_params = $_GET;
                                unset($base_params['page']);
                                $base_query = http_build_query($base_params);
                                ?>
                                <li class="page-item <?php echo $page <= 1 ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?<?php echo $base_query . ($base_query ? '&' : '') . 'page=' . ($page - 1); ?>">Previous</a>
                                </li>
                                <?php
                                $start = max(1, $page - 3);
                                $end = min($total_pages, $page + 3);
                                for ($i = $start; $i <= $end; $i++): ?>
                                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?<?php echo $base_query . ($base_query ? '&' : '') . 'page=' . $i; ?>"><?php echo $i; ?></a>
                                    </li>
                                <?php endfor; ?>
                                <li class="page-item <?php echo $page >= $total_pages ? 'disabled' : ''; ?>">
                                    <a class="page-link" href="?<?php echo $base_query . ($base_query ? '&' : '') . 'page=' . ($page + 1); ?>">Next</a>
                                </li>
                            </ul>
                        </nav>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
