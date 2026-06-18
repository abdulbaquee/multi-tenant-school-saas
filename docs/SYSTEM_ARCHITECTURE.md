# SYSTEM ARCHITECTURE

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

Frontend:
Blade Templates + Bootstrap 5

Authentication:
Laravel Breeze

Multi-Tenancy:
Stancl Tenancy

Architecture Style:
Layered Monolithic Architecture

Deployment Model:
Single Application
Single Database
Multi-Tenant SaaS

---

# 1. ARCHITECTURE OVERVIEW

The application follows a Layered Monolithic Architecture using Laravel 13.

The architecture is designed to provide:

* Maintainability
* Scalability
* Security
* Tenant Isolation
* Modular Development
* Clean Separation of Concerns

This approach is ideal for an MCA major project because it demonstrates professional software engineering practices while remaining manageable within the project timeline.

---

# 2. HIGH-LEVEL ARCHITECTURE

```text
Browser
    │
    ▼
Routes
    │
    ▼
Middleware
    │
    ▼
Controllers
    │
    ▼
Form Requests
    │
    ▼
Services
    │
    ▼
Models
    │
    ▼
MySQL Database
```

---

# 3. APPLICATION LAYERS

## Presentation Layer

Responsibilities:

* User Interface
* Forms
* Reports
* Dashboards
* Charts

Technology:

* Blade Templates
* Bootstrap 5
* Bootstrap Icons
* Chart.js

Location:

resources/views

---

## Routing Layer

Responsibilities:

* URL Mapping
* Route Protection
* Middleware Assignment

Location:

routes/web.php

---

## Middleware Layer

Responsibilities:

* Authentication
* Authorization
* Tenant Resolution
* Session Validation
* Security Checks

Examples:

* auth
* verified
* tenant
* role

Location:

app/Http/Middleware

---

## Controller Layer

Responsibilities:

* Handle HTTP Requests
* Call Services
* Return Responses

Controllers must remain thin.

Business logic must not be placed in controllers.

Location:

app/Http/Controllers

---

## Validation Layer

Responsibilities:

* Input Validation
* Request Sanitization
* Business Rule Validation

Implementation:

Laravel Form Requests

Location:

app/Http/Requests

---

## Service Layer

Responsibilities:

* Business Logic
* Workflow Processing
* Transaction Management

Services:

* SchoolService
* StudentService
* AttendanceService
* FeeService
* ExamService
* ReportService
* ActivityLogService
* AuditService

Location:

app/Services

---

## Data Access Layer

Responsibilities:

* Data Persistence
* Relationships
* Query Scopes

Implementation:

Laravel Eloquent ORM

Location:

app/Models

---

## Database Layer

Responsibilities:

* Data Storage
* Foreign Keys
* Indexes
* Constraints

Implementation:

MySQL 8

---

# 4. MULTI-TENANT ARCHITECTURE

Architecture Type:

* Shared Application
* Shared Database
* Shared Schema

Tenant Identifier:

school_id

Benefits:

* Reduced Infrastructure Cost
* Easier Maintenance
* Faster Development
* Simplified Deployment

---

# 5. TENANT ISOLATION STRATEGY

Every business entity belongs to a school.

Examples:

* students
* teachers
* attendances
* exams
* subjects
* fee_payments
* report_cards

All records are isolated using:

school_id

Example:

```php
Student::where('school_id', auth()->user()->school_id)
```

No tenant can access another tenant's data.

---

# 6. TENANT REQUEST FLOW

```text
User Login
     │
     ▼
Authenticated User
     │
     ▼
Resolve Tenant
     │
     ▼
Determine school_id
     │
     ▼
Apply Tenant Scope
     │
     ▼
Load Authorized Data
```

---

# 7. AUTHENTICATION ARCHITECTURE

Framework:

Laravel Breeze

Authentication Flow:

```text
Login
   │
   ▼
Credential Validation
   │
   ▼
Authentication Success
   │
   ▼
Role Resolution
   │
   ▼
Dashboard Redirect
```

Features:

* Login
* Logout
* Password Reset
* Email Verification
* Profile Management

---

# 8. AUTHORIZATION ARCHITECTURE

Implementation:

* Middleware
* Gates
* Policies

Roles:

* Super Admin
* School Admin
* Teacher
* Accountant

Authorization Flow:

```text
Request
   │
   ▼
Authentication Check
   │
   ▼
Role Check
   │
   ▼
Policy Validation
   │
   ▼
Access Granted
```

---

# 9. ROLE HIERARCHY

```text
Super Admin
│
└── School Admin
      │
      ├── Teacher
      │
      └── Accountant
```

---

# 10. MODULE ARCHITECTURE

## Platform Module

* School Management
* User Management
* Role Management
* Settings Management

## Academic Module

* Academic Years
* Academic Terms
* Classes
* Sections
* Subjects
* Student Enrollment

## Student Module

* Student Registration
* Student Profiles
* Student Records

## Attendance Module

* Daily Attendance
* Attendance Reports

## Fee Management Module

* Fee Categories
* Fee Structures
* Fee Collection
* Payment Tracking

## Examination Module

* Exams
* Marks Entry
* Grade Calculation
* Report Cards

## Reporting Module

* Student Reports
* Attendance Reports
* Fee Reports
* Examination Reports

## Audit Module

* Activity Logs
* Audit Logs

---

# 11. DASHBOARD ARCHITECTURE

Dashboard Components:

* Statistics Cards
* Attendance Summary
* Fee Collection Summary
* Examination Summary
* Charts
* Recent Activities
* Quick Actions

Data Sources:

* Students
* Attendance
* Fees
* Exams
* Activity Logs

---

# 12. REPORTING ARCHITECTURE

```text
User Request
      │
      ▼
Apply Filters
      │
      ▼
Build Query
      │
      ▼
Generate Dataset
      │
      ▼
Render Report
```

Supported Reports:

* Student Reports
* Attendance Reports
* Fee Reports
* Examination Reports
* Audit Reports

---

# 13. ACTIVITY LOGGING ARCHITECTURE

Table:

activity_logs

Tracked Events:

* Login
* Logout
* Create
* Update
* Delete
* Attendance Entry
* Fee Collection
* Marks Entry
* Report Generation

Implementation:

Centralized ActivityLogService

---

# 14. AUDIT ARCHITECTURE

Table:

audit_logs

Tracked Information:

* Old Values
* New Values
* User
* Timestamp
* IP Address
* User Agent

Audit records are immutable.

---

# 15. DIRECTORY STRUCTURE

```text
app/
├── Http/
│   ├── Controllers/
│   ├── Middleware/
│   └── Requests/
│
├── Models/
├── Policies/
├── Services/
├── Providers/
│
database/
├── migrations/
├── seeders/
│
resources/
├── views/
│
routes/
├── web.php
```

---

# 16. SECURITY ARCHITECTURE

Security Layers:

1. Authentication
2. Authorization
3. Tenant Isolation
4. Request Validation
5. CSRF Protection
6. Session Security
7. File Upload Validation
8. Password Hashing

---

# 17. PERFORMANCE ARCHITECTURE

Optimization Techniques:

* Database Indexing
* Pagination
* Eager Loading
* Query Optimization
* Lazy Loading Prevention

Avoid:

* N+1 Queries
* Large Unfiltered Queries
* Unnecessary Database Calls

---

# 18. DEPLOYMENT ARCHITECTURE

Production Environment:

* Ubuntu 24.04 LTS
* Nginx
* PHP 8.4
* MySQL 8
* Composer
* Git

Deployment Model:

Single Server Deployment

Suitable for MCA demonstration and evaluation.

---

# 19. ARCHITECTURAL DECISIONS

Decision 1

Use Laravel 13

Reason:

Latest stable framework with modern features and long-term support.

---

Decision 2

Use PHP 8.4

Reason:

Improved performance and future-proof development.

---

Decision 3

Use Single Database Multi-Tenancy

Reason:

Simpler implementation, easier maintenance, suitable for MCA scope.

---

Decision 4

Use Service Layer Architecture

Reason:

Cleaner code organization and separation of business logic.

---

Decision 5

Use Bootstrap 5

Reason:

Rapid UI development and responsive design.

---

# 20. SUCCESS CRITERIA

The architecture is successful when:

✓ Multi-Tenancy Works

✓ Tenant Isolation Is Enforced

✓ RBAC Is Implemented

✓ Modules Are Independent

✓ Business Logic Exists In Services

✓ Controllers Remain Thin

✓ Reports Perform Efficiently

✓ Security Standards Are Followed

✓ Architecture Is Easy To Explain During Viva

✓ Application Is Ready For MCA Submission
