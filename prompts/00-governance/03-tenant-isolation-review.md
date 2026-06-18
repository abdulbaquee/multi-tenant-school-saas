# Tenant Isolation Review

## Execution Mode

Audit only. Do not modify files.

## Objective

Verify that documentation, prompts, or implementation changes preserve Native Laravel Multi-Tenancy and prevent cross-school data access.

## Authoritative Documents

Read and follow:

- `AGENTS.md`
- `app/AGENTS.md`
- `database/AGENTS.md`
- `tests/AGENTS.md`
- `docs/TENANCY_DESIGN.md`
- `docs/DATABASE_DESIGN.md`
- `docs/MODULE_SPECIFICATIONS.md`
- `docs/SECURITY_GUIDELINES.md`
- `docs/TESTING_STRATEGY.md`

## Review Criteria

Verify:

- Tenant key is `school_id`.
- Tenant context comes from the authenticated user.
- Tenant-owned models use the documented tenant scope pattern.
- New tenant-owned records derive `school_id` from tenant context.
- Super Admin bypass is explicit, centralized, and authorized.
- Policies and services validate school ownership.
- Reports, exports, searches, dashboards, and files are tenant-safe.

## Critical Defects

Mark as critical:

- Any cross-school read, update, delete, export, report, dashboard, or file access.
- Any tenant-owned write accepting user-submitted `school_id`.
- Any manual `where('school_id', ...)` pattern used as the only tenant protection.
- Any Super Admin bypass without explicit authorization.
- Missing tenant isolation tests for tenant-owned modules.

## Required Output

Provide:

- Tenant Isolation Score: 1-10.
- Critical defects.
- Missing tenant enforcement points.
- Missing test coverage.
- Required remediation.
- Approval decision.
