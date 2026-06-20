# TESTING STRATEGY

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

Architecture:
Single Database Multi-Tenant SaaS

Purpose:

Define the testing approach, quality assurance process, validation strategy, defect management process, and acceptance criteria for the project.

This document serves as the official testing reference throughout the software development lifecycle.

---

# 1. TESTING OBJECTIVES

Testing must ensure:

* Functional Correctness
* Security
* Reliability
* Performance
* Tenant Isolation
* Data Integrity
* Usability
* User Acceptance

The objective is to validate that the application satisfies all project requirements before MCA final submission.

---

# 2. TESTING PHILOSOPHY

Testing is not a final activity.

Testing begins during development.

Every feature must be:

Design
→ Develop
→ Test
→ Verify
→ Document

before being considered complete.

---

# 3. TESTING LEVELS

The project will implement:

1. Unit Testing
2. Feature Testing
3. Integration Testing
4. System Testing
5. User Acceptance Testing (UAT)
6. Security Testing
7. Performance Testing
8. Tenant Isolation Testing

---

# 4. TESTING LIFECYCLE

Requirements
↓
Design
↓
Development
↓
Unit Testing
↓
Feature Testing
↓
Integration Testing
↓
System Testing
↓
User Acceptance Testing
↓
Release Approval

---

# 5. UNIT TESTING

Purpose:

Validate individual classes and business logic in isolation.

Scope:

* Services
* Helpers
* Policies
* Form Requests
* Business Rules

Examples:

* AttendanceService
* FeeService
* ExamService
* ReportService
* GradeCalculationService

Expected Outcome:

Each component behaves correctly in isolation.

---

# 6. FEATURE TESTING

Purpose:

Validate application features from the user perspective.

Scope:

* Authentication
* Authorization
* Student Management
* Attendance
* Fees
* Examinations
* Reporting

Expected Outcome:

Features behave correctly through HTTP requests and responses.

---

# 7. INTEGRATION TESTING

Purpose:

Validate interaction between modules.

Examples:

Student → Enrollment

Student → Attendance

Student → Fee Assignment

Student → Examination

Fee Payment → Receipt

Reports → Analytics

Expected Outcome:

Modules communicate correctly and data remains consistent.

---

# 8. SYSTEM TESTING

Purpose:

Validate complete end-to-end workflows.

Example Workflow:

Create School
↓
Create User
↓
Create Student
↓
Enroll Student
↓
Mark Attendance
↓
Assign Fees
↓
Collect Fees
↓
Create Examination
↓
Enter Marks
↓
Generate Report Card

Expected Outcome:

Entire business process executes successfully.

---

# 9. USER ACCEPTANCE TESTING (UAT)

Purpose:

Verify usability and business requirements.

Participants:

* School Admin
* Teacher
* Accountant
* Project Evaluator

Evaluation Areas:

* Ease of Use
* Navigation
* Reporting
* Performance
* Data Accuracy

Expected Outcome:

Users can complete business tasks without assistance.

---

# 10. SECURITY TESTING

Purpose:

Validate application security controls.

Areas Covered:

* Authentication
* Authorization
* Session Security
* Password Security
* CSRF Protection
* Input Validation
* Output Escaping
* File Upload Security
* Tenant Isolation

Expected Outcome:

No unauthorized access or privilege escalation.

---

# 11. TENANT ISOLATION TESTING

Purpose:

Validate multi-tenant security enforced by the automatic global scope
(BelongsToTenant trait + TenantContext middleware). See `TENANCY_DESIGN.md` §10.

Scenarios:

* School A cannot list, search, view, route-bind, update, deactivate, or delete
  School B tenant-owned records.
* Cross-tenant route-model binding returns 404 without exposing existence.
* Creating a strict tenant-owned record derives `school_id` from Tenant context
  and rejects or ignores a forged tenant id.
* Unresolved context cannot read or create tenant-owned data.
* A validated Super Admin receives explicit Platform context; malformed users
  cannot obtain platform access.
* Inactive, missing, or soft-deleted schools cannot log in or continue sessions.
* Deactivation revokes school-user sessions and remember tokens while retaining
  users and tenant data.
* Sequential requests, exceptions, queued jobs, console commands, and repeated
  tests do not leak context.
* Tenant jobs require a trusted school id; platform jobs and commands opt into
  Platform context explicitly.
* The global scope filters automatically without an explicit per-query
  `where('school_id')` clause.

Expected Result:

Access denied for cross-tenant attempts. No data exposure.

Critical Requirement:

Tenant Isolation must pass 100%.

Failure of tenant isolation is considered a Critical defect.

---

# 12. PERFORMANCE TESTING

Purpose:

Validate responsiveness and user experience.

Performance Targets:

Dashboard Load:
< 3 Seconds

Student Search:
< 2 Seconds

Attendance Reports:
< 5 Seconds

Fee Reports:
< 5 Seconds

Examination Reports:
< 5 Seconds

Analytics Dashboard:
< 5 Seconds

---

# 13. TEST ENVIRONMENT

Environment:

Development

Technology Stack:

* Laravel 13 (installed)
* PHP 8.4
* MySQL 8
* Bootstrap 5 (installed)
* Laravel Breeze (installed)
* Native Laravel Multi-Tenancy (school_id + Global Scopes)

Browsers:

* Google Chrome
* Microsoft Edge
* Mozilla Firefox
* Safari

---

# 14. TEST DATA STRATEGY

Seed Data:

* 1 Demo School
* 1 School Admin
* 2 Teachers
* 1 Accountant
* 20 Students
* 5 Classes
* 10 Sections
* Subjects
* Fee Categories
* Examinations
* Attendance Records
* Fee Payments

Purpose:

Provide realistic testing scenarios.

---

# 15. AUTHENTICATION TEST CASES

Validate:

* Login
* Logout
* Invalid Credentials
* Password Reset
* Session Expiry
* Unauthorized Access

Expected Result:

Authentication functions correctly.

---

# 16. AUTHORIZATION TESTING

Validate:

* Super Admin Access
* School Admin Access
* Teacher Access
* Accountant Access

Verify:

* Menus
* Permissions
* Actions
* Reports

Expected Result:

Role restrictions work correctly.

Phase 4 Required Coverage:

* The four role rows and permission catalog remain fixed and seed idempotently.
* The Super Admin mapping contains its exact matrix-approved set, excludes
  out-of-bound operational permissions, and rejects all edits.
* School-role mappings reject essential-permission removal and grants outside
  the canonical maximum.
* An authorized Super Admin in Platform context can revoke and restore an
  allowed non-essential permission transactionally.
* School Admin can view effective school-role mappings but cannot edit them,
  access Super Admin mapping data, assign Super Admin, or cross tenant boundaries.
* Teacher, Accountant, guests, malformed users, and incorrect TenantContext
  states are denied every management route and direct service call.
* Policies require both permission and tenant or record scope.
* Sidebar links, dashboard widgets, direct URLs, controllers, Form Requests,
  Policies, and services agree after a mapping change.
* Forged IDs, duplicate mappings, stale form submissions, and empty payloads fail
  safely without partial writes.
* Mapping and role-assignment changes create sanitized activity and audit logs.
* User role changes revoke only the target user's sessions and remember token.
* A forced logging failure rolls back the mapping or role assignment.

---

# 17. STUDENT MODULE TESTING

Validate:

* Create Student
* Edit Student
* View Student
* Search Student
* Student Enrollment
* Student Reports

Expected Result:

Student lifecycle functions correctly.

---

# 18. ATTENDANCE MODULE TESTING

Validate:

* Attendance Entry
* Attendance Update
* Attendance Reports
* Monthly Attendance
* Attendance Analytics

Expected Result:

Attendance calculations remain accurate.

---

# 19. FEE MANAGEMENT TESTING

Validate:

* Fee Categories
* Fee Structures
* Fee Assignment
* Fee Collection
* Receipt Generation
* Outstanding Fee Calculation

Expected Result:

Financial data remains accurate.

---

# 20. EXAMINATION MODULE TESTING

Validate:

* Exam Creation
* Subject Assignment
* Marks Entry
* Grade Calculation
* Result Processing
* Report Card Generation

Expected Result:

Academic calculations remain correct.

---

# 21. REPORTING TESTING

Validate:

* Student Reports
* Attendance Reports
* Fee Reports
* Examination Reports
* Audit Reports

Verify:

* Filters
* Pagination
* Data Accuracy
* Export Functionality

Expected Result:

Reports reflect accurate data.

---

# 22. DASHBOARD & ANALYTICS TESTING

Validate:

* Statistics Cards
* Charts
* Dashboard Metrics
* Trend Calculations

Expected Result:

Dashboard values match database values.

---

# 23. ACTIVITY LOG TESTING

Validate:

* Login Logging
* Logout Logging
* Create Events
* Update Events
* Delete Events
* Report Generation Events

Expected Result:

Activities are tracked correctly.

---

# 24. AUDIT LOG TESTING

Validate:

* Old Values
* New Values
* User Information
* Timestamp
* IP Address

Expected Result:

Audit history is accurate and immutable.

---

# 25. FILE UPLOAD TESTING

Validate:

* Student Photos
* School Logos

Test:

* Allowed File Types
* Invalid File Types
* Oversized Files
* Duplicate Uploads

Expected Result:

Only valid files are accepted.

---

# 26. DATABASE TESTING

Validate:

* Foreign Keys
* Constraints
* Relationships
* Soft Deletes
* Indexes

Expected Result:

Database integrity is maintained.

---

# 27. UI TESTING

Validate:

* Forms
* Tables
* Navigation
* Alerts
* Responsive Layout

Verify:

* Consistency
* Accessibility
* Readability

Expected Result:

Professional and usable interface.

---

# 28. RESPONSIVE TESTING

Devices:

* Mobile
* Tablet
* Desktop

Verify:

* Navigation
* Forms
* Tables
* Dashboards

Expected Result:

Consistent user experience across devices.

---

# 29. ERROR HANDLING TESTING

Validate:

* Validation Errors
* 403 Pages
* 404 Pages
* 500 Errors

Expected Result:

User-friendly messages.

No sensitive information exposed.

---

# 30. DEFECT SEVERITY LEVELS

Critical

* Tenant Isolation Failure
* Authentication Failure
* Data Loss

Major

* Module Functionality Failure
* Incorrect Calculations

Minor

* Validation Issues
* Layout Issues

Cosmetic

* Visual Inconsistencies

Critical defects must be resolved before release.

---

# 31. DEFECT TRACKING FORMAT

Fields:

* Bug ID
* Module
* Description
* Severity
* Priority
* Status
* Assigned To
* Resolution

---

# 32. ACCEPTANCE CRITERIA

A module is accepted when:

✓ Functional Testing Passed

✓ Authorization Testing Passed

✓ Tenant Isolation Passed

✓ UI Testing Passed

✓ Validation Testing Passed

✓ No Critical Defects

---

# 33. RELEASE READINESS CHECKLIST

✓ Authentication Tested

✓ Authorization Tested

✓ Student Module Tested

✓ Attendance Module Tested

✓ Fee Management Tested

✓ Examination Module Tested

✓ Reporting Tested

✓ Dashboard Tested

✓ Activity Logs Tested

✓ Audit Logs Tested

✓ Tenant Isolation Tested

✓ Security Verified

---

# 34. MCA TESTING EVIDENCE

## Phase 2 Completion Baseline

Recorded on 2026-06-20:

* Phase 2 feature suite: 50 tests and 191 assertions passed.
* Full application suite: 52 tests and 193 assertions passed.
* Covered login, logout, password reset, email verification, password and
  profile updates, dashboard access, role-aware navigation, user-management
  allowed and denied paths, validation, role restrictions, deactivation, and
  cross-school user access denial.
* The automatic tenant-context and global-scope test suite remains a Phase 3
  requirement; Phase 2 verifies the implemented policy and service-layer school
  boundaries only.

## Phase 3 Core Tenant Context Baseline

Recorded on 2026-06-20:

* Full application suite: 68 tests and 256 assertions passed.
* TenantContext unit tests cover Unresolved, Tenant, Platform, reset, invalid
  tenant id, and tenant-id access behavior.
* Middleware feature tests cover school and platform setup, request cleanup,
  exception cleanup, sequential schools, malformed users, inactive and deleted
  schools, login denial, and middleware ordering before route-model binding.
* `TenantScope`, `BelongsToTenant`, and strict tenant-owned model isolation tests
  remain required by the next Phase 3 implementation prompt.

## Phase 3 Tenant Scope Baseline

Recorded on 2026-06-20:

* Full application suite: 76 tests and 285 assertions passed.
* School Settings isolation tests cover Unresolved deny-all reads, rejected
  Unresolved and Platform creates, Tenant filtering, Platform reads, forged
  school-id overwrite, immutable ownership, sequential tenants, and
  cross-tenant route-model binding as 404.
* The additive School Settings migration ran successfully with its one-to-one
  unique constraint, index, and `restrictOnDelete()` foreign key.
* School Management lifecycle and authorization tests were the next Phase 3
  requirement and are recorded in the baseline below.

## Phase 3 School Management Baseline

Recorded on 2026-06-20:

* Full application suite: 98 tests and 441 assertions passed.
* School Management tests cover guest protection, denied School Admin, Teacher,
  and Accountant paths, Super Admin listing, search, status filtering,
  registration, editing, validation, and role-aware navigation.
* Lifecycle tests prove required deactivation reasons, selective remember-token
  and database-session revocation, retained tenant records, next-request logout,
  reactivation behavior, invalid-transition denial, and the absence of a normal
  delete endpoint.
* TenantContext unit tests prove temporary tenant execution restores Unresolved,
  Tenant, and Platform state, including after callback exceptions.
* User Management regression coverage proves inactive schools cannot receive
  new user assignments.
* User Management tests prove Super Admin can manage verification platform-wide,
  while a verified School Admin can manage it only for other users in the same
  school and cannot use administrative attestation to self-verify.

## Phase 3 School Settings Baseline

Recorded on 2026-06-20:

* Full application suite: 110 tests and 529 assertions passed.
* School Settings tests cover missing-row initialization, School Admin profile
  and operating-settings updates, prohibited tenant/platform fields, role denial,
  Super Admin read-only visibility, and permission-aware navigation.
* Validation tests cover schema lengths, unique school email, timezone, currency,
  academic month, attendance time, grading system, website, and logo controls.
* Public-storage tests prove MIME and size rejection, generated tenant-partitioned
  filenames, replacement cleanup, removal cleanup, and preservation of another
  school's logo.
* Existing automatic TenantScope and School Management lifecycle suites remain
  green.

## Phase 3 Tenant-Isolation Review Baseline

Recorded on 2026-06-21:

* Full application suite: 115 tests and 553 assertions passed.
* Direct service tests prove User Management and Dashboard workflows reject
  Unresolved context, mismatched tenant context, school actors in Platform mode,
  and Super Admin actors in Tenant mode.
* Matching Tenant and Platform tests prove authorized service execution retains
  school isolation and platform-wide behavior respectively.
* User Management HTTP, Dashboard HTTP, TenantContext, TenantScope, School
  Management, and School Settings regression suites remain green.

## Phase 3 Security Remediation Baseline

Recorded on 2026-06-21:

* Full application suite: 132 tests and 655 assertions passed.
* Canonical password-policy tests cover user creation, administrative reset,
  profile change, and forgot-password reset.
* Security tests prove administrator self-reset denial, current-password
  confirmation, target-only session revocation, generic and throttled recovery,
  CSRF rejection, Blade escaping, and bound search input.
* Activity and audit tests prove login/logout and current mutation coverage,
  tenant filtering, explicit Platform visibility, sensitive-value removal,
  Unresolved denial, and Eloquent immutability.
* Cross-school user reassignment tests prove transition history is Platform-only
  and invisible in both the source and target Tenant contexts.
* Transaction-failure tests prove password, remember-token, session, and
  security-log changes roll back together when logging cannot be persisted.
* Composer and npm security advisory checks reported no known vulnerabilities.

Capture:

* Test Cases
* Test Results
* Screenshots
* Validation Reports
* Performance Results
* Security Verification

These artifacts will be included in:

Chapter 6 – Testing & Validation

of the MCA Project Report.

---

# 35. VIVA PREPARATION QUESTIONS

Be prepared to answer:

* Why is testing important?
* What is Unit Testing?
* What is Feature Testing?
* What is Integration Testing?
* What is System Testing?
* What is UAT?
* How was multi-tenancy tested?
* How was security tested?
* How were reports validated?
* How was tenant isolation verified?

---

# 36. SUCCESS CRITERIA

Testing is successful when:

✓ All Modules Tested

✓ No Critical Defects

✓ Security Controls Verified

✓ Tenant Isolation Verified

✓ Reports Verified

✓ Performance Acceptable

✓ Documentation Complete

✓ Ready For MCA Evaluation

✓ Ready For Project Demonstration
