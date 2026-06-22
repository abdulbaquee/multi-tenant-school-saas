# Phase 7 Core Attendance Schema

## Role

Act as a senior Laravel architect, database architect, multi-tenant SaaS
architect, security architect, testing specialist, and MCA project reviewer for
this repository.

## Objective

Implement the approved Phase 7 Attendance schema and tenant-aware model
foundation without introducing user-facing Attendance workflows.

## Execution Mode

Implementation. Create only the approved migration, model, relationships,
integrity safeguards, focused tests, and synchronized implementation evidence.

## Authoritative Documents

Read and follow:

- `AGENTS.md`
- `app/AGENTS.md`
- `database/AGENTS.md`
- `tests/AGENTS.md`
- `docs/AGENTS.md`
- `docs/PROJECT_CONSTITUTION.md`
- `docs/PROJECT_GOVERNANCE.md`
- `docs/DECISIONS_LOG.md`
- `docs/DEVELOPMENT_ROADMAP.md`
- `docs/SYSTEM_ARCHITECTURE.md`
- `docs/TENANCY_DESIGN.md`
- `docs/DATABASE_DESIGN.md`
- `docs/ER_DIAGRAM.md`
- `docs/MODULE_SPECIFICATIONS.md`
- `docs/SECURITY_GUIDELINES.md`
- `docs/TESTING_STRATEGY.md`
- `docs/CODING_STANDARDS.md`
- `prompts/06-attendance/01-phase-07-attendance-design-remediation.md`

## Prerequisites

Verify before implementation:

- Phase 6 is committed, pushed, and release-approved.
- Phase 7 design remediation is committed.
- The Phase 7 readiness rerun is READY at 10/10.
- Existing tests pass before schema changes.
- No Attendance implementation already exists.

## Scope

Implement:

- One reversible migration creating `attendances` exactly as documented in
  `DATABASE_DESIGN.md`.
- Every documented column, type, default, named index, unique constraint, and
  `restrictOnDelete()` foreign key.
- No soft deletes on `attendances`.
- A canonical `Attendance` model using `BelongsToTenant`.
- Fillable workflow fields excluding tenant ownership, canonical status
  constants, the attendance-date cast, and documented Eloquent relationships.
- Inverse Attendance relationships on School, Student, AcademicYear,
  SchoolClass, Section, and User.
- Model-level integrity safeguards that keep Student, Academic Year, Class,
  Section, attendance date, original marker, and tenant ownership immutable
  after creation.
- A model-level deletion guard preserving Attendance history.
- Focused schema, index, relationship, casting, retained-history,
  automatic-scope, ownership, unresolved-context, Platform-read, immutable-
  ownership, unique-constraint, and foreign-key tests.
- Documentation implementation-status and test-evidence updates after
  verification.

## Tenant And Integrity Requirements

- `school_id` is never fillable and is always derived from TenantContext.
- Tenant-context creation overwrites a forged `school_id` with the context
  school.
- Unresolved and Platform context cannot create Attendance.
- Unresolved context reads no Attendance records.
- Tenant context reads only its school records.
- Explicit Platform context may read all records at the model foundation layer;
  future HTTP access still requires Policies and services.
- Changing `school_id` after creation throws the existing controlled tenant-
  ownership exception.
- All foreign keys use `restrictOnDelete()` and never cascade.
- Foreign keys do not replace future same-tenant, lifecycle, Enrollment, or
  authorization validation in the service layer.

## Attendance Integrity Contract

- One retained daily row exists per School, Student, and attendance date through
  `uq_attendances_student_date`.
- Allowed status constants are `present`, `absent`, `leave`, `late`, and
  `holiday`.
- The database default remains `present`, but future Form Requests and services
  must require an explicit status.
- `student_id`, `academic_year_id`, `class_id`, `section_id`,
  `attendance_date`, and `marked_by` are immutable after creation.
- `marked_by` is the original authenticated creator and is never replaced by a
  correction actor.
- Only `status` and `remarks` may be corrected at the model foundation layer.
- Attendance rows remain retained history and cannot be deleted.
- Retained relationships to soft-deleted Student, Class, Section, and User
  records must continue to resolve.
- This prompt does not implement complete-roster validation, Teacher assignment
  scope, current-year checks, date boundaries, correction authorization,
  locking, audit logging, or activity logging. Those belong to the dedicated
  Attendance workflow prompt.

## Required Tests

Prove:

- The table and every documented column exist after migration.
- Named indexes and `uq_attendances_student_date` match the data dictionary.
- Missing foreign-key parents and hard deletion of referenced parents fail.
- Attendance does not use soft deletes and rejects deletion.
- The model derives tenant ownership and filters automatically across School A,
  School B, Platform, and Unresolved context.
- Forged `school_id` cannot override TenantContext.
- Tenant ownership and documented Attendance identity fields are immutable.
- Status and remarks remain correctable while the original marker remains
  unchanged.
- Canonical relationships and the attendance-date cast resolve inside matching
  tenant context, including soft-deleted retained parents.
- Duplicate School, Student, and attendance-date rows are rejected.
- Existing Phase 2-6 tests remain green.

## Out Of Scope

Do not implement:

- Attendance services, Policies, Form Requests, controllers, routes, menus,
  Blade views, daily-entry pages, bulk roster saves, history pages, search, or
  monthly summaries.
- Teacher assignment authorization, School Admin authorization, holiday
  workflows, current-year rules, Enrollment validation, date validation, or
  transactional locking.
- Attendance reports, exports, analytics, dashboards, or deletion workflows.
- Activity or audit mutation workflows beyond preserving their documented
  contract for later implementation.
- Fees, Examinations, Reporting, Analytics, or system operations.
- Attendance demo seed data or real student data.
- Packages, triggers, tenancy libraries, or schema changes outside the approved
  Attendance table and inverse relationships.

## Required Workflow

1. Verify prerequisites and inspect migration, model, relationship, and test
   conventions.
2. Create the migration directly from `DATABASE_DESIGN.md`.
3. Create Attendance with `BelongsToTenant`, immutable identity safeguards, and
   retained-history deletion protection.
4. Add inverse relationships without adding workflow logic.
5. Add focused schema and automatic-isolation tests.
6. Run targeted tests, the full suite, migration verification, Pint, Composer
   validation, and `git diff --check`.
7. Update implementation status, testing evidence, changelog, and prompt index.

## Acceptance Criteria

- The schema and model match the approved data dictionary exactly.
- Attendance defaults to deny without a resolved tenant context.
- No soft-delete column, foreign-key cascade, undocumented column, or duplicate
  daily Student row exists.
- Placement, date, tenant ownership, Student identity, and original marker
  cannot drift after creation.
- Attendance history cannot be deleted through any workflow introduced here.
- No user-facing Attendance workflow is introduced.
- The complete regression suite passes.
- No package or out-of-scope module is changed.

## Stop Conditions

Stop and report instead of guessing if:

- The database design conflicts with DECISION-032.
- A second table or schema field appears necessary.
- Tenant isolation would require weakening `BelongsToTenant` or TenantScope.
- Existing tests fail before implementation.
- A package, trigger, or cross-tenant mechanism appears necessary.

## Required Final Response

Provide:

- Files modified.
- Table and model implemented.
- Tenant and integrity protections.
- Tests and validation run.
- Documentation updates.
- Remaining Phase 7 work.
- Exact next task.
