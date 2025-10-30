<?php
if (session_status() === PHP_SESSION_NONE) {
    session_start();
}
// This file is included after database.php, so SITE_URL is available.
?>
    <!-- Navigation -->
    <nav class="navbar navbar-expand-lg navbar-dark bg-primary sticky-top">
        <div class="container">
            <a class="navbar-brand d-flex align-items-center" href="<?php echo SITE_URL; ?>/index.php">
                <i class="fas fa-briefcase me-2"></i>
                <span>OSTA Job Portal</span>
            </a>
            
            <button class="navbar-toggler" type="button" data-bs-toggle="collapse" data-bs-target="#mainNav">
                <span class="navbar-toggler-icon"></span>
            </button>
            
            <div class="collapse navbar-collapse" id="mainNav">
                <ul class="navbar-nav me-auto">
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/index.php"><i class="fas fa-home me-1"></i> Home</a>
                    </li>
                    <li class="nav-item">
                        <a class="nav-link" href="<?php echo SITE_URL; ?>/jobs.php"><i class="fas fa-search me-1"></i> Find Jobs</a>
                    </li>
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <?php if ($_SESSION['role'] === 'applicant'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo SITE_URL; ?>/applicant/dashboard.php"><i class="fas fa-tachometer-alt me-1"></i> Dashboard</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo SITE_URL; ?>/applicant/job_alerts.php"><i class="fas fa-bell me-1"></i> Job Alerts</a>
                            </li>
                        <?php elseif ($_SESSION['role'] === 'employer'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo SITE_URL; ?>/employer/dashboard.php"><i class="fas fa-tachometer-alt me-1"></i> Dashboard</a>
                            </li>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo SITE_URL; ?>/employer/post_job.php"><i class="fas fa-plus-circle me-1"></i> Post a Job</a>
                            </li>
                        <?php elseif ($_SESSION['role'] === 'admin'): ?>
                            <li class="nav-item">
                                <a class="nav-link" href="<?php echo SITE_URL; ?>/admin/dashboard.php"><i class="fas fa-tachometer-alt me-1"></i> Admin Dashboard</a>
                            </li>
                        <?php endif; ?>
                    <?php endif; ?>
                </ul>
                
                <ul class="navbar-nav ms-auto">
                    <?php if (isset($_SESSION['user_id'])): ?>
                        <!-- Notifications Dropdown -->
                        <li class="nav-item dropdown me-3">
                            <a class="nav-link position-relative" href="#" id="notificationsDropdown" role="button" data-bs-toggle="dropdown" aria-expanded="false">
                                <i class="fas fa-bell"></i>
                                <?php
                                // Get unread notifications count
                                if (isset($_SESSION['user_id'])) {
                                    $unread_count = 0;
                                    try {
                                        $stmt = $pdo->prepare("SELECT COUNT(*) FROM notifications 
                                                             WHERE (target = 'all' OR (target = 'user' AND target_id = ?)) 
                                                             AND status = 'unread'");
                                        $stmt->execute([$_SESSION['user_id']]);
                                        $unread_count = $stmt->fetchColumn();
                                    } catch (Exception $e) {
                                        error_log("Error getting unread notifications: " . $e->getMessage());
                                    }
                                    
                                    if ($unread_count > 0) {
                                        echo '<span class="position-absolute top-0 start-100 translate-middle badge rounded-pill bg-danger">' . 
                                             $unread_count . 
                                             '<span class="visually-hidden">unread notifications</span></span>';
                                    }
                                }
                                ?>
                            </a>
                            <div class="dropdown-menu dropdown-menu-end dropdown-menu-lg-start p-0" aria-labelledby="notificationsDropdown" style="min-width: 350px;">
                                <div class="card">
                                    <div class="card-header d-flex justify-content-between align-items-center">
                                        <h6 class="mb-0">Notifications</h6>
                                        <a href="<?php echo SITE_URL; ?>/notifications.php" class="small">View All</a>
                                    </div>
                                    <div class="card-body p-0">
                                        <?php
                                        if (isset($_SESSION['user_id'])) {
                                            try {
                                                $stmt = $pdo->prepare("SELECT n.*, u.name as creator_name 
                                                      FROM notifications n 
                                                      JOIN users u ON n.created_by = u.id 
                                                      WHERE (n.target = 'all' OR (n.target = 'user' AND n.target_id = ?))
                                                      ORDER BY n.created_at DESC 
                                                      LIMIT 5");
                                                $stmt->execute([$_SESSION['user_id']]);
                                                $notifications = $stmt->fetchAll();
                                                
                                                if (count($notifications) > 0) {
                                                    echo '<div class="list-group list-group-flush">';
                                                    foreach ($notifications as $notification) {
                                                        $badge_class = 'bg-secondary';
                                                        if ($notification['status'] === 'unread') {
                                                            $badge_class = 'bg-primary';
                                                        }
                                                        echo '<a href="#" class="list-group-item list-group-item-action ' . 
                                                             ($notification['status'] === 'unread' ? 'fw-bold' : '') . '">';
                                                        echo '<div class="d-flex w-100 justify-content-between">';
                                                        echo '<h6 class="mb-1">' . htmlspecialchars($notification['title']) . '</h6>';
                                                        echo '<small class="text-muted" title="' . date('M j, Y g:i A', strtotime($notification['created_at'])) . '">' .
                                                             time_elapsed_string($notification['created_at']) .
                                                             (!empty($notification['creator_name']) ? '<br><small>by ' . htmlspecialchars($notification['creator_name']) . '</small>' : '') .
                                                             '</small>';
                                                        echo '</div>';
                                                        echo '<p class="mb-1">' . 
                                                             nl2br(htmlspecialchars(substr($notification['message'], 0, 100) . 
                                                             (strlen($notification['message']) > 100 ? '...' : ''))) . 
                                                             '</p>';
                                                        echo '</a>';
                                                    }
                                                    echo '</div>';
                                                } else {
                                                    echo '<div class="text-center p-3 text-muted">No notifications</div>';
                                                }
                                            } catch (Exception $e) {
                                                error_log("Error fetching notifications: " . $e->getMessage());
                                                echo '<div class="text-center p-3 text-danger">Error loading notifications</div>';
                                            }
                                        }
                                        ?>
                                    </div>
                                </div>
                            </div>
                        </li>
                        
                        <!-- User Dropdown -->
                        <li class="nav-item dropdown">
                            <a class="nav-link dropdown-toggle d-flex align-items-center" href="#" id="userDropdown" role="button" data-bs-toggle="dropdown">
                                <i class="fas fa-user-circle me-1"></i>
                                <?php echo htmlspecialchars(isset($_SESSION['name']) ? $_SESSION['name'] : 'User'); ?>
                            </a>
                            <ul class="dropdown-menu dropdown-menu-end">
                                <?php if ($_SESSION['role'] === 'applicant'): ?>
                                    <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/applicant/profile.php"><i class="fas fa-user me-2"></i>My Profile</a></li>
                                    <li><a class="dropdown-item" href="<?php echo SITE_URL; ?>/applicant/applications.php"><i class="fas fa-file-alt me-2"></i>My Applications</a></li>
                                    <li><hr class="dropdown-divider"></li>
                                <?php endif; ?>
                                <li><a class="dropdown-item text-danger" href="<?php echo SITE_URL; ?>/logout.php"><i class="fas fa-sign-out-alt me-2"></i>Logout</a></li>
                            </ul>
                        </li>
                    <?php else: ?>
                        <li class="nav-item">
                            <a class="btn btn-outline-light me-2" href="<?php echo SITE_URL; ?>/login.php"><i class="fas fa-sign-in-alt me-1"></i> Login</a>
                        </li>
                        <li class="nav-item">
                            <a class="btn btn-light" href="<?php echo SITE_URL; ?>/register.php"><i class="fas fa-user-plus me-1"></i> Register</a>
                        </li>
                    <?php endif; ?>
                </ul>
            </div>
        </div>
    </nav>

    <!-- Main Content Container -->
    <main class="py-4">
        <div class="container">
            <?php if (isset($_SESSION['success_message'])): ?>
                <div class="alert alert-success alert-dismissible fade show" role="alert">
                    <?php 
                    echo $_SESSION['success_message'];
                    unset($_SESSION['success_message']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
            
            <?php if (isset($_SESSION['error_message'])): ?>
                <div class="alert alert-danger alert-dismissible fade show" role="alert">
                    <?php 
                    echo $_SESSION['error_message'];
                    unset($_SESSION['error_message']);
                    ?>
                    <button type="button" class="btn-close" data-bs-dismiss="alert" aria-label="Close"></button>
                </div>
            <?php endif; ?>
