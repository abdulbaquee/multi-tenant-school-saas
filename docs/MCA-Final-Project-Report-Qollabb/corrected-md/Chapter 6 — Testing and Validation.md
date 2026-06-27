# Chapter 6 — Testing and Validation

Chapter 6 — Testing and Validation
6.1 Testing Strategy
Testing and validation were essential parts of the School Portal project because the application handles multi-tenant school data, role-based access, student records, attendance, fee records, examination results, reports, activity logs, audit logs, and backup history. The testing strategy was designed to verify not only whether screens loaded correctly, but also whether business rules, security restrictions, tenant isolation, and workflow integrity were enforced consistently across the system.
The testing approach followed multiple levels of validation: unit testing, feature testing, integration testing, system testing, user acceptance testing, security testing, and tenant isolation testing. Unit testing was used to verify individual classes, services, policies, form requests, helper logic, and business rules. Feature testing was used to validate complete HTTP workflows from the user’s perspective, including authentication, authorization, student management, attendance, fees, examinations, reporting, analytics, activity logs, audit trail, and backup management. Integration testing was used to verify the interaction between modules, such as Student to Enrollment, Student to Attendance, Student to Fee Assignment, Fee Payment to Receipt, Examination to Report Card, and Reports to Analytics.
The overall testing philosophy treated testing as a continuous activity rather than a final step after development. Each feature was designed, developed, tested, verified, and documented before being treated as complete. This approach was suitable for the phased development model used in the project, where the application progressed from authentication and tenancy foundations to RBAC, academic structure, students, attendance, fees, examinations, reports, analytics, and system operations. The test strategy therefore supported both functional correctness and release readiness.
6.2 Test Environment
The automated test environment was configured for Laravel 13 and PHP 8.4 using PHPUnit as the test runner. The automated tests used SQLite :memory: as the test database. This approach allowed tests to run in an isolated environment without affecting development or production data. The testing configuration also forced the application environment to testing, used an array-based cache and session configuration, disabled unnecessary external services, and used synchronous queue execution for predictable test behavior.
Using SQLite :memory: for automated testing provided fast and repeatable execution. Each test run could create and migrate a clean in-memory database, execute the required assertions, and then discard the database after completion. This was especially important for tenant isolation tests because the test suite needed to verify cross-school behavior repeatedly without leaking data between test cases. The test environment also helped validate migrations, model relationships, policies, services, and feature routes under controlled conditions.
The production environment used MySQL 8 as the persistent database. Therefore, automated testing validated application behavior in an isolated testing database, while production deployment validated the application using the real production database technology. Manual smoke testing was performed through browser-based workflows on the live deployed School Portal system. This combination of automated tests and manual validation helped confirm both internal correctness and real user workflow readiness.
Table 6.1: Test Types and Tools
Test type
Purpose
Tools / environment used
Unit Testing
Validate services, helpers, policies, form requests, and business rules in isolation.
PHPUnit, Laravel test harness, SQLite :memory:
Feature Testing
Validate application behavior through HTTP requests and responses.
PHPUnit, Laravel Feature tests, php artisan test
Integration Testing
Verify interaction between modules such as enrollment, attendance, fees, examinations, and reports.
PHPUnit, Laravel test database, SQLite :memory:
System Testing
Validate complete workflows across multiple modules.
Browser testing, Laravel application, seeded data
User Acceptance Testing
Confirm that users can complete business tasks through the UI.
Browser smoke testing, demo users
Security Testing
Verify authentication, authorization, CSRF, validation, RBAC denials, and unauthorized access handling.
PHPUnit Feature tests, policies, browser checks
Tenant Isolation Testing
Confirm that one school cannot access another school’s data.
Automated tenant tests, two-browser SHA vs SHB smoke test
Production Validation
Confirm live deployment readiness.
OVH VPS, Nginx, PHP 8.4, MySQL 8, HTTPS live URL
6.3 Automated Test Results — 394 Tests Passing
The final automated regression suite completed successfully with 394 tests passing. This result provided strong evidence that the core application workflows were working as expected at the time of final validation. The automated suite included both unit-level and feature-level tests, covering authentication, dashboards, tenancy, RBAC, academic structure, student management, attendance, fee management, examination workflows, reporting, analytics, activity logs, audit trail, and backup management.
The automated test suite also included 3,312 assertions. Assertions are important because they represent the specific conditions checked during testing. A single test may contain multiple assertions, such as confirming that a route is accessible to an authorized role, inaccessible to an unauthorized role, returns the correct status code, stores the correct database record, rejects forged input, or displays the expected role-specific interface. The assertion count therefore indicates that the test suite did not merely execute routes but also verified expected behavior at multiple points.
The final automated result of 394 passing tests was used as the main evidence for regression stability. Since the project includes strict tenant isolation and role boundaries, a full test suite pass was important before final reporting. The passing suite indicated that the implemented modules remained compatible after later additions such as reporting, analytics, activity logs, audit trail, and backup management.
6.4 Feature and Integration Testing
Feature testing validated the application from the user’s perspective. Instead of testing only individual methods, feature tests exercised complete HTTP workflows such as login, dashboard access, student listing, attendance entry, fee collection, marks entry, report generation, log access, audit access, and backup actions. These tests were important because the system is mainly used through browser-based screens and role-specific workflows.
Authentication and authorization feature tests verified login behavior, guest protection, inactive-user rejection, role restrictions, dashboard access, navigation visibility, direct URL protection, and permission enforcement. User and RBAC feature tests verified that the four fixed roles remained protected, that Super Admin authority was preserved, and that school-level users could not mutate platform-level role definitions. These tests helped prove that role-based access control worked across routes, menus, controllers, policies, and services.
Integration testing verified that connected modules worked together correctly. Student records were validated with enrollment records. Attendance records were validated against students, academic years, classes, and sections. Fee assignments were connected with fee structures, students, payments, receipts, outstanding balances, and transaction records. Examination setup was connected with exam subjects, marks entry, grade scales, results, and report cards. Reporting and analytics were tested as downstream outputs that depended on accurate records from other modules.
6.5 Security Testing
Security testing focused on authentication, authorization, RBAC denials, unauthorized access, CSRF-protected workflows, validation, tenant isolation, file access, and privacy-sensitive reporting. The system contains sensitive academic and financial data, so security validation was necessary to ensure that users could access only the modules and records permitted by their roles and tenant boundaries.
RBAC denial testing verified that unauthorized roles could not access restricted modules. For example, Teacher and Accountant users were denied access to System Operations. Accountant users were denied examination administration workflows. Teacher users were denied fee administration and user management workflows. School Admin users could access Activity Logs and Audit Trail for their own school, but Backup Management remained restricted to Super Admin. These checks confirmed that module visibility and backend route authorization worked together.
Security testing also verified that permission codes alone did not bypass record-level checks. Policies and service-layer validations were used to confirm that users could not access records outside their school, outside their assignment scope, or outside their workflow responsibility. This was important because the system uses shared application code and a shared database, so authorization had to be enforced consistently at every sensitive access point.
6.6 Tenant Isolation Testing
Tenant isolation testing was one of the most critical validation areas for School Portal. The project uses a single-database multi-tenant model, meaning multiple schools share the same database schema while tenant-owned records are separated using school_id. Therefore, the test suite had to prove that one school could not list, search, view, update, route-bind, modify, or delete another school’s records.
Automated tenant isolation tests verified behavior for valid tenant context, platform context, unresolved context, forged ownership, and cross-school access attempts. A school-level user was expected to access only records belonging to the assigned school. Cross-tenant route-model binding was expected to return a not-found response rather than reveal that the record existed under another school. Tenant-owned record creation was expected to derive school_id from TenantContext instead of trusting browser input.
Tenant isolation testing also covered lifecycle rules. Inactive, missing, or soft-deleted schools were not allowed to continue normal tenant sessions. Deactivation rules preserved tenant data while preventing unauthorized access. These checks supported the security objective that tenant separation must be enforced automatically and consistently, not manually on selected screens only.
6.7 Manual Smoke Testing
Manual smoke testing was performed to validate that the deployed application could be operated through a real browser by the four canonical roles. The four roles tested were Super Admin, School Admin, Teacher, and Accountant. The Super Admin workflow verified platform-level dashboard access, school listing, users, reports, analytics, System Operations, and HTTPS access. The School Admin workflow verified own-school dashboard, students, attendance, fees, examinations, reports, and restricted access to Super Admin-only screens. The Teacher workflow verified assigned academic access such as attendance and marks-related screens, while confirming that fee administration, user management, schools, and System Operations were not exposed. The Accountant workflow verified fee collection, receipts, outstanding balances, financial reports, and absence of examination administration or System Operations access.
A two-browser tenant isolation smoke test was also completed. In this test, one browser session used Springdale High A credentials, while another browser or private window used Springdale High B credentials. Both sessions opened student-related screens and reports. The expected result was that Springdale High A displayed only SHA data and Springdale High B displayed only SHB data. The test passed, confirming that tenant separation was visible at the browser level in addition to being validated by automated tests.
The manual smoke test complemented the automated suite because it verified real navigation, dashboard visibility, browser sessions, menus, HTTPS access, and screenshots required for the MCA project report. Manual testing did not replace automated testing, but it provided practical evidence that the application was ready for demonstration and evaluation.
[SCREENSHOT: php artisan test output — 394 passed]
[SCREENSHOT: Tenant isolation SHA vs SHB side by side]
6.8 Test Case Summary
The following test case summary presents representative validation cases across major modules. The status is marked as Pass for documented automated test coverage and documented smoke-test outcomes.
Table 6.2: Test Case Summary
Test ID
Module
Input/Precondition
Expected Output
Actual Output
Status
TC-01
Authentication
Valid Super Admin credentials entered on login screen.
Super Admin dashboard opens successfully.
Super Admin dashboard opened successfully.
Pass
TC-02
Authentication
Invalid or inactive user attempts login.
Access is rejected and protected screens remain unavailable.
Login was rejected for invalid or inactive access.
Pass
TC-03
Dashboard
Super Admin logs in with platform role.
Platform dashboard and authorized navigation are displayed.
Platform dashboard and authorized navigation were displayed.
Pass
TC-04
School Management
Super Admin opens Schools list.
Registered schools such as SHA, SHB, and SHC are visible.
Schools list loaded with demo school records.
Pass
TC-05
School Admin Access
School Admin logs in for Springdale High A.
Own-school dashboard and school modules are visible.
School Admin dashboard and own-school modules loaded.
Pass
TC-06
RBAC
School Admin attempts Backup Management access.
Backup Management is denied because it is Super Admin-only.
Backup access was denied / unavailable to School Admin.
Pass
TC-07
RBAC
Teacher attempts System Operations access.
System Operations is not visible or direct access is denied.
Teacher could not access System Operations.
Pass
TC-08
RBAC
Accountant attempts Examination administration access.
Examination administration is unavailable or denied.
Accountant could not access examination administration.
Pass
TC-09
Student Management
School Admin opens own-school student list.
Only students belonging to the assigned school are listed.
Own-school student list loaded successfully.
Pass
TC-10
Tenant Isolation
SHA School Admin attempts to view SHB student data.
Cross-school data is blocked or returned as not found.
SHB data was not exposed to SHA user.
Pass
TC-11
Tenant Isolation
Two-browser SHA vs SHB student list comparison.
SHA and SHB sessions show separate school data.
Separate school data was confirmed side by side.
Pass
TC-12
Attendance
Teacher opens assigned attendance workflow.
Attendance entry is available only for assigned section scope.
Assigned attendance workflow opened successfully.
Pass
TC-13
Attendance
Teacher attempts unassigned attendance scope.
Access is denied or records are unavailable.
Unassigned attendance access was blocked.
Pass
TC-14
Fee Management
Accountant collects fee for permitted student fee record.
Fee receipt is generated and payment history updates.
Fee collection and receipt workflow completed.
Pass
TC-15
Fee Management
Sandbox transaction record is viewed after fee workflow.
Local sandbox transaction evidence is displayed.
Sandbox transaction evidence was available.
Pass
TC-16
Examination
Teacher enters marks for assigned subject.
Marks entry is allowed only within assigned scope.
Assigned marks entry workflow completed.
Pass
TC-17
Examination
Report card is generated by authorized user.
Report card view and print workflow are available.
Report card workflow loaded successfully.
Pass
TC-18
Reporting
School Admin opens reports for own school.
Reports display own-school filtered data only.
Own-school reports loaded successfully.
Pass
TC-19
Analytics
Authorized admin opens analytics page.
Charts and summaries load without exposing unnecessary PII.
Analytics charts loaded successfully.
Pass
TC-20
Activity Log
School Admin opens Activity Logs.
Own-school activity logs are shown.
Own-school activity logs loaded successfully.
Pass
TC-21
Audit Trail
School Admin opens Audit Trail.
Own-school audit records are shown.
Own-school audit trail loaded successfully.
Pass
TC-22
Backup Management
Super Admin creates or views backup history.
Backup Management functions are available to Super Admin.
Super Admin backup workflow was available.
Pass
TC-23
Production Deployment
User opens live URL with HTTPS.
School Portal landing/login page loads securely.
Live HTTPS site loaded successfully.
Pass
TC-24
Automated Suite
php artisan test executed in test environment.
Full regression suite passes.
394 tests passed.
Pass
The test results demonstrate that the implemented application satisfied its primary validation goals. Functional workflows were verified through feature and integration tests. Security restrictions were validated through RBAC denials and unauthorized access checks. Tenant isolation was validated through both automated tests and two-browser smoke testing. Manual testing confirmed that the four role-based workflows were usable through the deployed web interface. Therefore, the system was considered ready for MCA final report evidence and Qollabb demonstration.
Word Count
Approximate word count: 2,430 words.

