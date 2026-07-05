# Software Requirements Specification (SRS)
## OSTA Job Portal System

**Document Version:** 2.0  
**Date:** August 24, 2025  
**Project:** OSTA Job Portal  
**Organization:** OSTA (Occupational Safety and Training Authority)
**Last Updated:** August 2025  

---

## Table of Contents

1. [Introduction](#1-introduction)
2. [Overall Description](#2-overall-description)
3. [System Features](#3-system-features)
4. [External Interface Requirements](#4-external-interface-requirements)
5. [System Features](#5-system-features)
6. [Non-Functional Requirements](#6-non-functional-requirements)
7. [Other Requirements](#7-other-requirements)

---

## 1. Introduction

### 1.1 Purpose
This Software Requirements Specification (SRS) describes the functional and non-functional requirements for the OSTA Job Portal System. The document is intended for developers, system administrators, project managers, and stakeholders involved in the development and maintenance of the system.

### 1.2 Document Conventions
- **Shall/Must**: Mandatory requirements
- **Should**: Highly desirable requirements
- **May/Could**: Optional requirements
- **User**: Generic term for any system user
- **Applicant**: Job seekers using the system
- **Employer**: Organizations posting job opportunities
- **Admin**: System administrators

### 1.3 Intended Audience and Reading Suggestions
This document is primarily intended for:
- Software developers and architects
- System administrators
- Project managers
- Quality assurance teams
- End users (Applicants, Employers, Administrators)

### 1.4 Product Scope
The OSTA Job Portal is a web-based employment management system that facilitates job posting, application management, and recruitment processes. The system serves three primary user types: job applicants, employers, and system administrators.

### 1.5 References
- PHP 7.4+ Documentation
- MySQL 8.0+ Documentation
- Bootstrap 5.1.3 Framework
- OWASP Security Guidelines

---

## 2. Overall Description

### 2.1 Product Perspective
The OSTA Job Portal is a comprehensive web application built using:
- **Backend**: PHP 7.4+ with MySQL 8.0+ database
- **Frontend**: HTML5, CSS3, JavaScript, Bootstrap 5.1.3
- **Architecture**: MVC-inspired structure with role-based access control
- **Security**: Enterprise-grade security with CSRF protection, input sanitization, session management, and audit logging
- **Key Features**: Centralized application system, document management, automated eligibility screening, and comprehensive interview management

### 2.2 Product Functions
The system provides the following major functions:

#### For Applicants:
- User registration and profile management
- Job search and filtering
- Job application submission
- Application status tracking
- Resume and certificate upload
- Saved jobs management
- Email notifications

#### For Employers:
- Employer registration and profile management
- Job posting and management
- Application review and management
- Applicant communication
- Application status updates
- Export functionality for applications

#### For Administrators:
- User management (applicants and employers)
- Department management
- Job approval and management
- System reports and analytics
- Notification management
- System settings configuration
- Security monitoring

### 2.3 User Classes and Characteristics

#### 2.3.1 Applicants (Job Seekers)
- **Technical Expertise**: Basic to intermediate computer skills
- **Primary Goals**: Find and apply for suitable job opportunities
- **Usage Frequency**: Regular during job search periods
- **Key Features**: Job search, application submission, status tracking

#### 2.3.2 Employers (Recruiters/HR Personnel)
- **Technical Expertise**: Intermediate computer skills
- **Primary Goals**: Post jobs, manage applications, find suitable candidates
- **Usage Frequency**: Daily during recruitment periods
- **Key Features**: Job management, application review, candidate communication

#### 2.3.3 System Administrators
- **Technical Expertise**: Advanced technical skills
- **Primary Goals**: System maintenance, user management, security oversight
- **Usage Frequency**: Daily for system monitoring and maintenance
- **Key Features**: User management, system configuration, reporting

### 2.4 Operating Environment
- **Web Server**: Apache/Nginx
- **Database**: MySQL 8.0+
- **PHP Version**: 7.4+
- **Client Browsers**: Chrome, Firefox, Safari, Edge (latest versions)
- **Operating System**: Cross-platform (Windows, Linux, macOS)

### 2.5 Design and Implementation Constraints
- Must comply with web accessibility standards (WCAG 2.1)
- Must implement comprehensive security measures
- Database must support concurrent users
- System must be responsive for mobile devices
- Must support file uploads up to 5MB per file

### 2.6 Assumptions and Dependencies
- Users have reliable internet connectivity
- Email service (SMTP) is available for notifications
- Web server has PHP and MySQL support
- File system has adequate storage for uploads

---

## 3. System Features

### 3.1 User Authentication and Authorization

#### 3.1.1 Description
Secure user authentication system with role-based access control.

#### 3.1.2 Functional Requirements
- **REQ-AUTH-001**: System shall provide user registration for applicants and employers
- **REQ-AUTH-002**: System shall authenticate users using email and password
- **REQ-AUTH-003**: System shall implement role-based access control (applicant, employer, admin)
- **REQ-AUTH-004**: System shall provide secure password hashing using Argon2ID
- **REQ-AUTH-005**: System shall implement session management with timeout
- **REQ-AUTH-006**: System shall provide logout functionality
- **REQ-AUTH-007**: System shall implement CSRF protection on all forms

### 3.2 Job Management

#### 3.2.1 Description
Comprehensive job posting and management system for employers.

#### 3.2.2 Functional Requirements
- **REQ-JOB-001**: Employers shall be able to create job postings
- **REQ-JOB-002**: Job postings shall include title, description, requirements, location, salary, deadline
- **REQ-JOB-003**: System shall support job categories and employment types
- **REQ-JOB-004**: Employers shall be able to edit and delete their job postings
- **REQ-JOB-005**: System shall provide job search and filtering capabilities
- **REQ-JOB-006**: Jobs shall have approval workflow (pending, approved, expired)
- **REQ-JOB-007**: System shall automatically expire jobs after deadline

### 3.3 Application Management

#### 3.3.1 Description
Complete application lifecycle management from submission to final decision with centralized application system and automated screening.

#### 3.3.2 Functional Requirements
- **REQ-APP-001**: Applicants shall be able to submit a single centralized application for multiple departments
- **REQ-APP-002**: System shall support document uploads including resumes, certificates, and transcripts
- **REQ-APP-003**: System shall track application status (Draft → Submitted → Under Review → Shortlisted → Interview/Exam → Accepted/Rejected)
- **REQ-APP-004**: Automated eligibility screening with real-time qualification validation
- **REQ-APP-005**: Document verification workflow for certificates and credentials
- **REQ-APP-006**: Comprehensive application status tracking dashboard
- **REQ-APP-007**: Prevention of duplicate applications with smart detection
- **REQ-APP-008**: Merit-based scoring system for applications
- **REQ-APP-009**: Application history with audit trail
- **REQ-APP-010**: Bulk application status updates for employers

### 3.4 Profile Management

#### 3.4.1 Description
User profile management for all user types.

#### 3.4.2 Functional Requirements
- **REQ-PROF-001**: Users shall be able to update their profile information
- **REQ-PROF-002**: Applicants shall be able to upload and manage resumes
- **REQ-PROF-003**: Applicants shall be able to upload certificates
- **REQ-PROF-004**: System shall validate uploaded file types and sizes
- **REQ-PROF-005**: Employers shall be able to update company information

### 3.5 Notification System

#### 3.5.1 Description
Email notification system for important events and updates.

#### 3.5.2 Functional Requirements
- **REQ-NOT-001**: System shall send email notifications for application status changes
- **REQ-NOT-002**: System shall send notifications for new job postings (optional)
- **REQ-NOT-003**: System shall support HTML email templates
- **REQ-NOT-004**: System shall log email sending status
- **REQ-NOT-005**: System shall provide email configuration interface

### 3.6 Administrative Functions

#### 3.6.1 Description
Administrative interface for system management and oversight.

#### 3.6.2 Functional Requirements
- **REQ-ADM-001**: Admins shall be able to manage user accounts
- **REQ-ADM-002**: Admins shall be able to manage departments
- **REQ-ADM-003**: Admins shall be able to approve/reject job postings
- **REQ-ADM-004**: System shall provide reporting and analytics
- **REQ-ADM-005**: Admins shall be able to configure system settings
- **REQ-ADM-006**: System shall provide security monitoring tools

---

## 4. External Interface Requirements

### 4.1 User Interfaces

#### 4.1.1 General UI Requirements
- **REQ-UI-001**: Interface shall be responsive and mobile-friendly
- **REQ-UI-002**: Interface shall use Bootstrap 5 framework for consistency
- **REQ-UI-003**: Interface shall provide clear navigation and breadcrumbs
- **REQ-UI-004**: Interface shall display appropriate error and success messages
- **REQ-UI-005**: Interface shall be accessible (WCAG 2.1 compliant)

#### 4.1.2 Specific Interface Requirements
- **REQ-UI-006**: Login/Registration forms with validation
- **REQ-UI-007**: Dashboard interfaces for each user role
- **REQ-UI-008**: Job listing and detail pages
- **REQ-UI-009**: Application forms and management interfaces
- **REQ-UI-010**: Administrative control panels

### 4.2 Hardware Interfaces
- **REQ-HW-001**: System shall run on standard web server hardware
- **REQ-HW-002**: System shall support concurrent user access
- **REQ-HW-003**: System shall handle file uploads and storage

### 4.3 Software Interfaces

#### 4.3.1 Database Interface
- **REQ-SW-001**: System shall interface with MySQL database
- **REQ-SW-002**: System shall use PDO for database connections
- **REQ-SW-003**: System shall implement prepared statements for security

#### 4.3.2 Email Interface
- **REQ-SW-004**: System shall interface with SMTP servers
- **REQ-SW-005**: System shall support PHPMailer library
- **REQ-SW-006**: System shall fallback to PHP mail() function if needed

### 4.4 Communication Interfaces
- **REQ-COM-001**: System shall communicate via HTTP/HTTPS protocols
- **REQ-COM-002**: System shall support SMTP for email communication
- **REQ-COM-003**: System shall implement secure communication channels

---

## 5. System Features

### 5.1 Security Features

#### 5.1.1 Authentication Security
- **REQ-SEC-001**: System shall implement secure password hashing
- **REQ-SEC-002**: System shall enforce strong password policies
- **REQ-SEC-003**: System shall implement session security measures
- **REQ-SEC-004**: System shall provide rate limiting for login attempts

#### 5.1.2 Data Security
- **REQ-SEC-005**: System shall implement CSRF protection
- **REQ-SEC-006**: System shall sanitize all user inputs
- **REQ-SEC-007**: System shall use prepared statements for database queries
- **REQ-SEC-008**: System shall implement proper file upload validation

#### 5.1.3 Access Control
- **REQ-SEC-009**: System shall implement role-based access control
- **REQ-SEC-010**: System shall validate user permissions for each action
- **REQ-SEC-011**: System shall log security events

### 5.2 Data Management Features

#### 5.2.1 Data Storage
- **REQ-DATA-001**: System shall store user data securely in database
- **REQ-DATA-002**: System shall handle file uploads in designated directories
- **REQ-DATA-003**: System shall implement data backup capabilities

#### 5.2.2 Data Validation
- **REQ-DATA-004**: System shall validate all input data
- **REQ-DATA-005**: System shall enforce data integrity constraints
- **REQ-DATA-006**: System shall handle data errors gracefully

---

## 6. Non-Functional Requirements

### 6.1 Performance Requirements
- **REQ-PERF-001**: System shall support at least 100 concurrent users
- **REQ-PERF-002**: Page load times shall not exceed 3 seconds
- **REQ-PERF-003**: Database queries shall be optimized for performance
- **REQ-PERF-004**: File uploads shall complete within reasonable time limits

### 6.2 Security Requirements
- **REQ-SEC-012**: System shall implement HTTPS for all communications
- **REQ-SEC-013**: System shall protect against common web vulnerabilities (OWASP Top 10)
- **REQ-SEC-014**: System shall implement proper error handling without information disclosure
- **REQ-SEC-015**: System shall maintain security logs

### 6.3 Availability Requirements
- **REQ-AVAIL-001**: System shall have 99% uptime availability
- **REQ-AVAIL-002**: System shall handle graceful degradation during high load
- **REQ-AVAIL-003**: System shall provide appropriate error messages during downtime

### 6.4 Maintainability Requirements
- **REQ-MAINT-001**: Code shall be well-documented and commented
- **REQ-MAINT-002**: System shall use modular architecture for easy maintenance
- **REQ-MAINT-003**: System shall provide logging for debugging purposes
- **REQ-MAINT-004**: System shall support easy configuration updates

### 6.5 Usability Requirements
- **REQ-USE-001**: System shall be intuitive and easy to use
- **REQ-USE-002**: System shall provide helpful error messages
- **REQ-USE-003**: System shall support multiple browsers
- **REQ-USE-004**: System shall be responsive on mobile devices

### 6.6 Scalability Requirements
- **REQ-SCALE-001**: System architecture shall support horizontal scaling
- **REQ-SCALE-002**: Database design shall support growth in data volume
- **REQ-SCALE-003**: System shall handle increased user load gracefully

---

## 7. Other Requirements

### 7.1 Legal Requirements
- **REQ-LEGAL-001**: System shall comply with data protection regulations
- **REQ-LEGAL-002**: System shall provide privacy policy and terms of service
- **REQ-LEGAL-003**: System shall handle user data according to privacy laws

### 7.2 Standards Compliance
- **REQ-STD-001**: System shall follow web standards (HTML5, CSS3)
- **REQ-STD-002**: System shall implement accessibility standards (WCAG 2.1)
- **REQ-STD-003**: System shall follow security best practices

### 7.3 Environmental Requirements
- **REQ-ENV-001**: System shall run in LAMP/WAMP environment
- **REQ-ENV-002**: System shall be compatible with standard hosting environments
- **REQ-ENV-003**: System shall support both development and production environments

---

## 8. Database Schema Overview

### 8.1 Core Tables
- **users**: User accounts and profiles with enhanced security attributes
- **departments**: Organization departments with hierarchy support
- **jobs**: Job postings with detailed metadata
- **applications**: Centralized job applications with status tracking
- **application_history**: Complete audit trail of application status changes
- **documents**: Centralized document management with versioning
- **exams**: Exam scheduling and management
- **interviews**: Interview scheduling and feedback
- **notifications**: System notifications with read receipts
- **audit_log**: Comprehensive security and activity logging
- **settings**: System-wide configuration
- **merit_scores**: Application scoring and ranking
- **eligibility_rules**: Automated screening criteria

### 8.2 File Storage
- **uploads/resumes/**: Applicant resume files
- **uploads/certificates/**: Applicant certificate files
- **uploads/job_attachments/**: Job posting attachments

---

## 9. Security Implementation

### 9.1 Current Security Features
- **Authentication Security**
  - Secure session management with validation
  - Argon2ID password hashing
  - Account lockout after failed attempts
  - Session timeout and regeneration

- **Data Protection**
  - CSRF protection on all forms
  - Input validation and sanitization
  - Output encoding
  - Secure headers (CSP, XSS Protection, etc.)

- **Access Control**
  - Role-based access control (RBAC)
  - Function-level authorization
  - Data-level access restrictions
  - Secure file handling with proper permissions

- **Audit & Monitoring**
  - Comprehensive audit logging
  - Security event monitoring
  - Suspicious activity detection
  - Regular security reviews

### 9.2 File Security
- .htaccess protection for upload directories
- File type validation
- File size restrictions
- Prevention of PHP execution in upload directories

---

## 10. System Architecture

### 10.1 Directory Structure
```
osta_job_portal/
├── admin/              # Administrative interface
├── applicant/          # Applicant interface
├── employer/           # Employer interface
├── config/             # Configuration files
├── includes/           # Shared PHP includes
├── assets/             # CSS, JS, images
├── uploads/            # File uploads
├── database/           # Database scripts
└── documentation/      # Project documentation
```

### 10.2 Technology Stack
- **Frontend**: HTML5, CSS3, JavaScript, Bootstrap 5.1.3
- **Backend**: PHP 7.4+
- **Database**: MySQL 8.0+
- **Security**: Custom security implementation
- **Email**: PHPMailer with SMTP fallback

---

## 11. Conclusion

The OSTA Job Portal system is a comprehensive employment management platform that successfully implements all required functionality with robust security measures. The system serves three distinct user roles with appropriate interfaces and features for each. The architecture is scalable, maintainable, and follows modern web development best practices.

The system has been thoroughly tested and is ready for production deployment with proper email configuration and server setup.

---

**Document Control:**
- **Author**: System Analysis Team
- **Reviewed By**: Project Manager
- **Approved By**: Technical Lead
- **Last Updated**: July 26, 2025
- **Version**: 1.0
