<div class="card dashboard-card">
    <div class="card-header bg-gradient-primary text-white">
        <h3 class="mb-0"><i class="fas fa-building me-2"></i>Employer Menu</h3>
    </div>
    <div class="list-group list-group-flush dashboard-sidebar">
        <a href="dashboard.php" class="list-group-item list-group-item-action <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
            <i class="fas fa-tachometer-alt me-2"></i> Dashboard
        </a>
        <a href="post_job.php" class="list-group-item list-group-item-action <?= basename($_SERVER['PHP_SELF']) == 'post_job.php' ? 'active' : '' ?>">
            <i class="fas fa-plus-circle me-2"></i> Post Job
        </a>
        <a href="manage_jobs.php" class="list-group-item list-group-item-action <?= basename($_SERVER['PHP_SELF']) == 'manage_jobs.php' ? 'active' : '' ?>">
            <i class="fas fa-briefcase me-2"></i> Manage Jobs
        </a>
        <a href="manage_applications.php" class="list-group-item list-group-item-action <?= basename($_SERVER['PHP_SELF']) == 'manage_applications.php' ? 'active' : '' ?>">
            <i class="fas fa-users me-2"></i> Applications
        </a>
        <a href="profile.php" class="list-group-item list-group-item-action <?= basename($_SERVER['PHP_SELF']) == 'profile.php' ? 'active' : '' ?>">
            <i class="fas fa-user me-2"></i> Profile
        </a>
        <a href="reports.php" class="list-group-item list-group-item-action <?= basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : '' ?>">
            <i class="fas fa-chart-bar me-2"></i> Reports
        </a>
    </div>
</div>