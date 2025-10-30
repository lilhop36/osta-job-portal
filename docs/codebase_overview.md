# OSTA Job Portal System Codebase Overview

## Project Structure
```
osta_job_portal/
├── admin/                 # Admin interface files
│   ├── dashboard.php     # Admin dashboard
│   ├── manage_departments.php  # Department management
│   ├── manage_jobs.php   # Job management
│   ├── manage_users.php  # User management
│   ├── reports.php       # Reports generation
│   ├── settings.php      # System settings
│   ├── notifications.php # Notification management
│   └── analytics.php     # Analytics dashboard
├── applicant/            # Applicant interface files
│   ├── dashboard.php     # Applicant dashboard
│   ├── profile.php       # Profile management
│   ├── apply_job.php     # Job application
│   ├── saved_jobs.php    # Saved jobs
│   └── track_applications.php  # Application tracking
├── employer/             # Employer interface files
│   ├── dashboard.php     # Employer dashboard
│   └── post_job.php      # Job posting
├── config/               # Configuration files
│   └── database.php      # Database and system configuration
├── includes/             # Common includes
│   ├── header.php        # Header template
│   └── footer.php        # Footer template
├── assets/               # Static assets
│   ├── css/             # CSS files
│   ├── js/              # JavaScript files
│   └── images/          # Images
├── uploads/              # User uploads
│   ├── resumes/         # Resume files
│   └── cover_letters/   # Cover letter files
└── index.php            # Homepage
```

## Database Structure

### Users Table
```sql
CREATE TABLE users (
    id INT PRIMARY KEY AUTO_INCREMENT,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('applicant', 'employer', 'admin') NOT NULL,
    status ENUM('active', 'pending', 'inactive') DEFAULT 'active',
    department_id INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id)
);
```

### Departments Table
```sql
CREATE TABLE departments (
    id INT PRIMARY KEY AUTO_INCREMENT,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    contact_email VARCHAR(100),
    contact_phone VARCHAR(20),
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);
```

### Jobs Table
```sql
CREATE TABLE jobs (
    id INT PRIMARY KEY AUTO_INCREMENT,
    department_id INT NOT NULL,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    requirements TEXT,
    employment_type ENUM('full_time', 'part_time', 'contract') NOT NULL,
    location VARCHAR(100) NOT NULL,
    salary_range VARCHAR(50),
    deadline DATE NOT NULL,
    status ENUM('pending', 'approved', 'expired') DEFAULT 'pending',
    created_by INT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (department_id) REFERENCES departments(id),
    FOREIGN KEY (created_by) REFERENCES users(id)
);
```

### Applications Table
```sql
CREATE TABLE applications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    job_id INT NOT NULL,
    cover_letter TEXT,
    status ENUM('pending', 'shortlisted', 'rejected', 'accepted') DEFAULT 'pending',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id),
    FOREIGN KEY (job_id) REFERENCES jobs(id)
);
```

### Settings Table
```sql
CREATE TABLE settings (
    id INT PRIMARY KEY AUTO_INCREMENT,
    setting_name VARCHAR(100) NOT NULL,
    setting_value TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY (setting_name)
);
```

### Notifications Table
```sql
CREATE TABLE notifications (
    id INT PRIMARY KEY AUTO_INCREMENT,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'warning', 'success', 'error') NOT NULL,
    target ENUM('all', 'user', 'department', 'job') NOT NULL,
    target_id VARCHAR(50),
    created_by INT NOT NULL,
    status ENUM('unread', 'read') DEFAULT 'unread',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
);
```

### Audit Log Table
```sql
CREATE TABLE audit_log (
    id INT PRIMARY KEY AUTO_INCREMENT,
    user_id INT NOT NULL,
    action VARCHAR(100) NOT NULL,
    details TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    FOREIGN KEY (user_id) REFERENCES users(id)
);
```

## Key Features

### Security Features
- Role-based access control (RBAC)
- Password hashing using PHP's password_hash()
- Prepared statements for SQL injection prevention
- File upload validation
- XSS protection through input sanitization
- CSRF protection
- Session management

### Core Functionality
1. **User Management**
   - Applicant registration and profile management
   - Employer registration (admin-only)
   - Admin user management
   - Role-based permissions

2. **Job Management**
   - Job posting and approval workflow
   - Multiple employment types
   - Location-based filtering
   - Deadline tracking
   - Status management

3. **Application Management**
   - Job application system
   - Cover letter submission
   - Multiple file attachments
   - Application tracking
   - Status updates

4. **Reporting and Analytics**
   - Job reports (PDF/CSV)
   - Application statistics
   - User statistics
   - Department analysis
   - Trend visualization

5. **System Administration**
   - Department management
   - User management
   - Job management
   - Settings configuration
   - Notification system
   - Analytics dashboard

## Technical Stack
- PHP 8.x
- MySQL/MariaDB
- Bootstrap 5
- Chart.js
- jQuery
- DataTables
- mPDF (for PDF generation)

## Best Practices Implemented
1. **Security**
   - Input validation
   - Output escaping
   - Prepared statements
   - Secure file uploads
   - Password hashing

2. **Code Organization**
   - Separation of concerns
   - Consistent naming conventions
   - Modular code structure
   - Error handling

3. **Performance**
   - Efficient database queries
   - Proper indexing
   - Caching mechanisms
   - Optimized file handling

4. **Maintainability**
   - Documentation
   - Consistent code style
   - Error logging
   - Configuration management

## Future Considerations
1. **Scalability**
   - Database optimization
   - Caching strategy
   - Load balancing

2. **Enhancements**
   - Mobile responsiveness
   - Advanced search features
   - Social media integration
   - Email templates

3. **Security**
   - Two-factor authentication
   - Rate limiting
   - API security
   - Regular security audits
