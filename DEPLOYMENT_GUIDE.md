# OSTA Job Portal - Deployment Guide

## 📋 Pre-Deployment Checklist

### Step 1: Export Database
1. Open your browser and go to: `http://localhost/osta_job_portal/export_database.php`
2. This will download a SQL file with your database structure and data
3. Save this file - you'll need it for the hosting setup

### Step 2: Prepare Files for Upload
Create a deployment folder with these files/folders:

**✅ INCLUDE:**
```
osta_job_portal/
├── admin/              (all .php files)
├── applicant/          (all .php files)  
├── employer/           (all .php files)
├── includes/           (all .php files)
├── assets/             (css, js folders)
├── config/             (empty folder)
├── uploads/            (empty subfolders)
│   ├── resumes/
│   ├── certificates/
│   └── job_attachments/
├── *.php               (all root PHP files)
└── .htaccess           (if exists)
```

**❌ EXCLUDE:**
```
logs/                   (will be recreated)
config/database.php     (create new one)
export_database.php     (temporary file)
database/               (optional)
docs/                   (optional)
documentation/          (optional)
```

## 🌐 Hosting Setup

### Recommended: InfinityFree (infinityfree.net)

1. **Sign up** for free account
2. **Create website** - choose subdomain or use your domain
3. **Access cPanel** from your account dashboard

### Database Setup
1. In cPanel, go to **MySQL Databases**
2. **Create new database** (note the full database name)
3. **Create database user** with password
4. **Add user to database** with all privileges
5. Go to **phpMyAdmin**
6. Select your database and **Import** the SQL file you exported

### File Upload
1. In cPanel, go to **File Manager**
2. Navigate to `htdocs` or `public_html` folder
3. **Upload** your deployment ZIP file
4. **Extract** the files
5. **Delete** the ZIP file after extraction

## ⚙️ Configuration

### Step 1: Database Configuration
1. Copy `config/database_production.php` to `config/database.php`
2. Edit `config/database.php` with your hosting details:

```php
define('DB_HOST', 'sql123.infinityfree.com');     // From hosting panel
define('DB_NAME', 'if0_12345678_osta_portal');    // Your database name
define('DB_USER', 'if0_12345678');                // Your database user
define('DB_PASS', 'your_password');               // Your database password
define('SITE_URL', 'https://yoursite.infinityfreeapp.com');
```

### Step 2: Set Folder Permissions
Set these folders to **755** or **777**:
- `uploads/`
- `uploads/resumes/`
- `uploads/certificates/`
- `uploads/job_attachments/`

### Step 3: Create Admin User
Visit: `https://yoursite.com/register.php`
1. Register first user (will be admin)
2. Or manually update database to set role = 'admin'

## 🔧 Post-Deployment

### Test These Features:
- [ ] User registration/login
- [ ] Job posting (admin)
- [ ] Job application (applicant)
- [ ] Application management (employer)
- [ ] File uploads work
- [ ] Email notifications (if configured)

### Common Issues:

**File Upload Errors:**
- Check folder permissions (755/777)
- Verify upload_max_filesize in hosting

**Database Connection Errors:**
- Double-check database credentials
- Ensure database user has proper privileges

**CSRF Token Errors:**
- Clear browser cache
- Check session configuration

## 📞 Support

If you encounter issues:
1. Check hosting provider's error logs
2. Enable error reporting temporarily in `database.php`
3. Contact hosting support for server-specific issues

## 🎉 Go Live!

Once everything is working:
1. Update SITE_URL in database.php to your final domain
2. Set error reporting to false
3. Remove any test data
4. Your OSTA Job Portal is live!

---
**Generated:** <?= date('Y-m-d H:i:s') ?>
