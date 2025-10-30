<div class="card">
    <div class="card-header bg-primary text-white">
        <h3 class="mb-0">Admin Menu</h3>
    </div>
    <div class="list-group list-group-flush">
        <a href="dashboard.php" class="list-group-item list-group-item-action <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
            <i class="bi bi-speedometer2 me-2"></i> Dashboard
        </a>
        <a href="manage_users.php" class="list-group-item list-group-item-action <?= basename($_SERVER['PHP_SELF']) == 'manage_users.php' ? 'active' : '' ?>">
            <i class="bi bi-people me-2"></i> Manage Users
        </a>
        <a href="manage_departments.php" class="list-group-item list-group-item-action <?= basename($_SERVER['PHP_SELF']) == 'manage_departments.php' ? 'active' : '' ?>">
            <i class="bi bi-building me-2"></i> Manage Departments
        </a>
        <a href="manage_jobs.php" class="list-group-item list-group-item-action <?= basename($_SERVER['PHP_SELF']) == 'manage_jobs.php' ? 'active' : '' ?>">
            <i class="bi bi-briefcase me-2"></i> Manage Jobs
        </a>
        <a href="manage_interviews.php" class="list-group-item list-group-item-action <?= basename($_SERVER['PHP_SELF']) == 'manage_interviews.php' ? 'active' : '' ?>">
            <i class="fas fa-comments me-2"></i> Manage Interviews
        </a>
        <a href="reports.php" class="list-group-item list-group-item-action <?= basename($_SERVER['PHP_SELF']) == 'reports.php' ? 'active' : '' ?>">
            <i class="bi bi-file-earmark-text me-2"></i> Reports
        </a>
        <a href="settings.php" class="list-group-item list-group-item-action <?= basename($_SERVER['PHP_SELF']) == 'settings.php' ? 'active' : '' ?>">
            <i class="bi bi-gear me-2"></i> Settings
        </a>
        <a href="notifications.php" class="list-group-item list-group-item-action <?= basename($_SERVER['PHP_SELF']) == 'notifications.php' ? 'active' : '' ?>">
            <i class="bi bi-bell me-2"></i> Notifications
        </a>
        <a href="analytics.php" class="list-group-item list-group-item-action <?= basename($_SERVER['PHP_SELF']) == 'analytics.php' ? 'active' : '' ?>">
            <i class="bi bi-graph-up me-2"></i> Analytics
        </a>
        <a href="interview_analytics.php" class="list-group-item list-group-item-action <?= basename($_SERVER['PHP_SELF']) == 'interview_analytics.php' ? 'active' : '' ?>">
            <i class="fas fa-chart-bar me-2"></i> Interview Analytics
        </a>
    </div>
</div>
