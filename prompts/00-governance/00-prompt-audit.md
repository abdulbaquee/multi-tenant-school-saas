# Prompt Audit

## Execution Mode

Audit only. Do not modify files.

## Objective

Audit one or more prompts for compliance with the active AGENTS governance system and project documentation.

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
- `docs/MODULE_SPECIFICATIONS.md`
- `docs/SECURITY_GUIDELINES.md`
- `docs/TESTING_STRATEGY.md`
- `docs/UI_UX_DESIGN_SYSTEM.md`
- `docs/SCREEN_FLOW.md`

## Review Scope

Evaluate prompts for:

- AGENTS compliance.
- Architecture compliance.
- Technology accuracy.
- Phase alignment.
- Tenant isolation requirements.
- Security requirements.
- Testing requirements.
- Documentation requirements.
- Scope control.
- Codex suitability.

## Checks

Flag any prompt that:

- References Laravel 12 or PHP 8.3.
- Introduces unapproved packages or frameworks.
- Overrides canonical documentation.
- Combines unrelated roadmap phases.
- Asks for broad "generate everything" output.
- Omits tenant isolation checks.
- Omits authorization checks.
- Omits feature, security, or tenant isolation tests for implementation work.
- Includes stale settings, roadmap, or completed-action references.

## Required Output

For each prompt provide:

- Prompt ID.
- Current Status: Approved, Approved With Changes, Requires Rewrite, or Obsolete.
- Issues Found.
- Risk Level: Low, Medium, or High.
- Recommended Changes.
- Codex Compatibility Score: 0-10.
- Governance Compliance Score: 0-10.
- Architecture Compliance Score: 0-10.
- Maintainability Score: 0-10.
- Final Recommendation: Keep, Revise, Rewrite, or Archive.

## Final Summary

Provide:

- Common problems.
- Duplicate instructions.
- Outdated technology references.
- Missing governance references.
- Missing testing requirements.
- Missing documentation requirements.
- Missing tenant isolation requirements.
- Missing security requirements.
- Priority order for remediation.
