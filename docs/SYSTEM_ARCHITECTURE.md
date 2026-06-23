# SYSTEM ARCHITECTURE

Version: 1.0
Status: Draft

Project:
Multi-Tenant School Administration Management SaaS Platform

Program:
Master of Computer Applications (MCA)

Framework:
Laravel 13

Language:
PHP 8.4

Database:
MySQL 8

Frontend:
Blade Templates + Bootstrap 5

Authentication:
Laravel Breeze (Installed)

Multi-Tenancy:
Native Laravel Multi-Tenancy (school_id + Global Scopes)

Architecture Style:
Layered Monolithic Architecture

Deployment Model:
Single Application
Single Database
Multi-Tenant SaaS

---

# 1. ARCHITECTURE OVERVIEW

The application follows a Layered Monolithic Architecture using Laravel 13.

The architecture is designed to provide:

* Maintainability
* Scalability
* Security
* Tenant Isolation
* Modular Development
* Clean Separation of Concerns

This approach is ideal for an MCA major project because it demonstrates professional software engineering practices while remaining manageable within the project timeline.

---

# 2. HIGH-LEVEL ARCHITECTURE

```text
Browser
    │
    ▼
Routes
    │
    ▼
Middleware
    │
    ▼
Controllers
    │
    ▼
Form Requests
    │
    ▼
Services
    │
    ▼
Models
    │
    ▼
MySQL Database
```

---

# 3. APPLICATION LAYERS

## Presentation Layer

Responsibilities:

* User Interface
* Forms
* Reports
* Dashboards
* Charts

Technology:

* Blade Templates
* Bootstrap 5
* Bootstrap Icons
* Chart.js

Location:

resources/views

---

## Routing Layer

Responsibilities:

* URL Mapping
* Route Protection
* Middleware Assignment

Location:

routes/web.php

---

## Middleware Layer

Responsibilities:

* Authentication
* Authorization
* Tenant Resolution
* Session Validation
* Security Checks

Examples:

* auth
* verified
* tenant
* role

Location:

app/Http/Middleware

---

## Controller Layer

Responsibilities:

* Handle HTTP Requests
* Call Services
* Return Responses

Controllers must remain thin.

Business logic must not be placed in controllers.

Location:

app/Http/Controllers

---

## Validation Layer

Responsibilities:

* Input Validation
* Request Sanitization
* Business Rule Validation

Implementation:

Laravel Form Requests

Location:

app/Http/Requests

---

## Service Layer

Responsibilities:

* Business Logic
* Workflow Processing
* Transaction Management

Services:

Implemented foundations:

* SchoolService
* SchoolSettingService
* UserService
* DashboardService
* SecurityLogService
* PasswordSecurityService
* StudentService
* AttendanceService

Planned module services:

* FeeService
* ExamService
* ReportService

Location:

app/Services

---

## Data Access Layer

Responsibilities:

* Data Persistence
* Relationships
* Query Scopes

Implementation:

Laravel Eloquent ORM

Location:

app/Models

---

## Database Layer

Responsibilities:

* Data Storage
* Foreign Keys
* Indexes
* Constraints

Implementation:

MySQL 8

---

# 4. MULTI-TENANT ARCHITECTURE

Architecture Type:

* Shared Application
* Shared Database
* Shared Schema

Implementation:

Native Laravel Multi-Tenancy (no external tenancy package). Tenant isolation is
provided by a BelongsToTenant trait, an Eloquent global scope, and a
TenantContext middleware. See `TENANCY_DESIGN.md` for the authoritative design.

Tenant Identifier:

school_id

Benefits:

* Reduced Infrastructure Cost
* Easier Maintenance
* Faster Development
* Simplified Deployment

---

# 5. TENANT ISOLATION STRATEGY

Every business entity belongs to a school.

Examples:

* students
* teachers
* attendances
* exams
* subjects
* fee_payments
* report_cards

All records are isolated using:

school_id

Isolation is **automatic and default-deny**. Strict tenant-owned models use a
`BelongsToTenant` trait that registers an Eloquent global scope, so every query
is filtered by the active `school_id` without any manual `where('school_id', ...)`
clause. The trait also auto-fills `school_id` on creation from the tenant context.

Tenant context has three explicit states: Unresolved (deny), Tenant (scope to one
school), and Platform (authorized Super Admin access). Unresolved context never
acts as an implicit bypass. The hybrid `users` identity table is the documented
pre-authentication exception and remains policy- and service-scoped for
operational workflows.

No tenant can access another tenant's data.

---

# 6. TENANT REQUEST FLOW

```text
User Login
     │
     ▼
Authenticated User
     │
     ▼
TenantContextMiddleware
     │
     ▼
Validate role, status, school_id, and school state
     │
     ▼
Set explicit Tenant or Platform Context
     │
     ▼
Route Binding and Global Scope Apply Context
     │
     ▼
Load Authorized Data
     │
     ▼
Clear Context After Response or Exception
```

Tenant identity is resolved from the authenticated user's `school_id`, not from a
domain, subdomain, or request parameter. A validated Super Admin enters explicit
Platform context; a null school id alone does not bypass tenant scope. Context is
established before route-model binding and cleared after every execution. The
only anonymous exception is the trusted `SecurityLogService` callback approved
in DECISION-027, which temporarily enters Platform context solely to append a
credential-free failed-login activity event and then restores Unresolved state.

---

# 7. AUTHENTICATION ARCHITECTURE

Framework:

Laravel Breeze (installed)

Authentication Type:

Session-based. Login is by email + password. Email is globally unique
(see `TENANCY_DESIGN.md` §9 Email Strategy — Option A). The tenant is resolved
from the authenticated user's `school_id`.

Authentication Flow:

```text
Login
   │
   ▼
Credential Validation
   │
   ▼
Authentication Success
   │
   ▼
Role Resolution
   │
   ▼
Dashboard Redirect
```

Features:

* Login
* Logout
* Password Reset
* Email Verification
* Profile Management

---

# 8. AUTHORIZATION ARCHITECTURE

Implementation:

* Middleware
* Gates
* Policies

Roles:

* Super Admin
* School Admin
* Teacher
* Accountant

Authorization Flow:

```text
Request
   │
   ▼
Authentication Check
   │
   ▼
Permission Resolution
   │
   ▼
Policy + Tenant/Record Scope Validation
   │
   ▼
Service Context Validation
   │
   ▼
Access Granted
```

RBAC uses native Laravel models, Eloquent relationships, Gates, Policies,
services, and Blade `@can` directives. `User::hasPermission(code)` resolves the
authenticated user's current role-permission mapping from the database. The MVP
does not cache permission results, so approved mapping changes apply on the next
request without cache invalidation.

Phase 4 Components:

Implemented components:

* `config/rbac.php` - machine-readable fixed roles, permission catalog, default
  and maximum mappings, and essential permissions. It must mirror
  `MODULE_SPECIFICATIONS.md` and is shared by seeding, validation, and tests.
* `Permission` model - role relationship and immutable catalog metadata.
* `Role` model - permission relationship; role rows remain immutable.
* `User::hasPermission()` - current database mapping resolution.
* Permission-aware current-module Policies and dashboard Gate.
* Safe seed synchronization and target-only session revocation after role or
  school-ownership changes.
* `RolePolicy` - platform edit and tenant read-only boundaries.
* `RolePermissionService` - listing, constrained mapping replacement,
  stale-write protection, transactions, and activity/audit recording.
* Form Request - rejects Super Admin edits, essential removal, out-of-bound or
  forged permission IDs, and duplicate values.
* `RoleController` and Bootstrap views - fixed role directory, grouped effective
  permissions, read-only School Admin access, and constrained school-role edits.

No role or permission CRUD controller is permitted. The HTTP layer exposes only
the fixed role directory, permission details, and constrained mapping update.

Permission checks answer whether an action is available to the role. They never
replace explicit Platform or Tenant context, Policy ownership checks,
assigned-record restrictions, or service-layer validation. Denial at any layer
denies the operation.

---

# 9. ROLE ADMINISTRATION BOUNDARIES

```text
Super Admin (Platform context)
├── View all fixed roles and permissions
├── Edit allowed mappings for school roles
└── Assign any canonical role under User Management rules

School Admin (Tenant context)
├── View effective school-role permissions
└── Assign School Admin, Teacher, or Accountant within own school
```

This is an administration boundary, not permission inheritance. Teacher and
Accountant cannot administer roles. Custom roles and per-user overrides are not
part of the MVP.

---

# 10. MODULE ARCHITECTURE

The canonical module list is defined in `MODULE_SPECIFICATIONS.md` (15 modules).
The architecture groups them as follows:

1. Authentication Module
2. School Management Module
3. School Settings Module
4. User Management Module
5. Role & Permission Module
6. Academic Structure Module (Academic Years, Terms, minimal Teacher Profiles,
   Classes, Sections, and Subjects)
7. Student Management Module
8. Attendance Management Module
9. Fee Management Module
10. Examination Management Module
11. Reporting Module
12. Dashboard & Analytics Module
13. Activity Log Module
14. Audit Trail Module
15. Backup Management Module

---

## Phase 5 Academic Structure Architecture

Phase 5 owns Academic Years, Academic Terms, minimal Teacher Profiles, Classes,
Sections, and Subjects. Student Enrollment remains in Student Management.

Dependency order:

1. Academic Years
2. Academic Terms
3. Classes
4. Teacher Profiles linked to existing Users
5. Sections linked to Classes and optional Teacher Profiles
6. Subjects linked to Classes and optional Teacher Profiles

Canonical Laravel names:

| Table | Model | Service | Notes |
| ----- | ----- | ------- | ----- |
| `academic_years` | `AcademicYear` | `AcademicYearService` | Academic-year lifecycle and current activation. |
| `academic_terms` | `AcademicTerm` | `AcademicTermService` | Parent-range and term-order lifecycle. |
| `teachers` | `Teacher` | `TeacherService` | UI label is Teacher Profile. |
| `classes` | `SchoolClass` | `SchoolClassService` | Uses `$table = 'classes'`; `Class` is a PHP reserved keyword. |
| `sections` | `Section` | `SectionService` | Class and optional Teacher relationship. |
| `subjects` | `Subject` | `SubjectService` | Class and optional Teacher relationship. |

Controllers, Form Requests, and Policies use these model names consistently.
Routes retain the user-facing `/classes` and `/teacher-profiles` paths.

All six models are strict tenant-owned and use `BelongsToTenant`. Controllers
remain thin; Form Requests validate shape and tenant-scoped existence; Policies
combine exact `academic.*` permissions with role and record boundaries; services
revalidate TenantContext, same-school relationships, lifecycle transitions,
transactions, and activity/audit writes.

The Academic Year activation service serializes changes by locking the owning
School and tenant Academic Year rows in one transaction. Academic Term services
enforce parent range and non-overlap. Class, Section, Subject, and Teacher
Profile services enforce dependency-safe deactivation, archive, and restore.
User Management must reject role or school changes for a User linked to any
retained Teacher Profile.

Super Admin execution is explicit Platform-context and read-only. School Admin
execution is Tenant-context management. Teacher execution resolves the actor's
active Teacher Profile and restricts reads to assigned Sections, assigned
Subjects, and related Classes. Accountant execution is denied.

Activity and audit records use module `academic_structure`, inherit tenant
ownership, and participate in the domain transaction. No Phase 5 service may
remove `TenantScope` or accept request-selected `school_id`.

Implementation Status:

* Core migration, six canonical models, automatic TenantScope behavior,
  relationships, casts, soft-delete boundaries, and retained Teacher Profile
  User safeguards are implemented.
* Academic Year and Academic Term Policies, Form Requests, services,
  transactional lifecycle rules, tenant-safe HTTP/UI workflows, and
  activity/audit evidence are implemented.
* Teacher Profile eligible-user linking, immutable identity, dependency-safe
  lifecycle, retained-record routing, tenant-safe HTTP/UI, and activity/audit
  workflows are implemented.
* Class lifecycle, retained-record routing, tenant-safe HTTP/UI, Platform
  read-only access, assigned-Teacher reads, and activity/audit workflows are
  implemented.
* Section and Subject lifecycle, assignment validation, retained-record routing,
  tenant-safe HTTP/UI, Platform read-only access, assigned-Teacher reads, and
  activity/audit workflows are implemented.

## Phase 7 Attendance Architecture

Phase 7 owns operational daily and bulk Attendance entry, history, search,
current-year corrections, and monthly on-screen summaries. Reports, exports,
analytics, dashboard widgets, and platform summaries remain Phase 10 work.

Canonical Laravel components:

| Table | Model | Service | Notes |
| ----- | ----- | ------- | ----- |
| `attendances` | `Attendance` | `AttendanceService` | Strict tenant-owned retained daily Student records. |

`AttendanceController` handles HTTP flow only. Dedicated Form Requests validate
the date and submitted Student/status map. `AttendancePolicy` combines exact
permissions with role, tenant, and assigned-Section boundaries.
`AttendanceService` resolves the current Academic Year, Section, Class, eligible
Enrollment roster, school timezone, and actor context again before writing.

School Admin may operate on every eligible active Section in the tenant. Teacher
write and read scope requires the actor's active Teacher Profile to be assigned
directly to the active Section through `sections.teacher_id`; a Subject
assignment or related Class read does not grant Attendance authority. Super
Admin and Accountant have no Phase 7 Attendance execution path.

Bulk saves use one transaction. The service locks the selected Section and the
relevant Attendance rows, rejects incomplete or forged rosters, creates missing
eligible rows, corrects existing rows only with update authorization, and uses
the daily unique constraint as the final concurrency guard. Any validation,
authorization, activity-log, audit-log, or persistence failure rolls back the
whole batch.

Attendance placement, date, tenant, Student, and original marker are immutable.
Corrections may change only status and the optional remark. Records are never
deleted. Activity records summarize the batch without minor data; audit records
store IDs, date, old/new status, and `remarks_changed` but never raw remarks.
The audit actor identifies who made a correction while `marked_by` continues to
identify the original creator.

The status `holiday` is a School Admin-only complete-roster operation. Late is
manually selected in Phase 7; the School Setting attendance start time is a UI
reference only. School-local date calculations use the configured school
timezone.

Implementation status (2026-06-22): the Attendance migration, tenant-aware
model, status constants, date cast, retained parent relationships, immutable
identity/original-marker safeguards, and deletion rejection are implemented.
`AttendanceService`, Policy, dedicated Form Requests, thin controller, protected
routes, Bootstrap workspace/history/summary views, and role-aware navigation now
implement the approved operational workflow. Phase 10 capabilities remain
absent. Code-review remediation merges lifecycle-changed retained same-date rows
into correction rosters without permitting new ineligible records, revalidates
direct correction payloads, and reuses eager-loaded authorization context so
History row policies do not produce N+1 queries.

---

# 11. DASHBOARD ARCHITECTURE

Dashboard Components:

* Statistics Cards
* Attendance Summary
* Fee Collection Summary
* Examination Summary
* Charts
* Recent Activities
* Quick Actions

Data Sources:

* Students
* Attendance
* Fees
* Exams
* Activity Logs

---

# 12. REPORTING ARCHITECTURE

```text
User Request
      │
      ▼
Apply Filters
      │
      ▼
Build Query
      │
      ▼
Generate Dataset
      │
      ▼
Render Report
```

Supported Reports:

* Student Reports
* Attendance Reports
* Fee Reports
* Examination Reports
* Audit Reports

---

# 13. ACTIVITY LOGGING ARCHITECTURE

Table:

activity_logs

Tracked Events:

* Login
* Logout
* Create
* Update
* Delete
* Attendance Entry
* Fee Collection
* Marks Entry
* Report Generation

Implementation:

Centralized ActivityLogService

---

# 14. AUDIT ARCHITECTURE

Table:

audit_logs

Tracked Information:

* Old Values
* New Values
* User
* Timestamp
* IP Address
* User Agent

Audit records are immutable.

---

# 15. DIRECTORY STRUCTURE

```text
app/
├── Http/
│   ├── Controllers/
│   ├── Middleware/
│   └── Requests/
│
├── Models/
├── Policies/
├── Services/
├── Providers/
│
database/
├── migrations/
├── seeders/
│
resources/
├── views/
│
routes/
├── web.php
```

---

# 16. SECURITY ARCHITECTURE

Security Layers:

1. Authentication
2. Authorization
3. Tenant Isolation
4. Request Validation
5. CSRF Protection
6. Session Security
7. File Upload Validation
8. Password Hashing

---

# 17. PERFORMANCE ARCHITECTURE

Optimization Techniques:

* Database Indexing
* Pagination
* Eager Loading
* Query Optimization
* Lazy Loading Prevention

Avoid:

* N+1 Queries
* Large Unfiltered Queries
* Unnecessary Database Calls

---

# 18. DEPLOYMENT ARCHITECTURE

Production Environment:

* Ubuntu 24.04 LTS
* Nginx
* PHP 8.4
* MySQL 8
* Composer
* Git

Deployment Model:

Single Server Deployment

Suitable for MCA demonstration and evaluation.

---

# 19. ARCHITECTURAL DECISIONS

Decision 1

Use Laravel 13

Reason:

Latest stable framework with modern features and long-term support.

---

Decision 2

Use PHP 8.4

Reason:

Improved performance and future-proof development.

---

Decision 3

Use Single Database Multi-Tenancy with Native Laravel Implementation

Reason:

Simpler implementation, easier maintenance, suitable for MCA scope. Implemented
with a BelongsToTenant trait, an Eloquent global scope, and a TenantContext
middleware (no external tenancy package). See `TENANCY_DESIGN.md`.

---

Decision 4

Use Service Layer Architecture

Reason:

Cleaner code organization and separation of business logic.

---

Decision 5

Use Bootstrap 5

Reason:

Rapid UI development and responsive design.

---

# 20. SUCCESS CRITERIA

The architecture is successful when:

✓ Multi-Tenancy Works

✓ Tenant Isolation Is Enforced

✓ RBAC Is Implemented

✓ Modules Are Independent

✓ Business Logic Exists In Services

✓ Controllers Remain Thin

✓ Reports Perform Efficiently

✓ Security Standards Are Followed

✓ Architecture Is Easy To Explain During Viva

✓ Application Is Ready For MCA Submission
