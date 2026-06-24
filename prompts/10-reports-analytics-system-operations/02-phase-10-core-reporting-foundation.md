# Phase 10 Core Reporting Foundation And Shared Report Filters

## Role

Act as a senior Laravel architect, security architect, reporting-workflow analyst,
RBAC reviewer, testing specialist, and documentation maintainer for this
repository.

## Objective

Implement the shared Reporting foundation that later Student, Attendance, Fee,
and Examination report screens will reuse, without building full module report
datasets yet.

## Execution Mode

Foundation implementation only. Do not build complete module report datasets,
dashboard analytics widgets, Activity/Audit review screens, or Backup Management
in this run.

## Authoritative Documents

Read and follow:

- `AGENTS.md`
- `app/AGENTS.md`
- `tests/AGENTS.md`
- `docs/AGENTS.md`
- `docs/DECISIONS_LOG.md` (DECISION-036)
- `docs/MODULE_SPECIFICATIONS.md`
- `docs/SCREEN_FLOW.md`
- `docs/SECURITY_GUIDELINES.md`
- `docs/TESTING_STRATEGY.md`
- `docs/UI_UX_DESIGN_SYSTEM.md`
- `prompts/10-reports-analytics-system-operations/01-phase-10-design-remediation.md`

## Prerequisites

Verify before implementation:

- Phase 10 design remediation is approved.
- DECISION-036 is recorded.
- The full suite passes before changes begin.

## Scope

Allowed work:

- `ReportCategory` registry for role-visible report types.
- `ReportPolicy` for `reports.view`, `reports.export`, and category access.
- `ReportService` for actor-context authorization and report catalog assembly.
- `ReportCsvExportService` for native CSV streaming with authorization hooks.
- Shared report filter request foundation with prohibited tenant-override fields.
- `ReportController` hub route and Bootstrap view.
- Sidebar Reports navigation for authorized roles.
- Gate registration for reporting permissions.
- Focused feature tests for authorization, catalog visibility, and filter safety.
- Documentation and changelog updates for the foundation checkpoint.

## Out Of Scope

Do not:

- Implement Student, Attendance, Fee, or Examination report datasets/routes.
- Add PDF/Excel libraries, Chart.js, Activity/Audit screens, Backup Management,
  or dashboard widget enhancements.
- Change Phase 7–9 operational workflows except documented reporting
  dependencies.

## Required Foundation Behavior

- Reports hub lists only categories allowed for the current role.
- Super Admin sees platform summary categories in Platform context.
- School Admin sees own-school categories.
- Teacher sees assigned Student, Attendance, and Examination categories only.
- Accountant sees Fee and fee-context Student categories only.
- Future module report routes use shared filter and CSV export foundation.
- `school_id` and other tenant-override fields are prohibited in report filters.
- CSV export service requires explicit authorization before streaming.

## Acceptance Criteria

- Authorized roles can open the Reports hub.
- Unauthorized roles are denied by route, policy, and UI visibility.
- Role catalogs match DECISION-036 and the permission matrix.
- Shared filter and CSV export foundation are ready for module report prompts.
- Focused reporting foundation tests and the full suite pass.

## Required Final Response

Provide:

- Files created or modified.
- Routes added.
- Tests or validation run.
- Recommended next implementation prompt.
