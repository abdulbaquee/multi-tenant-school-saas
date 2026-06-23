# Standard Prompt Template

Use this template for every new prompt added to `prompts/`.

## Role

Act as a senior Laravel architect, security architect, database architect, testing specialist, documentation maintainer, and MCA project reviewer for this repository.

## Objective

State the exact task this prompt is allowed to perform.

The objective must be narrow enough to complete in one focused Codex run.

## Execution Mode

Choose one:

- Audit only: do not modify files.
- Documentation only: modify documentation or prompt files only.
- Implementation: modify application files only within the stated scope.

## Authoritative Documents

Read and follow:

- `AGENTS.md`
- The nearest nested `AGENTS.md` for affected files
- Relevant documents in `docs/`

Required domain references:

- MCA submission: `docs/MCA_SUBMISSION_MASTER_PLAN.md`
- Architecture: `docs/SYSTEM_ARCHITECTURE.md`
- Tenancy: `docs/TENANCY_DESIGN.md`
- Database: `docs/DATABASE_DESIGN.md`
- Modules and permissions: `docs/MODULE_SPECIFICATIONS.md`
- Screen flow: `docs/SCREEN_FLOW.md`
- Security: `docs/SECURITY_GUIDELINES.md`
- Testing: `docs/TESTING_STRATEGY.md`
- UI: `docs/UI_UX_DESIGN_SYSTEM.md`
- Decisions: `docs/DECISIONS_LOG.md`
- Roadmap: `docs/DEVELOPMENT_ROADMAP.md`

## Scope

Allowed work:

- List exact files, modules, or artifact types allowed.

## Out Of Scope

Do not:

- List forbidden files, modules, frameworks, package changes, or phase work.

## Constraints

- Use Laravel 13, PHP 8.4, and MySQL 8.
- Use Blade Templates, Bootstrap 5, Bootstrap Icons, and Chart.js.
- Use Native Laravel Multi-Tenancy with `school_id`.
- Keep controllers thin.
- Use services for business workflows.
- Use Form Requests for validation.
- Use Policies, Gates, and Middleware for authorization.
- Use Eloquent relationships and documented indexes/constraints.
- Use `restrictOnDelete()` by default.
- Preserve audit, payment, attendance, exam, report, and backup history.
- Do not install packages or modify `composer.json` unless the user explicitly asks for that implementation task.

## Required Workflow

1. Inspect the repository and relevant documentation.
2. Identify existing patterns.
3. Confirm the task scope against the roadmap.
4. Perform only the allowed work.
5. Add or update tests when implementation changes behavior.
6. Update documentation when required.
7. Report modified files and verification results.

## Deliverables

- List expected outputs.

## Review Requirements

Verify:

- Architecture compliance.
- Tenant isolation.
- Authorization.
- Validation.
- Database alignment.
- UI stack compliance.
- Testing coverage.
- Documentation consistency.
- No unapproved packages or frameworks.

## Acceptance Criteria

The prompt is successful when:

- All scoped deliverables are complete.
- No out-of-scope work was performed.
- No governance contradiction was introduced.
- Relevant tests or validation commands were run, or a clear reason is reported.
- Documentation is updated when the change requires it.

## Stop Conditions

Stop and report instead of guessing if:

- Required documentation conflicts.
- The database design is insufficient for the requested work.
- The task requires an unapproved package or architecture change.
- Tenant isolation cannot be enforced safely.
- The requested scope belongs to a later roadmap phase.

## Required Final Response

Provide:

- Files modified.
- Changes made.
- Tests or validation run.
- Documentation updates.
- Risks or remaining issues.
