# MODULE SPECIFICATIONS

Version: 1.0
Status: Draft

> This document is the **canonical source of truth for the module list (15
> modules)**. README.md, SYSTEM_ARCHITECTURE.md, SCREEN_FLOW.md, and
> PROJECT_OVERVIEW.md must use these exact module names.

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

This document defines all business modules, features, workflows, permissions, screens, reports, validation requirements, and user stories for the application.

It serves as the master functional specification for development, testing, documentation, and MCA project evaluation.

---

# 1. MODULE OVERVIEW

Core Modules:

1. Authentication Module
2. School Management Module
3. School Settings Module
4. User Management Module
5. Role & Permission Module
6. Academic Structure Module
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

# 2. AUTHENTICATION MODULE

Purpose:

Provide secure access to the platform.

Features:

* Login
* Logout
* Forgot Password
* Password Reset
* Session Management
* Profile Management

Screens:

* Login
* Forgot Password
* Reset Password
* My Profile

Accessible By:

All Users

---

# 3. SCHOOL MANAGEMENT MODULE

Purpose:

Manage tenant schools.

Accessible By:

Super Admin

Features:

* Create School
* Edit School
* View School
* Activate School
* Deactivate School
* Search Schools

Screens:

* School List
* School Details
* Create School
* Edit School

Reports:

* Active Schools
* Inactive Schools

---

# 4. SCHOOL SETTINGS MODULE

Purpose:

Manage school-level configuration.

Features:

* School Profile
* Logo Management
* Contact Information
* Academic Settings
* Attendance Settings
* Grading Settings

Screens:

* General Settings
* Academic Settings
* Attendance Settings
* Grading Settings
* Logo Management

Accessible By:

School Admin

---

# 5. USER MANAGEMENT MODULE

Purpose:

Manage system users.

Accessible By:

Super Admin
School Admin

Features:

* Create User
* Edit User
* Reset Password
* Activate User
* Deactivate User
* Assign Role

User Types:

* School Admin
* Teacher
* Accountant

Screens:

* User List
* User Profile
* Create User
* Edit User

Reports:

* User Summary
* Active Users
* Inactive Users

---

# 6. ROLE & PERMISSION MODULE

Purpose:

Control system access.

Roles:

* Super Admin
* School Admin
* Teacher
* Accountant

Features:

* Fixed Canonical Role Directory
* Read-Only Permission Catalog
* Constrained Role-Permission Mapping
* Tenant-Safe Role Assignment
* Permission Validation
* Access Control

MVP Contract:

* The only roles are Super Admin, School Admin, Teacher, and Accountant.
* Role and permission records are system-managed. The UI cannot create, delete,
  rename, or change their stable codes.
* Super Admin can view all mappings and edit non-essential mappings for School
  Admin, Teacher, and Accountant within the canonical maximum boundary.
* The Super Admin mapping is read-only and always contains every permission
  assigned to Super Admin by the canonical matrix.
* School Admin can view effective mappings for assignable school roles and can
  assign School Admin, Teacher, or Accountant only to users in the active
  school. Mapping edits and Super Admin assignment are prohibited.
* Teacher and Accountant have no Role & Permission screens.
* Permissions determine whether an action may be attempted. Tenant context,
  record ownership, assigned-class or assigned-subject limits, and lifecycle
  rules determine which records may be affected.

Implementation Contract:

* `config/rbac.php` is the machine-readable mirror of this canonical matrix and
  supplies the seeder, mapping validator, and mapping-integrity tests.
* The Phase 4 bootstrap synchronization updates existing role-permission rows to
  the documented defaults before permission-aware Policies are enabled.
* Mapping updates replace the selected school role's non-essential mappings in
  one transaction while retaining every essential permission.
* Activity records use module `role_permissions` and action `mapping_updated`.
  Audit records target the Role and store old/new permission-code arrays.
* User role assignment continues through User Management. Its audit contains
  old/new role IDs, and an actual role or school-ownership change clears the
  target user's sessions and remember token.

Security Requirement:

Every protected action must pass authorization checks.

Implementation Status:

* Fixed catalog configuration, immutable Role and Permission models,
  relationships, database-backed permission resolution, seed synchronization,
  current-module Policy integration, and role-change session revocation are
  implemented.
* Role directory, effective-permission details, constrained mapping updates,
  stale-write protection, Platform audit evidence, and read-only School Admin
  screens are implemented. Phase 4 is completed and release-approved.

---

# 7. ACADEMIC STRUCTURE MODULE

Purpose:

Manage academic organization.

Canonical Phase 5 Scope:

* Academic Years
* Academic Terms
* Minimal Teacher Profiles
* Classes
* Sections
* Subjects

"Academic Sessions" means the combined Academic Year and Academic Term
lifecycle. Student Enrollment is part of Student Management in Phase 6.

Features:

Academic Years

* Create Academic Year
* Edit Academic Year
* Activate Academic Year
* Deactivate or Reactivate Non-Current Academic Year

Academic Terms

* Create Terms
* Edit Terms
* Activate or Deactivate Terms
* Reactivate Terms under an active Academic Year

Teacher Profiles

* Link an existing same-school Teacher-role user
* Maintain employee code and academic profile fields
* Activate, Deactivate, Archive, and Restore
* Support Section and Subject assignments only

Classes

* Create Class
* Edit Class

Sections

* Create Section
* Edit Section
* Assign an active same-school Teacher Profile

Subjects

* Create Subject
* Edit Subject
* Assign an active same-school Teacher Profile

Screens:

* Academic Years
* Academic Terms
* Teacher Profiles
* Classes
* Sections
* Subjects

Reports:

* Class Directory
* Section Directory
* Subject Directory

Access Contract:

* Super Admin: read-only Platform-context lists and details across schools when
  `academic.view` is effective. No academic mutation is permitted.
* School Admin: own-school management only. Each action requires its exact
  effective `academic.view`, `academic.create`, `academic.update`,
  `academic.delete`, or `academic.export` permission.
* Teacher: read-only access when `academic.view` is effective, limited to
  Sections and Subjects assigned to their own active Teacher Profile and the
  related Classes. No unassigned directory, mutation, export, Academic Year, or
  Academic Term administration access is permitted.
* Accountant: no Academic Structure screen, menu, service, or route access.

Tenant And Relationship Contract:

* Every Phase 5 model uses `BelongsToTenant`; TenantContext supplies
  `school_id`.
* Requests cannot submit or change tenant ownership.
* Services verify same-school Academic Year, Term, Class, Teacher Profile,
  Section, Subject, and linked User relationships independently of validation.
* Cross-tenant route-model binding returns 404. Forged cross-tenant parent IDs
  fail without revealing record existence.
* A Teacher Profile requires an active, non-deleted, same-school Teacher-role
  User and remains one-to-one with that User.
* The linked User is immutable after profile creation. Archived profiles remain
  retained and prevent a second profile for that User.
* A User linked to any retained Teacher Profile cannot change role or school in
  the MVP.

Lifecycle Contract:

* Academic Years cannot overlap within a school. Activation transactionally
  locks the owning School and its Academic Years, clears the previous current
  flag, and marks one active target current. A current year cannot be
  deactivated, and a non-current year with active Terms cannot be deactivated.
  Reactivation restores active, non-current status.
* Academic Terms must be ordered, non-overlapping, and contained inside their
  parent year. A Term cannot be active or reactivated when its parent year is
  inactive.
* Academic Years and Terms use status lifecycle only; no soft-delete route is
  permitted.
* Classes, Sections, Subjects, and Teacher Profiles prefer deactivation and may
  be archived only when inactive and free of active downstream dependencies.
  Teacher Profile deactivation is also blocked by active Section or Subject
  assignments. Restoration returns the profile inactive after revalidating its
  linked Teacher user.
* Soft-deleted unique names, codes, and employee codes remain reserved;
  restoration reuses the original record.
* `academic.delete` authorizes only the documented deactivation or archive
  transition, never hard deletion.

Activity And Audit Contract:

* Activity module: `academic_structure`.
* Allowed activity actions: `created`, `updated`, `activated`, `deactivated`,
  `archived`, and `restored`.
* Audit records target the mutated model and contain sanitized changed fields.
* Tenant mutations derive log ownership from TenantContext and commit their
  activity and audit records inside the same transaction.

Implementation Boundary:

* Use thin controllers, dedicated Form Requests, Policies, and service-layer
  workflows.
* Canonical models are `AcademicYear`, `AcademicTerm`, `Teacher`, `SchoolClass`,
  `Section`, and `Subject`. `SchoolClass` maps to `classes`; Teacher Profile is
  the UI label for `Teacher`.
* Student registration, Student Enrollment, attendance, fees, examinations,
  timetable, HR, payroll, and staff attendance remain out of scope.
* No Phase 5 route becomes active solely because an `academic.*` permission
  exists.

Implementation Status:

* The six-table schema, canonical tenant-aware models, inverse relationships,
  casts, soft-delete boundaries, and retained Teacher Profile user identity
  safeguard are implemented.
* Academic Year, Academic Term, Teacher Profile, Class, Section, and Subject
  services, Policies, Form Requests, controllers, routes, menus, views,
  lifecycle rules, and mutation activity/audit workflows are implemented.
* Teacher Class reads are limited to active related Classes and the actor's own
  active Section or Subject assignments.
* Teacher Section and Subject reads are limited to the actor's own active
  assignments under active Classes.
* Academic Structure exports remain deferred to the reporting phase.

---

# 8. STUDENT MANAGEMENT MODULE

Purpose:

Manage student information.

Features:

* Student Registration
* Student Profiles
* Enrollment Management
* Initial Class and Section Assignment through Enrollment
* Status Management
* Private Student Photo Management

Screens:

* Student List
* Student Registration
* Student Profile
* Student Search
* Student Enrollment History

Reports:

Student Reports and exports are implemented in Phase 10 Reporting, not in the
Phase 6 Student Management module. Phase 6 provides only authorized list and
search views.

Access:

* School Admin: full own-school Student and Enrollment management, including
  private photo management.
* Teacher: read-only, privacy-minimized access to active enrolled Students
  reachable through the Teacher's own active Section assignment, or an active
  Subject assignment for the Student's Class. Teachers cannot create, update,
  archive, enroll, or access Student photos.
* Super Admin: read-only, privacy-minimized access only after explicit Platform
  authorization and selection of a school. No Platform mutation or Student
  photo access is permitted.
* Accountant: no direct Student Management menu or route in Phase 6. Limited
  Student lookup begins only inside authorized Fee Management workflows in
  Phase 8.

Phase 6 Enrollment Boundary:

* One immutable Enrollment placement exists per Student and Academic Year.
* Enrollment owns the canonical roll number, Class, and Section assignment.
* Internal Class or Section reassignment, promotion, and mid-year transfer are
  deferred until an approved enrollment-history design exists.
* A transferred Student means departure from the current school only. The MVP
  does not transfer data between tenants.

Implementation Status:

* Student registration, profile updates, search, state filters, activation,
  deactivation, archival, restoration, and read-only Enrollment history are
  implemented for authorized School Admins.
* Teacher assigned-record and Super Admin school-selected reads use
  privacy-minimized projections. Accountant has no direct route.
* Private Student photo upload, replacement, removal, archival retention, and
  protected School Admin-only delivery are implemented on private storage.
* Enrollment creation and retained completion are implemented. Student transfer
  atomically transfers the active Enrollment; graduation atomically completes
  it. Phase 6 is release-approved.
* `students.create` authorizes Student registration and initial Enrollment
  creation. `students.update` authorizes Enrollment completion and the coupled
  Student transfer/graduation actions. Policies and Services additionally
  enforce School Admin role, tenant ownership, and lifecycle state.

---

# 9. ATTENDANCE MANAGEMENT MODULE

Purpose:

Track student attendance.

Phase 7 Features:

* Daily Attendance
* Bulk Attendance Entry
* Attendance History
* Attendance Search
* Monthly Operational Summary
* Authorized Correction of Retained Attendance

Attendance Status:

* Present
* Absent
* Leave
* Late
* Holiday

Phase 7 Screens:

* Attendance Entry
* Attendance History
* Monthly Attendance Summary

Phase Boundary:

* Phase 7 does not create report, export, analytics, dashboard-summary, or
  Super Admin Attendance routes.
* Attendance reports, exports, trends, and platform summaries belong to Phase 10
  Reporting, Analytics & System Operations.
* Operational history and monthly on-screen summaries use `attendance.view` and
  are not report generation.

Phase 7 Access:

| Role | Access |
| ---- | ------ |
| School Admin | View and create Attendance for eligible Students in every active Section of the current school. May correct retained current-year own-school records even when a Student, Enrollment, Class, or Section later becomes inactive or terminal. School Admin alone may mark an active Section roster as holiday. |
| Teacher | View, create, and correct Attendance only for an active Section directly assigned to the actor's active Teacher Profile through `sections.teacher_id`. Subject assignment alone grants no Attendance access. |
| Super Admin | No Phase 7 route or menu. Platform Attendance reports begin in Phase 10. |
| Accountant | No Attendance route or menu. |

Attendance Rules:

* New rows use the active current Academic Year and an active same-tenant Class,
  Section, Student, and matching active Student Enrollment.
* The server derives Class, Section, Academic Year, and school ownership from the
  selected Section and eligible Enrollment roster; request data cannot override
  placement or `school_id`.
* Attendance date must be inside the Academic Year, on or after Enrollment date,
  and no later than the current date in the school's configured timezone.
* One retained row exists per school, Student, and attendance date.
* Bulk saves require exactly one allowed status for every eligible roster member,
  reject duplicate, omitted, or extraneous Student IDs, and commit atomically.
* Repeated saves create missing eligible rows and correct existing rows under
  create/update permissions. Unchanged rows do not create duplicate evidence.
* Attendance rows are never deleted. Corrections change only status and remarks
  and preserve privacy-safe audit history.
* `marked_by` stores the original authenticated creator and is immutable.
  Correction actors are recorded by audit-log user identity.
* Holiday is a School Admin-only bulk status for the full eligible Section
  roster. Transitions both to and from Holiday must use the atomic complete-
  roster workflow; Teachers cannot overwrite Holiday and single-record
  correction cannot change a Holiday status. Late is selected manually;
  `attendance_start_time` is a reference value and does not classify a Student
  automatically in the MCA scope.
* Remarks are optional, limited to 500 characters, and must not contain detailed
  medical, disability, or other unnecessary minor information.

Implementation Status (2026-06-22):

* The Phase 7 operational entry, correction, retained history, search, monthly
  summary, authorization, tenant isolation, privacy-safe logging, navigation,
  and Bootstrap UI workflows are implemented. Tenant-isolation, security, and
  documentation reviews are approved at 10/10. Code-review remediation preserves
  lifecycle-changed retained roster rows, validates direct corrections, and
  bounds History authorization queries. The code-review rerun is approved; only
  the release gate remains.

---

# 10. FEE MANAGEMENT MODULE

Purpose:

Manage fee collection and tracking.

Features:

* Fee Categories
* Fee Structures
* Fee Assignment
* Fee Collection
* Receipt Generation
* Payment Tracking
* Outstanding Balance Views
* Local Sandbox Transactions

Screens:

* Fee Categories
* Fee Structures
* Student Fees
* Fee Collection
* Receipts
* Payment History
* Outstanding Balances

Reports:

Fee reports, exports, analytics, dashboard widgets, and platform summaries are
deferred to Phase 10 Reporting. Phase 8 provides operational payment history and
outstanding balance views only.

Accessible By:

* School Admin: full own-school setup, assignment, collection, receipt, payment
  history, outstanding balance, and sandbox transaction workflows.
* Accountant: own-school Student Fee lookup, collection, receipt, payment
  history, outstanding balance, and sandbox transaction workflows only.
* Super Admin: no Phase 8 Fee route; platform Fee reports are deferred to Phase
  10.
* Teacher: no Fee Management access.

Implementation Boundary:

* Accountant cannot create, update, delete, archive, waive, cancel, or configure
  Fee Categories, Fee Structures, or Student Fee assignments.
* `fees.report` and `fees.export` remain dormant catalog permissions in Phase
  8.
* `fees.delete` cannot authorize payment or transaction deletion. Setup
  deactivation, waiver, or cancellation must preserve retained financial
  history and audit evidence.

Implementation Status (2026-06-23):

The Phase 8 core Fee migration and tenant-aware model foundation are
implemented. The foundation includes Fee Categories, Fee Structures, Student
Fees, Fee Payments, Payment Transactions, canonical statuses and payment modes,
money/date casts, inverse relationships, automatic tenant ownership, immutable
scope/financial identity safeguards, setup soft deletes, retained Payment and
Transaction deletion guards, and focused schema/isolation tests.

School Admin Fee Category list/create/update/show, activate/deactivate,
Fee Structure list/create/update/show, activate/deactivate, and Student Fee
assignment list/create/show workflows are implemented with policies, Form
Requests, services, Bootstrap views, sidebar navigation, activity logs, audit
logs, and tenant isolation tests. Assignment amounts, payable, paid, and balance
values are server-derived; forged amount and payable fields are rejected.
Accountant, Teacher, Super Admin, guest, and cross-tenant actors are denied on
setup and assignment routes.

Collection, receipt, payment-history, outstanding-balance, and sandbox transaction
operational screens are implemented with focused tenant isolation tests. Phase 8
release gate reviews remain pending.

---

# 11. EXAMINATION MANAGEMENT MODULE

Purpose:

Manage examinations and academic results.

Features:

* Exam Creation
* Subject Assignment
* Marks Entry
* Grade Calculation
* Result Processing
* Report Card Generation

Screens:

* Exam List
* Marks Entry
* Results
* Report Cards

Grade Scale:

* A+
* A
* B+
* B
* C
* D
* F

Reports:

* Pass/Fail Analysis
* Grade Distribution
* Top Performers
* Result Summary

Accessible By:

School Admin
Teacher

---

# 12. REPORTING MODULE

Purpose:

Provide operational reports.

Categories:

* Student Reports
* Attendance Reports
* Fee Reports
* Examination Reports
* Audit Reports

Features:

* Filters
* Search
* Export
* Pagination

Export Formats:

* PDF
* Excel

---

# 13. DASHBOARD & ANALYTICS MODULE

Purpose:

Provide real-time operational insights.

Dashboard Components:

* Statistics Cards
* Charts
* Alerts
* Recent Activity
* Quick Actions

Analytics:

* Student Growth
* Attendance Trends
* Fee Collection Trends
* Academic Performance

---

# 14. ACTIVITY LOG MODULE

Purpose:

Track user activities.

Track:

* Login
* Logout
* Create
* Update
* Delete
* Attendance
* Fees
* Examinations
* Reports

Screens:

* Activity Logs
* Activity Details

Accessible By:

* Super Admin: platform-wide activity logs
* School Admin: own school activity logs

---

# 15. AUDIT TRAIL MODULE

Purpose:

Track data modifications.

Track:

* Old Values
* New Values
* User
* Timestamp
* IP Address
* User Agent

Screens:

* Audit Logs
* Audit Details

Requirement:

Audit records are immutable.

Accessible By:

* Super Admin: platform-wide audit logs
* School Admin: own school audit logs

---

# 16. BACKUP MANAGEMENT MODULE

Purpose:

Manage backup operations.

Features:

* Manual Backup
* Backup History
* Backup Logs

Screens:

* Backup Dashboard
* Backup History

Accessible By:

Super Admin

---

# 17. DASHBOARD SPECIFICATIONS

Dashboard visibility is role-specific.

## Super Admin Dashboard

Visible Widgets:

* Total Schools
* Active Schools
* Total Users
* Recent Platform Activity
* Audit Alerts
* Backup Status

## School Admin Dashboard

Visible Widgets:

* Total Students
* Attendance Summary
* Fee Summary
* Examination Summary
* Recent School Activity

## Teacher Dashboard

Visible Widgets:

* Assigned Classes
* Today's Attendance
* Pending Marks Entry
* Recent Examination Results

## Accountant Dashboard

Visible Widgets:

* Today's Fee Collections
* Outstanding Fees
* Recent Transactions
* Receipt Summary

---

# 18. CANONICAL ROLE PERMISSION MATRIX

This matrix is the canonical permission source for the MVP. `SCREEN_FLOW.md`, `PROJECT_CONSTITUTION.md`, and `PROJECT_OVERVIEW.md` must match this table.

Permission Levels:

* Full: view, create, update, deactivate or soft delete where allowed, export where applicable.
* View: read-only access.
* Limited: restricted to assigned classes, fee workflows, or role-specific reports.
* No: no direct module access.

Matrix Semantics:

* For Super Admin, the listed access is immutable. Permissions outside the
  listed access remain unassigned even though they exist in the shared catalog.
* For School Admin, Teacher, and Accountant, the matrix defines the default and
  maximum permission set. A Super Admin may revoke or restore only non-essential
  permissions inside that set and cannot grant access where the matrix says No.
* Limited and assigned access always requires a Policy or service to enforce the
  relevant record boundary; a permission code alone is insufficient.

Permission-Level Translation:

* Own profile: `profile.view` and `profile.update`.
* Role-specific dashboard: `dashboard.view`; `analytics.view` is included only
  where that dashboard exposes approved analytics.
* Full: every canonical action code for the module.
* View: the module `.view` code only.
* View reports: the module `.report` code only.
* Platform, school, assigned-class, or financial reports: `reports.view` and
  `reports.export`, with Policy and tenant restrictions.
* Manage canonical mappings and role assignment: `roles.view`, `roles.assign`,
  and `roles.manage`.
* View mappings and assign school roles: `roles.view` and `roles.assign`.
* Limited student view: `students.view` with record-scope enforcement.
* No: no permission code from that module.

For retained-data workflows, a `.delete` permission authorizes the documented
deactivation or soft-delete action only; it never authorizes hard deletion.
Reactivation uses the corresponding `.update` permission plus Policy checks.

| Module | Super Admin | School Admin | Teacher | Accountant |
| ------ | ----------- | ------------ | ------- | ---------- |
| Authentication & Profile | Own profile | Own profile | Own profile | Own profile |
| Dashboard & Analytics | Platform dashboard | School dashboard | Teacher dashboard | Accountant dashboard |
| School Management | Full | No | No | No |
| School Settings | View via School Details | Full | No | No |
| User Management | Full | Full for own school | No | No |
| Role & Permission | Manage constrained canonical mappings and role assignment | View mappings and assign school roles | No | No |
| Academic Structure | View | Full | View assigned classes and subjects | No |
| Student Management | View | Full | Limited view for assigned classes | Limited view for fee collection |
| Attendance Management | View reports | Full | Full for directly assigned Sections | No |
| Fee Management | Phase 10 reports only | Full own-school operations | No | Collection, receipts, payment history, and outstanding balances only |
| Examination Management | View reports | Full | Full for assigned classes and subjects | No |
| Reporting | Platform reports | School reports | Assigned class reports | Financial reports |
| Activity Logs | Full | View own school logs | No | No |
| Audit Trail | Full | View own school logs | No | No |
| Backup Management | Full | No | No | No |

Phase Availability:

* The matrix defines the maximum authorized permission set; it does not create a
  route, menu item, or feature before its roadmap phase.
* In Phase 6, `students.export` remains a catalog permission only. Student
  exports and reports become operational in Phase 10 Reporting.
* In Phase 6, Super Admin `students.view` is a read-only school-scoped
  privacy-minimized projection, Teacher `students.view` is assigned-record only,
  and Accountant `students.view` has no direct menu or route until Phase 8 Fee
  Management.
* In Phase 7, only `attendance.view`, `attendance.create`, and
  `attendance.update` become operational for School Admin and assigned-Section
  Teacher workflows. `attendance.delete`, `attendance.report`, and
  `attendance.export` remain catalog permissions with no Phase 7 route. Super
  Admin report access begins only in Phase 10.

## Essential Permission Rules

| Role | Locked Permissions | Reason |
| ---- | ------------------ | ------ |
| Super Admin | Every permission in the Super Admin matrix-approved set | Prevent platform lockout and preserve recovery authority without exceeding the canonical role boundary. |
| School Admin | `profile.view`, `profile.update`, `dashboard.view`, `users.view`, `users.update`, `roles.view`, `roles.assign` | Preserve own-account access and the documented tenant user/role-assignment responsibility. |
| Teacher | `profile.view`, `profile.update`, `dashboard.view` | Preserve own-account and dashboard access. |
| Accountant | `profile.view`, `profile.update`, `dashboard.view` | Preserve own-account and dashboard access. |

The permission catalog is fixed for the MVP. `roles.manage` is exclusive to the
Super Admin role and cannot be removed. `roles.assign` permits role assignment
only when User Policy, tenant context, assignable-role rules, and self-change
protections also pass.

The Phase 2 bootstrap seeder temporarily assigned the complete permission
catalog to Super Admin before database-backed authorization existed. Phase 4
must replace that bootstrap mapping with the matrix-approved immutable set before
permissions become authoritative. No later-phase route is activated merely
because its permission record exists.

## Permission Reconciliation Notes

* Backup Management is Super Admin only.
* Audit Logs are available to Super Admin platform-wide and School Admin for the active school only.
* Reports must never expose data beyond the user's permitted module and tenant scope.
* Phase 8 Fee setup and assignment screens are School Admin-only.
* Phase 8 Fee collection, receipts, payment history, and outstanding balance
  screens are available to School Admin and Accountant inside the active tenant.
* Super Admin Fee reports, Fee exports, analytics, and platform summaries remain
  Phase 10 work. Teachers do not access Fee Management.
* Dashboard widgets must only summarize data that the role is permitted to view.
* Only School Settings is included as a settings module in the MVP.

---

# 19. MODULE IMPLEMENTATION PRIORITY

The implementation order follows the canonical phase sequence in
`DEVELOPMENT_ROADMAP.md`. To avoid conflicting roadmaps, this section maps modules
to those phases rather than defining its own sequence:

| Roadmap Phase | Modules |
| ------------- | ------- |
| Phase 2 — Authentication & User Management | Authentication, User Management |
| Phase 3 — Multi-Tenant Foundation | School Management, School Settings |
| Phase 4 — Roles & Permissions | Role & Permission Management |
| Phase 5 — Academic Structure | Academic Structure |
| Phase 6 — Student Management | Student Management |
| Phase 7 — Attendance Management | Attendance Management |
| Phase 8 — Fee Management | Fee Management |
| Phase 9 — Examination Management | Examination Management |
| Phase 10 — Reports, Analytics & System Operations | Reporting, Dashboard & Analytics, Activity Logs, Audit Trail, Backup Management |
| Phase 11 — Testing & QA | All implemented modules (verification only) |

Activity logging and audit trail are implemented incrementally alongside each
module. Their review screens and Backup Management workflow are delivered with
Phase 10 system operations, then all modules are finalized during Phase 11
testing.

---

# 20. MODULE COMPLETION CRITERIA

A module is considered complete when:

✓ CRUD Operations Implemented

✓ Validation Implemented

✓ Authorization Implemented

✓ Tenant Isolation Verified

✓ Activity Logging Enabled

✓ Reports Generated when the roadmap phase owns reporting, or explicitly
deferred to Phase 10

✓ Screenshots Captured

✓ Test Cases Written

✓ Documentation Updated

---

# 21. FINAL FUNCTIONAL GOAL

Deliver a complete, secure, multi-tenant School Administration Management SaaS Platform that demonstrates modern software engineering practices, supports real-world educational workflows, and satisfies MCA project requirements while remaining maintainable, scalable, and easy to explain during project evaluation and viva.
