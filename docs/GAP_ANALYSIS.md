# OSTA Job Portal — Codebase Gap Analysis

> Generated: July 2026 | Baseline: 20-Phase Master Plan

## Executive Summary

The OSTA Job Portal has **working core functionality** (register, apply, track, interview) with **surprisingly solid security foundations**, but suffers from architectural debt (procedural PHP, no routing, global state) and significant feature gaps (no messaging, no password reset, no REST API). The codebase is at a crossroads — the scaffolding for modernization exists (`Connection.php`, `services/`, `bootstrap.php`, `.env`), but the migration has barely begun.

**Overall Maturity: 5/10**

---

## Scores by Phase

| Phase | Area | Score | Status |
|-------|------|-------|--------|
| 0 | Research & Planning | 3/10 | SRS exists but outdated. No personas, journeys, KPIs. |
| 1 | Information Architecture | 5/10 | File structure is logical. No centralized routing. 11 pages bypass shared layouts. |
| 2 | Database Architecture | 5.5/10 | 29 tables exist. 14 lack CREATE TABLE. Duplicate application systems. Users table overloaded. |
| 3 | Backend Architecture | 4/10 | Fully procedural. No controllers, router, or DI. `global $pdo` everywhere. Services are stubs. |
| 4 | Frontend Architecture | 5.5/10 | Design system exists (CSS vars, gradients). UI components underutilized. Chart.js broken. |
| 5 | UI/UX Design | 6/10 | Landing page is modern. Dashboards solid. No spacing tokens, no mobile sidebar, no tablet breakpoint. |
| 6 | Auth & Security | 6.5/10 | Strong foundations. Missing: password reset, 2FA, OTP rate limiting, HTTPS, SRI hashes. |
| 7 | Job Seeker Features | 7/10 | Core flow works. Missing: resume builder, recommendations, skills assessment. |
| 8 | Employer Features | 5.5/10 | Dashboard and job CRUD work. Missing: candidate search, shortlist notes, employer branding. |
| 9 | Admin Features | 6/10 | 15 pages. Missing: audit log viewer, bulk operations, system monitoring, role granularity. |
| 10 | Search System | 3/10 | Basic keyword/type/department filter only. No location, salary, experience, skills, or date filters. |
| 11 | Notification System | 5/10 | In-app + email queue exist. No push, no real-time, no per-user read tracking. |
| 12 | Messaging System | 0/10 | Not implemented. |
| 13 | Analytics & Reporting | 5/10 | Pages exist but Chart.js is broken. No employer analytics. |
| 14 | API Design | 1/10 | Only 1 internal JSON endpoint. No REST API. |
| 15 | Performance | 4/10 | No caching, no lazy loading, no image optimization, no pagination. |
| 16 | Accessibility | 4/10 | Some ARIA on modals/alerts. No skip-to-content, focus management, color audit, keyboard nav. |
| 17 | Testing | 2/10 | 3 unit tests, empty integration tests, no CI pipeline. |
| 18 | Deployment | 3/10 | .env works. No CI/CD, staging, error monitoring, automated backups. |
| 19 | Documentation | 4/10 | Multiple .md files exist but outdated. No API docs, user manual, dev guide. |
| 20 | Future Roadmap | 0/10 | Not planned yet. |

---

## Critical Gaps (Must Fix)

| # | Gap | Severity | Phase |
|---|-----|----------|-------|
| 1 | No password reset flow | HIGH | 6 |
| 2 | OTP brute-force vulnerability (no rate limiting) | HIGH | 6 |
| 3 | No centralized router (every page is standalone) | HIGH | 3 |
| 4 | 14 tables lack CREATE TABLE definitions | HIGH | 2 |
| 5 | Duplicate application systems (`applications` + `centralized_applications`) | HIGH | 2 |
| 6 | Chart.js never loaded (analytics/reports broken) | MEDIUM | 4 |
| 7 | 3 conflicting `sanitize()` functions | MEDIUM | 3 |
| 8 | Global `$pdo` everywhere (blocks testability) | MEDIUM | 3 |
| 9 | No messaging system | HIGH | 12 |
| 10 | Users table overloaded (all roles in one table) | MEDIUM | 2 |

---

## What's Working Well

| Area | Evidence |
|------|----------|
| Landing page design | Modern gradient theme, hero section, search, stats |
| Security foundations | Argon2id, CSRF on all forms, CSP headers, session security, upload hardening, rate limiting |
| Core recruitment flow | Register → apply → track → interview pipeline works end-to-end |
| Document upload system | 9 types, MIME validation, hardened uploads directory |
| Admin dashboard | 8 labeled stat cards, quick actions, DataTables |
| Environment config | `.env` via vlucas/phpdotenv, Connection singleton |
| Composer autoloading | PSR-4 for `src/`, classmap for `services/` |

---

## Database Schema Status

### Tables That Exist (29 total)

**Core (7):** users, departments, jobs, applications, settings, notifications, audit_log

**Extended (6):** centralized_applications, application_documents, eligibility_criteria, application_eligibility_checks, notification_templates, vacancy_requests

**Auth (2):** sessions, email_verifications

**Inferred from code (14):** interviews, interview_types, interview_panel_members, application_status_history, application_history, system_logs, application_audit_log, enhanced_audit_log, saved_jobs, job_alerts, applicant_documents, contact_messages, notification_queue, job_attachments

### Tables Missing

| Table | Needed For |
|-------|-----------|
| companies | Employer profiles, company branding |
| messages | Employer↔applicant communication |
| skills + user_skills + job_skills | Structured skill matching |
| categories | Job category taxonomy |
| locations | Structured region/city data |
| roles + permissions | Granular RBAC |
| password_resets | Password reset flow |

---

## Architecture Status

| Pattern | Status |
|---------|--------|
| Routing | File-based (no centralized router) |
| Controllers | None (page files ARE controllers) |
| Models | None (raw SQL everywhere) |
| Services | 5 stubs (1 method each, not integrated) |
| Middleware | None (auth checks inline in each file) |
| Dependency Injection | None (`global $pdo` in 19+ files) |
| PSR-4 Compliance | 1 file (`src/Database/Connection.php`) |
| Testing | 3 unit tests, no integration tests |

---

## Security Status

### Implemented (Strong)
- Argon2id password hashing (64MB memory, 4 iterations)
- CSRF tokens on all forms with `hash_equals()` verification
- PDO prepared statements (no raw SQL injection vectors)
- `htmlspecialchars()` on all output (XSS prevention)
- Secure session config (httponly, SameSite=Strict, 30-min regeneration)
- Session-to-database validation
- Rate limiting on login (5 attempts/15 min)
- CSP headers, X-Frame-Options, X-XSS-Protection
- Upload hardening (script execution blocked, directory listing disabled)
- Open redirect prevention
- Security event logging

### Missing (Critical)
- Password reset flow
- Two-factor authentication
- OTP rate limiting (brute-force vulnerable)
- HTTPS enforcement / HSTS
- SRI hashes on CDN resources
- Account lockout after failed attempts
- CORS policy
- Rate limiting on registration/contact form

---

## Feature Inventory

### Public Pages (6)
Home, Jobs (redirect), Job Details, About, Contact, Health Check

### Auth (4)
Login, Register, Email Verify (OTP), Logout

### Admin (15 files)
Dashboard, Manage Users/Jobs/Departments/Interviews, View/Update Applications, Analytics, Reports, Export, Notifications, Settings, Setup Email, Interview Analytics, API endpoint

### Employer (14 files)
Dashboard, Post/Edit/Manage Jobs, Manage/View Applications, View Applicants, Update Status, Export, Reports, Profile, Change Password, Vacancy Request, Logout

### Applicant (21 files)
Dashboard, Centralized Application, Apply, My Applications, Application Status, Cancel, Document Upload/Download, Save/Saved Jobs, Job Alerts, Eligibility Checker, Interviews, Profile, Change Password, Delete Account, Export, Register, Logout

### Missing Features
- Messaging/chat between employer and applicant
- Company profiles
- Resume builder/generator
- AI job matching
- Two-factor authentication
- Password reset
- REST API
- Push notifications
- Mobile PWA
- Social login
- Dark mode toggle
- Multi-language support
- Real-time notifications (WebSocket)
- Scheduled job expiry cron
- Document verification workflow (admin UI)
