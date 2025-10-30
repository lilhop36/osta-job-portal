<div class="card">
    <div class="card-header bg-primary text-white">
        <h3 class="mb-0">Applicant Menu</h3>
    </div>
    <div class="list-group list-group-flush">
        <a href="dashboard.php" class="list-group-item list-group-item-action <?= basename($_SERVER['PHP_SELF']) == 'dashboard.php' ? 'active' : '' ?>">
            <i class="fas fa-home me-2"></i> Dashboard
        </a>
        <a href="centralized_application.php" class="list-group-item list-group-item-action <?= basename($_SERVER['PHP_SELF']) == 'centralized_application.php' ? 'active' : '' ?>">
            <i class="fas fa-file-alt me-2"></i> My Application
        </a>
        <a href="document_upload.php" class="list-group-item list-group-item-action <?= basename($_SERVER['PHP_SELF']) == 'document_upload.php' ? 'active' : '' ?>">
            <i class="fas fa-file-upload me-2"></i> Document Upload
        </a>
        <a href="application_status.php" class="list-group-item list-group-item-action <?= basename($_SERVER['PHP_SELF']) == 'application_status.php' ? 'active' : '' ?>">
            <i class="fas fa-tasks me-2"></i> Application Status
        </a>
                <a href="interviews.php" class="list-group-item list-group-item-action <?= basename($_SERVER['PHP_SELF']) == 'interviews.php' ? 'active' : '' ?>">
            <i class="fas fa-comments me-2"></i> My Interviews
        </a>
        <a href="alerts.php" class="list-group-item list-group-item-action <?= basename($_SERVER['PHP_SELF']) == 'alerts.php' ? 'active' : '' ?>">
            <i class="fas fa-bell me-2"></i> Alerts & Notifications
        </a>
    </div>
</div>
