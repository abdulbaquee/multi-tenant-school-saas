# Phase 6 Core Student Schema

## Role

Act as a senior Laravel architect, database architect, multi-tenant SaaS
architect, security architect, privacy reviewer, testing specialist, and MCA
project reviewer for this repository.

## Objective

Implement the approved Phase 6 Student and Student Enrollment schema and
tenant-aware model foundation without introducing user-facing Student
Management workflows.

## Execution Mode

Implementation. Create only the approved migration, models, relationships,
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
- `prompts/05-student-management/01-phase-06-student-design-remediation.md`

## Prerequisites

Verify before implementation:

- Phase 5 is committed, pushed, and release-approved.
- Phase 6 design remediation is committed.
- The Phase 6 readiness rerun is READY at 10/10.
- Existing tests pass before schema changes.
- No Student Management implementation already exists.

## Scope

Implement:

- One reversible migration creating, in dependency-safe order:
  - `students`
  - `student_enrollments`
- Every documented column, type, default, named index, unique constraint, and
  `restrictOnDelete()` foreign key from `DATABASE_DESIGN.md`.
- Soft deletes on `students` only. `student_enrollments` remains retained
  history without soft deletes.
- Canonical models:
  - `Student`
  - `StudentEnrollment`
- `BelongsToTenant` on both models.
- Fillable fields excluding tenant ownership, lifecycle/gender constants,
  documented casts, and Eloquent relationships.
- Inverse relationships on School, AcademicYear, SchoolClass, and Section.
- Model-level integrity safeguards that keep `students.admission_no` immutable
  and keep Enrollment Student, Academic Year, Class, Section, and roll number
  immutable after creation.
- Focused schema, index, relationship, casting, soft-delete, retained-history,
  automatic-scope, ownership, unresolved-context, Platform-read, immutable-
  ownership, unique-constraint, and foreign-key tests.
- Documentation implementation-status and test-evidence updates after
  verification.

## Tenant And Integrity Requirements

- `school_id` is never fillable on either model.
- Tenant-context creation overwrites a forged `school_id` with the context
  school.
- Unresolved and Platform context cannot create either strict tenant-owned
  model.
- Unresolved context reads no Student or Enrollment records.
- Tenant context reads only its school records.
- Explicit Platform context may read all records at the model foundation layer;
  future HTTP access still requires Policies and services.
- Changing `school_id` after creation throws the existing controlled tenant-
  ownership exception.
- Foreign keys use `restrictOnDelete()` and never cascade.
- The database foreign keys do not replace future same-tenant or active-parent
  service validation.

## Student Integrity Contract

- `admission_no` is immutable after creation.
- Accepted genders are `male`, `female`, `other`, and
  `prefer_not_to_say`.
- Student statuses are `active`, `inactive`, `transferred`, and `graduated`.
- Date, status-transition, archive, restoration, and private-photo workflows are
  implemented in later prompts, not in this foundation.
- `photo_path` remains a private storage path field; this prompt does not create
  upload or delivery behavior.

## Enrollment Integrity Contract

- `student_enrollments.roll_no` is the only roll-number source of truth.
- One Enrollment exists per Student and Academic Year through the documented
  unique constraint.
- Student, Academic Year, Class, Section, and roll number are immutable after
  creation.
- Enrollment status constants are `active`, `transferred`, and `completed`.
- This prompt does not implement enrollment services, same-tenant parent
  validation, lifecycle transitions, reassignment, promotion, or transfer UI.
- Enrollment rows remain retained history and no delete workflow is introduced.

## Required Tests

Prove:

- Both tables and every documented column exist after migration.
- Named indexes and unique constraints match the data dictionary.
- Missing foreign-key parents and hard deletion of referenced parents fail.
- Student uses SoftDeletes and StudentEnrollment does not.
- Both models derive tenant ownership and filter automatically across School A,
  School B, Platform, and Unresolved context.
- Forged `school_id` cannot override TenantContext.
- Tenant ownership is immutable after creation.
- Student admission number and Enrollment placement fields are immutable.
- Canonical relationships and date casts resolve inside matching tenant context.
- Duplicate school admission numbers, duplicate Student/year Enrollments, and
  duplicate roll numbers in one year/Class/Section are rejected.
- Existing Phase 2-5 tests remain green.

## Out Of Scope

Do not implement:

- Student services, Policies, Form Requests, controllers, routes, menus, Blade
  views, profile pages, search, private-photo upload/delivery, or lifecycle UI.
- Enrollment creation workflows, status transitions, transfer, promotion,
  reassignment, reports, or exports.
- Activity or audit mutation workflows beyond documenting their later contract.
- Attendance, Fees, Examinations, Reporting, Analytics, or system operations.
- Student demo seed data or real minor data.
- Packages, triggers, tenancy libraries, or schema changes outside the approved
  two tables and inverse relationships.

## Required Workflow

1. Verify prerequisites and inspect migration, model, relationship, and test
   conventions.
2. Create the migration directly from `DATABASE_DESIGN.md`.
3. Create Student and StudentEnrollment with `BelongsToTenant`.
4. Add inverse relationships without adding workflow logic.
5. Add focused schema and automatic-isolation tests.
6. Run targeted tests, full tests, migration verification, Pint, Composer
   validation, and `git diff --check`.
7. Update implementation status, testing evidence, changelog, and prompt index.

## Acceptance Criteria

- Schema and model names match the approved data dictionary exactly.
- Both tenant-owned models default deny without resolved context.
- No foreign-key cascades, duplicate Student roll number, or undocumented
  column exists.
- Admission and Enrollment placement identity cannot drift after creation.
- Enrollment history cannot be deleted through any workflow introduced here.
- No user-facing Student Management workflow is introduced.
- Complete regression suite passes.
- No package or out-of-scope module is changed.

## Stop Conditions

Stop and report instead of guessing if:

- The database design conflicts with DECISION-031.
- A third table or schema field appears necessary.
- Tenant isolation would require weakening `BelongsToTenant` or TenantScope.
- Existing tests fail before implementation.
- A package, trigger, or cross-tenant transfer mechanism appears necessary.

## Required Final Response

Provide:

- Files modified.
- Tables and models implemented.
- Tenant and integrity protections.
- Tests and validation run.
- Documentation updates.
- Remaining Phase 6 work.
- Exact next task.
