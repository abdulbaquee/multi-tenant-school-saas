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

Status: Core Foundation Completed — Mapping Management Pending

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

## Verification

* Full application suite: 140 tests and 705 assertions passed.
* Local database synchronization confirmed four roles, 57 permissions, and the
  exact 31-permission Super Admin mapping.
* Laravel Pint and Composer configuration validation passed.

## Planned

* Role directory and effective-permission screens
* Constrained school-role mapping management
* Mapping activity and audit recording

## Deliverables

* RBAC Module

---

# [0.7.0] - Academic Management

Status: Planned

## Planned

* Classes
* Sections
* Subjects
* Academic sessions
* Student enrollment

## Deliverables

* Academic Structure Module

---

# [0.8.0] - Student Management

Status: Planned

## Planned

* Student registration
* Student profiles
* Student search
* Student reports

## Deliverables

* Student Management Module

---

# [0.9.0] - Attendance Management

Status: Planned

## Planned

* Daily attendance
* Attendance reports
* Attendance analytics

## Deliverables

* Attendance Module

---

# [1.0.0] - Fee Management

Status: Planned

## Planned

* Fee categories
* Fee structures
* Fee collection
* Receipts
* Financial reports

## Deliverables

* Fee Management Module

---

# [1.1.0] - Examination Management

Status: Planned

## Planned

* Examinations
* Marks entry
* Grade calculations
* Report cards
* Academic reports

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

Phase: Implementation Phase (Phase 4 — Core RBAC Foundation Completed)

Repository Setup: Completed

Laravel Installation: Completed

Documentation Setup: Completed (Draft v1.0; maintained during implementation)

Database Design Documentation: Remediated and implementation-ready draft (DATABASE_DESIGN.md, ER_DIAGRAM.md)

Application Implementation: Phase 2 completed; Phase 3 tenant infrastructure,
School Management, School Settings, security logging, tenant-isolation review,
security review, documentation review, code review, and release review completed

Next Task: Implement Phase 4 Role & Permission mapping management
