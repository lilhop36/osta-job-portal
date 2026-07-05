<?php
declare(strict_types=1);

require_once __DIR__ . '/../includes/bootstrap.php';
require_once __DIR__ . '/../vendor/autoload.php';

use App\Helpers\Pagination;

require_role('employer', '../login.php');

$userId = (int) $_SESSION['user_id'];

// Get employer's department
$stmt = $pdo->prepare("SELECT department_id FROM users WHERE id = ?");
$stmt->execute([$userId]);
$employer = $stmt->fetch();
$deptId = $employer['department_id'] ?? null;

// Build query - find applicants who applied to employer's jobs
$where = ["ca.status != 'draft'"];
$params = [];

if (!empty($_GET['keyword'])) {
    $where[] = "(ca.first_name LIKE ? OR ca.last_name LIKE ? OR ca.field_of_study LIKE ? OR ca.skills LIKE ?)";
    $kw = '%' . sanitize($_GET['keyword']) . '%';
    $params[] = $kw;
    $params[] = $kw;
    $params[] = $kw;
    $params[] = $kw;
}

if (!empty($_GET['education'])) {
    $where[] = "ca.education_level = ?";
    $params[] = sanitize($_GET['education']);
}

if (!empty($_GET['experience'])) {
    $where[] = "ca.years_of_experience >= ?";
    $params[] = (int) $_GET['experience'];
}

$whereClause = implode(' AND ', $where);

$page = max(1, (int) ($_GET['page'] ?? 1));

$countSql = "SELECT COUNT(DISTINCT ca.id) FROM centralized_applications ca
             INNER JOIN applications a ON ca.user_id = a.user_id
             INNER JOIN jobs j ON a.job_id = j.id
             WHERE j.created_by = ? AND {$whereClause}";
$countStmt = $pdo->prepare($countSql);
$countParams = array_merge([$userId], $params);
$countStmt->execute($countParams);
$total = (int) $countStmt->fetchColumn();

$pagination = new Pagination($page, 12, $total);

$sql = "SELECT DISTINCT ca.*, u.username,
               (SELECT GROUP_CONCAT(j.title SEPARATOR ', ') 
                FROM applications a2 
                INNER JOIN jobs j ON a2.job_id = j.id 
                WHERE a2.user_id = ca.user_id AND j.created_by = ?) as applied_jobs
        FROM centralized_applications ca
        INNER JOIN users u ON ca.user_id = u.id
        INNER JOIN applications a ON ca.user_id = a.user_id
        INNER JOIN jobs j ON a.job_id = j.id
        WHERE j.created_by = ? AND {$whereClause}
        ORDER BY ca.created_at DESC
        LIMIT ? OFFSET ?";

$paramsForQuery = array_merge([$userId, $userId], $params, [$pagination->getPerPage(), $pagination->getOffset()]);
$stmt = $pdo->prepare($sql);
$stmt->execute($paramsForQuery);
$candidates = $stmt->fetchAll();

$pageTitle = 'Find Candidates';
?>
<?php include __DIR__ . '/../includes/header.php'; ?>

<div class="container py-4">
    <div class="d-flex justify-content-between align-items-center mb-4">
        <h4 class="fw-bold mb-0"><i class="fas fa-users me-2" style="color: var(--osta-green);"></i>Find Candidates</h4>
        <a href="dashboard.php" class="btn btn-outline-secondary btn-sm"><i class="fas fa-arrow-left me-1"></i>Dashboard</a>
    </div>

    <!-- Filters -->
    <div class="card border-0 shadow-sm mb-4" style="border-radius: 12px;">
        <div class="card-body">
            <form method="GET" class="row g-3 align-items-end">
                <div class="col-md-4">
                    <label class="form-label small fw-semibold">Search</label>
                    <input type="text" name="keyword" class="form-control" placeholder="Name, skills, education..." value="<?php echo htmlspecialchars($_GET['keyword'] ?? ''); ?>">
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Education</label>
                    <select name="education" class="form-select">
                        <option value="">Any Level</option>
                        <?php foreach (['high_school', 'diploma', 'bachelor', 'master', 'phd'] as $level): ?>
                        <option value="<?php echo $level; ?>" <?php echo ($_GET['education'] ?? '') === $level ? 'selected' : ''; ?>><?php echo ucwords(str_replace('_', ' ', $level)); ?></option>
                        <?php endforeach; ?>
                    </select>
                </div>
                <div class="col-md-3">
                    <label class="form-label small fw-semibold">Min Experience (years)</label>
                    <input type="number" name="experience" class="form-control" min="0" value="<?php echo htmlspecialchars($_GET['experience'] ?? ''); ?>">
                </div>
                <div class="col-md-2">
                    <button type="submit" class="btn w-100" style="background: var(--osta-green); color: white;"><i class="fas fa-search me-1"></i>Search</button>
                </div>
            </form>
        </div>
    </div>

    <?php if (empty($candidates)): ?>
        <div class="text-center py-5">
            <i class="fas fa-users fa-3x text-muted mb-3"></i>
            <h5 class="text-muted">No candidates found</h5>
            <p class="text-muted">Try adjusting your search filters.</p>
        </div>
    <?php else: ?>
        <div class="row g-3">
            <?php foreach ($candidates as $c): ?>
            <div class="col-md-6 col-lg-4">
                <div class="card h-100 border-0 shadow-sm" style="border-radius: 12px;">
                    <div class="card-body">
                        <div class="d-flex align-items-center mb-3">
                            <div class="rounded-circle d-flex align-items-center justify-content-center me-3" style="width:48px;height:48px;background:var(--osta-green);color:white;font-weight:700;">
                                <?php echo strtoupper(substr($c['first_name'], 0, 1)); ?>
                            </div>
                            <div>
                                <h6 class="fw-bold mb-0"><?php echo htmlspecialchars($c['first_name'] . ' ' . $c['last_name']); ?></h6>
                                <small class="text-muted"><?php echo htmlspecialchars($c['email']); ?></small>
                            </div>
                        </div>
                        <div class="small text-muted mb-2">
                            <div><i class="fas fa-graduation-cap me-1"></i><?php echo ucwords(str_replace('_', ' ', $c['education_level'])); ?> — <?php echo htmlspecialchars($c['field_of_study']); ?></div>
                            <div><i class="fas fa-briefcase me-1"></i><?php echo $c['years_of_experience']; ?> years experience</div>
                            <div><i class="fas fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($c['city'] . ', ' . $c['region']); ?></div>
                        </div>
                        <?php if (!empty($c['skills'])): ?>
                            <div class="mb-2">
                                <?php foreach array_slice(explode(',', $c['skills']), 0, 4) as $skill): ?>
                                    <span class="badge bg-light text-dark"><?php echo htmlspecialchars(trim($skill)); ?></span>
                                <?php endforeach; ?>
                            </div>
                        <?php endif; ?>
                        <?php if (!empty($c['applied_jobs'])): ?>
                            <div class="small text-muted"><i class="fas fa-file-alt me-1"></i>Applied: <?php echo htmlspecialchars($c['applied_jobs']); ?></div>
                        <?php endif; ?>
                    </div>
                    <div class="card-footer bg-white border-top-0">
                        <a href="view_application.php?id=<?php echo $c['id']; ?>" class="btn btn-sm w-100" style="background: var(--osta-green); color: white;">View Profile</a>
                    </div>
                </div>
            </div>
            <?php endforeach; ?>
        </div>

        <div class="mt-4">
            <?php echo $pagination->render('candidates.php', array_filter($_GET, fn($v) => $v !== '')); ?>
        </div>
    <?php endif; ?>
</div>

<?php include __DIR__ . '/../includes/footer.php'; ?>
