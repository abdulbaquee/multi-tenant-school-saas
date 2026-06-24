# Phase 10 Activity And Audit Review Screens

## Role

Act as a senior Laravel architect, security architect, RBAC reviewer, testing
specialist, and documentation maintainer for this repository.

## Objective

Deliver read-only Activity Log and Audit Trail review screens for Super Admin
(platform-wide) and School Admin (own school), per DECISION-036.

## Execution Mode

Activity and audit review implementation only.

## Authoritative Documents

Read and follow:

- `AGENTS.md`
- `docs/DECISIONS_LOG.md` (DECISION-036)
- `docs/MODULE_SPECIFICATIONS.md`
- `docs/SCREEN_FLOW.md`
- `docs/SECURITY_GUIDELINES.md`
- `docs/UI_UX_DESIGN_SYSTEM.md`
- `docs/TESTING_STRATEGY.md`
- `prompts/10-reports-analytics-system-operations/05-phase-10-dashboard-analytics-widgets.md`

## Prerequisites

- Phase 10 dashboard and analytics work is complete.
- The full suite passes before changes begin.

## Scope

Allowed work:

- `ActivityLogService`, `AuditLogService`, and CSV export helper.
- `ActivityLogController`, `AuditLogController`, Form Requests, policies, gates,
  routes, Blade views, and sidebar links.
- `PrivacySafeLogValues` for audit detail rendering.
- Focused feature tests and documentation updates.

## Out Of Scope

- Backup Management, new logging writers, log deletion, or mutation endpoints.
- Teacher or Accountant Activity/Audit routes or permissions.

## Required Behavior

- Super Admin reviews platform-wide logs in Platform context with school column
  where applicable.
- School Admin reviews own-school logs in Tenant context only.
- List screens support search, module/action or event filters, date range, and
  CSV export for authorized reviewers.
- Detail screens are read-only. Audit `old_values` and `new_values` use
  privacy-safe field filtering before display.
- Activity and audit records remain immutable.

## Acceptance Criteria

- Authorized roles see correct navigation, list, detail, and export behavior.
- Teacher and Accountant are denied all Activity Log and Audit Trail routes.
- Cross-tenant log access returns not found.
- Focused tests and the full suite pass.

## Required Final Response

Provide files modified, routes added, tests run, and the recommended next prompt.
