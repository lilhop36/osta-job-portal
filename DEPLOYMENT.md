# Deployment Guide for OSTA Job Portal

## Heroku Deployment

### Prerequisites
- Heroku CLI installed
- Heroku account
- Git repository

### Steps

1. **Install Heroku CLI** (if not already installed)
   ```bash
   npm install -g heroku
   ```

2. **Login to Heroku**
   ```bash
   heroku login
   ```

3. **Create a new Heroku app**
   ```bash
   heroku create your-app-name
   ```

4. **Add PostgreSQL addon** (required for database)
   ```bash
   heroku addons:create heroku-postgresql:mini
   ```

5. **Set environment variables**
   ```bash
   heroku config:set APP_ENV=production
   heroku config:set SITE_URL=https://your-app-name.herokuapp.com
   heroku config:set SMTP_HOST=smtp.gmail.com
   heroku config:set SMTP_PORT=587
   heroku config:set SMTP_USERNAME=your-email@gmail.com
   heroku config:set SMTP_PASSWORD=your-app-password
   heroku config:set SMTP_ENCRYPTION=tls
   heroku config:set FROM_EMAIL=noreply@example.com
   heroku config:set FROM_NAME="OSTA Job Portal"
   ```

6. **Deploy to Heroku**
   ```bash
   git add .
   git commit -m "Prepare for Heroku deployment"
   git push heroku main
   ```

7. **Run database migrations** (if applicable)
   ```bash
   heroku run php scripts/migrate.php
   ```

8. **Open your application**
   ```bash
   heroku open
   ```

### Database Configuration on Heroku

Heroku automatically provides database credentials via the `DATABASE_URL` environment variable. You may need to update your database connection code to parse this URL.

## Railway Deployment

Railway is a deployment platform with a generous free tier that supports PHP + MySQL.

### Prerequisites

- A [Railway](https://railway.app) account (sign up with GitHub)
- Your project pushed to a GitHub repository

### Steps

#### 1. Push to GitHub

```bash
git add .
git commit -m "Ready for Railway deployment"
git push origin main
```

#### 2. Create a Railway Project

1. Go to [Railway Dashboard](https://railway.app/dashboard)
2. Click **New Project** → **Deploy from GitHub repo**
3. Select your repository
4. Railway will auto-detect PHP via `composer.json` and deploy

#### 3. Add a MySQL Database

1. In the Railway project canvas, click **Create** → **Database** → **Add MySQL**
2. Wait for the MySQL service to deploy (takes ~30 seconds)
3. Railway automatically sets these env vars on the MySQL service:
   - `MYSQL_HOST`, `MYSQL_PORT`, `MYSQL_DATABASE`, `MYSQL_USER`, `MYSQL_PASSWORD`

#### 4. Reference the Database from Your App Service

1. Click your app service (not the database service)
2. Go to **Variables** tab
3. Add the following variables (use the **Reference** button to pull from MySQL):
   - `DB_HOST` → reference `${{MySQL.MYSQL_HOST}}`
   - `DB_PORT` → reference `${{MySQL.MYSQL_PORT}}`
   - `DB_NAME` → reference `${{MySQL.MYSQL_DATABASE}}`
   - `DB_USER` → reference `${{MySQL.MYSQL_USER}}`
   - `DB_PASS` → reference `${{MySQL.MYSQL_PASSWORD}}`
4. Also add these static variables:
   - `APP_ENV` → `production`
   - `SITE_URL` → `https://your-app-name.railway.app` (use the domain you generate in step 5)
   - `COMPOSER_ALLOW_SUPERUSER` → `1`

#### 5. Generate a Public Domain

1. Go to your app service's **Settings** tab
2. Click **Generate Domain** in the Networking section
3. Copy the domain and update `SITE_URL` in Variables to match

#### 6. Set Up SMTP (for email)

Add these variables for email to work:

- `SMTP_HOST`
- `SMTP_PORT`
- `SMTP_USERNAME`
- `SMTP_PASSWORD`
- `SMTP_ENCRYPTION` → `tls`
- `FROM_EMAIL`
- `FROM_NAME` → `OSTA Job Portal`

#### 7. Import Your Database

1. From your local machine, export your database:
   ```bash
   mysqldump -u root -p osta_job_portal > backup.sql
   ```
2. In Railway, open the MySQL service → **Connect** tab
3. Use the Railway CLI or a MySQL client to import:
   ```bash
   # Install Railway CLI: npm i -g @railway/cli
   railway login
   railway run "mysql -h $MYSQL_HOST -u $MYSQL_USER -p$MYSQL_PASSWORD $MYSQL_DATABASE < backup.sql"
   ```

Alternatively, run the schema SQL manually through a MySQL client connected to Railway's MySQL.

#### 8. Redeploy

After setting all variables, click **Deploy** on the app service to redeploy with the new configuration.

### Railway-Specific Notes

- **Static assets** (CSS, JS, images) are served via Nginx with 7-day caching for performance
- **Uploaded files** in `uploads/` are ephemeral — they will be lost on restart. For production use, add a Railway Volume or external storage (e.g., S3)
- **PHP sessions** are file-based and work on single-instance free tier
- **Nginx config** is pre-configured via `nginx.template.conf` for proper URL rewriting

### Troubleshooting Railway

- **Build fails**: Check the build logs. Ensure `composer.json` is valid and all dependencies resolve
- **500 errors**: Look at the deployment logs. Common issues include missing env vars or DB connection failures
- **Static files 404**: Ensure `RAILPACK_PHP_ROOT_DIR` is NOT set (it defaults incorrectly for this project)
- **Session/login issues**: On the free tier, sessions reset on restart. This is normal

## DigitalOcean Deployment

### Option 1: DigitalOcean App Platform

1. **Create a new App** in DigitalOcean dashboard
2. **Select "PHP"** as the runtime
3. **Connect your GitHub repository**
4. **Configure build settings:**
   - Build command: `composer install --no-dev`
   - Run command: `heroku-php-apache2`
5. **Add a PostgreSQL database**
6. **Set environment variables** in the App Settings
7. **Deploy**

### Option 2: DigitalOcean Droplet (VPS)

1. **Create a Droplet** with Ubuntu and LAMP stack
2. **SSH into the droplet**
3. **Install dependencies:**
   ```bash
   sudo apt update
   sudo apt install apache2 php8.0 mysql-server composer git
   ```
4. **Clone your repository**
   ```bash
   git clone https://github.com/lilhop36/osta-job-portal.git /var/www/html/osta-job-portal
   cd /var/www/html/osta-job-portal
   ```
5. **Install PHP dependencies**
   ```bash
   composer install --no-dev
   ```
6. **Configure Apache virtual host**
7. **Set up MySQL database**
8. **Configure .env file**
9. **Set proper permissions**
   ```bash
   sudo chown -R www-data:www-data /var/www/html/osta-job-portal
   sudo chmod -R 755 /var/www/html/osta-job-portal
   ```

## Traditional Shared Hosting

### Steps

1. **Export your database** from local development
   ```bash
   mysqldump -u root -p osta_job_portal > backup.sql
   ```

2. **Upload files** via FTP or file manager
   - Upload all files except `.git`, `node_modules`, `tests`
   - Upload `vendor` directory

3. **Import database** via phpMyAdmin or hosting control panel

4. **Configure .env file**
   - Copy `.env.example` to `.env`
   - Update database credentials
   - Update SMTP settings
   - Update `SITE_URL` to your domain

5. **Set file permissions**
   - Ensure `uploads/` directory is writable (755 or 777)
   - Ensure `logs/` directory is writable (755 or 777)

6. **Configure domain** in hosting control panel

### Common Shared Hosting Requirements

- PHP version: 8.0 or higher
- MySQL/MariaDB database
- SMTP access for emails
- Mod_rewrite enabled (for .htaccess)

## Environment Variables

Required environment variables for production:

```bash
APP_ENV=production
SITE_URL=https://your-domain.com
DB_HOST=your-db-host
DB_NAME=your-db-name
DB_USER=your-db-user
DB_PASS=your-db-password
SMTP_HOST=smtp.gmail.com
SMTP_PORT=587
SMTP_USERNAME=your-smtp-username
SMTP_PASSWORD=your-smtp-password
SMTP_ENCRYPTION=tls
FROM_EMAIL=noreply@your-domain.com
FROM_NAME="OSTA Job Portal"
```

## Security Considerations

1. **Never commit .env file** to version control
2. **Use HTTPS** in production
3. **Set strong database passwords**
4. **Configure SMTP with app-specific passwords**
5. **Keep dependencies updated**
6. **Enable error logging** but disable error display in production
7. **Set up regular backups**

## Troubleshooting

### Heroku Deployment Issues

- **Build fails**: Check `composer.json` and ensure all dependencies are compatible
- **Database connection errors**: Verify PostgreSQL addon is attached and credentials are correct
- **Permission errors**: Ensure writable directories have correct permissions

### Shared Hosting Issues

- **500 Internal Server Error**: Check `.htaccess` file and PHP error logs
- **Database connection failed**: Verify database credentials in `.env`
- **Email not sending**: Check SMTP settings and hosting provider's email policies
