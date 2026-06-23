# Phase 9 Teacher-Scoped Marks Entry And Result Processing

## Role

Act as a senior Laravel architect, security architect, database architect,
academic-workflow analyst, testing specialist, documentation maintainer, and
MCA project reviewer for this repository.

## Objective

Implement the approved Phase 9 teacher-scoped marks entry, grade calculation,
and School Admin result processing workflows without implementing operational
report cards, Examination reports, exports, analytics, or platform summaries.

## Execution Mode

Implementation. Modify only the application, tests, views, routes, and
documentation needed for marks entry and result processing.

## Authoritative Documents

Read and follow:

- `AGENTS.md`
- `app/AGENTS.md`
- `database/AGENTS.md`
- `tests/AGENTS.md`
- `resources/AGENTS.md`
- `docs/AGENTS.md`
- `docs/PROJECT_CONSTITUTION.md`
- `docs/MCA_SUBMISSION_MASTER_PLAN.md`
- `docs/PROJECT_GOVERNANCE.md`
- `docs/DECISIONS_LOG.md`
- `docs/DEVELOPMENT_ROADMAP.md`
- `docs/SYSTEM_ARCHITECTURE.md`
- `docs/TENANCY_DESIGN.md`
- `docs/DATABASE_DESIGN.md`
- `docs/MODULE_SPECIFICATIONS.md`
- `docs/SCREEN_FLOW.md`
- `docs/SECURITY_GUIDELINES.md`
- `docs/TESTING_STRATEGY.md`
- `docs/UI_UX_DESIGN_SYSTEM.md`
- `docs/CODING_STANDARDS.md`

## Prerequisites

Verify before implementation:

- Phase 9 design remediation (DECISION-035) is approved.
- Core Examination schema and School Admin exam setup/assignment workflows are
  implemented.
- Focused setup/assignment tests and the full suite pass before marks-entry
  changes.

## Scope

Implement:

- Marks entry workspace for ongoing Exam Subjects only.
- Complete eligible Student roster save with server-derived pass/fail/absent
  status, grade-scale resolution, immutable `entered_by`, and correction audit
  evidence.
- Teacher scope through `subjects.teacher_id`, matching Exam Subject class, and
  eligible active Enrollment for the Exam Academic Year.
- School Admin full marks entry within the tenant.
- Result review list/show screens scoped to School Admin and assigned Teachers.
- School Admin-only result processing that recalculates grades for retained
  Exam Results.
- Policies, Form Requests, Services, controllers, routes, Blade views,
  navigation updates, and tests required for these workflows.
- Documentation evidence updates after verification.

## Out Of Scope

Do not implement:

- Operational report-card generation/view/print.
- Examination reports, exports, analytics, dashboard widgets, or platform
  summaries.
- Super Admin or Accountant Examination routes.
- Exam Result or Report Card deletion.

## Required Behaviors

- Marks entry is allowed only when the Exam status is `ongoing`.
- Submit exactly one row for every eligible active Enrollment in the roster.
- Reject forged tenant, identity, grade, status, and audit fields from user
  input.
- Preserve retained Exam Result history; corrections update marks/status/grade
  and remarks only.
- Log privacy-safe activity and audit evidence for create, correct, and process
  actions.

## Verification

Run and record:

- Focused marks-entry feature tests.
- Full application test suite.
- Laravel Pint on dirty files.

## Deliverables

- `ExamResultPolicy`, `ExamResultService`, and `GradeScaleService` calculation
  helpers.
- Marks entry and result processing controllers, requests, routes, and views.
- `tests/Feature/ExamMarksEntryTest.php`.
- Updated governance and module documentation evidence.

## Next Checkpoint

Implement Phase 9 operational report-card view and print workflows.
