# Phase 7 Documentation Remediation

## Role

Act as a senior Laravel documentation maintainer, multi-tenant SaaS reviewer,
security reviewer, testing-evidence reviewer, and MCA project reviewer.

## Objective

Remediate only the consistency findings from the Phase 7 Attendance
documentation review, rerun the canonical documentation review, and synchronize
the approved review-gate status without changing application behavior.

## Execution Mode

Documentation only. Modify documentation and prompt files only. Do not modify
application code, routes, views, tests, configuration, migrations, dependencies,
or runtime data.

## Authoritative Documents

Read and follow:

- `AGENTS.md`
- `docs/AGENTS.md`
- `docs/PROJECT_CONSTITUTION.md`
- `docs/MCA_SUBMISSION_MASTER_PLAN.md`
- `docs/PROJECT_GOVERNANCE.md`
- `docs/DECISIONS_LOG.md`
- `docs/DEVELOPMENT_ROADMAP.md`
- `docs/SYSTEM_ARCHITECTURE.md`
- `docs/TENANCY_DESIGN.md`
- `docs/DATABASE_DESIGN.md`
- `docs/ER_DIAGRAM.md`
- `docs/MODULE_SPECIFICATIONS.md`
- `docs/SCREEN_FLOW.md`
- `docs/CODING_STANDARDS.md`
- `docs/SECURITY_GUIDELINES.md`
- `docs/TESTING_STRATEGY.md`
- `docs/UI_UX_DESIGN_SYSTEM.md`
- `docs/MCA_REPORT_NOTES.md`
- `docs/CHANGELOG.md`
- `prompts/README.md`
- `prompts/templates/standard-prompt-template.md`
- `prompts/00-governance/04-documentation-review.md`

## Findings To Remediate

- Replace the stale `SCREEN_FLOW.md` statement that says Phase 7 navigation is
  omitted with the implemented School Admin and assigned-Section Teacher
  Attendance navigation.
- Replace broad "assigned classes" Attendance wording with the canonical direct
  active Section assignment through `sections.teacher_id`.
- Convert premature diagram and final-submission completion marks in
  `MCA_REPORT_NOTES.md` into truthful completed/pending checkboxes.
- Replace the stale `StoreAttendanceRequest` example with the implemented
  `AttendanceStoreRequest` name.
- Add `MCA_SUBMISSION_MASTER_PLAN.md` to the prompt README, standard template,
  and governance review prompts as required by DECISION-033.
- Record this remediation in the prompt inventory and changelog.

## Constraints

- Preserve the approved Phase 7 role, permission, tenant, Holiday, retention,
  privacy, schema, and testing contracts.
- Do not change the verified test totals: focused workflow 13 tests and 150
  assertions; combined Attendance 19 tests and 217 assertions; focused
  security/Attendance 25 tests and 243 assertions; full suite 276 tests and
  2,216 assertions.
- Do not mark the documentation review approved until the audit rerun finds no
  remaining contradiction, stale reference, or missing update.
- Do not mark screenshots, report, presentation, PDF, references, deployment,
  or viva artifacts complete unless repository evidence proves completion.
- Do not introduce Phase 8 or later implementation claims.

## Required Workflow

1. Apply only the listed documentation and prompt consistency fixes.
2. Search authoritative documents and prompt governance for stale Phase 7,
   assigned-Class, request-name, master-plan, and completion-checklist language.
3. Run `prompts/00-governance/04-documentation-review.md` in audit-only mode.
4. If findings remain, remediate them within this prompt's scope and rerun the
   review.
5. After approval, synchronize Phase 7 status and next-task language across the
   Constitution-governed status documents and MCA evidence.
6. Run `git diff --check` and inspect the final documentation-only diff.

## Acceptance Criteria

- The documentation review scores 10/10 and recommends Approve.
- Screen flow and menu status match implemented Phase 7 routes and navigation.
- Teacher Attendance scope consistently means directly assigned active Section,
  not a related or broadly assigned Class.
- MCA evidence checklists distinguish completed artifacts from pending work.
- Every reusable governance prompt and future-prompt template consults the MCA
  submission master plan.
- Phase status and next task consistently advance to the Phase 7 code review
  after documentation approval.
- No application or test file is modified by this remediation.

## Required Final Response

Provide:

- Files modified.
- Documentation findings remediated.
- Documentation review score and recommendation.
- Final Phase 7 status.
- Validation performed.
- Exact next task.
