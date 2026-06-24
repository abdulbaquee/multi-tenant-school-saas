# Phase 10 Backup Management

## Role

Act as a senior Laravel architect, security architect, RBAC reviewer, testing
specialist, and documentation maintainer for this repository.

## Objective

Deliver Super Admin backup management with the `backup_logs` table, manual
platform backup workflow, private storage, and retained history per DECISION-036
and DECISION-025.

## Execution Mode

Backup management implementation only.

## Authoritative Documents

Read and follow:

- `AGENTS.md`
- `docs/DECISIONS_LOG.md` (DECISION-036, DECISION-025)
- `docs/MODULE_SPECIFICATIONS.md`
- `docs/SCREEN_FLOW.md`
- `docs/DATABASE_DESIGN.md`
- `docs/SECURITY_GUIDELINES.md`
- `docs/TESTING_STRATEGY.md`
- `prompts/10-reports-analytics-system-operations/06-phase-10-activity-audit-screens.md`

## Prerequisites

- Phase 10 Activity Log and Audit Trail screens are complete.
- The full suite passes before changes begin.

## Scope

Allowed work:

- `backup_logs` migration, `BackupLog` model, `BackupService`, and
  `PlatformDatabaseExporter`.
- `BackupController`, `BackupPolicy`, gates, routes, Blade views, and sidebar
  link.
- Focused feature tests and documentation updates.

## Out Of Scope

- Scheduled backups, external providers, queues, or deployment automation.
- School-scoped backups, Teacher/Accountant routes, or hard deletion of history
  rows.

## Required Behavior

- Super Admin creates synchronous local platform backups stored on the private
  `local` disk.
- Authorized download serves files only through controller paths with private
  cache headers.
- `backups.delete` removes the private file and sets `status = deleted` while
  retaining the history row.
- Backup create, download, and delete actions write privacy-safe activity and
  audit evidence.

## Acceptance Criteria

- Only Super Admin can access backup routes.
- Backup history is listed and viewable with filters.
- Completed backups can be downloaded; deleted backups cannot.
- Focused tests and the full suite pass.

## Required Final Response

Provide files modified, routes added, tests run, and the recommended next prompt.
