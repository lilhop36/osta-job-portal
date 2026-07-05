<?php
require_once __DIR__ . '/../config/database.php';
require_once __DIR__ . '/../includes/auth.php';
require_once __DIR__ . '/../includes/security.php';

require_role('employer', '../login.php');
set_security_headers();

$employer_id = (int)$_SESSION['user_id'];

// Handle status update (complete/cancel)
if ($_SERVER['REQUEST_METHOD'] === 'POST' && isset($_POST['action'])) {
    if (!verify_csrf_token($_POST['csrf_token'] ?? '')) {
        $_SESSION['error_message'] = 'Invalid security token.';
        header('Location: interviews.php');
        exit;
    }

    $interview_id = (int)$_POST['interview_id'];
    $action = $_POST['action'];

    // Verify this interview belongs to this employer
    $check = $pdo->prepare("SELECT id FROM interviews WHERE id = ? AND primary_interviewer_id = ?");
    $check->execute([$interview_id, $employer_id]);
    if ($check->fetch()) {
        $new_status = $action === 'complete' ? 'completed' : 'cancelled';
        $feedback = sanitize($_POST['feedback'] ?? '');
        $rating = !empty($_POST['rating']) ? (float)$_POST['rating'] : null;

        $stmt = $pdo->prepare("UPDATE interviews SET status = ?, feedback = ?, overall_rating = ?, updated_at = NOW() WHERE id = ?");
        $stmt->execute([$new_status, $feedback, $rating, $interview_id]);

        $_SESSION['success_message'] = 'Interview marked as ' . $new_status . '.';
    }

    header('Location: interviews.php');
    exit;
}

// Fetch interviews assigned to this employer
$stmt = $pdo->prepare("
    SELECT i.*, 
           ca.first_name, ca.last_name, ca.email as applicant_email,
           j.title as job_title
    FROM interviews i
    JOIN centralized_applications ca ON i.application_id = ca.id
    LEFT JOIN applications a ON a.user_id = ca.user_id
    LEFT JOIN jobs j ON a.job_id = j.id
    WHERE i.primary_interviewer_id = ?
    ORDER BY i.scheduled_date DESC, i.start_time DESC
");
$stmt->execute([$employer_id]);
$interviews = $stmt->fetchAll();

// Separate by status
$upcoming = array_filter($interviews, fn($i) => in_array($i['status'], ['scheduled', 'rescheduled']) && $i['scheduled_date'] >= date('Y-m-d'));
$completed = array_filter($interviews, fn($i) => $i['status'] === 'completed');
$cancelled = array_filter($interviews, fn($i) => $i['status'] === 'cancelled');
?>

<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="container-fluid mt-4">
    <div class="row">
        <div class="col-md-3">
            <?php include __DIR__ . '/../includes/employer_sidebar.php'; ?>
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

            <!-- Stats -->
            <div class="row mb-4">
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h3 class="text-primary"><?= count($upcoming) ?></h3>
                            <small class="text-muted">Upcoming</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h3 class="text-success"><?= count($completed) ?></h3>
                            <small class="text-muted">Completed</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h3 class="text-danger"><?= count($cancelled) ?></h3>
                            <small class="text-muted">Cancelled</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-3">
                    <div class="card text-center">
                        <div class="card-body">
                            <h3 class="text-info"><?= count($interviews) ?></h3>
                            <small class="text-muted">Total</small>
                        </div>
                    </div>
                </div>
            </div>

            <!-- Upcoming Interviews -->
            <div class="card mb-4">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-calendar-alt me-2"></i>Upcoming Interviews</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($upcoming)): ?>
                        <div class="text-center py-4 text-muted">
                            <i class="fas fa-calendar-check fa-2x mb-2"></i>
                            <p>No upcoming interviews.</p>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover align-middle">
                                <thead class="table-light">
                                    <tr>
                                        <th>Code</th>
                                        <th>Candidate</th>
                                        <th>Job</th>
                                        <th>Type</th>
                                        <th>Date & Time</th>
                                        <th>Venue</th>
                                        <th></th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($upcoming as $int): ?>
                                        <tr>
                                            <td><span class="badge bg-primary"><?= htmlspecialchars($int['interview_code'] ?? 'N/A') ?></span></td>
                                            <td>
                                                <div class="fw-semibold"><?= htmlspecialchars($int['first_name'] . ' ' . $int['last_name']) ?></div>
                                                <small class="text-muted"><?= htmlspecialchars($int['applicant_email'] ?? '') ?></small>
                                            </td>
                                            <td><small><?= htmlspecialchars($int['job_title'] ?? 'N/A') ?></small></td>
                                            <td><span class="badge bg-info"><?= ucfirst(str_replace('_', ' ', $int['interview_type'])) ?></span></td>
                                            <td>
                                                <div class="fw-semibold"><?= date('M d, Y', strtotime($int['scheduled_date'])) ?></div>
                                                <small class="text-muted"><?= date('h:i A', strtotime($int['start_time'])) ?> (<?= $int['duration_minutes'] ?>min)</small>
                                            </td>
                                            <td><small><?= htmlspecialchars($int['venue'] ?: ($int['meeting_link'] ?? 'TBD')) ?></small></td>
                                            <td>
                                                <button class="btn btn-sm btn-success" data-bs-toggle="modal" data-bs-target="#completeModal<?= $int['id'] ?>" title="Complete">
                                                    <i class="fas fa-check"></i>
                                                </button>
                                                <button class="btn btn-sm btn-outline-danger" data-bs-toggle="modal" data-bs-target="#cancelModal<?= $int['id'] ?>" title="Cancel">
                                                    <i class="fas fa-times"></i>
                                                </button>
                                            </td>
                                        </tr>

                                        <!-- Complete Modal -->
                                        <div class="modal fade" id="completeModal<?= $int['id'] ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form method="POST">
                                                        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                                        <input type="hidden" name="interview_id" value="<?= $int['id'] ?>">
                                                        <input type="hidden" name="action" value="complete">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Complete Interview</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p>Mark interview with <strong><?= htmlspecialchars($int['first_name'] . ' ' . $int['last_name']) ?></strong> as completed?</p>
                                                            <div class="mb-3">
                                                                <label class="form-label">Rating (1-5)</label>
                                                                <select name="rating" class="form-select">
                                                                    <option value="">-- No rating --</option>
                                                                    <option value="1">1 - Poor</option>
                                                                    <option value="2">2 - Below Average</option>
                                                                    <option value="3">3 - Average</option>
                                                                    <option value="4">4 - Good</option>
                                                                    <option value="5">5 - Excellent</option>
                                                                </select>
                                                            </div>
                                                            <div class="mb-3">
                                                                <label class="form-label">Feedback</label>
                                                                <textarea name="feedback" class="form-control" rows="3" placeholder="Interview notes..."></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Cancel</button>
                                                            <button type="submit" class="btn btn-success">Complete</button>
                                                        </div>
                                                    </form>
                                                </div>
                                            </div>
                                        </div>

                                        <!-- Cancel Modal -->
                                        <div class="modal fade" id="cancelModal<?= $int['id'] ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <form method="POST">
                                                        <input type="hidden" name="csrf_token" value="<?= generate_csrf_token() ?>">
                                                        <input type="hidden" name="interview_id" value="<?= $int['id'] ?>">
                                                        <input type="hidden" name="action" value="cancel">
                                                        <div class="modal-header">
                                                            <h5 class="modal-title">Cancel Interview</h5>
                                                            <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                        </div>
                                                        <div class="modal-body">
                                                            <p>Are you sure you want to cancel the interview with <strong><?= htmlspecialchars($int['first_name'] . ' ' . $int['last_name']) ?></strong>?</p>
                                                            <div class="mb-3">
                                                                <label class="form-label">Reason (optional)</label>
                                                                <textarea name="feedback" class="form-control" rows="2" placeholder="Reason for cancellation..."></textarea>
                                                            </div>
                                                        </div>
                                                        <div class="modal-footer">
                                                            <button type="button" class="btn btn-secondary" data-bs-dismiss="modal">Keep</button>
                                                            <button type="submit" class="btn btn-danger">Cancel Interview</button>
                                                        </div>
                                                    </form>
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

            <!-- Completed Interviews -->
            <?php if (!empty($completed)): ?>
            <div class="card">
                <div class="card-header">
                    <h5 class="mb-0"><i class="fas fa-check-circle me-2 text-success"></i>Completed Interviews</h5>
                </div>
                <div class="card-body">
                    <div class="table-responsive">
                        <table class="table table-hover align-middle">
                            <thead class="table-light">
                                <tr>
                                    <th>Code</th>
                                    <th>Candidate</th>
                                    <th>Job</th>
                                    <th>Date</th>
                                    <th>Rating</th>
                                    <th>Feedback</th>
                                </tr>
                            </thead>
                            <tbody>
                                <?php foreach ($completed as $int): ?>
                                    <tr>
                                        <td><span class="badge bg-secondary"><?= htmlspecialchars($int['interview_code'] ?? '') ?></span></td>
                                        <td><?= htmlspecialchars($int['first_name'] . ' ' . $int['last_name']) ?></td>
                                        <td><small><?= htmlspecialchars($int['job_title'] ?? 'N/A') ?></small></td>
                                        <td><small><?= date('M d, Y', strtotime($int['scheduled_date'])) ?></small></td>
                                        <td>
                                            <?php if ($int['overall_rating']): ?>
                                                <span class="text-warning"><?= number_format($int['overall_rating'], 1) ?>/5</span>
                                            <?php else: ?>
                                                <span class="text-muted">-</span>
                                            <?php endif; ?>
                                        </td>
                                        <td><small class="text-muted"><?= htmlspecialchars(substr($int['feedback'] ?? '', 0, 50)) ?></small></td>
                                    </tr>
                                <?php endforeach; ?>
                            </tbody>
                        </table>
                    </div>
                </div>
            </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
