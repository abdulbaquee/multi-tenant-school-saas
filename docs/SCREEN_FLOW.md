# SCREEN FLOW

Version: 1.1
Status: Draft - Permission Aligned

Project:
Multi-Tenant School Administration Management SaaS Platform

Program:
Master of Computer Applications (MCA)

Framework:
Laravel 13

Frontend:
Blade Templates + Bootstrap 5

Purpose:

Define application navigation, user journeys, menu hierarchy, screen relationships, breadcrumbs, and module navigation flows.

The canonical permission matrix is defined in `MODULE_SPECIFICATIONS.md`. Every menu in this document must match that matrix.

## Current Implementation Status

The current implementation exposes authorized navigation for completed Phases
2 through 9:

* Super Admin: Dashboard, Users, Roles & Permissions, Academic Structure,
  Students, Schools, Profile.
* School Admin: Dashboard, Users, Roles & Permissions, Academic Structure,
  Students, Attendance, Fees, Examinations, School Settings, Profile.
* Teacher: Dashboard, assigned Academic Structure, assigned Students,
  Attendance, assigned Examination marks/results/report-card views, Profile.
* Accountant: Dashboard, Fee collection/history/outstanding/sandbox views,
  Profile.
* Student Management now provides the implemented Phase 6 profile and immutable
  Enrollment workflows. Attendance, Fee, and Examination operational workflows
  are implemented. Phase 10 Reporting, Dashboard Analytics, Activity/Audit
  review screens, and Backup Management remain omitted until their routes and
  authorization are implemented.

School Management currently provides Super Admin-only listing, search, status
filtering, registration, details, editing, activation, and deactivation. School
deactivation requires a reason, revokes school-user sessions, and preserves all
tenant records. Routine school deletion is not exposed.

School Settings currently provides School Admin-only profile, contact, academic,
attendance, grading, and logo controls for the active school. Super Admin keeps
read-only settings visibility through School Details.

Academic Structure currently provides Academic Year, Academic Term, Teacher
Profile, Class, Section, and Subject workflows. Super Admin access is Platform-
context read-only, School Admin management is own-tenant and permission-bound,
Teacher access is limited to active assignments, and Accountant access is denied.

Student Management currently provides School Admin registration, profile
updates, search, lifecycle controls, private photos, immutable Enrollment
creation/completion, transaction-coupled transfer/graduation, and retained
Enrollment history. Teacher reads are assignment-scoped and privacy-minimized;
Super Admin reads require one selected school and are privacy-minimized;
Accountant access is denied.

---

# 1. APPLICATION FLOW

Login
│
▼
Authenticate User
│
▼
Resolve Tenant Context
│
▼
Resolve Role & Permissions
│
▼
Role-Specific Dashboard
│
▼
Authorized Menus Only

---

# 2. GLOBAL LAYOUT STRUCTURE

Every authenticated screen contains:

Header
│
├── School Name or Platform Name
├── Active Academic Year when applicable
├── User Role
├── Notifications
└── User Menu

Sidebar
│
▼
Page Header
│
▼
Content Area
│
▼
Footer

---

# 3. NAVIGATION RULES

Maximum Navigation Depth:

2 Levels

Rules:

* Menus must be generated from the user's permissions.
* Hidden menu items must also be protected by authorization checks.
* No role may see a menu for a module marked "No" in the canonical permission matrix.
* Reports must only show categories available to the current role.
* Dashboard widgets must only summarize data available to the current role.
* No separate platform-wide or application-wide settings module may appear in navigation.

---

# 4. SUPER ADMIN DASHBOARD FLOW

Dashboard
│
├── Schools
├── Users
├── Roles & Permissions
├── Academic Structure (Read Only)
├── Reports
├── Analytics
├── Activity Logs
├── Audit Logs
└── Backup Management

Visible Widgets:

* Total Schools
* Active Schools
* Total Users
* Recent Platform Activity
* Audit Alerts
* Backup Status

---

# 5. SCHOOL ADMIN DASHBOARD FLOW

Dashboard
│
├── School Settings
├── Users
├── Academic Structure
├── Students
├── Attendance
├── Fees
├── Examinations
├── Reports
├── Analytics
├── Activity Logs
└── Audit Logs

Visible Widgets:

* Total Students
* Attendance Summary
* Fee Summary
* Examination Summary
* Recent School Activity

---

# 6. TEACHER DASHBOARD FLOW

Dashboard
│
├── Students
├── Academic Structure
├── Attendance
├── Examinations
└── Reports

Visible Widgets:

* Assigned Classes
* Today's Attendance
* Pending Marks Entry
* Recent Examination Results

---

# 7. ACCOUNTANT DASHBOARD FLOW

Dashboard
│
├── Fees
└── Reports

Visible Widgets:

* Today's Fee Collections
* Outstanding Fees
* Recent Transactions
* Receipt Summary

---

# 8. SUPER ADMIN MENU

Dashboard

Schools
├── All Schools
├── Add School
└── School Details

Users
├── All Users
└── Add User

Roles & Permissions

Academic Structure
├── Academic Years
├── Academic Terms
├── Teacher Profiles
├── Classes
├── Sections
└── Subjects

Students
└── School-Scoped Student Directory (read only, privacy-minimized)

Reports
├── School Reports
├── User Reports
├── Activity Reports
├── Audit Reports
└── Backup Reports

Analytics

Activity Logs

Audit Logs

Backup Management
├── Backup Dashboard
├── Create Backup
├── Backup History
└── Backup Details

Profile

---

# 9. SCHOOL ADMIN MENU

The role menus below show the approved end-state navigation. Phase 7 activates
only the Attendance Entry, History, and Monthly Summary items documented in the
Attendance flow. Reports and Analytics branches remain hidden until Phase 10.

Dashboard

School Settings
├── General Settings
├── Academic Settings
├── Attendance Settings
├── Grading Settings
└── Logo Management

Users
├── All Users
└── Add User

Roles & Permissions

Academic Structure
├── Academic Years
├── Academic Terms
├── Teacher Profiles
├── Classes
├── Sections
└── Subjects

Students
├── Student List
├── Add Student
└── Student Enrollment

Attendance
├── Mark Attendance
├── Attendance History
└── Monthly Summary

Fees
├── Fee Categories
├── Fee Structures
├── Student Fees
├── Fee Collection
├── Receipts
├── Payment History
├── Outstanding Balances
└── Sandbox Transactions

Examinations
├── Exams
├── Marks Entry
├── Results
└── Report Cards

Reports
├── Student Reports
├── Attendance Reports
├── Fee Reports
├── Examination Reports
├── Activity Reports
└── Audit Reports

Analytics

Activity Logs

Audit Logs

Profile

---

# 10. TEACHER MENU

Dashboard

Academic Structure
├── Assigned Classes
├── Assigned Sections
└── Assigned Subjects

Students
├── Assigned Students
└── Student Profiles

Attendance
├── Mark Attendance
├── Attendance History
└── Monthly Summary

Examinations
├── Marks Entry
├── Results
└── Report Cards

Reports
├── Assigned Student Reports
├── Attendance Reports
└── Examination Reports

Profile

---

# 11. ACCOUNTANT MENU

Dashboard

Fees
├── Fee Collection
├── Receipts
├── Payment History
├── Outstanding Balances
└── Sandbox Transactions

Reports
└── Fee Reports

Profile

---

# 12. SCHOOL MANAGEMENT FLOW

School List
│
├── Add School
├── View School
├── Edit School
├── Activate School
└── Deactivate School

Access:

* Super Admin only

Lifecycle Rules:

* Deactivation requires a reason and revokes school-user sessions and remember
  tokens while retaining users and tenant data.
* Inactive or soft-deleted schools cannot authenticate or continue sessions.
* Reactivation restores access only for otherwise-active users and does not
  restore a soft-deleted school.

---

# 13. SCHOOL SETTINGS FLOW

General Settings
│
├── Edit School Profile
├── Update Contact Information
├── Upload School Logo
├── Update Academic Settings
├── Update Attendance Settings
└── Update Grading Settings

Access:

* School Admin: full access for own school
* Super Admin: view through School Details only

---

# 14. USER MANAGEMENT FLOW

User List
│
├── Add User
├── View User
├── Edit User
├── Assign Role
├── Reset Password
├── Activate User
└── Deactivate User

Access:

* Super Admin: platform users and school users
* School Admin: own school users only

---

# 14A. ROLE & PERMISSION FLOW

Roles & Permissions
│
├── Role List
│   ├── Super Admin
│   ├── School Admin
│   ├── Teacher
│   └── Accountant
├── View Effective Permissions
└── Edit Mapping
    ├── Group Permissions By Module
    ├── Show Essential Permissions As Checked And Disabled
    ├── Prevent Permissions Outside The Role Maximum
    ├── Review Changes
    └── Save Mapping

Route Contract:

* `GET /roles` - role directory.
* `GET /roles/{role}` - effective permission details.
* `GET /roles/{role}/edit` - Super Admin edit form for an editable school role.
* `PUT /roles/{role}/permissions` - transactional mapping replacement.

Super Admin Access:

* Sees Roles & Permissions in platform navigation.
* Views all four roles and the complete permission catalog.
* Super Admin mapping is read-only.
* Edits only non-essential permissions for School Admin, Teacher, and
  Accountant.

School Admin Access:

* Sees a read-only Roles & Permissions page when `roles.view` is effective.
* Views School Admin, Teacher, and Accountant mappings only.
* Assigns those roles through User Management when `roles.assign` and the
  corresponding User Policy checks pass.
* Cannot access mapping edit routes or view the Super Admin mapping.

Teacher and Accountant Access:

* No Roles & Permissions menu or route access.

Mapping Save Behavior:

* Server validation rejects forged, duplicate, essential-removal, Super Admin,
  and out-of-bound permission changes.
* A successful save is transactional, audited, and reflected in menus and
  authorization on the next request.

---

# 15. ACADEMIC STRUCTURE FLOW

Academic Years
│
├── Create Academic Year
├── Edit Academic Year
├── Activate Academic Year
├── Deactivate Non-Current Academic Year
└── Reactivate Academic Year as Non-Current

Academic Terms
│
├── Create Term
├── Edit Term
├── Activate Term
├── Deactivate Term
└── Reactivate Term under an Active Academic Year

Teacher Profiles
│
├── Link Teacher User
├── Edit Academic Profile
├── Activate or Deactivate
└── Archive or Restore

Classes
│
├── Create Class
├── Edit Class
├── View Class
├── Deactivate Class
└── Archive or Restore

Sections
│
├── Create Section
├── Edit Section
├── Assign Class Teacher
├── View Section
├── Deactivate Section
└── Archive or Restore

Subjects
│
├── Create Subject
├── Edit Subject
├── Assign Teacher
├── View Subject
├── Deactivate Subject
└── Archive or Restore

Access:

* Super Admin: Platform-context read-only lists and details across schools.
* School Admin: own-school management subject to exact `academic.*` permissions.
* Teacher: read-only assigned Sections, assigned Subjects, and related Classes
  through their own active Teacher Profile. No Academic Year or Term management.
* Accountant: denied.

Route Families:

* `/academic-years`
* `/academic-terms`
* `/teacher-profiles`
* `/classes`
* `/sections`
* `/subjects`

Lifecycle Rules:

* Academic years cannot overlap. Activating an active year transactionally
  replaces the school's previous current year; a current year cannot be
  deactivated, and all Terms must be inactive before a non-current year is
  deactivated. Reactivation restores active status without making the year
  current.
* Terms must be ordered, non-overlapping, and contained within their parent
  Academic Year. Reactivation requires an active parent year.
* Academic Years and Terms use status only. Classes, Sections, Subjects, and
  Teacher Profiles prefer deactivation and require an inactive, dependency-safe
  record before archive.
* A Class cannot deactivate or archive while it has an active, nonarchived
  Section or Subject. Restoring a Class returns it inactive before a separate
  activation action.
* Section and Subject activation or restoration revalidates an active,
  nonarchived, same-school parent Class and any retained Teacher assignment.
  Restoration returns the record inactive before a separate activation action.
* A Teacher Profile cannot deactivate or archive while an active Section or
  Subject assignment remains. Restore revalidates the immutable linked Teacher
  user and returns the profile inactive before separate activation.
* Cross-tenant route binding and forged parent or Teacher Profile IDs are denied
  without exposing record existence.

---

# 16. STUDENT MANAGEMENT FLOW

Student List
│
├── Add Student
├── View Student
│   ├── Edit Student
│   ├── Enrollment History
│   ├── Complete Active Enrollment
│   └── Status Management
│       ├── Transfer Student and Active Enrollment
│       └── Graduate Student and Complete Active Enrollment
│
└── Create Initial Enrollment

Access:

* School Admin: full access inside the active school, including private photo
  upload, replacement, removal, and authorized delivery.
* Teacher: read-only, privacy-minimized Student directory and profile access
  only for active Enrollments in an assigned active Section, or in an active
  Class reached through an assigned active Subject. Teacher views exclude DOB,
  guardian details, address, mobile numbers, email, and Student photos.
* Super Admin: read-only, privacy-minimized directory and profile access only
  after selecting a school through an authorized Platform workflow. Super Admin
  views exclude DOB, guardian details, address, mobile numbers, email, and
  Student photos.
* Accountant: no direct Student Management flow in Phase 6. Limited lookup is
  introduced only in Phase 8 Fee Management.

Phase Boundary:

* Attendance, Fee, and Examination history links are introduced with their
  respective modules.
* Student Reports and exports are introduced in Phase 10 Reporting.
* Internal Class or Section reassignment, promotion, and mid-year transfer are
  not Phase 6 workflows.

---

# 17. STUDENT ENROLLMENT FLOW

Student
│
▼
Select Academic Year
│
▼
Assign Class
│
▼
Assign Section
│
▼
Assign Roll Number
│
▼
Save Enrollment
│
▼
Retain Immutable Enrollment History
│
├── Complete Enrollment
├── Transfer Student and Enrollment
└── Graduate Student and Complete Enrollment

Access:

* School Admin only

Rules:

* The selected Student, Academic Year, Class, and Section must be active and
  belong to the same school; the Section must belong to the selected Class.
* The Academic Year must be the school's active current year, and the enrollment
  date must fall inside its date range.
* `student_enrollments.roll_no` is the only roll-number source of truth and is
  unique within the selected school, Academic Year, Class, and Section.
* One immutable Enrollment exists for each Student and Academic Year. Class,
  Section, and roll number cannot be edited after creation.
* Enrollment may change only from active to completed or transferred. Internal
  reassignment, promotion, and cross-tenant transfer are not supported in the
  MVP.
* Completion leaves the Student active. Transfer and graduation update the
  Student and its single active Enrollment in one transaction.

---

# 18. ATTENDANCE FLOW

Attendance Workspace
│
├── Mark Attendance
├── Attendance History
└── Monthly Summary

Access:

* School Admin: all eligible active Sections in own school.
* Teacher: only an active Section directly assigned through
  `sections.teacher_id` to the actor's active Teacher Profile. Subject assignment
  alone does not authorize Attendance.
* Super Admin: no Phase 7 screen; platform reports begin in Phase 10.
* Accountant: no Attendance access.

Phase Boundary:

* Attendance history and monthly summaries are operational on-screen views using
  `attendance.view`.
* Reports, exports, analytics, dashboard widgets, and platform summaries are not
  Phase 7 screens.
* No Attendance delete flow exists. Authorized corrections use
  `attendance.update` and preserve audit evidence.

---

# 19. ATTENDANCE ENTRY FLOW

Select Date
│
▼
Select Class
│
▼
Select Section
│
▼
Load Eligible Enrollment Roster
│
▼
Assign One Status Per Student
│
▼
Validate Complete Roster And Save Atomically

Rules:

* The server resolves the current Academic Year and same-tenant Enrollment
  placement. The request cannot choose a school or override Student placement.
* Date must be within the current Academic Year, on or after Enrollment date,
  and no later than the school-local current date.
* New rows require active Students and active Enrollments. Existing retained rows
  remain visible after later lifecycle changes.
* A repeated save may create newly eligible missing rows and correct existing
  rows only when the actor has the required create/update permissions.
* Holiday applies to the complete eligible Section roster and is School
  Admin-only.

Correction Flow:

Attendance History
│
▼
Select Existing Date And Section
│
▼
Load Retained Records
│
▼
Correct Status Or Optional Remark
│
▼
Save With Audit Evidence

Historical Academic Years are read-only in Phase 7. Corrections are limited to
the current Academic Year. `marked_by` remains the original creator; the audit
record identifies the correcting actor.

Implementation Status (2026-06-22):

The Attendance workspace, complete-roster save, whole-roster Holiday action,
history/search, retained correction, monthly summary, active tab/navigation,
and denied role/tenant flows are implemented. Report, export, delete, analytics,
dashboard, Super Admin, and Accountant Attendance flows remain absent.

---

# 20. FEE MANAGEMENT FLOW

Fee Categories
│
├── Fee Structures
├── Student Fees
├── Fee Collection
├── Receipts
├── Payment History
├── Outstanding Balances
└── Sandbox Transactions

Access:

* School Admin: full own-school setup, assignment, collection, receipt,
  payment-history, outstanding-balance, and sandbox-transaction access.
* Accountant: own-school Student Fee lookup, collection, receipt,
  payment-history, outstanding-balance, and sandbox-transaction access only.
* Super Admin: no Phase 8 Fee route; platform Fee reports are Phase 10.
* Teacher: no Fee Management access.

Phase 8 excludes Fee reports, exports, analytics, dashboard widgets, and
platform summaries. Those flows remain under Phase 10 Reporting.

Implementation Status (2026-06-23):

School Admin Fee Category, Fee Structure, and Student Fee assignment screens,
navigation, lifecycle actions, privacy-minimized assignment detail fields,
activity/audit evidence, and denied role/tenant flows are implemented.
School Admin and Accountant collection, receipt, payment-history, outstanding-balance,
and sandbox transaction operational screens are implemented. Phase 8 release gate
reviews are approved. Report, export, analytics, dashboard, Super Admin,
Accountant setup, and Teacher Fee flows remain absent.

---

# 21. FEE COLLECTION FLOW

Search Student
│
▼
Load Fee Balance
│
▼
Collect Payment
│
▼
Generate Receipt
│
▼
Save Payment
│
▼
Save Transaction

Phase 8 collection creates local retained payment and transaction records. The
`sandbox_gateway` payment mode uses a deterministic local transaction payload
only; no production gateway, real credentials, webhook, external SDK, or network
call is used.

---

# 22. EXAMINATION FLOW

Exams
│
├── Exam Subject Assignment
├── Grade Scales
├── Marks Entry
├── Results
└── Report Cards

Access:

* School Admin: full own-school setup, assignment, grade-scale management,
  result processing, and operational report-card generation/view/print.
* Teacher: assigned class/subject marks entry, assigned result review, and
  operational report-card view/print only.
* Super Admin: no Phase 9 Examination route; platform Examination reports are
  Phase 10.
* Accountant: no Examination Management access.

Phase 9 excludes Examination reports, exports, analytics, dashboard widgets,
and platform summaries. Those flows remain under Phase 10 Reporting.

Implementation Status (2026-06-24):

DECISION-035 defines the approved Phase 9 operational boundary. The core schema
and tenant-aware model foundation are implemented with focused tests. School
Admin exam setup, Exam Subject assignment, Teacher-scoped marks entry, result
processing, and operational report-card view/print flows are implemented.
Release-gate remediation limits Teacher report-card list/detail/print output to
assigned subject scope. Phase 9 release gate reviews are approved, and reporting
branches remain under Phase 10.

---

# 23. MARKS ENTRY FLOW

Select Exam
│
▼
Select Class
│
▼
Select Subject
│
▼
Load Students
│
▼
Enter Marks
│
▼
Save Results

---

# 24. REPORT CARD FLOW

Results
│
▼
Process Results
│
▼
Calculate Grades
│
▼
Generate Report Card
│
▼
Print or View

Phase 9 report cards provide on-screen view and browser print only. PDF/Excel
export belongs to Phase 10 Reporting.

---

# 25. REPORTING FLOW

Reports
│
├── Student Reports
├── Attendance Reports
├── Fee Reports
├── Examination Reports
├── Activity Reports
├── Audit Reports
└── Backup Reports

Role Visibility:

| Report Category | Super Admin | School Admin | Teacher | Accountant |
| --------------- | ----------- | ------------ | ------- | ---------- |
| School Reports | Yes | No | No | No |
| User Reports | Yes | Own school | No | No |
| Student Reports | Platform summary | Own school | Assigned classes | Fee lookup only |
| Attendance Reports | Platform summary | Own school | Directly assigned Sections | No |
| Fee Reports | Platform summary | Own school | No | Own school |
| Examination Reports | Platform summary | Own school | Assigned classes | No |
| Activity Reports | Platform-wide | Own school | No | No |
| Audit Reports | Platform-wide | Own school | No | No |
| Backup Reports | Platform-wide | No | No | No |

---

# 26. REPORT SCREEN STRUCTURE

Every report page must contain:

* Breadcrumb
* Page Title
* Filters
* Summary Cards
* Chart where useful
* Data Table
* Pagination
* Export Button where permitted

---

# 27. ANALYTICS FLOW

Analytics Dashboard
│
├── Student Growth
├── Attendance Trends
├── Fee Collection Trends
├── Examination Performance
└── Activity Trends

Access:

* Super Admin: platform summaries
* School Admin: own school summaries
* Teacher: dashboard-only assigned class summaries
* Accountant: dashboard-only fee summaries

---

# 28. ACTIVITY LOG FLOW

Activity Logs
│
├── View Activity
├── Search
├── Filters
└── Export

Access:

* Super Admin: platform-wide
* School Admin: own school only

---

# 29. AUDIT LOG FLOW

Audit Logs
│
├── View Details
├── Search
├── Filters
└── Export

Access:

* Super Admin: platform-wide
* School Admin: own school only

---

# 30. BACKUP MANAGEMENT FLOW

Backup Dashboard
│
├── Create Backup
├── Backup History
├── Backup Details
└── Download Backup

Access:

* Super Admin only

---

# 31. BREADCRUMB STANDARDS

Examples:

Dashboard

Dashboard > Students

Dashboard > Students > Add Student

Dashboard > Attendance > Mark Attendance

Dashboard > Examinations > Marks Entry

Dashboard > Examinations > Report Card

Dashboard > Backup Management > Backup History

---

# 32. SCREEN DESIGN REQUIREMENTS

Every screen must contain:

* Breadcrumb
* Page Title
* Primary Action Button where permitted
* Search where applicable
* Filters where applicable
* Content Area

---

# 33. SCREENSHOT PLANNING

Required Screenshot Categories:

* Authentication
* Dashboard
* School Management
* School Settings
* User Management
* Academic Structure
* Students
* Attendance
* Fees
* Receipts
* Examinations
* Marks Entry
* Results
* Report Cards
* Reports
* Analytics
* Activity Logs
* Audit Logs
* Backups

Target Screenshots:

40-60

---

# 34. PRIMARY DEMO FLOW

Login As School Admin
│
▼
Dashboard
│
▼
Create Academic Year
│
▼
Create Class
│
▼
Register Student
│
▼
Enroll Student
│
▼
Mark Attendance
│
▼
Assign Fees
│
▼
Collect Fee
│
▼
Create Exam
│
▼
Enter Marks
│
▼
Generate Report Card
│
▼
Generate Reports

---

# 35. SUCCESS CRITERIA

The screen flow is successful when:

* Navigation is consistent.
* Menus match the canonical permission matrix.
* Breadcrumbs are clear.
* User journeys are simple.
* Reports are easy to access.
* Backup Management is Super Admin only.
* Activity Logs and Audit Logs are tenant-scoped for School Admin.
* Only School Settings appears as a settings area.
* Screenshots are easy to capture.
* Demonstrations flow smoothly.
* The flow is suitable for MCA evaluation and viva.
