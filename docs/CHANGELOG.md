# CHANGELOG

Version: 1.0
Status: Draft

All notable changes to the Multi-Tenant School Administration Management SaaS Platform project will be documented in this file.

The format is inspired by Keep a Changelog and adapted for MCA project development milestones.

Phase numbering follows the canonical sequence in `DEVELOPMENT_ROADMAP.md`.

---

# [0.1.0] - Project Initiation & Planning

Date: 2026-06-18

Status: Completed

## Added

* Project scope finalized
* MCA major project planning completed
* Technology stack selection finalized
* Multi-tenant SaaS architecture selected
* GitHub repository created
* Initial project documentation prepared

## Technology Stack

* Laravel 13 (Installed)
* PHP 8.4
* MySQL 8
* Bootstrap 5 (Planned)
* Laravel Breeze (Planned)
* Native Laravel Multi-Tenancy (school_id + Global Scopes)
* Git & GitHub

> Note: The multi-tenancy approach was revised on 2026-06-19 from Stancl Tenancy
> to Native Laravel Multi-Tenancy. See DECISIONS_LOG.md DECISION-005-R.

## Documentation Created

* README.md
* PROJECT_OVERVIEW.md
* DEVELOPMENT_ROADMAP.md
* TESTING_STRATEGY.md
* DECISIONS_LOG.md
* CHANGELOG.md

## Outcome

Project planning and governance phase completed.

---

# [0.2.0] - Repository & Framework Setup

Date: 2026-06-19

Status: Completed

## Added

* GitHub repository initialized
* Laravel 13 application installed
* Project structure finalized
* Documentation folders created
* Reports folder created
* Screenshots folder created
* Diagrams folder created

## Repository Structure

docs/
diagrams/
reports/
screenshots/
app/
bootstrap/
config/
database/
resources/
routes/
storage/
tests/

## Outcome

Development environment ready.

---

# [0.3.0] - Architecture & Database Design

Date: 2026-06-19

Status: Completed

## Completed

* Multi-tenant database architecture
* Entity Relationship Diagram (ERD)
* Database schema design
* Tenant isolation strategy
* Naming conventions
* Migration planning documentation

## Deliverables

* DATABASE_DESIGN.md
* ER_DIAGRAM.md
* TENANCY_DESIGN.md
* Migration blueprint only; no migrations generated during documentation remediation

---

# [0.3.1] - Phase 2 Documentation Remediation

Date: 2026-06-19

Status: Completed

## Changed

* Completed implementation-ready data dictionary for all 28 planned tables.
* Rebuilt ERD to include all 28 tables and corrected `schools` to `school_settings` as one-to-one.
* Added roles, permissions, role_permissions, academic_terms, payment_transactions, and backup_logs to the ERD.
* Created one canonical role permission matrix in MODULE_SPECIFICATIONS.md.
* Reconciled dashboard visibility, menus, reports, fees, audit logs, and backup access across documentation.
* Removed orphan settings references from scope and navigation.
* Standardized deletion strategy around soft deletes, historical retention, and `restrictOnDelete()`.
* Added secure file-storage policy for student photos, school logos, and backups.
* Added student data privacy, data access, data retention, and minor privacy guidance.
* Updated project governance status for documentation remediation.

## Notes

* Documentation only.
* No application code generated.
* No migrations generated.
* No models, controllers, or package changes made.

---

# [0.3.2] - Codex Governance Migration

Date: 2026-06-19

Status: Completed

## Changed

* Migrated AI governance from Cursor Rules to Codex `AGENTS.md` files.
* Added scoped instructions for root, application, database, tests, documentation, and resources.
* Replaced tracked Cursor rule files with thin compatibility bridges to `AGENTS.md`.
* Updated governance and tenancy documentation to reference the AGENTS hierarchy.

## Notes

* Documentation and governance only.
* No application code generated.
* No packages installed.

---

# [0.4.0] - Authentication & User Management

Date: 2026-06-20

Status: Completed

## Added

* Core authentication schema for schools, roles, permissions, role permissions, users, password resets, and sessions.
* Canonical role and permission seed data for Super Admin, School Admin, Teacher, and Accountant.
* Laravel Breeze authentication foundation for login, logout, password reset, email verification, password confirmation, and profile updates.
* User management foundation with policy-driven listing, create, edit, show, activation, and deactivation flows.
* User forms with explicit required-field indicators, optional password-update guidance, and clear success messages.
* Bootstrap 5 and Bootstrap Icons integration for Blade authentication and profile views.
* Responsive authenticated application shell with sidebar, top bar, breadcrumbs, footer, role-aware dashboard summaries, and Phase 2 navigation.
* Authentication, profile, and user-management feature tests, including inactive-user login rejection, guest protection, validation, authorization, role restrictions, deactivation, and cross-school scope enforcement.
* Dashboard access and navigation tests for all four canonical roles.

## Deferred To Later Phases

* Automatic tenant context and global-scope enforcement: Phase 3.
* Full role and permission management: Phase 4.

## Deliverables

* Login System: Completed
* User Management Foundation: Completed
* Dashboard and Navigation Foundation: Completed
* Phase 2 Automated Feature Tests: Completed

## Verification

* Phase 2 suite: 50 tests, 191 assertions passed.
* Full application suite: 52 tests, 193 assertions passed.
* No critical authentication, authorization, or cross-school access defect remained at the Phase 2 gate.

---

# [0.4.1] - Phase 3 Tenancy Design Remediation

Date: 2026-06-20

Status: Completed

## Changed

* Replaced implicit null-context bypass behavior with explicit Unresolved,
  Tenant, and Platform context states.
* Defined default-deny reads and writes when tenant context is unresolved.
* Defined middleware ordering before route-model binding and mandatory context
  cleanup after requests, jobs, commands, exceptions, and tests.
* Classified tenant registry, platform, hybrid identity, strict tenant-owned,
  and contextual-log tables.
* Documented the pre-authentication `users` exception and its Policy/Service
  isolation requirements.
* Defined school deactivation, session revocation, login denial, reactivation,
  and soft-deletion behavior.
* Reconciled School Settings as a Phase 3 deliverable.
* Expanded the mandatory tenant-isolation and lifecycle test contract.
* Added DECISION-026 for architectural traceability.

## Notes

* Documentation and prompt files only.
* No application code, migration, test, route, view, or package changes.

---

# [0.5.0] - Multi-Tenant Foundation

Date Started: 2026-06-20

Status: Completed

## Added

* Type-safe Unresolved, Tenant, and Platform context states.
* Execution-scoped TenantContext service with deterministic reset behavior.
* Authenticated TenantContext middleware registered after authentication and
  before route-model binding.
* Explicit Platform mode for valid active Super Admin users only.
* Active, missing, inactive, soft-deleted, and malformed school-user checks.
* Generic login denial and current-session invalidation for invalid school
  access.
* Foundational tests for transitions, request setup, cleanup, exceptions,
  sequential requests, malformed users, school lifecycle, and middleware order.
* Stateless default-deny TenantScope that resolves the active context for every
  query.
* Reusable BelongsToTenant trait with context-derived creation and immutable
  tenant ownership.
* Additive School Settings migration with documented defaults, one-to-one
  uniqueness, index, and `restrictOnDelete()` foreign key.
* SchoolSetting model and one-to-one School relationships.
* Automatic isolation tests for Unresolved, Tenant, Platform, forged ownership,
  sequential schools, and route-model binding behavior.
* Super Admin School Management with listing, search, status filters,
  registration, details, editing, activation, and deactivation.
* Policy, Form Request, service-layer, and navigation enforcement that denies
  School Management to School Admin, Teacher, and Accountant roles.
* Required deactivation reasons, timestamps, selective school-user session and
  remember-token revocation, retained tenant records, and safe reactivation.
* Automatic default School Settings creation for every newly registered school.
* Temporary tenant-context execution with exact prior-state restoration,
  including exception paths.
* Active-school enforcement for new school-user assignments.
* Administrator-controlled email verification for managed users: Super Admin
  has platform scope, while verified School Admin users are restricted to other
  users in their own school and cannot self-verify.
* School Admin-only School Settings workspace for profile, contact, academic,
  attendance, grading, and logo configuration within the active tenant.
* Automatic initialization of missing legacy School Settings rows under strict
  Tenant context.
* Public school-logo upload, display, replacement, and removal using generated,
  tenant-partitioned paths with image MIME and 2 MB validation.
* Super Admin read-only settings visibility through School Details.
* Service-boundary tenant-context enforcement for User Management and Dashboard
  workflows: Super Admin actors require explicit Platform context, while school
  actors require matching Tenant context.
* Direct service integration tests for Unresolved, mismatched, incorrect-mode,
  matching Tenant, and matching Platform execution paths.
* Canonical password complexity across every password-setting workflow.
* Administrator self-reset protection and target-only session revocation after
  authorized password reset.
* Generic, throttled forgot-password responses.
* Append-only, tenant-aware activity and audit recording foundations matching
  the documented data dictionary.
* Current login, logout, password, user, school, and School Settings activity
  and audit coverage with sensitive-value filtering.
* Platform-only audit ownership for Super Admin user transfers between schools,
  preventing source-school history from becoming visible to the target school.
* Dedicated password Form Requests and transactional `PasswordSecurityService`
  workflows for profile changes and token-based resets.
* Explicit CSRF, Blade escaping, bound-query, log immutability, and log-isolation
  security tests.
* DECISION-027 documenting the narrow trusted-system Platform callback used for
  credential-free failed-login activity recording.
* DECISION-028 documenting Platform-only ownership for cross-school transition
  history.
* Cross-school audit isolation and security-log transaction rollback regression
  tests added during the Phase 3 code-review remediation.

## Verification

* Full application suite: 132 tests and 655 assertions passed.
* Laravel Pint formatting validation passed.
* Composer configuration validation passed.
* Route inspection confirmed tenant context on every authenticated web route.
* Tenant-isolation review confirmed UserService and DashboardService now fail
  closed when invoked outside an authorized actor-aligned context.
* Security review confirmed password, session, recovery, logging, CSRF, output,
  query-binding, storage, deletion, and tenant controls for implemented modules.
* Code-review rerun found no remaining defects after cross-school audit ownership
  and password-workflow architecture remediation.
* Phase 3 release review approved progression with a 9.1/10 overall score and no
  blocking issues.
* Composer and npm audits reported no known dependency vulnerabilities.
* Clean temporary SQLite migration verification confirmed School Settings,
  activity logs, and audit logs migrate successfully; live MySQL 8 validation
  remains a deployment-target verification step.

## Planned

* Automatic tenant-isolation rollout to later module models in their roadmap phases

## Deliverables

* Schools Module
* Tenant Infrastructure

---

# [0.5.1] - Phase 4 RBAC Design Remediation

Date: 2026-06-21

Status: Completed

## Added

* DECISION-029 approving four fixed roles, a fixed permission catalog, and
  constrained mappings for school roles.
* Immutable matrix-approved Super Admin mapping, bootstrap-mapping reconciliation,
  and essential-permission lockout protection.
* Native Laravel permission-resolution, Policy, service, TenantContext, audit,
  immediate-effect, and session-revocation contracts.
* Role & Permission dashboard flow, menu visibility, read-only School Admin
  access, and module-grouped mapping controls.
* Phase 4 privilege-escalation, tenant-isolation, mapping-integrity, rollback,
  audit, menu, and session testing requirements.

## Notes

* Documentation and prompt files only.
* No application code, migration, route, view, test, or package changes.

---

# [0.6.0] - Role & Permission Management

Date: 2026-06-21

Status: Completed

## Added

* Native Role and Permission models and relationships
* Database-backed permission resolution
* Permission-aware Policies, Gates, services, menus, and dashboards
* Canonical `config/rbac.php` role, permission, maximum, default, and essential mappings
* Safe bootstrap mapping synchronization that preserves allowed revocations and existing passwords
* Immutable Role and Permission catalog enforcement
* Tenant-safe role assignment with target-only session and remember-token revocation
* Role-assignment activity and audit evidence
* Explicit complex bootstrap-password configuration with no known production fallback
* Target-only session revocation when a user moves between schools
* Fixed role directory and module-grouped effective-permission details
* Read-only school-role visibility for School Admin without Super Admin exposure
* Super Admin-only constrained mapping editor for School Admin, Teacher, and Accountant
* Server-enforced maximum and essential permission boundaries
* Transactional mapping replacement with deterministic stale-write fingerprints
* Platform-owned mapping activity and audit records containing sorted permission codes
* Permission-aware Roles & Permissions sidebar navigation and responsive Bootstrap views

## Verification

* Full application suite: 154 tests and 830 assertions passed.
* Local database synchronization confirmed four roles, 57 permissions, and the
  exact 31-permission Super Admin mapping.
* Laravel Pint and Composer configuration validation passed.
* Mapping tests cover allowed and denied routes, direct service context,
  immutable Super Admin mapping, essential retention, forged and duplicate IDs,
  out-of-bound grants, essential removal, empty payloads, stale submissions,
  logging rollback, audit payloads,
  immediate permission effects, menu visibility, and responsive markup.

## Review Gate

* Tenant-isolation review: approved after expanded direct-service context and
  inactive-school denial coverage.
* Security review: approved with route-specific CSRF evidence and clean Composer
  and npm advisory audits.
* Documentation review: approved after reconciling README, architecture, menu,
  security-log, roadmap, and testing evidence.
* Code review: no findings; Pint, production asset build, Composer validation,
  and the full regression suite passed.
* Release review: READY, overall 9.8/10, with no blocking issues.

## Deliverables

* RBAC Module

---

# [0.6.1] - Phase 5 Academic Design Remediation

Date: 2026-06-21

Status: Completed

## Added

* DECISION-030 defining the six-entity Phase 5 boundary and keeping Student
  Enrollment in Phase 6.
* Minimal Teacher Profile scope for same-tenant Section and Subject assignments.
* Transaction-safe current Academic Year lifecycle and non-overlapping Year and
  Term date rules.
* Explicit Super Admin read-only, School Admin management, Teacher assigned-read,
  and Accountant-denied access contracts.
* Tenant-safe parent, linked-user, and Teacher Profile relationship validation.
* Deactivation, dependency-safe archive, restore, reserved-unique-value, and
  historical-retention rules.
* Academic Structure activity/audit vocabulary and mandatory Phase 5 test
  coverage.

## Changed

* Removed Student Enrollment from the Academic Structure architecture grouping.
* Added Sections to the approved soft-delete decision.
* Reconciled roadmap, architecture, database, tenancy, modules, screens,
  security, testing, UI, governance, and project overview documentation.

## Notes

* Documentation and prompt files only.
* No migration, model, controller, service, route, view, test, package, or
  application file was created or changed.

## Verification

* Phase 5 readiness rerun: READY at 9.7/10 with no blocking issues.
* Existing application suite: 154 tests and 830 assertions passed.
* Documentation consistency and whitespace validation passed.

---

# [0.7.0] - Academic Management

Date: 2026-06-21

Status: Implementation Completed — Review Gate Pending

## Added

* Academic Years, Academic Terms, Classes, Teachers, Sections, and Subjects
  migrations with documented named indexes and restricted foreign keys.
* Canonical `AcademicYear`, `AcademicTerm`, `SchoolClass`, `Teacher`, `Section`,
  and `Subject` models.
* Automatic `BelongsToTenant` isolation, tenant-derived ownership, immutable
  ownership, relationships, casts, and approved soft-delete boundaries.
* School and User inverse Academic Structure relationships.
* User Management validation preventing role or school changes while an active
  or soft-deleted Teacher Profile is retained.
* Core schema, relationship, constraint, isolation, ownership, casting,
  soft-delete, and User safeguard tests.
* Academic Year and Academic Term Policies, Form Requests, services,
  controllers, routes, and responsive Blade workspaces.
* Platform-wide Super Admin read-only directories and details with school
  context, plus own-tenant School Admin management.
* Academic Year overlap prevention, retained-Term date protection,
  transactional single-current-year activation, deactivation guards, and
  non-current reactivation.
* Academic Term parent alignment, positive unique ordering, parent-range and
  overlap validation, deactivation, and parent-aware reactivation.
* Transaction-coupled `academic_structure` activity and sanitized audit
  evidence for every Year and Term mutation.
* Allowed, denied, tenant-isolation, exact-permission, lifecycle, rollback,
  navigation, and no-delete feature coverage.
* Teacher Profile Policy, scoped eligibility Requests, tenant-aware service,
  thin controller, retained-record routes, and responsive Blade workspace.
* Existing-user linking limited to active, non-deleted, same-school, unlinked
  Teacher-role users with immutable profile identity.
* Active, inactive, and archived directories; dependency-aware deactivation and
  archival; inactive restoration; and explicit activation.
* Reserved employee codes, retained-profile User safeguards, historical User and
  Class relationship rendering, and Section/Subject assignment visibility.
* Teacher Profile allowed, denied, tenant-isolation, exact-permission,
  eligibility, lifecycle, dependency, rollback, navigation, and retention tests.
* Class Policy, Form Requests, service, thin controller, retained-record routes,
  and responsive Blade workspace.
* Platform-wide Super Admin read-only Class visibility, own-tenant School Admin
  management, and Teacher read-only visibility limited to active assigned Classes.
* Normalized and retained unique Class names and codes, dependency-safe
  deactivation and archival, inactive restoration, and explicit activation.
* Class mutation activity/audit evidence and allowed, denied, isolation,
  exact-permission, lifecycle, rollback, navigation, and no-delete coverage.
* Section and Subject Policies, relationship-aware Form Requests, dedicated
  services, thin controllers, retained-record routes, and responsive Blade
  workspaces.
* Active same-tenant Class and optional Teacher Profile validation at create,
  update, activation, and restoration boundaries.
* Platform-wide Super Admin read-only visibility, own-tenant School Admin
  management, and Teacher read-only directories limited to active assignments.
* Retained Section-name and Subject-code uniqueness, optional assignment
  clearing, inactive restoration, separate activation, and no hard deletion.
* Section and Subject transaction-coupled activity/audit evidence and allowed,
  denied, isolation, exact-permission, relationship, lifecycle, rollback,
  navigation, and no-delete coverage.
* Retained Section and Subject relationships continue displaying an archived
  Teacher Profile's historical identity while lifecycle reentry still requires
  an active, nonarchived Teacher Profile.

## Verification

* Full application suite: 227 tests and 1,619 assertions passed.
* Focused Academic Structure schema suite: 7 tests and 159 assertions passed.
* Focused Academic Year and Term management suite: 17 tests and 175 assertions
  passed.
* Focused Teacher Profile management suite: 17 tests and 149 assertions passed.
* Focused Class management suite: 15 tests and 136 assertions passed.
* Focused Section and Subject management suite: 17 tests and 170 assertions
  passed.
* Phase 5 tenant-isolation review: 10/10, approved with no defects or missing
  enforcement points for implemented modules.
* Phase 5 security review: 10/10, approved after root-specifically ignoring the
  local dummy-login credential file.
* Phase 5 documentation review rerun: 10/10, approved after synchronizing
  implementation navigation, review status, MCA evidence, and next-step records.
* Phase 5 code review rerun: approved with no remaining findings after retaining
  archived Teacher identity on historical Section and Subject records and adding
  lifecycle regression coverage.
* Phase 5 release review: READY at 9.8/10 with no blocking issues. The approved
  checkpoint was committed and pushed as `9320be0`.
* Security-focused suite: 138 tests and 1,057 assertions passed.
* PHP and JavaScript dependency audits reported no advisories.
* Isolated in-memory migration and canonical seed completed successfully.
* Academic routes and Blade compilation validated successfully.
* Laravel Pint, frontend production build, Composer validation, and whitespace
  checks passed.

## Planned

* Academic Structure exports in the reporting phase

## Deliverables

* Academic Structure Module

---

# [0.8.0] - Student Management

Status: Completed — Release Approved

## Design Remediation

* DECISION-031 defines Phase 6 Student Management and immutable Enrollment
  boundaries before implementation.
* Student Enrollment owns the only canonical roll number; the duplicate
  `students.roll_no` column was removed from the database design and ERD.
* One immutable Enrollment is retained for each Student and Academic Year;
  internal reassignment, promotion, and mid-year transfer are deferred.
* Student Reports and exports are deferred to Phase 10 Reporting.
* School Admin, Teacher, Super Admin, and Accountant Student access paths are
  reconciled with privacy-minimized views and Phase availability.
* Private Student-photo access and privacy-safe activity/audit rules are
  documented.

## Implemented

* Reversible `students` and `student_enrollments` migration with every approved
  named index, unique constraint, restricted foreign key, and retention rule.
* Tenant-aware Student and StudentEnrollment models with canonical constants,
  date casts, and relationships to School and Academic Structure entities.
* Immutable Student admission number, immutable Enrollment placement, retained
  Enrollment deletion protection, and tenant ownership safeguards.
* School, Academic Year, Class, and Section inverse Enrollment relationships.
* Tenant-aware Student registration, full School Admin profile management,
  lifecycle transitions, retained archival/restoration, search, filters, and
  read-only Enrollment history.
* Assignment-scoped Teacher reads and school-selected Super Admin reads use
  privacy-minimized projections; Accountant direct access remains unavailable.
* Private Student photos use generated names on private local storage and are
  delivered only through an authorized School Admin endpoint. Replacement,
  removal, archival retention, and rollback cleanup are implemented.
* Student Policy, context-aware Service, Form Requests, thin controllers,
  protected routes, Bootstrap views, and permission-aware navigation.
* School Admin-only initial Enrollment creation against the active current
  Academic Year and active same-tenant Class/Section parents.
* Immutable placement, retained completion, and no Enrollment edit or delete
  routes.
* Transaction-coupled Student transfer and graduation with locked Student and
  Enrollment rows, exact-one-active safeguards, and privacy-safe logs.
* Enrollment Form Requests, dedicated Policy and Service boundaries,
  confirmation-based Bootstrap controls, and denied-role/tenant enforcement.

## Verification

* Phase 6 readiness rerun: READY at 10/10 with no blocking issues.
* Focused Student schema suite: 6 tests and 92 assertions passed.
* Focused Student Profile Management suite: 11 tests and 165 assertions passed.
* Combined Phase 6 Student suite: 17 tests and 257 assertions passed.
* Full application suite: 244 tests and 1,876 assertions passed.
* Focused Enrollment Management suite: 13 tests and 123 assertions passed.
* Combined Phase 6 Student suite after Enrollment implementation: 30 tests and
  380 assertions passed.
* Full application suite after Enrollment implementation: 257 tests and 1,999
  assertions passed.
* Pint, production frontend build, Composer validation, route checks, and
  migration execution passed.
* Documentation consistency and whitespace validation passed.

## Review Status

* Phase 6 tenant-isolation review: 10/10 and approved after expanding denied
  HTTP and direct-service mutation coverage.
* Phase 6 security review: 10/10 and approved with no dependency advisories or
  unresolved findings.
* Phase 6 documentation review: 10/10 and approved after reconciling lifecycle
  flows, permission mapping, and stale next-task references.
* Phase 6 code review: approved after replacing a same-tenant relationship 404
  with field validation, eliminating policy N+1 queries, and preserving reduced
  Teacher/Super Admin projections.
* Phase 6 release review: READY at 9.8/10 with no blocking issues.

## Deliverables

* Student Management Module

---

# [0.9.0] - Attendance Management

Status: Completed — Release Approved

## Design Remediation

* Reconciled Phase 7 daily operations with Phase 10 ownership of reports,
  exports, analytics, dashboards, and platform summaries.
* Defined School Admin tenant access and direct assigned-Section Teacher access;
  Subject-only assignment grants no Attendance authority.
* Defined the Enrollment-derived active roster, school-local date validation,
  immutable placement, and Student/Enrollment lifecycle boundaries.
* Defined complete atomic bulk saves, repeated create/correction behavior,
  concurrency handling, and whole-batch rollback.
* Standardized retained Attendance with no delete route. Catalog delete, report,
  and export permissions remain dormant until an approved phase uses them.
* Made the original `marked_by` required and immutable; corrections identify the
  actor through privacy-safe audit evidence.
* Defined School Admin-only roster holiday handling, manual Late selection,
  optional 500-character privacy-limited remarks, and school timezone behavior.
* Expanded Phase 7 schema, tenancy, security, UI, screen-flow, and testing
  requirements and recorded DECISION-032.

## Implemented

* Reversible `attendances` migration with every approved column, named index,
  daily Student unique constraint, and restricted foreign key.
* Tenant-aware Attendance model with canonical statuses, date casting,
  automatic ownership, immutable placement and original marker, and retained-
  history deletion protection.
* Retained inverse relationships across School, Student, Academic Year, Class,
  Section, and original marking User.
* Focused schema and isolation suite: 6 tests and 67 assertions passed.
* Full application suite after the foundation: 263 tests and 2,066 assertions
  passed.

## Operational Workflow Implemented

* School Admin complete-roster entry and correction across active own-tenant
  Sections, including a whole-roster Holiday action.
* Active directly assigned Section Teacher entry, history, and current-year
  correction with Subject-only and stale-assignment denial.
* Server-derived current Academic Year, active Section/Class, active Enrollment,
  Student lifecycle, and enrollment-date roster boundaries.
* School-local date validation, exact-roster validation, atomic create/correct
  saves, idempotent repeated saves, and unique-race protection.
* Retained operational history, Student search, Section/date/status filters, and
  monthly on-screen status totals without report or export routes.
* Privacy-safe activity summaries and per-row audit evidence that preserve the
  original marker and never copy raw remarks or unrelated minor data.
* Policy, Form Request, Service, thin controller, protected routes, Bootstrap 5
  views, role-aware navigation, and Mark All Present support.

## Workflow Verification

* Focused Attendance workflow suite: 13 tests and 150 assertions passed.
* Combined Attendance schema/workflow suite: 19 tests and 217 assertions passed.
* Full application suite: 276 tests and 2,216 assertions passed.
* Cross-school roster POST, route-bound edit/PATCH, unfiltered history, and
  monthly-summary denied paths now have explicit regression coverage.
* Pint, production frontend build, route inspection, Composer validation, and
  whitespace checks passed.

## Security Remediation

* Restricted transitions both to and from Holiday to the School Admin atomic
  complete-roster workflow.
* Denied Teacher correction and roster overwrite of existing Holiday records,
  and denied single-record Holiday transitions for every role.
* Preserved ordinary Teacher correction rights for assigned non-Holiday
  Attendance and retained privacy-safe transactional evidence.
* Focused security/Attendance suite: 25 tests and 243 assertions passed.
* Composer and production npm audits reported no dependency advisories.
* Phase 7 security-review rerun: 10/10, approved with no critical, high, or
  medium finding.

## Documentation Remediation

* Reconciled the current navigation summary with the implemented School Admin
  and directly assigned-Section Teacher Attendance workspace.
* Replaced broad assigned-Class Attendance wording with the canonical direct
  active Section assignment boundary.
* Converted premature diagram and final-submission completion marks into
  evidence-based completed and pending checkboxes.
* Corrected the implemented `AttendanceStoreRequest` name in the validation
  guidance.
* Added the MCA submission master plan to the reusable prompt template, prompt
  README, and every governance review prompt as required by DECISION-033.
* Added the scoped Phase 7 documentation-remediation prompt and synchronized
  the prompt inventory.
* Phase 7 documentation-review rerun: 10/10, approved with no remaining
  contradiction, stale reference, or missing update.

## Code Review Remediation

* Merged lifecycle-changed retained same-date Attendance rows into complete
  correction rosters while keeping new ineligible records excluded.
* Revalidated direct correction status and remark payloads, including the
  500-character limit, before persistence.
* Reused eager-loaded actor, permission, Section, Class, and Academic Year
  relationships so per-row History authorization no longer causes N+1 queries.
* Added regression coverage for retained Holiday transitions, direct-service
  invalid remarks, bounded History query counts, and distinct create, update,
  and view permission behavior.
* Focused Attendance suite: 16 tests and 179 assertions passed.
* Combined Attendance schema/workflow suite: 22 tests and 246 assertions passed.
* Focused security/Attendance suite: 28 tests and 272 assertions passed.
* Full application suite: 279 tests and 2,245 assertions passed.
* Phase 7 code-review rerun: approved with no remaining findings.

## Test Database Safety

* PHPUnit now declares `APP_ENV=testing` and SQLite `:memory:` as forced test
  configuration.
* The shared test bootstrap aborts before `RefreshDatabase` can migrate or
  truncate data unless Laravel resolves the approved isolated test database.
* Local development data remains tenant-owned and is not recreated by automated
  tests.

## Release Review

* Phase 7 release review: READY WITH MINOR IMPROVEMENTS at 9.5/10, final
  verdict YES, with no blocking issues.
* Verified 279 tests with 2,245 assertions, Pint, Attendance route inspection,
  Composer validation, Composer audit, production frontend build, production
  npm audit, and whitespace checks.
* Remaining improvements are non-blocking: commit/push the checkpoint and
  capture sanitized Attendance screenshots and MCA evidence while the module
  context is fresh.

## Deliverables

* Attendance Module

---

# [0.9.1] - MCA Submission Governance

Date: 2026-06-22

Status: Completed

## Added

* Canonical `MCA_SUBMISSION_MASTER_PLAN.md` based on the reviewed Major Project
  assignment, detailed writing guidelines, official report template, Qollabb
  portal requirements, and the 2026-07-05 deadline.
* Verified report, presentation, live-link, optional-video, milestone,
  evaluation, demonstration, certificate, and submission requirements.
* Chapter/page/word targets, formatting conflict resolution, diagram and
  screenshot inventory, deployment contract, presentation plan, viva plan,
  critical-path calendar, feature freeze, and final submission checklist.

## Governance

* Root and documentation AGENT instructions now require every remaining phase
  to consult the submission plan.
* The Constitution, Governance, Roadmap, Project Overview, and report notes now
  share the same deadline and parallel-delivery obligations.

---

# [1.0.0] - Fee Management

Status: Completed — Release Approved

## Planned

* Fee categories
* Fee structures
* Student Fee assignment
* Fee collection
* Receipts
* Payment history
* Outstanding balance views
* Local sandbox transactions

Fee reports, exports, analytics, dashboard widgets, and platform financial
summaries are deferred to Phase 10 Reporting.

## Design Remediation

* Added the Phase 8 Fee Management readiness review prompt.
* Initial readiness review found design and RBAC gaps around Fee reports,
  Accountant setup permissions, Super Admin visibility, Student Fee assignment,
  payment idempotency, sandbox payloads, financial retention, and required
  tests.
* Recorded DECISION-034 to define operational setup, assignment, collection,
  receipt, payment-history, outstanding-balance, sandbox, role, and Phase 10
  deferral boundaries.
* Tightened Accountant RBAC defaults to Fee view/collect only; setup, delete,
  report, and export permissions are out of bounds.
* Phase 8 readiness rerun: approved with no blocking issues after design and
  RBAC remediation.

## Core Schema Implemented

* Reversible migration creates `fee_categories`, `fee_structures`,
  `student_fees`, `fee_payments`, and `payment_transactions` with documented
  columns, named indexes, unique constraints, soft-delete boundaries, and
  restricted foreign keys.
* Added tenant-aware `FeeCategory`, `FeeStructure`, `StudentFee`, `FeePayment`,
  and `PaymentTransaction` models with canonical statuses, payment modes,
  money/date casts, retained-history deletion guards, and immutable financial
  identity safeguards.
* Added inverse relationships on School, Academic Year, Class, Student, and
  User.
* Focused Fee schema suite: 6 tests and 210 assertions passed.
* Combined Fee/RBAC/Attendance schema suite: 27 tests and 427 assertions passed.
* Full application suite: 286 tests and 2,480 assertions passed.

## Setup And Assignment Implemented

* Added School Admin Fee Category list/create/update/show workflows with
  activate/deactivate lifecycle routes and tenant-safe visibility.
* Added School Admin Fee Structure list/create/update/show workflows with
  activate/deactivate lifecycle routes, current Academic Year and active Class
  validation, and tenant-safe visibility.
* Added School Admin Student Fee assignment list/create/show workflows with
  eligible-enrollment validation, duplicate rejection, server-derived amount,
  payable, paid, and balance values, and privacy-minimized Student identifiers.
* Added policies, Form Requests, services, controllers, routes, Bootstrap
  views, sidebar navigation, activity logs, and audit logs for setup and
  assignment only.
* Denied Accountant, Teacher, Super Admin, guest, inactive, cross-tenant, and
  direct-service wrong-context paths on setup and assignment routes.
* Focused setup/assignment suite: 7 tests and 59 assertions passed.
* Full application suite: 293 tests and 2,539 assertions passed.

## Collection And Receipts Implemented

* Added School Admin and Accountant collectible Student Fee lookup and collection
  workflow with tenant-safe visibility.
* Added atomic collection that updates Student Fee balances, creates retained Fee
  Payment receipts, creates Payment Transactions, and writes activity/audit
  evidence in one transaction.
* Added server-generated receipt and transaction numbers, collection-token replay
  protection, school-local payment-date validation, and privacy-safe audit values.
* Added receipt detail and print-friendly views.
* Denied Teacher, Super Admin, guest, cross-tenant, forged-field, and wrong
  tenant-context collection paths.
* Focused collection/receipt suite: 8 tests and 50 assertions passed.
* Full application suite: 301 tests and 2,589 assertions passed.

## Payment History And Outstanding Balances Implemented

* Added School Admin and Accountant payment-history list with search, payment-mode,
  and date-range filters linked to existing receipt detail views.
* Added School Admin and Accountant outstanding-balance list with on-screen summary
  totals, search, and balance-state filters.
* Added `FeePaymentHistoryService`, `FeeOutstandingBalanceService`, Form Requests,
  controllers, routes, Bootstrap views, and fee navigation updates.
* Denied Teacher, Super Admin, guest, cross-tenant, prohibited-field, and wrong
  tenant-context read paths.
* Focused payment-history/outstanding suite: 8 tests and 42 assertions passed.
* Full application suite: 309 tests and 2,631 assertions passed.

## Sandbox Transaction Screens Implemented

* Added School Admin and Accountant sandbox Payment Transaction list and detail screens
  for `sandbox_gateway` records only.
* Added sanitized payload display, receipt links, and privacy-minimized Student
  identifiers without export or mutation controls.
* Added `SandboxTransactionService`, `PaymentTransactionPolicy`, Form Request,
  controller, routes, Bootstrap views, and fee navigation updates.
* Denied Teacher, Super Admin, guest, cross-tenant, non-sandbox detail, and wrong
  tenant-context read paths.
* Focused sandbox screen suite: 7 tests and 24 assertions passed.
* Full application suite: 316 tests and 2,655 assertions passed.

## Release Gate Approved

* Ran Phase 8 tenant isolation, security, documentation, code, and release reviews.
* Remediated collection-token replay by consuming session tokens on first valid use.
* Added controller-level authorization on receipt and sandbox detail routes.
* Expanded release-gate tests for cross-tenant collection POST denial, token replay
  rejection, paid-fee collection denial, and sandbox show service context checks.
* Focused Fee workflow suites: 39 tests and 389 assertions passed.
* Full application suite: 319 tests and 2,662 assertions passed.

## Phase 9 Design Remediation

* Added the Phase 9 Examination Management readiness review prompt.
* Initial readiness review found design and RBAC gaps around Examination reports,
  Teacher setup permissions, Super Admin visibility, marks-entry assignment
  scope, grade-scale rules, result retention, report-card export deferral, and
  required tests.
* Recorded DECISION-035 to define operational setup, assignment, marks entry,
  grade-scale, result-processing, report-card, role, and Phase 10 deferral
  boundaries.
* Tightened Teacher RBAC defaults to `exams.view`, `exams.create`, and
  `exams.update` only; setup, delete, publish, report, and export permissions
  are out of bounds.
* Phase 9 readiness rerun: approved with no blocking issues after design and
  RBAC remediation.

## Core Schema Implemented

* Reversible migration creates `grade_scales`, `exams`, `exam_subjects`,
  `exam_results`, and `report_cards` with documented columns, named indexes,
  unique constraints, Exam soft-delete boundaries, and restricted foreign keys.
* Added tenant-aware `GradeScale`, `Exam`, `ExamSubject`, `ExamResult`, and
  `ReportCard` models with canonical statuses, decimal/date/datetime casts,
  retained-history deletion guards, and immutable scope/identity safeguards.
* Added inverse relationships on School, Academic Year, Academic Term, Class,
  Section, Subject, Student, and User.
* Focused Examination schema suite: 6 tests and 236 assertions passed.

## Deliverables

* Fee Management Module

---

# [1.1.0] - Examination Management

Status: Core Schema Implemented — Pending Workflow Implementation

## Design Remediation

* DECISION-035 defines School Admin Examination setup and assignment authority,
  Teacher marks-entry-only boundaries, no Phase 9 Super Admin or Accountant
  route, Phase 10 report/export/analytics deferral, retained result history,
  school-local grade scales, and privacy-safe audit expectations.

## Planned

* Examination setup and Exam Subject assignment
* Grade scale seeding and management
* Teacher-scoped marks entry
* Grade calculation and result processing
* Operational report-card view and print

## Deliverables

* Examination Module

---

# [1.2.0] - Reports & Analytics

Status: Planned

## Planned

* Student reports
* Attendance reports
* Fee reports
* Examination reports
* Dashboard analytics
* Charts and visualizations

## Deliverables

* Reporting System
* Analytics Dashboard

---

# [1.3.0] - Testing & Quality Assurance

Status: Planned

## Planned

* Unit testing
* Feature testing
* Integration testing
* Tenant isolation testing
* Security testing

## Deliverables

* Test Reports
* QA Documentation

---

# [1.4.0] - Deployment & Documentation

Status: Planned

## Planned

* Production deployment
* Deployment guide
* User manual
* Installation guide
* Final screenshots

## Deliverables

* Deployment Documentation
* User Documentation

---

# [2.0.0] - MCA Final Submission

Status: Planned

## Deliverables

* Complete source code
* Database schema
* MCA project report
* Presentation slides
* Screenshots package
* User manual
* Viva preparation notes

## Outcome

Project ready for MCA final evaluation and submission.

---

# Versioning Strategy

Major Version

* Significant project milestone completion

Example:

2.0.0

Minor Version

* Module completion

Example:

1.1.0

Patch Version

* Bug fixes and minor improvements

Example:

1.1.1

---

# Current Project Status

Phase: Phase 7 Attendance Completed — Release Approved; Phase 8 Fee Management
Setup And Assignment Implemented — Collection And Receipts Implemented — Payment History And Outstanding Balances Implemented — Sandbox Transaction Screens Implemented — Release Approved

Repository Setup: Completed

Laravel Installation: Completed

Documentation Setup: Completed (Draft v1.0; maintained during implementation)

Database Design Documentation: Remediated and implementation-ready draft (DATABASE_DESIGN.md, ER_DIAGRAM.md)

Application Implementation: Phase 2, Phase 3, Phase 4, and Phase 5 are
completed. Phase 6 Student Management is release-approved. Phase 7 Attendance is
release-approved and includes
the Attendance schema/model foundation, daily complete-roster entry, authorized
correction, retained history, and monthly operational summary workflows. Phase 8
Fee Management readiness and design-boundary remediation are approved. The core
Fee schema and tenant-aware model foundation are implemented. School Admin Fee
Category, Fee Structure, and Student Fee assignment workflows are implemented.
School Admin and Accountant collection, receipt, payment-history, outstanding-balance,
and sandbox transaction operational workflows are implemented and release-approved.
Phase 9 Examination Management design remediation is approved. The core
Examination schema and tenant-aware model foundation are implemented. School
Admin exam setup and Exam Subject assignment are the next checkpoint.

Next Task: Implement Phase 9 School Admin exam setup and Exam Subject assignment workflows.
