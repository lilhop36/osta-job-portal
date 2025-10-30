<?php
// Start session at the very beginning
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

require_once __DIR__ . '/config/database.php';

// Get search filters
$search = isset($_GET['search']) ? sanitize($_GET['search']) : '';
$location = isset($_GET['location']) ? sanitize($_GET['location']) : '';
$job_type = isset($_GET['job_type']) ? sanitize($_GET['job_type']) : '';
$department = isset($_GET['department']) ? (int)$_GET['department'] : 0;
$page = isset($_GET['page']) ? max(1, (int)$_GET['page']) : 1;
$per_page = 12; // Show more jobs on homepage
$offset = ($page - 1) * $per_page;

// Build query (matching jobs.php structure)
$query = "SELECT j.*, IFNULL(d.name, 'Admin Posted') as department_name 
          FROM jobs j 
          LEFT JOIN departments d ON j.department_id = d.id 
          WHERE j.status = 'approved' AND j.deadline >= CURDATE()";

$params = [];

// Add search conditions
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

// Get total count for pagination
$count_query = "SELECT COUNT(*) as total FROM ($query) as count_table";
$count_stmt = $pdo->prepare($count_query);
$count_stmt->execute($params);
$total_jobs = $count_stmt->fetch()['total'];
$total_pages = ceil($total_jobs / $per_page);

// Add sorting and pagination
$query .= " ORDER BY j.created_at DESC LIMIT " . (int)$per_page . " OFFSET " . (int)$offset;

// Execute query
$stmt = $pdo->prepare($query);
$stmt->execute($params);
$jobs = $stmt->fetchAll();

// Get departments for filter dropdown
$dept_stmt = $pdo->query("SELECT id, name FROM departments ORDER BY name");
$departments = $dept_stmt->fetchAll();
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OSTA Job Portal - Find Your Future Career</title>
    <!-- Bootstrap 5 CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome 6 -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="assets/css/styles.css">
    <style>
        :root {
            --osta-green: #228B22;
            --osta-red: #DC143C;
            --osta-gold: #DAA520;
            --osta-dark: #2C3E50;
        }
        
        .hero-section {
            background: linear-gradient(135deg, var(--osta-green) 0%, var(--osta-dark) 100%);
            padding: 100px 0;
            color: white;
        }
        
        .hero-search {
            background: rgba(255,255,255,0.95);
            padding: 30px;
            border-radius: 15px;
            box-shadow: 0 10px 30px rgba(0,0,0,0.2);
            margin-top: 40px;
        }
        
        .job-card {
            transition: all 0.3s ease;
            border: none;
            box-shadow: 0 5px 15px rgba(0,0,0,0.08);
            border-radius: 12px;
            overflow: hidden;
        }
        
        .job-card:hover {
            transform: translateY(-8px);
            box-shadow: 0 15px 35px rgba(0,0,0,0.15);
        }
        
        .job-type {
            font-size: 0.8rem;
            padding: 0.3rem 0.8rem;
            border-radius: 20px;
            font-weight: 600;
            text-transform: uppercase;
            letter-spacing: 0.5px;
        }
        
        .job-type.full_time { background-color: #d4edda; color: #155724; }
        .job-type.part_time { background-color: #fff3cd; color: #856404; }
        .job-type.contract { background-color: #cce5ff; color: #004085; }
        .job-type.internship { background-color: #f8d7da; color: #721c24; }
        
        .section-title {
            position: relative;
            margin-bottom: 50px;
        }
        
        .section-title::after {
            content: '';
            position: absolute;
            bottom: -10px;
            left: 50%;
            transform: translateX(-50%);
            width: 60px;
            height: 3px;
            background: var(--osta-green);
        }
        
        .how-to-apply {
            background: #f8f9fa;
            padding: 80px 0;
        }
        
        .step-card {
            text-align: center;
            padding: 30px 20px;
            background: white;
            border-radius: 15px;
            box-shadow: 0 5px 20px rgba(0,0,0,0.05);
            transition: all 0.3s ease;
            height: 100%;
        }
        
        .step-card:hover {
            transform: translateY(-5px);
            box-shadow: 0 10px 30px rgba(0,0,0,0.1);
        }
        
        .step-number {
            width: 60px;
            height: 60px;
            background: var(--osta-green);
            color: white;
            border-radius: 50%;
            display: flex;
            align-items: center;
            justify-content: center;
            font-size: 24px;
            font-weight: bold;
            margin: 0 auto 20px;
        }
        
        .about-osta {
            padding: 80px 0;
            background: white;
        }
        
        .btn-osta-primary {
            background: var(--osta-green);
            border: none;
            padding: 12px 30px;
            border-radius: 25px;
            font-weight: 600;
            transition: all 0.3s ease;
        }
        
        .btn-osta-primary:hover {
            background: #1e7a1e;
            transform: translateY(-2px);
            box-shadow: 0 5px 15px rgba(34, 139, 34, 0.3);
        }
        
        .salary-badge {
            background: var(--osta-gold);
            color: white;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 600;
        }
        
        .deadline-badge {
            background: var(--osta-red);
            color: white;
            padding: 5px 12px;
            border-radius: 15px;
            font-size: 0.85rem;
            font-weight: 600;
        }
    </style>
</head>
<body>
    <?php include __DIR__ . '/includes/header.php'; ?>

    <!-- Hero Section -->
    <div class="hero-section">
        <div class="container">
            <div class="row">
                <div class="col-lg-10 mx-auto text-center">
                    <h1 class="display-4 fw-bold mb-4">Find Your Future Career at OSTA & Partners</h1>
                    <p class="lead mb-4 fs-5">Connecting skilled professionals with opportunities across Oromia</p>
                    
                    <!-- Hero Search Form -->
                    <div class="hero-search">
                        <form method="GET" action="" class="row g-3 align-items-end">
                            <div class="col-md-4">
                                <label class="form-label text-dark fw-bold">Keywords</label>
                                <div class="input-group">
                                    <span class="input-group-text"><i class="fas fa-search text-muted"></i></span>
                                    <input type="text" class="form-control" name="search" 
                                           placeholder="e.g., Engineer, Manager..." 
                                           value="<?php echo htmlspecialchars($search); ?>">
                                </div>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label text-dark fw-bold">Job Type</label>
                                <select class="form-select" name="job_type">
                                    <option value="">All Types</option>
                                    <option value="full_time" <?php echo $job_type === 'full_time' ? 'selected' : ''; ?>>Full-time</option>
                                    <option value="part_time" <?php echo $job_type === 'part_time' ? 'selected' : ''; ?>>Part-time</option>
                                    <option value="contract" <?php echo $job_type === 'contract' ? 'selected' : ''; ?>>Contract</option>
                                    <option value="internship" <?php echo $job_type === 'internship' ? 'selected' : ''; ?>>Internship</option>
                                </select>
                            </div>
                            <div class="col-md-3">
                                <label class="form-label text-dark fw-bold">Department</label>
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
                                <button type="submit" class="btn btn-osta-primary w-100">
                                    <i class="fas fa-search me-2"></i>Search Jobs
                                </button>
                            </div>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    </div>

    <!-- Featured Jobs Section -->
    <section class="py-5">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title h3 fw-bold">Featured Jobs / Recent Openings</h2>
                <p class="text-muted">Discover exciting career opportunities with OSTA and partner organizations</p>
            </div>
            
            <div class="row">
                <?php if (empty($jobs)): ?>
                    <div class="col-12 text-center py-5">
                        <i class="fas fa-briefcase fa-3x text-muted mb-3"></i>
                        <h4 class="text-muted">No jobs found</h4>
                        <p class="text-muted">Try adjusting your search criteria or check back later for new opportunities.</p>
                        <a href="index.php" class="btn btn-osta-primary">View All Jobs</a>
                    </div>
                <?php else: ?>
                    <?php foreach (array_slice($jobs, 0, 6) as $job): // Show only first 6 jobs on homepage ?>
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
                                    
                                    <h5 class="card-title mb-3">
                                        <a href="job_details.php?id=<?php echo $job['id']; ?>" class="text-decoration-none text-dark">
                                            <?php echo htmlspecialchars($job['title']); ?>
                                        </a>
                                    </h5>
                                    
                                    <div class="mb-3">
                                        <small class="text-muted">
                                            <i class="fas fa-building me-1"></i>
                                            <?php echo htmlspecialchars($job['department_name']); ?>
                                        </small>
                                    </div>
                                    
                                    <div class="mb-3">
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
                                    
                                    <p class="card-text text-muted mb-3 flex-grow-1">
                                        <?php echo substr(strip_tags($job['description']), 0, 120) . '...'; ?>
                                    </p>
                                    
                                    <div class="mt-auto">
                                        <?php if (isset($_SESSION['user_id']) && $_SESSION['role'] === 'applicant'): ?>
                                            <?php
                                            // Check if job is saved
                                            $check_stmt = $pdo->prepare("SELECT 1 FROM saved_jobs WHERE job_id = ? AND user_id = ?");
                                            $check_stmt->execute([$job['id'], $_SESSION['user_id']]);
                                            $is_saved = $check_stmt->fetch();
                                            
                                            // Check if already applied
                                            $applied_stmt = $pdo->prepare("SELECT 1 FROM applications WHERE job_id = ? AND user_id = ?");
                                            $applied_stmt->execute([$job['id'], $_SESSION['user_id']]);
                                            $has_applied = $applied_stmt->fetch();
                                            ?>
                                            <form action="<?php echo SITE_URL; ?>/applicant/save_job.php" method="POST" class="d-inline" id="save-form-<?php echo $job['id']; ?>">
                                                <input type="hidden" name="job_id" value="<?php echo $job['id']; ?>">
                                                <button type="submit" class="btn btn-sm btn-outline-primary mb-2" 
                                                        title="<?php echo $is_saved ? 'Remove from saved jobs' : 'Save for later'; ?>">
                                                    <i class="fas <?php echo $is_saved ? 'fa-bookmark' : 'fa-bookmark-o'; ?> me-1"></i>
                                                    <?php echo $is_saved ? 'Saved' : 'Save'; ?>
                                                </button>
                                            </form>
                                            
                                            <?php if ($has_applied): ?>
                                                <button class="btn btn-success w-100 mb-2" disabled>
                                                    <i class="fas fa-check-circle me-1"></i> Applied
                                                </button>
                                            <?php else: ?>
                                                <a href="<?php echo SITE_URL; ?>/applicant/apply.php?job_id=<?php echo $job['id']; ?>" 
                                                   class="btn btn-primary w-100 mb-2">
                                                    <i class="fas fa-paper-plane me-1"></i> Apply Now
                                                </a>
                                            <?php endif; ?>
                                            
                                        <?php elseif (!isset($_SESSION['user_id'])): ?>
                                            <a href="<?php echo SITE_URL; ?>/login.php?redirect=<?php echo urlencode(SITE_URL . '/applicant/apply.php?job_id=' . $job['id']); ?>" 
                                               class="btn btn-outline-primary mb-2" title="Login to save jobs">
                                                <i class="fas fa-bookmark-o me-1"></i> Save
                                            </a>
                                            <a href="<?php echo SITE_URL; ?>/login.php?redirect=<?php echo urlencode(SITE_URL . '/applicant/apply.php?job_id=' . $job['id']); ?>" 
                                               class="btn btn-primary w-100 mb-2">
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
            
            <?php if (count($jobs) > 6): ?>
                <div class="text-center mt-4">
                    <a href="jobs.php" class="btn btn-outline-primary btn-lg">
                        <i class="fas fa-search me-2"></i>Browse All Jobs
                    </a>
                </div>
            <?php endif; ?>
        </div>
    </section>
    <!-- How to Apply Section -->
    <section class="how-to-apply">
        <div class="container">
            <div class="text-center mb-5">
                <h2 class="section-title h3 fw-bold">How to Apply</h2>
                <p class="text-muted fs-5">Whether you're a new graduate or a seasoned expert, we're here to help you find the right fit.</p>
            </div>
            
            <div class="row g-4">
                <div class="col-lg-3 col-md-6">
                    <div class="step-card">
                        <div class="step-number">1</div>
                        <h5 class="fw-bold mb-3">Browse Jobs</h5>
                        <p class="text-muted">Explore our comprehensive job listings and find positions that match your skills and interests.</p>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="step-card">
                        <div class="step-number">2</div>
                        <h5 class="fw-bold mb-3">Register/Login</h5>
                        <p class="text-muted">Create your account or login to access our application system and manage your job applications.</p>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="step-card">
                        <div class="step-number">3</div>
                        <h5 class="fw-bold mb-3">Fill Application</h5>
                        <p class="text-muted">Complete your application with your resume, cover letter, and relevant documents.</p>
                    </div>
                </div>
                
                <div class="col-lg-3 col-md-6">
                    <div class="step-card">
                        <div class="step-number">4</div>
                        <h5 class="fw-bold mb-3">Wait for Response</h5>
                        <p class="text-muted">Track your application status and receive notifications about interview invitations and decisions.</p>
                    </div>
                </div>
            </div>
            
            <div class="text-center mt-5">
                <?php if (!isset($_SESSION['user_id'])): ?>
                    <a href="register.php" class="btn btn-osta-primary btn-lg me-3">
                        <i class="fas fa-user-plus me-2"></i>Get Started
                    </a>
                    <a href="jobs.php" class="btn btn-outline-primary btn-lg">
                        <i class="fas fa-search me-2"></i>Browse All Jobs
                    </a>
                <?php else: ?>
                    <a href="jobs.php" class="btn btn-osta-primary btn-lg">
                        <i class="fas fa-search me-2"></i>Browse All Jobs
                    </a>
                <?php endif; ?>
            </div>
        </div>
    </section>
    
    <!-- About OSTA Section -->
    <section class="about-osta">
        <div class="container">
            <div class="row align-items-center">
                <div class="col-lg-6">
                    <h2 class="section-title h3 fw-bold text-start">About OSTA</h2>
                    <p class="lead mb-4">The Oromia Science and Technology Authority (OSTA) is committed to advancing scientific research, technological innovation, and human resource development across the Oromia region.</p>
                    
                    <div class="row g-4 mb-4">
                        <div class="col-sm-6">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-users fa-2x text-success"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="fw-bold mb-1">Expert Team</h6>
                                    <p class="text-muted mb-0">Skilled professionals across various fields</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-sm-6">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-lightbulb fa-2x text-warning"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="fw-bold mb-1">Innovation</h6>
                                    <p class="text-muted mb-0">Driving technological advancement</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-sm-6">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-graduation-cap fa-2x text-primary"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="fw-bold mb-1">Development</h6>
                                    <p class="text-muted mb-0">Continuous learning and growth</p>
                                </div>
                            </div>
                        </div>
                        
                        <div class="col-sm-6">
                            <div class="d-flex align-items-center">
                                <div class="flex-shrink-0">
                                    <i class="fas fa-handshake fa-2x text-info"></i>
                                </div>
                                <div class="flex-grow-1 ms-3">
                                    <h6 class="fw-bold mb-1">Partnership</h6>
                                    <p class="text-muted mb-0">Collaborating for regional progress</p>
                                </div>
                            </div>
                        </div>
                    </div>
                    
                    <a href="#" class="btn btn-outline-primary">
                        <i class="fas fa-external-link-alt me-2"></i>Visit Official Website
                    </a>
                </div>
                
                <div class="col-lg-6">
                    <div class="text-center">
                        <i class="fas fa-building fa-5x text-success mb-4"></i>
                        <h4 class="fw-bold mb-3">Join Our Mission</h4>
                        <p class="text-muted mb-4">Be part of Oromia's technological transformation and contribute to building a better future for our region.</p>
                        
                        <div class="row text-center">
                            <div class="col-4">
                                <h3 class="fw-bold text-success"><?php echo $total_jobs; ?>+</h3>
                                <p class="text-muted small mb-0">Active Jobs</p>
                            </div>
                            <div class="col-4">
                                <h3 class="fw-bold text-success"><?php echo count($departments); ?>+</h3>
                                <p class="text-muted small mb-0">Departments</p>
                            </div>
                            <div class="col-4">
                                <h3 class="fw-bold text-success">100+</h3>
                                <p class="text-muted small mb-0">Partners</p>
                            </div>
                        </div>
                    </div>
                </div>
            </div>
        </div>
    </section>

    <?php include 'includes/footer.php'; ?>

    <script src="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/js/bootstrap.bundle.min.js"></script>
</body>
</html>
