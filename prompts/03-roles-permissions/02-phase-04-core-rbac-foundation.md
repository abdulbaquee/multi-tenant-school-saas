# Phase 4 Core RBAC Foundation

## Role

Act as a senior Laravel architect, security architect, database architect,
testing specialist, and MCA project reviewer for this repository.

## Objective

Implement the native Laravel core RBAC foundation approved by DECISION-029
without building the Role & Permission management screens.

## Execution Mode

Implementation. Modify only the application, configuration, seeder, test, prompt,
and documentation files required by this scope.

## Authoritative Documents

Read and follow:

- `AGENTS.md`
- `app/AGENTS.md`
- `database/AGENTS.md`
- `tests/AGENTS.md`
- `docs/PROJECT_CONSTITUTION.md`
- `docs/DECISIONS_LOG.md`
- `docs/DEVELOPMENT_ROADMAP.md`
- `docs/SYSTEM_ARCHITECTURE.md`
- `docs/TENANCY_DESIGN.md`
- `docs/DATABASE_DESIGN.md`
- `docs/MODULE_SPECIFICATIONS.md`
- `docs/SECURITY_GUIDELINES.md`
- `docs/TESTING_STRATEGY.md`
- `docs/CODING_STANDARDS.md`

## Scope

Implement:

- `config/rbac.php` as the machine-readable canonical roles, permissions,
  default/maximum mappings, and essential-permission source.
- A fixed `Permission` model and immutable Role/Permission catalog behavior.
- Role-to-permission and permission-to-role relationships.
- Database-backed `User::hasPermission()` resolution without application caching.
- Seeder synchronization that:
  - creates or updates canonical catalog metadata;
  - gives a fresh role its canonical default mapping;
  - removes out-of-bound mappings;
  - restores essential mappings;
  - synchronizes Super Admin to its exact immutable matrix-approved set;
  - preserves allowed runtime revocations for existing school-role mappings;
  - never overwrites an existing Super Admin password;
  - requires an explicit complex password when creating a fresh Super Admin.
- Permission-aware authorization for currently implemented Dashboard, School
  Management, School Settings, and User Management actions while retaining all
  TenantContext and record-scope checks.
- `roles.assign` enforcement when a user's role actually changes.
- Target-only session and remember-token revocation after a role or school-
  ownership change.
- Focused mapping-integrity, immutable-catalog, authorization, tenant-isolation,
  and role-change session tests.
- Documentation and changelog evidence for the completed foundation.

## Out Of Scope

Do not implement:

- Role or Permission controllers, routes, Form Requests, services, Blade views,
  sidebar links, or mapping-management UI.
- Custom roles, custom permissions, tenant-specific mappings, inheritance, or
  per-user overrides.
- A schema migration unless repository evidence proves the documented schema is
  missing.
- External RBAC packages or permission caching.
- Later roadmap modules.

## Constraints

- Use Laravel 13, PHP 8.4, and native Laravel authorization.
- Keep canonical identities separate from configurable database mappings.
- Permission grants never bypass TenantContext, Policies, ownership, assignment,
  lifecycle, or service checks.
- Super Admin identity validation remains role-code plus `school_id = NULL`; it
  must not depend on a mutable permission.
- Role and Permission catalog writes from normal Eloquent workflows must fail.
- Use transactions for mapping synchronization and role-change side effects.
- Do not modify `composer.json`, install packages, or change the approved schema.

## Required Workflow

1. Inspect the current schema, seeder, policies, Gates, services, and tests.
2. Implement the canonical RBAC configuration and catalog models.
3. Reconcile the bootstrap mappings safely.
4. Migrate current authorization to exact permission codes plus existing scope
   controls.
5. Add role-change permission and session protections.
6. Add focused tests before running the full suite.
7. Run Pint, Composer validation, and the complete test suite.
8. Update only documentation affected by the implemented behavior.

## Deliverables

- Native database-backed RBAC foundation.
- Canonical configuration and safe seeder synchronization.
- Permission-aware current-module authorization.
- Role-change session security.
- Automated regression evidence.
- Updated changelog and testing evidence.

## Acceptance Criteria

- Exactly four fixed roles and the fixed permission catalog are represented.
- Super Admin has the exact immutable matrix-approved mapping.
- Every active mapping is inside its role maximum and includes essentials.
- Current Policies require exact permissions and retain scope checks.
- Mapping changes are visible on the next permission check without cache reset.
- Role changes require `roles.assign` and revoke only the target user's sessions.
- Guests, incorrect roles, missing permissions, and cross-tenant actors are denied.
- Existing Phase 2 and Phase 3 behavior remains green.
- No management UI or later module is introduced.

## Stop Conditions

Stop and report instead of guessing if:

- The documented maximum mapping cannot be expressed from canonical permission
  codes.
- Existing schema differs from `DATABASE_DESIGN.md`.
- Permission-aware authorization would weaken a TenantContext or Policy boundary.
- A package or architecture change appears necessary.

## Required Final Response

Provide:

- Files modified.
- Foundation behavior implemented.
- Authorization changes.
- Tests and validation run.
- Documentation updates.
- Remaining Phase 4 work.
- Exact next task.
