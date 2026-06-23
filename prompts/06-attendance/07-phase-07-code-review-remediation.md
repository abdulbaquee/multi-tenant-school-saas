# Phase 7 Code Review Remediation

## Role

Act as a senior Laravel architect, multi-tenant SaaS architect, security
reviewer, database-integrity reviewer, performance reviewer, testing specialist,
and MCA project reviewer.

## Objective

Remediate the Phase 7 Attendance code-review findings without expanding the
approved module scope, then rerun the canonical code review.

## Execution Mode

Implementation. Modify only the focused Attendance Service, Policy, tests, and
synchronized prompt/documentation evidence required by these findings.

## Authoritative Documents

Read and follow:

- `AGENTS.md`
- `app/AGENTS.md`
- `tests/AGENTS.md`
- `docs/AGENTS.md`
- `docs/PROJECT_CONSTITUTION.md`
- `docs/MCA_SUBMISSION_MASTER_PLAN.md`
- `docs/DECISIONS_LOG.md`
- `docs/DEVELOPMENT_ROADMAP.md`
- `docs/SYSTEM_ARCHITECTURE.md`
- `docs/TENANCY_DESIGN.md`
- `docs/DATABASE_DESIGN.md`
- `docs/MODULE_SPECIFICATIONS.md`
- `docs/CODING_STANDARDS.md`
- `docs/SECURITY_GUIDELINES.md`
- `docs/TESTING_STRATEGY.md`
- `prompts/00-governance/05-code-review.md`
- `prompts/06-attendance/03-phase-07-daily-attendance-management.md`
- `prompts/06-attendance/05-phase-07-security-remediation.md`

## Findings To Remediate

- Complete-roster correction must include retained same-date Attendance rows
  whose Student or Enrollment became lifecycle-ineligible after creation, so a
  Holiday transition cannot become partial or permanently stranded.
- Direct `AttendanceService::correct()` calls must enforce the same status,
  string, and 500-character remark contract as the HTTP Form Request.
- Attendance History must not execute authorization relationship and permission
  queries once per displayed row.
- Add regression coverage for lifecycle-changed Holiday rows, direct-service
  validation, bounded History queries, and create/update/view permission
  permutations.

## Constraints

- Preserve TenantContext, `BelongsToTenant`, Policies, Form Requests, immutable
  Attendance identity, original marker, privacy-safe logging, and transaction
  rollback behavior.
- New Attendance rows still require active Students and active matching
  Enrollments. Only already-retained rows may rejoin the correction roster after
  a later lifecycle change.
- Holiday transitions remain School Admin-only, atomic, and complete-roster.
- Teachers retain ordinary non-Holiday correction rights only for directly
  assigned active Sections.
- Do not add routes, schema, permissions, packages, reports, exports, deletion,
  analytics, dashboards, or Phase 8 work.

## Required Workflow

1. Reproduce each finding in focused tests.
2. Merge active eligible Enrollments with resolvable retained same-date
   Attendance placement for correction, while preventing new ineligible rows.
3. Revalidate direct correction payloads before persistence.
4. Eager-load and reuse authorization relationships for History row policies.
5. Run focused Attendance, combined Attendance, security/Attendance, and full
   suites.
6. Run Pint, route inspection, and `git diff --check`.
7. Synchronize exact evidence and rerun the code review.

## Acceptance Criteria

- Lifecycle-changed retained Holiday rows transition with the complete original
  same-date roster and cannot be omitted.
- Direct oversized or non-string correction remarks fail validation without
  Attendance or security-evidence mutation.
- Attendance History query count remains bounded as page rows increase.
- Create-only, update-only, and revoked-view behavior is explicitly tested.
- All test and validation commands pass.
- The code-review rerun reports no remaining finding.

## Required Final Response

Provide:

- Files modified.
- Remediation implemented.
- Regression tests added.
- Exact verification totals.
- Code-review rerun result.
- Final Phase 7 status and exact next task.
