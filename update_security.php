<?php
/**
 * Security Update Script for OSTA Job Portal
 * This script adds security enhancements to all key pages
 */

// List of files that need security updates
$files_to_update = [
    // Admin files
    'admin/dashboard.php',
    'admin/manage_users.php',
    'admin/manage_departments.php',
    'admin/manage_jobs.php',
    'admin/profile.php',
    'admin/change_password.php',
    
    // Employer files
    'employer/post_job.php',
    'employer/manage_jobs.php',
    'employer/manage_applications.php',
    'employer/edit_job.php',
    'employer/profile.php',
    'employer/change_password.php',
    
    // Applicant files
    'applicant/profile.php',
    'applicant/apply_job.php',
    'applicant/saved_jobs.php',
    'applicant/change_password.php',
    'applicant/alerts.php',
    'applicant/export.php'
];

echo "<h2>Security Enhancement Report</h2>\n";
echo "<p>Adding security features to " . count($files_to_update) . " files...</p>\n";

$updated_count = 0;
$errors = [];

foreach ($files_to_update as $file) {
    $full_path = __DIR__ . '/' . $file;
    
    if (!file_exists($full_path)) {
        $errors[] = "File not found: $file";
        continue;
    }
    
    $content = file_get_contents($full_path);
    $original_content = $content;
    
    // Check if security module is already included
    if (strpos($content, "require_once '../includes/security.php'") === false && 
        strpos($content, "require_once __DIR__ . '/../includes/security.php'") === false) {
        
        // Add security include after auth include
        if (strpos($content, "require_once '../includes/auth.php'") !== false) {
            $content = str_replace(
                "require_once '../includes/auth.php';",
                "require_once '../includes/auth.php';\nrequire_once '../includes/security.php';",
                $content
            );
        } elseif (strpos($content, "require_once __DIR__ . '/../includes/auth.php'") !== false) {
            $content = str_replace(
                "require_once __DIR__ . '/../includes/auth.php';",
                "require_once __DIR__ . '/../includes/auth.php';\nrequire_once __DIR__ . '/../includes/security.php';",
                $content
            );
        }
    }
    
    // Add security headers after role requirement
    if (strpos($content, "set_security_headers();") === false) {
        if (strpos($content, "require_role(") !== false) {
            // Find the line with require_role and add security headers after it
            $lines = explode("\n", $content);
            for ($i = 0; $i < count($lines); $i++) {
                if (strpos($lines[$i], "require_role(") !== false) {
                    // Insert security headers after this line
                    array_splice($lines, $i + 1, 0, [
                        "",
                        "// Set security headers",
                        "set_security_headers();"
                    ]);
                    break;
                }
            }
            $content = implode("\n", $lines);
        }
    }
    
    // Add back navigation prevention before closing body tag
    if (strpos($content, "prevent_back_navigation();") === false && strpos($content, "</body>") !== false) {
        $content = str_replace(
            "</body>",
            "    <?php prevent_back_navigation(); ?>\n</body>",
            $content
        );
    }
    
    // Add CSRF token to forms if not present
    if (strpos($content, "<form") !== false && strpos($content, "csrf_token") === false) {
        // Add CSRF token to forms
        $content = preg_replace(
            '/(<form[^>]*method=["\']POST["\'][^>]*>)/i',
            '$1' . "\n" . '                            <input type="hidden" name="csrf_token" value="<?php echo generate_csrf_token(); ?>">',
            $content
        );
    }
    
    // Only write if content changed
    if ($content !== $original_content) {
        if (file_put_contents($full_path, $content)) {
            echo "<p>✓ Updated: $file</p>\n";
            $updated_count++;
        } else {
            $errors[] = "Failed to write: $file";
        }
    } else {
        echo "<p>- No changes needed: $file</p>\n";
    }
}

echo "<h3>Summary</h3>\n";
echo "<p>Files updated: $updated_count</p>\n";

if (!empty($errors)) {
    echo "<h3>Errors</h3>\n";
    foreach ($errors as $error) {
        echo "<p style='color: red;'>✗ $error</p>\n";
    }
}

echo "<h3>Security Features Added:</h3>\n";
echo "<ul>\n";
echo "<li>Enhanced session management with security headers</li>\n";
echo "<li>Browser cache control to prevent back navigation</li>\n";
echo "<li>CSRF protection for forms</li>\n";
echo "<li>JavaScript-based navigation prevention</li>\n";
echo "<li>Right-click and developer tools prevention</li>\n";
echo "</ul>\n";

echo "<p><strong>Note:</strong> Please test all functionality after these security updates.</p>\n";
?>
