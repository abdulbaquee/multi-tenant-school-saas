# Multi-Tenant School Administration Management SaaS Platform

> MCA Major Project 2026
> Built with Laravel 13, PHP 8.4, Blade, and Bootstrap 5

Version: 1.0
Status: Draft (Implementation Phase - Phase 2 completed)

---

## Project Overview

A cloud-based Multi-Tenant School Administration Management SaaS Platform designed to help educational institutions manage academic and administrative operations through a centralized and secure system.

The platform follows a Single Database Multi-Tenant Architecture where multiple schools share the same application infrastructure while maintaining complete data isolation through tenant-aware design.

---

## Technology Stack

> Status legend: **Installed** = present in the repository now; **Planned** = selected but not yet installed.

### Backend

* Laravel 13 (Installed)
* PHP 8.4

### Frontend

* Blade Templates (In Use)
* Bootstrap 5 (Installed)
* Bootstrap Icons (Installed)
* Chart.js (Planned)

### Database

* MySQL 8

### Authentication

* Laravel Breeze (Installed)

### Multi-Tenancy

* Native Laravel Multi-Tenancy (school_id + Global Scopes) — see `docs/TENANCY_DESIGN.md`

### Development Tools

* Composer
* Vite
* Git
* GitHub

---

## Architecture

### Multi-Tenant Strategy

Single Database + Shared Schema

Each school is isolated using:

school_id

Every business record will belong to a school tenant. Phase 3 will add automatic
query scoping through the documented `BelongsToTenant` trait, tenant context,
and Eloquent global scope. Phase 2 user-management access is protected by
policies and service-layer school checks until that infrastructure is active.
See `docs/TENANCY_DESIGN.md`.

---

## Core Modules

The canonical module list (15 modules) is defined in
`docs/MODULE_SPECIFICATIONS.md`:

1. Authentication
2. School Management
3. School Settings
4. User Management
5. Role & Permission Management
6. Academic Structure
7. Student Management
8. Attendance Management
9. Fee Management
10. Examination Management
11. Reporting
12. Dashboard & Analytics
13. Activity Logs
14. Audit Trail
15. Backup Management

---

## User Roles

### Super Admin

* Manage Schools
* Manage Tenants
* View Global Reports

### School Admin

* Manage School Operations
* Manage Students
* Manage Staff

### Teacher

* Attendance Entry
* Marks Entry

### Accountant

* Fee Collection
* Financial Reporting

---

## Documentation

| Document                      | Description                       |
| ----------------------------- | --------------------------------- |
| docs/PROJECT_OVERVIEW.md      | Project Scope & Objectives        |
| docs/TENANCY_DESIGN.md        | Multi-Tenancy (Single Source)     |
| docs/MODULE_SPECIFICATIONS.md | Module List (Single Source)       |
| docs/DEVELOPMENT_ROADMAP.md   | Development Plan (Single Source)  |
| docs/TESTING_STRATEGY.md      | Testing Approach                  |
| docs/DECISIONS_LOG.md         | Architectural Decisions           |
| docs/CHANGELOG.md             | Project History                   |

---

## Project Structure

app/
bootstrap/
config/
database/
docs/
diagrams/
reports/
screenshots/
resources/
routes/
storage/
tests/

---

## Development Roadmap

* [x] Project Planning
* [x] Repository Setup
* [x] Laravel 13 Installation
* [x] Authentication & User Management Foundation
* [ ] Multi-Tenant Foundation
* [ ] RBAC
* [ ] Student Management
* [ ] Attendance Management
* [ ] Fee Management
* [ ] Examination Management
* [ ] Reports & Analytics
* [ ] Testing
* [ ] Deployment

---

## Installation

```bash
git clone <repository-url>

cd multi-tenant-school-saas

composer install

cp .env.example .env

php artisan key:generate

php artisan migrate

php artisan serve
```

---

## MCA Project Information

**Project Title**

Multi-Tenant School Administration Management SaaS Platform

**Program**

Master of Computer Applications (MCA)

**University**

Chandigarh University

**Project Type**

Major Project

**Domain**

Education Technology (EdTech)

**Architecture**

Multi-Tenant SaaS

---

## Future Enhancements

* Parent Portal
* Student Portal
* Mobile Application
* Online Payments
* SMS Notifications
* Email Notifications
* AI-Based Analytics
* Cloud Deployment

---

## Author

Mohammed Abdul Baquee

Master of Computer Applications (MCA)

Chandigarh University

2026
