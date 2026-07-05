<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

$role = isset($_SESSION['role']) ? $_SESSION['role'] : '';
$is_logged_in = isset($_SESSION['user_id']);
$username = isset($_SESSION['username']) ? $_SESSION['username'] : 'User';
?>
<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title><?php echo isset($page_title) ? htmlspecialchars($page_title) . ' - ' : ''; ?>OSTA Job Portal</title>
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.4.0/css/all.min.css">
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/styles.css">
</head>
<body>

<a href="#main-content" class="visually-hidden-focusable position-absolute top-0 start-0 bg-white text-dark px-3 py-2 z-3" style="z-index:9999;">
    Skip to main content
</a>

<nav class="navbar navbar-expand-lg navbar-dark" role="navigation" aria-label="Main navigation" style="background: linear-gradient(135deg, #1a2332, #1a6b1a);">
    <div class="container">
        <a class="navbar-brand d-flex align-items-center fw-bold" href="<?php echo SITE_URL; ?>/index.php" aria-label="OSTA Jobs Home" style="font-size: 1.25rem;">
            <i class="fas fa-briefcase me-2" style="color: #90EE90;" aria-hidden="true"></i>
            <span>OSTA</span><span style="color: #90EE90; margin-left: 2px;">Jobs</span>
        </a>
        
        <button class="navbar-toggler border-0" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav" aria-controls="mainNav" aria-expanded="false" aria-label="Toggle navigation menu">
            <span class="navbar-toggler-icon"></span>
        </button>
        
        <div class="collapse navbar-collapse" id="mainNav">
            <ul class="navbar-nav me-auto ms-lg-3">
                <?php if (!$is_logged_in): ?>
                    <li class="nav-item">
                        <a class="nav-link px-3" href="<?php echo SITE_URL; ?>/index.php"><i class="fas fa-home me-1"></i> Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-3" href="<?php echo SITE_URL; ?>/jobs.php"><i class="fas fa-search me-1"></i> Find Jobs</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-3" href="<?php echo SITE_URL; ?>/about.php"><i class="fas fa-info-circle me-1"></i> About</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link px-3" href="<?php echo SITE_URL; ?>/contact.php"><i class="fas fa-envelope me-1"></i> Contact</a>
                    </li>
                <?php endif; ?>
            </ul>
            
            <ul class="navbar-nav ms-auto">
                <?php if ($is_logged_in): ?>
                    <li class="nav-item dropdown">
                        <a class="nav-link dropdown-toggle d-flex align-items-center px-3" href="#" role="button" data-bs-toggle="dropdown">
                            <i class="fas fa-user-circle me-1"></i> <?php echo htmlspecialchars($username); ?>
                        </a>
                        <ul class="dropdown-menu dropdown-menu-end">
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/<?php echo $role; ?>/dashboard.php"><i class="fas fa-tachometer-alt me-2"></i>Dashboard</a></li>
                            <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/<?php echo $role; ?>/profile.php"><i class="fas fa-user me-2"></i>Profile</a></li>
                            <li><hr class="dropdown-divider"></li>
                            <li><a class="dropdown-item text-danger" href="<?php echo SITE_URL; ?>/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                        </ul>
                    </li>
                <?php else: ?>
                    <li class="nav-item">
                        <a class="nav-link px-3" href="<?php echo SITE_URL; ?>/login.php">
                            <i class="fas fa-sign-in-alt me-1"></i> Login
                        </a>
                    </li>
                    <li class="nav-item ms-lg-2">
                        <a class="btn btn-light btn-sm px-3 fw-bold" href="<?php echo SITE_URL; ?>/register.php" style="border-radius: 8px;">
                            <i class="fas fa-user-plus me-1"></i> Register
                        </a>
                    </li>
                <?php endif; ?>
            </ul>
        </div>
    </div>
</nav>
