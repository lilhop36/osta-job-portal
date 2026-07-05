# Project Upgrade Plan

## Goal

Raise OSTA Job Portal from a functional internship project to a portfolio-grade, production-ready software engineering project.

## Score Target

Current assessed state after XAMPP verification: about 68/100.

Target: 95%+ production-readiness and GitHub portfolio credibility.

## Sprint 1: Runtime, Config, And Security Foundation

Completed in this upgrade pass:

- Added `includes/helpers.php` with role constants, safe redirect helpers, app path helpers, flash helper, and filename cleanup.
- Added `includes/bootstrap.php` for centralized startup.
- Converted root `database.php` into a compatibility wrapper for `config/database.php`.
- Added safe redirect handling to login, save-job, and employer status update flows.
- Added `health.php` operational diagnostics.
- Added upload directory `.htaccess` protection.
- Added standard error pages.

## Sprint 2: Access Control And File Safety

Partially completed:

- Added shared role constants.
- Hardened applicant document download filenames.
- Added upload execution protection.
- Added status-change security logging.

Remaining:

- Complete route-by-route role audit.
- Move private uploads outside public web root.
- Replace remaining direct `session_start()` calls over time.

## Sprint 3: Maintainability Refactor

Started:

- Added service-style files for auth, jobs, applications, documents, and notifications.
- Existing URLs remain stable.

Remaining:

- Move repeated SQL/business logic into services gradually.
- Standardize layouts and flash messages.

## Sprint 4: Database Engineering

Started:

- Added `003_add_production_indexes.sql`.
- Added sanitized development seed data.
- Added database backup/restore documentation.

Remaining:

- Add a migration runner.
- Convert older PHP migration scripts to consistent SQL or CLI-safe migrations.

## Sprint 5: Testing And Quality Proof

Started:

- Added non-vendor PHP lint runner.
- Added dependency-free helper/security tests.
- Added smoke checks for public pages and protected redirects.
- Added manual QA matrix.

Remaining:

- Add PHPUnit after Composer dependencies are installed.
- Add integration tests for applicant, employer, and admin workflows.

## Sprint 6: Documentation And Portfolio Polish

Started:

- Rewrote README with portfolio-focused engineering highlights.
- Added installation, security, testing, database, deployment, and roadmap docs.
- Added Word report update guide.

Remaining:

- Add real screenshots.
- Add ERD and architecture diagram images.
- Update the external DOCX report.

## Next Highest-Impact Work

1. Replace remaining direct `session_start()` calls with bootstrap/session helper use.
2. Complete route-by-route authorization audit.
3. Move document storage outside public web root.
4. Add screenshots and architecture diagrams.
5. Add PHPUnit and CI once dependencies are available.
