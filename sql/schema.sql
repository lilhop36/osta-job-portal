-- ============================================================
-- OSTA Job Portal — Complete Database Schema
-- Version: 2026.07
-- ============================================================
-- This file defines ALL tables for the OSTA Job Portal.
-- Run this on a fresh database or use as migration reference.
-- ============================================================

SET NAMES utf8mb4;
SET FOREIGN_KEY_CHECKS = 0;

-- ============================================================
-- 1. CORE PLATFORM TABLES
-- ============================================================

-- -----------------------------------------------------------
-- departments
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS departments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    description TEXT,
    contact_email VARCHAR(100),
    contact_phone VARCHAR(20),
    location VARCHAR(200),
    created_by INT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_name (name),
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- users
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS users (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    username VARCHAR(50) UNIQUE NOT NULL,
    email VARCHAR(100) UNIQUE NOT NULL,
    password VARCHAR(255) NOT NULL,
    role ENUM('applicant', 'employer', 'admin') NOT NULL,
    status ENUM('active', 'pending', 'inactive') DEFAULT 'active',
    email_verified TINYINT(1) DEFAULT 0,
    full_name VARCHAR(150),
    first_name VARCHAR(100),
    last_name VARCHAR(100),
    phone VARCHAR(20),
    address TEXT,
    skills TEXT,
    education TEXT,
    experience TEXT,
    department_id INT UNSIGNED,
    account_status VARCHAR(20) DEFAULT 'active',
    api_token VARCHAR(64) NULL,
    api_token_expires DATETIME NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_role (role),
    INDEX idx_status (status),
    INDEX idx_email (email),
    INDEX idx_department (department_id),
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- Now that users exists, add FK to departments
ALTER TABLE departments
    ADD FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL;

-- -----------------------------------------------------------
-- companies (NEW — employer profiles)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS companies (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    name VARCHAR(200) NOT NULL,
    description TEXT,
    website VARCHAR(255),
    logo VARCHAR(255),
    industry VARCHAR(100),
    size ENUM('1-10', '11-50', '51-200', '201-500', '501-1000', '1000+') DEFAULT '1-10',
    founded_year YEAR,
    address TEXT,
    city VARCHAR(100),
    region VARCHAR(100),
    phone VARCHAR(20),
    email VARCHAR(100),
    is_verified TINYINT(1) DEFAULT 0,
    verified_by INT UNSIGNED,
    verified_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_industry (industry),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- jobs
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS jobs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    department_id INT UNSIGNED NOT NULL,
    company_id INT UNSIGNED,
    title VARCHAR(200) NOT NULL,
    description TEXT NOT NULL,
    requirements TEXT,
    responsibilities TEXT,
    employment_type ENUM('full_time', 'part_time', 'contract', 'internship') NOT NULL,
    location VARCHAR(100) NOT NULL,
    salary_range VARCHAR(50),
    salary VARCHAR(100),
    deadline DATE NOT NULL,
    status ENUM('pending', 'approved', 'expired', 'closed') DEFAULT 'pending',
    application_count INT UNSIGNED DEFAULT 0,
    created_by INT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_department (department_id),
    INDEX idx_status (status),
    INDEX idx_created_by (created_by),
    INDEX idx_deadline (deadline),
    INDEX idx_employment_type (employment_type),
    FULLTEXT INDEX idx_fulltext_search (title, description, requirements),
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE RESTRICT,
    FOREIGN KEY (company_id) REFERENCES companies(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- categories (NEW — job category taxonomy)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS categories (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    parent_id INT UNSIGNED,
    sort_order INT DEFAULT 0,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_parent (parent_id),
    INDEX idx_slug (slug),
    FOREIGN KEY (parent_id) REFERENCES categories(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- job_categories (junction)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS job_categories (
    job_id INT UNSIGNED NOT NULL,
    category_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (job_id, category_id),
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
    FOREIGN KEY (category_id) REFERENCES categories(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- locations (NEW — structured regions/cities)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS locations (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    region VARCHAR(100),
    country VARCHAR(100) DEFAULT 'Ethiopia',
    latitude DECIMAL(10, 8),
    longitude DECIMAL(11, 8),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_region (region),
    INDEX idx_name (name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- skills (NEW — structured skill taxonomy)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS skills (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    slug VARCHAR(100) UNIQUE NOT NULL,
    category VARCHAR(100),
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_slug (slug),
    INDEX idx_category (category)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- user_skills (NEW — junction)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS user_skills (
    user_id INT UNSIGNED NOT NULL,
    skill_id INT UNSIGNED NOT NULL,
    proficiency ENUM('beginner', 'intermediate', 'advanced', 'expert') DEFAULT 'intermediate',
    years_experience INT UNSIGNED,
    PRIMARY KEY (user_id, skill_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (skill_id) REFERENCES skills(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- job_skills (NEW — junction)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS job_skills (
    job_id INT UNSIGNED NOT NULL,
    skill_id INT UNSIGNED NOT NULL,
    is_required TINYINT(1) DEFAULT 0,
    min_proficiency ENUM('beginner', 'intermediate', 'advanced', 'expert') DEFAULT 'beginner',
    PRIMARY KEY (job_id, skill_id),
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
    FOREIGN KEY (skill_id) REFERENCES skills(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- settings
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS settings (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    setting_name VARCHAR(100) NOT NULL,
    setting_value TEXT NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    UNIQUE KEY uk_setting_name (setting_name)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 2. APPLICATION SYSTEM TABLES
-- ============================================================

-- -----------------------------------------------------------
-- applications (legacy — simple one-to-one with jobs)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS applications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    job_id INT UNSIGNED NOT NULL,
    cover_letter TEXT,
    resume_path VARCHAR(500),
    status ENUM('pending', 'shortlisted', 'rejected', 'accepted') DEFAULT 'pending',
    feedback TEXT,
    updated_by INT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_job (job_id),
    INDEX idx_status (status),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE,
    FOREIGN KEY (updated_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- centralized_applications (primary application system)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS centralized_applications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    department_id INT UNSIGNED,
    application_number VARCHAR(20) UNIQUE NOT NULL,
    first_name VARCHAR(100) NOT NULL,
    last_name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    phone VARCHAR(20) NOT NULL,
    national_id VARCHAR(20) NOT NULL,
    date_of_birth DATE NOT NULL,
    gender ENUM('male', 'female', 'other') NOT NULL,
    address TEXT NOT NULL,
    city VARCHAR(100) NOT NULL,
    region VARCHAR(100) NOT NULL,
    education_level ENUM('high_school', 'diploma', 'bachelor', 'master', 'phd') NOT NULL,
    field_of_study VARCHAR(200) NOT NULL,
    institution VARCHAR(200) NOT NULL,
    graduation_year YEAR NOT NULL,
    gpa DECIMAL(3, 2),
    years_of_experience INT UNSIGNED DEFAULT 0,
    current_position VARCHAR(200),
    current_employer VARCHAR(200),
    preferred_departments JSON,
    preferred_positions JSON,
    willing_to_relocate TINYINT(1) DEFAULT 0,
    expected_salary_min INT UNSIGNED,
    expected_salary_max INT UNSIGNED,
    status ENUM('draft', 'submitted', 'under_review', 'shortlisted', 'interview_scheduled', 'rejected', 'accepted', 'onboarding') DEFAULT 'draft',
    eligibility_status ENUM('pending', 'eligible', 'not_eligible') DEFAULT 'pending',
    eligibility_notes TEXT,
    submitted_at TIMESTAMP NULL,
    reviewed_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_department (department_id),
    INDEX idx_status (status),
    INDEX idx_application_number (application_number),
    INDEX idx_submitted (submitted_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- application_documents
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS application_documents (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    application_id INT UNSIGNED NOT NULL,
    document_type ENUM('resume', 'cover_letter', 'national_id', 'passport', 'diploma', 'transcript', 'certificate', 'recommendation_letter', 'other') NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    stored_filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT UNSIGNED NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    is_required TINYINT(1) DEFAULT 0,
    verification_status ENUM('pending', 'verified', 'rejected') DEFAULT 'pending',
    verification_notes TEXT,
    verified_by INT UNSIGNED,
    verified_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_application (application_id),
    INDEX idx_verification (verification_status),
    FOREIGN KEY (application_id) REFERENCES centralized_applications(id) ON DELETE CASCADE,
    FOREIGN KEY (verified_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- application_status_history
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS application_status_history (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    application_id INT UNSIGNED NOT NULL,
    old_status VARCHAR(50),
    new_status VARCHAR(50) NOT NULL,
    changed_by INT UNSIGNED,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_application (application_id),
    FOREIGN KEY (application_id) REFERENCES centralized_applications(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- application_history (for legacy applications table)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS application_history (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    application_id INT UNSIGNED NOT NULL,
    old_status VARCHAR(50),
    new_status VARCHAR(50) NOT NULL,
    changed_by INT UNSIGNED,
    feedback TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_application (application_id),
    FOREIGN KEY (application_id) REFERENCES applications(id) ON DELETE CASCADE,
    FOREIGN KEY (changed_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- eligibility_criteria
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS eligibility_criteria (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    department_id INT UNSIGNED,
    position_type VARCHAR(200),
    criteria_name VARCHAR(200) NOT NULL,
    criteria_type ENUM('education_level', 'field_of_study', 'years_experience', 'age_range', 'certification', 'skill', 'other') NOT NULL,
    operator ENUM('equals', 'greater_than', 'less_than', 'greater_equal', 'less_equal', 'contains', 'in_list') NOT NULL,
    required_value TEXT NOT NULL,
    is_mandatory TINYINT(1) DEFAULT 1,
    weight INT UNSIGNED DEFAULT 1,
    is_active TINYINT(1) DEFAULT 1,
    created_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_department (department_id),
    INDEX idx_active (is_active),
    FOREIGN KEY (department_id) REFERENCES departments(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- application_eligibility_checks
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS application_eligibility_checks (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    application_id INT UNSIGNED NOT NULL,
    criteria_id INT UNSIGNED NOT NULL,
    check_result ENUM('pass', 'fail', 'pending') NOT NULL,
    actual_value TEXT,
    score INT UNSIGNED DEFAULT 0,
    notes TEXT,
    checked_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_application_criteria (application_id, criteria_id),
    FOREIGN KEY (application_id) REFERENCES centralized_applications(id) ON DELETE CASCADE,
    FOREIGN KEY (criteria_id) REFERENCES eligibility_criteria(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 3. INTERVIEW SYSTEM TABLES
-- -----------------------------------------------------------
-- NOTE: These tables were inferred from code. Formal definitions
-- below match the column usage in admin/manage_interviews.php
-- and applicant/interviews.php.
-- ============================================================

-- -----------------------------------------------------------
-- interview_types
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS interview_types (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    type_name VARCHAR(100),
    description TEXT,
    is_active TINYINT(1) DEFAULT 1,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- interviews
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS interviews (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    application_id INT UNSIGNED NOT NULL,
    interview_type_id INT UNSIGNED,
    interview_code VARCHAR(20) UNIQUE,
    interview_type ENUM('in_person', 'phone', 'video', 'panel') DEFAULT 'in_person',
    scheduled_date DATE NOT NULL,
    start_time TIME NOT NULL,
    duration_minutes INT UNSIGNED DEFAULT 60,
    venue VARCHAR(200),
    meeting_link VARCHAR(500),
    primary_interviewer_id INT UNSIGNED,
    status ENUM('scheduled', 'completed', 'cancelled', 'rescheduled') DEFAULT 'scheduled',
    feedback TEXT,
    overall_rating DECIMAL(3, 2),
    recommendation VARCHAR(200),
    evaluation_score DECIMAL(5, 2),
    evaluation_result VARCHAR(50),
    created_by INT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_application (application_id),
    INDEX idx_interviewer (primary_interviewer_id),
    INDEX idx_date (scheduled_date),
    INDEX idx_status (status),
    INDEX idx_code (interview_code),
    FOREIGN KEY (application_id) REFERENCES centralized_applications(id) ON DELETE CASCADE,
    FOREIGN KEY (interview_type_id) REFERENCES interview_types(id) ON DELETE SET NULL,
    FOREIGN KEY (primary_interviewer_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (created_by) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- interview_panel_members
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS interview_panel_members (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    interview_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    role VARCHAR(50) DEFAULT 'member',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_interview (interview_id),
    INDEX idx_user (user_id),
    FOREIGN KEY (interview_id) REFERENCES interviews(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 4. NOTIFICATION SYSTEM TABLES
-- ============================================================

-- -----------------------------------------------------------
-- notifications
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS notifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    title VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    type ENUM('info', 'warning', 'success', 'error') NOT NULL,
    target ENUM('all', 'user', 'department', 'job') NOT NULL,
    target_id VARCHAR(50),
    user_id INT UNSIGNED,
    created_by INT UNSIGNED NOT NULL,
    status ENUM('unread', 'read') DEFAULT 'unread',
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_target (target, target_id),
    INDEX idx_user_id (user_id),
    INDEX idx_status (status),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- notification_templates
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS notification_templates (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    template_code VARCHAR(50) UNIQUE NOT NULL,
    template_name VARCHAR(200) NOT NULL,
    notification_type ENUM('email', 'sms', 'system') NOT NULL,
    subject VARCHAR(255),
    message_template TEXT NOT NULL,
    variables JSON,
    is_active TINYINT(1) DEFAULT 1,
    created_by INT UNSIGNED NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    FOREIGN KEY (created_by) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- notification_queue
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS notification_queue (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    recipient_id INT UNSIGNED NOT NULL,
    notification_type ENUM('email', 'sms', 'system') NOT NULL,
    recipient_email VARCHAR(100),
    recipient_phone VARCHAR(20),
    subject VARCHAR(255),
    message TEXT NOT NULL,
    reference_type VARCHAR(50),
    status ENUM('pending', 'sent', 'failed') DEFAULT 'pending',
    scheduled_at DATETIME DEFAULT CURRENT_TIMESTAMP,
    attempts INT UNSIGNED DEFAULT 0,
    sent_at DATETIME,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_status_scheduled (status, scheduled_at),
    INDEX idx_recipient (recipient_id),
    FOREIGN KEY (recipient_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 5. MESSAGING SYSTEM TABLE (NEW)
-- ============================================================

CREATE TABLE IF NOT EXISTS messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    sender_id INT UNSIGNED NOT NULL,
    receiver_id INT UNSIGNED NOT NULL,
    subject VARCHAR(200),
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    read_at TIMESTAMP NULL,
    parent_id INT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_sender (sender_id),
    INDEX idx_receiver (receiver_id),
    INDEX idx_conversation (sender_id, receiver_id),
    INDEX idx_read (is_read),
    FOREIGN KEY (sender_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (receiver_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (parent_id) REFERENCES messages(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 6. APPLICANT FEATURE TABLES
-- ============================================================

-- -----------------------------------------------------------
-- saved_jobs
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS saved_jobs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    job_id INT UNSIGNED NOT NULL,
    notes TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    UNIQUE KEY uk_user_job (user_id, job_id),
    INDEX idx_user (user_id),
    INDEX idx_job (job_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- job_alerts
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS job_alerts (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    keywords VARCHAR(200),
    location VARCHAR(100),
    job_type VARCHAR(50),
    frequency ENUM('daily', 'weekly', 'monthly') DEFAULT 'daily',
    is_active TINYINT(1) DEFAULT 1,
    last_sent_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_active (is_active),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- applicant_documents (standalone uploads not tied to application)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS applicant_documents (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    document_type VARCHAR(50) NOT NULL,
    original_filename VARCHAR(255) NOT NULL,
    stored_filename VARCHAR(255) NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    file_size INT UNSIGNED NOT NULL,
    mime_type VARCHAR(100) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- password_resets (NEW — for forgot password flow)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS password_resets (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    token VARCHAR(64) UNIQUE NOT NULL,
    expires_at DATETIME NOT NULL,
    used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_token (token),
    INDEX idx_user (user_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 7. EMPLOYER FEATURE TABLES
-- ============================================================

-- -----------------------------------------------------------
-- vacancy_requests
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS vacancy_requests (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    request_number VARCHAR(20) UNIQUE NOT NULL,
    department_id INT UNSIGNED NOT NULL,
    requested_by INT UNSIGNED NOT NULL,
    position_title VARCHAR(200) NOT NULL,
    position_description TEXT NOT NULL,
    number_of_positions INT UNSIGNED NOT NULL DEFAULT 1,
    employment_type ENUM('full_time', 'part_time', 'contract', 'internship') NOT NULL,
    salary_min INT UNSIGNED,
    salary_max INT UNSIGNED,
    education_requirements TEXT NOT NULL,
    experience_requirements TEXT NOT NULL,
    skills_requirements TEXT NOT NULL,
    other_requirements TEXT,
    business_justification TEXT NOT NULL,
    urgency_level ENUM('low', 'medium', 'high', 'critical') DEFAULT 'medium',
    preferred_start_date DATE,
    status ENUM('draft', 'submitted', 'hr_review', 'approved', 'rejected', 'published', 'closed') DEFAULT 'draft',
    hr_reviewer_id INT UNSIGNED,
    hr_review_notes TEXT,
    hr_reviewed_at TIMESTAMP NULL,
    final_approver_id INT UNSIGNED,
    final_approval_notes TEXT,
    approved_at TIMESTAMP NULL,
    job_id INT UNSIGNED,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_department (department_id),
    INDEX idx_status (status),
    FOREIGN KEY (department_id) REFERENCES departments(id),
    FOREIGN KEY (requested_by) REFERENCES users(id),
    FOREIGN KEY (hr_reviewer_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (final_approver_id) REFERENCES users(id) ON DELETE SET NULL,
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- job_attachments
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS job_attachments (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    job_id INT UNSIGNED NOT NULL,
    file_path VARCHAR(500) NOT NULL,
    original_name VARCHAR(255) NOT NULL,
    file_size INT UNSIGNED,
    mime_type VARCHAR(100),
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_job (job_id),
    FOREIGN KEY (job_id) REFERENCES jobs(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 8. CONTACT & MESSAGES
-- ============================================================

-- -----------------------------------------------------------
-- contact_messages
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS contact_messages (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) NOT NULL,
    email VARCHAR(100) NOT NULL,
    subject VARCHAR(200) NOT NULL,
    message TEXT NOT NULL,
    is_read TINYINT(1) DEFAULT 0,
    replied_at TIMESTAMP NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_read (is_read)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 9. AUDIT & LOGGING TABLES
-- ============================================================

-- -----------------------------------------------------------
-- audit_log (general audit trail)
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS audit_log (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    action VARCHAR(100) NOT NULL,
    details TEXT,
    data JSON,
    ip_address VARCHAR(45),
    user_agent TEXT,
    request_uri TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_user (user_id),
    INDEX idx_action (action),
    INDEX idx_created (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- application_audit_log
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS application_audit_log (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    application_id INT UNSIGNED NOT NULL,
    user_id INT UNSIGNED NOT NULL,
    action VARCHAR(100) NOT NULL,
    context JSON,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_application (application_id),
    INDEX idx_user (user_id),
    FOREIGN KEY (application_id) REFERENCES centralized_applications(id) ON DELETE CASCADE,
    FOREIGN KEY (user_id) REFERENCES users(id)
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- system_logs
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS system_logs (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED,
    event_type VARCHAR(50) NOT NULL,
    details TEXT,
    ip_address VARCHAR(45),
    user_agent TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_event_type (event_type),
    INDEX idx_created (created_at),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 10. SESSION & AUTH TABLES
-- ============================================================

-- -----------------------------------------------------------
-- sessions
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS sessions (
    id VARCHAR(128) NOT NULL PRIMARY KEY,
    user_id INT UNSIGNED,
    ip_address VARCHAR(45),
    user_agent TEXT,
    payload LONGTEXT NOT NULL,
    last_activity INT(11) NOT NULL,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    updated_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP ON UPDATE CURRENT_TIMESTAMP,
    INDEX idx_user_id (user_id),
    INDEX idx_last_activity (last_activity),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE SET NULL
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- -----------------------------------------------------------
-- email_verifications
-- -----------------------------------------------------------
CREATE TABLE IF NOT EXISTS email_verifications (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    user_id INT UNSIGNED NOT NULL,
    otp_code VARCHAR(6) NOT NULL,
    purpose ENUM('email_verify', 'password_reset') NOT NULL DEFAULT 'email_verify',
    expires_at DATETIME NOT NULL,
    used TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    INDEX idx_otp_lookup (user_id, purpose, used),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 11. ROLES & PERMISSIONS (NEW — granular RBAC)
-- ============================================================

CREATE TABLE IF NOT EXISTS roles (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(50) UNIQUE NOT NULL,
    display_name VARCHAR(100) NOT NULL,
    description TEXT,
    is_system TINYINT(1) DEFAULT 0,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS permissions (
    id INT UNSIGNED AUTO_INCREMENT PRIMARY KEY,
    name VARCHAR(100) UNIQUE NOT NULL,
    description TEXT,
    created_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS role_permissions (
    role_id INT UNSIGNED NOT NULL,
    permission_id INT UNSIGNED NOT NULL,
    PRIMARY KEY (role_id, permission_id),
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE,
    FOREIGN KEY (permission_id) REFERENCES permissions(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

CREATE TABLE IF NOT EXISTS user_roles (
    user_id INT UNSIGNED NOT NULL,
    role_id INT UNSIGNED NOT NULL,
    assigned_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP,
    PRIMARY KEY (user_id, role_id),
    FOREIGN KEY (user_id) REFERENCES users(id) ON DELETE CASCADE,
    FOREIGN KEY (role_id) REFERENCES roles(id) ON DELETE CASCADE
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- 12. SCHEMA VERSION TRACKING
-- ============================================================

CREATE TABLE IF NOT EXISTS schema_migrations (
    version INT UNSIGNED PRIMARY KEY,
    name VARCHAR(200) NOT NULL,
    applied_at TIMESTAMP DEFAULT CURRENT_TIMESTAMP
) ENGINE=InnoDB DEFAULT CHARSET=utf8mb4 COLLATE=utf8mb4_unicode_ci;

-- ============================================================
-- SEED DATA: Default roles
-- ============================================================

INSERT INTO roles (name, display_name, description, is_system) VALUES
    ('admin', 'Administrator', 'Full system access', 1),
    ('employer', 'Employer', 'Can post jobs and manage applications', 1),
    ('applicant', 'Applicant', 'Can apply for jobs and manage profile', 1)
ON DUPLICATE KEY UPDATE display_name = VALUES(display_name);

-- ============================================================
-- DONE
-- ============================================================

SET FOREIGN_KEY_CHECKS = 1;
