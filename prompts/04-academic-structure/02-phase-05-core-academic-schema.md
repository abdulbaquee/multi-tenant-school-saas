# Phase 5 Core Academic Schema

## Role

Act as a senior Laravel architect, database architect, multi-tenant SaaS
architect, security architect, testing specialist, and MCA project reviewer for
this repository.

## Objective

Implement the Phase 5 Academic Structure schema and tenant-aware model
foundation exactly as approved, without introducing user-facing Academic
Structure workflows.

## Execution Mode

Implementation. Create the approved migrations, models, relationships,
integrity safeguard, tests, and synchronized implementation documentation only.

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
- `prompts/04-academic-structure/01-phase-05-academic-design-remediation.md`

## Prerequisites

Verify before implementation:

- Phase 4 is committed, pushed, and release-approved.
- The Phase 5 readiness rerun is READY.
- The approved scope contains Academic Years, Academic Terms, minimal Teacher
  Profiles, Classes, Sections, and Subjects only.
- Student Enrollment remains Phase 6.
- Existing tests pass before schema changes.

## Scope

Implement:

- One reversible migration creating, in dependency-safe order:
  - `academic_years`
  - `academic_terms`
  - `classes`
  - `teachers`
  - `sections`
  - `subjects`
- Every documented column, type, default, named index, unique constraint, and
  `restrictOnDelete()` foreign key from `DATABASE_DESIGN.md`.
- Soft deletes only for `classes`, `teachers`, `sections`, and `subjects`.
- Canonical models:
  - `AcademicYear`
  - `AcademicTerm`
  - `SchoolClass` mapped to `classes`
  - `Teacher` presented later as Teacher Profile
  - `Section`
  - `Subject`
- `BelongsToTenant` on every Phase 5 model.
- Fillable fields that exclude tenant ownership, appropriate casts, lifecycle
  constants, subject-type constants, and documented Eloquent relationships.
- School relationships to all six entities.
- User one-to-one Teacher Profile relationship.
- Existing User Management protection that rejects user role or school changes
  while any retained Teacher Profile, including a soft-deleted profile, exists.
- Focused schema, relationship, casting, soft-delete, automatic-scope,
  tenant-ownership, unresolved-context, Platform-read, immutable-ownership, and
  User Management safeguard tests.
- Documentation status and test-evidence updates after verification.

## Tenant And Integrity Requirements

- `school_id` is never fillable on a Phase 5 model.
- Tenant-context creation sets `school_id` even if a forged value is supplied.
- Unresolved and Platform context cannot create strict tenant-owned records.
- Unresolved context reads no Phase 5 records.
- Tenant context reads only its school records.
- Explicit Platform context may read all records at the model foundation layer;
  future HTTP access still requires Policies and services.
- Changing model `school_id` after creation throws the existing controlled
  immutable-ownership exception.
- Foreign keys never cascade.
- The migration does not attempt to encode cross-tenant parent validation or
  one-current-year workflow in ad hoc database triggers; approved services will
  enforce those workflows in later prompts.

## User Safeguard Contract

- A retained Teacher Profile is any `teachers` row for the target user,
  including soft-deleted rows.
- Updating the user's name, email, phone, password, verification, or status
  remains available through existing authorized workflows.
- Changing `role_id` while a retained Teacher Profile exists fails validation.
- Changing `school_id` while a retained Teacher Profile exists fails validation.
- The guard must work for School Admin Tenant context and Super Admin Platform
  context without bypassing existing authorization or tenant checks.
- No Teacher Profile creation or management UI is implemented in this prompt.

## Required Tests

Prove:

- All six tables and documented columns exist after migration.
- Named indexes and unique constraints match the data dictionary.
- Foreign keys reject missing parents and use retained relationships.
- Academic Year and Academic Term do not use SoftDeletes.
- SchoolClass, Teacher, Section, and Subject use SoftDeletes.
- Every Phase 5 model derives tenant ownership and filters automatically across
  School A, School B, Platform, and Unresolved context.
- Forged `school_id` cannot override TenantContext.
- Tenant ownership is immutable after creation.
- Canonical relationships resolve inside a matching tenant context.
- Date, boolean, integer, and soft-delete casts behave as documented.
- A retained active or soft-deleted Teacher Profile blocks user role and school
  reassignment without partial changes or new logs.
- Existing Phase 2-4 tests remain green.

## Out Of Scope

Do not implement:

- Academic Year activation or overlap workflow services.
- Academic Term lifecycle or date-overlap services.
- Teacher Profile, Class, Section, or Subject management services.
- Academic Policies, Form Requests, controllers, routes, menus, Blade views,
  exports, reports, activity/audit mutation workflows, or demo seed data.
- Student, Student Enrollment, Attendance, Fee, Examination, Reporting, or
  system-operations behavior.
- Packages, triggers, custom tenancy libraries, or schema changes outside the
  approved six tables and required existing-user relationship.

## Required Workflow

1. Verify prerequisites and inspect existing migration/model/test conventions.
2. Create the migration from `DATABASE_DESIGN.md` without inventing fields.
3. Create canonical models and relationships using `BelongsToTenant`.
4. Add School and User inverse relationships.
5. Add the retained Teacher Profile role/school reassignment safeguard.
6. Add focused schema and automatic-isolation tests.
7. Run targeted tests, full tests, migration verification, Pint, Composer
   validation, and `git diff --check`.
8. Update implementation status, testing evidence, changelog, and prompt index.

## Acceptance Criteria

- Schema and model names match the approved data dictionary exactly.
- All tenant-owned models default deny without resolved context.
- No foreign key cascades or undocumented columns exist.
- Teacher-linked users cannot drift from their retained profile's role or school.
- No user-facing Academic Structure workflow is introduced.
- Complete regression suite passes.
- No package or out-of-scope module is changed.

## Stop Conditions

Stop and report instead of guessing if:

- The database design conflicts with DECISION-030.
- A schema change beyond the approved tables appears necessary.
- Tenant isolation would require weakening `BelongsToTenant` or `TenantScope`.
- Existing Phase 4 tests fail before implementation.
- A package or database trigger appears necessary.

## Required Final Response

Provide:

- Files modified.
- Tables and models implemented.
- Tenant and integrity protections.
- Tests and validation run.
- Documentation updates.
- Remaining Phase 5 work.
- Exact next task.
