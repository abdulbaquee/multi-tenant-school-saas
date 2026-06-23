# Phase 9 Examination Management Design Remediation

## Role

Act as a senior Laravel architect, security architect, database architect,
academic-workflow analyst, RBAC reviewer, documentation maintainer, testing
specialist, and MCA project reviewer for this repository.

## Objective

Resolve the Phase 9 readiness blockers before generating Examination Management
migrations, models, controllers, services, views, or workflow tests.

## Execution Mode

Documentation and RBAC alignment only. Do not implement Examination Management
application workflows.

## Authoritative Documents

Read and follow:

- `AGENTS.md`
- `app/AGENTS.md`
- `tests/AGENTS.md`
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
- `docs/SECURITY_GUIDELINES.md`
- `docs/TESTING_STRATEGY.md`
- `docs/UI_UX_DESIGN_SYSTEM.md`
- `docs/CODING_STANDARDS.md`
- `docs/CHANGELOG.md`
- `prompts/09-examination/00-phase-09-readiness-review.md`

## Prerequisites

Verify before remediation:

- Phase 8 Fee Management is release-approved, committed, and passing tests.
- The Phase 9 readiness review decision is `REQUIRES REMEDIATION`.
- No Examination migrations, models, routes, or workflow tests exist yet.

## Scope

Allowed work:

- Add a Phase 9 Examination Management decision to `docs/DECISIONS_LOG.md`.
- Reconcile Examination scope in roadmap, module, database, screen-flow,
  tenancy, security, testing, MCA, governance, and changelog documentation.
- Clearly defer Examination reports, exports, analytics, dashboards, and platform
  summaries to Phase 10.
- Define Phase 9 operational screens: exam setup, exam-subject assignment, grade
  scales, teacher-scoped marks entry, result processing, and operational
  report-card view/print.
- Define School Admin, Teacher, Super Admin, Accountant, guest, and inactive
  context boundaries.
- Align `config/rbac.php` so Teacher does not receive exam setup, delete,
  publish, report, or export permissions by default.
- Define teacher assignment scope for marks entry using existing Teacher Profile
  and Academic Structure assignment patterns.
- Define grade-scale seeding, overlap rules, and calculation timing.
- Define exam/result/report-card retention, correction, and soft-delete
  boundaries.
- Add or update focused RBAC regression tests for the permission boundary.
- Add the remediation prompt to `prompts/README.md`.

## Out Of Scope

Do not:

- Create Examination migrations, models, policies, services, controllers,
  requests, views, routes, factories, seeders, or workflow tests.
- Add external assessment platforms, online proctoring, packages, webhooks,
  secrets, or network dependencies.
- Implement reports, exports, analytics, charts, dashboard widgets, platform
  summaries, parent/student portals, notifications, or PDF/Excel export
  infrastructure beyond documented operational report-card print/view.
- Change Student, Enrollment, Attendance, Fee Management, Reporting, Backup, or
  final-submission implementation outside documented Examination dependencies.

## Required Decisions To Record

Document that Phase 9 implements:

- School Admin exam setup and exam-subject assignment for active own-tenant
  schools.
- School Admin grade-scale management or seeding with school-local A+ through F
  default ranges unless documentation specifies a narrower MVP.
- School Admin and assigned Teacher marks entry for eligible enrolled Students
  within assigned class/subject scope only.
- School Admin result processing and operational report-card generation/view/print
  for own-tenant schools.
- Teacher access limited to assigned class/subject marks entry, assigned result
  review, and operational report-card view/print; no exam setup, publish,
  delete, report, or export routes.
- No Phase 9 Super Admin or Accountant Examination route or menu.
- Super Admin and platform Examination reporting deferred to Phase 10.
- Examination analytics reports, exports, dashboard widgets, and platform
  summaries remain Phase 10 work.
- Exam results and report cards are retained history; corrections require audit
  evidence and no hard deletion.
- Exams may soft-delete only before results exist; retained results and report
  cards must not be orphaned or misrepresented.
- Marks entry must validate max marks, passing marks, absent status, duplicate
  rows, enrolled-student eligibility, and tenant ownership through services,
  policies, Form Requests, and tests.
- Examination workflows must be tenant-derived, service-validated, privacy-safe,
  transaction-safe, and test-covered.

## Acceptance Criteria

The remediation is complete when:

- The Phase 9 implementation boundary is explicit enough for schema work without
  guessing.
- Teacher, School Admin, Super Admin, and Accountant Examination boundaries are
  consistent across docs and RBAC defaults.
- Examination reports/export/analytics/dashboard work is consistently deferred to
  Phase 10.
- Marks entry assignment scope, grade-scale rules, result retention, report-card
  generation, and privacy rules are documented.
- Relevant RBAC tests pass, and the full suite remains green.
- Phase 9 readiness rerun can approve implementation.

## Required Workflow

1. Read the Phase 9 readiness review findings and authoritative docs.
2. Draft DECISION-035 in `docs/DECISIONS_LOG.md`.
3. Reconcile roadmap, modules, screen flow, security, testing, RBAC, and MCA
   evidence.
4. Tighten Teacher default Examination permissions in `config/rbac.php`.
5. Add focused RBAC regression coverage for the new boundary.
6. Run targeted RBAC tests, full suite, Pint, and `git diff --check`.
7. Rerun Phase 9 readiness review mentally or explicitly and record approval
   evidence in governance/changelog docs.

## Required Final Response

Provide:

- Files modified.
- Decisions recorded.
- RBAC changes.
- Tests or validation run.
- Readiness rerun score and PASS/FAIL verdict.
- Recommended Phase 9 implementation prompt order.
