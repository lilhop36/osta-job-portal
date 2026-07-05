# Deployment Guide

## Production Checklist

- Set `APP_ENV=production`.
- Set a production `SITE_URL` using HTTPS.
- Use a dedicated database user, not `root`.
- Disable detailed error display for visitors.
- Keep `.env` out of Git.
- Ensure upload directories cannot execute scripts.
- Move private uploaded documents outside public web root where possible.
- Configure PHP error logs and web server logs.
- Run migrations and verify indexes.
- Run smoke checks after deployment.

## Required Environment Variables

```text
DB_HOST=
DB_NAME=
DB_USER=
DB_PASS=
SITE_URL=
SMTP_HOST=
SMTP_PORT=
SMTP_USERNAME=
SMTP_PASSWORD=
FROM_EMAIL=
FROM_NAME=
APP_ENV=production
```

## Deployment Steps

1. Back up the existing database and files.
2. Upload project files excluding local-only artifacts.
3. Create `.env` on the server.
4. Install Composer dependencies if needed:

```bash
composer install --no-dev --optimize-autoloader
```

5. Import schema and apply migrations.
6. Set file permissions for uploads/logs.
7. Visit `/health.php` and confirm status is `ok`.
8. Run smoke checks from an environment that can reach the site.
9. Test login and each role dashboard.

## Rollback

- Restore the latest database backup.
- Restore the previous project directory.
- Clear PHP/web server cache if enabled.
- Re-run health and smoke checks.
