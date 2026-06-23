# Code Review

## Execution Mode

Audit only. Do not modify files.

## Objective

Review implementation changes for bugs, regressions, architecture violations, security risks, missing tests, and documentation gaps.

## Authoritative Documents

Read and follow:

- `AGENTS.md`
- The nearest nested `AGENTS.md` for changed files
- `docs/MCA_SUBMISSION_MASTER_PLAN.md`
- `docs/CODING_STANDARDS.md`
- `docs/SYSTEM_ARCHITECTURE.md`
- `docs/TENANCY_DESIGN.md`
- `docs/DATABASE_DESIGN.md`
- `docs/MODULE_SPECIFICATIONS.md`
- `docs/SECURITY_GUIDELINES.md`
- `docs/TESTING_STRATEGY.md`

## Review Priorities

Lead with findings ordered by severity:

1. Security defects.
2. Tenant isolation defects.
3. Data integrity defects.
4. Authorization defects.
5. Validation defects.
6. Missing tests.
7. Documentation gaps.
8. Maintainability issues.

## Required Checks

Verify:

- Controllers are thin.
- Services hold workflows.
- Form Requests hold validation.
- Policies authorize protected actions.
- Tenant-owned models are scoped correctly.
- Queries avoid N+1 problems.
- Deletes follow the retention policy.
- Sensitive files are not publicly exposed.
- Tests cover allowed and denied paths.
- Documentation was updated when behavior changed.

## Required Output

Provide:

- Findings first, ordered by severity.
- File and line references.
- Open questions or assumptions.
- Test gaps.
- Residual risks.
- Brief change summary only after findings.

If no issues are found, say so clearly and list any residual risk.
