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
  old/new role IDs, and an actual role change clears the target user's sessions
  and remember token.

Security Requirement:

Every protected action must pass authorization checks.

---

# 7. ACADEMIC STRUCTURE MODULE

Purpose:

Manage academic organization.

Features:

Academic Years

* Create Academic Year
* Activate Academic Year

Academic Terms

* Create Terms
* Manage Terms

Classes

* Create Class
* Edit Class

Sections

* Create Section
* Assign Teacher

Subjects

* Create Subject
* Assign Subject

Screens:

* Academic Years
* Academic Terms
* Classes
* Sections
* Subjects

Reports:

* Class Directory
* Section Directory
* Subject Directory

---

# 8. STUDENT MANAGEMENT MODULE

Purpose:

Manage student information.

Features:

* Student Registration
* Student Profiles
* Enrollment Management
* Class Assignment
* Section Assignment
* Student Transfer
* Status Management

Screens:

* Student List
* Student Registration
* Student Profile
* Student Search

Reports:

* Student Directory
* Class-wise Students
* Section-wise Students
* Gender-wise Students
* Active Students

Accessible By:

School Admin
Teacher

---

# 9. ATTENDANCE MANAGEMENT MODULE

Purpose:

Track student attendance.

Features:

* Daily Attendance
* Bulk Attendance Entry
* Attendance History
* Attendance Search
* Monthly Attendance

Attendance Status:

* Present
* Absent
* Leave
* Late
* Holiday

Screens:

* Attendance Entry
* Attendance List
* Attendance Reports

Reports:

* Daily Attendance
* Monthly Attendance
* Student Attendance
* Class Attendance

Accessible By:

School Admin
Teacher

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

Screens:

* Fee Categories
* Fee Structures
* Fee Collection
* Payment History

Reports:

* Fee Collection
* Outstanding Fees
* Daily Collection
* Monthly Collection

Accessible By:

School Admin
Accountant

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
| Attendance Management | View reports | Full | Full for assigned classes | No |
| Fee Management | View reports | Full | No | Full |
| Examination Management | View reports | Full | Full for assigned classes and subjects | No |
| Reporting | Platform reports | School reports | Assigned class reports | Financial reports |
| Activity Logs | Full | View own school logs | No | No |
| Audit Trail | Full | View own school logs | No | No |
| Backup Management | Full | No | No | No |

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
* Fee screens are available to School Admin and Accountant. Teachers do not access Fee Management.
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

✓ Reports Generated

✓ Screenshots Captured

✓ Test Cases Written

✓ Documentation Updated

---

# 21. FINAL FUNCTIONAL GOAL

Deliver a complete, secure, multi-tenant School Administration Management SaaS Platform that demonstrates modern software engineering practices, supports real-world educational workflows, and satisfies MCA project requirements while remaining maintainable, scalable, and easy to explain during project evaluation and viva.
