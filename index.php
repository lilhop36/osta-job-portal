<?php
require_once __DIR__ . '/includes/bootstrap.php';

$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$location = isset($_GET['location']) ? sanitize($_GET['location']) : '';
$job_type = isset($_GET['job_type']) ? sanitize($_GET['job_type']) : '';
$department = isset($_GET['department']) ? (int)$_GET['department'] : 0;

$query = "SELECT j.*, IFNULL(d.name, 'Admin Posted') as department_name 
          FROM jobs j 
          LEFT JOIN departments d ON j.department_id = d.id 
          WHERE j.status = 'approved' AND j.deadline >= CURDATE()";

$params = [];

if (!empty($search)) {
    $query .= " AND (j.title LIKE ? OR j.description LIKE ? OR j.requirements LIKE ?)";
    $search_term = "%$search%";
    $params = array_merge($params, [$search_term, $search_term, $search_term]);
}
if (!empty($location)) {
    $query .= " AND j.location LIKE ?";
    $params[] = "%$location%";
}
if (!empty($job_type)) {
    $query .= " AND j.employment_type = ?";
    $params[] = $job_type;
}
if (!empty($department)) {
    $query .= " AND j.department_id = ?";
    $params[] = $department;
}

$count_query = "SELECT COUNT(*) as total FROM ($query) as count_table";
$count_stmt = $pdo->prepare($count_query);
$count_stmt->execute($params);
$total_jobs = $count_stmt->fetch()['total'];

$query .= " ORDER BY j.created_at DESC LIMIT 6";
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$jobs = $stmt->fetchAll();

$dept_stmt = $pdo->query("SELECT id, name FROM departments ORDER BY name");
$departments = $dept_stmt->fetchAll();

$total_applicants = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'applicant'")->fetchColumn();
$total_employers = $pdo->query("SELECT COUNT(*) FROM users WHERE role = 'employer'")->fetchColumn();
?>
<?php include __DIR__ . '/includes/header.php'; ?>
    <style>
        :root {
            --osta-green: #228B22;
            --osta-green-dark: #1a6b1a;
            --osta-red: #DC143C;
            --osta-gold: #DAA520;
            --osta-dark: #1a2332;
        }

        .hero-section {
            position: relative;
            background: linear-gradient(160deg, var(--osta-dark) 0%, #0f3d0f 50%, var(--osta-green-dark) 100%);
            padding: 100px 0 120px;
            color: white;
            overflow: hidden;
        }

        .hero-section::before {
            content: '';
            position: absolute;
            top: -50%;
            right: -20%;
            width: 800px;
            height: 800px;
            background: radial-gradient(circle, rgba(34,139,34,0.15) 0%, transparent 70%);
            border-radius: 50%;
        }

        .hero-section .container { position: relative; z-index: 1; }

        .hero-badge {
            display: inline-block;
            background: rgba(255,255,255,0.12);
            border: 1px solid rgba(255,255,255,0.2);
            padding: 6px 18px;
            border-radius: 50px;
            font-size: 0.85rem;
            letter-spacing: 1px;
            text-transform: uppercase;
            margin-bottom: 20px;
            backdrop-filter: blur(10px);
        }

        .hero-title {
            font-size: 3.2rem;
            font-weight: 800;
            line-height: 1.15;
            margin-bottom: 20px;
        }

        .hero-title span {
            background: linear-gradient(135deg, #90EE90, var(--osta-gold));
            -webkit-background-clip: text;
            -webkit-text-fill-color: transparent;
            background-clip: text;
        }

        .hero-subtitle {
            font-size: 1.15rem;
            color: rgba(255,255,255,0.75);
            max-width: 600px;
            margin: 0 auto 40px;
            line-height: 1.7;
        }

        .hero-search {
            background: rgba(255,255,255,0.97);
            padding: 28px 32px;
            border-radius: 16px;
            box-shadow: 0 20px 60px rgba(0,0,0,0.3);
            max-width: 900px;
            margin: 0 auto;
        }

        .hero-search .form-label {
            font-size: 0.8rem;
            font-weight: 700;
            text-transform: uppercase;
            letter-spacing: 0.5px;
            color: #495057;
            margin-bottom: 6px;
        }

        .hero-search .form-control,
        .hero-search .form-select {
            border: 2px solid #e9ecef;
            border-radius: 10px;
            padding: 10px 14px;
        }

        .hero-search .form-control:focus,
        .hero-search .form-select:focus {
            border-color: var(--osta-green);
            box-shadow: 0 0 0 3px rgba(34,139,34,0.1);
        }

        .hero-search .input-group-text {
            background: #f8f9fa;
            border: 2px solid #e9ecef;
            border-right: none;
            border-radius: 10px 0 0 10px;
        }

        .hero-search .input-group .form-control {
            border-left: none;
            border-radius: 0 10px 10px 0;
        }

        .btn-hero {
            background: linear-gradient(135deg, var(--osta-green), var(--osta-green-dark));
            border: none;
            padding: 12px 28px;
            border-radius: 10px;
            font-weight: 700;
            color: white;
            transition: all 0.3s ease;
            width: 100%;
        }

        .btn-hero:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(34,139,34,0.4);
            color: white;
        }

        .hero-stats {
            display: flex;
            justify-content: center;
            gap: 50px;
            margin-top: 50px;
            padding-top: 30px;
            border-top: 1px solid rgba(255,255,255,0.1);
        }

        .hero-stat-number {
            font-size: 2rem;
            font-weight: 800;
            color: #90EE90;
        }

        .hero-stat-label {
            font-size: 0.85rem;
            color: rgba(255,255,255,0.6);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 4px;
        }

        .section-title::after {
            content: '';
            position: absolute;
            bottom: -12px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background: var(--osta-green);
            border-radius: 3px;
        }

        .section-title { position: relative; margin-bottom: 50px; }

        .job-card {
            transition: all 0.3s ease;
            border: 1px solid #f0f0f0;
            box-shadow: 0 2px 12px rgba(0,0,0,0.06);
            border-radius: 14px;
            overflow: hidden;
        }

        .job-card:hover {
            transform: translateY(-6px);
            box-shadow: 0 12px 35px rgba(0,0,0,0.12);
            border-color: transparent;
        }

        .job-type {
            font-size: 0.75rem;
            padding: 4px 12px;
            border-radius: 20px;
            font-weight: 700;
            text-transform: uppercase;
        }

        .job-type.full_time { background: #d4edda; color: #155724; }
        .job-type.part_time { background: #fff3cd; color: #856404; }
        .job-type.contract { background: #cce5ff; color: #004085; }
        .job-type.internship { background: #f8d7da; color: #721c24; }

        .salary-badge {
            background: linear-gradient(135deg, var(--osta-gold), #c4941a);
            color: white;
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .deadline-badge {
            background: #fee2e2;
            color: var(--osta-red);
            padding: 4px 12px;
            border-radius: 20px;
            font-size: 0.8rem;
            font-weight: 600;
        }

        .how-to-apply { background: #f8faf8; padding: 80px 0; }

        .step-card {
            text-align: center;
            padding: 35px 25px;
            background: white;
            border-radius: 16px;
            box-shadow: 0 4px 20px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            height: 100%;
            border: 1px solid #f0f0f0;
        }

        .step-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 12px 35px rgba(0,0,0,0.1);
            border-color: var(--osta-green);
        }

        .step-number {
            width: 64px;
            height: 64px;
            background: linear-gradient(135deg, var(--osta-green), var(--osta-green-dark));
            color: white;
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 26px;
            font-weight: 800;
            margin: 0 auto 20px;
        }

        .btn-osta-primary {
            background: linear-gradient(135deg, var(--osta-green), var(--osta-green-dark));
            border: none;
            padding: 12px 30px;
            border-radius: 10px;
            font-weight: 600;
            color: white;
            transition: all 0.3s ease;
        }

        .btn-osta-primary:hover {
            transform: translateY(-2px);
            box-shadow: 0 8px 25px rgba(34,139,34,0.3);
            color: white;
        }

        .feature-box {
            text-align: center;
            padding: 30px 20px;
            border-radius: 14px;
            transition: all 0.3s ease;
            border: 1px solid #f0f0f0;
            height: 100%;
        }

        .feature-box:hover {
            background: #f0faf0;
            border-color: var(--osta-green);
        }

        .feature-icon {
            width: 70px;
            height: 70px;
            background: linear-gradient(135deg, rgba(34,139,34,0.1), rgba(34,139,34,0.05));
            border-radius: 16px;
            display: flex;
            align-items: center;
            justify-content: center;
            margin: 0 auto 18px;
            font-size: 28px;
            color: var(--osta-green);
        }

        .stats-bar {
            background: linear-gradient(135deg, var(--osta-dark), var(--osta-green-dark));
            padding: 60px 0;
            color: white;
        }

        .stat-number {
            font-size: 2.8rem;
            font-weight: 800;
            color: #90EE90;
            line-height: 1;
        }

        .stat-label {
            font-size: 0.9rem;
            color: rgba(255,255,255,0.65);
            text-transform: uppercase;
            letter-spacing: 1px;
            margin-top: 8px;
        }

        .cta-section {
            background: linear-gradient(135deg, var(--osta-green) 0%, var(--osta-green-dark) 100%);
            padding: 80px 0;
            text-align: center;
            color: white;
        }

        .btn-cta {
            background: white;
            color: var(--osta-green);
            border: none;
            padding: 14px 40px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 1.05rem;
            transition: all 0.3s ease;
        }

        .btn-cta:hover {
            transform: translateY(-3px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            color: var(--osta-green-dark);
        }

        .btn-cta-outline {
            background: transparent;
            color: white;
            border: 2px solid rgba(255,255,255,0.4);
            padding: 12px 38px;
            border-radius: 10px;
            font-weight: 700;
            font-size: 1.05rem;
            transition: all 0.3s ease;
        }

        .btn-cta-outline:hover {
            background: rgba(255,255,255,0.1);
            border-color: white;
            color: white;
        }

        @media (max-width: 768px) {
            .hero-title { font-size: 2rem; }
            .hero-stats { gap: 20px; flex-wrap: wrap; }
            .hero-stat-number { font-size: 1.5rem; }
            .stat-number { font-size: 2rem; }
        }
    </style>
</head>
<body>

<!-- Hero Section -->
<section class="hero-section">
    <div class="container">
        <div class="row">
            <div class="col-lg-10 mx-auto text-center">
                <div class="hero-badge">
                    <i class="fas fa-briefcase me-1"></i> Oromia Science & Technology Authority
                </div>
                <h1 class="hero-title">
                    Build Your Future<br>With <span>OSTA Careers</span>
                </h1>
                <p class="hero-subtitle">
                    Discover meaningful career opportunities across Oromia's leading science, technology, and innovation organizations.
                </p>

                <div class="hero-search">
                    <form method="GET" action="jobs.php" class="row g-3 align-items-end">
                        <div class="col-md-4">
                            <label class="form-label">Keywords</label>
                            <div class="input-group">
                                <span class="input-group-text"><i class="fas fa-search"></i></span>
                                <input type="text" class="form-control" name="search" 
                                       placeholder="Job title, skill, or keyword" 
                                       value="<?php echo htmlspecialchars($search); ?>">
                            </div>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Job Type</label>
                            <select class="form-select" name="job_type">
                                <option value="">All Types</option>
                                <option value="full_time" <?php echo $job_type === 'full_time' ? 'selected' : ''; ?>>Full-time</option>
                                <option value="part_time" <?php echo $job_type === 'part_time' ? 'selected' : ''; ?>>Part-time</option>
                                <option value="contract" <?php echo $job_type === 'contract' ? 'selected' : ''; ?>>Contract</option>
                                <option value="internship" <?php echo $job_type === 'internship' ? 'selected' : ''; ?>>Internship</option>
                            </select>
                        </div>
                        <div class="col-md-3">
                            <label class="form-label">Department</label>
                            <select class="form-select" name="department">
                                <option value="">All Departments</option>
                                <?php foreach ($departments as $dept): ?>
                                    <option value="<?php echo $dept['id']; ?>" <?php echo $department == $dept['id'] ? 'selected' : ''; ?>>
                                        <?php echo htmlspecialchars($dept['name']); ?>
                                    </option>
                                <?php endforeach; ?>
                            </select>
                        </div>
                        <div class="col-md-2">
                            <button type="submit" class="btn btn-hero">
                                <i class="fas fa-search me-1"></i> Search
                            </button>
                        </div>
                    </form>
                </div>

                <div class="hero-stats">
                    <div class="hero-stat">
                        <div class="hero-stat-number"><?php echo $total_jobs; ?>+</div>
                        <div class="hero-stat-label">Open Positions</div>
                    </div>
                    <div class="hero-stat">
                        <div class="hero-stat-number"><?php echo count($departments); ?>+</div>
                        <div class="hero-stat-label">Departments</div>
                    </div>
                    <div class="hero-stat">
                        <div class="hero-stat-number"><?php echo $total_applicants; ?>+</div>
                        <div class="hero-stat-label">Applicants</div>
                    </div>
                    <div class="hero-stat">
                        <div class="hero-stat-number"><?php echo $total_employers; ?>+</div>
                        <div class="hero-stat-label">Employers</div>
                    </div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Stats Bar -->
<section class="stats-bar">
    <div class="container">
        <div class="row">
            <div class="col-md-3 col-6">
                <div class="text-center">
                    <div class="stat-number"><?php echo $total_jobs; ?>+</div>
                    <div class="stat-label">Jobs Posted</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="text-center">
                    <div class="stat-number"><?php echo $total_applicants; ?>+</div>
                    <div class="stat-label">Registered Users</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="text-center">
                    <div class="stat-number"><?php echo count($departments); ?>+</div>
                    <div class="stat-label">Departments</div>
                </div>
            </div>
            <div class="col-md-3 col-6">
                <div class="text-center">
                    <div class="stat-number">100%</div>
                    <div class="stat-label">Free to Apply</div>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- Featured Jobs -->
<section class="py-5">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title h3 fw-bold">Featured Opportunities</h2>
            <p class="text-muted">Explore the latest openings from OSTA and partner organizations</p>
        </div>
        
        <div class="row">
            <?php if (empty($jobs)): ?>
                <div class="col-12 text-center py-5">
                    <div style="width:80px;height:80px;background:#f0faf0;border-radius:50%;display:flex;align-items:center;justify-content:center;margin:0 auto 20px;">
                        <i class="fas fa-briefcase fa-2x" style="color:var(--osta-green);"></i>
                    </div>
                    <h5 class="fw-bold">No Jobs Found</h5>
                    <p class="text-muted">Try adjusting your search or check back later.</p>
                    <a href="jobs.php" class="btn btn-osta-primary">View All Jobs</a>
                </div>
            <?php else: ?>
                <?php foreach ($jobs as $job): ?>
                    <div class="col-lg-4 col-md-6 mb-4">
                        <div class="card job-card h-100">
                            <div class="card-body d-flex flex-column">
                                <div class="d-flex justify-content-between align-items-start mb-3">
                                    <span class="job-type <?php echo $job['employment_type']; ?>">
                                        <?php echo ucfirst(str_replace('_', ' ', $job['employment_type'])); ?>
                                    </span>
                                    <?php if ($job['salary_range']): ?>
                                        <span class="salary-badge">
                                            <i class="fas fa-money-bill-wave me-1"></i><?php echo htmlspecialchars($job['salary_range']); ?>
                                        </span>
                                    <?php endif; ?>
                                </div>
                                
                                <h5 class="card-title mb-2">
                                    <a href="job_details.php?id=<?php echo $job['id']; ?>" class="text-decoration-none text-dark">
                                        <?php echo htmlspecialchars($job['title']); ?>
                                    </a>
                                </h5>
                                
                                <div class="mb-2">
                                    <small class="text-muted">
                                        <i class="fas fa-building me-1"></i>
                                        <?php echo htmlspecialchars($job['department_name']); ?>
                                    </small>
                                </div>
                                
                                <div class="mb-2">
                                    <small class="text-muted">
                                        <i class="fas fa-map-marker-alt me-1"></i>
                                        <?php echo htmlspecialchars($job['location']); ?>
                                    </small>
                                </div>
                                
                                <div class="mb-3">
                                    <small class="deadline-badge">
                                        <i class="fas fa-clock me-1"></i>
                                        Deadline: <?php echo date('M j, Y', strtotime($job['deadline'])); ?>
                                    </small>
                                </div>
                                
                                <p class="card-text text-muted mb-3 flex-grow-1" style="font-size:0.9rem;">
                                    <?php echo substr(strip_tags($job['description']), 0, 110) . '...'; ?>
                                </p>
                                
                                <div class="mt-auto">
                                    <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'applicant'): ?>
                                        <?php
                                        $check_stmt = $pdo->prepare("SELECT 1 FROM saved_jobs WHERE job_id = ? AND user_id = ?");
                                        $check_stmt->execute([$job['id'], $_SESSION['user_id']]);
                                        $is_saved = $check_stmt->fetch();
                                        
                                        $applied_stmt = $pdo->prepare("SELECT 1 FROM applications WHERE job_id = ? AND user_id = ?");
                                        $applied_stmt->execute([$job['id'], $_SESSION['user_id']]);
                                        $has_applied = $applied_stmt->fetch();
                                        ?>
                                        <form action="<?php echo SITE_URL; ?>/applicant/save_job.php" method="POST" class="d-inline">
                                            <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                            <button type="submit" class="btn btn-sm btn-outline-success mb-2">
                                                <i class="fas <?php echo $is_saved ? 'fa-bookmark' : 'fa-bookmark-o'; ?>"></i>
                                            </button>
                                        </form>
                                        
                                        <?php if ($has_applied): ?>
                                            <button class="btn btn-success w-100 mb-2" disabled>
                                                <i class="fas fa-check-circle me-1"></i> Applied
                                            </button>
                                        <?php else: ?>
                                            <a href="<?php echo SITE_URL; ?>/applicant/apply.php?job_id=<?php echo $job['id']; ?>" class="btn btn-osta-primary w-100 mb-2">
                                                <i class="fas fa-paper-plane me-1"></i> Apply Now
                                            </a>
                                        <?php endif; ?>
                                    <?php else: ?>
                                        <a href="login.php" class="btn btn-osta-primary w-100 mb-2">
                                            <i class="fas fa-paper-plane me-1"></i> Apply Now
                                        </a>
                                    <?php endif; ?>
                                    
                                    <a href="job_details.php?id=<?php echo $job['id']; ?>" class="btn btn-outline-secondary w-100">
                                        <i class="fas fa-info-circle me-1"></i> View Details
                                    </a>
                                </div>
                            </div>
                        </div>
                    </div>
                <?php endforeach; ?>
            <?php endif; ?>
        </div>
        
        <?php if ($total_jobs > 6): ?>
            <div class="text-center mt-4">
                <a href="jobs.php" class="btn btn-osta-primary btn-lg">
                    <i class="fas fa-search me-2"></i>Browse All <?php echo $total_jobs; ?> Jobs
                </a>
            </div>
        <?php endif; ?>
    </div>
</section>

<!-- How to Apply -->
<section class="how-to-apply">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title h3 fw-bold">How to Apply</h2>
            <p class="text-muted fs-5">Four simple steps to your next career opportunity</p>
        </div>
        
        <div class="row g-4">
            <div class="col-lg-3 col-md-6">
                <div class="step-card">
                    <div class="step-number">1</div>
                    <h5 class="fw-bold mb-3">Create Account</h5>
                    <p class="text-muted">Register for free and complete your professional profile in minutes.</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="step-card">
                    <div class="step-number">2</div>
                    <h5 class="fw-bold mb-3">Browse & Search</h5>
                    <p class="text-muted">Explore job listings and filter by department, type, or location.</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="step-card">
                    <div class="step-number">3</div>
                    <h5 class="fw-bold mb-3">Apply Online</h5>
                    <p class="text-muted">Submit your application with resume and documents in one click.</p>
                </div>
            </div>
            <div class="col-lg-3 col-md-6">
                <div class="step-card">
                    <div class="step-number">4</div>
                    <h5 class="fw-bold mb-3">Track & Get Hired</h5>
                    <p class="text-muted">Monitor your application status and receive interview invitations.</p>
                </div>
            </div>
        </div>
        
        <div class="text-center mt-5">
            <?php if (!isset($_SESSION['user_id'])): ?>
                <a href="register.php" class="btn btn-osta-primary btn-lg me-3">
                    <i class="fas fa-user-plus me-2"></i>Get Started Free
                </a>
                <a href="jobs.php" class="btn btn-outline-primary btn-lg">
                    <i class="fas fa-search me-2"></i>Browse Jobs
                </a>
            <?php else: ?>
                <a href="jobs.php" class="btn btn-osta-primary btn-lg">
                    <i class="fas fa-search me-2"></i>Browse All Jobs
                </a>
            <?php endif; ?>
        </div>
    </div>
</section>

<!-- Why OSTA -->
<section class="py-5 bg-white">
    <div class="container">
        <div class="text-center mb-5">
            <h2 class="section-title h3 fw-bold">Why OSTA?</h2>
            <p class="text-muted">We connect talent with purpose across the Oromia region</p>
        </div>
        <div class="row g-4">
            <div class="col-lg-4 col-md-6">
                <div class="feature-box">
                    <div class="feature-icon"><i class="fas fa-shield-alt"></i></div>
                    <h5 class="fw-bold">Secure & Trusted</h5>
                    <p class="text-muted mb-0">Government-backed platform with enterprise-grade security.</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="feature-box">
                    <div class="feature-icon"><i class="fas fa-bolt"></i></div>
                    <h5 class="fw-bold">Quick Process</h5>
                    <p class="text-muted mb-0">Streamlined application with real-time status tracking.</p>
                </div>
            </div>
            <div class="col-lg-4 col-md-6">
                <div class="feature-box">
                    <div class="feature-icon"><i class="fas fa-map-marked-alt"></i></div>
                    <h5 class="fw-bold">Oromia-Wide</h5>
                    <p class="text-muted mb-0">Opportunities across all departments and regions.</p>
                </div>
            </div>
        </div>
    </div>
</section>

<!-- CTA Section -->
<section class="cta-section">
    <div class="container">
        <h2 class="fw-bold" style="margin-bottom:15px;">Ready to Start Your Career?</h2>
        <p style="color:rgba(255,255,255,0.8);max-width:500px;margin:0 auto 30px;">Join thousands of professionals who found their dream job through OSTA Job Portal.</p>
        <?php if (!isset($_SESSION['user_id'])): ?>
            <a href="register.php" class="btn btn-cta me-3">
                <i class="fas fa-user-plus me-2"></i>Register Now
            </a>
            <a href="jobs.php" class="btn btn-cta-outline">
                <i class="fas fa-search me-2"></i>Explore Jobs
            </a>
        <?php else: ?>
            <a href="jobs.php" class="btn btn-cta">
                <i class="fas fa-search me-2"></i>Explore Jobs
            </a>
        <?php endif; ?>
    </div>
</section>

<?php include __DIR__ . '/includes/footer.php'; ?>
