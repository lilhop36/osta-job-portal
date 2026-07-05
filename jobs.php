<?php
declare(strict_types=1);

require_once __DIR__ . '/includes/bootstrap.php';
require_once __DIR__ . '/vendor/autoload.php';

use App\Models\Job;
use App\Models\Department;
use App\Models\Skill;
use App\Models\Location;
use App\Helpers\Pagination;

$jobModel = new Job();
$deptModel = new Department();
$skillModel = new Skill();

// Get filter values
$filters = [
    'keyword'    => sanitize($_GET['keyword'] ?? $_GET['search'] ?? ''),
    'type'       => sanitize($_GET['type'] ?? ''),
    'department' => (int) ($_GET['department'] ?? 0),
    'location'   => sanitize($_GET['location'] ?? ''),
    'salary_min' => (int) ($_GET['salary_min'] ?? 0),
    'salary_max' => (int) ($_GET['salary_max'] ?? 0),
    'sort'       => sanitize($_GET['sort'] ?? 'newest'),
];

$page = max(1, (int) ($_GET['page'] ?? 1));
$perPage = 12;

// Build query
$where = ["j.status = 'approved'", "j.deadline >= CURDATE()"];
$params = [];

if (!empty($filters['keyword'])) {
    $where[] = "MATCH(j.title, j.description, j.requirements) AGAINST(? IN BOOLEAN MODE)";
    $params[] = $filters['keyword'];
}
if (!empty($filters['type'])) {
    $where[] = "j.employment_type = ?";
    $params[] = $filters['type'];
}
if ($filters['department'] > 0) {
    $where[] = "j.department_id = ?";
    $params[] = $filters['department'];
}
if (!empty($filters['location'])) {
    $where[] = "j.location LIKE ?";
    $params[] = '%' . $filters['location'] . '%';
}
if ($filters['salary_min'] > 0) {
    $where[] = "CAST(j.salary AS UNSIGNED) >= ?";
    $params[] = $filters['salary_min'];
}
if ($filters['salary_max'] > 0) {
    $where[] = "CAST(j.salary AS UNSIGNED) <= ?";
    $params[] = $filters['salary_max'];
}

$whereClause = implode(' AND ', $where);

// Count total
$countSql = "SELECT COUNT(*) FROM jobs j WHERE {$whereClause}";
$countStmt = $pdo->prepare($countSql);
$countStmt->execute($params);
$totalJobs = (int) $countStmt->fetchColumn();

$pagination = new Pagination($page, $perPage, $totalJobs);

// Sort
$orderBy = match($filters['sort']) {
    'oldest'    => 'j.created_at ASC',
    'salary'    => 'j.salary DESC',
    'deadline'  => 'j.deadline ASC',
    'title'     => 'j.title ASC',
    default     => 'j.created_at DESC',
};

// Fetch jobs
$sql = "SELECT j.*, IFNULL(d.name, 'General') as department_name 
        FROM jobs j 
        LEFT JOIN departments d ON j.department_id = d.id 
        WHERE {$whereClause}
        ORDER BY {$orderBy}
        LIMIT ? OFFSET ?";
$params[] = $perPage;
$params[] = $pagination->getOffset();

$stmt = $pdo->prepare($sql);
$stmt->execute($params);
$jobs = $stmt->fetchAll();

// Get departments for filter
$departments = $deptModel->getAll();

// Build base URL for pagination
$paginationParams = array_filter($filters, fn($v) => $v !== '' && $v !== 0);
$paginationUrl = '/jobs.php';

$pageTitle = 'Find Jobs';
?>
<?php include __DIR__ . '/includes/header.php'; ?>

<style>
    .search-hero {
        background: linear-gradient(160deg, var(--osta-dark) 0%, #0f3d0f 50%, var(--osta-green-dark) 100%);
        padding: 3rem 0 2rem;
        color: white;
    }
    .filter-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.08);
    }
    .job-card {
        border: none;
        border-radius: 12px;
        box-shadow: 0 2px 12px rgba(0,0,0,0.06);
        transition: all 0.3s ease;
        height: 100%;
    }
    .job-card:hover {
        transform: translateY(-4px);
        box-shadow: 0 8px 25px rgba(0,0,0,0.12);
    }
    .job-type-badge {
        font-size: 0.75rem;
        padding: 4px 10px;
        border-radius: 20px;
        font-weight: 600;
    }
    .job-meta {
        font-size: 0.85rem;
        color: #6c757d;
    }
    .job-meta i {
        width: 16px;
        color: var(--osta-green);
    }
    .filter-active {
        background: var(--osta-green) !important;
        color: white !important;
        border-color: var(--osta-green) !important;
    }
</style>

<div class="search-hero">
    <div class="container">
        <h2 class="fw-bold mb-2"><i class="fas fa-search me-2"></i>Find Your Perfect Job</h2>
        <p class="mb-0 opacity-75"><?php echo number_format($totalJobs); ?> jobs available</p>
    </div>
</div>

<div class="container py-4">
    <div class="row g-4">
        <!-- Filters Sidebar -->
        <div class="col-lg-3">
            <div class="card filter-card p-3 mb-4">
                <h6 class="fw-bold mb-3"><i class="fas fa-filter me-2" style="color: var(--osta-green);"></i>Filters</h6>
                <form method="GET" action="jobs.php" id="filterForm">
                    <input type="hidden" name="keyword" value="<?php echo htmlspecialchars($filters['keyword']); ?>">

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Job Type</label>
                        <div class="d-flex flex-wrap gap-1">
                            <a href="?<?php echo http_build_query(array_merge($filters, ['type' => ''])); ?>" 
                               class="btn btn-sm <?php echo empty($filters['type']) ? 'filter-active' : 'btn-outline-secondary'; ?>">All</a>
                            <a href="?<?php echo http_build_query(array_merge($filters, ['type' => 'full_time'])); ?>" 
                               class="btn btn-sm <?php echo $filters['type'] === 'full_time' ? 'filter-active' : 'btn-outline-secondary'; ?>">Full-time</a>
                            <a href="?<?php echo http_build_query(array_merge($filters, ['type' => 'part_time'])); ?>" 
                               class="btn btn-sm <?php echo $filters['type'] === 'part_time' ? 'filter-active' : 'btn-outline-secondary'; ?>">Part-time</a>
                            <a href="?<?php echo http_build_query(array_merge($filters, ['type' => 'contract'])); ?>" 
                               class="btn btn-sm <?php echo $filters['type'] === 'contract' ? 'filter-active' : 'btn-outline-secondary'; ?>">Contract</a>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Department</label>
                        <select name="department" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="">All Departments</option>
                            <?php foreach ($departments as $dept): ?>
                            <option value="<?php echo $dept['id']; ?>" <?php echo $filters['department'] == $dept['id'] ? 'selected' : ''; ?>>
                                <?php echo htmlspecialchars($dept['name']); ?>
                            </option>
                            <?php endforeach; ?>
                        </select>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Location</label>
                        <input type="text" name="location" class="form-control form-control-sm" 
                               placeholder="e.g. Addis Ababa"
                               value="<?php echo htmlspecialchars($filters['location']); ?>">
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Salary Range (ETB)</label>
                        <div class="row g-1">
                            <div class="col-6">
                                <input type="number" name="salary_min" class="form-control form-control-sm" 
                                       placeholder="Min" min="0" step="1000"
                                       value="<?php echo $filters['salary_min'] ?: ''; ?>">
                            </div>
                            <div class="col-6">
                                <input type="number" name="salary_max" class="form-control form-control-sm" 
                                       placeholder="Max" min="0" step="1000"
                                       value="<?php echo $filters['salary_max'] ?: ''; ?>">
                            </div>
                        </div>
                    </div>

                    <div class="mb-3">
                        <label class="form-label fw-semibold small">Sort By</label>
                        <select name="sort" class="form-select form-select-sm" onchange="this.form.submit()">
                            <option value="newest" <?php echo $filters['sort'] === 'newest' ? 'selected' : ''; ?>>Newest First</option>
                            <option value="oldest" <?php echo $filters['sort'] === 'oldest' ? 'selected' : ''; ?>>Oldest First</option>
                            <option value="title" <?php echo $filters['sort'] === 'title' ? 'selected' : ''; ?>>Title A-Z</option>
                            <option value="deadline" <?php echo $filters['sort'] === 'deadline' ? 'selected' : ''; ?>>Deadline (soonest)</option>
                        </select>
                    </div>

                    <button type="submit" class="btn btn-sm w-100" style="background: var(--osta-green); color: white;">
                        <i class="fas fa-search me-1"></i>Apply Filters
                    </button>
                    <a href="jobs.php" class="btn btn-sm btn-outline-secondary w-100 mt-2">Clear All</a>
                </form>
            </div>
        </div>

        <!-- Job Listings -->
        <div class="col-lg-9">
            <!-- Search Bar -->
            <form method="GET" action="jobs.php" class="mb-4">
                <div class="input-group">
                    <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                    <input type="text" name="keyword" class="form-control" 
                           placeholder="Search jobs by title, description, or requirements..."
                           value="<?php echo htmlspecialchars($filters['keyword']); ?>">
                    <button type="submit" class="btn" style="background: var(--osta-green); color: white;">Search</button>
                </div>
                <input type="hidden" name="type" value="<?php echo htmlspecialchars($filters['type']); ?>">
                <input type="hidden" name="department" value="<?php echo $filters['department']; ?>">
                <input type="hidden" name="location" value="<?php echo htmlspecialchars($filters['location']); ?>">
                <input type="hidden" name="sort" value="<?php echo htmlspecialchars($filters['sort']); ?>">
            </form>

            <?php if (empty($jobs)): ?>
                <div class="text-center py-5">
                    <i class="fas fa-search fa-3x text-muted mb-3"></i>
                    <h4 class="text-muted">No jobs found</h4>
                    <p class="text-muted">Try adjusting your search filters</p>
                    <a href="jobs.php" class="btn" style="background: var(--osta-green); color: white;">Clear Filters</a>
                </div>
            <?php else: ?>
                <div class="row g-3">
                    <?php foreach ($jobs as $job): ?>
                    <div class="col-md-6">
                        <div class="card job-card p-3">
                            <div class="d-flex justify-content-between align-items-start mb-2">
                                <h6 class="fw-bold mb-0">
                                    <a href="job_details.php?id=<?php echo $job['id']; ?>" class="text-decoration-none" style="color: var(--osta-dark);">
                                        <?php echo htmlspecialchars($job['title']); ?>
                                    </a>
                                </h6>
                                <span class="job-type-badge bg-<?php echo match($job['employment_type']) {
                                    'full_time' => 'success',
                                    'part_time' => 'info',
                                    'contract' => 'warning',
                                    'internship' => 'primary',
                                    default => 'secondary'
                                }; ?> text-white">
                                    <?php echo str_replace('_', ' ', ucfirst($job['employment_type'])); ?>
                                </span>
                            </div>
                            <div class="job-meta mb-2">
                                <div><i class="fas fa-building me-1"></i><?php echo htmlspecialchars($job['department_name']); ?></div>
                                <div><i class="fas fa-map-marker-alt me-1"></i><?php echo htmlspecialchars($job['location']); ?></div>
                                <?php if (!empty($job['salary_range'])): ?>
                                <div><i class="fas fa-money-bill-wave me-1"></i><?php echo htmlspecialchars($job['salary_range']); ?></div>
                                <?php endif; ?>
                            </div>
                            <p class="text-muted small mb-2" style="display: -webkit-box; -webkit-line-clamp: 2; -webkit-box-orient: vertical; overflow: hidden;">
                                <?php echo htmlspecialchars(strip_tags($job['description'])); ?>
                            </p>
                            <div class="d-flex justify-content-between align-items-center mt-auto">
                                <small class="text-muted"><i class="fas fa-calendar me-1"></i>Deadline: <?php echo date('M d, Y', strtotime($job['deadline'])); ?></small>
                                <a href="job_details.php?id=<?php echo $job['id']; ?>" class="btn btn-sm" style="background: var(--osta-green); color: white;">View</a>
                            </div>
                        </div>
                    </div>
                    <?php endforeach; ?>
                </div>

                <!-- Pagination -->
                <div class="mt-4">
                    <?php echo $pagination->render('jobs.php', $paginationParams); ?>
                </div>
            <?php endif; ?>
        </div>
    </div>
</div>

<?php include __DIR__ . '/includes/footer.php'; ?>
