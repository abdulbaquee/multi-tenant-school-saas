# Phase 7 Tenant Isolation Test Remediation

## Role

Act as a senior Laravel architect, multi-tenant SaaS security architect, testing
specialist, documentation maintainer, and MCA project reviewer.

## Objective

Close the remaining Phase 7 Attendance tenant-isolation evidence gaps identified
by the governance review without changing approved production behavior.

## Execution Mode

Implementation. Modify focused Attendance tests and synchronized verification
evidence only. Change production code only if a new denied-path test proves an
actual tenant-isolation defect.

## Authoritative Documents

Read and follow:

- `AGENTS.md`
- `app/AGENTS.md`
- `tests/AGENTS.md`
- `docs/AGENTS.md`
- `docs/PROJECT_CONSTITUTION.md`
- `docs/TENANCY_DESIGN.md`
- `docs/DATABASE_DESIGN.md`
- `docs/MODULE_SPECIFICATIONS.md`
- `docs/SECURITY_GUIDELINES.md`
- `docs/TESTING_STRATEGY.md`
- `prompts/00-governance/03-tenant-isolation-review.md`
- `prompts/06-attendance/03-phase-07-daily-attendance-management.md`

## Scope

Add focused denied-path tests proving:

- School A cannot submit a complete roster using School B's Section or Students.
- School A cannot route-bind, open, or PATCH a School B Attendance record.
- School A's unfiltered Attendance history excludes School B records.
- School A cannot select School B's Section in the monthly summary.
- Denied attempts create no Attendance, activity, or audit mutation in School A
  and do not alter retained School B records.
- Existing global scope, Policy, Form Request, and Service protections remain
  the enforcement mechanism.

Update test and changelog evidence with exact verified totals after the complete
suite passes.

## Out Of Scope

Do not:

- Add or change Attendance routes, screens, permissions, schema, or workflows.
- Introduce manual tenant filtering as the primary protection.
- Add report, export, delete, analytics, dashboard, Super Admin, or Accountant
  Attendance capabilities.
- Install packages or modify `composer.json`.
- Change another module merely to increase assertion counts.

## Constraints

- Use the existing `RefreshDatabase` in-memory testing pattern.
- Build both schools through tenant-aware test helpers.
- Create School B Attendance only through the authorized School B workflow.
- Attempt every denied route as an authenticated School A user.
- Assert concealment with `404` where tenant-scoped route or Section resolution
  applies.
- Assert retained School B values and evidence counts after denied requests.
- Never disable `TenantScope` in production code.

## Deliverables

- Focused cross-school Attendance HTTP denied-path coverage.
- Explicit unfiltered history and monthly-summary isolation coverage.
- Updated verification evidence.
- A rerun of the canonical tenant-isolation review.

## Required Workflow

1. Reproduce the audit's missing paths in focused Feature tests.
2. Run the focused Attendance workflow test.
3. Run the combined Attendance schema and workflow tests.
4. Run the complete application suite.
5. Run Pint and `git diff --check`.
6. Update exact verification evidence.
7. Rerun `prompts/00-governance/03-tenant-isolation-review.md`.

## Acceptance Criteria

- Every new cross-school request is concealed or denied before mutation.
- School A never sees School B Attendance through history or summary filters.
- School B Attendance and audit evidence remain unchanged after School A
  attempts access.
- No production implementation change is necessary unless a test demonstrates
  a real defect.
- Focused, combined, and complete suites pass.
- The tenant-isolation review reports no missing Attendance denied-path
  coverage.

## Stop Conditions

Stop and report instead of weakening controls if:

- A foreign Attendance record becomes visible or mutable.
- Satisfying the test requires bypassing TenantScope.
- Documentation conflicts about Phase 7 role or tenant boundaries.
- Remediation requires a new schema element, package, or Phase 10 capability.

## Required Final Response

Provide:

- Files modified.
- Denied paths added.
- Focused and full verification totals.
- Tenant-isolation review score and decision.
- Remaining risks or manual checks.
