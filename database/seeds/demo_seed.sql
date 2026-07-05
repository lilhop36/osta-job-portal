-- Development-only sanitized demo accounts.
-- Password for all demo users: DemoPass123!
-- Never reuse these credentials in production.

INSERT INTO departments (id, name, description, contact_email)
VALUES (1, 'Human Resources', 'Demo department for portfolio testing.', 'hr@example.com')
ON DUPLICATE KEY UPDATE name = VALUES(name), description = VALUES(description), contact_email = VALUES(contact_email);

INSERT INTO users (username, email, password, role, status, department_id, full_name, account_status)
VALUES
('demo_admin', 'admin@example.com', '$argon2id$v=19$m=65536,t=4,p=1$MHpFN1g2UFFLUzZwYUowQQ$MK32i0AJBtKYONQaOjZV5QPrwTdyNnjv3z0m7c6bKTE', 'admin', 'active', NULL, 'Demo Admin', 'active'),
('demo_employer', 'employer@example.com', '$argon2id$v=19$m=65536,t=4,p=1$MHpFN1g2UFFLUzZwYUowQQ$MK32i0AJBtKYONQaOjZV5QPrwTdyNnjv3z0m7c6bKTE', 'employer', 'active', 1, 'Demo Employer', 'active'),
('demo_applicant', 'applicant@example.com', '$argon2id$v=19$m=65536,t=4,p=1$MHpFN1g2UFFLUzZwYUowQQ$MK32i0AJBtKYONQaOjZV5QPrwTdyNnjv3z0m7c6bKTE', 'applicant', 'active', NULL, 'Demo Applicant', 'active')
ON DUPLICATE KEY UPDATE
    password = VALUES(password),
    role = VALUES(role),
    status = VALUES(status),
    department_id = VALUES(department_id),
    full_name = VALUES(full_name),
    account_status = VALUES(account_status);

INSERT INTO jobs (department_id, title, description, requirements, employment_type, location, deadline, status, created_by)
SELECT 1,
       'Demo Software Developer',
       'Portfolio demo vacancy used for smoke testing the OSTA Job Portal.',
       'PHP, MySQL, basic web development, and communication skills.',
       'full_time',
       'Addis Ababa',
       DATE_ADD(CURDATE(), INTERVAL 30 DAY),
       'approved',
       u.id
FROM users u
WHERE u.email = 'employer@example.com'
  AND NOT EXISTS (SELECT 1 FROM jobs WHERE title = 'Demo Software Developer');
