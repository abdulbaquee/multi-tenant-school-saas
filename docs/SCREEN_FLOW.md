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

The current implementation exposes only authorized navigation for completed
Phase 2 and Phase 3 work and the implemented Phase 4 RBAC workspace:

* Super Admin: Dashboard, Users, Roles & Permissions, Schools, Profile.
* School Admin: Dashboard, Users, Roles & Permissions, School Settings, Profile.
* Teacher and Accountant: Dashboard, Profile.
* Later-module menu items documented below remain the approved MVP target and
  must stay omitted from the application until their routes and authorization
  are implemented.

School Management currently provides Super Admin-only listing, search, status
filtering, registration, details, editing, activation, and deactivation. School
deactivation requires a reason, revokes school-user sessions, and preserves all
tenant records. Routine school deletion is not exposed.

School Settings currently provides School Admin-only profile, contact, academic,
attendance, grading, and logo controls for the active school. Super Admin keeps
read-only settings visibility through School Details.

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
└── Attendance Reports

Fees
├── Fee Categories
├── Fee Structures
├── Student Fees
├── Fee Collection
├── Payment History
└── Fee Reports

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
└── Attendance Reports

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
├── Transactions
├── Outstanding Fees
└── Fee Reports

Reports
├── Fee Collection Reports
├── Outstanding Fee Reports
└── Transaction Reports

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
│   ├── Attendance History
│   ├── Fee History
│   └── Examination Results
│
└── Student Transfer

Access:

* School Admin: full access
* Teacher: limited view for assigned classes
* Accountant: limited student lookup inside fee workflows only

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

Access:

* School Admin only

---

# 18. ATTENDANCE FLOW

Attendance Dashboard
│
├── Mark Attendance
├── Attendance History
├── Monthly Attendance
└── Attendance Reports

Access:

* School Admin: all classes in own school
* Teacher: assigned classes only
* Super Admin: reports only

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
Load Students
│
▼
Mark Attendance
│
▼
Save Attendance

---

# 20. FEE MANAGEMENT FLOW

Fee Categories
│
├── Fee Structures
├── Student Fees
├── Fee Collection
├── Receipts
├── Transactions
└── Fee Reports

Access:

* School Admin: full access
* Accountant: full access
* Super Admin: reports only

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

---

# 22. EXAMINATION FLOW

Exams
│
▼
Assign Subjects
│
▼
Marks Entry
│
▼
Grade Calculation
│
▼
Result Processing
│
▼
Report Card Generation

Access:

* School Admin: full access
* Teacher: marks and results for assigned classes and subjects
* Super Admin: reports only

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
Print or Export

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
| Attendance Reports | Platform summary | Own school | Assigned classes | No |
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
