# PROJECT OVERVIEW

Version: 1.0
Status: Draft

Project Title:
Multi-Tenant School Administration Management SaaS Platform

Program:
Master of Computer Applications (MCA)

University:
Chandigarh University

Project Type:
Major Project

Development Year:
2026

---

# 1. INTRODUCTION

The Multi-Tenant School Administration Management SaaS Platform is a cloud-based web application designed to streamline and automate the administrative and academic operations of educational institutions.

The platform enables multiple schools to operate independently within a shared Software-as-a-Service (SaaS) infrastructure while ensuring complete tenant-level data isolation and security.

The system centralizes school administration activities including student management, attendance tracking, fee collection, examination processing, reporting, and user management through a modern web interface.

---

# 2. PROBLEM STATEMENT

Many schools continue to rely on spreadsheets, paper-based records, and disconnected software systems for managing academic and administrative activities.

These approaches often result in:

* Data duplication
* Human errors
* Inefficient reporting
* Limited visibility into operations
* Poor record management
* Increased administrative workload

There is a need for a centralized, secure, and scalable platform that can simplify school management processes while supporting multiple institutions within a single system.

---

# 3. PROJECT OBJECTIVES

The primary objectives of the project are:

* Develop a Multi-Tenant SaaS platform for school management.
* Provide secure tenant-level data isolation.
* Implement role-based access control.
* Manage students and academic records.
* Track attendance efficiently.
* Manage fee structures and fee collection.
* Process examinations and academic results.
* Generate operational and analytical reports.
* Demonstrate modern software engineering practices.

---

# 4. PROJECT SCOPE

The project includes the following functional areas:

### Platform Administration

* School Management
* User Management
* Fixed Role & Constrained Permission Management
* School Settings

### Academic Structure

* Academic Years
* Academic Terms
* Minimal Teacher Profiles for academic assignments
* Classes
* Sections
* Subjects

### Student Management

* Student Registration
* Student Profiles
* Student Enrollment

### Attendance Management

* Daily Attendance
* Attendance Reporting

### Fee Management

* Fee Structures
* Fee Collection
* Payment Tracking

### Examination Management

* Examination Setup
* Marks Entry
* Grade Calculation
* Report Card Generation

### Reporting & Analytics

* Student Reports
* Attendance Reports
* Fee Reports
* Examination Reports

### System Monitoring

* Activity Logs
* Audit Logs
* Backup Logs

The MVP includes no separate platform-wide or application-wide settings module. School-level configuration is handled only through School Settings.

---

# 5. TECHNOLOGY STACK

## Backend

* Laravel 13
* PHP 8.4

## Frontend

* Blade Templates
* Bootstrap 5
* Bootstrap Icons
* Chart.js (Planned)

## Database

* MySQL 8

## Authentication

* Laravel Breeze (Installed)

## Multi-Tenancy

* Native Laravel Multi-Tenancy (school_id + Global Scopes) — see TENANCY_DESIGN.md

## Version Control

* Git
* GitHub

---

# 6. SYSTEM ARCHITECTURE

Architecture Style:

Layered Monolithic Architecture

Deployment Model:

* Single Application
* Single Database
* Shared Schema
* Multi-Tenant SaaS

Tenant Identifier:

school_id

The system uses a shared database approach where all schools share the same
infrastructure while maintaining complete data isolation through native Laravel
multi-tenancy (an Eloquent global scope via a BelongsToTenant trait, set from the
authenticated user's `school_id`). No external tenancy package is used. See
`TENANCY_DESIGN.md`.

The canonical functional module list (15 modules) is defined in
`MODULE_SPECIFICATIONS.md`. The scope groupings above are organized by functional
area and map to those modules.

---

# 7. USER ROLES

### Super Admin

Responsible for school management, platform monitoring, audit visibility, reports, and backup management.

### School Admin

Responsible for managing school settings, users, academic structure, students, attendance, fees, examinations, school reports, and school-scoped logs.

### Teacher

Responsible for assigned class attendance, marks entry, report cards, and assigned class reports.

### Accountant

Responsible for fee collection, payment tracking, and financial reporting.

Role permissions and menu visibility follow the canonical matrix in
`MODULE_SPECIFICATIONS.md`. The MVP uses four fixed roles, a fixed permission
catalog, constrained school-role mappings, and no custom roles or per-user
permission overrides.

---

# 8. KEY FEATURES

* Multi-Tenant Architecture
* Secure Authentication
* Role-Based Access Control (RBAC)
* Student Information Management
* Attendance Tracking
* Fee Management
* Examination Processing
* Reporting & Analytics
* Activity Logging
* Audit Trail
* Backup Management
* Responsive User Interface

---

# 9. EXPECTED BENEFITS

The platform provides the following benefits:

### For Schools

* Centralized management
* Improved record keeping
* Reduced administrative effort
* Faster reporting

### For Staff

* Improved productivity
* Simplified workflows
* Better data accessibility

### For Administrators

* Operational visibility
* Better decision making
* Improved accountability

---

# 10. PROJECT DELIVERABLES

The project will deliver:

* Source Code Repository
* Database Schema
* System Documentation
* ER Diagram
* Architecture Documentation
* User Interface Screenshots
* Testing Documentation
* MCA Project Report
* Presentation Slides

---

# 11. SUCCESS CRITERIA

The project will be considered successful when:

✓ Multi-Tenant Architecture Is Implemented

✓ Tenant Isolation Is Enforced

✓ Role-Based Access Control Is Operational

✓ Core Modules Are Functional

✓ Reports Are Generated Successfully

✓ Documentation Is Complete

✓ Testing Is Completed

✓ MCA Report Is Submitted

✓ Project Can Be Demonstrated Successfully

---

# 12. FUTURE ENHANCEMENTS

Potential future improvements include:

* Parent Portal
* Student Portal
* Mobile Applications
* Online Fee Payments
* SMS Notifications
* Email Notifications
* Advanced Analytics
* AI-Based Insights

---

# 13. CONCLUSION

The Multi-Tenant School Administration Management SaaS Platform aims to provide a secure, scalable, and maintainable solution for educational institutions. The project demonstrates modern web application development practices, multi-tenant SaaS architecture, database design, reporting systems, security implementation, and software engineering principles while fulfilling the academic requirements of the MCA major project.
