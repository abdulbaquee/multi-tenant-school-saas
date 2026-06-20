# Phase 3 Tenancy Design Remediation

## Role

Act as a senior Laravel architect, SaaS architect, security architect, database
architect, testing specialist, documentation maintainer, and MCA project
reviewer for this repository.

## Objective

Resolve every documentation blocker identified by the Phase 3 readiness review
so Native Laravel Multi-Tenancy and School Management can be implemented without
guessing or creating an unsafe implicit tenant bypass.

## Execution Mode

Documentation only. Do not modify application code, migrations, tests, routes,
views, configuration, package manifests, or lock files.

## Authoritative Documents

Read and follow:

- `AGENTS.md`
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
- `docs/SCREEN_FLOW.md`
- `docs/SECURITY_GUIDELINES.md`
- `docs/TESTING_STRATEGY.md`
- `docs/CODING_STANDARDS.md`
- `docs/CHANGELOG.md`

## Scope

Update documentation to define:

- Explicit unresolved, tenant, and platform tenant-context states.
- Default-deny behavior when context is unresolved.
- Explicit, authorized Super Admin platform context.
- Middleware ordering before route-model binding and context cleanup after every
  request.
- Safe context use and cleanup for console commands and queued jobs.
- Tenant-owned, platform, tenant-registry, and hybrid table/model categories.
- The pre-authentication `users` exception and its defense-in-depth controls.
- Route-model binding behavior for cross-tenant records.
- School activation, deactivation, soft deletion, login, session revocation,
  and reactivation behavior.
- Whether School Settings belongs to Phase 3.
- Mandatory lifecycle and tenant-isolation test scenarios.
- The revised Phase 3 readiness status and changelog entry.

## Out Of Scope

Do not:

- Generate or modify application code.
- Generate migrations, models, middleware, scopes, controllers, requests,
  policies, services, routes, tests, or views.
- Install tenancy or other packages.
- Change the single-database shared-schema architecture.
- Add domain/subdomain tenant resolution.
- Implement later roadmap modules.

## Constraints

- Use Native Laravel Multi-Tenancy with `school_id`.
- Absence of tenant context must never silently grant platform-wide access.
- Super Admin bypass must be explicit, authenticated, and policy protected.
- Tenant context must not leak between requests, jobs, commands, or tests.
- Tenant-owned record creation must derive `school_id` from tenant context.
- Cross-tenant route-model binding must not reveal record existence.
- Keep the design simple enough for MCA implementation and viva explanation.
- Preserve retention-first deletion and `restrictOnDelete()` policies.

## Required Workflow

1. Review the readiness findings and current tenancy documentation.
2. Make `TENANCY_DESIGN.md` the complete source of truth for the remediated
   lifecycle and model classification.
3. Reconcile security, testing, architecture, coding, roadmap, module, screen,
   governance, decision, and changelog documents with that design.
4. Do not claim any tenancy implementation exists yet.
5. Validate terminology and Phase 3 status across documentation.
6. Rerun the Phase 3 readiness review after remediation.

## Deliverables

- Implementation-ready tenancy lifecycle specification.
- Explicit Super Admin bypass policy.
- School lifecycle and session policy.
- Model/table tenancy classification.
- Reconciled Phase 3 School Settings scope.
- Expanded tenant-isolation test contract.
- Decision-log and changelog traceability.
- List of remaining non-blocking issues.

## Review Requirements

Verify:

- Unresolved context is default-deny.
- Platform context cannot be inferred from a null `school_id` alone.
- Authentication can load a globally unique user before tenant resolution
  without making operational user queries unscoped.
- Tenant context is active before route-model binding.
- Context is cleared in `finally`-equivalent lifecycle handling.
- Inactive or soft-deleted schools cannot authenticate or continue sessions.
- School Settings scope is consistent across roadmap and module specifications.
- Tests cover sequential requests, malformed users, jobs, console behavior,
  route-model binding, and inactive schools.
- No application implementation is falsely marked complete.

## Acceptance Criteria

The remediation is complete when:

- Phase 3 implementation can proceed without an implicit unscoped state.
- All readiness blockers have one documented answer.
- All affected documents agree.
- No application code is changed.
- The readiness review can be rerun with evidence-based conclusions.

## Stop Conditions

Stop and report if remediation would require:

- A third-party tenancy package.
- A change to database-per-tenant architecture.
- An undocumented exception to tenant isolation.
- Application implementation to resolve a documentation decision.

## Required Final Response

Provide:

- Files modified.
- Decisions clarified.
- Contradictions resolved.
- Validation performed.
- Remaining issues.
- Result of the rerun Phase 3 readiness review.
