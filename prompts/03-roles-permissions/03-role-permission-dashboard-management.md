# Phase 4 Role & Permission Dashboard Management

## Role

Act as a senior Laravel architect, security architect, SaaS architect, UI/UX
implementer, testing specialist, and MCA project reviewer for this repository.

## Objective

Implement the approved Role & Permission dashboard workspace on top of the core
RBAC foundation, including fixed-role visibility, constrained school-role
mapping updates, tenant-safe read access, audit evidence, and complete denied-
path testing.

## Execution Mode

Implementation. Modify only the application, routes, Blade views, tests,
documentation, and prompt files required by this scope.

## Authoritative Documents

Read and follow:

- `AGENTS.md`
- `app/AGENTS.md`
- `resources/AGENTS.md`
- `tests/AGENTS.md`
- `docs/AGENTS.md`
- `docs/PROJECT_CONSTITUTION.md`
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
- `config/rbac.php`

## Prerequisites

Verify before implementation:

- The core RBAC foundation commit is present.
- The full test suite passes.
- Exactly four fixed roles and the fixed permission catalog exist.
- Super Admin has its exact immutable matrix-approved mapping.
- `User::hasPermission()` and current-module permission-aware Policies work.
- No external RBAC package is installed.

## Scope

Implement:

- A `RolePolicy` for fixed-role directory, details, and mapping-update access.
- A `RolePermissionService` that:
  - enforces actor-aligned Platform or Tenant context;
  - lists only roles visible to the actor;
  - groups effective permissions by module;
  - exposes mapping metadata from `config/rbac.php`;
  - permits updates only for School Admin, Teacher, and Accountant by an
    authorized Super Admin in Platform context;
  - rejects Super Admin mapping changes, essential removal, out-of-bound grants,
    forged permission IDs, duplicates, and stale mapping submissions;
  - replaces the mapping transactionally;
  - writes Platform-owned activity and audit records with old/new permission
    code arrays.
- A Form Request for mapping validation and mapping-fingerprint verification.
- A thin `RoleController` for index, show, edit, and update HTTP flow.
- Authenticated `tenant.context` routes matching `SCREEN_FLOW.md`.
- A Roles & Permissions sidebar item controlled by server-side authorization.
- Bootstrap 5 and Bootstrap Icons Blade screens for:
  - fixed role directory;
  - effective permission details grouped by module;
  - constrained mapping edit for school roles;
  - read-only Super Admin mapping;
  - read-only School Admin views of School Admin, Teacher, and Accountant.
- Essential permissions shown checked, disabled, and labelled Essential.
- A Bootstrap confirmation step summarizing grants and revocations before save.
- Success, validation, forbidden, stale-submission, and empty-state behavior.
- Feature, authorization, tenant-context, mapping-integrity, rollback, audit,
  menu-visibility, and responsive-view tests.
- Documentation and changelog updates after implementation.

## Access Contract

### Super Admin

- Requires `roles.view` for index/show and `roles.manage` for edit/update.
- Must be active, authenticated, `school_id = NULL`, and in explicit Platform
  context.
- Can view all four roles and the complete catalog.
- Cannot edit the Super Admin mapping.
- Can edit non-essential mappings for School Admin, Teacher, and Accountant only
  inside each role's configured maximum.

### School Admin

- Requires `roles.view`, active-school membership, and matching Tenant context.
- Can view only School Admin, Teacher, and Accountant effective mappings.
- Cannot view Super Admin mapping or access edit/update routes.
- Continues to assign school roles through User Management when `roles.assign`
  and User Policy checks pass.

### Teacher, Accountant, Guest, And Malformed Users

- Receive no Roles & Permissions navigation.
- Are denied every role-management route and direct service method.

## Mapping Integrity

- Treat permission IDs as untrusted input.
- Convert validated IDs to canonical permission codes before comparison.
- Require every submitted code to belong to the selected role's maximum set.
- Merge locked essentials server-side; never trust disabled checkbox submission.
- Use a deterministic fingerprint of sorted current permission codes to detect a
  stale edit before writing.
- Lock the role/mapping workflow inside the transaction and reject a fingerprint
  mismatch with a user-friendly validation error.
- Do not update Role or Permission catalog rows.
- Do not cache effective permission results.

## Audit Contract

- Activity module: `role_permissions`.
- Activity action: `mapping_updated`.
- Activity and audit records use `school_id = NULL` in explicit Platform context.
- Audit target: the edited Role.
- Audit old/new values contain sorted permission-code arrays only.
- A logging failure rolls back the mapping replacement.

## Out Of Scope

Do not implement:

- Role creation, rename, code changes, deletion, or custom roles.
- Permission creation, rename, code changes, or deletion.
- Per-user permission overrides, inheritance, tenant-specific mappings, or
  permission caching.
- Academic, Student, Attendance, Fee, Examination, Reporting, log-review, or
  Backup screens.
- External RBAC packages, JavaScript frameworks, or schema changes.
- User-role assignment redesign beyond integration verification.

## UI Requirements

- Use the existing authenticated shell, page header, breadcrumbs, and sidebar.
- Use Bootstrap 5, Bootstrap Icons, semantic tables, checkboxes, badges, alerts,
  and confirmation modal components.
- Keep role and permission information compact and scannable.
- Do not use nested cards, custom SVG icons, or unsupported frameworks.
- Preserve stable responsive layouts and prevent permission labels or action
  controls from overflowing on mobile.
- Include required accessibility labels, keyboard behavior, and visible focus.

## Required Tests

Prove:

- Guests are redirected to login.
- Super Admin can list and view all roles.
- Super Admin can update an allowed school-role mapping.
- Super Admin cannot edit its own mapping.
- Essential removal and out-of-bound or forged grants fail without partial writes.
- Duplicate IDs and stale fingerprints fail validation.
- School Admin can view only the three school roles and cannot edit mappings.
- School Admin cannot infer or access Super Admin mapping by direct URL.
- Teacher, Accountant, malformed users, incorrect context, and direct service
  calls are denied.
- A revoked current-module permission changes menu/action access on the next
  request and restoration returns it.
- Mapping changes write sanitized Platform activity and audit records.
- Forced logging failure rolls back the mapping.
- Sidebar visibility and active state match permissions.
- Existing tenant isolation and all prior tests remain green.

## Required Workflow

1. Inspect the core RBAC foundation and existing UI patterns.
2. Implement Policy, service, request, controller, and routes in that order.
3. Build the read-only screens before mapping edits.
4. Implement constrained mapping validation and transaction behavior.
5. Add navigation and confirmation behavior.
6. Add allowed and denied feature tests, then rollback and stale tests.
7. Run targeted tests, full tests, Pint, and Composer validation.
8. Update implementation status, testing evidence, and changelog.
9. Provide manual dashboard test steps and dummy scenarios.

## Acceptance Criteria

- The dashboard exposes only the approved fixed-role workflows.
- Every route and direct service method is authorized and context-aware.
- No actor can exceed the canonical maximum or remove essentials.
- Super Admin cannot be locked out through the UI.
- School Admin visibility does not expose Super Admin mapping data.
- Mapping changes are immediate, transactional, stale-safe, and auditable.
- The complete regression suite passes.
- No out-of-scope module or framework is introduced.

## Stop Conditions

Stop and report instead of guessing if:

- Core RBAC tests are failing.
- Mapping configuration conflicts with `MODULE_SPECIFICATIONS.md`.
- Safe stale-write detection cannot be implemented without a schema decision.
- Tenant-context enforcement would be weakened.
- A package or migration appears necessary.

## Required Final Response

Provide:

- Files modified.
- Role and permission workflows implemented.
- Authorization and tenant controls.
- Tests and validation run.
- Documentation updates.
- Manual dashboard testing steps and dummy scenarios.
- Remaining Phase 4 work.
- Exact next task.
