# SECURITY GUIDELINES

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

Authentication:
Laravel Breeze (Installed)

Multi-Tenancy:
Native Laravel Multi-Tenancy (school_id + Global Scopes)

Architecture:
Single Database Multi-Tenant SaaS

Tenant Identifier:
school_id

Purpose:

Define security standards, controls, implementation requirements, and security validation procedures for the application.

This document serves as the official security reference for the project.

---

# 1. SECURITY OBJECTIVES

The application must provide:

* Confidentiality
* Integrity
* Availability
* Accountability
* Tenant Isolation

while maintaining usability and simplicity suitable for an MCA project.

---

# 2. SECURITY PRINCIPLES

Always follow:

1. Least Privilege
2. Defense In Depth
3. Secure By Default
4. Explicit Authorization
5. Input Validation
6. Output Escaping
7. Tenant Isolation

Never trust:

* User Input
* Browser Validation
* Client-side Authorization
* Query Parameters

---

# 3. SECURITY ARCHITECTURE

Security Layers:

User
↓
Authentication
↓
Authorization
↓
Tenant Validation
↓
Input Validation
↓
Business Logic
↓
Database Access

Every request must pass all security layers.

---

# 4. AUTHENTICATION POLICY

Authentication System:

Laravel Breeze (installed)

Authentication Type:

Session-Based Authentication

Email Model:

Email is globally unique across the platform (Option A). Login is by
email + password; the tenant is resolved from the authenticated user's
`school_id`. See `TENANCY_DESIGN.md` §9.

Requirements:

* Secure Login
* Secure Logout
* Password Reset
* Session Protection
* Remember Me Support
* Active school validation for every non-Super-Admin login
* Generic forgot-password responses that do not disclose account existence
* Request throttling for login, forgot-password, and verification endpoints

Never:

* Store Plain Text Passwords
* Display Passwords
* Log Passwords

---

# 5. PASSWORD POLICY

Minimum Requirements:

* 8 Characters

Recommended:

* 12+ Characters

Must Include:

* Uppercase Letter
* Lowercase Letter
* Number

Storage Method:

Hash::make()

Passwords must never be stored in plain text.

Implementation:

* `Password::defaults()` defines the canonical minimum-eight, mixed-case, and
  numeric rule for every password-setting path.
* User creation, administrative reset, profile change, and forgot-password reset
  must all use the canonical rule.
* Profile changes and token-based resets use dedicated Form Requests and the
  transactional `PasswordSecurityService`; credential, session-revocation, and
  security-log writes must succeed or roll back together.
* Administrators cannot reset their own password through User Management; they
  must use Profile and provide their current password.

---

# 6. SESSION SECURITY

Requirements:

* Session Regeneration After Login
* Session Invalidation After Logout
* Secure Cookies
* HTTP Only Cookies
* SameSite Protection

Laravel security defaults must remain enabled.

School Lifecycle Requirements:

* Deactivating a school revokes database sessions and remember tokens for its
  users without deleting their accounts.
* Every authenticated request rejects users whose school is inactive, missing,
  or soft deleted.
* Reactivation restores login eligibility only for users whose own status is
  active.
* Administrative and token-based password resets clear remember tokens and
  revoke the affected user's database sessions.
* Self-service password changes retain the current session but revoke other
  database sessions for the same user.

Production Cookie Requirements:

* Use HTTPS and set `SESSION_SECURE_COOKIE=true`.
* Keep `SESSION_HTTP_ONLY=true`.
* Keep `SESSION_SAME_SITE=lax` or use a stricter reviewed value.
* Set `APP_DEBUG=false` in production.

---

# 7. AUTHORIZATION POLICY

Authorization Components:

* Policies
* Gates
* Middleware

Authorization must always be enforced server-side.

UI restrictions alone are not security.

---

# 8. ROLE-BASED ACCESS CONTROL

Roles:

* Super Admin
* School Admin
* Teacher
* Accountant

Permissions must be validated before:

* View
* Create
* Update
* Delete
* Export
* Approve

Every protected action requires authorization.

RBAC Security Contract:

* Four fixed system roles and a fixed permission catalog are used for the MVP.
* The Super Admin mapping is immutable and always retains every permission in
  its matrix-approved set, including `roles.manage`; it does not exceed the
  canonical module boundary.
* School-role mappings cannot receive permissions outside their canonical
  maximum, and essential permissions cannot be revoked.
* Only an authenticated Super Admin in explicit Platform context can update
  school-role mappings.
* School Admin role assignment remains limited to assignable roles and users in
  the active school. Super Admin assignment, cross-tenant assignment, and self
  role changes are denied.
* Permission checks are additive to TenantContext, Policies, and service-layer
  validation; they never bypass record scope.
* Permission results are not cached in the MVP and are resolved from the current
  database mapping on each request.
* Mapping replacement and user role assignment are transactional and append
  activity and audit records. Role or school-ownership changes revoke the target
  user's sessions and remember token; mapping changes take effect on the next
  request.
* Forged permission IDs, duplicate mappings, out-of-bound grants, essential-
  permission removal, and Super Admin mapping changes must fail validation.

Implementation Status:

* The fixed catalog, constrained seed synchronization, immutable catalog models,
  database permission resolution, permission-aware current-module Policies, and
  role-change session revocation are implemented.
* Mapping routes and validation enforce explicit Platform context, immutable
  Super Admin access, maximum boundaries, essential retention, duplicate and
  forged-ID rejection, stale-write detection, transactional logging, and
  rollback on failure. The Phase 4 security and release reviews are approved.

## Phase 5 Academic Structure Security Contract

* Academic Structure models are strict tenant-owned and must use automatic
  `BelongsToTenant` scope enforcement.
* Tenant ownership is derived from TenantContext. `school_id` is prohibited in
  create and update requests.
* Services revalidate same-school Academic Year, Term, Class, Teacher Profile,
  Section, Subject, and linked User relationships. A valid foreign key from a
  different school is a denied forged-parent attempt.
* Super Admin access is Platform-context and read-only. School Admin mutations
  require matching Tenant context and the exact `academic.*` permission.
* Teacher access is read-only and derives assignments from the authenticated
  user's active Teacher Profile. Request parameters cannot select another
  teacher's assignment scope. Accountant access is denied.
* Academic-year activation is transactional and serialized by locking the
  owning School and tenant year rows. Overlapping years and multiple current
  years are rejected. Current years and non-current years with active Terms
  cannot be deactivated.
* Terms must remain inside the parent year and cannot overlap another term in
  that year.
* Teacher-role or school changes are denied while any retained Teacher Profile
  exists. Cross-school Teacher Profile transfer is not supported.
* Deactivation and dependency-safe archival replace hard deletion. Every foreign
  key remains `restrictOnDelete()`.
* Academic mutations and their sanitized activity/audit records commit or roll
  back together under the tenant school.

Implementation Status:

* Core Phase 5 schema/models enforce automatic tenant scope, tenant-derived and
  immutable ownership, restricted foreign keys, approved soft deletes, and the
  retained Teacher Profile role/school-change guard.
* Academic Year and Academic Term services enforce actor-context alignment,
  exact permissions, Platform read-only access, tenant-safe parent validation,
  serialized current-year activation, retained status lifecycle, and
  transaction-coupled activity/audit records.
* Teacher Profile services enforce same-school active Teacher-user eligibility,
  immutable linked identity, retained archived uniqueness, active-assignment
  lifecycle guards, restore-time revalidation, exact permissions, and
  transaction-coupled logs.
* Class services enforce tenant-derived ownership, retained uniqueness,
  dependency-safe lifecycle transitions, exact permissions, and transaction-
  coupled logs. Teacher reads are restricted to active Classes related through
  the actor's own active Teacher Profile and active assignments.
* Section and Subject services enforce active same-tenant Class and optional
  Teacher Profile relationships, retained identity, lifecycle revalidation,
  exact permissions, assigned-Teacher concealment, and transaction-coupled logs.
* The Phase 5 security review passed at 10/10 after the local dummy-login file
  was made root-specifically ignored. PHP and JavaScript dependency audits
  reported no advisories, and 138 security-focused tests with 1,057 assertions
  passed.

---

# 9. MULTI-TENANT SECURITY

Most Critical Security Requirement.

Tenant Identifier:

school_id

Every business record belongs to a school.

Examples:

* students
* attendances
* fee_payments
* exams
* report_cards

Enforcement Mechanism:

Isolation is **automatic and default-deny** via a `BelongsToTenant` trait that
registers an Eloquent global scope on strict tenant-owned models. The active
tenant is set by `TenantContextMiddleware` from the authenticated user's
validated `school_id` and active school.
Manual `where('school_id', ...)` filtering is NOT the primary protection
mechanism and must not be relied upon. See `TENANCY_DESIGN.md`.

Unresolved context returns no tenant records and rejects tenant writes. Super
Admin platform access requires explicit Platform context after role, null
`school_id`, active status, authentication, and policy checks. The hybrid
`users` identity table is the documented pre-authentication exception and its
operational workflows remain Policy- and Service-scoped.

Rule:

School A must never access School B data.

No exceptions.

---

# 10. TENANT ISOLATION CONTROLS

Primary Control:

* Automatic Eloquent global scope (BelongsToTenant trait) — default-deny

Defense-in-Depth Layers:

* TenantContext middleware (sets explicit Tenant or Platform context per request)
* Policies (authorize record ownership)
* Services (enforce business rules within tenant)
* Reports and Exports (inherit the same global scope as on-screen queries)

Validation Checklist:

✓ Tenant model uses BelongsToTenant trait (global scope active)

✓ User belongs to an active, non-deleted school, or is a validated Super Admin

✓ Queries filtered automatically by the global scope

✓ Reports inherit the global scope

✓ Exports inherit the global scope

✓ Super Admin scope bypass is explicit and authorized

✓ Unresolved context is default-deny

✓ Context is established before route-model binding and always cleared

✓ Tenant jobs and commands establish and clear trusted context explicitly

---

# 11. INPUT VALIDATION

All user input must be validated.

Implementation:

Laravel Form Requests

Examples:

* StoreStudentRequest
* StoreAttendanceRequest
* StoreFeeRequest
* StoreExamRequest

Never:

* Trust Browser Validation
* Trust JavaScript Validation
* Validate Directly In Blade Templates

---

# 12. OUTPUT ESCAPING

Always escape user-generated content.

Safe Example:

```php
{{ $student->name }}
```

Avoid:

```php
{!! $userInput !!}
```

unless explicitly required and sanitized.

Purpose:

Prevent Cross-Site Scripting (XSS).

---

# 13. SQL INJECTION PROTECTION

Use:

* Eloquent ORM
* Query Builder
* Prepared Statements

Never:

* Build SQL queries using string concatenation.

Always use parameterized queries.

---

# 14. CROSS-SITE SCRIPTING (XSS)

Protection Strategy:

* Blade Escaping
* Input Validation
* Output Encoding
* Sanitization

User-supplied content must never be rendered unescaped.

---

# 15. CROSS-SITE REQUEST FORGERY (CSRF)

Use Laravel CSRF Protection.

Every form must contain:

@csrf

Never disable CSRF middleware.

---

# 16. FILE UPLOAD SECURITY

Supported Uploads:

* Student Photos
* School Logos

Validation Required:

* File Type
* MIME Type
* File Size

Allowed Types:

* jpg
* jpeg
* png
* webp

Maximum Size:

2 MB

Never trust file extensions.

Always validate MIME types.

---

# 17. FILE STORAGE POLICY

Student Photos:

* Store on a private storage disk.
* Do not place student photos in a public web directory.
* Serve photos only through an authorized controller action.
* Validate the authenticated user's tenant before returning the file.
* Validate the user's permission before returning the file.
* Use unique generated filenames.
* Never store uploads using original filenames.

Recommended private path:

```text
storage/app/private/student-photos
```

School Logos:

* Public storage is allowed.
* Logos may be stored on the public disk because they are not student personal data.
* Validate file type, MIME type, and file size.
* Use unique generated filenames.

Recommended public path:

```text
storage/app/public/school-logos
```

Backup Files:

* Backup files must use private storage.
* Backup files must never be publicly accessible.
* Backup download access is Super Admin only.

Allowed storage approach:

```text
Storage::disk('private')->putFile(...)
Storage::disk('public')->putFile(...) for school logos only
```

---

# 18. STUDENT DATA PRIVACY

Student records contain sensitive information because most students are minors.

Sensitive student data includes:

* Date of Birth
* Guardian Name
* Guardian Phone Number
* Guardian Email
* Mobile Numbers
* Student Photos
* Residential Address

Privacy Requirements:

* Collect only data required for school administration.
* Display sensitive fields only to authorized roles.
* Do not expose student photos through public URLs.
* Do not include unnecessary personal data in reports.
* Exports must follow the same permission and tenant rules as screens.
* Logs must not store full sensitive payloads unless required for audit trail.

---

## Phase 6 Student Access and Photo Rules

Student data is shown by role and need:

* School Admin may manage complete Student records only inside the active school.
* Teacher access is limited to the assigned-record scope defined in
  `MODULE_SPECIFICATIONS.md` and excludes DOB, guardian details, address, phone
  numbers, email, and Student photos.
* Super Admin access is read-only, requires explicit Platform authorization and
  an explicitly selected school, and uses the same privacy-minimized fields as
  Teacher access.
* Accountant has no direct Student route or menu before Phase 8 Fee Management.

Student photo rules:

* Only an authorized School Admin in the Student's active tenant may upload,
  replace, remove, or receive a Student photo.
* Replacement deletes the prior private file only after the new file is stored
  successfully and the Student update commits. Removal deletes the private file
  only after the database update commits.
* Photo access is denied for Teachers, Accountants, Super Admins, guests,
  cross-tenant actors, inactive or archived Students, and inactive or
  soft-deleted schools.
* Student archival retains the private file for record continuity but denies all
  photo delivery. Restoration returns the Student inactive, so photo delivery
  remains denied until a separate authorized reactivation.

Student logging rules:

* Student and Enrollment activity/audit records retain only IDs, admission
  number, status, Academic Year, Class, Section, roll number, and a
  `photo_changed` indicator where needed.
* Never log DOB, guardian details, address, phone number, email, photo path, or
  image contents in activity descriptions, audit old/new values, exceptions, or
  validation messages.

Implementation Status:

* Student Policy and Service enforcement cover own-tenant School Admin
  management, assigned-record Teacher reads, selected-school Super Admin reads,
  Accountant denial, default-deny service contexts, and cross-tenant 404s.
* Student photos use generated names under tenant-separated paths on the private
  local disk and are streamed only through an authorized controller. Public
  paths and URLs are not used.
* Photo replacement/removal, transaction rollback cleanup, archived-file
  retention, inactive/archived denial, and privacy-safe logs are tested.
* Enrollment creation, completion, transfer, and graduation are School
  Admin-only own-tenant workflows. Placement cannot be edited or deleted;
  transfer and graduation update Student and Enrollment state in one locked
  transaction with privacy-safe activity and audit evidence.
* The Phase 6 security review passed at 10/10 with no critical, high, medium,
  dependency, privacy, retention, or authorization findings.

---

# 19. DATA ACCESS POLICY

Data access must follow least privilege.

Role-Based Access:

* Super Admin can access platform-level summaries and authorized school records.
* School Admin can access records for the active school only.
* Teacher can access assigned class/student academic records only.
* Accountant can access fee-related student information only.

Tenant Access:

* Every tenant-owned read must be scoped to `school_id`.
* Every tenant-owned write must validate the active school context.
* Cross-school access is prohibited.

Report and Export Access:

* Reports inherit screen permissions.
* Exports inherit report permissions.
* Exported files must not include fields outside the role's permission.

---

# 20. DATA RETENTION POLICY

The project uses retention-first data handling.

Retention Rules:

* School deactivation is preferred over deletion.
* School deletion means soft deletion only in the MVP.
* Student, teacher, user, and appropriate fee setup records use soft deletes.
* Attendance, payments, transactions, exam results, report cards, activity logs, and audit logs are retained as historical records.
* Audit logs are immutable.
* Financial corrections should use status changes or reversal records, not hard deletion.

Tenant Data Retention:

* Deactivated schools keep tenant data for reports, audits, and recovery.
* Hard deletion of tenant data is outside the MVP.
* Private files remain protected for inactive or soft-deleted records.

---

# 21. PRIVACY CONSIDERATIONS FOR MINORS

Student privacy must be handled carefully because the system stores records for minors.

Rules:

* Student photos must be private.
* DOB and guardian details must only be visible when needed for the user's role.
* Public pages must never expose student personal data.
* Reports should avoid unnecessary DOB, guardian, address, and photo fields.
* Downloaded reports must be protected by the same authorization checks as the UI.
* Demonstration data should use sample students, not real minor data.

---

# 22. MASS ASSIGNMENT PROTECTION

Every Eloquent model must define:

* $fillable

or

* $guarded

Never leave models unprotected.

---

# 23. DATABASE SECURITY

Use:

* Foreign Keys
* Constraints
* Indexes
* Soft Deletes
* Audit Fields

Protect Against:

* Orphan Records
* Invalid References
* Data Corruption

Deletion Rule:

Use `restrictOnDelete()` for foreign keys by default. Do not use cascading deletes for tenant-owned data unless a documented exception is approved in `DECISIONS_LOG.md`.

---

# 24. DELETION SECURITY POLICY

Deletion must preserve auditability and historical reporting.

Rules:

* Soft delete schools.
* Soft delete students.
* Soft delete teachers.
* Soft delete users.
* Soft delete fee categories, fee structures, and student fee assignments where appropriate.
* Preserve fee payments, payment transactions, attendance, exam results, report cards, activity logs, and audit logs.
* Prefer deactivation/status changes over deletion.
* Hard deletion is outside the MVP.

---

# 25. AUDIT TRAIL SECURITY

Audit Records Must Track:

* User
* Module
* Action
* Timestamp
* IP Address
* Old Values
* New Values

Audit logs are immutable.

Users must not modify audit history.

Implementation Status:

* The append-only `audit_logs` recording foundation is implemented for current
  user, school, School Settings, password, and role-permission mapping
  mutations.
* A Super Admin reassignment that moves a user between schools is recorded with
  `school_id = NULL` in explicit Platform context. Neither the source nor target
  school may inherit audit values belonging to the other tenant.
* Audit list/detail screens remain deferred to the documented system-operations
  delivery in Phase 10.

---

# 26. ACTIVITY LOGGING SECURITY

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
* Unauthorized Access Attempts

Implementation Status:

* Login, logout, failed or tenant-denied login, password, user, school, School
  Settings, and role-permission mapping activity recording is implemented
  without storing attempted credentials.
* Anonymous failed-login events use only the narrow trusted-system Platform
  callback approved in DECISION-027; it can append the event but cannot read
  tenant business data and always restores Unresolved context.
* Activity list/detail screens remain deferred to Phase 10.

---

# 27. REPORT SECURITY

Reports must respect:

* Tenant Isolation
* User Permissions
* Data Visibility Rules
* Student Privacy Rules

Never expose:

* Other School Data
* Hidden Fields
* Internal Configuration
* Student Photos
* Unnecessary Minor Data

---

# 28. EXPORT SECURITY

Supported Exports:

* PDF
* Excel
* CSV

Requirements:

* Same permissions as screen view
* Same tenant filters as screen view
* Authorization validation before export
* Privacy review for student DOB, guardian data, mobile numbers, and photos

---

# 29. BACKUP SECURITY

Backup Access:

Super Admin Only

Track:

* Backup User
* Backup Time
* Backup Status
* Backup Size

Backup files must never be publicly accessible.

Backup downloads must pass authorization before file access.

---

# 30. ERROR HANDLING

Never expose:

* Stack Traces
* SQL Queries
* Server Paths
* Environment Variables

Users should receive friendly error messages.

Developers should use logs for diagnostics.

---

# 31. LOGGING POLICY

Logs must never contain:

* Passwords
* API Keys
* Access Tokens
* Secret Keys
* Sensitive Credentials

Log only necessary operational information.

---

# 32. SECURITY TESTING

Required Security Tests:

* Authentication Testing
* Authorization Testing
* Tenant Isolation Testing
* SQL Injection Testing
* XSS Testing
* CSRF Testing
* File Upload Testing
* Session Testing

Security testing is mandatory before release.

---

# 33. SECURITY INCIDENT SEVERITY

Critical

* Tenant Isolation Failure
* Authentication Bypass
* Data Exposure

Major

* Authorization Failure
* Privilege Escalation
* Export Security Failure

Minor

* Validation Gaps
* Logging Issues

Cosmetic

* Security UI Messages

Critical issues must be fixed immediately.

---

# 34. SECURITY REVIEW CHECKLIST

Before Release:

✓ Authentication Works

✓ Authorization Works

✓ Policies Implemented

✓ Tenant Isolation Verified

✓ Input Validation Implemented

✓ Output Escaping Implemented

✓ CSRF Protection Enabled

✓ File Upload Validation Added

✓ Activity Logs Enabled

✓ Audit Logs Enabled

✓ Sensitive Data Protected

✓ Exports Secured

---

# 35. SECURE DEVELOPMENT PRACTICES

Developers must:

* Follow Coding Standards
* Use Form Requests
* Use Policies
* Use Service Layer Architecture
* Review Queries For Tenant Isolation
* Avoid Duplicate Security Logic

Security must be considered during development, not after development.

---

# 36. MCA VIVA QUESTIONS

Be prepared to answer:

* How is authentication implemented?
* How is authorization implemented?
* How is multi-tenancy secured?
* How do you prevent SQL injection?
* How do you prevent XSS?
* How do you secure file uploads?
* How are passwords stored?
* How are audit logs maintained?
* How do you enforce tenant isolation?

---

# 37. SUCCESS CRITERIA

Security implementation is successful when:

✓ Authentication Is Secure

✓ Authorization Is Enforced

✓ Tenant Isolation Works

✓ Input Validation Is Implemented

✓ Output Escaping Is Implemented

✓ File Uploads Are Protected

✓ Activity Logs Are Recorded

✓ Audit Trails Are Maintained

✓ Reports Respect Permissions

✓ Exports Respect Permissions

✓ Security Can Be Explained During Viva

✓ No Critical Security Risks Exist
