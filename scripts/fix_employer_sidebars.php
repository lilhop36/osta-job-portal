<?php
$base = dirname(__DIR__) . '/employer';
$files = [
    'dashboard.php',
    'post_job.php',
    'manage_jobs.php',
    'reports.php',
    'profile.php',
    'change_password.php',
    'view_applicants.php',
    'view_application.php',
    'manage_applications.php',
];

$sidebar_include = '<?php include __DIR__ . \'/../includes/employer_sidebar.php\'; ?>';

$fixed = 0;
foreach ($files as $file) {
    $path = $base . '/' . $file;
    if (!file_exists($path)) {
        echo "SKIP (not found): $file\n";
        continue;
    }

    $content = file_get_contents($path);
    $original = $content;

    // Find the sidebar block: <!-- Sidebar --> ... all the way to the next col-md-9 or similar div
    // Use a more specific pattern that matches the known structures
    $pattern = '/\s*<!-- Sidebar -->\s*<div class="col-md-3">.*?<\/div>\s*<\/div>\s*(?=<!--|<div class="col-md-[0-9]+">|$)/s';
    
    $count = 0;
    $content = preg_replace($pattern, "\n            <!-- Sidebar -->\n            <div class=\"col-md-3\">\n                $sidebar_include\n            </div>\n\n            <!-- Main Content -->\n            ", $content, -1, $count);
    
    if ($count > 0) {
        file_put_contents($path, $content);
        echo "FIXED: $file\n";
        $fixed++;
    } else {
        echo "NO MATCH: $file\n";
    }
}

echo "\nDone! Fixed: $fixed\n";
