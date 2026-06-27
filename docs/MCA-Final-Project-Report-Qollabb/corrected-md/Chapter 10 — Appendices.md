# Chapter 10 — Appendices

Chapter 10 — Appendices
Appendix A — GitHub Repository
The source code for the project is maintained in the following GitHub repository:
GitHub Repository: https://github.com/abdulbaquee/multi-tenant-school-saas
The repository contains the Laravel 13 source code, database migrations, seeders, documentation files, tests, screenshots, and deployment-related project evidence. The folder structure follows the standard Laravel application layout with additional documentation and report-supporting folders.
Folder / File
Purpose
app/Http
Contains controllers, middleware, and request-handling classes.
app/Services
Contains business logic and workflow service classes.
app/Policies
Contains authorization policies for module and record-level access control.
app/Models
Contains Eloquent models and relationships for database tables.
app/Tenancy
Contains native tenant context classes used for school-based tenancy.
resources/views
Contains Blade templates for dashboards, forms, reports, and module screens.
routes/web.php
Defines web routes for the application modules.
database/migrations
Contains migration files for creating database tables.
database/seeders
Contains seeders for RBAC data, demo schools, and initial records.
tests
Contains automated unit and feature tests.
docs
Contains project documentation such as architecture, database design, tenancy design, testing strategy, installation, and deployment guides.
mca-screenshots
Contains screenshots used as evidence in the MCA report.
Appendix B — Installation Guide
The local installation guide explains how to set up School Portal on a developer machine for testing, demonstration, and report evidence. The required software includes PHP 8.4 with common Laravel extensions, Composer 2.x, Node.js 20 LTS or newer, npm 10+, MySQL 8.0 for production-like local setup, and Git 2.x. SQLite may also be used for quick local test execution where applicable.
The local setup begins by cloning the repository from GitHub, entering the project folder, copying .env.example to .env, installing Composer dependencies, and generating the Laravel application key. The .env file is then configured with the local application name, URL, database connection, database username, database password, session driver, and filesystem settings.
After configuration, a local MySQL database named schoolportal is created using the utf8mb4 character set and collation. The application schema and seed data are then prepared using Laravel migration and seeding commands. The seed process loads canonical RBAC roles, the Super Admin user, and demo schools such as SHA, SHB, and SHC in the local environment. Frontend dependencies are installed through npm, and assets are built using the Laravel Vite pipeline.
The local application can be started using Laravel’s development server. The developer opens http://127.0.0.1:8000 in the browser and signs in using the demo credentials stored separately in the repository’s demo-login reference file. Installation can be verified by checking that the login page loads, migrations are successful, demo schools exist, tenant isolation works between different school logins, and the automated test suite runs successfully using php artisan test.
Private storage rules are important during installation. Student photos and backup archives use private local storage and should not be moved to the public directory. Public storage linking is used only where appropriate, such as for public school logo display.
Appendix C — Deployment Guide
The production deployment guide is recorded as version 1.1 and marked as frozen and production verified. The live deployment for School Portal is hosted at:
Live Demo URL: https://schoolportal.pagescorch.com
The deployment uses an OVH VPS with Ubuntu 22.04 LTS, Nginx 1.18.0, PHP 8.4.21 with PHP-FPM, MySQL 8.0.46, and Let’s Encrypt HTTPS through Certbot. The application is deployed at /var/www/schoolportal, with the public web root set to /var/www/schoolportal/public. The Nginx site configuration is stored separately from the main pagescorch.com site, allowing School Portal to run as its own subdomain.
The deployment process includes preparing the server, creating the MySQL production database, cloning the GitHub repository into the canonical production path, installing Composer dependencies with production optimization, copying and configuring the .env file, generating the Laravel application key, installing npm dependencies, building frontend assets, running database migrations, seeding production data, explicitly running the demo data seeder, creating the storage link, caching configuration, caching routes, caching views, and setting proper ownership and permissions on storage and bootstrap/cache.
The production environment uses APP_ENV=production, APP_DEBUG=false, and APP_URL=https://schoolportal.pagescorch.com. The deployment also enables HTTPS and uses private storage rules for student photos and backups. After deployment, smoke tests are performed for landing page access, login, role-based dashboards, School Admin workflow, Teacher workflow, Accountant workflow, reports, tenant isolation, and HTTPS behavior.
The deployment evidence confirms that the live School Portal site loads over HTTPS, displays School Portal branding, and supports authenticated role-based access. The SHA and SHB two-browser tenant isolation demonstration is used as report evidence for confirming that school data remains separated in production-style usage.
Appendix D — Database Schema Overview
The database contains 28 tables grouped by functional domain. The design follows a single-database, shared-schema multi-tenant model. Tenant-owned records use school_id to identify the owning school.
Domain
Tables
Count
Platform / Core
schools, school_settings, users, roles, permissions, role_permissions
6
Academic
academic_years, academic_terms, classes, sections, subjects, teachers
6
Students
students, student_enrollments
2
Attendance
attendances
1
Fees
fee_categories, fee_structures, student_fees, fee_payments, payment_transactions
5
Examinations
exams, exam_subjects, exam_results, grade_scales, report_cards
5
System
activity_logs, audit_logs, backup_logs
3
Total

28
The platform tables manage tenant schools, school settings, users, roles, permissions, and role-permission mappings. The academic tables manage the school’s academic structure, including academic years, terms, classes, sections, subjects, and teacher profiles. The student tables store student master records and yearly enrollment records. The attendance table stores daily attendance entries. The fee tables manage fee categories, fee structures, student fee assignments, payments, and sandbox transaction evidence. The examination tables support exam setup, subject assignment, marks, grade scales, and report cards. The system tables store activity logs, audit logs, and backup operation history.
Appendix E — User Manual Quick Start
E.1 Super Admin Quick Start
	•	Open the live School Portal URL or local application URL in a browser.
	•	Log in using Super Admin credentials.
	•	Review the platform dashboard for schools, users, activity, audit, and backup summaries.
	•	Open the School Management module to view, create, edit, activate, or deactivate schools.
	•	Open User Management to review platform and permitted school users.
	•	Open Role & Permission screens to review constrained RBAC mappings.
	•	Open System Operations to view Activity Logs, Audit Trail, and Backup Management.
	•	Use reports and analytics to review platform-level information.
E.2 School Admin Quick Start
	•	Open School Portal and log in using School Admin credentials for the assigned school.
	•	Review the School Admin dashboard for student, attendance, fee, examination, and activity summaries.
	•	Configure school profile and operational preferences from School Settings.
	•	Create or manage school users such as Teachers and Accountants.
	•	Set up academic years, terms, classes, sections, subjects, and teacher profiles.
	•	Register students and manage student enrollments.
	•	Manage attendance, fees, examinations, reports, analytics, Activity Logs, and Audit Trail for the own school.
	•	Do not attempt Backup Management, as it is restricted to Super Admin only.
E.3 Teacher Quick Start
	•	Open School Portal and log in using Teacher credentials.
	•	Review the Teacher dashboard for assigned academic responsibilities.
	•	Open assigned academic records such as classes, sections, and subjects.
	•	View permitted student information within assigned scope.
	•	Mark attendance only for directly assigned sections.
	•	Enter examination marks only for assigned class and subject scope.
	•	View assigned examination results, report cards, and academic reports.
	•	Log out after completing attendance or marks-entry work.
E.4 Accountant Quick Start
	•	Open School Portal and log in using Accountant credentials.
	•	Review the Accountant dashboard for fee collections, outstanding balances, and recent transactions.
	•	Open the Fee Management module.
	•	Search the permitted student fee record.
	•	Collect fee payment through the authorized fee collection workflow.
	•	Generate and review the receipt.
	•	View payment history, outstanding balances, sandbox transaction evidence, and financial reports.
	•	Log out after completing fee-related work.
Appendix F — Selected Source Code Reference
The following source files are selected as reference files for technical review. Full code is not pasted in this appendix to avoid unnecessary code dumping.
File path
Purpose
app/Tenancy/TenantContext.php
Maintains the active tenant or platform context during request processing.
app/Policies/ReportPolicy.php
Controls report-related authorization by role, tenant boundary, and permitted reporting scope.
These files may be referenced during viva or technical review to explain native multi-tenancy and policy-based authorization in the School Portal project.

