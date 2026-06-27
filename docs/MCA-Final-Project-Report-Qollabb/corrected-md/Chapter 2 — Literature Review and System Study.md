# Chapter 2 — Literature Review and System Study

Chapter 2 — Literature Review and System Study
2.1 School Management and ERP Systems
School management systems are software applications used to organize and automate academic, administrative, financial, and reporting activities in educational institutions. Traditional school administration generally involves student admission records, class allocation, attendance registers, fee records, examination marks, staff information, and report preparation. When these functions are performed manually, the school depends heavily on registers, spreadsheets, printed receipts, and individual staff members. A school management system attempts to reduce this dependency by centralizing institutional data and supporting repeatable workflows.
Enterprise Resource Planning systems apply the same idea at a broader organizational level. ERP systems integrate multiple business processes into one information system so that departments do not maintain separate and inconsistent records. In the school context, an ERP-like system can integrate student information, academic structure, attendance, fee collection, examinations, reporting, and administrative monitoring. According to Laudon and Laudon (2020) [VERIFY APA], information systems improve coordination by allowing organizations to collect, process, store, and distribute information for decision-making and control.
In educational institutions, the need for reliable information is especially important because academic and administrative records affect students, parents, teachers, accounts staff, and school leadership. Student profiles, attendance history, fee balances, marks, and report cards must be accurate and accessible to authorized users. A school administration platform can therefore improve operational efficiency by reducing repeated data entry and by making records available through structured workflows. The School Portal project follows this principle by implementing student management, attendance, fees, examinations, reporting, analytics, logs, and audit-related features in one integrated web application.
However, not all school management systems solve the same problem. A single-school desktop or web application may digitize records for one institution, but it may not provide a scalable model for managing multiple schools under one platform. A SaaS-based approach can address this limitation by allowing multiple institutions to use the same hosted application while keeping their data logically separated. This makes the study of SaaS and multi-tenant design relevant to the proposed system.
2.2 Software as a Service (SaaS) Platforms
Software as a Service is a cloud computing service model in which users access software applications over a network, usually through a web browser, without directly managing the underlying infrastructure. The NIST definition of cloud computing describes cloud computing as convenient, on-demand network access to shared configurable computing resources and identifies Software as a Service as one of the major service models (Mell & Grance, 2011). In the SaaS model, the service provider manages the application, infrastructure, updates, and hosting environment, while end users access application features through a thin client such as a browser.
SaaS platforms are useful when many organizations require similar functionality but do not want to install or maintain separate software instances. For schools, this can mean lower infrastructure burden, centralized updates, browser-based access, and easier deployment. A school can access the hosted system through login credentials while the provider maintains the application environment. This model is especially suitable for administrative applications because most school operations involve structured data entry, reporting, and controlled user access.
SaaS systems also introduce important design responsibilities. The system must support authentication, authorization, secure data storage, backup, auditability, tenant isolation, and reliable access. In a SaaS school administration system, one school’s records must not be visible to another school. Therefore, SaaS design is not only a hosting decision but also an architectural decision. The application must be built with tenant identity, user responsibility, and data boundaries from the beginning.
The School Portal project adopts the SaaS concept by deploying one web application that supports multiple schools. It uses a shared application and single database with a tenant identifier called school_id. Each school operates independently while sharing the same application infrastructure. The project therefore applies the SaaS model within an MCA-level implementation by focusing on browser-based access, role-based modules, tenant isolation, and cloud deployment.
2.3 Multi-Tenant Architecture Models
Multi-tenancy is an architectural model in which one software application serves multiple customers or organizations, known as tenants. Each tenant should experience the system as its own environment, even though the software and infrastructure are shared. Microsoft’s tenancy guidance identifies different tenancy models, including single-tenant deployment, multi-tenant deployment, and hybrid approaches, with trade-offs in cost, isolation, scalability, and operational complexity (Microsoft, 2025) [VERIFY APA].
There are several common data architecture models for multi-tenant systems. In a separate-database-per-tenant model, each tenant has an independent database. This can provide strong physical isolation and easier tenant-specific backup or restore, but it increases infrastructure and maintenance complexity when the number of tenants grows. In a shared-database, separate-schema model, tenants share a database server but use separate schemas. This can improve manageability compared with separate databases but still requires schema-level operational handling.
In a shared-database, shared-schema model, all tenants share the same tables, and each tenant-owned record includes a tenant key. This model is cost-effective and simpler to deploy, but it requires disciplined application-level enforcement of tenant boundaries. If tenant filtering is applied manually and inconsistently, cross-tenant data exposure can occur. Therefore, global scopes, middleware, policies, services, and database constraints are important in such systems.
The School Portal project uses the shared-database, shared-schema model. Tenant-owned records are separated through the school_id column. Tenant context is resolved from the authenticated user and enforced through Laravel middleware, Eloquent global scopes, policies, and service-layer checks. This model was suitable for the project because it supports multiple schools in one application while remaining simple enough to implement, test, document, and demonstrate within the MCA project scope.
2.4 Laravel PHP Framework
Laravel is a PHP web application framework that provides a structured foundation for building modern web applications. It includes routing, middleware, controllers, validation, Eloquent ORM, Blade templates, authentication support, authorization policies, migrations, seeders, queues, and testing utilities. The Laravel documentation describes the framework as providing a structure and starting point for creating applications, along with features such as dependency injection, database abstraction, and testing support (Laravel, 2026).
Laravel follows conventions that encourage maintainability. Routes define request entry points, controllers organize request handling, form requests validate input, services can contain business logic, policies enforce authorization, and models represent database tables and relationships. This structure supports the layered monolithic design used in School Portal. Instead of writing all logic inside views or controllers, the application separates responsibilities into maintainable classes.
Laravel also supports security-related practices that are useful for school administration systems. The framework includes CSRF protection, password hashing, validation, authentication scaffolding through starter kits, authorization gates and policies, session handling, and middleware-based route protection. Laravel’s CSRF documentation explains that a token is generated for each active user session to verify that authenticated users are the ones making state-changing requests (Laravel, 2026). These built-in mechanisms reduce the amount of security code that must be written manually.
For School Portal, Laravel 13 was selected because it supports MVC-style organization, Blade views, Eloquent relationships, migrations, testing, middleware, policies, and service-layer development. These capabilities directly match the project requirements for authentication, RBAC, tenant context, student records, attendance, fees, examinations, reports, logs, audits, and deployment-ready structure.
2.5 MySQL Relational Database
MySQL is a relational database management system widely used for web applications. Relational databases organize data into tables with rows, columns, keys, indexes, and relationships. This model is suitable for school administration because school records are naturally relational. A student belongs to a school, an enrollment belongs to a student and academic year, attendance belongs to a student and date, fees belong to students and structures, and examination results belong to students, exams, and subjects.
The MySQL 8.0 Reference Manual documents MySQL 8.0 through version 8.0.46 and provides guidance for SQL operations, indexing, storage engines, constraints, security, and administrative behavior (Oracle, 2026). These capabilities are relevant to the School Portal database design because the system requires reliable storage for tenant records, academic data, financial records, examination results, activity logs, audit logs, and backup logs.
A relational model also helps enforce consistency through primary keys, foreign keys, indexes, unique constraints, and transactional operations. In School Portal, fee collection, attendance entry, examination processing, and report generation depend on consistent relationships between tables. For example, a fee payment must be connected to the correct student fee record, and an attendance record must be connected to the correct student enrollment context. MySQL supports these structured relationships effectively.
The project uses MySQL 8 as the production database and SQLite :memory: for automated tests. MySQL was appropriate for production because it supports relational integrity, indexing, query optimization, and operational stability. The database design contains 28 tables grouped into platform, academic, students, attendance, fees, examinations, and system areas.
2.6 Bootstrap and Web UI Frameworks
A web application’s usability depends not only on backend correctness but also on clear and consistent interface design. Bootstrap is a front-end framework that provides reusable CSS and JavaScript components for responsive, mobile-first websites. The Bootstrap documentation describes it as a toolkit for building responsive layouts and interfaces using containers, grids, forms, buttons, navigation, utilities, and components (Bootstrap, 2026).
For administrative systems, a UI framework helps maintain consistency across forms, tables, dashboards, filters, reports, alerts, cards, and navigation menus. Without a consistent interface, users may find it difficult to complete routine tasks such as registering students, marking attendance, collecting fees, or generating reports. Bootstrap reduces interface inconsistency by offering standard patterns for layout and components.
School Portal uses Blade templates with Bootstrap 5. Blade allows server-side rendering of role-specific screens, and Bootstrap provides visual consistency. This combination is suitable for the project because it avoids the complexity of a separate frontend framework while still producing a clean, responsive browser interface. It also supports the project’s maintainability principle because screens remain within Laravel’s view structure.
The user interface design supports role-specific navigation. Super Admin, School Admin, Teacher, and Accountant users see different menu items according to their responsibilities. The combination of backend authorization and frontend menu control improves usability while preserving security. Hidden menu items are not treated as the only protection; backend policies and middleware still enforce access rules.
2.7 Web Application Security Practices
Security is a major concern in web-based school administration systems because the application stores student records, user information, attendance, fee records, examination results, activity logs, and audit trails. A security weakness could expose sensitive records or allow unauthorized modification of academic or financial data. Therefore, secure design must be included at authentication, authorization, validation, file storage, tenant isolation, and deployment levels.
OWASP identifies broken access control as one of the most critical web application security risks. The OWASP Top 10 guidance explains that access-control failures can lead to unauthorized data access, modification, or privilege misuse (OWASP Foundation, 2021). This is highly relevant to School Portal because different roles must be restricted to different modules and tenant records. A Teacher must not access unrelated school data, and an Accountant must not manage academic records or examinations.
The project addresses these risks through a layered security model. Authentication verifies user identity. RBAC controls which modules a role may attempt to access. Policies check whether a user may act on a particular record. Tenant context and global scopes restrict tenant-owned data to the active school. Service-layer validation checks business rules before data changes are committed. CSRF protection and form validation reduce risks from forged requests and invalid input.
Security is also important in production configuration. A deployed Laravel application should not expose debug details to public users. The School Portal deployment uses production configuration with HTTPS and APP_DEBUG=false. Private student photos and backup files are treated as private resources. These practices support the security and privacy expectations of a school management SaaS system.
2.8 Software Development Life Cycle
A Software Development Life Cycle provides a structured method for planning, designing, implementing, testing, deploying, and maintaining software. Traditional models such as waterfall follow a linear sequence, while agile models emphasize iterative development, working software, and response to change. The Agile Manifesto values working software, collaboration, and responding to change over rigid process-heavy development (Beck et al., 2001).
The School Portal project followed a phased agile-style approach. The implementation was organized into phases rather than attempting to build all modules at once. Early phases focused on planning, architecture, database design, authentication, user management, multi-tenancy, and RBAC. Later phases implemented academic structure, student management, attendance, fees, examinations, reports, analytics, system operations, testing, deployment, and documentation.
This phased approach was suitable because the project had many interconnected modules. For example, attendance required academic structure and student enrollment to exist first. Fee assignment required student and academic records. Examination processing required classes, subjects, students, and grade scales. Reporting and analytics depended on completed operational modules. Developing the system in phases reduced risk and allowed each foundation to be validated before dependent features were added.
The agile nature of the process appeared in iterative reviews, testing, corrections, and documentation updates. Each phase produced implementation outputs and validation evidence. This helped ensure that the system remained aligned with the MCA deadline, Qollabb submission requirements, and academic demonstration expectations. The approach also supported maintainability because decisions were documented in project files such as the roadmap, governance record, decision log, changelog, testing strategy, and deployment guide.
2.9 Comparative Analysis
The proposed School Portal system can be compared with traditional manual administration and standalone school software. Manual administration is simple to start but becomes difficult to scale. Standalone software improves digitization but may still be limited to one institution or one deployment. A multi-tenant SaaS approach provides centralized deployment, shared maintenance, and independent tenant operation.
Table 2.1: Traditional vs Standalone Software vs Multi-Tenant SaaS
Parameter
Traditional manual system
Standalone school software
Proposed multi-tenant SaaS
Data storage
Paper files, registers, and spreadsheets.
Local or single-school database.
Shared SaaS database with tenant isolation using school_id.
Accessibility
Limited to physical location or local files.
Usually limited to installed system or specific school network.
Browser-based access through cloud-hosted application.
Multi-school support
Difficult and mostly duplicated manually.
Requires separate installation or database for each school.
Multiple schools supported in one application.
Data consistency
High risk of duplicate and inconsistent records.
Improved consistency within one school.
Centralized structure with tenant-scoped records.
Reporting
Manual, slow, and dependent on staff effort.
Available but often school-specific.
Role-aware reports and analytics across permitted scope.
Security
Physical control and informal access rules.
Basic login-based control.
Authentication, RBAC, policies, tenant context, and audit evidence.
Maintenance
Manual files require physical handling.
Each installation may need separate updates.
Central application can be updated once for all tenants.
Cost model
Low software cost but high manual effort.
Separate setup and maintenance per school.
Shared infrastructure reduces duplication.
Auditability
Limited record of who changed information.
Depends on software design.
Activity logs and audit trail included.
Scalability
Poor for growing institutions.
Moderate for one institution.
Better suited for multiple schools under SaaS model.
The comparison shows that School Portal offers advantages in centralization, tenant support, role separation, reporting, and maintainability. It is more structured than a manual system and more scalable than a single-school application. However, it also requires careful tenant isolation and security design, which the project addresses through native Laravel multi-tenancy, policies, services, and testing.
2.10 Research Gap
The review of school management systems, SaaS design, multi-tenant architecture, Laravel development, relational databases, UI frameworks, security practices, and SDLC methods shows a clear gap relevant to the project. Many school administration processes are still handled through manual files, spreadsheets, or disconnected tools. These approaches create duplication, errors, slow reporting, and weak accountability. Standalone school software can reduce some of these problems, but it does not always solve multi-school administration or shared cloud deployment needs.
A second gap exists in affordable and explainable multi-tenant design for academic-level implementation. Enterprise SaaS platforms may use complex microservices, separate databases, cloud-native infrastructure, or paid external services. These designs may be powerful, but they can be too complex for an MCA major project and difficult to explain in viva. The School Portal project fills this gap by implementing native single-database multi-tenancy using school_id, TenantContextMiddleware, global scopes, policies, and service validation.
A third gap exists between feature implementation and testable security. A school administration system should not only provide screens for students, attendance, fees, and examinations; it must also prove that users cannot access unauthorized modules or cross-tenant records. The project addresses this by combining RBAC, tenant-aware queries, automated tests, manual smoke tests, and audit evidence.
Therefore, the proposed School Portal system fills a practical and academic gap. It demonstrates a secure, maintainable, cloud-deployed, multi-tenant school administration SaaS platform using Laravel 13, PHP 8.4, MySQL 8, Blade, and Bootstrap 5. It remains within the MCA project scope while covering the major operational modules required by schools.
Draft Reference List for This Chapter
Agile Alliance. (2001). Manifesto for Agile Software Development. https://agilemanifesto.org/
Beck, K., Beedle, M., van Bennekum, A., Cockburn, A., Cunningham, W., Fowler, M., Grenning, J., Highsmith, J., Hunt, A., Jeffries, R., Kern, J., Marick, B., Martin, R. C., Mellor, S., Schwaber, K., Sutherland, J., & Thomas, D. (2001). Manifesto for Agile Software Development. Agile Alliance.
Bootstrap. (2026). Bootstrap 5.3 documentation. https://getbootstrap.com/docs/5.3/
Jagli, D., Purohit, S., & Chandra, N. S. (2019). SaaS CloudQual: A quality model for evaluating Software as a Service on the cloud computing environment. arXiv. [VERIFY APA]
Laravel. (2026). Laravel 13.x documentation. https://laravel.com/docs/13.x
Laudon, K. C., & Laudon, J. P. (2020). Management information systems: Managing the digital firm. Pearson. [VERIFY APA]
Mell, P., & Grance, T. (2011). The NIST definition of cloud computing (Special Publication 800-145). National Institute of Standards and Technology. https://doi.org/10.6028/NIST.SP.800-145
Microsoft. (2025). Tenancy models for a multitenant solution. Microsoft Learn. [VERIFY APA]
Microsoft. (2025). Architectural approaches for storage and data in multitenant solutions. Microsoft Learn. [VERIFY APA]
Oracle. (2026). MySQL 8.0 reference manual. Oracle. https://dev.mysql.com/doc/refman/8.0/en/
OWASP Foundation. (2021). OWASP Top 10:2021 — A01 Broken Access Control. https://owasp.org/Top10/2021/A01_2021-Broken_Access_Control/
PHP Group. (2024). PHP 8.4 release announcement. https://www.php.net/releases/8.4/en.php
Sommerville, I. (2016). Software engineering (10th ed.). Pearson. [VERIFY APA]
Word Count
Approximate word count: 2,760 words.

