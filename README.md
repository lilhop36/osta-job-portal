# OSTA Job Portal

OSTA Job Portal is a PHP/MySQL recruitment management system for the Oromia Science and Technology Authority. It supports applicants, employers, and administrators through job posting, profile management, applications, document uploads, interview workflows, notifications, and reporting.

This repository is being upgraded as a portfolio-grade software engineering project: secure configuration, repeatable setup, documented architecture, smoke tests, database migrations, and production-readiness notes are all part of the work.

## Features

### Applicant
- Register and log in securely.
- Search jobs and view job details.
- Create a centralized application profile.
- Upload required documents.
- Save jobs and apply for approved vacancies.
- Track applications and interviews.

### Employer
- Post and manage jobs.
- Review applications by department.
- Update application statuses.
- Export applicant data and view reports.

### Admin
- Manage users, departments, jobs, interviews, and settings.
- Review platform reports and analytics.
- Monitor system activity and notifications.

## Tech Stack

- PHP 8.x compatible page-based application
- MySQL/MariaDB
- Apache/XAMPP local development
- Bootstrap 5, CSS, JavaScript
- PDO prepared statements
- Composer for dependency management

## Engineering Highlights

- Environment-based database configuration through `.env`.
- Secure password hashing with Argon2id.
- CSRF helper functions for state-changing forms.
- Role-based access control for admin, employer, and applicant pages.
- Shared redirect helpers to block open redirects.
- Upload directory hardening through `.htaccess`.
- Health check endpoint for database, sessions, and upload protection.
- Dependency-free lint, helper, and smoke test scripts.
- Versioned database migration and sanitized demo seed files.

## Quick Start

1. Copy `.env.example` to `.env`.
2. Set database values in `.env`.
3. Start Apache and MySQL in XAMPP.
4. Import the database schema and migrations.
5. Optionally import `database/seeds/demo_seed.sql` for safe demo accounts.
6. Open `http://localhost/osta_job_portal`.

See [INSTALLATION.md](INSTALLATION.md) for full setup steps.

## Demo Credentials

Development-only seed accounts are provided in `database/seeds/demo_seed.sql`.

| Role | Email | Password |
| --- | --- | --- |
| Admin | admin@example.com | DemoPass123! |
| Employer | employer@example.com | DemoPass123! |
| Applicant | applicant@example.com | DemoPass123! |

Never use these credentials in production.

## Quality Checks

```bash
composer validate --no-check-publish
composer run lint
composer run test
composer run smoke
```

If Composer dependencies are not installed, the scripts can also be run directly:

```bash
php scripts/lint.php
php tests/run_helper_tests.php
php tests/smoke_check.php
```

## Documentation

- [INSTALLATION.md](INSTALLATION.md): local setup and troubleshooting.
- [SECURITY.md](SECURITY.md): implemented security controls and remaining hardening work.
- [TESTING.md](TESTING.md): automated checks and manual QA matrix.
- [DATABASE.md](DATABASE.md): schema, migrations, seeds, backup, and restore.
- [DEPLOYMENT.md](DEPLOYMENT.md): production deployment checklist.
- [PROJECT_UPGRADE_PLAN.md](PROJECT_UPGRADE_PLAN.md): roadmap to 95%+ production readiness.

## Known Limitations

- The app is still a page-based PHP system, not a full MVC framework.
- Some business logic remains inside page files and should continue moving into services.
- Uploaded private documents should eventually be stored outside public web root.
- PHPUnit can be added once Composer dependencies are installed reliably.
- The Word internship report should be updated with screenshots, ERD, test evidence, and this engineering roadmap.

## Future Improvements

- Full service/controller extraction.
- CI pipeline for lint and smoke tests.
- More complete automated workflow tests.
- Production mail provider integration.
- Centralized audit dashboard.
- Applicant recommendation and scoring improvements.
