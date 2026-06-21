# DECISIONS LOG

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

Purpose:

This document records major architectural, technical, security, and design decisions made during the project lifecycle.

The goal is to provide traceability, justification, and historical context for all important decisions.

---

# DECISION-001

Date:
2026-06-18

Title:
Use Laravel 13 As Primary Framework

Status:
Approved

Decision:

The application will be developed using Laravel 13.

Reason:

* Latest stable Laravel release
* Modern architecture
* Strong ecosystem
* Built-in authentication support
* Excellent documentation
* Industry adoption

Alternatives Considered:

* CodeIgniter 4
* Symfony
* Slim Framework

Outcome:

Laravel 13 selected.

---

# DECISION-002

Date:
2026-06-18

Title:
Use PHP 8.4

Status:
Approved

Decision:

The application will use PHP 8.4.

Reason:

* Improved performance
* Modern language features
* Long-term maintainability
* Better type safety

Alternatives Considered:

* PHP 8.2
* PHP 8.3

Outcome:

PHP 8.4 selected.

---

# DECISION-003

Date:
2026-06-18

Title:
Use MySQL 8 As Database

Status:
Approved

Decision:

The application database will be MySQL 8.

Reason:

* Widely adopted
* Easy deployment
* Excellent Laravel support
* Strong documentation
* Suitable for MCA project scope

Alternatives Considered:

* PostgreSQL
* MariaDB
* SQL Server

Outcome:

MySQL 8 selected.

---

# DECISION-004

Date:
2026-06-18

Title:
Adopt Single Database Multi-Tenant Architecture

Status:
Approved

Decision:

All schools will share one database and one schema.

Tenant isolation will be implemented using:

school_id

Reason:

* Simpler implementation
* Easier maintenance
* Lower infrastructure complexity
* Suitable for MCA project scope

Alternatives Considered:

* Database Per Tenant
* Schema Per Tenant

Outcome:

Single Database Multi-Tenant Architecture selected.

---

# DECISION-005 (Superseded)

Date:
2026-06-18

Title:
Use Stancl Tenancy

Status:
Superseded by DECISION-005-R (2026-06-19)

Original Decision:

Stancl Tenancy was initially proposed as the multi-tenancy foundation.

Reason It Was Superseded:

Stancl Tenancy is designed around a tenants table and domain/request-based
tenant identification (commonly database-per-tenant). This conflicts with the
project's Single Database + Shared Schema + `school_id` model (DECISION-004),
where the tenant is resolved from the authenticated user's `school_id`. The
package was also never installed. To remove the architectural contradiction, the
project adopts native Laravel multi-tenancy instead.

---

# DECISION-005-R

Date:
2026-06-19

Title:
Use Native Laravel Multi-Tenancy (school_id + Global Scopes)

Status:
Approved

Decision:

Multi-tenancy will be implemented using native Laravel features and no external
tenancy package:

* `school_id` tenant key on every business table
* Eloquent Global Scope via a BelongsToTenant trait
* TenantContext middleware
* Policies and Services

Reason:

* Resolves the DECISION-004 / DECISION-005 contradiction
* Simplicity-first (Project Constitution): nothing extra to install or explain
* Single-database shared-schema needs no connection switching
* Automatic, default-deny tenant isolation via global scope
* Easy to demonstrate and defend during MCA viva

Alternatives Considered:

* Stancl Tenancy (superseded — see DECISION-005)
* Spatie Multitenancy
* Manual per-query filtering (rejected — error-prone, not default-deny)

Outcome:

Native Laravel Multi-Tenancy selected. Authoritative design in
`TENANCY_DESIGN.md`.

---

# DECISION-006

Date:
2026-06-18

Title:
Use Laravel Breeze For Authentication

Status:
Approved

Decision:

Authentication will be implemented using Laravel Breeze.

Reason:

* Lightweight
* Official Laravel package
* Easy customization
* Suitable for educational projects

Alternatives Considered:

* Laravel Jetstream
* Laravel Fortify
* Custom Authentication

Outcome:

Laravel Breeze selected.

---

# DECISION-007

Date:
2026-06-18

Title:
Use Bootstrap 5 As UI Framework

Status:
Approved

Decision:

Bootstrap 5 will be the only UI framework.

Reason:

* Fast development
* Responsive by default
* Consistent components
* Easy maintenance

Alternatives Considered:

* Tailwind CSS
* Material UI
* Premium Admin Templates

Outcome:

Bootstrap 5 selected.

---

# DECISION-008

Date:
2026-06-18

Title:
Adopt Layered Monolithic Architecture

Status:
Approved

Decision:

The application will follow a layered monolithic architecture.

Layers:

* Presentation Layer
* Controller Layer
* Service Layer
* Model Layer
* Database Layer

Reason:

* Easier development
* Suitable for MCA scope
* Simpler deployment
* Easier debugging

Alternatives Considered:

* Microservices
* Modular Monolith

Outcome:

Layered Monolith selected.

---

# DECISION-009

Date:
2026-06-18

Title:
Use Service Layer Pattern

Status:
Approved

Decision:

Business logic will reside in services.

Reason:

* Thin controllers
* Better maintainability
* Easier testing
* Cleaner architecture

Outcome:

Service Layer Architecture adopted.

---

# DECISION-010

Date:
2026-06-18

Title:
Use Role-Based Access Control

Status:
Approved

Decision:

Access control will be role based.

Roles:

* Super Admin
* School Admin
* Teacher
* Accountant

Reason:

* Easy to understand
* Easy to implement
* Matches school workflows

Outcome:

RBAC selected.

---

# DECISION-011

Date:
2026-06-18

Title:
Use Academic Structure Module

Status:
Approved

Decision:

Academic Years, Terms, Classes, Sections, and Subjects will be grouped under a single Academic Structure module.

Reason:

* Cleaner navigation
* Better organization
* Easier maintenance

Outcome:

Academic Structure Module created.

---

# DECISION-012

Date:
2026-06-18

Title:
Implement Activity Logging

Status:
Approved

Decision:

All critical business actions will be logged.

Examples:

* Login
* Logout
* Attendance Entry
* Fee Collection
* Marks Entry

Reason:

* Auditability
* Accountability
* Reporting

Outcome:

Activity Logging mandatory.

---

# DECISION-013

Date:
2026-06-18

Title:
Implement Audit Trail

Status:
Approved

Decision:

Data modifications will be recorded.

Tracked Information:

* Old Values
* New Values
* User
* Timestamp

Reason:

* Accountability
* Traceability
* Security

Outcome:

Audit Trail mandatory.

---

# DECISION-014

Date:
2026-06-18

Title:
Use Soft Deletes

Status:
Approved

Decision:

Business entities will use Soft Deletes.

Applies To:

* Students
* Teachers
* Users
* Classes
* Sections
* Subjects
* Exams

Reason:

* Prevent accidental data loss
* Improve recovery capability

Outcome:

Soft Deletes enabled.

---

# DECISION-015

Date:
2026-06-18

Title:
No Parent Portal In MVP

Status:
Approved

Decision:

Parent Portal excluded from initial release.

Reason:

* Reduce project complexity
* Focus on core academic workflows
* Meet MCA timeline

Outcome:

Deferred to future enhancement.

---

# DECISION-016

Date:
2026-06-18

Title:
No Student Portal In MVP

Status:
Approved

Decision:

Student Portal excluded from initial release.

Reason:

* Reduce scope
* Prioritize administration modules

Outcome:

Deferred to future enhancement.

---

# DECISION-017

Date:
2026-06-18

Title:
Dark Mode Not Included

Status:
Approved

Decision:

Dark Mode excluded from MVP.

Reason:

* Additional UI complexity
* Increased testing effort
* Not required for evaluation

Outcome:

Future enhancement.

---

# DECISION-018

Date:
2026-06-19

Title:
Use Chart.js For Analytics

Status:
Approved

Decision:

Analytics visualizations will use Chart.js.

Reason:

* Lightweight
* Open source
* Easy Bootstrap integration

Alternatives Considered:

* ApexCharts
* Highcharts

Outcome:

Chart.js selected.

---

# DECISION-019

Date:
2026-06-19

Title:
Tenant Isolation Is Highest Priority Security Requirement

Status:
Approved

Decision:

All business data must be filtered using:

school_id

Reason:

* Core SaaS requirement
* Prevent cross-school access
* Maintain confidentiality

Outcome:

Mandatory validation in all modules.

---

# DECISION-020

Date:
2026-06-19

Title:
Documentation First Development Approach

Status:
Approved

Decision:

Complete architecture and design documentation before development.

Reason:

* Reduce rework
* Improve planning
* Improve MCA report quality
* Simplify implementation

Outcome:

Documentation phase completed before coding.

---

# DECISION-021

Date:
2026-06-19

Title:
Global Unique Email For Authentication (Option A)

Status:
Approved

Decision:

`users.email` will be globally unique across the entire platform. Login is by
email + password, and the tenant is resolved from the authenticated user's
`school_id`. `users.school_id` is nullable (NULL = Super Admin).

Reason:

* Keeps the default Laravel Breeze login flow unchanged (no school selector or
  subdomain required at login)
* Simplicity-first; avoids composite-key login ambiguity
* Each user belongs to exactly one school

Alternatives Considered:

* Unique (school_id, email) — rejected for MVP because it complicates login

Trade-off:

* A person working at two schools needs two separate accounts. Deferred to
  Future Enhancements.

Outcome:

Global unique email adopted. Documented in `TENANCY_DESIGN.md` §9.

---

# DECISION-022

Date:
2026-06-19

Title:
Use MODULE_SPECIFICATIONS.md As Canonical Permission Matrix

Status:
Approved

Decision:

The canonical MVP permission matrix is defined in `MODULE_SPECIFICATIONS.md`.
Menus, dashboards, reports, screen flows, and role responsibilities must match
that matrix.

Reason:

* Prevents contradictions between module specifications and navigation
* Keeps RBAC simple for MCA implementation
* Makes role behavior easy to test and explain

Outcome:

Canonical permission matrix approved for Super Admin, School Admin, Teacher, and
Accountant roles.

---

# DECISION-023

Date:
2026-06-19

Title:
Remove Orphan Settings Modules From MVP

Status:
Approved

Decision:

The only settings module retained in the MVP is School Settings for school-level
configuration.

Reason:

* No database table exists for additional settings areas
* No module specification exists for additional settings areas
* Removing orphan references keeps the project simpler and implementation-ready

Outcome:

Orphan settings references removed from scope, menus, screen flows, and
project-level documentation.

---

# DECISION-024

Date:
2026-06-19

Title:
Adopt Retention-First Deletion Strategy

Status:
Approved

Decision:

The project uses a retention-first deletion strategy:

* Soft delete schools
* Soft delete users
* Soft delete teachers
* Soft delete students
* Soft delete fee categories, fee structures, and student fee assignments where appropriate
* Preserve attendance, payments, transactions, exam results, report cards, activity logs, and audit logs
* Use `restrictOnDelete()` for foreign keys by default
* Do not use cascading deletes for tenant-owned data

Reason:

* Preserves audit history
* Prevents accidental tenant data loss
* Keeps historical reports valid
* Matches financial and academic record retention expectations

Outcome:

Deletion policy standardized across database design, coding standards, and
security guidelines.

---

# DECISION-025

Date:
2026-06-19

Title:
Use Private Storage For Student Photos And Backup Files

Status:
Approved

Decision:

Student photos and backup files must use private storage and be served only
through authorized controller access. School logos may use public storage.

Reason:

* Student photos are personal data
* The project stores records for minors
* Backup files may contain sensitive tenant data
* School logos are public branding assets and do not require private delivery

Outcome:

File storage and privacy policy standardized in `SECURITY_GUIDELINES.md`.

---

# DECISION-026

Date:
2026-06-20

Title:
Use Explicit Tenant Context States And Default-Deny Lifecycle

Status:
Approved

Decision:

Native Laravel Multi-Tenancy uses three explicit execution states:

* Unresolved: deny tenant-owned reads and writes.
* Tenant: scope to one validated active school.
* Platform: explicit authenticated Super Admin access protected by Policies and
  Services.

A null `school_id` alone never grants a bypass. Context is established before
route-model binding and cleared after every request, exception, job, command,
test, or per-school iteration.

The `users` table is a documented hybrid identity exception because login must
retrieve a globally unique user before tenant resolution. Operational user
management remains policy- and service-scoped. `schools` is the platform tenant
registry. Strict tenant-owned business models use `BelongsToTenant`.

School deactivation revokes school-user sessions and remember tokens, blocks
future login and authenticated requests, and retains tenant data. School
Settings is included in Phase 3 with School Management and tenant infrastructure.

Reason:

* Removes the unsafe ambiguity where missing context could imply either deny or
  platform access.
* Prevents context leakage in long-running workers and sequential requests.
* Preserves simple globally unique email authentication.
* Defines inactive-school behavior before School Management implementation.
* Reconciles roadmap scope with the canonical module-to-phase mapping.

Alternatives Considered:

* Treat missing context as an implicit Super Admin bypass - rejected as unsafe.
* Apply the generic global scope to pre-authentication User lookup - rejected as
  unnecessary complexity for the MCA scope.
* Defer School Settings to an unspecified later phase - rejected because the
  canonical module mapping places it in Phase 3.

Outcome:

Phase 3 tenancy lifecycle and scope are implementation-ready in
`TENANCY_DESIGN.md` and aligned across security, testing, database, architecture,
roadmap, and coding documentation.

---

# DECISION-027

Date:
2026-06-21

Title:
Allow Narrow Trusted-System Platform Context For Security Activity Recording

Status:
Approved

Decision:

Unauthenticated failed or tenant-denied login attempts may enter Platform
context temporarily only inside `SecurityLogService` and only for the callback
that appends a credential-free platform activity record.

This pathway cannot infer Platform access from a missing `school_id`, cannot
read tenant business data, and must restore the previous context after success
or exception. Authenticated activity and audit records continue to require an
actor whose role and `school_id` match explicit Tenant or Platform context.

Reason:

* Failed login attempts have no authenticated actor or trusted tenant id.
* Security operations require queryable unauthorized-access evidence.
* A centralized append-only callback is narrower than granting public routes a
  general tenant-scope bypass.
* Credential fields remain prohibited from activity and audit records.

Alternatives Considered:

* Treat Unresolved context as Platform for logs - rejected because it weakens
  default-deny semantics.
* Add a fourth System context state - rejected as unnecessary complexity for the
  MCA scope.
* Record failed login attempts only in application text logs - rejected because
  it would not support the documented activity-log review workflow.

Outcome:

Phase 3 security activity recording can capture anonymous login failures without
changing tenant isolation for business models. The exception is centralized,
append-only, tested for context restoration, and documented in
`TENANCY_DESIGN.md` and `SECURITY_GUIDELINES.md`.

---

# DECISION-028

Date:
2026-06-21

Title:
Keep Cross-School Ownership Transition Logs In Platform Context

Status:
Approved

Decision:

When an authorized Super Admin moves a user from one school to another, the
corresponding activity and audit records use `school_id = NULL` and are written
only in explicit Platform context.

Reason:

The audit values contain both the source and target school ownership. Assigning
that history to either school would expose information from the other tenant in
a future tenant-scoped audit screen.

Outcome:

The platform retains complete transfer evidence while both source-school and
target-school log queries remain isolated. Regression tests verify visibility in
Platform context and denial in both Tenant contexts.

---

# DECISION-029

Date:
2026-06-21

Title:
Use Fixed Roles With Constrained Canonical Permission Mappings

Status:
Approved

Decision:

The MVP uses exactly four system-managed roles and a fixed permission catalog.
Custom roles, role deletion, role-code changes, tenant-defined permissions,
permission inheritance, and per-user overrides are prohibited.

The Super Admin mapping is immutable and always contains every permission in its
matrix-approved set. An authorized Super Admin in explicit Platform context may grant or
revoke non-essential permissions for School Admin, Teacher, and Accountant only
within each role's canonical maximum set. Essential permissions remain locked.

School Admin can view effective school-role mappings and assign School Admin,
Teacher, or Accountant to users in the active school, but cannot edit mappings,
view the Super Admin mapping, assign Super Admin, or cross tenant boundaries.

Permission checks use native Laravel models, relationships, Gates, Policies,
services, and Blade authorization. Permission results are not cached in the MVP.
Tenant context, record ownership, assigned-record boundaries, and lifecycle
checks remain mandatory independently of permission grants.

`config/rbac.php` is the machine-readable mirror of the canonical matrix and is
used by seed synchronization, mapping validation, and integrity tests. Runtime
authorization still reads the current role-permission rows from the database.

Reason:

* Preserves a demonstrable Role & Permission module without custom-role
  complexity.
* Prevents privilege escalation and accidental platform lockout.
* Keeps the canonical matrix authoritative while allowing controlled school-role
  configuration.
* Uses the existing database schema and native Laravel authorization stack.
* Makes permission changes immediate and easy to test during the MCA viva.

Alternatives Considered:

* Fully immutable mappings - rejected because it would reduce Permission
  Management to a read-only catalog.
* Custom tenant roles - rejected as unnecessary complexity for the MCA scope.
* Per-user overrides - rejected because they obscure the canonical role model.
* External RBAC package - rejected because native Laravel is sufficient.
* Cached permission resolution - deferred because the MVP data set is small and
  immediate consistency is safer.

Outcome:

Phase 4 implements the approved RBAC boundary without a schema change or
external package. The fixed role directory, effective-permission views,
constrained school-role editor, stale-write protection, server-locked
essentials, immediate database-backed authorization, and transactional Platform
activity/audit evidence are covered by automated tests. The governance review
gate approved Phase 4 with no blocking issues.

---

# DECISION-030

Date:
2026-06-21

Title:
Define Phase 5 Academic Structure And Lifecycle Boundaries

Status:
Approved

Decision:

Phase 5 implements Academic Years, Academic Terms, minimal Teacher Profiles,
Classes, Sections, and Subjects as one tenant-owned Academic Structure module.
"Academic Sessions" is documentation shorthand for the combined academic-year
and academic-term lifecycle; implementation uses the canonical entity names.
Student registration and Student Enrollment remain Phase 6.

Teacher Profiles exist only to link an existing active Teacher-role user to
academic identity and section/subject assignments. Payroll, staff attendance,
HR, timetable, and general staff-management features are excluded. A Teacher
Profile must belong to the same school as its user, and the user cannot change
role or school while any retained Teacher Profile exists. This deliberately
avoids cross-tenant profile transfer and preserves the one-profile-per-user
history in the MVP.

Every Phase 5 model uses `BelongsToTenant`; TenantContext supplies `school_id`.
Services and Policies must verify that every parent, linked user, and teacher
assignment belongs to the same active school. Request input never chooses the
tenant.

Academic years cannot overlap within one school. Creation produces a non-current
year unless an authorized activation workflow is executed. Activation runs in
one transaction, locks the owning school and its academic years, clears the
previous current flag, and marks one active target current. At most one current
year is allowed per school. A current year cannot be deactivated; activate its
replacement first. A non-current year cannot be deactivated until all of its
Terms are inactive. Reactivating an inactive year restores active, non-current
status; making it current remains a separate authorized activation.

Academic terms must have positive unique ordering within their year, dates
inside the parent academic-year range, and no date overlap with another term in
that year. An inactive Term may be reactivated only while its parent year is
active and its dates remain valid. Academic years and terms use status lifecycle
only and are never soft deleted in the MVP.

Classes, Sections, Subjects, and Teacher Profiles prefer deactivation. They may
be soft deleted only when inactive and free of active downstream assignments or
records. Their tenant-scoped unique names, codes, and employee codes remain
reserved after soft deletion; restoration reuses the original record. Teacher
Profile deactivation is also blocked while active Section or Subject assignments
remain. Its linked User is immutable, and restoration requires that User to
remain an active, non-deleted, same-school Teacher. Restoration returns the
profile inactive so activation remains an explicit authorized transition.

Super Admin receives explicit Platform-context read-only Academic Structure
access. School Admin manages Academic Structure inside the active tenant when
the exact `academic.*` permission is effective. Teacher receives read-only
access only to Sections and Subjects assigned through their own active Teacher
Profile and to the related Classes. Accountant has no Academic Structure access.

Academic mutations use activity module `academic_structure` with the action
vocabulary `created`, `updated`, `activated`, `deactivated`, `archived`, and
`restored`. Audit records target the mutated model, contain sanitized changed
fields, inherit the tenant school from TenantContext, and are written in the
same transaction as the mutation.

Reason:

* Removes scope conflicts between Academic Structure and Student Enrollment.
* Makes teacher assignment and assigned-record authorization implementable.
* Prevents cross-tenant parent and teacher references.
* Defines deterministic academic-year and term lifecycle behavior.
* Preserves history without introducing scheduling or HR complexity.
* Keeps the Phase 5 design demonstrable and explainable for MCA evaluation.

Alternatives Considered:

* Defer Teacher Profiles - rejected because Section/Subject assignment and
  Teacher read scope would remain undefined.
* Include Student Enrollment - rejected because it belongs to Student
  Management in Phase 6.
* Permit overlapping years or terms - rejected because current context and
  reporting would become ambiguous.
* Transfer Teacher Profiles across schools - rejected as unnecessary
  cross-tenant lifecycle complexity for the MVP.
* Hard-delete academic structure - rejected because later academic history must
  remain reportable.

Outcome:

Phase 5 has one canonical scope, lifecycle model, role boundary, tenant contract,
retention strategy, audit vocabulary, and testable implementation sequence.

---

# DECISION CHANGE PROCESS

New decisions must include:

* Decision ID
* Date
* Title
* Status
* Decision
* Reason
* Alternatives Considered
* Outcome

No major architectural decision should be implemented without updating this document.

---

# CURRENT PROJECT STATUS

Architecture Decisions:
Completed

Security Decisions:
Completed

Technology Decisions:
Completed

Database Decisions:
Completed

UI/UX Decisions:
Completed

Development Decisions:
Completed

Project Ready For:
Phase 5 Tenant-Isolation Review
