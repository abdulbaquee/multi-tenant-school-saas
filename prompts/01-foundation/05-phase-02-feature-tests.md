# Phase 2 Feature Tests

## Execution Mode

Implementation. Use this prompt only when the user explicitly approves Phase 2 test implementation.

## Objective

Add or update the required Phase 2 tests for authentication, user management, authorization, and security behavior.

## Authoritative Documents

Read and follow:

- `AGENTS.md`
- `tests/AGENTS.md`
- `docs/TESTING_STRATEGY.md`
- `docs/SECURITY_GUIDELINES.md`
- `docs/MODULE_SPECIFICATIONS.md`
- `docs/TENANCY_DESIGN.md`

## Scope

Allowed:

- Authentication feature tests.
- User management feature tests.
- Authorization tests.
- Validation tests.
- Security behavior tests for Phase 2.
- Factory or seeder updates needed for tests.

## Out Of Scope

Do not test unimplemented modules as if they exist.

Do not implement application features inside tests.

Do not add tenant isolation tests for modules that do not exist yet, except for Phase 2 user ownership behavior that is already implemented.

## Required Test Coverage

Cover:

- Guest cannot access authenticated dashboard.
- Authenticated user can access dashboard.
- Login works with valid credentials.
- Login fails with invalid credentials.
- Logout works.
- Profile access is protected.
- User management allowed paths.
- User management denied paths.
- User validation failures.
- Role assignment restrictions.
- Soft delete or deactivation behavior where implemented.

## Constraints

- Use PHPUnit and Laravel Feature Testing.
- Tests must be clear enough for MCA evidence.
- Use demo-safe factories and seeders.
- Do not seed real student or minor data.
- Tenant isolation failures are critical.

## Required Workflow

1. Inspect existing tests.
2. Add focused Phase 2 tests.
3. Run the relevant test command.
4. Fix only Phase 2 failures caused by scoped work.
5. Report test results.

## Deliverables

- Phase 2 feature tests.
- Authorization and validation tests.
- Test run summary.

## Acceptance Criteria

- Phase 2 behavior is covered by tests.
- Allowed and denied paths are both tested.
- Test data respects current schema and role model.
- Relevant tests pass or failures are clearly reported.
