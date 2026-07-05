<?php
/**
 * Batch update admin inline sidebars to use green gradient theme
 */
$base = dirname(__DIR__) . '/admin';
$files = [
    'dashboard.php',
    'manage_users.php',
    'manage_departments.php',
    'manage_jobs.php',
    'reports.php',
    'settings.php',
    'notifications.php',
    'analytics.php',
];

$fixed = 0;
foreach ($files as $file) {
    $path = $base . '/' . $file;
    if (!file_exists($path)) {
        echo "SKIP (not found): $file\n";
        continue;
    }

    $content = file_get_contents($path);
    $original = $content;

    // Pattern 1: Replace card with bg-primary text-white header + h3 Admin Menu
    // e.g.: <div class="card-header bg-primary text-white">
    //            <h3 class="mb-0">Admin Menu</h3>
    $content = preg_replace(
        '/<div class="card">\s*<div class="card-header bg-primary text-white">\s*<h3 class="mb-0">Admin Menu<\/h3>/',
        '<div class="card dashboard-card">
                    <div class="card-header bg-gradient-primary text-white">
                        <h3 class="mb-0"><i class="fas fa-cog me-2"></i>Admin Menu</h3>',
        $content
    );

    // Pattern 2: Replace list-group list-group-flush (sidebar items) with dashboard-sidebar
    $content = preg_replace(
        '/<div class="list-group list-group-flush">\s*<a href="dashboard\.php"/',
        '<div class="list-group list-group-flush dashboard-sidebar">
                        <a href="dashboard.php"',
        $content
    );

    // Pattern 3: Replace bi- icons with fontawesome icons in admin sidebar
    $icon_map = [
        'bi bi-speedometer2' => 'fas fa-tachometer-alt',
        'bi bi-people' => 'fas fa-users',
        'bi bi-building' => 'fas fa-building',
        'bi bi-briefcase' => 'fas fa-briefcase',
        'bi bi-file-earmark-text' => 'fas fa-file-alt',
        'bi bi-gear' => 'fas fa-cog',
        'bi bi-bell' => 'fas fa-bell',
        'bi bi-graph-up' => 'fas fa-chart-line',
        'bi bi-list' => 'fas fa-list',
        'bi bi-calendar-event' => 'fas fa-calendar',
        'bi bi-clock' => 'fas fa-clock',
        'bi bi-person-lines-fill' => 'fas fa-comments',
        'bi bi-speedometer' => 'fas fa-tachometer-alt',
    ];
    foreach ($icon_map as $old => $new) {
        $content = str_replace($old, $new, $content);
    }

    // Clean up duplicate blank lines
    $content = preg_replace('/\n{3,}/', "\n\n", $content);

    if ($content !== $original) {
        file_put_contents($path, $content);
        echo "FIXED: $file\n";
        $fixed++;
    } else {
        echo "NO CHANGE: $file\n";
    }
}

echo "\nDone! Fixed: $fixed\n";
