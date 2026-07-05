# OSTA Job Portal — Execution Roadmap

> Strategy: Incremental Refactor | Timeline: 12–16 Weeks | 8 Sprints

## Overview

This roadmap organizes the 20-phase master plan into executable sprints. Each sprint has specific tasks, estimated effort, and clear deliverables. The strategy is **incremental refactor** — keep working code, improve architecture phase-by-phase.

**Key Principles:**
1. Don't break what works
2. Build foundation before features
3. Security first
4. Tests accompany code
5. Document as you go

---

## Sprint 1 — Foundation & Critical Fixes (Weeks 1–2)

**Goal:** Fix what's broken, stabilize what exists, set up development infrastructure.

### 1.1 Database Schema Consolidation
| Task | Effort | Priority |
|------|--------|----------|
| Create `sql/schema.sql` with ALL tables (proper FKs, indexes, cascades) | High | Critical |
| Merge duplicate application systems: keep `centralized_applications`, deprecate `applications` | High | Critical |
| Merge duplicate audit tables: keep `audit_log` + `application_status_history` | Medium | High |
| Add missing indexes (saved_jobs, interviews, centralized_applications) | Low | Medium |
| Create `sql/seed.sql` with demo data | Low | Low |

### 1.2 Critical Security Fixes
| Task | Effort | Priority |
|------|--------|----------|
| Add rate limiting to OTP verification (5 attempts/15 min) | Low | Critical |
| Implement password reset flow (email OTP → new password) | High | Critical |
| Consolidate 3 `sanitize()` functions into 1 | Low | High |
| Add rate limiting to registration and contact form | Low | High |

### 1.3 Fix Broken Features
| Task | Effort | Priority |
|------|--------|----------|
| Load Chart.js CDN in footer on analytics/reports pages | Low | Critical |
| Fix DataTables loading after `</html>` | Low | Medium |
| Remove dead `jobs.php` redirect file | Low | Low |
| Consolidate or delete `header_new.php` / `footer_new.php` | Medium | Low |

### 1.4 Dev Infrastructure
| Task | Effort | Priority |
|------|--------|----------|
| Create `composer.json` scripts for migrations | Low | Medium |
| Add PHPUnit tests for critical security functions | Medium | Medium |

**Deliverable:** Stable codebase, all tables documented, critical security holes fixed, Chart.js working.

---

## Sprint 2 — Architecture Foundation (Weeks 3–4)

**Goal:** Build the backend architecture that enables clean feature development.

### 2.1 Routing & Front Controller
| Task | Effort | Priority |
|------|--------|----------|
| Create `public/index.php` as front controller | High | Critical |
| Create `src/Router.php` with method/path matching | High | Critical |
| Create route definitions: `routes/web.php` | High | Critical |
| Add `.htaccess` rewrite rules to funnel through front controller | Medium | Critical |
| Migrate `about.php` as proof of concept | Medium | High |

### 2.2 Controller Layer
| Task | Effort | Priority |
|------|--------|----------|
| Create `src/Controllers/BaseController.php` (render, redirect, json) | Medium | Critical |
| Create `src/Controllers/AuthController.php` | High | Critical |
| Create `src/Controllers/DashboardController.php` | High | High |
| Extract business logic from auth pages into controllers | High | High |

### 2.3 Middleware
| Task | Effort | Priority |
|------|--------|----------|
| Create `src/Middleware/AuthMiddleware.php` | Medium | Critical |
| Create `src/Middleware/CsrfMiddleware.php` | Medium | Critical |
| Create `src/Middleware/RateLimitMiddleware.php` | Medium | High |
| Integrate middleware pipeline into Router | Medium | Critical |

### 2.4 Service Layer
| Task | Effort | Priority |
|------|--------|----------|
| Move services to PSR-4 namespace: `App\Services\*` | Medium | High |
| Implement `App\Services\UserService` | Medium | High |
| Implement `App\Services\JobService` | Medium | High |
| Implement `App\Services\ApplicationService` | Medium | High |
| Implement `App\Services\AuthService` | Medium | High |

**Deliverable:** Centralized routing, controller layer, middleware pipeline, service layer — existing pages still work via legacy compatibility.

---

## Sprint 3 — Database & Model Layer (Weeks 5–6)

**Goal:** Proper ORM/model layer so code doesn't write raw SQL everywhere.

### 3.1 Model Layer
| Task | Effort | Priority |
|------|--------|----------|
| Create `src/Models/BaseModel.php` (find, findAll, create, update, delete) | Medium | Critical |
| Create `src/Models/User.php` | Medium | Critical |
| Create `src/Models/Job.php` | Medium | Critical |
| Create `src/Models/Application.php` | Medium | Critical |
| Create `src/Models/Department.php` | Low | High |
| Create `src/Models/Interview.php` | Medium | High |
| Create `src/Models/Notification.php` | Low | High |

### 3.2 New Tables
| Task | Effort | Priority |
|------|--------|----------|
| Create `companies` table + model | Medium | High |
| Create `skills` + `user_skills` + `job_skills` tables | Medium | High |
| Create `messages` table (employer↔applicant) | High | High |
| Create `categories` + `job_categories` tables | Low | Medium |
| Create `locations` table (structured regions/cities) | Low | Medium |
| Formalize `saved_jobs` and `job_alerts` tables | Low | Medium |

### 3.3 Migration System
| Task | Effort | Priority |
|------|--------|----------|
| Create `src/Database/MigrationRunner.php` | Medium | High |
| Create `schema_migrations` tracking table | Low | High |
| Number and track all existing migrations | Low | Medium |
| Add `composer migrate` script | Low | Medium |

**Deliverable:** Clean model layer, new normalized tables, migration system.

---

## Sprint 4 — Applicant Features (Weeks 7–8)

**Goal:** Complete the job seeker experience.

### 4.1 Profile & Resume
| Task | Effort | Priority |
|------|--------|----------|
| Redesign applicant profile page (structured fields, skills picker) | Medium | High |
| Implement resume builder (generate PDF from profile data) | High | High |
| Add profile photo upload | Medium | Medium |

### 4.2 Job Search & Discovery
| Task | Effort | Priority |
|------|--------|----------|
| Build advanced search page (keyword, location, category, salary, type, experience) | High | High |
| Add search result sorting (date, relevance, salary) | Medium | High |
| Add server-side pagination | Medium | High |
| Add "Similar Jobs" recommendations on job details page | Medium | Medium |

### 4.3 Application Experience
| Task | Effort | Priority |
|------|--------|----------|
| Unify application flow (remove duplicate `apply.php`/`apply_job.php`) | Medium | High |
| Add application withdrawal reason | Low | Low |
| Add interview feedback viewing for applicants | Low | Medium |
| Add saved jobs with notes | Low | Low |

### 4.4 Notifications
| Task | Effort | Priority |
|------|--------|----------|
| Add per-user notification read tracking (for `target='all'`) | Medium | Medium |
| Add notification preferences page | Medium | Medium |

**Deliverable:** Complete job seeker portal with advanced search, resume builder, and unified application flow.

---

## Sprint 5 — Employer Features (Weeks 9–10)

**Goal:** Complete the employer experience.

### 5.1 Company Profile
| Task | Effort | Priority |
|------|--------|----------|
| Create company profile page (logo, description, website, industry) | High | High |
| Create public company listing page | Medium | High |
| Add company verification workflow (admin approves) | Medium | Medium |

### 5.2 Candidate Management
| Task | Effort | Priority |
|------|--------|----------|
| Build candidate search page (filter by skills, experience, education, location) | High | High |
| Add shortlist with notes | Medium | High |
| Add candidate comparison view | Medium | Medium |

### 5.3 Employer Messaging
| Task | Effort | Priority |
|------|--------|----------|
| Build employer inbox (message list, conversation view) | High | High |
| Implement message send/receive | High | High |
| Add read receipts | Low | Low |

### 5.4 Employer Analytics
| Task | Effort | Priority |
|------|--------|----------|
| Build employer analytics dashboard (application trends, job performance) | Medium | Medium |
| Add hiring funnel visualization | Medium | Medium |

**Deliverable:** Complete employer portal with company profiles, candidate search, messaging, and analytics.

---

## Sprint 6 — Admin & Notifications (Weeks 11–12)

**Goal:** Complete admin capabilities and notification system.

### 6.1 Admin Improvements
| Task | Effort | Priority |
|------|--------|----------|
| Build dedicated audit log viewer page | Medium | High |
| Add bulk user operations (import CSV, bulk status change) | Medium | High |
| Add system health monitoring dashboard | Medium | Medium |
| Add document verification UI for admin | Medium | Medium |
| Add role/permission management (beyond simple ENUM) | High | Medium |

### 6.2 Notification System
| Task | Effort | Priority |
|------|--------|----------|
| Build notification center (in-app real-time via polling/SSE) | High | High |
| Add email notification templates for all events | Medium | High |
| Add notification preferences per role | Medium | Medium |
| Add scheduled job expiry (cron-based) | Medium | Medium |

### 6.3 CMS
| Task | Effort | Priority |
|------|--------|----------|
| Add FAQ management (admin CRUD) | Low | Low |
| Add Terms & Privacy policy pages (dynamic content) | Low | Low |

**Deliverable:** Complete admin portal with audit logs, bulk operations, and a real notification system.

---

## Sprint 7 — Search, API & Performance (Weeks 13–14)

**Goal:** Build the API layer, optimize performance, improve search.

### 7.1 REST API
| Task | Effort | Priority |
|------|--------|----------|
| Create `src/Http/Request.php` and `src/Http/Response.php` | Medium | High |
| Create `src/Api/AuthController.php` (login, register, verify) | Medium | High |
| Create `src/Api/JobController.php` (CRUD + search) | Medium | High |
| Create `src/Api/ApplicationController.php` | Medium | High |
| Add API authentication (token-based) | Medium | High |
| Document API endpoints | Medium | High |

### 7.2 Search Improvements
| Task | Effort | Priority |
|------|--------|----------|
| Add full-text search (MySQL FULLTEXT index on jobs) | Medium | High |
| Add search result caching (file-based or Redis) | Medium | Medium |
| Add job auto-suggest/autocomplete | Low | Medium |

### 7.3 Performance
| Task | Effort | Priority |
|------|--------|----------|
| Add page-level caching (output buffering + file cache) | Medium | Medium |
| Optimize database queries (N+1 detection, eager loading) | Medium | Medium |
| Add lazy loading for images | Low | Low |
| Add SRI hashes for CDN resources | Low | Medium |
| Enable gzip compression via .htaccess | Low | Low |

**Deliverable:** REST API, advanced search, optimized performance.

---

## Sprint 8 — Accessibility, Testing & Deployment (Weeks 15–16)

**Goal:** Polish, test, and prepare for production.

### 8.1 Accessibility
| Task | Effort | Priority |
|------|--------|----------|
| Add skip-to-content link on all pages | Low | High |
| Add focus indicators (visible focus ring) | Low | High |
| Add ARIA labels to all interactive elements | Medium | High |
| Add keyboard navigation for dropdowns/modals | Medium | Medium |
| Color contrast audit (aim for WCAG AA) | Medium | Medium |
| Add responsive typography (clamp()) | Low | Low |

### 8.2 Testing
| Task | Effort | Priority |
|------|--------|----------|
| Write unit tests for all Services | High | High |
| Write integration tests for auth flow | High | High |
| Write integration tests for application flow | High | High |
| Write integration tests for job CRUD | Medium | High |
| Set up GitHub Actions CI pipeline | Medium | Medium |

### 8.3 Responsive & Mobile
| Task | Effort | Priority |
|------|--------|----------|
| Add tablet breakpoint (992px) | Low | Medium |
| Add mobile sidebar toggle (hamburger for dashboards) | Medium | Medium |
| Add touch-friendly sizing (44px min tap targets) | Low | Medium |
| Test all pages on mobile viewport | Medium | Medium |

### 8.4 Deployment
| Task | Effort | Priority |
|------|--------|----------|
| Create `.env.production.example` | Low | High |
| Create `deploy.sh` script (rsync/FTP) | Low | Medium |
| Set up error monitoring (Sentry or log aggregation) | Medium | Medium |
| Create deployment documentation | Medium | Medium |
| Security hardening checklist (HTTPS, HSTS, remove dev mode) | Medium | High |

**Deliverable:** Accessible, tested, documented, deployment-ready application.

---

## Dependency Graph

```
Sprint 1 (Foundation)
    ↓
Sprint 2 (Architecture)
    ↓
Sprint 3 (Models/DB) ← depends on Sprint 2 (services need models)
    ↓
Sprint 4 (Applicant) ──┐
Sprint 5 (Employer)  ───┤── can run in parallel after Sprint 3
Sprint 6 (Admin)     ───┘
    ↓
Sprint 7 (API/Performance) ← needs stable features from 4-6
    ↓
Sprint 8 (Polish/Deploy) ← final
```

---

## Effort Summary

| Sprint | Focus | Effort |
|--------|-------|--------|
| 1 | Foundation & Critical Fixes | Medium |
| 2 | Architecture Foundation | High |
| 3 | Database & Model Layer | High |
| 4 | Applicant Features | Medium |
| 5 | Employer Features | Medium |
| 6 | Admin & Notifications | Medium |
| 7 | API & Performance | High |
| 8 | Accessibility, Testing, Deploy | Medium |

**Total**: ~16 weeks sequential. With parallel sprints 4–6, realistic timeline is **12–14 weeks**.

---

## Quick Reference: File Structure Target

```
osta job portal/
├── public/                    # Web root (front controller)
│   ├── index.php              # Front controller
│   ├── .htaccess              # URL rewriting
│   └── assets/                # CSS, JS, images
├── src/                       # PSR-4 namespaced code
│   ├── Controllers/           # HTTP controllers
│   ├── Middleware/             # Request middleware
│   ├── Models/                # Database models
│   ├── Services/              # Business logic
│   ├── Database/              # Connection, migrations
│   ├── Http/                  # Request/Response objects
│   └── Api/                   # REST API controllers
├── routes/                    # Route definitions
│   └── web.php
├── config/                    # Configuration
│   ├── database.php
│   ├── email.php
│   └── .env
├── sql/                       # Database schema & seeds
│   ├── schema.sql
│   └── seed.sql
├── includes/                  # Legacy includes (gradually migrated)
├── admin/                     # Admin pages (legacy, to be migrated)
├── employer/                  # Employer pages (legacy, to be migrated)
├── applicant/                 # Applicant pages (legacy, to be migrated)
├── tests/                     # PHPUnit tests
├── docs/                      # Documentation
├── scripts/                   # Utility scripts
├── errors/                    # Error pages
├── uploads/                   # User uploads
├── vendor/                    # Composer dependencies
├── composer.json
└── .env
```
