<?php
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/security.php';

init_secure_session();
set_security_headers();
require_role('admin', '../login.php');

// System info
$php_version = phpversion();
$mysql_version = $pdo->query("SELECT VERSION()")->fetchColumn();
$server_software = $_SERVER['SERVER_SOFTWARE'] ?? 'Unknown';
$os = php_uname('s') . ' ' . php_uname('r') . ' ' . php_uname('m');
$document_root = $_SERVER['DOCUMENT_ROOT'] ?? 'Unknown';
$server_ip = $_SERVER['SERVER_ADDR'] ?? '127.0.0.1';

// PHP settings
$upload_max = ini_get('upload_max_filesize');
$post_max = ini_get('post_max_size');
$memory_limit = ini_get('memory_limit');
$max_exec = ini_get('max_execution_time');
$display_errors = ini_get('display_errors');
$timezone = date_default_timezone_get();
$session_path = session_save_path();

// Database info
$db_name = $pdo->query("SELECT DATABASE()")->fetchColumn();
$db_size = $pdo->query("SELECT ROUND(SUM(DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024, 2) FROM information_schema.TABLES WHERE TABLE_SCHEMA = '$db_name'")->fetchColumn();

// Table sizes (no rows to avoid MariaDB compatibility issues)
$tables = $pdo->query("
    SELECT TABLE_NAME AS name, 
           ROUND((DATA_LENGTH + INDEX_LENGTH) / 1024 / 1024, 2) AS size_mb
    FROM information_schema.TABLES 
    WHERE TABLE_SCHEMA = '$db_name' 
    ORDER BY DATA_LENGTH + INDEX_LENGTH DESC
")->fetchAll();

// User counts by role
$role_counts = $pdo->query("SELECT role, COUNT(*) as count FROM users GROUP BY role ORDER BY count DESC")->fetchAll(PDO::FETCH_KEY_PAIR);

// Job stats
$job_stats = $pdo->query("SELECT status, COUNT(*) as count FROM jobs GROUP BY status ORDER BY count DESC")->fetchAll(PDO::FETCH_KEY_PAIR);

// Application stats
$app_stats = $pdo->query("SELECT status, COUNT(*) as count FROM centralized_applications GROUP BY status ORDER BY count DESC")->fetchAll(PDO::FETCH_KEY_PAIR);

// Disk usage (best effort)
$disk_total = @disk_total_space('/');
$disk_free = @disk_free_space('/');
$disk_used = $disk_total ? round(($disk_total - $disk_free) / 1024 / 1024 / 1024, 2) : 'N/A';
$disk_total_gb = $disk_total ? round($disk_total / 1024 / 1024 / 1024, 2) : 'N/A';
$disk_free_gb = $disk_free ? round($disk_free / 1024 / 1024 / 1024, 2) : 'N/A';
$disk_percent = $disk_total ? round((($disk_total - $disk_free) / $disk_total) * 100, 1) : 0;

// Uploads folder size
$uploads_path = __DIR__ . '/../uploads';
$uploads_size = 'N/A';
$uploads_count = 0;
if (is_dir($uploads_path)) {
    $rii = new RecursiveIteratorIterator(new RecursiveDirectoryIterator($uploads_path));
    $files = iterator_to_array($rii, false);
    $uploads_count = count($files);
    $total_bytes = 0;
    foreach ($files as $file) {
        if ($file->isFile()) {
            $total_bytes += $file->getSize();
        }
    }
    $uploads_size = round($total_bytes / 1024 / 1024, 2) . ' MB';
}

// Extensions
$extensions = get_loaded_extensions();
$important_exts = ['openssl', 'curl', 'mbstring', 'json', 'pdo_mysql', 'gd', 'zip', 'xml', 'bcmath', 'intl'];

// Check maintenance mode
$maintenance = $pdo->query("SELECT setting_value FROM settings WHERE setting_name = 'maintenance_mode'")->fetchColumn();
$is_maintenance = $maintenance === '1';
?>

<?php include '../includes/header.php'; ?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-3">
            <?php include '../includes/admin_sidebar.php'; ?>
        </div>

        <div class="col-md-9">
            <h2 class="mb-4"><i class="fas fa-heartbeat me-2 text-success"></i>System Health</h2>

            <!-- Server Info -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header bg-primary text-white"><i class="fas fa-server me-1"></i> Server Information</div>
                        <div class="card-body">
                            <table class="table table-sm mb-0">
                                <tr><td><strong>OS</strong></td><td><?php echo htmlspecialchars($os); ?></td></tr>
                                <tr><td><strong>Server</strong></td><td><?php echo htmlspecialchars($server_software); ?></td></tr>
                                <tr><td><strong>Server IP</strong></td><td><?php echo htmlspecialchars($server_ip); ?></td></tr>
                                <tr><td><strong>Document Root</strong></td><td><?php echo htmlspecialchars($document_root); ?></td></tr>
                                <tr><td><strong>PHP Version</strong></td><td><?php echo $php_version; ?></td></tr>
                                <tr><td><strong>MySQL Version</strong></td><td><?php echo htmlspecialchars($mysql_version); ?></td></tr>
                            </table>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header bg-info text-white"><i class="fas fa-cog me-1"></i> PHP Configuration</div>
                        <div class="card-body">
                            <table class="table table-sm mb-0">
                                <tr><td><strong>Upload Max</strong></td><td><?php echo $upload_max; ?></td></tr>
                                <tr><td><strong>Post Max</strong></td><td><?php echo $post_max; ?></td></tr>
                                <tr><td><strong>Memory Limit</strong></td><td><?php echo $memory_limit; ?></td></tr>
                                <tr><td><strong>Max Execution</strong></td><td><?php echo $max_exec; ?>s</td></tr>
                                <tr><td><strong>Timezone</strong></td><td><?php echo htmlspecialchars($timezone); ?></td></tr>
                                <tr><td><strong>Session Path</strong></td><td><?php echo htmlspecialchars($session_path); ?></td></tr>
                                <tr>
                                    <td><strong>Display Errors</strong></td>
                                    <td>
                                        <?php if ($display_errors): ?>
                                            <span class="badge bg-warning"><?php echo $display_errors; ?></span>
                                        <?php else: ?>
                                            <span class="badge bg-success">Off</span>
                                        <?php endif; ?>
                                    </td>
                                </tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Status Banner -->
            <div class="alert <?php echo $is_maintenance ? 'alert-danger' : 'alert-success'; ?> d-flex align-items-center mb-4">
                <i class="fas fa-<?php echo $is_maintenance ? 'exclamation-triangle' : 'check-circle'; ?> me-2 fa-lg"></i>
                <strong class="me-2">System Status:</strong>
                <?php echo $is_maintenance ? 'MAINTENANCE MODE ACTIVE' : 'All Systems Operational'; ?>
            </div>

            <!-- Disk & Database -->
            <div class="row mb-4">
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header bg-warning text-dark"><i class="fas fa-hdd me-1"></i> Disk Usage</div>
                        <div class="card-body">
                            <?php if ($disk_total): ?>
                                <div class="progress mb-3" style="height: 30px;">
                                    <div class="progress-bar bg-<?php echo $disk_percent > 90 ? 'danger' : ($disk_percent > 70 ? 'warning' : 'success'); ?>" 
                                         style="width: <?php echo $disk_percent; ?>%">
                                        <?php echo $disk_percent; ?>%
                                    </div>
                                </div>
                                <table class="table table-sm mb-0">
                                    <tr><td><strong>Total</strong></td><td><?php echo $disk_total_gb; ?> GB</td></tr>
                                    <tr><td><strong>Used</strong></td><td><?php echo $disk_used; ?> GB</td></tr>
                                    <tr><td><strong>Free</strong></td><td><?php echo $disk_free_gb; ?> GB</td></tr>
                                </table>
                            <?php else: ?>
                                <p class="text-muted">Disk info not available on this server.</p>
                            <?php endif; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-6">
                    <div class="card h-100">
                        <div class="card-header bg-success text-white"><i class="fas fa-database me-1"></i> Database</div>
                        <div class="card-body">
                            <table class="table table-sm mb-0">
                                <tr><td><strong>Database</strong></td><td><?php echo htmlspecialchars($db_name); ?></td></tr>
                                <tr><td><strong>Size</strong></td><td><?php echo $db_size; ?> MB</td></tr>
                                <tr><td><strong>Uploads Folder</strong></td><td><?php echo $uploads_size; ?> (<?php echo $uploads_count; ?> files)</td></tr>
                            </table>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Database Tables -->
            <div class="card mb-4">
                <div class="card-header"><i class="fas fa-table me-1"></i> Database Tables</div>
                <div class="card-body">
                    <div class="table-responsive">
                            <table class="table table-sm table-hover">
                                <thead>
                                    <tr><th>Table</th><th>Size (MB)</th></tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($tables as $table): ?>
                                        <tr>
                                            <td><code><?php echo htmlspecialchars($table['name']); ?></code></td>
                                            <td><?php echo $table['size_mb']; ?></td>
                                        </tr>
                                    <?php endforeach; ?>
                                </tbody>
                            </table>
                    </div>
                </div>
            </div>

            <!-- Data Breakdown -->
            <div class="row mb-4">
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-header"><i class="fas fa-users me-1"></i> Users by Role</div>
                        <div class="card-body">
                            <?php foreach ($role_counts as $role => $count): ?>
                                <div class="d-flex justify-content-between mb-2">
                                    <span><?php echo ucfirst($role); ?></span>
                                    <span class="badge bg-primary"><?php echo number_format($count); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-header"><i class="fas fa-briefcase me-1"></i> Jobs by Status</div>
                        <div class="card-body">
                            <?php foreach ($job_stats as $status => $count): ?>
                                <div class="d-flex justify-content-between mb-2">
                                    <span><?php echo ucfirst(str_replace('_', ' ', $status)); ?></span>
                                    <span class="badge bg-info"><?php echo number_format($count); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
                <div class="col-md-4">
                    <div class="card h-100">
                        <div class="card-header"><i class="fas fa-file-alt me-1"></i> Applications by Status</div>
                        <div class="card-body">
                            <?php foreach ($app_stats as $status => $count): ?>
                                <div class="d-flex justify-content-between mb-2">
                                    <span><?php echo ucfirst(str_replace('_', ' ', $status)); ?></span>
                                    <span class="badge bg-success"><?php echo number_format($count); ?></span>
                                </div>
                            <?php endforeach; ?>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Extensions -->
            <div class="card mb-4">
                <div class="card-header"><i class="fas fa-puzzle-piece me-1"></i> PHP Extensions</div>
                <div class="card-body">
                    <div class="row">
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2">Required Extensions</h6>
                            <?php foreach ($important_exts as $ext): ?>
                                <span class="badge bg-<?php echo extension_loaded($ext) ? 'success' : 'danger'; ?> me-1 mb-1">
                                    <?php echo $ext; ?>: <?php echo extension_loaded($ext) ? 'OK' : 'Missing'; ?>
                                </span>
                            <?php endforeach; ?>
                        </div>
                        <div class="col-md-6">
                            <h6 class="text-muted mb-2">All Loaded (<?php echo count($extensions); ?>)</h6>
                            <div style="max-height:200px; overflow-y:auto;">
                                <?php foreach ($extensions as $ext): ?>
                                    <span class="badge bg-light text-dark me-1 mb-1"><?php echo $ext; ?></span>
                                <?php endforeach; ?>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include '../includes/footer.php'; ?>
