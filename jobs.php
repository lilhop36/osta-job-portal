<?php
// Redirect to unified homepage with job listings
require_once __DIR__ . '/config/database.php';

// Preserve any query parameters when redirecting
$query_string = $_SERVER['QUERY_STRING'] ? '?' . $_SERVER['QUERY_STRING'] : '';
header('Location: ' . SITE_URL . '/index.php' . $query_string);
exit();
?>
        }
        .job-type {
            font-size: 0.85rem;
            padding: 0.25rem 0.75rem;
            border-radius: 50px;
            display: inline-block;
            margin-bottom: 0.5rem;
        }
        .job-type.full_time { background-color: #d4edda; color: #155724; }
        .job-type.part_time { background-color: #fff3cd; color: #856404; }
        .job-type.contract { background-color: #cce5ff; color: #004085; }
        .job-type.internship { background-color: #f8d7da; color: #721c24; }
        .pagination {
            justify-content: center;
            margin-top: 30px;
        }
        .filter-card {
            margin-bottom: 30px;
            background-color: #f8f9fa;
            padding: 20px;
            border-radius: 8px;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>

    <!-- Hero Section -->
    <div class="bg-primary text-white py-5">
        <div class="container">
            <div class="row">
                <div class="col-lg-8 mx-auto text-center">
                    <h1 class="display-5 fw-bold">Find Your Dream Job</h1>
                    <p class="lead mb-4">Browse through our latest job openings and start your career journey with OSTA</p>
                    
                    <!-- Search Form -->
                    <form method="GET" action="" class="row g-3 justify-content-center">
                        <div class="col-md-4">
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="fas fa-search text-muted"></i></span>
                                <input type="text" class="form-control" name="search" placeholder="Job title, keywords" value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <div class="input-group">
                                <span class="input-group-text bg-white"><i class="fas fa-map-marker-alt text-muted"></i></span>
                                <input type="text" class="form-control" name="location" placeholder="Location" value="<?php echo htmlspecialchars($location); ?>">
                            </div>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-light text-primary w-100">
                                <i class="fas fa-search me-1"></i> Search
                            </button>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <!-- Main Content -->
    <div class="container py-5">
        <div class="row">
            <!-- Filters -->
            <div class="col-lg-3 mb-4">
                <div class="filter-card">
                    <h5 class="mb-3">Filters</h5>
                    <form method="GET" action="" id="filter-form">
                        <input type="hidden" name="search" value="<?php echo htmlspecialchars($search); ?>">
                        <input type="hidden" name="location" value="<?php echo htmlspecialchars($location); ?>">
                        
                        <div class="mb-3">
                            <label for="job_type" class="form-label fw-bold">Job Type</label>
                            <select class="form-select" name="job_type" id="job_type">
                                <option value="">All Types</option>
                                <option value="full_time" <?php echo $job_type === 'full_time' ? 'selected' : ''; ?>>Full Time</option>
                                <option value="part_time" <?php echo $job_type === 'part_time' ? 'selected' : ''; ?>>Part Time</option>
                                <option value="contract" <?php echo $job_type === 'contract' ? 'selected' : ''; ?>>Contract</option>
                                <option value="internship" <?php echo $job_type === 'internship' ? 'selected' : ''; ?>>Internship</option>
                            </select>
                        </div>
                        
                        <div class="mb-3">
                            <label for="department" class="form-label fw-bold">Department</label>
                            <select class="form-select" name="department" id="department">
                                <option value="">All Departments</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo $dept['id']; ?>" <?php echo $department === $dept['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dept['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        
                        <button type="submit" class="btn btn-primary w-100">Apply Filters</button>
                        <?php if ($search || $location || $job_type || $department): ?>
                            <a href="jobs.php" class="btn btn-outline-secondary w-100 mt-2">Clear All</a>
                        <?php endif; ?>
                    </form>
                </div>
                
                <!-- Create Job Alert -->
                <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'applicant'): ?>
                    <div class="card mt-4">
                        <div class="card-body">
                            <h6 class="card-title">Get Job Alerts</h6>
                            <p class="small text-muted">Save your search and get notified when new jobs match your criteria.</p>
                            <a href="applicant/alerts.php" class="btn btn-outline-primary btn-sm w-100">
                                <i class="fas fa-bell me-1"></i> Create Job Alert
                            </a>
                        </div>
                    </div>
                <?php endif; ?>
            </div>
            
            <!-- Job Listings -->
            <div class="col-lg-9">
                <div class="d-flex justify-content-between align-items-center mb-4">
                    <h2 class="h4 mb-0"><?php echo $total_jobs; ?> Jobs Found</h2>
                    <div class="dropdown">
                        <button class="btn btn-outline-secondary dropdown-toggle" type="button" id="sortDropdown" data-bs-toggle="dropdown" aria-expanded="false">
                            <i class="fas fa-sort me-1"></i> Sort By
                        </button>
                        <ul class="dropdown-menu" aria-labelledby="sortDropdown">
                            <li><a class="dropdown-item" href="#">Most Recent</a></li>
                            <li><a class="dropdown-item" href="#">Job Title (A-Z)</a></li>
                            <li><a class="dropdown-item" href="#">Location (A-Z)</a></li>
                        </ul>
                    </div>
                </div>
                
                <?php if (empty($jobs)): ?>
                    <div class="alert alert-info">
                        <h5 class="alert-heading">No jobs found</h5>
                        <p class="mb-0">Try adjusting your search or filter criteria to find more jobs.</p>

                    </div>
                <?php else: ?>
                    <?php foreach ($jobs as $job): 
                        // Format date
                        $post_date = new DateTime($job['created_at']);
                        $today = new DateTime();
                        $interval = $post_date->diff($today);
                        
                        if ($interval->days === 0) {
                            $time_ago = 'Today';
                        } elseif ($interval->days === 1) {
                            $time_ago = 'Yesterday';
                        } else {
                            $time_ago = $interval->days . ' days ago';
                        }
                        
                        // Format job type
                        $job_types = [
                            'full_time' => 'Full Time',
                            'part_time' => 'Part Time',
                            'contract' => 'Contract',
                            'internship' => 'Internship'
                        ];
                        $job_type_display = $job_types[$job['employment_type']] ?? $job['employment_type'];
                    ?>
                        <div class="card job-card mb-3">
                            <div class="card-body">
                                <div class="row">
                                    <div class="col-md-8">
                                        <h5 class="card-title mb-1">
                                            <a href="job_details.php?id=<?php echo $job['id']; ?>" class="text-decoration-none">
                                                <?php echo htmlspecialchars($job['title']); ?>
                                            </a>
                                        </h5>
                                        <p class="mb-2 text-muted">
                                            <i class="fas fa-building me-1"></i> <?php echo htmlspecialchars($job['department_name']); ?>
                                            <span class="mx-2">•</span>
                                            <i class="fas fa-map-marker-alt me-1"></i> <?php echo htmlspecialchars($job['location']); ?>
                                        </p>
                                        <span class="job-type <?php echo $job['employment_type']; ?>">
                                            <i class="fas fa-briefcase me-1"></i> <?php echo $job_type_display; ?>
                                        </span>
                                        <p class="card-text mt-2 text-muted small">
                                            <i class="far fa-clock me-1"></i> Posted <?php echo $time_ago; ?>
                                            <span class="mx-2">•</span>
                                            <i class="fas fa-calendar-times me-1"></i> Apply before <?php echo date('M j, Y', strtotime($job['deadline'])); ?>
                                        </p>
                                    </div>
                                    <div class="col-md-4 text-md-end mt-3 mt-md-0">
                                        <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'applicant'): 
                                            // Check if job is saved
                                            $is_saved = false;
                                            if (isset($_SESSION['user_id'])) {
                                                $check_stmt = $pdo->prepare("SELECT id FROM saved_jobs WHERE job_id = ? AND user_id = ?");
                                                $check_stmt->execute([$job['id'], $_SESSION['user_id']]);
                                                $is_saved = $check_stmt->fetch();
                                            }
                                        ?>
                                            <form action="<?php echo SITE_URL; ?>/applicant/save_job.php" method="POST" class="d-inline" id="save-form-<?php echo $job['id']; ?>">
                                                <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-primary mb-2" 
                                                        title="<?php echo $is_saved ? 'Remove from saved jobs' : 'Save for later'; ?>">
                                                    <i class="fas <?php echo $is_saved ? 'fa-bookmark' : 'fa-bookmark-o'; ?> me-1"></i>
                                                    <?php echo $is_saved ? 'Saved' : 'Save'; ?>
                                                </button>
                                            </form>
                                        <?php endif; ?>
                                        
                                        <a href="job_details.php?id=<?php echo $job['id']; ?>" class="btn btn-primary">
                                            View Details <i class="fas fa-arrow-right ms-1"></i>
                                        </a>
                                    </div>
                                </div>
                            </div>
                        </div>
                    <?php endforeach; ?>
                    
                    <!-- Pagination -->
                    <?php if ($total_pages > 1): ?>
                        <nav aria-label="Job pagination">
                            <ul class="pagination">
                                <?php if ($page > 1): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page - 1])); ?>" aria-label="Previous">
                                            <span aria-hidden="true">&laquo; Previous</span>
                                        </a>
                                    </li>
                                <?php endif; ?>
                                
                                <?php for ($i = 1; $i <= $total_pages; $i++): ?>
                                    <li class="page-item <?php echo $i === $page ? 'active' : ''; ?>">
                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $i])); ?>">
                                            <?php echo $i; ?>
                                        </a>
                                    </li>
                                <?php endfor; ?>
                                
                                <?php if ($page < $total_pages): ?>
                                    <li class="page-item">
                                        <a class="page-link" href="?<?php echo http_build_query(array_merge($_GET, ['page' => $page + 1])); ?>" aria-label="Next">
                                            <span aria-hidden="true">Next &raquo;</span>
                                        </a>
                                    </li>
                                <?php endif; ?>
                            </ul>
                        </nav>
                    <?php endif; ?>
                <?php endif; ?>
            </div>
        </div>
    </div>

    <?php include __DIR__ . '/includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
    <script>
        // Auto-submit filter form when dropdowns change
        document.getElementById('job_type').addEventListener('change', function() {
            document.getElementById('filter-form').submit();
        });
        
        document.getElementById('department').addEventListener('change', function() {
            document.getElementById('filter-form').submit();
        });
    </script>
</body>
</html>
