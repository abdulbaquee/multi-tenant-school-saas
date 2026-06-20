# Phase 3 Tenant Scope And School Settings Schema

## Role

Act as a senior Laravel architect, SaaS security architect, database architect,
testing specialist, and MCA project reviewer for this repository.

## Objective

Implement the default-deny `TenantScope` and reusable `BelongsToTenant` pattern,
then prove automatic isolation using `school_settings` as the first strict
tenant-owned model.

## Execution Mode

Implementation. Modify only the tenancy scope/trait, School Settings schema and
model foundation, tests, and affected status documentation.

## Authoritative Documents

Read and follow:

- `AGENTS.md`
- `app/AGENTS.md`
- `database/AGENTS.md`
- `tests/AGENTS.md`
- `docs/AGENTS.md`
- `docs/PROJECT_CONSTITUTION.md`
- `docs/DECISIONS_LOG.md`
- `docs/DEVELOPMENT_ROADMAP.md`
- `docs/SYSTEM_ARCHITECTURE.md`
- `docs/TENANCY_DESIGN.md`
- `docs/DATABASE_DESIGN.md`
- `docs/ER_DIAGRAM.md`
- `docs/SECURITY_GUIDELINES.md`
- `docs/TESTING_STRATEGY.md`
- `docs/CODING_STANDARDS.md`

## Scope

Implement:

- A stateless `TenantScope` that resolves the current TenantContext at query
  execution time.
- Tenant-state filtering by qualified `school_id`.
- Unresolved-state deny-all reads.
- Explicit Platform-state unscoped reads.
- A reusable `BelongsToTenant` model trait.
- Automatic context-derived `school_id` assignment on create.
- Rejection of strict tenant-owned creation outside Tenant state.
- Immutable tenant ownership after record creation.
- A new additive `school_settings` migration matching `DATABASE_DESIGN.md`.
- A `SchoolSetting` model using `BelongsToTenant` and documented casts.
- One-to-one School and SchoolSetting relationships.
- Automated tests for unresolved, tenant, platform, create, forged tenant id,
  immutable ownership, sequential tenants, and cross-tenant route-model binding.
- Migration execution and affected Phase 3 status documentation.

## Out Of Scope

Do not implement:

- School Management controllers, policies, services, requests, routes, or views.
- School Settings controllers, requests, services, policies, routes, or views.
- Logo upload or file storage workflows.
- Academic or later tenant-owned models.
- Role and Permission Management.
- Queue jobs or console commands.
- New packages or `composer.json` changes.

## Constraints

- Do not rewrite the already-deployed core authentication migration.
- Use a new additive migration matching every documented School Settings column,
  default, index, unique constraint, and `restrictOnDelete()` foreign key.
- The scope must not retain a request-scoped TenantContext instance in static
  model boot state.
- Unresolved reads must return no tenant rows.
- Platform reads require context already established by authorized middleware or
  service paths.
- Strict tenant-owned creates require Tenant state.
- Browser-supplied or manually assigned `school_id` cannot override context.
- `school_id` is immutable after creation.
- Cross-tenant route-model binding returns 404.
- Keep User as the documented hybrid identity exception without the trait.

## Required Workflow

1. Inspect TenantContext, current migrations, School model, schema dictionary,
   and test conventions.
2. Create the additive School Settings migration.
3. Implement the stateless scope and reusable trait.
4. Implement the SchoolSetting model and relationships.
5. Add focused unit/integration/feature isolation tests.
6. Run targeted tests and the full suite.
7. Run migration status and formatting validation.
8. Update only affected Phase 3 status and evidence documentation.

## Deliverables

- TenantScope.
- BelongsToTenant trait.
- Tenant-context exception for rejected operations.
- School Settings migration and model foundation.
- School relationships.
- Automatic isolation tests.
- Phase 3 status documentation update.

## Review Requirements

Verify:

- Scope evaluation uses the current execution context for every query.
- Unresolved state cannot leak records.
- Tenant state sees only one school.
- Platform state can read all records but cannot create strict tenant-owned
  records without entering Tenant state.
- Forged `school_id` is overwritten by context.
- Ownership cannot move between schools.
- Cross-tenant route binding returns 404.
- One-to-one and foreign-key constraints match the data dictionary.
- Existing authentication and TenantContext tests remain green.
- No UI, School Management, later module, or package work is introduced.

## Acceptance Criteria

The prompt is complete when:

- `school_settings` is the first automatically isolated tenant-owned model.
- All allowed and denied isolation paths are tested.
- Migration and model match authoritative documentation.
- Targeted and full tests pass.
- Local migration status is current.
- Documentation truthfully marks scope/schema complete but workflows pending.

## Stop Conditions

Stop and report instead of guessing if:

- The schema conflicts with `DATABASE_DESIGN.md`.
- Static model boot behavior would retain stale request context.
- Platform creation requires undocumented bypass behavior.
- The task requires School Management or Settings UI implementation.

## Required Final Response

Provide:

- Files modified.
- Isolation behavior implemented.
- Schema and relationship changes.
- Tests and migration validation.
- Deferred work and remaining risks.
- Exact next prompt.
