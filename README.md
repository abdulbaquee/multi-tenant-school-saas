# Multi-Tenant School Administration Management SaaS Platform

> MCA Major Project 2026
> Built with Laravel 13, PHP 8.4, MySQL 8, Bootstrap 5

---

## Project Overview

A cloud-based Multi-Tenant School Administration Management SaaS Platform designed to help educational institutions manage academic and administrative operations through a centralized and secure system.

The platform follows a Single Database Multi-Tenant Architecture where multiple schools share the same application infrastructure while maintaining complete data isolation through tenant-aware design.

---

## Technology Stack

### Backend

* Laravel 13
* PHP 8.4

### Frontend

* Blade Templates
* Bootstrap 5
* Bootstrap Icons
* Chart.js

### Database

* MySQL 8

### Authentication

* Laravel Breeze

### Multi-Tenancy

* Stancl Tenancy

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

Every business record belongs to a school tenant and all queries are automatically scoped to the active tenant.

---

## Core Modules

### Platform Administration

* School Management
* Tenant Management
* User Management
* Role Management
* Activity Monitoring

### Student Management

* Student Registration
* Student Profiles
* Class Assignment
* Section Assignment

### Attendance Management

* Daily Attendance
* Attendance Tracking
* Attendance Reports

### Fee Management

* Fee Structures
* Fee Collection
* Receipt Generation
* Financial Reports

### Examination Management

* Subject Management
* Marks Entry
* Grade Calculation
* Report Cards

### Reporting & Analytics

* Student Reports
* Attendance Reports
* Fee Reports
* Examination Reports
* Dashboard Analytics

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

| Document                    | Description                |
| --------------------------- | -------------------------- |
| docs/PROJECT_OVERVIEW.md    | Project Scope & Objectives |
| docs/DEVELOPMENT_ROADMAP.md | Development Plan           |
| docs/TESTING_STRATEGY.md    | Testing Approach           |
| docs/DECISIONS_LOG.md       | Architectural Decisions    |
| docs/CHANGELOG.md           | Project History            |

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
* [ ] Authentication
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
