<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\Notification;

require_role('applicant', '../login.php');

$userId = (int) $_SESSION['user_id'];
$notifModel = new Notification();

// Handle mark as read
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['error_message'] = 'Invalid security token.';
        header('Location: notifications_settings.php');
        exit;
    }

    if ($_POST['action'] === 'mark_all_read') {
        $notifModel->markAllRead($userId);
        $_SESSION['success_message'] = 'All notifications marked as read.';
    }

    header('Location: notifications_settings.php');
    exit;
}

$notifications = $notifModel->getForUser($userId, 50);
$unreadCount = $notifModel->countUnread($userId);

// Get current notification preferences (stored in settings table)
$stmt = $pdo->prepare("SELECT setting_value FROM settings WHERE setting_name = ?");
$stmt->execute(['notification_prefs_' . $userId]);
$prefs = $stmt->fetch();
$preferences = $prefs ? json_decode($prefs['setting_value'], true) : [
    'email_application_update' => true,
    'email_interview' => true,
    'email_messages' => true,
    'email_job_alerts' => true,
];

// Save preferences
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action']) && $_POST['action'] === 'save_prefs') {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['error_message'] = 'Invalid security token.';
        header('Location: notifications_settings.php');
        exit;
    }

    $newPrefs = [
        'email_application_update' => isset($_POST['email_application_update']),
        'email_interview'          => isset($_POST['email_interview']),
        'email_messages'           => isset($_POST['email_messages']),
        'email_job_alerts'         => isset($_POST['email_job_alerts']),
    ];

    $jsonPrefs = json_encode($newPrefs);
    $stmt = $pdo->prepare("INSERT INTO settings (setting_name, setting_value) VALUES (?, ?) ON DUPLICATE KEY UPDATE setting_value = ?");
    $stmt->execute(['notification_prefs_' . $userId, $jsonPrefs, $jsonPrefs]);

    $preferences = $newPrefs;
    $_SESSION['success_message'] = 'Notification preferences saved.';
    header('Location: notifications_settings.php');
    exit;
}

$pageTitle = 'Notification Settings';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0"><i class="fas fa-bell me-2" style="color: var(--osta-green);"></i>Notification Settings</h4>
        <a href="dashboard.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>Dashboard</a>
    </div>

    <div class="row g-4">
        <!-- Preferences -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm" style="border-radius: 12px;">
                <div class="card-body">
                    <h6 class="fw-bold mb-3">Email Notification Preferences</h6>
                    <form method="POST">
                        <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                        <input type="hidden" name="action" value="save_prefs">

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" name="email_application_update" id="appUpdate" <?php echo $preferences['email_application_update'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="appUpdate">Application Status Updates</label>
                            <small class="text-muted d-block">Get notified when your application status changes</small>
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" name="email_interview" id="interviews" <?php echo $preferences['email_interview'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="interviews">Interview Notifications</label>
                            <small class="text-muted d-block">Get notified about interview scheduling and updates</small>
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" name="email_messages" id="messages" <?php echo $preferences['email_messages'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="messages">Messages</label>
                            <small class="text-muted d-block">Get notified when you receive a new message</small>
                        </div>

                        <div class="form-check form-switch mb-3">
                            <input class="form-check-input" type="checkbox" name="email_job_alerts" id="jobAlerts" <?php echo $preferences['email_job_alerts'] ? 'checked' : ''; ?>>
                            <label class="form-check-label" for="jobAlerts">Job Alerts</label>
                            <small class="text-muted d-block">Receive job matching your saved preferences</small>
                        </div>

                        <button type="submit" class="btn w-100" style="background: var(--osta-green); color: white;">
                            <i class="fas fa-save me-1"></i>Save Preferences
                        </button>
                    </form>
                </div>
            </div>
        </div>

        <!-- Recent Notifications -->
        <div class="col-md-6">
            <div class="card border-0 shadow-sm" style="border-radius: 12px;">
                <div class="card-body">
                    <div class="d-flex justify-content-between align-items-center mb-3">
                        <h6 class="fw-bold mb-0">Recent Notifications</h6>
                        <?php if ($unreadCount > 0): ?>
                        <form method="POST" class="d-inline">
                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">
                            <input type="hidden" name="action" value="mark_all_read">
                            <button type="submit" class="btn btn-sm btn-outline-secondary">Mark all read</button>
                        </form>
                        <?php endif; ?>
                    </div>

                    <?php if (empty($notifications)): ?>
                        <p class="text-muted text-center py-3">No notifications yet.</p>
                    <?php else: ?>
                        <div class="list-group list-group-flush">
                            <?php foreach (array_slice($notifications, 0, 10) as $notif): ?>
                            <div class="list-group-item px-0 <?php echo $notif['status'] === 'unread' ? 'bg-light' : ''; ?>">
                                <div class="d-flex align-items-start">
                                    <div class="me-2 mt-1">
                                        <i class="fas fa-<?php echo match($notif['type']) {
                                            'success' => 'check-circle text-success',
                                            'warning' => 'exclamation-triangle text-warning',
                                            'error' => 'times-circle text-danger',
                                            default => 'info-circle text-info'
                                        }; ?>"></i>
                                    </div>
                                    <div class="flex-grow-1">
                                        <h6 class="mb-1 small fw-bold"><?php echo htmlspecialchars($notif['title']); ?></h6>
                                        <p class="mb-1 small text-muted"><?php echo htmlspecialchars($notif['message']); ?></p>
                                        <small class="text-muted"><?php echo date('M d, Y h:i A', strtotime($notif['created_at'])); ?></small>
                                    </div>
                                </div>
                            </div>
                            <?php endforeach; ?>
                        </div>
                    <?php endif; ?>
                </div>
            </div>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
