# Phase 10 Reports, Analytics & System Operations Design Remediation

## Role

Act as a senior Laravel architect, security architect, database architect,
reporting-workflow analyst, RBAC reviewer, documentation maintainer, testing
specialist, and MCA project reviewer for this repository.

## Objective

Resolve the Phase 10 readiness blockers before generating Reporting, Analytics,
Activity Log, Audit Trail, Backup Management migrations, models, controllers,
services, views, or workflow tests.

## Execution Mode

Documentation and RBAC alignment only. Do not implement Phase 10 application
workflows.

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
- `prompts/10-reports-analytics-system-operations/00-phase-10-readiness-review.md`

## Prerequisites

Verify before remediation:

- Phase 9 Examination Management is release-approved, committed, and passing tests.
- The Phase 10 readiness review decision is `REQUIRES REMEDIATION`.
- No Phase 10 report, analytics, activity/audit review, or backup workflow routes
  exist yet.

## Scope

Allowed work:

- Add a Phase 10 decision to `docs/DECISIONS_LOG.md` as DECISION-036.
- Reconcile Reporting, Analytics, Activity Log, Audit Trail, and Backup scope in
  roadmap, module, database, screen-flow, tenancy, security, testing, MCA,
  governance, and changelog documentation.
- Define Phase 10 export boundary: CSV streaming and browser print only; defer
  PDF/Excel libraries unless a separate approved package decision exists.
- Define manual platform backup workflow, private storage, and `backup_logs`
  implementation boundary.
- Define role-specific report, dashboard, analytics, log, and backup access.
- Align `config/rbac.php` so Teacher and Accountant do not receive
  `analytics.view`; Accountant receives `fees.report` and `fees.export` for
  financial reports only.
- Define `backups.delete` as private-file removal with retained `backup_logs`
  history; never hard-delete backup history rows.
- Add focused RBAC regression tests for the permission boundary.
- Add the remediation prompt to `prompts/README.md`.

## Out Of Scope

Do not:

- Create report, analytics, activity, audit, or backup migrations, models,
  policies, services, controllers, requests, views, routes, factories, seeders,
  or workflow tests.
- Add PDF/Excel libraries, external backup providers, queue/cron automation,
  object storage services, or network dependencies.
- Implement parent/student portals, SMS, AI, production payment gateways, or
  Phase 11–13 final QA/deployment/submission packaging beyond evidence notes.
- Change Phase 7–9 operational workflows outside documented reporting
  dependencies.

## Required Decisions To Record

Document that Phase 10 implements:

- Shared reporting foundation with server-derived datasets, Form Request
  filters, Policies, pagination, and summary cards.
- Student, Attendance, Fee, and Examination report screens with the same tenant,
  role, and assignment boundaries as their source modules.
- Super Admin platform report views as privacy-minimized aggregate summaries in
  explicit Platform context only.
- School Admin own-school reports; Teacher assigned-class/subject reports;
  Accountant own-school financial reports and fee-context student lookup only.
- CSV export through native PHP streaming where export permissions apply;
  browser print for printable report views; no PDF/Excel package in Phase 10 MVP.
- Role-specific dashboard widget enhancements in `DashboardService`.
- Dedicated Analytics page for Super Admin and School Admin with Chart.js via npm;
  Teacher and Accountant receive dashboard-embedded summaries only.
- Activity Log and Audit Trail list/detail review screens with privacy-safe detail
  rendering and CSV export.
- `backup_logs` migration, manual platform backup generation, private backup
  storage, authorized download, and immutable history retention.
- Activity and audit list/detail screens for Super Admin platform-wide and School
  Admin own-school only; Teachers and Accountants have no log review routes.
- Backup Management is Super Admin only.
- Report, export, backup, and log review actions write privacy-safe activity and
  audit evidence when appropriate.

## Acceptance Criteria

The remediation is complete when:

- The Phase 10 implementation boundary is explicit enough for foundation work
  without guessing.
- Export, backup, analytics, and role boundaries are consistent across docs and
  RBAC defaults.
- PDF/Excel package dependency is explicitly deferred or excluded for Phase 10
  MVP.
- Relevant RBAC tests pass, and the full suite remains green.
- Phase 10 readiness rerun can approve implementation.

## Required Workflow

1. Read the Phase 10 readiness review findings and authoritative docs.
2. Draft DECISION-036 in `docs/DECISIONS_LOG.md`.
3. Reconcile roadmap, modules, screen flow, security, testing, RBAC, and MCA
   evidence.
4. Tighten Teacher and Accountant `analytics.view` defaults; add Accountant Fee
   report/export permissions in `config/rbac.php`.
5. Add focused RBAC regression coverage for the new boundary.
6. Run targeted RBAC tests, full suite, Pint, and `git diff --check`.
7. Record approval evidence in governance and changelog docs.

## Required Final Response

Provide:

- Files modified.
- Decisions recorded.
- RBAC changes.
- Tests or validation run.
- Readiness rerun score and PASS/FAIL verdict.
- Recommended Phase 10 implementation prompt order.
