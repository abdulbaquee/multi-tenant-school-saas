# Chapter 7 — Results and Discussion

Chapter 7 — Results and Discussion
7.1 System Outputs Overview
The completed School Portal system produced a working Multi-Tenant School Administration Management SaaS Platform for managing multiple schools through one Laravel-based web application. The system delivered role-based dashboards, tenant-aware school administration, academic structure management, student records, attendance workflows, fee collection, examination management, report cards, operational reports, analytics, activity logs, audit trail, and backup management. These outputs demonstrate that the project achieved its central objective of building a cloud-hosted school administration platform with native school_id based multi-tenancy.
The main operational output of the system is a centralized web portal where different users can perform their responsibilities based on assigned roles. Super Admin users can manage the platform and school tenants. School Admin users can manage their own school’s settings, users, academic setup, students, attendance, fees, examinations, reports, activity logs, and audit records. Teacher users can access assigned academic and attendance-related workflows. Accountant users can perform fee collection, receipt generation, outstanding balance review, and financial reporting.
The system also produced validation outputs such as automated test results, manual smoke-test evidence, tenant isolation screenshots, and production deployment evidence. The final test evidence showed 394 automated tests passing, and the deployed application was accessible through the live HTTPS URL. These outputs support the conclusion that the system was not only implemented but also validated through automated and manual testing.
7.2 Dashboard and Analytics Results
The dashboard implementation produced role-specific views for each major user type. The Super Admin dashboard provided a platform-level overview of schools, users, activity, audit information, and backup status. This dashboard helped represent the system as a SaaS platform rather than a single-school application. It allowed the Super Admin to observe the platform from a central administrative perspective.
[SCREENSHOT: Super Admin platform overview]
The School Admin dashboard produced an own-school operational summary. For Springdale High A, the dashboard displayed school-level information such as student counts, attendance status, fee-related summaries, examination status, recent activity, and quick access to school modules. The dashboard was tenant-aware, meaning that it summarized only the data belonging to the logged-in school. It did not combine or expose data from other schools such as Springdale High B or Springdale High C.
[SCREENSHOT: School Admin dashboard — Springdale High A]
The analytics output provided visual summaries for administrative decision-making. Instead of requiring the user to manually inspect multiple tables, the analytics screens presented school or platform information in summarized form. These results improved usability because administrators could quickly understand important operational indicators such as student distribution, fee status, attendance trends, examination progress, and system activity. The analytics results were useful for demonstration because they showed how raw records stored in different modules were converted into meaningful administrative information.
7.3 Module Operation Results
The Student Management module produced successful outputs for student listing, student profile viewing, student registration, enrollment management, status management, and private student photo handling. School Admin users could view and manage students within their own school. Teacher access was limited to assigned academic scope, and Accountant access was limited to student lookup inside fee-related workflows. This confirmed that student information was available to relevant users without becoming unrestricted across the platform.
[SCREENSHOT: Student list or student profile]
The Attendance Management module produced daily attendance entry and attendance summary outputs. Authorized School Admin users could manage attendance for eligible own-school students, while Teacher users could work only with directly assigned sections. The attendance results supported routine school operation because attendance could be recorded, reviewed, corrected where permitted, and summarized for reporting. This module also demonstrated assignment-based access control, especially for Teacher users.
[SCREENSHOT: Attendance entry or monthly summary]
The Fee Management module produced fee setup, fee assignment, fee collection, receipt, payment history, outstanding balance, and sandbox transaction outputs. School Admin users could manage fee configuration and assignment, while Accountant users could perform collection and financial review workflows. The system generated fee receipts and maintained payment history. The sandbox transaction workflow demonstrated payment transaction handling within academic scope, but it did not represent a production payment gateway integration.
[SCREENSHOT: Fee collection or receipt]
The Examination and Report Card module produced examination setup, marks entry, result calculation, grade scale usage, report card view, and print-friendly report card outputs. School Admin users could manage examinations and process results, while Teacher users could enter marks only within assigned class and subject scope. This confirmed that the examination module supported academic evaluation workflows while preserving role and tenant restrictions.
[SCREENSHOT: Examination result or report card]
The Reporting module produced filtered operational reports for student, attendance, fee, examination, activity, audit, and other permitted areas. Reports supported administrative review and were restricted by role and tenant context. School Admin reports showed own-school data, Teacher reports showed assigned academic data, Accountant reports focused on financial records, and Super Admin reports focused on platform-level information. These outputs demonstrated that reporting was integrated with the authorization and tenancy model.
[SCREENSHOT: Reports hub or Analytics charts]
7.4 Multi-Tenant Isolation Results
The most important result of the project was successful multi-tenant isolation. Since School Portal uses one application and one database for multiple schools, tenant isolation was necessary to ensure that one school could not access another school’s records. The implemented system used school_id, TenantContextMiddleware, TenantContext, global scopes, policies, and service-layer validation to enforce tenant boundaries.
The concurrent demo was performed using separate browser sessions for Springdale High A and Springdale High B. In one browser session, the user logged in as a School Admin for SHA. In the second browser or private window, the user logged in as a School Admin for SHB. When both sessions opened student and school-level screens, each session displayed only its own school’s information. SHA data remained visible only to the SHA user, and SHB data remained visible only to the SHB user.
This result confirmed that tenant isolation was not only implemented internally but was also visible through real browser behavior. It also verified that tenant context was derived from the authenticated user rather than from a user-controlled form field or URL parameter. The side-by-side test supported the claim that the system functions as a multi-tenant SaaS platform and not simply as a single-school application with multiple users.
[SCREENSHOT: Tenant isolation]
7.5 Cloud Deployment Results
The project was successfully deployed to a live cloud server and made available through the School Portal HTTPS URL. The production deployment provided evidence that the system could run outside the local development environment. The deployment used an OVH VPS with Ubuntu, Nginx, PHP-FPM, MySQL, and HTTPS configuration. This allowed the system to be demonstrated through a public browser-accessible URL for academic evaluation.
Table 7.1: Production Stack
Component
Production value
Hosting provider
OVH VPS
Operating system
Ubuntu 22.04
Web server
Nginx 1.18.0
Server-side language
PHP 8.4.21
Database
MySQL 8.0.46
Application framework
Laravel 13
Frontend technology
Blade and Bootstrap 5
Public URL
https://schoolportal.pagescorch.com
Security mode
HTTPS enabled
Environment mode
Production with APP_DEBUG=false
The production result showed that the application could be accessed securely over HTTPS. The live landing page displayed School Portal branding, and authenticated role-based workflows could be accessed through demo credentials. The deployment result is significant because it demonstrates that the project was not limited to code execution on a local machine but was configured as a real hosted web application.
[SCREENSHOT: Live landing page with HTTPS URL visible]
7.6 Comparison: Traditional Process vs School Portal
The system results can be better understood by comparing the proposed application with a manual or paper-based school administration process. Traditional school administration often depends on registers, spreadsheets, physical receipts, manual attendance sheets, and separate files for different departments. This can lead to duplicated work, slow reporting, poor visibility, and higher risk of human error. School Portal reduces these limitations by centralizing academic, administrative, financial, and reporting workflows in one role-aware web application.
Table 7.2: Manual/Paper Process vs School Portal
Parameter
Manual or paper-based process
School Portal proposed system
Data entry
Repeated across registers, spreadsheets, and files.
Centralized entry through web forms and module screens.
Time required
Higher time required for searching, calculation, and reporting.
Faster access through dashboards, filters, reports, and summaries.
Error possibility
Higher chance of manual calculation and duplication errors.
Reduced errors through validation, structured records, and service workflows.
Attendance management
Maintained in physical registers or separate sheets.
Daily attendance entry, correction, history, and reports.
Fee handling
Manual receipts and outstanding balance tracking.
Fee assignment, collection, receipt, payment history, and outstanding balances.
Examination records
Manual marks sheets and separate report preparation.
Exam setup, marks entry, result processing, and report card output.
Security
Physical access control and informal sharing of files.
Authentication, RBAC, policies, tenant context, and HTTPS deployment.
Multi-school support
Difficult to manage centrally without duplication.
Multiple schools managed through one SaaS platform using tenant isolation.
Reporting
Slow and often manually prepared.
Role-aware reports, filters, exports where permitted, and analytics dashboards.
Auditability
Limited evidence of who changed records.
Activity logs and audit trail provide accountability.
The comparison shows that School Portal improves administrative control, data organization, role separation, reporting speed, and tenant-level security. It does not remove the need for school staff judgment, but it provides a structured platform that can reduce repetitive administrative effort and improve visibility of school operations.
7.7 Limitations
Although School Portal achieved the defined MCA project objectives, the implementation has clear limitations. The system does not include a parent portal or student portal. Therefore, parents and students cannot log in directly to view attendance, fees, report cards, or announcements. These features remain future enhancements and are not claimed as implemented in the present scope.
The Fee Management module includes a sandbox transaction workflow only. It does not include a production payment gateway such as live Razorpay, Stripe, or bank integration. Therefore, the system demonstrates fee collection and local transaction evidence but does not process real online payments. This limitation was intentional because production payment gateway integration requires additional compliance, credentials, settlement handling, and payment-provider approval.
The current scope also does not include a mobile application, SMS gateway, AI features, or custom role creation. The implemented system uses four fixed canonical roles: Super Admin, School Admin, Teacher, and Accountant. This fixed RBAC model keeps the project secure, testable, and suitable for academic evaluation, but it does not support institution-defined custom roles.
Backup Management is implemented as a manual Super Admin workflow rather than a fully automated disaster recovery system. School Admin users can access Activity Logs and Audit Trail for their own school, but Backup Management remains restricted to Super Admin. Advanced automated backup scheduling, restore workflows, and offsite backup policies can be considered as future work. These limitations define the boundary of the current implementation and help keep the results honest and academically accurate.
Word Count
Approximate word count: 1,590 words.
List of Figures in This Chapter
Figure 7.1: Super Admin platform overview Figure 7.2: School Admin dashboard — Springdale High A Figure 7.3: Student list or student profile Figure 7.4: Attendance entry or monthly summary Figure 7.5: Fee collection or receipt Figure 7.6: Examination result or report card Figure 7.7: Reports hub or Analytics charts Figure 7.8: Tenant isolation Figure 7.9: Live landing page with HTTPS URL visible
List of Tables in This Chapter
Table 7.1: Production Stack Table 7.2: Manual/Paper Process vs School Portal

