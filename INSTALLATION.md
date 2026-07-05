# Installation Guide

## Prerequisites

- XAMPP with Apache and MySQL/MariaDB.
- PHP 8.x recommended.
- Composer recommended.
- Git recommended for version control.

## Local Setup

1. Place the project in the XAMPP web root:

```text
C:\xampp\htdocs\osta job portal
```

2. Copy the environment file:

```bash
copy .env.example .env
```

3. Confirm these values in `.env`:

```text
DB_HOST=localhost
DB_NAME=osta_job_portal
DB_USER=root
DB_PASS=
SITE_URL=http://localhost/osta%20job%20portal
APP_ENV=development
```

4. Start Apache and MySQL from the XAMPP Control Panel.

5. Create/import the database:

```bash
C:\xampp\mysql\bin\mysql.exe -u root -e "CREATE DATABASE IF NOT EXISTS osta_job_portal CHARACTER SET utf8mb4 COLLATE utf8mb4_general_ci;"
```

Import the base schema from the existing project SQL/database export, then apply migrations from `database/migrations` in order.

6. Optional demo data:

```bash
C:\xampp\mysql\bin\mysql.exe -u root -D osta_job_portal < database\seeds\demo_seed.sql
```

7. Open the app:

```text
http://localhost/osta_job_portal
```

## Health Check

Open:

```text
http://localhost/osta_job_portal/health.php
```

Expected status is `ok` when database, sessions, and upload protection are available.

## Troubleshooting

- If database connection fails, confirm MySQL is running and `.env` matches your local credentials.
- If sessions fail, confirm the PHP session save path is writable. In XAMPP this is usually `C:\xampp\tmp`.
- If pages show detailed PHP errors in production, set `APP_ENV=production`.
- If Composer says dependencies are missing, run `composer install` when network access is available.
