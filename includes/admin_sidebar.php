<div class="card dashboard-card">
    <div class="card-header bg-gradient-primary text-white">
        <h3 class="mb-0"><i class="fas fa-cog me-2"></i>Admin Menu</h3>
    </div>
    <div class="list-group list-group-flush dashboard-sidebar">
        <a href="dashboard.php" class="list-group-item list-group-item-action <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
            <i class="fas fa-tachometer-alt me-2"></i> Dashboard
        </a>
        <a href="manage_users.php" class="list-group-item list-group-item-action <?= basename($_SERVER['PHP_SELF']) == 'manage_users.php' ? 'active' : '' ?>">
            <i class="fas fa-users me-2"></i> Users
        </a>
        <a href="manage_jobs.php" class="list-group-item list-group-item-action <?= basename($_SERVER['PHP_SELF']) == 'manage_jobs.php' ? 'active' : '' ?>">
            <i class="fas fa-briefcase me-2"></i> Jobs
        </a>
        <a href="manage_departments.php" class="list-group-item list-group-item-action <?= basename($_SERVER['PHP_SELF']) == 'manage_departments.php' ? 'active' : '' ?>">
            <i class="fas fa-building me-2"></i> Departments
        </a>
        <a href="reports.php" class="list-group-item list-group-item-action <?= basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : '' ?>">
            <i class="fas fa-chart-bar me-2"></i> Reports
        </a>
        <a href="manage_interviews.php" class="list-group-item list-group-item-action <?= basename($_SERVER['PHP_SELF']) == 'manage_interviews.php' ? 'active' : '' ?>">
            <i class="fas fa-comments me-2"></i> Interviews
        </a>
        <a href="notifications.php" class="list-group-item list-group-item-action <?= basename($_SERVER['PHP_SELF']) == 'notifications.php' ? 'active' : '' ?>">
            <i class="fas fa-bell me-2"></i> Notifications
        </a>
        <a href="analytics.php" class="list-group-item list-group-item-action <?= basename($_SERVER['PHP_SELF']) == 'analytics.php' ? 'active' : '' ?>">
            <i class="fas fa-chart-line me-2"></i> Analytics
        </a>
        <a href="settings.php" class="list-group-item list-group-item-action <?= basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : '' ?>">
            <i class="fas fa-cog me-2"></i> Settings
        </a>
    </div>
</div>