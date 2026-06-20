# Phase 3 Tenant Service-Context Hardening

## Role

Act as a senior Laravel architect, SaaS security architect, multi-tenancy
specialist, testing specialist, and MCA project reviewer for this repository.

## Objective

Harden User Management and Dashboard service boundaries so they fail closed
unless the acting user is executing within the correct explicit Tenant or
Platform context, including when services are called outside HTTP middleware.

## Execution Mode

Implementation. Modify only the affected services, focused tenant-context
tests, and directly related Phase 3 verification documentation.

## Authoritative Documents

Read and follow:

- `AGENTS.md`
- `app/AGENTS.md`
- `tests/AGENTS.md`
- `docs/AGENTS.md`
- `docs/PROJECT_CONSTITUTION.md`
- `docs/SYSTEM_ARCHITECTURE.md`
- `docs/TENANCY_DESIGN.md`
- `docs/MODULE_SPECIFICATIONS.md`
- `docs/SECURITY_GUIDELINES.md`
- `docs/TESTING_STRATEGY.md`
- `docs/CODING_STANDARDS.md`
- `prompts/00-governance/03-tenant-isolation-review.md`

## Scope

Implement:

- Actor-to-context validation at every public `UserService` entry point.
- Actor-to-context validation before `DashboardService` loads data or computes
  metrics.
- Explicit Platform-context enforcement for Super Admin service execution.
- Matching Tenant-context enforcement for School Admin, Teacher, and Accountant
  service execution.
- Direct service integration tests that do not depend on request middleware.
- Phase 3 testing evidence and changelog updates.

## Out Of Scope

Do not implement:

- New modules, routes, controllers, requests, models, views, or migrations.
- New tenant-context states or alternative tenancy architecture.
- Generic tenant scopes on the hybrid `users` identity model.
- Reports, exports, jobs, queues, or scheduled commands.
- Package installation or `composer.json` changes.
- Permission-matrix or role changes.

## Constraints

- Continue using the existing execution-scoped `TenantContext` service.
- Unresolved context must deny every affected service operation.
- A school actor in Platform context must be denied.
- A school actor in another school's Tenant context must be denied.
- A Super Admin in Tenant context must be denied.
- School actors may proceed only when Tenant context matches their `school_id`.
- Super Admin may proceed only in explicit Platform context and with existing
  policy or Gate authorization.
- Existing Policies and Gates remain mandatory; context validation supplements
  rather than replaces authorization.
- The `users` table remains a documented hybrid identity exception and retains
  service-scoped operational queries.
- Keep controllers thin and leave middleware lifecycle behavior unchanged.

## Required Workflow

1. Inspect `UserService`, `DashboardService`, `TenantContext`, their callers,
   existing policies, and current tenant-isolation tests.
2. Add fail-closed actor-to-context validation before every affected query or
   mutation.
3. Add direct service tests for Unresolved, mismatched Tenant, incorrect mode,
   matching Tenant, and matching Platform execution.
4. Rerun User Management and Dashboard regression tests.
5. Run the full application suite, formatting validation, route inspection,
   scope-bypass search, and diff validation.
6. Update measured Phase 3 verification evidence.
7. Rerun `prompts/00-governance/03-tenant-isolation-review.md`.

## Deliverables

- Fail-closed User Management service boundary.
- Fail-closed Dashboard service boundary.
- Direct service-context integration tests.
- Updated Phase 3 changelog and testing baseline.
- Completed tenant-isolation review result.

## Review Requirements

Verify:

- Every public UserService operation validates actor-to-context alignment.
- Dashboard data is not loaded before actor-to-context validation.
- Existing Policies and Gates still authorize the requested action.
- No direct service invocation treats Unresolved context as Platform context.
- No school actor can use another tenant's context or Platform context.
- No Super Admin can use tenant-wide services from Tenant context.
- Matching Tenant execution remains isolated to one school.
- Matching Platform execution retains authorized platform-wide behavior.
- No production `withoutGlobalScope` bypass is introduced.
- Every authenticated route still uses `tenant.context` middleware.

## Acceptance Criteria

The prompt is complete when:

- All affected service entry points fail closed under invalid context.
- Direct denied-path and allowed-path tests pass.
- User Management and Dashboard regression tests pass.
- The complete test suite passes.
- Laravel Pint and `git diff --check` pass.
- Route and scope-bypass inspections find no new tenant-isolation defect.
- Documentation records exact measured verification evidence.
- The tenant-isolation review reports no remaining critical, major, or medium
  finding for implemented modules.

## Stop Conditions

Stop and report instead of guessing if:

- Correct enforcement requires changing the documented tenancy architecture.
- A required operation cannot distinguish Tenant from Platform context.
- Existing policy behavior conflicts with the canonical permission matrix.
- The remediation requires a schema change or external package.

## Required Final Response

Provide:

- Files modified.
- Service-context controls implemented.
- Denied and allowed paths tested.
- Full verification results.
- Tenant-isolation review score and decision.
- Exact next governance review prompt.
