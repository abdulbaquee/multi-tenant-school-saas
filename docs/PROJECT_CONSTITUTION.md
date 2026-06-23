# PROJECT CONSTITUTION

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

Architecture:
Single Database Multi-Tenant SaaS

Tenant Identifier:
school_id

---

# MCA SUBMISSION CONSTRAINT

The final MCA Project Work submission deadline is 2026-07-05.

`MCA_SUBMISSION_MASTER_PLAN.md` is authoritative for the deadline, university
requirements, Qollabb deliverables, report structure, deployment, presentation,
viva preparation, critical-path schedule, and final submission checklist.

Implementation must not consume the entire remaining schedule. Each phase must
produce tests, documentation evidence, sanitized screenshots, report inputs,
and demonstration notes while the module context is current. New feature work
freezes on 2026-07-02 except for critical security, deployment, or submission
defects.

---

# 1. PROJECT VISION

Build a modern, secure, scalable, and maintainable School Administration Management SaaS Platform that demonstrates professional software engineering practices and satisfies MCA project requirements.

The platform must support multiple schools operating independently within a shared SaaS infrastructure while ensuring complete tenant-level data isolation.

---

# 2. PROJECT OBJECTIVES

The system must enable schools to manage:

* School Administration
* User Management
* Academic Structure
* Student Records
* Attendance Tracking
* Fee Collection
* Examination Management
* Academic Reporting
* Audit Logging

The solution should remain simple enough to be implemented, demonstrated, documented, and explained during MCA evaluation and viva.

---

# 3. CORE PRINCIPLES

Every project decision must prioritize:

1. Simplicity
2. Security
3. Maintainability
4. Scalability
5. Usability
6. Academic Demonstrability

When complexity and simplicity conflict, simplicity wins.

---

# 4. TARGET USERS

## Super Admin

Responsibilities:

* Manage Schools
* Monitor System Activity
* View Audit Logs
* Manage Backups

## School Admin

Responsibilities:

* Manage School Operations
* Manage Users
* Manage Students
* Manage Attendance
* Manage Fees
* Manage Examinations

## Teacher

Responsibilities:

* Manage Attendance
* Enter Examination Marks
* View Students
* Generate Assigned Class Reports

## Accountant

Responsibilities:

* Manage Fee Collection
* Track Transactions
* Generate Financial Reports

---

# 5. APPLICATION SCOPE

Included In MVP:

* Authentication
* School Management
* School Settings
* User Management
* Roles & Permissions
* Academic Structure
* Student Management
* Attendance Management
* Fee Management
* Examination Management
* Reporting
* Dashboard & Analytics
* Activity Logs
* Audit Logs
* Backup Management

Excluded From MVP:

* Parent Portal
* Student Portal
* Mobile Applications
* Production Payment Gateways
* SMS Gateway
* Email Marketing
* AI Features

---

# 6. ARCHITECTURE PRINCIPLES

Architecture Style:

Layered Monolithic Architecture

Deployment Model:

* Single Application
* Single Database
* Multi-Tenant SaaS

Business Logic:

* Services
* Policies
* Form Requests

Controllers must remain thin.

---

# 7. MULTI-TENANCY PRINCIPLES

Tenant Identifier:

school_id

Implementation:

Native Laravel Multi-Tenancy using a BelongsToTenant trait, an Eloquent global
scope, and a TenantContext middleware. No external tenancy package is used.

Rules:

* Every strict tenant-owned business record belongs to a school.
* Tenant scoping is applied automatically by a global scope (default-deny),
  not by manual per-query filtering. Unresolved context cannot return tenant
  data.
* Tenant isolation is mandatory.
* Cross-tenant data access is prohibited.
* Super Admin requires the canonical role, `school_id = NULL`, active status,
  explicit Platform context, and policy authorization for platform-wide access.
* The hybrid `users` identity table is loaded before tenant resolution and is
  operationally isolated by Policies and Services as documented in
  `TENANCY_DESIGN.md`.

The authoritative tenancy reference is `TENANCY_DESIGN.md`.

---

# 8. SECURITY PRINCIPLES

Always Enforce:

* Authentication
* Authorization
* CSRF Protection
* Input Validation
* Password Hashing
* Session Security
* Tenant Isolation

Never Trust:

* User Input
* Client-side Authorization
* Unsanitized Data

---

# 9. DATABASE PRINCIPLES

Requirements:

* Primary Keys
* Foreign Keys
* Indexes
* Soft Deletes
* Audit Fields
* Tenant Isolation

Database Design Goals:

* Normalized
* Scalable
* Maintainable
* Report Friendly

---

# 10. DEVELOPMENT PRINCIPLES

Follow:

* SOLID Principles
* DRY
* KISS
* Separation of Concerns

Business logic belongs in Services.

Validation belongs in Form Requests.

Authorization belongs in Policies and Middleware.

---

# 11. TESTING PRINCIPLES

Every module must support:

* Feature Testing
* Authorization Testing
* Tenant Isolation Testing
* Integration Testing

Testing is mandatory.

---

# 12. REPORTING PRINCIPLES

All reports must support:

* Search
* Filters
* Pagination
* Export Capability

Reports should be easy to understand and suitable for academic demonstrations.

---

# 13. DOCUMENTATION PRINCIPLES

All architecture and implementation decisions must be documented.

Required Documentation:

* Architecture
* Database Design
* ER Diagram
* UI/UX Design
* Testing Strategy
* Module Specifications

Role permissions, dashboards, reports, and menus must follow the canonical
matrix in `MODULE_SPECIFICATIONS.md`. For the three school roles, that matrix is
both the default mapping and the maximum grant boundary; active mappings may be
a subset only where the permission is not marked essential. The Super Admin
mapping is immutable and always contains its complete matrix-approved set.

Permission grants never bypass TenantContext, Policies, service-layer checks,
or assigned-record restrictions. The MVP has no custom roles, tenant-defined
permissions, permission inheritance, or per-user permission overrides.

---

# 14. PERFORMANCE PRINCIPLES

Always Use:

* Pagination
* Eager Loading
* Database Indexes
* Query Optimization

Avoid:

* N+1 Queries
* Large Unfiltered Queries
* Unnecessary Processing

---

# 15. MCA SUCCESS CRITERIA

The project is successful when:

✓ Multi-Tenancy Works

✓ RBAC Is Implemented

✓ Core Modules Are Functional

✓ Reports Are Generated

✓ Testing Is Completed

✓ Documentation Is Complete

✓ Screenshots Are Captured

✓ MCA Report Is Prepared

✓ Viva Questions Can Be Answered Confidently

---

# 16. FINAL MISSION

Build a professional School Administration Management SaaS Platform that demonstrates modern software engineering practices, satisfies MCA project requirements, and serves as a strong portfolio project showcasing Laravel 13, PHP 8.4, multi-tenant architecture, database design, reporting, testing, and secure application development.
