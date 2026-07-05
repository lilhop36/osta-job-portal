<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../vendor/autoload.php';

use App\Models\Interview;

require_role('applicant', '../login.php');

$userId = (int) $_SESSION['user_id'];
$interviewModel = new Interview();

// Get user's applications
$stmt = $pdo->prepare("SELECT id FROM centralized_applications WHERE user_id = ?");
$stmt->execute([$userId]);
$appIds = array_column($stmt->fetchAll(), 'id');

$interviews = [];
if (!empty($appIds)) {
    $placeholders = implode(',', array_fill(0, count($appIds), '?'));
    $stmt = $pdo->prepare("
        SELECT i.*, 
               ca.first_name, ca.last_name, ca.application_number,
               u.username as interviewer_name,
               it.name as type_name
        FROM interviews i
        LEFT JOIN centralized_applications ca ON i.application_id = ca.id
        LEFT JOIN users u ON i.primary_interviewer_id = u.id
        LEFT JOIN interview_types it ON i.interview_type_id = it.id
        WHERE i.application_id IN ({$placeholders})
        ORDER BY i.scheduled_date DESC, i.start_time DESC
    ");
    $stmt->execute($appIds);
    $interviews = $stmt->fetchAll();
}

$pageTitle = 'My Interviews';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0"><i class="fas fa-calendar-check me-2" style="color: var(--osta-green);"></i>My Interviews</h4>
        <a href="dashboard.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>Dashboard</a>
    </div>

    <?php if (empty($interviews)): ?>
        <div class="text-center py-5">
            <i class="fas fa-calendar-times fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">No interviews scheduled</h5>
            <p class="text-muted">Your scheduled interviews will appear here.</p>
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($interviews as $interview): ?>
            <div class="col-md-6">
                <div class="card border-0 shadow-sm" style="border-radius: 12px; border-left: 4px solid <?php echo match($interview['status']) {
                    'scheduled' => '#ffc107',
                    'completed' => '#28a745',
                    'cancelled' => '#dc3545',
                    default => '#6c757d'
                }; ?>;">
                    <div class="card-body">
                        <div class="d-flex justify-content-between align-items-start mb-2">
                            <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($interview['interview_code'] ?? 'N/A'); ?></h6>
                            <span class="badge bg-<?php echo match($interview['status']) {
                                'scheduled' => 'warning',
                                'completed' => 'success',
                                'cancelled' => 'danger',
                                'rescheduled' => 'info',
                                default => 'secondary'
                            }; ?>"><?php echo ucfirst($interview['status']); ?></span>
                        </div>

                        <div class="small text-muted mb-2">
                            <div><i class="fas fa-calendar me-1"></i><?php echo date('l, M d, Y', strtotime($interview['scheduled_date'])); ?></div>
                            <div><i class="fas fa-clock me-1"></i><?php echo date('h:i A', strtotime($interview['start_time'])); ?> (<?php echo $interview['duration_minutes'] ?? 60; ?> min)</div>
                            <div><i class="fas fa-user-tie me-1"></i><?php echo htmlspecialchars($interview['interviewer_name'] ?? 'TBD'); ?></div>
                            <?php if (!empty($interview['type_name'])): ?>
                            <div><i class="fas fa-tag me-1"></i><?php echo htmlspecialchars($interview['type_name']); ?></div>
                            <?php endif; ?>
                        </div>

                        <?php if (!empty($interview['venue'])): ?>
                            <div class="small mb-2"><i class="fas fa-map-marker-alt me-1 text-danger"></i><?php echo htmlspecialchars($interview['venue']); ?></div>
                        <?php endif; ?>
                        <?php if (!empty($interview['meeting_link'])): ?>
                            <div class="small mb-2"><a href="<?php echo htmlspecialchars($interview['meeting_link']); ?>" target="_blank" class="text-primary"><i class="fas fa-video me-1"></i>Join Meeting</a></div>
                        <?php endif; ?>

                        <?php if ($interview['status'] === 'completed' && !empty($interview['feedback'])): ?>
                            <div class="bg-light rounded p-2 mt-2">
                                <small class="fw-semibold">Interview Feedback:</small>
                                <p class="small mb-0"><?php echo htmlspecialchars($interview['feedback']); ?></p>
                                <?php if (!empty($interview['overall_rating'])): ?>
                                    <div class="mt-1">
                                        <?php for ($i = 1; $i <= 5; $i++): ?>
                                            <i class="fas fa-star <?php echo $i <= $interview['overall_rating'] ? 'text-warning' : 'text-muted'; ?>"></i>
                                        <?php endfor; ?>
                                    </div>
                                <?php endif; ?>
                            </div>
                        <?php endif; ?>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
