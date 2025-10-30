<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}

// This file is included after database.php, so SITE_URL is available.

// Handle navigation links based on user status
if (empty($_SESSION['user_id'])) {
    $nav_links = '<li class="nav-item"><a class="nav-link" href="' . SITE_URL . '/login.php">Login</a></li>
                  <li class="nav-item"><a class="nav-link" href="' . SITE_URL . '/register.php">Register</a></li>';
} else {
    // Get role safely
    $role = isset($_SESSION['role']) ? $_SESSION['role'] : 'applicant';
    $dashboard_path = SITE_URL . '/' . $role . '/dashboard.php';
    $logout_path = SITE_URL . '/logout.php'; // Central logout script

    $nav_links = '<li class="nav-item"><a class="nav-link" href="' . $dashboard_path . '">Dashboard</a></li>
                  <li class="nav-item"><a class="nav-link" href="' . $logout_path . '">Logout</a></li>';
}
?>

<!DOCTYPE html>
<html lang="en">
<head>
    <meta charset="UTF-8">
    <meta name="viewport" content="width=device-width, initial-scale=1.0">
    <title>OSTA Job Portal</title>
    <!-- Bootstrap CSS -->
    <link href="https://cdn.jsdelivr.net/npm/bootstrap@5.1.3/dist/css/bootstrap.min.css" rel="stylesheet">
    <!-- Font Awesome -->
    <link rel="stylesheet" href="https://cdnjs.cloudflare.com/ajax/libs/font-awesome/6.0.0-beta3/css/all.min.css">
    <!-- Custom CSS -->
    <link rel="stylesheet" href="<?php echo SITE_URL; ?>/assets/css/styles.css">
</head>
<body>
<nav class="navbar navbar-expand-lg navbar-dark bg-primary">
    <div class="container">
        <a class="navbar-brand" href="<?php echo SITE_URL; ?>/index.php">OSTA Job Portal</a>
        <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#navbarNav">
            <span class="navbar-toggler-icon"></span>
        </button>
        <div class="collapse navbar-collapse" id="navbarNav">
            <ul class="navbar-nav ms-auto">
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo SITE_URL; ?>/index.php">Home</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo SITE_URL; ?>/jobs.php">Jobs</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo SITE_URL; ?>/about.php">About</a>
                </li>
                <li class="nav-item">
                    <a class="nav-link" href="<?php echo SITE_URL; ?>/contact.php">Contact</a>
                </li>
                <?php echo $nav_links; ?>
            </ul>
        </div>
    </div>
</nav>
