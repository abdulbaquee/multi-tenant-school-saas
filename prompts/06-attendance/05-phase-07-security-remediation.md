# Phase 7 Security Remediation

## Role

Act as a senior Laravel security architect, multi-tenant SaaS architect,
testing specialist, documentation maintainer, and MCA project reviewer.

## Objective

Close the Phase 7 Attendance security-review finding that allowed a Teacher or
single-record correction to replace a School Admin-created Holiday status.

## Execution Mode

Implementation. Modify only the focused Attendance authorization, service,
Blade presentation, denied-path tests, prompt inventory, and synchronized
security/testing/status documentation required by this remediation.

## Authoritative Documents

Read and follow:

- `AGENTS.md`
- `app/AGENTS.md`
- `tests/AGENTS.md`
- `resources/AGENTS.md`
- `docs/AGENTS.md`
- `docs/PROJECT_CONSTITUTION.md`
- `docs/MODULE_SPECIFICATIONS.md`
- `docs/SECURITY_GUIDELINES.md`
- `docs/TESTING_STRATEGY.md`
- `docs/TENANCY_DESIGN.md`
- `docs/DECISIONS_LOG.md`
- `prompts/00-governance/02-security-review.md`
- `prompts/06-attendance/03-phase-07-daily-attendance-management.md`

## Scope

Implement and verify:

- Only School Admin may transition Attendance to or from Holiday.
- Every Holiday transition uses the existing complete-roster workflow.
- Teachers cannot correct, overwrite, or remove an existing Holiday status.
- Single-record correction cannot transition to or from Holiday.
- School Admin may replace a Holiday roster through one authorized, atomic,
  complete-roster save.
- Denied requests create no Attendance, activity, or audit mutation.
- Governing security, module, testing, decision, changelog, and phase-status
  documentation states the same boundary.

## Out Of Scope

Do not:

- Add a calendar, report, export, delete, analytics, dashboard, or bulk-history
  module.
- Change the Attendance schema, tenant ownership, roles, or permission catalog.
- Add Super Admin or Accountant Attendance access.
- Install packages or modify `composer.json`.
- Change another module to satisfy this remediation.

## Constraints

- Keep Holiday as a School Admin-only whole-roster status.
- Preserve normal Teacher correction rights for directly assigned active
  Sections and non-Holiday Attendance.
- Enforce the rule server-side in the Service and Policy; UI restrictions are
  supplementary only.
- Keep complete-roster transitions atomic with Attendance, activity, and audit
  evidence in one transaction.
- Preserve immutable tenant, Student, placement, date, and original marker.
- Return authorization denial for Teacher Holiday access and validation denial
  for attempted single-record Holiday transitions by an otherwise authorized
  School Admin.

## Required Workflow

1. Reproduce the missing Holiday transition paths in Feature tests.
2. Harden Policy and Service authorization.
3. Align the correction form with the server rule.
4. Run the focused Attendance workflow suite.
5. Run the combined Attendance schema/workflow suite.
6. Run the complete application suite.
7. Run Pint, `git diff --check`, route inspection, and dependency audits.
8. Update exact verification evidence.
9. Rerun `prompts/00-governance/02-security-review.md`.

## Deliverables

- Hardened Holiday authorization boundary.
- Teacher and single-record denied-path regression tests.
- School Admin complete-roster Holiday replacement test.
- Synchronized documentation and verification evidence.
- Completed Phase 7 security-review rerun.

## Review Requirements

Verify:

- Teacher cannot POST a Holiday action.
- Teacher cannot PATCH or roster-overwrite an existing Holiday record.
- School Admin cannot transition one Holiday row through the correction route.
- School Admin can transition the complete Holiday roster through the roster
  workflow when authorized for create and update.
- Every denied request leaves Attendance and security evidence unchanged.
- Normal non-Holiday Teacher corrections remain functional.
- Tenant-isolation and retained-history controls remain unchanged.

## Acceptance Criteria

- No Teacher or single-record request can transition to or from Holiday.
- Only a School Admin complete-roster save can perform a Holiday transition.
- Focused, combined, and complete test suites pass.
- Formatting, route, and dependency checks pass.
- The rerun security review has no critical, high, or medium finding for
  implemented modules.

## Stop Conditions

Stop and report instead of weakening controls if:

- The remediation requires a new permission or schema field.
- Teacher Holiday access is required by conflicting authoritative
  documentation.
- Atomic roster replacement cannot preserve retained audit evidence.
- A third-party package or tenancy bypass would be required.

## Required Final Response

Provide:

- Files modified.
- Security boundary implemented.
- Denied paths added.
- Focused and full verification totals.
- Security-review score and decision.
- Remaining risks and the next Phase 7 review gate.
