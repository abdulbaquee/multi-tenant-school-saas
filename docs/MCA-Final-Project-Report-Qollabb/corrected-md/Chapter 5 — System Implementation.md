# Chapter 5 — System Implementation

Chapter 5 — System Implementation
5.1 Development Environment
The School Portal project was implemented using a modern Laravel-based web development environment. The system was developed as a PHP web application using Laravel 13, PHP 8.4, MySQL 8, Blade templates, Bootstrap 5, Composer, npm, Git, and GitHub. The application was compatible with common developer workstations such as macOS and Windows because the selected stack is cross-platform and can be executed through a local PHP environment, Composer dependency management, Node.js asset tooling, and a MySQL database server.
Laravel 13 provided the core application framework, routing system, middleware pipeline, service container, Eloquent ORM, validation layer, policy authorization, migrations, seeders, and testing utilities. PHP 8.4 was used as the server-side programming language, while MySQL 8 was used as the relational database for persistent storage. Blade templates and Bootstrap 5 were used to build the user interface, and npm was used for frontend asset compilation through the Laravel Vite setup. Composer was used for PHP dependency management and autoloading.
Git was used for local version control, and GitHub was used as the remote repository for maintaining the project source code. This development environment supported phased implementation, testing, and deployment. The same technology foundation was also used for production deployment, where the School Portal application was hosted at the live demo URL. The development environment therefore remained aligned with the deployment environment, reducing differences between local testing and production execution.
5.2 Project Structure
The project follows the standard Laravel folder structure with additional organization for services, policies, tenancy components, and module-specific files. The app/Http area contains controllers, middleware, and form request classes. Controllers handle incoming HTTP requests, middleware protects authenticated and tenant-scoped routes, and form requests validate user input before it reaches the service layer. The app/Services folder contains business workflow classes for modules such as school management, users, academic structure, students, attendance, fees, examinations, reports, analytics, logs, and backups.
The app/Policies folder contains authorization policies. These policies control whether a user can view, create, update, collect, export, or manage a specific resource. The policy layer works together with role permissions, tenant context, and service validation. The app/Models folder contains Eloquent models and relationships for schools, users, academic entities, students, attendance, fee records, exam records, reports, activity logs, audit logs, and backup logs. The app/Tenancy and model concern files support the native multi-tenancy design.
The resources/views folder contains Blade templates used for the user interface. These include authentication pages, dashboards, module screens, forms, list views, detail views, report screens, receipt views, and print-friendly pages. The database/migrations folder contains database migration files for creating and modifying tables, while the database/seeders folder contains seeders for canonical RBAC data, Super Admin setup, and demo school data. The tests folder contains automated feature and unit tests used to verify implementation correctness.
5.3 Authentication and Profile Management
Authentication was implemented as the first major application workflow because every protected module depends on secure user access. The authentication system supports login, logout, password reset, password confirmation, email verification foundation, profile update, and password update workflows. Laravel Breeze was used as the authentication foundation and was customized with Blade and Bootstrap 5 views to match the School Portal interface and branding.
The authentication flow is not limited to checking email and password only. After successful credential validation, the system verifies user status, role status, school assignment, and tenant eligibility. A Super Admin user operates in platform context, while School Admin, Teacher, and Accountant users operate in tenant context. Inactive users and users connected to inactive or invalid school records are denied access. This ensures that authentication and tenant validation are connected instead of being treated as separate concerns.
Profile management allows authenticated users to update their own profile information and password through secure forms. Password workflows use validation, hashing, and session-safe handling. The implementation also includes activity and audit evidence for important authentication and account-related changes, while sensitive values are filtered from logs. This supports accountability without exposing confidential credentials.
[SCREENSHOT: Login page — School Portal branding]
Figure 5.1: Tenant ownership assignment through BelongsToTenant
static::addGlobalScope(new TenantScope);
static::creating(function (Model $model): void {
    $context = app(TenantContext::class);
    if (! $context->isTenant()) {
        throw TenantContextException::tenantRequired($model::class);
    }
    $model->setAttribute('school_id', $context->tenantId());
});
static::updating(function (Model $model): void {
    if ($model->isDirty('school_id')) {
        throw TenantContextException::immutableOwnership($model::class);
    }
});

The above tenant ownership logic demonstrates how tenant-owned models receive the active school identifier from TenantContext during creation. It also prevents later modification of school_id, which is important because tenant ownership must remain immutable after a record is created.
5.4 School Management and School Settings
The School Management module was implemented for Super Admin users. It allows the Super Admin to register schools, view the school list, search school records, filter by status, view school details, edit school information, activate schools, and deactivate schools with a required deactivation reason. Each school represents one tenant in the SaaS platform. School data is preserved even when a school is deactivated, because deactivation is a lifecycle action rather than a destructive deletion process.
The implementation automatically creates default school settings for newly registered schools. School Settings are then managed by School Admin users within their own tenant context. School Admin users can update profile, contact, academic, attendance, grading, and logo-related configuration for their own school only. Super Admin users retain read-only visibility of school settings through school detail screens, but routine tenant configuration remains a School Admin responsibility.
The module also handles tenant lifecycle security. When a school is deactivated, related school users are prevented from continued access according to the documented lifecycle rules. The service layer and policies ensure that school management remains Super Admin-only, while school settings are isolated to the active school. This separation supports platform administration without allowing one school administrator to affect another school’s configuration.
[SCREENSHOT: Super Admin Schools list]
5.5 User Management and RBAC
The User Management module was implemented to manage platform and school users. It supports user listing, creation, profile viewing, editing, activation, deactivation, password reset by authorized administrators, and role assignment. Super Admin users operate at platform level, while School Admin users manage users within their own school boundary. User status, role, school assignment, and tenant context are validated before user-management workflows are allowed.
The RBAC implementation uses four fixed canonical roles: Super Admin, School Admin, Teacher, and Accountant. The project does not implement custom role creation, deletion, or renaming in the MVP scope. Role and permission records are system-managed. Super Admin users can manage constrained mappings for school roles within documented permission boundaries, while School Admin users can view effective permissions and assign permitted school roles without changing the shared permission catalog.
Permissions are not used as the only security layer. A permission allows a user to attempt an operation, but policies and services still verify role boundary, tenant ownership, school status, record ownership, and workflow-specific rules. For example, a Teacher may access attendance only for directly assigned sections, and an Accountant may access fee collection workflows but not academic setup. This layered authorization model reduces the risk of privilege escalation.
[SCREENSHOT: Users or Roles screen]
Figure 5.2: Policy authorization example for attendance operation
public function operate(User $user, Section $section): bool
{
    if (! $this->viewAny($user) || blank($user->school_id)
        || (int) $user->school_id !== (int) $section->school_id
        || $section->trashed()
        || $section->status !== Section::STATUS_ACTIVE) {
        return false;
    }
    if ($this->isSchoolAdmin($user)) { return true; }
    $teacher = $this->activeTeacherProfile($user);
    return $teacher instanceof Teacher
        && (int) $section->teacher_id === (int) $teacher->id;
}

This policy excerpt shows that authorization is based on more than a role label. It checks tenant ownership, section status, School Admin authority, and Teacher assignment before allowing attendance operation.
5.6 Academic Structure Module
The Academic Structure module was implemented to define the school’s academic foundation. It includes Academic Years, Academic Terms, Teacher Profiles, Classes, Sections, and Subjects. These entities are required before student enrollment, attendance, fees, examinations, and reports can operate correctly. School Admin users manage these records for their own school, while Super Admin access is read-only where permitted.
The implementation treats academic records as tenant-owned data. This means academic years, terms, classes, sections, subjects, and teacher profiles belong to a specific school and cannot be accessed by another school. The module uses policies, form requests, services, and model relationships to validate ownership, active status, and lifecycle restrictions. Teacher profile records are linked to eligible Teacher-role users and are used for assignment-based access control in attendance and examination workflows.
The Academic Structure module also supports clean navigation and role-based screens. School Admin users can create and update academic records, Teachers can view assigned academic structure, and Accountants are denied academic structure access. This module forms the base for later modules and demonstrates how a school-specific academic environment is modeled inside a shared SaaS application.
[SCREENSHOT: Classes/Sections/Subjects]
5.7 Student Management Module
The Student Management module was implemented to handle student registration, profile management, lifecycle status, private photos, enrollment, transfer, graduation, and retained enrollment history. The student master record stores stable personal and admission details, while the enrollment record stores academic placement such as academic year, class, section, roll number, and enrollment status. This separation allows a student to move across academic years and sections without losing historical context.
The implementation includes tenant-safe uniqueness and privacy rules. Admission numbers are unique within a school. Student photos are handled as private files and are not exposed through unrestricted public storage. School Admin users can register and manage students within their own school. Teacher access is assignment-scoped and privacy-minimized, while Accountant access to student information is limited to permitted fee workflows. Super Admin student visibility is also controlled and privacy-minimized according to the documented design.
Student lifecycle operations such as transfer and graduation are handled as retained-history workflows rather than casual deletion. Enrollment completion and status changes are transaction-controlled so that academic history remains consistent. The module also creates activity and audit evidence for important student changes, supporting traceability for administrative actions.
[SCREENSHOT: Student list or profile]
5.8 Attendance Management Module
The Attendance Management module was implemented to support daily complete-roster attendance entry, authorized correction, retained history, search, filters, and monthly operational summaries. School Admin users can manage attendance for eligible students in active sections of their own school. Teachers can mark and correct attendance only for directly assigned active sections. Subject assignment alone does not grant attendance access.
Attendance entry is processed through validated forms and service-layer workflows. The user selects academic year, class, section, and date, after which the system loads the eligible roster for the active tenant. Attendance statuses are validated, and the service confirms that students belong to the selected section and school. The implementation also prevents forged cross-tenant submissions because tenant ownership is derived server-side and not trusted from browser input.
Attendance records are treated as retained operational history. Corrections update authorized existing records instead of deleting historical information. The module records privacy-safe activity summaries and audit evidence, while avoiding unnecessary exposure of sensitive student remarks. This design supports daily school operations while preserving accountability.
[SCREENSHOT: Attendance entry or monthly summary]
5.9 Fee Management Module
The Fee Management module was implemented to support fee categories, fee structures, student fee assignments, fee collection, receipts, payment history, outstanding balance views, and sandbox payment transaction screens. School Admin users can configure fee categories, fee structures, and student fee assignments. School Admin and Accountant users can perform fee collection, view receipts, review payment history, and track outstanding balances within the school context.
The fee collection workflow is transactional. During collection, the system validates collector authority, verifies that the student fee is collectible, prevents replay through collection tokens, updates the student fee paid and balance amounts, creates a retained fee payment receipt, creates a payment transaction record, and records activity and audit evidence. Receipt numbers and transaction numbers are generated server-side. Financial records are retained and are not treated as ordinary deletable records.
The current implementation includes a sandbox transaction workflow only. It does not implement a production payment gateway. Sandbox transaction screens display local transaction evidence for sandbox_gateway records and are available to School Admin and Accountant users according to permissions. This allows the project to demonstrate the fee transaction process within MCA scope without claiming live payment gateway integration.
[SCREENSHOT: Fee collection or receipt]
Figure 5.3: Service-layer workflow example from fee collection
public function collectionFormFor(StudentFee $studentFee, User $actor): StudentFee
{
    $this->authorizeCollectorContext($actor);
    $this->authorize($actor->can('collect', $studentFee));

    return $studentFee->load([
        'student',
        'feeStructure.feeCategory',
        'feeStructure.schoolClass',
        'academicYear',
    ]);
}

This service-layer example shows that controller actions delegate business checks to services. The service confirms collector context, verifies policy authorization, and loads only the data required for the fee collection form.
5.10 Examination and Report Card Module
The Examination module was implemented to manage exams, exam subject assignment, grade scales, teacher-scoped marks entry, result review, result processing, and operational report card generation. School Admin users create and manage exam records, assign exam subjects, manage grade scales, process results, generate report cards, and access report card print views. Teacher users perform marks entry and review results only within assigned scope.
Marks entry is controlled by teacher assignment rules. A Teacher can enter marks only for assigned class and subject scope. The system derives pass, fail, absent, grade, total, percentage, and related result values on the server side instead of trusting submitted values from the browser. This protects result integrity and prevents forged grade or status fields from being accepted. School Admin users have broader own-school examination control, while Accountant users are denied examination workflows.
Report cards are generated as operational academic outputs. They provide subject breakdown, result summary, and print-friendly presentation. Teacher report-card access is assignment-scoped and privacy-controlled so that teachers do not receive full unrelated result information outside their assigned subject or class scope. The module also records activity and audit evidence for examination setup, marks entry, result processing, and report-card workflows.
[SCREENSHOT: Exams or report card]
5.11 Reporting, Analytics, and System Operations
The Reporting module was implemented to provide role-aware and tenant-aware operational reports. Report categories in the Reports hub include student reports, attendance reports, fee reports, examination reports, and authorized school or user summary reports for Super Admin and School Admin roles. Reports support filters, pagination, CSV export where implemented, and browser print views. The report service layer ensures that each role sees only the data allowed by its responsibility boundary. Activity log review, audit trail review, and backup history are implemented under System Operations with separate policies and navigation, not as CSV report hub categories.
The Analytics implementation provides dashboard summaries and chart-based analytics. Super Admin and School Admin users can access analytics screens with server-derived charts. Role-specific dashboard widgets were also enhanced for all roles. Super Admin dashboards focus on platform status, School Admin dashboards summarize own-school operations, Teacher dashboards focus on assigned academic tasks, and Accountant dashboards focus on fee collections, outstanding balances, and transaction summaries.
System Operations combines Activity Logs, Audit Trail, and Backup Management under a unified navigation concept. Super Admin users can access platform-wide activity logs, audit logs, and backup management. School Admin users can access Activity Logs and Audit Trail for their own school only. Backup Management remains Super Admin-only. This distinction is important because backup operations affect platform-level data and must not be available to tenant administrators.
[SCREENSHOT: Reports hub or Analytics charts]
5.12 Security Implementation
Security was implemented across multiple layers of the application. CSRF protection is applied to web forms so that state-changing requests cannot be submitted from unauthorized external pages. Validation is handled through Form Requests and service checks so that user input is verified before processing. Query access is controlled through Eloquent relationships, tenant global scopes, policy checks, and service-layer validation. Blade templates escape output by default, reducing the risk of unsafe HTML rendering.
Policy authorization is used throughout the application to protect modules and records. However, policies are not the only defense. The implementation also verifies tenant context, role permission, school status, record ownership, teacher assignment, accountant fee boundaries, and workflow-specific lifecycle rules. This layered approach supports the central security requirement that one school must not access another school’s records.
File storage security was implemented for private resources. Student photos and backup archives use private storage and are not treated as public files. School logos may be displayed through public storage where appropriate, but student photos and backup files require stricter handling. In production, the application uses HTTPS, APP_ENV=production, and APP_DEBUG=false. These settings prevent debug information from being exposed to users and ensure that the live system runs under production configuration.
5.13 Version Control
Git was used to track the implementation history of the project, and GitHub was used as the remote repository. Version control supported phased development by allowing the project to be implemented, reviewed, corrected, and documented in checkpoints. The project followed the implementation sequence from Phase 2 to Phase 10, beginning with authentication and user management and continuing through tenancy, RBAC, academic structure, student management, attendance, fees, examinations, reporting, analytics, and system operations.
The changelog and decision log provided traceability for technical decisions and implementation milestones. Important architectural decisions included the use of Laravel 13, PHP 8.4, MySQL 8, single-database multi-tenancy, and native Laravel tenant isolation through school_id, global scopes, TenantContext, policies, and services. The decision log also records the revision away from an external tenancy package toward native Laravel multi-tenancy.
The version-control workflow helped maintain academic evidence. Each phase included implementation outputs, verification notes, review results, and documentation updates. This made the final report easier to prepare because the project history was already organized into modules and release checkpoints. GitHub also provided the public repository link required for Qollabb final submission.
5.14 Production Deployment
The School Portal application was deployed at https://schoolportal.pagescorch.com on an OVH VPS. The production server uses Ubuntu 22.04 LTS, Nginx 1.18.0, PHP 8.4.21 with PHP-FPM, MySQL 8.0.46, and Let’s Encrypt HTTPS. The canonical application root is /var/www/schoolportal, and the public web root is /var/www/schoolportal/public. The application is deployed as a separate Nginx site block on the same VPS that hosts the main pagescorch.com domain.
Production deployment included installing backend dependencies with Composer, building frontend assets with npm, configuring the .env file, generating the application key, running migrations, seeding canonical data, explicitly loading demo school data, linking storage, caching configuration, caching routes, caching views, and setting correct storage permissions.
The production .env uses APP_NAME="School Portal", APP_ENV=production, APP_DEBUG=false, and APP_URL=https://schoolportal.pagescorch.com.
The deployment record confirms that HTTPS loads the School Portal site, the landing page shows School Portal branding, and School Admin login and dashboard access were operational for Springdale High A. The deployment also supports Qollabb live-demo evaluation and final MCA report evidence. Demo schools SHA, SHB, and SHC are available through the demo data seeding process, and the two-browser SHA/SHB isolation evidence is intended for screenshot-based validation in the report.
[SCREENSHOT: Live landing page with HTTPS URL visible]
Word Count
Approximate word count: 4,530 words.

