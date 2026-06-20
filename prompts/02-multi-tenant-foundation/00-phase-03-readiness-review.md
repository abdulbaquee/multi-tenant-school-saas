# Phase 3 Multi-Tenant Foundation Readiness Review

## Role

Act as a senior Laravel architect, SaaS architect, security architect, database
architect, testing specialist, and MCA project reviewer for this repository.

## Objective

Determine whether the repository is ready to begin Phase 3: Multi-Tenant
Foundation without making undocumented tenancy, School Management, security, or
database decisions during implementation.

## Execution Mode

Audit only. Do not modify files.

## Authoritative Documents

Read and follow:

- `AGENTS.md`
- `app/AGENTS.md`
- `database/AGENTS.md`
- `tests/AGENTS.md`
- `resources/AGENTS.md`
- `docs/PROJECT_CONSTITUTION.md`
- `docs/PROJECT_GOVERNANCE.md`
- `docs/DECISIONS_LOG.md`
- `docs/DEVELOPMENT_ROADMAP.md`
- `docs/SYSTEM_ARCHITECTURE.md`
- `docs/TENANCY_DESIGN.md`
- `docs/DATABASE_DESIGN.md`
- `docs/ER_DIAGRAM.md`
- `docs/MODULE_SPECIFICATIONS.md`
- `docs/SCREEN_FLOW.md`
- `docs/SECURITY_GUIDELINES.md`
- `docs/TESTING_STRATEGY.md`
- `docs/UI_UX_DESIGN_SYSTEM.md`
- `docs/CODING_STANDARDS.md`

## Phase 3 Scope

Phase 3 includes readiness for:

- School Management for Super Admin.
- School registration, listing, viewing, editing, activation, and deactivation.
- School Settings foundation for the owning school.
- Request-scoped tenant context resolved from the authenticated user's
  `school_id`.
- Tenant context middleware registered after authentication.
- A reusable `TenantScope` and `BelongsToTenant` pattern.
- Automatic tenant filtering for tenant-owned models.
- Automatic `school_id` assignment from tenant context on creation.
- Explicit, authorized Super Admin platform-wide access.
- Mandatory tenant-isolation, authorization, validation, and lifecycle tests.
- Documentation updates after implementation.

## Out Of Scope

Do not implement or approve implementation of:

- Full Role and Permission Management UI.
- Academic Structure, Student Management, Attendance, Fees, Examinations,
  Reports, Analytics, Activity Logs, Audit Logs, or Backup Management.
- Subdomain or domain-based tenant resolution.
- Database-per-tenant or schema-per-tenant architecture.
- Stancl Tenancy, Spatie Multitenancy, or another tenancy package.
- Application code, migrations, tests, views, or documentation changes during
  this audit.

## Review Criteria

Verify:

- Phase 2 is completed, committed, and passing its automated tests.
- The current `schools` and `users` schema matches `DATABASE_DESIGN.md`.
- School Management fields, constraints, status values, and deletion behavior
  are implementation-ready.
- `TENANCY_DESIGN.md` completely defines tenant resolution, request lifecycle,
  default-deny behavior, model scoping, record creation, Super Admin bypass,
  console and queued-job behavior, and safe context clearing.
- School deactivation behavior is defined for existing sessions and future
  authentication attempts.
- Tenant-owned model coverage and documented exceptions are unambiguous.
- Policies and services remain required defense-in-depth controls.
- Route-model binding and record-not-found behavior cannot leak cross-tenant
  records.
- Tenant isolation tests cover read, create, update, delete/deactivate, listing,
  search, route-model binding, Super Admin access, malformed users, stale
  context, and sequential requests.
- No documentation conflict or missing decision would force implementation to
  guess.
- No prohibited tenancy package or architecture is present.

## Required Workflow

1. Inspect repository status and the completed Phase 2 implementation.
2. Compare Phase 3 roadmap scope with architecture, tenancy, database, module,
   screen-flow, security, and testing documents.
3. Inspect the existing School and User schema, models, policies, services,
   routes, middleware registration, and tests.
4. Run non-mutating validation commands and the existing test suite where
   available.
5. Classify every issue as blocking or non-blocking.
6. Recommend the exact Phase 3 prompt execution order.

## Deliverables

- Phase 3 readiness score from 1 to 10.
- Evidence reviewed.
- Blocking issues.
- Non-blocking improvements.
- Documentation contradictions or missing decisions.
- Security and tenant-isolation risk assessment.
- Recommended Phase 3 prompt execution order.
- Final decision: READY, READY WITH MINOR IMPROVEMENTS, REQUIRES REMEDIATION, or
  NOT READY.

## Acceptance Criteria

The review is complete when:

- Readiness is based on repository evidence rather than assumptions.
- Tenant isolation risks are treated as critical.
- No implementation work is performed.
- Any unresolved lifecycle or bypass behavior is identified before coding.
- The next action is precise and belongs to Phase 3.

## Stop Conditions

Stop and recommend documentation remediation instead of implementation if:

- Tenant context lifecycle or Super Admin bypass is ambiguous.
- School deactivation and tenant access behavior conflicts across documents.
- The schema or module scope requires undocumented decisions.
- Required tenant isolation tests cannot be specified from current governance.
- Phase 2 verification is failing.

## Required Final Response

Provide:

- Readiness score.
- Audit findings ordered by severity.
- Blocking and non-blocking issues.
- Phase 3 prompt execution order.
- Final readiness decision and justification.
- Exact next task.
