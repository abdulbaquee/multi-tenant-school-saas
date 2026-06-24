# Phase 10 Manual Test Checklist

Status: Passed — operator verification 2026-06-24  
Scope: Reports, Analytics, System Operations (Activity Logs, Audit Trail, Backup Management)

Use this checklist after `php artisan migrate:fresh --seed`. Logins are in
`Dummy-Logins-for-multiple-schools.md` at the repository root.

## Setup

1. `npm install`
2. `npm run dev` (separate terminal)
3. `php artisan serve`
4. Open `http://127.0.0.1:8000`

## Navigation contract

- Sidebar shows one **System Operations** item for authorized roles.
- Activity Logs, Audit Trail, and Backup Management are reached via in-page tabs
  on `/activity-logs`, `/audit-logs`, and `/backups`.

## Super Admin — `superadmin@example.com` / `McaAdmin#2026`

| # | Check | Pass |
|---|-------|------|
| 1 | Sidebar has **System Operations** (single item, not three) | |
| 2 | Tabs switch between Activity Logs, Audit Trail, Backup Management | |
| 3 | Reports hub and all four report categories load | |
| 4 | Report CSV exports include full filtered data | |
| 5 | Analytics page shows charts without unnecessary PII | |
| 6 | Activity log list, detail, and CSV export work | |
| 7 | Audit detail hides sensitive guardian/password fields | |
| 8 | Backup create, download, and soft-delete workflow works | |
| 9 | Backup detail does not show internal file path | |

## School Admin — `admin.sha@example.com` / `Password123`

| # | Check | Pass |
|---|-------|------|
| 1 | Reports and Analytics show School A data only | |
| 2 | **System Operations** shows Activity + Audit tabs only | |
| 3 | `/backups` returns 403 | |
| 4 | Foreign school activity log ID returns 404 | |

## Teacher — `teachera.sha@example.com` / `Password123`

| # | Check | Pass |
|---|-------|------|
| 1 | No Analytics or System Operations in sidebar | |
| 2 | `/analytics`, `/activity-logs`, `/audit-logs`, `/backups` → 403 | |

## Accountant — `accountant.sha@example.com` / `Password123`

| # | Check | Pass |
|---|-------|------|
| 1 | No Analytics or System Operations in sidebar | |
| 2 | `/analytics`, `/activity-logs`, `/audit-logs`, `/backups` → 403 | |
| 3 | Fee reports remain accessible | |

## Automated gate

```bash
php artisan test
```

Expected: full suite passes.

## Evidence for MCA

Capture screenshots for: Reports hub, Analytics, System Operations tabs, audit
detail sanitization, backup lifecycle, and role denial (403) pages.
