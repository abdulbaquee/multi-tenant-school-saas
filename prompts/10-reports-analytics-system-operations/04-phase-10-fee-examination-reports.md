# Phase 10 Fee And Examination Report Screens

## Role

Act as a senior Laravel architect, security architect, reporting-workflow analyst,
RBAC reviewer, testing specialist, and documentation maintainer for this
repository.

## Objective

Implement privacy-safe Fee and Examination report screens that reuse the Phase
10 reporting foundation, including filters, summary cards, paginated tables, CSV
export, and browser print support.

## Execution Mode

Fee and Examination reporting implementation only.

## Authoritative Documents

Read and follow:

- `AGENTS.md`
- `app/AGENTS.md`
- `tests/AGENTS.md`
- `docs/DECISIONS_LOG.md` (DECISION-036)
- `docs/MODULE_SPECIFICATIONS.md`
- `docs/SCREEN_FLOW.md`
- `docs/SECURITY_GUIDELINES.md`
- `docs/TESTING_STRATEGY.md`
- `docs/UI_UX_DESIGN_SYSTEM.md`
- `prompts/10-reports-analytics-system-operations/03-phase-10-student-attendance-reports.md`

## Prerequisites

- Phase 10 design remediation, reporting foundation, and Student/Attendance
  reports are complete.
- The full suite passes before changes begin.

## Scope

Allowed work:

- `FeeReportService` and `ExaminationReportService`.
- Fee and Examination report index/export routes, requests, controller actions,
  and Bootstrap views.
- Role-scoped datasets, summary cards, pagination, CSV export, and print
  actions.
- Focused feature tests for authorization, assignment scope, privacy, filters,
  and export boundaries.
- Documentation and changelog updates.

## Out Of Scope

- Dashboard analytics, Activity/Audit screens, Backup Management.
- PDF/Excel libraries.
- School and User platform report screens.

## Required Behavior

- Super Admin reports show platform aggregate summaries only.
- School Admin sees own-school Fee and Examination datasets.
- Accountant sees Fee reports only; Examination reports are denied.
- Teacher sees assigned-class/subject Examination data only; Fee reports are
  denied.
- CSV export uses the shared `ReportCsvExportService`.
- Report views exclude guardian contact details, addresses, DOB, and photos.

## Acceptance Criteria

- Authorized roles can open Fee and Examination reports from the hub.
- Unauthorized roles are denied.
- Focused tests and the full suite pass.

## Required Final Response

Provide files modified, routes added, tests run, and the recommended next prompt.
