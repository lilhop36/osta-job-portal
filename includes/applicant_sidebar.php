<div class="card dashboard-card">
    <div class="card-header bg-gradient-primary text-white">
        <h3 class="mb-0"><i class="fas fa-user me-2"></i>Applicant Menu</h3>
    </div>
    <div class="list-group list-group-flush dashboard-sidebar">
        <a href="dashboard.php" class="list-group-item list-group-item-action <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
            <i class="fas fa-tachometer-alt me-2"></i> Dashboard
        </a>
        <a href="../jobs.php" class="list-group-item list-group-item-action <?= basename($_SERVER['PHP_SELF']) == 'jobs.php' ? 'active' : '' ?>">
            <i class="fas fa-search me-2"></i> Find Jobs
        </a>
        <a href="applications.php" class="list-group-item list-group-item-action <?= basename($_SERVER['PHP_SELF']) == 'applications.php' ? 'active' : '' ?>">
            <i class="fas fa-file-alt me-2"></i> My Applications
        </a>
        <a href="document_upload.php" class="list-group-item list-group-item-action <?= basename($_SERVER['PHP_SELF']) == 'document_upload.php' ? 'active' : '' ?>">
            <i class="fas fa-upload me-2"></i> Documents
        </a>
        <a href="application_status.php" class="list-group-item list-group-item-action <?= basename($_SERVER['PHP_SELF']) == 'application_status.php' ? 'active' : '' ?>">
            <i class="fas fa-tasks me-2"></i> Application Status
        </a>
        <a href="interviews.php" class="list-group-item list-group-item-action <?= basename($_SERVER['PHP_SELF']) == 'interviews.php' ? 'active' : '' ?>">
            <i class="fas fa-comments me-2"></i> Interviews
        </a>
        <a href="saved_jobs.php" class="list-group-item list-group-item-action <?= basename($_SERVER['PHP_SELF']) == 'saved_jobs.php' ? 'active' : '' ?>">
            <i class="fas fa-bookmark me-2"></i> Saved Jobs
        </a>
        <a href="alerts.php" class="list-group-item list-group-item-action <?= basename($_SERVER['PHP_SELF']) == 'alerts.php' ? 'active' : '' ?>">
            <i class="fas fa-bell me-2"></i> Alerts
        </a>
    </div>
</div>