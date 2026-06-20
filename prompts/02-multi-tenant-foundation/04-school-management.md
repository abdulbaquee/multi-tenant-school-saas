# Phase 3 School Management

## Role

Act as a senior Laravel architect, SaaS security architect, database architect,
testing specialist, UI reviewer, and MCA project reviewer for this repository.

## Objective

Implement the Super Admin School Management workflow with secure creation,
editing, viewing, searching, activation, and deactivation while preserving tenant
data and enforcing the documented school lifecycle.

## Execution Mode

Implementation. Modify only School Management, directly affected user-assignment
rules, tests, navigation, and status documentation.

## Authoritative Documents

Read and follow:

- `AGENTS.md`
- `app/AGENTS.md`
- `database/AGENTS.md`
- `tests/AGENTS.md`
- `resources/AGENTS.md`
- `docs/AGENTS.md`
- `docs/PROJECT_CONSTITUTION.md`
- `docs/PROJECT_GOVERNANCE.md`
- `docs/DECISIONS_LOG.md`
- `docs/DEVELOPMENT_ROADMAP.md`
- `docs/SYSTEM_ARCHITECTURE.md`
- `docs/TENANCY_DESIGN.md`
- `docs/DATABASE_DESIGN.md`
- `docs/MODULE_SPECIFICATIONS.md`
- `docs/SCREEN_FLOW.md`
- `docs/SECURITY_GUIDELINES.md`
- `docs/TESTING_STRATEGY.md`
- `docs/UI_UX_DESIGN_SYSTEM.md`
- `docs/CODING_STANDARDS.md`

## Scope

Implement:

- Super Admin-only School Management authorization.
- School list, search, status filter, details, create, and edit workflows.
- School activation and deactivation actions.
- A required deactivation reason and recorded deactivation timestamp.
- Revocation of database sessions and remember tokens for users of a
  deactivated school.
- Preservation of the school, users, settings, and historical data during
  deactivation.
- Automatic creation of the documented default School Settings record when a
  school is created.
- Safe temporary TenantContext execution for strict tenant-owned setup records.
- Prevention of new user assignment to inactive schools.
- Bootstrap 5 School Management views and role-aware navigation.
- Feature, authorization, lifecycle, validation, and security tests.
- Affected Phase 3 status documentation.

## Out Of Scope

Do not implement:

- School deletion or restoration UI.
- Physical tenant-data deletion.
- School Settings editing workflows.
- School logo upload or file storage.
- Role and Permission Management.
- Academic, student, attendance, fee, examination, reporting, or backup modules.
- New packages or `composer.json` changes.

## Constraints

- School Management is available only to Super Admin users in Platform context.
- School Admin, Teacher, and Accountant users must receive server-side denial.
- Do not accept lifecycle fields such as `status`, `deactivated_at`,
  `deactivation_reason`, or `deleted_at` through create or edit forms.
- School creation starts in active status and creates exactly one default
  School Settings record.
- School code and email must remain unique and normalized consistently.
- Deactivation must not soft-delete the school or delete tenant-owned records.
- Deactivation must clear remember tokens and database sessions for every user
  of that school without affecting other schools or Super Admin users.
- Inactive and soft-deleted schools remain denied by TenantContextMiddleware.
- Reactivation clears deactivation metadata but does not reactivate individually
  inactive users.
- Use thin controllers, Form Requests, a service, and a policy.
- Use Blade, Bootstrap 5, and Bootstrap Icons only.

## Required Workflow

1. Inspect the School schema, lifecycle policy, permissions, screen flow, current
   tenant context, and existing User Management conventions.
2. Implement a safe temporary tenant-context callback with exact context
   restoration and focused tests.
3. Implement School policy, Form Requests, service, controller, and routes.
4. Implement Bootstrap views and Super Admin-only navigation.
5. Tighten user assignment so inactive schools cannot receive new users.
6. Add allowed and denied School Management tests.
7. Run targeted tests, the full suite, formatting, and Composer validation.
8. Update only affected roadmap, governance, changelog, screen-flow, and testing
   evidence documentation.

## Deliverables

- School Management policy, requests, service, controller, and routes.
- School list, details, create, and edit views.
- Activation and deactivation lifecycle controls.
- Default School Settings initialization.
- Safe temporary tenant-context execution.
- Inactive-school user-assignment protection.
- Comprehensive School Management tests.
- Phase 3 status documentation update.

## Review Requirements

Verify:

- Only Super Admin users can reach any School Management endpoint.
- Search and status filtering return expected platform records.
- Required fields, uniqueness, normalization, and prohibited lifecycle fields
  are enforced server-side.
- Every new school receives exactly one default School Settings record.
- Temporary tenant execution restores Platform, Tenant, or Unresolved state even
  when the callback throws.
- Deactivation records its reason and timestamp, revokes only affected users'
  sessions and remember tokens, and preserves all records.
- Inactive school users cannot authenticate or continue an existing session.
- Reactivation restores school access only for users whose own status is active.
- Inactive schools cannot receive newly assigned users.
- There is no normal delete route or UI.
- Navigation and action visibility match the permission matrix.
- Existing authentication, user management, dashboard, and tenant isolation
  tests remain green.

## Acceptance Criteria

The prompt is complete when:

- The documented School Management workflow works end to end.
- All allowed and denied authorization paths are tested.
- School lifecycle retention and session revocation are proven by tests.
- Tenant context restoration and default settings creation are proven by tests.
- Targeted and full tests pass.
- Formatting and dependency metadata validation pass.
- Documentation truthfully records completed and deferred Phase 3 work.

## Stop Conditions

Stop and report instead of guessing if:

- The School schema conflicts with `DATABASE_DESIGN.md`.
- School lifecycle behavior conflicts across canonical documents.
- Default settings require undocumented schema or tenancy bypass behavior.
- The task requires deletion, settings editing, file uploads, or later modules.

## Required Final Response

Provide:

- Files modified.
- School Management and lifecycle behavior implemented.
- Authorization and tenant-safety controls.
- Tests and validation results.
- Deferred work and remaining risks.
- Exact next prompt or review gate.
