# Phase 8 Release Gate Reviews

## Role

Act as a senior Laravel architect, security architect, tenant-isolation specialist,
testing specialist, documentation maintainer, and MCA project reviewer for this
repository.

## Objective

Run the Phase 8 Fee Management release gate by executing the canonical
governance reviews, remediating any blocking findings, rerunning verification, and
recording approval evidence without starting Phase 9 work.

## Execution Mode

Review and targeted remediation. Modify only files required to close blocking or
high-risk release gate findings.

## Authoritative Documents

Read and follow:

- `AGENTS.md`
- `docs/PROJECT_CONSTITUTION.md`
- `docs/MCA_SUBMISSION_MASTER_PLAN.md`
- `docs/PROJECT_GOVERNANCE.md`
- `docs/DECISIONS_LOG.md`
- `docs/DEVELOPMENT_ROADMAP.md`
- `docs/TENANCY_DESIGN.md`
- `docs/SECURITY_GUIDELINES.md`
- `docs/TESTING_STRATEGY.md`
- `docs/CHANGELOG.md`
- `prompts/00-governance/03-tenant-isolation-review.md`
- `prompts/00-governance/02-security-review.md`
- `prompts/00-governance/04-documentation-review.md`
- `prompts/00-governance/05-code-review.md`
- `prompts/00-governance/06-release-review.md`
- `prompts/07-fee-management/06-phase-08-sandbox-transaction-screens.md`

## Prerequisites

Verify before review:

- Phase 8 setup, assignment, collection, receipt, payment-history, outstanding-
  balance, and sandbox transaction workflows are implemented.
- Focused Fee feature suites and the full application suite pass.
- DECISION-034 remains the active Fee Management boundary.

## Required Workflow

1. Run tenant isolation review on Phase 8 Fee workflows.
2. Run security review on collection, receipt, history, outstanding, and sandbox
   screens.
3. Run documentation review for roadmap, module, screen-flow, testing, security,
   changelog, and MCA evidence consistency.
4. Run code review on Phase 8 services, policies, controllers, requests, views,
   routes, and tests.
5. Remediate any blocking or high-risk finding with the smallest correct diff.
6. Rerun focused Fee suites, full suite, Pint, and `git diff --check`.
7. Rerun failed review areas until approved or explicitly documented as residual
   demo-only risk.
8. Run release review and record governance evidence.
9. Update roadmap, governance, changelog, testing, security, and prompt inventory.

## Acceptance Criteria

- No critical tenant isolation, authorization, or financial-history defect remains
  open.
- High-risk collection replay/idempotency gaps are remediated or explicitly
  documented as accepted demo-only residual risk with no data corruption path.
- Governance scorecards are recorded in `docs/PROJECT_GOVERNANCE.md`.
- Phase 8 status moves to release-approved only after release review passes.
- Phase 9 remains untouched.

## Stop Conditions

Stop and report instead of guessing if:

- A release gate finding requires a schema change not approved in
  `DATABASE_DESIGN.md`.
- Tenant isolation cannot be preserved without weakening `BelongsToTenant`.
- Phase 10 reporting or production gateway integration becomes necessary to close
  the gate.
- Existing tests fail before remediation begins.

## Required Final Response

Provide:

- Review scorecards with PASS/FAIL or approval decisions.
- Remediation performed.
- Tests and validation run.
- Documentation updates.
- Release gate verdict: READY, READY WITH MINOR IMPROVEMENTS, REQUIRES
  REMEDIATION, or NOT READY.
- Exact next task from the roadmap.
