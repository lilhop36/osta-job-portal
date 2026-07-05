-- Migration: 003_add_production_indexes.sql
-- Purpose: Add missing indexes for common production queries.
-- Run after the base schema and previous migrations have been applied.

CREATE INDEX IF NOT EXISTS idx_applications_job_user
    ON applications (job_id, user_id);

CREATE INDEX IF NOT EXISTS idx_jobs_department_status
    ON jobs (department_id, status);

CREATE INDEX IF NOT EXISTS idx_users_role_department
    ON users (role, department_id);

CREATE INDEX IF NOT EXISTS idx_notifications_status_target
    ON notifications (status, target, target_id);

CREATE INDEX IF NOT EXISTS idx_application_documents_application_type
    ON application_documents (application_id, document_type);
