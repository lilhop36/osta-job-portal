# Testing Guide

## Automated Checks

Run from the project root:

```bash
composer validate --no-check-publish
composer run lint
composer run test
composer run smoke
```

Direct PHP equivalents:

```bash
php scripts/lint.php
php tests/run_helper_tests.php
php tests/smoke_check.php
```

The smoke test defaults to:

```text
http://localhost/osta_job_portal
```

Override it with:

```bash
set SMOKE_BASE_URL=http://localhost/osta_job_portal
php tests/smoke_check.php
```

## Current Automated Coverage

- PHP syntax lint for non-vendor files.
- Safe redirect helper behavior.
- Download filename sanitization.
- Upload extension helper behavior.
- Password hashing and verification.
- Public page smoke checks.
- Protected dashboard redirect checks.

## Manual QA Matrix

| Workflow | Steps | Expected Result | Status |
| --- | --- | --- | --- |
| Applicant registration | Register as applicant, log in, open dashboard | Applicant reaches dashboard | To verify |
| Applicant profile | Complete centralized application profile | Profile saves without errors | To verify |
| Document upload | Upload valid PDF/image document | Upload succeeds and appears in list | To verify |
| Invalid upload | Upload `.php` or unsupported file | Upload is rejected or cannot execute | To verify |
| Job search | Search/filter jobs | Approved matching jobs appear | To verify |
| Apply for job | Applicant applies to approved job | Application record is created | To verify |
| Employer job post | Employer creates vacancy/job | Job appears with expected status | To verify |
| Employer review | Employer views applicants in department | Only permitted applicants are visible | To verify |
| Status update | Employer updates application status | Status changes and history is logged | To verify |
| Admin management | Admin manages users/departments/jobs | Changes persist correctly | To verify |
| Unauthorized access | Logged-out user opens dashboards | Redirected to login | Verified by smoke test |

## Future Test Improvements

- Add PHPUnit once Composer dependencies are installed reliably.
- Add database transaction-based integration tests.
- Add browser workflow checks for applicant, employer, and admin paths.
- Add CI to run lint and helper tests on every push.
