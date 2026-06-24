# Phase 9 Release Gate Reviews

## Role

Act as a senior Laravel architect, security architect, tenant-isolation
specialist, testing specialist, documentation maintainer, and MCA project
reviewer for this repository.

## Objective

Run the Phase 9 Examination Management release gate by executing the canonical
governance reviews, remediating blocking or high-risk findings, rerunning
verification, and recording approval evidence without starting Phase 10 work.

## Execution Mode

Review and targeted remediation. Modify only files required to close blocking or
high-risk release-gate findings.

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
- `prompts/09-examination/05-phase-09-report-cards.md`

## Prerequisites

Verify before review:

- Phase 9 setup, assignment, marks-entry, result-processing, grade-scale, and
  operational report-card workflows are implemented.
- Focused Examination feature suites and the full application suite pass.
- DECISION-035 remains the active Examination Management boundary.

## Required Workflow

1. Run tenant isolation review on Phase 9 Examination workflows.
2. Run security review on marks entry, result processing, and report-card
   view/print workflows.
3. Run documentation review for roadmap, module, screen-flow, testing, security,
   changelog, prompt inventory, and MCA evidence consistency.
4. Run code review on Phase 9 services, policies, controllers, requests, views,
   routes, and tests.
5. Remediate any blocking or high-risk finding with the smallest correct diff.
6. Rerun focused Examination suites, full suite, Pint, Composer validation, route
   inspection, and `git diff --check`.
7. Rerun failed review areas until approved or explicitly documented as
   residual demo-only risk.
8. Run release review and record governance evidence.
9. Update roadmap, governance, changelog, testing, security, MCA evidence, and
   prompt inventory.

## Acceptance Criteria

- No critical tenant isolation, authorization, or academic-history defect
  remains open.
- Teacher access stays limited to assigned class/subject Examination workflows
  and does not expose unassigned subject marks.
- Super Admin and Accountant have no Phase 9 Examination route or direct
  service pathway.
- Result and report-card history remains retained and privacy-safe.
- Governance scorecards are recorded in `docs/PROJECT_GOVERNANCE.md`.
- Phase 9 status moves to release-approved only after release review passes.
- Phase 10 remains untouched.

## Stop Conditions

Stop and report instead of guessing if:

- A release-gate finding requires a schema change not approved in
  `DATABASE_DESIGN.md`.
- Tenant isolation cannot be preserved without weakening `BelongsToTenant`.
- Phase 10 reporting, export, analytics, dashboard, activity-log, audit-log, or
  backup work becomes necessary to close the gate.
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
