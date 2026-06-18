# SECURITY GUIDELINES

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

Authentication:
Laravel Breeze

Multi-Tenancy:
Stancl Tenancy

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

Laravel Breeze

Authentication Type:

Session-Based Authentication

Requirements:

* Secure Login
* Secure Logout
* Password Reset
* Session Protection
* Remember Me Support

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

---

# 6. SESSION SECURITY

Requirements:

* Session Regeneration After Login
* Session Invalidation After Logout
* Secure Cookies
* HTTP Only Cookies
* SameSite Protection

Laravel security defaults must remain enabled.

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

Rule:

School A must never access School B data.

No exceptions.

---

# 10. TENANT ISOLATION CONTROLS

Enforce At:

* Middleware
* Policies
* Services
* Controllers
* Reports
* Queries

Validation Checklist:

✓ Record belongs to school

✓ User belongs to school

✓ Query filtered by school_id

✓ Reports filtered by school_id

✓ Exports filtered by school_id

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

Store uploads in:

storage/app/public

Use:

Storage::putFile()

Requirements:

* Unique Filenames
* Access Control
* File Validation

Never store uploads using original filenames.

---

# 18. MASS ASSIGNMENT PROTECTION

Every Eloquent model must define:

* $fillable

or

* $guarded

Never leave models unprotected.

---

# 19. DATABASE SECURITY

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

---

# 20. AUDIT TRAIL SECURITY

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

---

# 21. ACTIVITY LOGGING SECURITY

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

---

# 22. REPORT SECURITY

Reports must respect:

* Tenant Isolation
* User Permissions
* Data Visibility Rules

Never expose:

* Other School Data
* Hidden Fields
* Internal Configuration

---

# 23. EXPORT SECURITY

Supported Exports:

* PDF
* Excel
* CSV

Requirements:

* Same permissions as screen view
* Same tenant filters as screen view
* Authorization validation before export

---

# 24. BACKUP SECURITY

Backup Access:

Super Admin Only

Track:

* Backup User
* Backup Time
* Backup Status
* Backup Size

Backup files must never be publicly accessible.

---

# 25. ERROR HANDLING

Never expose:

* Stack Traces
* SQL Queries
* Server Paths
* Environment Variables

Users should receive friendly error messages.

Developers should use logs for diagnostics.

---

# 26. LOGGING POLICY

Logs must never contain:

* Passwords
* API Keys
* Access Tokens
* Secret Keys
* Sensitive Credentials

Log only necessary operational information.

---

# 27. SECURITY TESTING

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

# 28. SECURITY INCIDENT SEVERITY

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

# 29. SECURITY REVIEW CHECKLIST

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

# 30. SECURE DEVELOPMENT PRACTICES

Developers must:

* Follow Coding Standards
* Use Form Requests
* Use Policies
* Use Service Layer Architecture
* Review Queries For Tenant Isolation
* Avoid Duplicate Security Logic

Security must be considered during development, not after development.

---

# 31. MCA VIVA QUESTIONS

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

# 32. SUCCESS CRITERIA

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
