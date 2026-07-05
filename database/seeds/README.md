# Demo Seed Data

This project currently ships with a populated local development database in the XAMPP environment used during development.

For a clean portfolio setup, keep demo credentials and sample records in development-only seed files. Do not publish production passwords or real applicant documents.

Recommended demo roles:

- Admin: manages users, departments, jobs, and reports.
- Employer: posts jobs, reviews applicants, and updates statuses.
- Applicant: completes a profile, uploads documents, saves jobs, and applies.

Before publishing the repository, export sanitized seed data with:

```bash
mysqldump -u root --no-create-info --skip-triggers osta_job_portal departments jobs users > database/seeds/demo_seed.sql
```

Then manually remove real emails, phone numbers, resumes, and personal details before committing the seed file.
