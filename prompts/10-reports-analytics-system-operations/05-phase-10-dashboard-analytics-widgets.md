# Phase 10 Dashboard And Analytics Widgets

## Role

Act as a senior Laravel architect, security architect, analytics-workflow analyst,
RBAC reviewer, testing specialist, and documentation maintainer for this
repository.

## Objective

Enhance role-specific dashboard summary widgets and deliver a Chart.js Analytics
page for Super Admin and School Admin only, per DECISION-036.

## Execution Mode

Dashboard analytics implementation only.

## Authoritative Documents

Read and follow:

- `AGENTS.md`
- `docs/DECISIONS_LOG.md` (DECISION-036)
- `docs/MODULE_SPECIFICATIONS.md`
- `docs/UI_UX_DESIGN_SYSTEM.md`
- `docs/TESTING_STRATEGY.md`
- `prompts/10-reports-analytics-system-operations/04-phase-10-fee-examination-reports.md`

## Prerequisites

- Phase 10 report screens are complete.
- The full suite passes before changes begin.

## Scope

Allowed work:

- `AnalyticsService`, `AnalyticsController`, and Analytics Blade view.
- Chart.js npm dependency and Vite entry.
- Enhanced `DashboardService` widgets for all roles.
- `analytics.view` gate, route, sidebar link, and focused tests.
- Documentation and changelog updates.

## Out Of Scope

- Activity/Audit screens, Backup Management, standalone report modules.
- Teacher or Accountant Analytics route or `analytics.view` permission changes.

## Required Behavior

- Super Admin and School Admin receive a dedicated Analytics page with up to four
  Chart.js charts using server-derived aggregate data only.
- Teacher and Accountant receive dashboard-embedded summary widgets only.
- Analytics datasets must not expose guardian contact details, addresses, DOB,
  photos, or unnecessary PII.

## Acceptance Criteria

- Authorized roles see correct dashboard widgets and Analytics navigation.
- Teacher and Accountant are denied the Analytics route.
- Focused tests and the full suite pass.

## Required Final Response

Provide files modified, routes added, tests run, and the recommended next prompt.
