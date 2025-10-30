<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>Job Applications - OSTA Job Portal</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="../assets/css/styles.css">
</head>
<body>
<?php
session_start();
require_once '../config/database.php';
require_once '../includes/auth.php';
require_once '../includes/security.php';

// Require authentication and applicant role
require_auth('applicant');

$user_id = $_SESSION['user_id'];

// Get user info
$stmt = $pdo->prepare("SELECT * FROM users WHERE id = ?");
$stmt->execute([$user_id]);
$user = $stmt->fetch();

// Get all job applications for this user
$stmt = $pdo->prepare("SELECT a.*, j.title, j.location, j.employment_type, j.department_id,
                              d.name as department_name
                      FROM applications a 
                      JOIN jobs j ON a.job_id = j.id 
                      LEFT JOIN departments d ON j.department_id = d.id
                      WHERE a.user_id = ? 
                      ORDER BY a.created_at DESC");
$stmt->execute([$user_id]);
$applications = $stmt->fetchAll();

// Get application statistics
$stats = [
    'total' => count($applications),
    'pending' => 0,
    'under_review' => 0,
    'shortlisted' => 0,
    'rejected' => 0,
    'accepted' => 0
];

foreach ($applications as $app) {
    if (isset($stats[$app['status']])) {
        $stats[$app['status']]++;
    }
}

$page_title = "My Applications";
include '../includes/header_new.php';
?>

<div class="container-fluid py-4">
    <div class="row">
        <div class="col-12">
            <div class="d-flex justify-content-between align-items-center mb-4">
                <h2><i class="fas fa-briefcase me-2"></i>My Job Applications</h2>
                <a href="dashboard.php" class="btn btn-outline-primary">
                    <i class="fas fa-arrow-left me-2"></i>Back to Dashboard
                </a>
            </div>
            
            <!-- Application Statistics -->
            <div class="row mb-4">
                <div class="col-md-2">
                    <div class="card text-center">
                        <div class="card-body">
                            <h3 class="text-primary"><?= $stats['total'] ?></h3>
                            <small class="text-muted">Total Applications</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card text-center">
                        <div class="card-body">
                            <h3 class="text-warning"><?= $stats['pending'] ?></h3>
                            <small class="text-muted">Pending</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card text-center">
                        <div class="card-body">
                            <h3 class="text-info"><?= $stats['under_review'] ?></h3>
                            <small class="text-muted">Under Review</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card text-center">
                        <div class="card-body">
                            <h3 class="text-success"><?= $stats['shortlisted'] ?></h3>
                            <small class="text-muted">Shortlisted</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card text-center">
                        <div class="card-body">
                            <h3 class="text-danger"><?= $stats['rejected'] ?></h3>
                            <small class="text-muted">Rejected</small>
                        </div>
                    </div>
                </div>
                <div class="col-md-2">
                    <div class="card text-center">
                        <div class="card-body">
                            <h3 class="text-success"><?= $stats['accepted'] ?></h3>
                            <small class="text-muted">Accepted</small>
                        </div>
                    </div>
                </div>
            </div>
            
            <!-- Applications List -->
            <div class="card shadow-sm">
                <div class="card-header bg-light">
                    <h5 class="mb-0">All Applications</h5>
                </div>
                <div class="card-body">
                    <?php if (empty($applications)): ?>
                        <div class="text-center py-5">
                            <i class="fas fa-inbox fa-3x text-muted mb-3"></i>
                            <h4 class="text-muted">No Applications Yet</h4>
                            <p class="text-muted">You haven't applied for any jobs yet.</p>
                            <a href="../jobs.php" class="btn btn-primary">
                                <i class="fas fa-search me-2"></i>Browse Jobs
                            </a>
                        </div>
                    <?php else: ?>
                        <div class="table-responsive">
                            <table class="table table-hover">
                                <thead>
                                    <tr>
                                        <th>Job Title</th>
                                        <th>Department</th>
                                        <th>Location</th>
                                        <th>Salary Range</th>
                                        <th>Applied Date</th>
                                        <th>Status</th>
                                        <th>Actions</th>
                                    </tr>
                                </thead>
                                <tbody>
                                    <?php foreach ($applications as $app): ?>
                                        <tr>
                                            <td>
                                                <strong><?= htmlspecialchars($app['title']) ?></strong>
                                                <br>
                                                <small class="text-muted"><?= htmlspecialchars($app['employment_type']) ?></small>
                                            </td>
                                            <td><?= htmlspecialchars($app['department_name'] ?? 'N/A') ?></td>
                                            <td><?= htmlspecialchars($app['location']) ?></td>
                                            <td>
                                                <?php 
                                                $salary_min = $app['salary_min'] ?? null;
                                                $salary_max = $app['salary_max'] ?? null;
                                                
                                                if ($salary_min !== null && $salary_max !== null && $salary_min !== '' && $salary_max !== ''): 
                                                ?>
                                                    SAR <?= number_format((float)$salary_min) ?> - <?= number_format((float)$salary_max) ?>
                                                <?php else: ?>
                                                    <span class="text-muted">Not specified</span>
                                                <?php endif; ?>
                                            </td>
                                            <td><?= date('M j, Y', strtotime($app['created_at'])) ?></td>
                                            <td>
                                                <?php
                                                $status_colors = [
                                                    'pending' => 'warning',
                                                    'under_review' => 'info',
                                                    'shortlisted' => 'success',
                                                    'rejected' => 'danger',
                                                    'accepted' => 'success'
                                                ];
                                                $color = $status_colors[$app['status']] ?? 'secondary';
                                                ?>
                                                <span class="badge bg-<?= $color ?>">
                                                    <?= ucfirst(str_replace('_', ' ', $app['status'])) ?>
                                                </span>
                                            </td>
                                            <td>
                                                <div class="btn-group btn-group-sm">
                                                    <a href="../job_details.php?id=<?= $app['job_id'] ?>" 
                                                       class="btn btn-outline-primary" title="View Job">
                                                        <i class="fas fa-eye"></i>
                                                    </a>
                                                    <button type="button" class="btn btn-outline-info" 
                                                            data-bs-toggle="modal" 
                                                            data-bs-target="#coverLetterModal<?= $app['id'] ?>" 
                                                            title="View Cover Letter">
                                                        <i class="fas fa-envelope"></i>
                                                    </button>
                                                    <?php if ($app['resume_path']): ?>
                                                        <a href="download_resume.php?id=<?= $app['id'] ?>" 
                                                           class="btn btn-outline-secondary" title="Download Resume">
                                                            <i class="fas fa-download"></i>
                                                        </a>
                                                    <?php endif; ?>
                                                </div>
                                            </td>
                                        </tr>
                                        
                                        <!-- Cover Letter Modal -->
                                        <div class="modal fade" id="coverLetterModal<?= $app['id'] ?>" tabindex="-1">
                                            <div class="modal-dialog">
                                                <div class="modal-content">
                                                    <div class="modal-header">
                                                        <h5 class="modal-title">Cover Letter - <?= htmlspecialchars($app['title']) ?></h5>
                                                        <button type="button" class="btn-close" data-bs-dismiss="modal"></button>
                                                    </div>
                                                    <div class="modal-body">
                                                        <?php if ($app['cover_letter']): ?>
                                                            <p><?= nl2br(htmlspecialchars($app['cover_letter'])) ?></p>
                                                        <?php else: ?>
                                                            <p class="text-muted">No cover letter provided.</p>
                                                        <?php endif; ?>
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
        </div>
    </div>
</div>

<?php include '../includes/footer_new.php'; ?>
