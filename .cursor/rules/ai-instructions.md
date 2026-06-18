# AI DEVELOPMENT INSTRUCTIONS

Version: 2.0

Project:
Multi-Tenant School Administration Management SaaS Platform

Repository:
multi-tenant-school-saas

Program:
Master of Computer Applications (MCA)

---

# PROJECT CONTEXT

This project is an MCA Final Year Project that must demonstrate:

* Multi-Tenant SaaS Architecture
* Laravel Best Practices
* Database Design
* Secure Development
* Role Based Access Control
* Reporting & Analytics
* Software Engineering Principles
* Testing & Validation

The application must remain simple enough for academic evaluation while maintaining professional coding standards.

---

# OFFICIAL TECHNOLOGY STACK

Backend

* Laravel 13
* PHP 8.4

Frontend

* Blade Templates
* Bootstrap 5
* Bootstrap Icons
* Chart.js

Database

* MySQL 8

Authentication

* Laravel Breeze

Multi-Tenancy

* Stancl Tenancy

Version Control

* Git
* GitHub

Testing

* PHPUnit
* Laravel Testing Framework

---

# DEVELOPMENT PHILOSOPHY

Always prioritize:

1. Simplicity
2. Readability
3. Maintainability
4. Security
5. User Experience
6. Academic Demonstrability

If there is a conflict between:

Enterprise Complexity

and

Academic Simplicity

Choose Academic Simplicity.

---

# FRONTEND RULES

ALWAYS USE

* Bootstrap 5
* Blade Templates
* Bootstrap Icons
* Chart.js

NEVER USE

* Tailwind CSS
* React
* Vue
* Inertia
* Livewire
* Alpine.js

unless explicitly approved.

Generate clean Bootstrap components only.

---

# UI / UX RULES

Design Style:

Modern SaaS Dashboard

Inspiration:

* Stripe
* Notion
* FreshBooks
* Google Workspace

Characteristics:

* Clean
* Spacious
* Professional
* Consistent
* Responsive

Avoid:

* Clutter
* Fancy animations
* Over-engineered interfaces
* Complex navigation

---

# RESPONSIVE DESIGN RULES

Every page must support:

* Desktop
* Tablet
* Mobile

Requirements:

* Bootstrap Grid System
* No horizontal scrolling
* Responsive tables
* Responsive forms

---

# ACCESSIBILITY RULES

Always:

* Use semantic HTML
* Use labels for form controls
* Use accessible buttons
* Maintain contrast ratios
* Support keyboard navigation

---

# LAYOUT RULES

Every authenticated page must contain:

* Header
* Sidebar
* Breadcrumb
* Page Title
* Content Area
* Footer

Dashboard pages should include:

* Statistics Cards
* Charts
* Recent Activities
* Quick Actions

---

# ARCHITECTURE RULES

Architecture Style:

Layered Monolithic Architecture

Flow:

Routes
→ Middleware
→ Controller
→ Form Request
→ Service
→ Model
→ Database

Controllers must remain thin.

Business logic belongs inside Services.

---

# MULTI-TENANCY RULES

Most Important Rule.

Tenant Identifier:

school_id

Every business entity belongs to a school.

Examples:

* students
* attendances
* fee_payments
* exams
* report_cards
* audit_logs

Every query must be tenant scoped.

Never generate code that can expose another school's data.

---

# AUTHORIZATION RULES

Always use:

* Policies
* Gates
* Middleware

Required Roles:

* Super Admin
* School Admin
* Teacher
* Accountant

Never rely only on hidden buttons.

Authorization must be enforced server-side.

---

# VALIDATION RULES

Always use:

Laravel Form Requests

Examples:

* StoreStudentRequest
* UpdateStudentRequest
* StoreAttendanceRequest

Never place validation inside controllers.

Generate friendly validation messages.

---

# CONTROLLER RULES

Controllers must:

* Receive Requests
* Call Services
* Return Responses

Avoid:

* Business Logic
* Complex Calculations
* Large Methods

Target:

Less than 200 lines per controller.

---

# SERVICE LAYER RULES

Complex operations belong in Services.

Examples:

* StudentService
* AttendanceService
* FeeService
* ExamService
* ReportService
* ActivityLogService
* AuditService

Services should:

* Handle business rules
* Coordinate workflows
* Return DTOs or Models

Services should never return Blade views.

---

# MODEL RULES

Every model must define:

* Fillable
* Casts
* Relationships
* Scopes

Use:

* HasFactory
* SoftDeletes where appropriate

Avoid placing business workflows in models.

---

# DATABASE RULES

Always use:

* Migrations
* Foreign Keys
* Indexes
* Soft Deletes
* Timestamps

Naming:

Tables:
plural snake_case

Columns:
snake_case

Foreign Keys:
entity_id

Examples:

school_id
student_id
class_id

---

# MIGRATION RULES

Every migration must:

* Include down() method
* Define indexes
* Define foreign keys
* Use proper constraints

Never generate incomplete migrations.

---

# QUERY RULES

Prefer:

* Eloquent Relationships
* Query Scopes
* Eager Loading

Avoid:

* Raw SQL
* N+1 Queries
* Duplicate Queries

Always consider tenant filtering.

---

# SECURITY RULES

Always implement:

* CSRF Protection
* Authorization
* Authentication
* Tenant Isolation
* Input Validation
* Output Escaping
* Secure File Uploads

Never expose:

* Stack Traces
* Secrets
* Credentials
* Tenant Data

---

# FILE UPLOAD RULES

Validate:

* File Type
* MIME Type
* File Size

Use:

Storage::disk()

Generate unique filenames.

Never trust uploaded files.

---

# ACTIVITY LOGGING RULES

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

Use centralized logging services.

---

# AUDIT TRAIL RULES

Track:

* User
* Module
* Action
* Old Values
* New Values
* Timestamp
* IP Address

Audit records must remain immutable.

---

# REPORTING RULES

Every report should support:

* Search
* Filters
* Pagination
* Export Ready Structure

Reports must always respect:

* Role Permissions
* Tenant Isolation

---

# DASHBOARD RULES

Every dashboard should answer:

* What happened today?
* What needs attention?
* What changed recently?

Use:

* Cards
* Charts
* Tables
* Recent Activity

---

# TABLE RULES

Every table should include:

* Search
* Filters
* Pagination
* Status Badges
* Action Buttons

Responsive by default.

---

# FORM RULES

Every form should include:

* Validation
* Error Messages
* Success Messages
* Required Indicators

Use Bootstrap 5 styling consistently.

---

# TESTING RULES

Generate tests for:

* Features
* Validation
* Authorization
* Tenant Isolation

Use:

* PHPUnit
* Laravel Feature Tests

Critical business workflows require tests.

---

# CODE QUALITY RULES

Follow:

* PSR-12
* SOLID
* DRY
* KISS
* Separation of Concerns

Avoid:

* Fat Controllers
* God Classes
* Duplicate Logic
* Hardcoded Values

---

# DOCUMENTATION RULES

When generating a new module, also provide:

* Database Impact
* Security Considerations
* Testing Considerations
* Screens Required For MCA Report

Update if needed:

* MODULE_SPECIFICATIONS.md
* DATABASE_DESIGN.md
* SCREEN_FLOW.md
* CHANGELOG.md

---

# GIT COMMIT RULES

Use Conventional Commits.

Examples:

feat: add student enrollment module

feat: implement attendance management

fix: resolve tenant isolation issue

docs: update module specifications

refactor: move fee calculation to service layer

test: add student feature tests

---

# MCA PROJECT RULES

Every generated feature should help produce:

* Screenshots
* Test Cases
* Documentation
* Viva Discussion Points

The project must remain:

* Easy to Demonstrate
* Easy to Explain
* Easy to Maintain

Academic clarity is more important than enterprise complexity.

---

# WHEN GENERATING CODE

Unless explicitly requested otherwise, generate:

1. Migration
2. Model
3. Form Request
4. Service
5. Policy
6. Controller
7. Routes
8. Blade Views
9. Feature Tests
10. Documentation Notes

Provide complete implementation-ready solutions.

Never provide partial implementations when a complete module is requested.

---

# FINAL AI RULE

When uncertain:

Prefer Laravel Native Solutions.

When uncertain:

Prefer Simplicity.

When uncertain:

Prefer Security.

When uncertain:

Follow Existing Project Documentation.
