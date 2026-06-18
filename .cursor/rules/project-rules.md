# PROJECT RULES

Version: 1.0
Status: Draft

Project:
Multi-Tenant School Administration Management SaaS Platform

Program:
Master of Computer Applications (MCA)

Repository:
multi-tenant-school-saas

---

# PROJECT OBJECTIVE

Build a production-quality educational School Administration Management SaaS Platform using modern Laravel development practices.

The system must be:

* Secure
* Maintainable
* Scalable
* Easy to demonstrate
* Suitable for MCA evaluation

---

# TECHNOLOGY STACK

Backend

* Laravel 13
* PHP 8.4

Frontend (Planned)

* Blade Templates
* Bootstrap 5
* Bootstrap Icons
* Chart.js

Database

* MySQL 8

Authentication (Planned)

* Laravel Breeze

Multi-Tenancy

* Native Laravel Multi-Tenancy (school_id + Global Scopes) — see docs/TENANCY_DESIGN.md
* No external tenancy package (Stancl Tenancy is NOT used)

Version Control

* Git
* GitHub

---

# ARCHITECTURE RULES

Architecture Style:

Layered Monolithic Architecture

Required Layers:

Routes
→ Middleware
→ Controllers
→ Form Requests
→ Services
→ Models
→ Database

Business logic MUST NOT exist inside controllers.

Controllers must remain thin.

---

# DIRECTORY STRUCTURE

app/

├── Http/
│   ├── Controllers
│   ├── Middleware
│   └── Requests
│
├── Models
│
├── Services
│
├── Policies
│
├── Enums
│
└── Providers

---

# MULTI-TENANCY RULES

Most Important Rule.

Tenant Key:

school_id

Implementation:

Native Laravel Multi-Tenancy — a BelongsToTenant trait + Eloquent global scope +
TenantContext middleware. No external tenancy package. See docs/TENANCY_DESIGN.md.

Every business entity must belong to a school.

Examples:

* students
* attendances
* fee_payments
* exams
* subjects
* report_cards

Tenant scoping is applied automatically by the global scope (default-deny). Every
tenant-owned model MUST use the BelongsToTenant trait. Do not rely on manual
where('school_id', ...) filtering as the protection mechanism.

Super Admin (school_id = NULL) bypasses the scope only via authorized paths.

Never generate code that allows cross-tenant access.

---

# DATABASE RULES

Database:

MySQL 8

Rules:

* Use migrations
* Use foreign keys
* Use indexes
* Use timestamps
* Use soft deletes where appropriate

Naming Conventions:

Tables:
plural snake_case

Columns:
snake_case

Foreign Keys:
entity_id

Examples:

school_id
student_id
class_id
section_id

---

# MODEL RULES

Every model must:

* Use HasFactory
* Define relationships
* Define casts
* Define fillable attributes

Avoid:

* Business logic in models
* Massive model methods

Models represent data.

Services handle business logic.

---

# SERVICE LAYER RULES

Business logic belongs in Services.

Examples:

AttendanceService
FeeService
ExamService
ReportService
ActivityLogService
AuditService

Controllers should call services.

Services should not return views.

---

# REQUEST VALIDATION RULES

Use Form Requests.

Examples:

StoreStudentRequest
UpdateStudentRequest
StoreAttendanceRequest

Never validate directly inside controllers.

---

# AUTHENTICATION RULES

Authentication System:

Laravel Breeze

Requirements:

* Login
* Logout
* Password Reset
* Session Security

Never create custom authentication unless required.

Use Laravel defaults whenever possible.

---

# AUTHORIZATION RULES

Use:

* Policies
* Gates
* Middleware

Roles:

* Super Admin
* School Admin
* Teacher
* Accountant

Every protected action requires authorization.

---

# UI RULES

Framework:

Bootstrap 5

Requirements:

* Responsive Design
* Consistent Layout
* Accessible Forms
* Mobile Friendly

Every page should include:

* Breadcrumb
* Page Title
* Primary Action Button

Tables should support:

* Search
* Pagination
* Filters

---

# MODULE STRUCTURE

Core Modules:

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
14. Audit Logs
15. Backup Management

---

# SECURITY RULES

Always enforce:

* Authentication
* Authorization
* Tenant Isolation
* Input Validation
* Output Escaping
* CSRF Protection

Never:

* Trust User Input
* Trust Client-Side Validation
* Expose Sensitive Data

---

# ACTIVITY LOGGING RULES

Track:

* Login
* Logout
* Create
* Update
* Delete
* Attendance Entry
* Fee Collection
* Marks Entry
* Report Generation

Important business actions must be logged.

---

# AUDIT TRAIL RULES

Track:

* Old Values
* New Values
* User
* Timestamp
* IP Address

Audit records must be immutable.

---

# TESTING RULES

Every module must include:

* Feature Tests
* Authorization Tests
* Tenant Isolation Tests

Critical workflows require end-to-end testing.

---

# CODE QUALITY RULES

Follow:

* SOLID Principles
* DRY
* KISS
* Separation of Concerns

Avoid:

* Fat Controllers
* Duplicate Logic
* Hardcoded Values
* Large Methods

---

# DOCUMENTATION RULES

Whenever creating a new module:

Update if required:

* MODULE_SPECIFICATIONS.md
* DATABASE_DESIGN.md
* SCREEN_FLOW.md
* CHANGELOG.md

Major architectural changes must be documented.

---

# GIT COMMIT RULES

Use Conventional Commits.

Examples:

feat: add student management module

feat: implement attendance entry workflow

fix: resolve tenant isolation issue

docs: update database design documentation

refactor: move fee calculation to service layer

test: add attendance feature tests

---

# MCA PROJECT RULES

The project must demonstrate:

* Multi-Tenant Architecture
* RBAC
* Database Design
* Secure Development
* Reporting
* Testing
* Documentation

The codebase must remain simple enough to explain during viva.

Academic clarity is more important than enterprise complexity.

---

# NON-GOALS

Do NOT introduce:

* Microservices
* Kubernetes
* Event Sourcing
* CQRS
* Complex Message Queues
* AI Features
* Real Payment Gateways

These may be documented as future enhancements only.

---

# FINAL RULE

When there is a conflict between:

Complexity vs Simplicity

Choose Simplicity.

When there is a conflict between:

Feature Speed vs Security

Choose Security.

When there is a conflict between:

Shortcuts vs Maintainability

Choose Maintainability.
