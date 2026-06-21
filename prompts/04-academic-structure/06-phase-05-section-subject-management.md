# Phase 5 Section And Subject Management

## Execution Mode

Implementation task. Modify only the files required for Section and Subject
management, tests, navigation, and synchronized documentation.

## Objective

Complete the Phase 5 Academic Structure workflows by implementing secure,
tenant-aware Section and Subject management on the existing approved schema and
models.

## Authoritative Documents

Read and follow:

- `AGENTS.md`
- `app/AGENTS.md`
- `tests/AGENTS.md`
- `resources/AGENTS.md`
- `docs/PROJECT_CONSTITUTION.md`
- `docs/PROJECT_GOVERNANCE.md`
- `docs/DEVELOPMENT_ROADMAP.md`
- `docs/SYSTEM_ARCHITECTURE.md`
- `docs/TENANCY_DESIGN.md`
- `docs/DATABASE_DESIGN.md`
- `docs/MODULE_SPECIFICATIONS.md`
- `docs/SCREEN_FLOW.md`
- `docs/SECURITY_GUIDELINES.md`
- `docs/TESTING_STRATEGY.md`
- `docs/UI_UX_DESIGN_SYSTEM.md`

## Scope

Implement:

- Section directory, detail, create, edit, activate, deactivate, archive, and
  restore workflows.
- Subject directory, detail, create, edit, activate, deactivate, archive, and
  restore workflows.
- Optional active same-school Teacher Profile assignment for both entities.
- Active same-school Class selection and relationship validation.
- Platform read-only, tenant management, and assigned-Teacher read modes.
- Activity and audit records for every successful mutation.
- Responsive Blade screens and Academic Structure navigation.
- Focused feature, authorization, tenant-isolation, lifecycle, validation,
  rollback, route, and navigation tests.
- Phase 5 status and verification documentation updates.

## Out Of Scope

Do not implement:

- New migrations or schema changes.
- Student registration or Student Enrollment.
- Timetables, attendance, fees, examinations, reports, or exports.
- Bulk import, bulk assignment, or hard deletion.
- A new package or framework.

## Access Contract

- Super Admin may read current and archived Sections and Subjects across all
  schools in explicit Platform context when `academic.view` is effective.
  Super Admin may not mutate them.
- School Admin may manage only records in the active tenant. Every operation
  requires its exact effective `academic.*` permission.
- Teacher may read only active Sections and Subjects assigned to their own
  active Teacher Profile, and only when the parent Class is active. Teacher may
  not discover unassigned, inactive, archived, or cross-tenant records.
- Accountant, guest, inactive, malformed, and unresolved-context users are
  denied.
- UI visibility never substitutes for server-side authorization.

## Data And Validation Contract

Sections:

- `class_id`: required active, nonarchived Class from the active tenant.
- `teacher_id`: optional active, nonarchived Teacher Profile from the active
  tenant; the assignment may be cleared.
- `name`: required trimmed string, maximum 50 characters, unique within the
  parent Class including archived records.
- `capacity`: optional integer from 1 through 65535.
- New Sections start active.

Subjects:

- `class_id`: required active, nonarchived Class from the active tenant.
- `teacher_id`: optional active, nonarchived Teacher Profile from the active
  tenant; the assignment may be cleared.
- `name`: required trimmed string, maximum 120 characters.
- `code`: required trimmed uppercase string, maximum 50 characters, unique
  within the parent Class including archived records.
- `subject_type`: required and limited to `theory`, `practical`, or `optional`.
- New Subjects start active.

For both entities:

- Reject submitted `school_id`, `status`, `deleted_at`, or other lifecycle
  ownership fields.
- Requests provide user-facing validation; services independently revalidate
  tenant ownership and relationships.
- Foreign keys alone do not prove tenant alignment.

## Lifecycle Contract

- Deactivation changes active to inactive and preserves history.
- Archive requires inactive status and uses soft deletion.
- Restore requires an active, nonarchived, same-school parent Class and any
  retained Teacher assignment must still identify an active, nonarchived,
  same-school Teacher Profile.
- Restore returns the original record inactive; activation is separate.
- Activation revalidates the parent Class and optional Teacher assignment.
- Archived Section names and Subject codes remain reserved; reuse requires
  restoration of the original record.
- Do not add `DELETE` routes or hard-delete workflows.

## Architecture Constraints

- Use dedicated Policies, Form Requests, services, thin controllers, routes,
  and Blade views for Sections and Subjects.
- Use existing `Section`, `Subject`, `SchoolClass`, and `Teacher` models and the
  existing `BelongsToTenant` behavior.
- Derive `school_id` from TenantContext, never request input.
- Keep mutations transactional and lock the tenant School and target record.
- Use activity module `academic_structure` and actions `created`, `updated`,
  `activated`, `deactivated`, `archived`, and `restored`.
- Audit values must be sanitized and mutations must roll back if logging fails.
- Use Blade, Bootstrap 5, Bootstrap Icons, and the shared authenticated layout.

## Deliverables

- `SectionPolicy` and `SubjectPolicy` registrations.
- Section and Subject Form Requests.
- `SectionService` and `SubjectService`.
- Thin Section and Subject controllers.
- Tenant-safe retained-record routes without normal DELETE endpoints.
- Section and Subject Blade directories, forms, details, and lifecycle controls.
- Role-aware Academic Structure navigation.
- Focused Section and Subject management feature tests.
- Synchronized roadmap, governance, architecture, tenancy, security, testing,
  changelog, module, screen-flow, database-status, and prompt-status documents.

## Acceptance Criteria

- School A cannot read or mutate School B Sections or Subjects, including
  archived records.
- Super Admin sees Platform-context current and archived records but cannot
  create, edit, assign, activate, deactivate, archive, or restore.
- Teachers see only their own active assignments under active Classes and have
  no mutation actions.
- Parent Classes and assigned Teacher Profiles are active, nonarchived, and
  tenant-aligned at create, update, activate, and restore boundaries.
- Exact permission revocation denies the matching operation.
- Retained uniqueness, normalization, lifecycle, rollback, and no-delete rules
  are tested.
- Activity and audit records use the correct actor, tenant, model, action, and
  sanitized values.
- Focused and full test suites pass, Blade compiles, routes are valid, Pint
  passes, the frontend builds, Composer validates, and `git diff --check` passes.

## Documentation Updates

On successful verification, update implementation status and evidence in:

- `README.md`
- `docs/CHANGELOG.md`
- `docs/DATABASE_DESIGN.md`
- `docs/DECISIONS_LOG.md`
- `docs/DEVELOPMENT_ROADMAP.md`
- `docs/MODULE_SPECIFICATIONS.md`
- `docs/PROJECT_GOVERNANCE.md`
- `docs/SCREEN_FLOW.md`
- `docs/SECURITY_GUIDELINES.md`
- `docs/SYSTEM_ARCHITECTURE.md`
- `docs/TENANCY_DESIGN.md`
- `docs/TESTING_STRATEGY.md`
- `prompts/README.md`

## Review Gate

Before considering Phase 5 complete, run focused Section/Subject tests, the full
application suite, route and Blade validation, formatting, frontend build,
Composer validation, and whitespace checks. Report any remaining manual test
limitations explicitly.
