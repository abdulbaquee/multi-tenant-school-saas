# Security Review

## Execution Mode

Audit only. Do not modify files.

## Objective

Review documentation, prompts, or implementation changes for security readiness.

## Authoritative Documents

Read and follow:

- `AGENTS.md`
- `app/AGENTS.md`
- `database/AGENTS.md`
- `resources/AGENTS.md`
- `docs/PROJECT_CONSTITUTION.md`
- `docs/SECURITY_GUIDELINES.md`
- `docs/TENANCY_DESIGN.md`
- `docs/MODULE_SPECIFICATIONS.md`
- `docs/DATABASE_DESIGN.md`
- `docs/TESTING_STRATEGY.md`

## Review Criteria

Verify:

- Authentication.
- Authorization.
- CSRF protection.
- Server-side validation.
- Session security.
- Password hashing.
- Tenant isolation.
- File storage privacy.
- Student data privacy.
- Audit logging.
- Deletion and retention policy.
- Export authorization.
- Report authorization.

## Required Security Checks

Confirm:

- Student photos use private storage.
- School logos may use public storage.
- Backups are private.
- Sensitive minor data is least-privilege.
- UI visibility is not treated as authorization.
- `restrictOnDelete()` is the default foreign key behavior.
- Audit, activity, payment, attendance, exam, report, and backup history is preserved.
- Cross-tenant exposure is treated as critical.

## Required Output

Provide:

- Security Score: 1-10.
- Critical findings.
- High-risk findings.
- Medium-risk findings.
- Missing tests.
- Missing documentation.
- Approval decision: Approve, Approve With Minor Changes, Requires Remediation, or Reject.
