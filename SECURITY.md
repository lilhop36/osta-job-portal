# Security Notes

## Implemented Controls

- Passwords are hashed with Argon2id through `hash_password()`.
- Login verifies passwords with `password_verify()`.
- PDO prepared statements are used throughout core workflows.
- CSRF tokens protect many state-changing forms.
- Secure session settings use HTTP-only cookies, SameSite Strict, strict mode, and session ID regeneration.
- Shared redirect helpers reject external URLs and protocol-relative redirects.
- Role checks protect admin, employer, and applicant areas.
- Upload directories include `.htaccess` rules to block script execution and directory listing.
- Production mode disables visitor-facing detailed errors through `APP_ENV=production`.

## Recently Hardened Areas

- Root `database.php` now delegates to environment-aware `config/database.php`.
- Login redirects are passed through safe redirect validation.
- Referer-based save-job redirects now use a safe fallback helper.
- Employer application status redirect is validated before redirecting.
- Applicant document download filenames are sanitized before being sent in headers.
- `health.php` exposes operational checks without leaking database credentials.

## Remaining Work

- Move private uploads outside public web root.
- Complete a route-by-route access-control audit.
- Add centralized audit dashboards for security events.
- Replace remaining direct `session_start()` calls with shared bootstrap usage.
- Expand automated tests around all role workflows.
- Review every file upload path for MIME, extension, size, and ownership enforcement.

## Reporting Vulnerabilities

For portfolio/demo use, report issues directly to the project maintainer. Do not include real applicant documents, passwords, or private data in public issues.
