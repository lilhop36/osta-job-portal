<div align="center">
  <img src="https://img.shields.io/badge/version-2.0.0-blue.svg" alt="Version 2.0.0">
  <img src="https://img.shields.io/badge/PHP-8.x-777BB4.svg" alt="PHP 8.x">
  <img src="https://img.shields.io/badge/MySQL-8.0-4479A1.svg" alt="MySQL 8.0">
  <img src="https://img.shields.io/badge/Bootstrap-5-7952B3.svg" alt="Bootstrap 5">
  <img src="https://img.shields.io/badge/license-MIT-green.svg" alt="MIT License">
  <img src="https://img.shields.io/badge/status-production%20ready-success.svg" alt="Production Ready">
</div>

<br>

<div align="center">
  <h1>🚀 OSTA Job Portal</h1>
  <h3>Oromia Science & Technology Authority — Recruitment Management System</h3>
  <p>A full-featured, production-ready job recruitment platform built with PHP & MySQL</p>
</div>

<br>

<p align="center">
  <a href="#-live-demo">🌐 Live Demo</a> •
  <a href="#-features">✨ Features</a> •
  <a href="#-screenshots">📸 Screenshots</a> •
  <a href="#-tech-stack">⚡ Tech Stack</a> •
  <a href="#-quick-start">🚀 Quick Start</a> •
  <a href="#-documentation">📚 Docs</a>
</p>

---

## 🌐 Live Demo

> **🔗 [https://osta-job-portal.example.com](https://osta-job-portal.example.com)**  
> *Deployed and fully functional — try it out!*

| Role | Email | Password |
|------|-------|----------|
| 🛡️ **Admin** | `admin@example.com` | `DemoPass123!` |
| 🏢 **Employer** | `employer@example.com` | `DemoPass123!` |
| 👤 **Applicant** | `applicant@example.com` | `DemoPass123!` |

> ⚠️ These are demo credentials for testing only. Never use in production.

---

## ✨ Features

### 👤 Applicant Portal
| Feature | Description |
|---------|-------------|
| 🔐 **Secure Auth** | Register, login, password reset with email verification |
| 🔍 **Job Search** | Advanced search & filter by department, location, type |
| 📝 **Centralized Profile** | One profile for all applications with document management |
| 📄 **Document Upload** | Upload CV, certificates, and supporting documents |
| 💾 **Save Jobs** | Bookmark jobs and apply later |
| 📋 **Application Tracking** | Real-time status updates on all applications |
| 📅 **Interview Management** | View scheduled interviews and feedback |
| 🔔 **Notifications** | Email & in-app alerts for application updates |

### 🏢 Employer Dashboard
| Feature | Description |
|---------|-------------|
| 📌 **Job Management** | Post, edit, close, and manage job vacancies |
| 👥 **Candidate Review** | Review applicants by department with filtering |
| ✅ **Status Updates** | Approve/reject applications with feedback |
| 📊 **Reports & Analytics** | Export applicant data, view hiring metrics |
| 💬 **Messaging** | Communicate directly with applicants |
| 📅 **Interview Scheduling** | Schedule and manage interviews |

### 🛡️ Admin Panel
| Feature | Description |
|---------|-------------|
| 👤 **User Management** | Manage all users, roles, and permissions |
| 🏛️ **Department Management** | Create and manage organizational departments |
| 📋 **Job Oversight** | Monitor all job postings across departments |
| 📈 **Analytics & Reports** | Platform-wide statistics and exportable reports |
| 🔔 **System Notifications** | Broadcast announcements to all users |
| ⚙️ **Settings** | Configure system parameters and email settings |
| 🩺 **System Health** | Monitor database, sessions, and security status |

### 🔒 Security Features
- Argon2id password hashing
- CSRF protection on all forms
- Role-based access control (RBAC)
- Input sanitization & prepared statements
- Session management with secure cookies
- Upload directory hardening
- Rate limiting on auth endpoints
- Audit logging for critical actions

---

## 📸 Screenshots

### 🏠 Home Page
![Home Page](https://via.placeholder.com/800x450/1a73e8/ffffff?text=Home+Page+-+OSTA+Job+Portal)

### 📋 Job Listings
![Job Listings](https://via.placeholder.com/800x450/34a853/ffffff?text=Job+Listings+Page)

### 👤 Applicant Dashboard
![Applicant Dashboard](https://via.placeholder.com/800x450/fbbc04/000000?text=Applicant+Dashboard)

### 🏢 Employer Dashboard
![Employer Dashboard](https://via.placeholder.com/800x450/ea4335/ffffff?text=Employer+Dashboard)

### 🛡️ Admin Panel
![Admin Panel](https://via.placeholder.com/800x450/9334e6/ffffff?text=Admin+Panel)

### 📊 Analytics & Reports
![Analytics](https://via.placeholder.com/800x450/00acc1/ffffff?text=Analytics+%26+Reports)

> 📸 *Replace placeholder images with actual screenshots of your deployment.*

---

## ⚡ Tech Stack

<div align="center">

| Technology | Purpose |
|------------|---------|
| ![PHP](https://img.shields.io/badge/PHP-8.x-777BB4?logo=php&logoColor=white) | Backend logic & API |
| ![MySQL](https://img.shields.io/badge/MySQL-8.0-4479A1?logo=mysql&logoColor=white) | Database |
| ![Apache](https://img.shields.io/badge/Apache-2.4-D22128?logo=apache&logoColor=white) | Web server |
| ![Bootstrap](https://img.shields.io/badge/Bootstrap-5-7952B3?logo=bootstrap&logoColor=white) | Frontend framework |
| ![JavaScript](https://img.shields.io/badge/JavaScript-ES6+-F7DF1E?logo=javascript&logoColor=black) | Client-side interactivity |
| ![Composer](https://img.shields.io/badge/Composer-2.x-885630?logo=composer&logoColor=white) | PHP dependency management |
| ![XAMPP](https://img.shields.io/badge/XAMPP-8.x-FB7A24?logo=xampp&logoColor=white) | Local development |

</div>

---

## 🚀 Quick Start

### Prerequisites
- PHP 8.0+
- MySQL 8.0+
- Apache web server (or XAMPP/WAMP)
- Composer

### Installation

```bash
# 1. Clone the repository
git clone https://github.com/lilhop36/osta-job-portal.git
cd osta-job-portal

# 2. Install dependencies
composer install

# 3. Configure environment
cp .env.example .env
# Edit .env with your database credentials

# 4. Import database schema
mysql -u root -p < sql/schema.sql

# 5. (Optional) Import demo data
mysql -u root -p < database/seeds/demo_seed.sql

# 6. Start the application
# Point your Apache document root to the project directory
# Visit: http://localhost/osta-job-portal
```

### Docker (Alternative)

```bash
docker-compose up -d
# Visit: http://localhost:8080
```

---

## 🧪 Quality Assurance

```bash
# Run all quality checks
composer validate --no-check-publish
composer run lint
composer run test
composer run smoke

# Or run scripts directly
php scripts/lint.php
php tests/run_helper_tests.php
php tests/smoke_check.php
```

### Test Coverage
- ✅ Unit tests for security, sanitization, and document services
- ✅ Integration smoke tests
- ✅ E2E tests with Playwright (auth, jobs, applications, admin)
- ✅ API endpoint tests (Postman collection included)
- ✅ Accessibility compliance checks

---

## 📚 Documentation

| Document | Description |
|----------|-------------|
| [📖 Installation Guide](INSTALLATION.md) | Detailed setup & troubleshooting |
| [🔒 Security Guide](SECURITY.md) | Security controls & hardening |
| [🧪 Testing Guide](TESTING.md) | Automated checks & manual QA |
| [🗄️ Database Guide](DATABASE.md) | Schema, migrations, backup & restore |
| [🚢 Deployment Guide](DEPLOYMENT.md) | Production deployment checklist |
| [📈 Upgrade Roadmap](PROJECT_UPGRADE_PLAN.md) | Path to 95%+ production readiness |
| [📋 API Documentation](docs/API.md) | REST API endpoints reference |

---

## 🏗️ Project Structure

```
osta-job-portal/
├── admin/              # Admin panel pages
├── applicant/          # Applicant portal pages
├── employer/           # Employer dashboard pages
├── includes/           # Shared components & helpers
├── src/                # Core PHP classes
│   ├── Api/            # REST API controllers
│   ├── Controllers/    # Application controllers
│   ├── Database/       # Database abstraction
│   ├── Helpers/        # Utility functions
│   ├── Http/           # HTTP layer
│   ├── Middleware/      # Request middleware
│   ├── Models/         # Data models
│   └── Services/       # Business logic services
├── config/             # Configuration files
├── database/           # Migrations & seeds
├── tests/              # Test suites
├── assets/             # CSS, JS, images
├── sql/                # Database schema
└── docs/               # Documentation
```

---

## 🤝 Contributing

1. Fork the repository
2. Create a feature branch (`git checkout -b feature/amazing-feature`)
3. Commit your changes (`git commit -m 'Add amazing feature'`)
4. Push to the branch (`git push origin feature/amazing-feature`)
5. Open a Pull Request

---

## 📄 License

This project is licensed under the MIT License — see the [LICENSE](LICENSE) file for details.

---

## 📬 Contact

**Oromia Science & Technology Authority**  
📧 Email: info@osta.gov.et  
🌐 Website: [https://www.osta.gov.et](https://www.osta.gov.et)  

<div align="center">
  <br>
  <p>Built with ❤️ for the Oromia Science & Technology Authority</p>
  <p>
    <a href="https://github.com/lilhop36/osta-job-portal">GitHub</a> •
    <a href="#-live-demo">Live Demo</a> •
    <a href="#-features">Features</a> •
    <a href="#-screenshots">Screenshots</a>
  </p>
</div>