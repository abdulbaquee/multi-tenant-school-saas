# Phase 9 Operational Report Cards

## Role

Act as a senior Laravel architect, security architect, database architect,
academic-workflow analyst, testing specialist, documentation maintainer, and
MCA project reviewer for this repository.

## Objective

Implement the approved Phase 9 operational report-card generation, view, and
browser-print workflows without implementing Examination reports, exports,
analytics, PDF/Excel infrastructure, or platform summaries.

## Execution Mode

Implementation. Modify only the application, tests, views, routes, and
documentation needed for operational report cards.

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
- `prompts/09-examination/04-phase-09-marks-entry-results.md`

## Prerequisites

Verify before implementation:

- Phase 9 marks entry and result processing workflows are implemented.
- Focused marks-entry tests and the full suite pass before report-card changes.
- DECISION-035 remains the active Examination boundary.

## Scope

Implement:

- School Admin report-card generation for ongoing or completed exams with
  complete per-student exam results.
- Server-derived totals, percentage, overall grade, and pass/fail status.
- Immutable generation scope (`exam_id`, `student_id`, placement fields,
  `generated_by`) with regeneration updating summary values only.
- Report card list, detail, and browser-print views.
- Teacher view/print access for assigned class/subject students only.
- Policies, Form Requests, Services, controllers, routes, Blade views,
  navigation updates, activity logs, audit logs, and tests.
- Documentation evidence updates after verification.

## Out Of Scope

Do not implement:

- Examination reports, exports, analytics, dashboard widgets, or platform
  summaries.
- PDF/Excel export infrastructure.
- Super Admin or Accountant Examination routes.
- Report Card or Exam Result deletion.

## Required Behaviors

- Generate only when every exam subject for the student's class has a retained
  exam result.
- Overall pass requires all subject results to be pass; any fail or absent
  subject makes the report card fail.
- Reject forged tenant, identity, and audit fields from user input.
- Log privacy-safe activity and audit evidence for generate and regenerate
  actions.
- Phase 9 print is browser print only.

## Verification

Run and record:

- Focused report-card feature tests.
- Full application test suite.
- Laravel Pint on dirty files.

## Deliverables

- `ReportCardPolicy`, `ReportCardService`, controllers, requests, routes, and
  views.
- `tests/Feature/ReportCardTest.php`.
- Updated governance and module documentation evidence.

## Next Checkpoint

Run the Phase 9 release gate reviews and submission evidence updates.
