# Phase 3 Core Tenant Context

## Role

Act as a senior Laravel architect, SaaS security architect, testing specialist,
and MCA project reviewer for this repository.

## Objective

Implement the execution-scoped TenantContext service, authenticated middleware
lifecycle, explicit Platform mode, active-school access checks, and foundational
lifecycle tests for Phase 3.

## Execution Mode

Implementation. Modify only files required for this focused tenancy foundation
and its tests and documentation updates.

## Authoritative Documents

Read and follow:

- `AGENTS.md`
- `app/AGENTS.md`
- `tests/AGENTS.md`
- `docs/AGENTS.md`
- `docs/PROJECT_CONSTITUTION.md`
- `docs/DECISIONS_LOG.md`
- `docs/DEVELOPMENT_ROADMAP.md`
- `docs/SYSTEM_ARCHITECTURE.md`
- `docs/TENANCY_DESIGN.md`
- `docs/DATABASE_DESIGN.md`
- `docs/SECURITY_GUIDELINES.md`
- `docs/TESTING_STRATEGY.md`
- `docs/CODING_STANDARDS.md`

## Scope

Implement:

- A type-safe Unresolved, Tenant, and Platform context state.
- An application-scoped TenantContext service with explicit state transitions,
  tenant-id access, and clear/reset behavior.
- Container registration for the TenantContext lifecycle.
- `TenantContextMiddleware` for authenticated web routes.
- Middleware ordering after authentication and before route-model binding.
- Active-user, canonical-role, school-id, active-school, and soft-delete checks.
- Explicit Platform mode only for a valid active Super Admin with
  `school_id = NULL`.
- Generic login denial for inactive, missing, or soft-deleted schools.
- Session invalidation when an existing authenticated user loses tenant access.
- Foundational unit and feature tests for state transitions, setup, cleanup,
  exceptions, sequential requests, malformed users, inactive schools, login,
  and middleware priority.
- Roadmap, governance, changelog, and tenancy implementation-status updates.

## Out Of Scope

Do not implement:

- `TenantScope` or `BelongsToTenant`.
- School Management controllers, policies, services, requests, routes, or views.
- School Settings schema or workflows.
- Queue jobs or console commands beyond testing the context service contract.
- Full Role and Permission Management.
- Any later module.
- New packages or changes to `composer.json`.

## Constraints

- Unresolved is the initial and reset state.
- A null `school_id` alone must never grant Platform mode.
- Tenant mode requires an active school user and an active, non-deleted school.
- Middleware must clear context before setup and in a `finally`-equivalent step.
- Authentication routes remain usable before tenant context exists.
- The hybrid User model must not receive the generic tenant global scope.
- Unauthorized tenant lifecycle failures must use generic responses without
  exposing school status.
- Keep middleware focused on cross-cutting lifecycle enforcement.
- Use native Laravel features and existing project patterns.

## Required Workflow

1. Inspect current authentication, routes, models, service provider, bootstrap,
   and tests.
2. Implement the TenantContext state and service.
3. Register the service and middleware with documented priority.
4. Apply middleware only to authenticated application routes that need context.
5. Enforce school lifecycle validation during login and existing sessions.
6. Add focused tests for allowed, denied, and cleanup paths.
7. Run targeted and full test suites.
8. Update only affected Phase 3 documentation status.

## Deliverables

- Tenant context state and service.
- Tenant context middleware and registration.
- Authentication school-state enforcement.
- Foundational lifecycle tests.
- Phase 3 status documentation update.

## Review Requirements

Verify:

- Unresolved is never treated as Platform.
- Valid school users receive only their own school id.
- Valid Super Admin receives explicit Platform state.
- Malformed, inactive, missing-school, and deleted-school users are denied.
- Context is cleared after successful requests and exceptions.
- Sequential requests cannot reuse another school's context.
- Middleware runs before route-model binding.
- Existing Phase 2 tests remain green.
- No scope, trait, School Management, or package work is introduced.

## Acceptance Criteria

The prompt is complete when:

- Context state transitions are deterministic and tested.
- Authenticated routes establish and clear context safely.
- Inactive or invalid school access fails closed.
- Platform mode requires the complete Super Admin invariant.
- Relevant and full tests pass.
- Documentation truthfully marks only the core context foundation implemented.

## Stop Conditions

Stop and report instead of guessing if:

- Laravel middleware ordering cannot satisfy the documented request flow.
- Login requires an unapproved authentication architecture change.
- The implementation would require a tenancy package.
- A required change belongs to School Management or the global-scope prompt.

## Required Final Response

Provide:

- Files modified.
- Context behavior implemented.
- Tests and validation run.
- Deferred Phase 3 work.
- Risks or remaining issues.
- Exact next prompt.
