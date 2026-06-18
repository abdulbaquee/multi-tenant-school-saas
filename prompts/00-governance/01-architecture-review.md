# Architecture Review

## Execution Mode

Audit only. Do not modify files.

## Objective

Review the repository, documentation, or proposed changes for architecture readiness and architecture drift.

## Authoritative Documents

Read and follow:

- `AGENTS.md`
- `app/AGENTS.md`
- `database/AGENTS.md`
- `resources/AGENTS.md`
- `docs/PROJECT_CONSTITUTION.md`
- `docs/SYSTEM_ARCHITECTURE.md`
- `docs/TENANCY_DESIGN.md`
- `docs/DEVELOPMENT_ROADMAP.md`
- `docs/DECISIONS_LOG.md`
- `docs/MODULE_SPECIFICATIONS.md`

## Review Criteria

Verify:

- Layered monolithic architecture.
- Native Laravel Multi-Tenancy.
- `school_id` tenant identifier.
- Thin controllers.
- Service layer for workflows.
- Form Requests for validation.
- Policies, Gates, and Middleware for authorization.
- Eloquent relationships and scopes.
- Blade and Bootstrap UI stack.
- No unapproved architecture patterns.

## Forbidden Drift

Flag:

- Stancl Tenancy or Spatie Multitenancy.
- Microservices, CQRS, event sourcing, service mesh, or Kubernetes complexity.
- React, Vue, Inertia, Livewire, Tailwind CSS, or Alpine.js.
- Repository layer unless explicitly approved later.
- Business logic in controllers.
- Manual tenant filtering as the primary isolation mechanism.

## Required Output

Provide:

- Architecture Score: 1-10.
- Findings ordered by severity.
- File and line references where possible.
- Conflicts with authoritative docs.
- Required remediation before implementation continues.
- Recommendation: Approve, Approve With Minor Changes, Requires Remediation, or Reject.
