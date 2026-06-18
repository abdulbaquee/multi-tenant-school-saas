# tests/AGENTS.md

Instructions for tests under `tests/`.

## Sources Of Truth

- Testing strategy: `../docs/TESTING_STRATEGY.md`
- Security requirements: `../docs/SECURITY_GUIDELINES.md`
- Tenancy requirements: `../docs/TENANCY_DESIGN.md`
- Module behavior: `../docs/MODULE_SPECIFICATIONS.md`

## Required Test Types

- Feature tests for user-facing workflows.
- Authorization tests for protected actions.
- Tenant isolation tests for every tenant-owned module.
- Integration tests for workflows crossing services, models, reports, files, and logs.
- Security tests for authentication, authorization, CSRF, validation, exports, and file access.

## Tenant Isolation Tests

Tenant isolation tests are mandatory and highest priority.

Every tenant-owned module must prove:

- School A users cannot read School B records.
- School A users cannot update or delete School B records.
- Reports, search, exports, and file access do not leak cross-tenant data.
- Creating tenant-owned records assigns the acting user's `school_id`.
- Super Admin access is platform-wide only through authorized paths.

A tenant isolation failure is a critical defect.

## Test Design

- Prefer Laravel Feature tests for request-level behavior.
- Use factories and seeders that respect tenant ownership.
- Test roles from the canonical permission matrix.
- Assert both allowed and denied paths.
- Keep tests clear enough to support MCA report evidence.

## Evidence And Maintenance

- Add or update tests with every implementation change.
- Update `docs/TESTING_STRATEGY.md` when testing scope or acceptance criteria changes.
- Keep test names descriptive and tied to user-visible behavior or security guarantees.
