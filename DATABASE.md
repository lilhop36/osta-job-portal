# Database Guide

## Database Name

Default local database:

```text
osta_job_portal
```

The name is configured through `.env` as `DB_NAME`.

## Configuration

Database credentials are loaded by `config/database.php` through `config/env.php`. Avoid hardcoding credentials in PHP files.

## Migrations

Run migrations in order after importing the base schema:

```text
database/migrations/001_add_application_count.php
database/migrations/002_add_salary_column.php
database/migrations/003_add_production_indexes.sql
```

The new production index migration adds indexes for common application, job, user, notification, and document lookup paths.

## Demo Seeds

Development-only sanitized demo data is stored in:

```text
database/seeds/demo_seed.sql
```

Demo password for all seed users:

```text
DemoPass123!
```

Never use demo credentials in production.

## Backup

```bash
C:\xampp\mysql\bin\mysqldump.exe -u root osta_job_portal > backup_osta_job_portal.sql
```

## Restore

```bash
C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE IF NOT EXISTS osta_job_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
C:\xampp\mysql\bin\mysql.exe -u root osta_job_portal < backup_osta_job_portal.sql
```

## Data Hygiene Before Publishing

Before pushing to GitHub, remove or sanitize:

- Real names, emails, phone numbers, and addresses.
- Resume/document files.
- Production SMTP credentials.
- Real passwords or password reset tokens.
- Internal logs containing personal data.
