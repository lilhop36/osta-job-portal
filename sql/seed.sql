-- ============================================================
-- OSTA Job Portal — Seed Data
-- ============================================================
-- Run AFTER schema.sql to populate with demo data.
-- ============================================================

SET NAMES utf8mb4;

-- ============================================================
-- Departments
-- ============================================================
INSERT INTO departments (name, description, contact_email, contact_phone, location) VALUES
('Information Technology', 'Software development, networking, and IT infrastructure', 'it@osta.gov.et', '+251-11-1111111', 'Addis Ababa'),
('Human Resources', 'Recruitment, employee relations, and organizational development', 'hr@osta.gov.et', '+251-11-2222222', 'Addis Ababa'),
('Finance & Accounting', 'Financial planning, budgeting, and accounting operations', 'finance@osta.gov.et', '+251-11-3333333', 'Addis Ababa'),
('Research & Development', 'Innovation, research projects, and technology development', 'rd@osta.gov.et', '+251-11-4444444', 'Addis Ababa'),
('Administration', 'General administration, office management, and support services', 'admin@osta.gov.et', '+251-11-5555555', 'Addis Ababa'),
('Marketing & Communications', 'Public relations, marketing campaigns, and brand management', 'marketing@osta.gov.et', '+251-11-6666666', 'Addis Ababa')
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- ============================================================
-- Admin User (password: admin123 — Argon2id hash)
-- ============================================================
-- Note: The hash below is a placeholder. In production, always
-- use hash_password('admin123') to generate a proper Argon2id hash.
INSERT INTO users (username, email, password, role, status, email_verified, full_name, first_name, last_name) VALUES
('admin', 'admin@osta.gov.et', '$argon2id$v=19$m=65536,t=4,p=3$bWFzdGVy$placeholder_hash', 'admin', 'active', 1, 'System Administrator', 'System', 'Admin')
ON DUPLICATE KEY UPDATE email = VALUES(email);

-- ============================================================
-- Categories
-- ============================================================
INSERT INTO categories (name, slug, description, is_active) VALUES
('Software Development', 'software-development', 'Software engineering and programming roles', 1),
('Data Science', 'data-science', 'Data analysis, machine learning, and AI roles', 1),
('Network Engineering', 'network-engineering', 'Network administration and infrastructure roles', 1),
('Project Management', 'project-management', 'Project and program management roles', 1),
('Research', 'research', 'Scientific research and development roles', 1),
('Administration', 'administration', 'General administrative and office roles', 1),
('Finance', 'finance', 'Financial and accounting roles', 1),
('Human Resources', 'human-resources', 'HR and recruitment roles', 1)
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- ============================================================
-- Locations
-- ============================================================
INSERT INTO locations (name, region, country) VALUES
('Addis Ababa', 'Addis Ababa', 'Ethiopia'),
('Adama', 'Oromia', 'Ethiopia'),
('Bahir Dar', 'Amhara', 'Ethiopia'),
('Hawassa', 'Sidama', 'Ethiopia'),
('Mekelle', 'Tigray', 'Ethiopia'),
('Jimma', 'Oromia', 'Ethiopia'),
('Dire Dawa', 'Dire Dawa', 'Ethiopia'),
('Bishoftu', 'Oromia', 'Ethiopia'),
('Nekemte', 'Oromia', 'Ethiopia'),
('Shashamane', 'Oromia', 'Ethiopia')
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- ============================================================
-- Skills
-- ============================================================
INSERT INTO skills (name, slug, category, is_active) VALUES
('PHP', 'php', 'Programming', 1),
('JavaScript', 'javascript', 'Programming', 1),
('Python', 'python', 'Programming', 1),
('Java', 'java', 'Programming', 1),
('MySQL', 'mysql', 'Databases', 1),
('PostgreSQL', 'postgresql', 'Databases', 1),
('HTML/CSS', 'html-css', 'Web Development', 1),
('React', 'react', 'Frameworks', 1),
('Node.js', 'nodejs', 'Frameworks', 1),
('Docker', 'docker', 'DevOps', 1),
('AWS', 'aws', 'Cloud', 1),
('Git', 'git', 'Tools', 1),
('Project Management', 'project-management', 'Soft Skills', 1),
('Communication', 'communication', 'Soft Skills', 1),
('Leadership', 'leadership', 'Soft Skills', 1),
('Data Analysis', 'data-analysis', 'Data', 1),
('Machine Learning', 'machine-learning', 'Data', 1),
('SQL', 'sql', 'Databases', 1),
('Linux Administration', 'linux-admin', 'Systems', 1),
('Networking', 'networking', 'Infrastructure', 1)
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- ============================================================
-- Interview Types
-- ============================================================
INSERT INTO interview_types (name, type_name, is_active) VALUES
('In-Person Interview', 'in_person', 1),
('Phone Interview', 'phone', 1),
('Video Interview', 'video', 1),
('Panel Interview', 'panel', 1),
('Technical Assessment', 'technical', 1),
('HR Screening', 'hr_screening', 1)
ON DUPLICATE KEY UPDATE name = VALUES(name);

-- ============================================================
-- Notification Templates
-- ============================================================
INSERT INTO notification_templates (template_code, template_name, notification_type, subject, message_template, variables, created_by) VALUES
('APP_SUBMITTED', 'Application Submitted', 'email', 'Application Submitted Successfully', 'Dear {{first_name}}, your application {{application_number}} has been submitted successfully.', '["first_name", "application_number"]', 1),
('APP_UNDER_REVIEW', 'Application Under Review', 'email', 'Application Under Review', 'Dear {{first_name}}, your application {{application_number}} is now under review.', '["first_name", "application_number"]', 1),
('APP_SHORTLISTED', 'Application Shortlisted', 'email', 'Congratulations! You have been shortlisted', 'Dear {{first_name}}, congratulations! Your application {{application_number}} has been shortlisted.', '["first_name", "application_number"]', 1),
('APP_REJECTED', 'Application Rejected', 'email', 'Application Update', 'Dear {{first_name}}, we regret to inform you that your application {{application_number}} was not successful at this time.', '["first_name", "application_number"]', 1),
('APP_ACCEPTED', 'Application Accepted', 'email', 'Congratulations! You have been accepted', 'Dear {{first_name}}, congratulations! Your application {{application_number}} has been accepted.', '["first_name", "application_number"]', 1),
('INTERVIEW_SCHEDULED', 'Interview Scheduled', 'email', 'Interview Scheduled', 'Dear {{first_name}}, an interview has been scheduled for your application {{application_number}} on {{interview_date}} at {{interview_time}}.', '["first_name", "application_number", "interview_date", "interview_time"]', 1),
('PASSWORD_RESET', 'Password Reset', 'email', 'Password Reset Request', 'Hello {{username}}, a password reset has been requested for your account. Use the code {{otp_code}} to reset your password.', '["username", "otp_code"]', 1),
('WELCOME', 'Welcome Email', 'email', 'Welcome to OSTA Job Portal', 'Dear {{first_name}}, welcome to OSTA Job Portal! Your account has been created successfully.', '["first_name"]', 1)
ON DUPLICATE KEY UPDATE template_name = VALUES(template_name);

-- ============================================================
-- Roles (from schema.sql seed, ensure they exist)
-- ============================================================
-- Already inserted by schema.sql via ON DUPLICATE KEY UPDATE

-- ============================================================
-- Sample Jobs (for demo)
-- ============================================================
INSERT INTO jobs (department_id, title, description, requirements, responsibilities, employment_type, location, salary_range, salary, deadline, status, created_by) VALUES
(1, 'Senior Software Developer', 'We are looking for an experienced software developer to join our IT team.', 'Bachelor degree in Computer Science or related field. 5+ years experience in PHP/Python development.', 'Develop and maintain web applications. Code review. Mentor junior developers.', 'full_time', 'Addis Ababa', '30,000 - 50,000 ETB', '40,000 ETB', DATE_ADD(CURDATE(), INTERVAL 30 DAY), 'approved', 1),
(1, 'Network Engineer', 'Join our infrastructure team to manage and optimize our network systems.', 'Bachelor degree in IT or related field. CCNA certification preferred. 3+ years experience.', 'Manage network infrastructure. Monitor system performance. Troubleshoot issues.', 'full_time', 'Addis Ababa', '25,000 - 40,000 ETB', '32,000 ETB', DATE_ADD(CURDATE(), INTERVAL 25 DAY), 'approved', 1),
(2, 'HR Officer', 'Seeking a dedicated HR officer to manage recruitment and employee relations.', 'Bachelor degree in HR or Business Administration. 2+ years HR experience.', 'Manage recruitment process. Handle employee onboarding. Maintain HR records.', 'full_time', 'Addis Ababa', '20,000 - 30,000 ETB', '25,000 ETB', DATE_ADD(CURDATE(), INTERVAL 20 DAY), 'approved', 1),
(3, 'Financial Analyst', 'Looking for a detail-oriented financial analyst to support our finance team.', 'Bachelor degree in Finance or Accounting. Proficiency in Excel and financial modeling.', 'Prepare financial reports. Analyze budgets. Support audit processes.', 'full_time', 'Adama', '22,000 - 35,000 ETB', '28,000 ETB', DATE_ADD(CURDATE(), INTERVAL 15 DAY), 'approved', 1),
(4, 'Research Assistant', 'Join our R&D team to support ongoing research projects.', 'Bachelor degree in relevant field. Strong analytical skills. Research experience preferred.', 'Assist with research projects. Collect and analyze data. Write reports.', 'contract', 'Addis Ababa', '18,000 - 25,000 ETB', '22,000 ETB', DATE_ADD(CURDATE(), INTERVAL 21 DAY), 'approved', 1),
(1, 'Junior Web Developer', 'Entry-level position for aspiring web developers.', 'Basic knowledge of HTML, CSS, JavaScript. Willingness to learn.', 'Build and maintain websites. Fix bugs. Collaborate with senior developers.', 'full_time', 'Jimma', '15,000 - 22,000 ETB', '18,000 ETB', DATE_ADD(CURDATE(), INTERVAL 18 DAY), 'approved', 1),
(5, 'Office Administrator', 'Managing daily office operations and administrative tasks.', 'Diploma in Business Administration. Proficiency in Microsoft Office.', 'Manage office supplies. Coordinate meetings. Handle correspondence.', 'full_time', 'Hawassa', '12,000 - 18,000 ETB', '15,000 ETB', DATE_ADD(CURDATE(), INTERVAL 14 DAY), 'approved', 1),
(6, 'Marketing Coordinator', 'Support our marketing team with campaigns and communications.', 'Bachelor degree in Marketing or Communications. Social media skills.', 'Coordinate marketing campaigns. Manage social media. Create content.', 'full_time', 'Addis Ababa', '18,000 - 28,000 ETB', '23,000 ETB', DATE_ADD(CURDATE(), INTERVAL 22 DAY), 'approved', 1)
ON DUPLICATE KEY UPDATE title = VALUES(title);

-- ============================================================
-- Sample Notification
-- ============================================================
INSERT INTO notifications (title, message, type, target, created_by, status) VALUES
('Welcome to OSTA Job Portal', 'The system has been upgraded with new features. Check out the latest updates!', 'info', 'all', 1, 'unread')
ON DUPLICATE KEY UPDATE title = VALUES(title);

-- ============================================================
-- Eligibility Criteria (defaults)
-- ============================================================
INSERT INTO eligibility_criteria (department_id, criteria_name, criteria_type, operator, required_value, is_mandatory, weight, created_by) VALUES
(NULL, 'Minimum Education Level', 'education_level', 'greater_equal', '"bachelor"', 1, 1, 1),
(NULL, 'Minimum Age', 'age_range', 'greater_equal', '18', 1, 1, 1),
(NULL, 'Maximum Age', 'age_range', 'less_equal', '35', 0, 1, 1),
(NULL, 'Minimum Experience', 'years_experience', 'greater_equal', '0', 0, 1, 1)
ON DUPLICATE KEY UPDATE criteria_name = VALUES(criteria_name);

-- ============================================================
-- Settings (defaults)
-- ============================================================
INSERT INTO settings (setting_name, setting_value) VALUES
('site_title', 'OSTA Job Portal'),
('site_description', 'Connecting talented individuals with opportunities in science and technology'),
('site_email', 'info@osta.gov.et'),
('site_phone', '+251-11-1234567'),
('site_address', 'Addis Ababa, Ethiopia'),
('smtp_host', 'smtp.gmail.com'),
('smtp_port', '587'),
('notification_email', 'noreply@osta.gov.et'),
('allowed_resume_types', 'pdf,doc,docx'),
('allowed_cover_letter_types', 'pdf,doc,docx'),
('max_upload_size', '5242880')
ON DUPLICATE KEY UPDATE setting_value = VALUES(setting_value);

-- ============================================================
-- Done
-- ============================================================
SELECT 'Seed data inserted successfully!' AS status;
