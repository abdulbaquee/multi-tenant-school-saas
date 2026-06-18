# SCREEN FLOW

Version: 2.0

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

This document serves as the master navigation reference for development, testing, screenshots, and project demonstrations.

---

# 1. APPLICATION FLOW

Login
│
▼
Role Dashboard
│
├── School Management
├── School Settings
├── User Management
├── Academic Structure
├── Student Management
├── Attendance Management
├── Fee Management
├── Examination Management
├── Reporting
├── Analytics
├── Activity Logs
├── Audit Logs
└── Backup Management

---

# 2. LOGIN FLOW

Login Page
│
▼
Authenticate User
│
▼
Resolve Tenant
│
▼
Determine Role
│
▼
Role Dashboard

---

# 3. GLOBAL LAYOUT STRUCTURE

Every authenticated screen contains:

Header
│
├── School Name
├── Academic Year
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

# 4. DASHBOARD FLOW

## Super Admin Dashboard

Dashboard
│
├── Schools
├── Users
├── Reports
├── Activity Logs
├── Audit Logs
├── Analytics
└── System Settings

---

## School Admin Dashboard

Dashboard
│
├── Students
├── Attendance
├── Fees
├── Examinations
├── Reports
├── Users
└── Academic Structure

---

## Teacher Dashboard

Dashboard
│
├── Students
├── Attendance
├── Marks Entry
├── Results
└── Reports

---

## Accountant Dashboard

Dashboard
│
├── Fee Collection
├── Transactions
├── Receipts
└── Financial Reports

---

# 5. SIDEBAR NAVIGATION RULES

Maximum Navigation Depth:

2 Levels

Rules:

* Keep navigation simple
* No nested third-level menus
* Frequently used actions must remain visible
* Consistent ordering across modules

---

# 6. SUPER ADMIN MENU

Dashboard

Schools
├── All Schools
└── Add School

Users
├── All Users
└── Add User

Reports

Activity Logs

Audit Logs

Analytics

System Settings

Profile

---

# 7. SCHOOL ADMIN MENU

Dashboard

School Settings

Users

Academic Structure
├── Academic Years
├── Academic Terms
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
├── Fee Collection
└── Fee Reports

Examinations
├── Exams
├── Marks Entry
├── Results
└── Report Cards

Reports

Analytics

Profile

---

# 8. TEACHER MENU

Dashboard

Students

Attendance
├── Mark Attendance
└── Attendance Reports

Examinations
├── Marks Entry
├── Results
└── Report Cards

Reports

Profile

---

# 9. ACCOUNTANT MENU

Dashboard

Fees
├── Fee Collection
├── Receipts
├── Transactions
└── Fee Reports

Financial Reports

Profile

---

# 10. SCHOOL MANAGEMENT FLOW

School List
│
├── Add School
├── View School
├── Edit School
├── Activate School
└── Deactivate School

---

# 11. ACADEMIC STRUCTURE FLOW

Academic Years
│
├── Create Academic Year
├── Edit Academic Year
└── Activate Academic Year

Academic Terms
│
├── Create Term
├── Edit Term
└── Manage Term

Classes
│
├── Create Class
├── Edit Class
└── View Class

Sections
│
├── Create Section
├── Edit Section
└── View Section

Subjects
│
├── Create Subject
├── Edit Subject
└── View Subject

---

# 12. STUDENT MANAGEMENT FLOW

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

---

# 13. STUDENT ENROLLMENT FLOW

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
Save Enrollment

---

# 14. ATTENDANCE FLOW

Attendance Dashboard
│
├── Mark Attendance
├── Attendance History
├── Monthly Attendance
└── Attendance Reports

---

# 15. ATTENDANCE ENTRY FLOW

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

# 16. FEE MANAGEMENT FLOW

Fee Categories
│
├── Fee Structures
├── Student Fees
├── Fee Collection
├── Receipts
└── Fee Reports

---

# 17. FEE COLLECTION FLOW

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
Save Transaction

---

# 18. EXAMINATION FLOW

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

---

# 19. MARKS ENTRY FLOW

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

# 20. REPORT CARD FLOW

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
Print / Export

---

# 21. REPORTING FLOW

Reports
│
├── Student Reports
├── Attendance Reports
├── Fee Reports
├── Examination Reports
├── Activity Reports
└── Audit Reports

---

# 22. REPORT SCREEN STRUCTURE

Every report page must contain:

* Breadcrumb
* Page Title
* Filters
* Summary Cards
* Chart
* Data Table
* Pagination
* Export Button

---

# 23. ANALYTICS FLOW

Analytics Dashboard
│
├── Student Growth
├── Attendance Trends
├── Fee Collection Trends
├── Examination Performance
└── Activity Trends

---

# 24. ACTIVITY LOG FLOW

Activity Logs
│
├── View Activity
├── Search
├── Filters
└── Export

---

# 25. AUDIT LOG FLOW

Audit Logs
│
├── View Details
├── Search
├── Filters
└── Export

---

# 26. BACKUP MANAGEMENT FLOW

Backup Dashboard
│
├── Create Backup
├── Backup History
├── Backup Details
└── Download Backup

---

# 27. BREADCRUMB STANDARDS

Examples:

Dashboard

Dashboard

Students

Dashboard > Students

Add Student

Dashboard > Students > Add Student

Attendance Entry

Dashboard > Attendance > Mark Attendance

Marks Entry

Dashboard > Examinations > Marks Entry

Report Card

Dashboard > Examinations > Report Card

---

# 28. SCREEN DESIGN REQUIREMENTS

Every screen must contain:

* Breadcrumb
* Page Title
* Primary Action Button
* Search (where applicable)
* Filters (where applicable)
* Content Area

---

# 29. SCREENSHOT PLANNING

Required Screenshot Categories:

* Authentication
* Dashboard
* School Management
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

40–60

---

# 30. PRIMARY DEMO FLOW

Login
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

# 31. SUCCESS CRITERIA

The screen flow is successful when:

✓ Navigation Is Consistent

✓ Menus Are Logical

✓ Breadcrumbs Are Clear

✓ User Journeys Are Simple

✓ Reports Are Easy To Access

✓ Screenshots Are Easy To Capture

✓ Demonstrations Flow Smoothly

✓ Suitable For MCA Evaluation

✓ Easy To Explain During Viva
