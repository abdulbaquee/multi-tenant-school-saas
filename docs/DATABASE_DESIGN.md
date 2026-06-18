# DATABASE DESIGN

Version: 2.0

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

Tenant Identifier:
school_id

---

# 1. PURPOSE

This document defines the database architecture, entity relationships, table structure, naming conventions, indexing strategy, and multi-tenant design principles for the Multi-Tenant School Administration Management SaaS Platform.

The database is designed to support multiple schools operating within a shared application while maintaining strict tenant-level data isolation.

---

# 2. DATABASE OBJECTIVES

The database must support:

* School Management
* User Management
* Student Management
* Academic Structure
* Attendance Tracking
* Fee Management
* Examination Management
* Reporting & Analytics
* Audit Logging
* Activity Tracking

The design must remain:

* Scalable
* Secure
* Normalized
* Report-Friendly
* Laravel-Friendly

---

# 3. MULTI-TENANT STRATEGY

## Architecture Type

* Shared Application
* Shared Database
* Shared Schema

## Tenant Isolation

Every business record belongs to a specific school.

Tenant identification is achieved using:

school_id

Examples:

* students.school_id
* attendances.school_id
* exams.school_id
* fee_payments.school_id

All business queries must be automatically scoped to the active tenant.

No school should be able to access records belonging to another school.

---

# 4. DATABASE NAMING CONVENTIONS

## Tables

Rules:

* snake_case
* plural nouns

Examples:

* schools
* students
* attendances
* fee_payments

## Columns

Rules:

* snake_case

Examples:

* first_name
* last_name
* admission_no
* attendance_date

## Foreign Keys

Rules:

singular_id

Examples:

* school_id
* student_id
* class_id
* section_id

---

# 5. DATABASE MODULES

## Platform Module

* schools
* school_settings
* roles
* permissions
* role_permissions
* users

## Academic Module

* academic_years
* academic_terms
* classes
* sections
* subjects
* teachers
* students
* student_enrollments

## Attendance Module

* attendances

## Fee Management Module

* fee_categories
* fee_structures
* student_fees
* fee_payments
* payment_transactions

## Examination Module

* exams
* exam_subjects
* exam_results
* grade_scales
* report_cards

## Audit Module

* activity_logs
* audit_logs

## System Module

* backup_logs

---

# 6. TABLE INVENTORY

| Module      | Tables |
| ----------- | ------ |
| Platform    | 6      |
| Academic    | 8      |
| Attendance  | 1      |
| Fees        | 5      |
| Examination | 5      |
| Audit       | 2      |
| System      | 1      |

Total Estimated Tables: 28

---

# 7. CORE ENTITIES

## schools

Stores tenant school information.

Key Fields:

* id
* name
* code
* email
* phone
* address
* principal_name
* website
* status

Relationships:

* hasMany(users)
* hasMany(students)
* hasMany(classes)
* hasMany(exams)

---

## school_settings

Stores school-specific configuration.

Examples:

* logo
* timezone
* currency
* grading_system
* attendance_rules

Relationship:

* belongsTo(school)

---

## users

Stores authenticated application users.

Key Fields:

* school_id
* role_id
* name
* email
* password
* status

Relationships:

* belongsTo(school)
* belongsTo(role)

---

## teachers

Stores teacher-specific information.

Key Fields:

* school_id
* user_id
* employee_code
* qualification
* joining_date

Relationships:

* belongsTo(user)

---

## classes

Stores academic classes.

Examples:

* Class 1
* Class 2
* Class 10

---

## sections

Stores class sections.

Examples:

* A
* B
* C

Relationships:

* belongsTo(class)

---

## students

Stores student information.

Key Fields:

* school_id
* admission_no
* roll_no
* first_name
* last_name
* gender
* date_of_birth
* guardian_name
* mobile
* admission_date
* status

Relationships:

* belongsTo(school)

---

## student_enrollments

Tracks student assignment to academic years, classes, and sections.

Relationships:

* belongsTo(student)
* belongsTo(class)
* belongsTo(section)
* belongsTo(academic_year)

---

# 8. ATTENDANCE MODULE

Table:

attendances

Purpose:

Stores daily student attendance.

Status Values:

* Present
* Absent
* Leave
* Late
* Holiday

Unique Constraint:

student_id + attendance_date

---

# 9. FEE MANAGEMENT MODULE

Tables:

* fee_categories
* fee_structures
* student_fees
* fee_payments
* payment_transactions

Payment Modes:

* Cash
* Card
* UPI
* Bank Transfer
* Sandbox Gateway

Fee Status:

* Pending
* Partial
* Paid

---

# 10. EXAMINATION MODULE

Tables:

* exams
* exam_subjects
* exam_results
* grade_scales
* report_cards

Grade Scale:

* A+
* A
* B+
* B
* C
* D
* F

Result Status:

* Pass
* Fail

---

# 11. ACTIVITY LOGGING

Table:

activity_logs

Tracks:

* Login
* Logout
* Create
* Update
* Delete
* Attendance Entry
* Fee Collection
* Marks Entry
* Report Generation

---

# 12. AUDIT LOGGING

Table:

audit_logs

Tracks:

* Old Values
* New Values
* User
* Timestamp
* IP Address
* User Agent

Audit records are immutable.

---

# 13. BACKUP LOGGING

Table:

backup_logs

Tracks:

* Backup Type
* Backup File
* Backup Size
* Backup Status
* Generated By
* Generated At

---

# 14. INDEXING STRATEGY

Mandatory Indexes:

* school_id
* Foreign Keys
* admission_no
* roll_no
* email
* attendance_date
* receipt_no
* transaction_no

Composite Indexes:

* school_id + admission_no
* school_id + roll_no
* student_id + attendance_date

---

# 15. SOFT DELETE STRATEGY

Use Soft Deletes:

* schools
* users
* teachers
* students
* classes
* sections
* subjects
* exams

Business records should never be permanently removed.

---

# 16. AUDIT FIELDS

All business tables should include:

* created_at
* updated_at
* deleted_at

Where applicable:

* created_by
* updated_by
* deleted_by

---

# 17. MIGRATION EXECUTION ORDER

1. schools
2. school_settings
3. roles
4. permissions
5. role_permissions
6. users
7. academic_years
8. academic_terms
9. teachers
10. classes
11. sections
12. subjects
13. students
14. student_enrollments
15. attendances
16. fee_categories
17. fee_structures
18. student_fees
19. fee_payments
20. payment_transactions
21. exams
22. exam_subjects
23. grade_scales
24. exam_results
25. report_cards
26. activity_logs
27. audit_logs
28. backup_logs

---

# 18. SEED DATA STRATEGY

Initial Seed Data:

* Super Admin
* School Admin
* Teacher
* Accountant

Demo Data:

* Demo School
* Demo Classes
* Demo Sections
* Demo Subjects
* Demo Students

This enables immediate project demonstration.

---

# 19. REPORT REQUIREMENTS

Database must support:

* Student Reports
* Attendance Reports
* Fee Reports
* Financial Reports
* Examination Reports
* Analytics Reports
* Audit Reports

---

# 20. FUTURE SCALABILITY

Future Enhancements:

* Parent Portal
* Student Portal
* Mobile Application
* Online Payments
* SMS Notifications
* Email Notifications
* AI Analytics
* Cloud-Based SaaS Scaling

---

# 21. DATABASE SUCCESS CRITERIA

✓ Multi-Tenant Ready

✓ Secure

✓ Fully Normalized

✓ Indexed

✓ Scalable

✓ Report Friendly

✓ Laravel 13 Compatible

✓ MySQL 8 Compatible

✓ MCA Report Ready

✓ Easy To Explain During Viva
