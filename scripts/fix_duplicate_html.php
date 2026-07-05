<?php
/**
 * Fix duplicate HTML structure in all pages that include header.php
 * header.php already outputs <!DOCTYPE html>, <head>, <body>, navbar
 * So pages must NOT have their own <!DOCTYPE html> or </body></html>
 */

$files = [
    // Root
    'about.php',
    'contact.php',
    'job_details.php',
    'change_password.php',
    // Admin
    'admin/analytics.php',
    'admin/manage_departments.php',
    'admin/manage_jobs.php',
    'admin/manage_users.php',
    'admin/notifications.php',
    'admin/reports.php',
    'admin/settings.php',
    // Applicant
    'applicant/alerts.php',
    'applicant/change_password.php',
    'applicant/delete_account.php',
    'applicant/job_alerts.php',
    'applicant/profile.php',
    'applicant/register.php',
    'applicant/saved_jobs.php',
    // Employer
    'employer/change_password.php',
    'employer/dashboard.php',
    'employer/edit_job.php',
    'employer/manage_jobs.php',
    'employer/post_job.php',
    'employer/profile.php',
    'employer/reports.php',
    'employer/view_applicants.php',
    'employer/view_application.php',
];

$base = dirname(__DIR__);
$fixed = 0;
$skipped = 0;

foreach ($files as $file) {
    $path = $base . '/' . $file;
    if (!file_exists($path)) {
        echo "SKIP (not found): $file\n";
        $skipped++;
        continue;
    }

    $content = file_get_contents($path);
    $original = $content;

    // Remove DOCTYPE through <body ...> (everything from <!DOCTYPE to <body...>)
    // This handles various patterns like <body class="bg-light">, <body>, etc.
    $content = preg_replace(
        '/<!DOCTYPE[^>]*>\s*<html[^>]*>\s*<head>[\s\S]*?<\/head>\s*<body[^>]*>\s*/',
        '',
        $content
    );

    // Also handle cases where there's no <head> block but DOCTYPE + body exist
    $content = preg_replace(
        '/<!DOCTYPE[^>]*>\s*<html[^>]*>\s*<body[^>]*>\s*/',
        '',
        $content
    );

    // Remove standalone <head> blocks that might be left over
    $content = preg_replace(
        '/<head>\s*<meta[^>]*>\s*<meta[^>]*>\s*<title>[^<]*<\/title>\s*<link[^>]*>\s*(<link[^>]*>\s*)*(<link[^>]*>\s*)*(<link[^>]*>\s*)*<\/head>\s*/',
        '',
        $content
    );

    // Remove duplicate closing </body></html>
    // Match last occurrence of </body>\s*</html> at the end of file
    $content = preg_replace('/\s*<\/body>\s*<\/html>\s*$/', '', $content);

    // Remove duplicate bootstrap.bundle.min.js script tags (footer.php loads it)
    $content = preg_replace(
        '/\s*<script src="https:\/\/cdn\.jsdelivr\.net\/npm\/bootstrap@[\d.]+\/dist\/js\/bootstrap\.bundle\.min\.js"><\/script>\s*/',
        "\n",
        $content
    );

    // Remove duplicate bootstrap CSS link tags (header.php loads them)
    $content = preg_replace(
        '/\s*<link href="https:\/\/cdn\.jsdelivr\.net\/npm\/bootstrap@[\d.]+\/dist\/css\/bootstrap\.min\.css" rel="stylesheet">\s*/',
        "\n",
        $content
    );

    // Remove duplicate Font Awesome link (header.php loads it)
    $content = preg_replace(
        '/\s*<link rel="stylesheet" href="https:\/\/cdnjs\.cloudflare\.com\/ajax\/libs\/font-awesome\/[\d.]+\/css\/all\.min\.css">\s*/',
        "\n",
        $content
    );

    // Remove duplicate custom CSS link (header.php loads it)
    $content = preg_replace(
        '/\s*<link rel="stylesheet" href="[^"]*assets\/css\/styles\.css">\s*/',
        "\n",
        $content
    );

    // Remove duplicate <meta charset/viewport> lines
    $content = preg_replace(
        '/\s*<meta charset="UTF-8">\s*/',
        "\n",
        $content
    );
    $content = preg_replace(
        '/\s*<meta name="viewport"[^>]*>\s*/',
        "\n",
        $content
    );

    // Clean up multiple blank lines
    $content = preg_replace('/\n{3,}/', "\n\n", $content);

    if ($content !== $original) {
        file_put_contents($path, $content);
        echo "FIXED: $file\n";
        $fixed++;
    } else {
        echo "NO CHANGE: $file\n";
        $skipped++;
    }
}

echo "\nDone! Fixed: $fixed, Skipped: $skipped\n";
