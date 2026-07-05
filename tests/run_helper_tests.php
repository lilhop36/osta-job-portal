<?php
require_once __DIR__ . '/../includes/helpers.php';
require_once __DIR__ . '/../includes/security.php';
require_once __DIR__ . '/../services/DocumentService.php';

$failures = [];

function check_true(bool $condition, string $message): void {
    global $failures;
    if (!$condition) {
        $failures[] = $message;
    }
}

check_true(is_safe_internal_redirect('/osta%20job%20portal/applicant/dashboard.php'), 'absolute internal app path should be allowed');
check_true(is_safe_internal_redirect('applicant/dashboard.php'), 'relative internal path should be allowed');
check_true(!is_safe_internal_redirect('https://example.com/phish'), 'external redirect should be blocked');
check_true(!is_safe_internal_redirect('//example.com/phish'), 'protocol-relative redirect should be blocked');
check_true(safe_redirect_target('https://example.com', 'index.php') === 'index.php', 'unsafe redirect should fall back');
check_true(clean_download_filename('../evil resume.php') === 'evil_resume.php', 'download filename should be basename-cleaned');
check_true(DocumentService::isAllowedExtension('resume.PDF', ['pdf', 'docx']), 'allowed upload extension should pass');
check_true(!DocumentService::isAllowedExtension('shell.php', ['pdf', 'docx']), 'disallowed upload extension should fail');

$password = 'PortfolioReady#2026';
$hash = hash_password($password);
check_true(verify_password($password, $hash), 'hashed password should verify');
check_true(!verify_password('wrong-password', $hash), 'wrong password should not verify');

if ($failures) {
    foreach ($failures as $failure) {
        fwrite(STDERR, '[FAIL] ' . $failure . PHP_EOL);
    }
    exit(1);
}

echo 'Helper/security tests passed.' . PHP_EOL;
