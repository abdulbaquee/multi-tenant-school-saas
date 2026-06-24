# Phase 10 Release Gate Review

## Role

Act as a senior Laravel architect, security architect, tenant-isolation
specialist, testing specialist, documentation maintainer, and MCA project
reviewer for this repository.

## Objective

Run the Phase 10 Reports, Analytics & System Operations release gate by executing
the canonical governance reviews, remediating blocking or high-risk findings,
rerunning verification, and recording approval evidence.

## Execution Mode

Review and targeted remediation. Modify only files required to close blocking or
high-risk release-gate findings.

## Authoritative Documents

Read and follow:

- `AGENTS.md`
- `docs/PROJECT_CONSTITUTION.md`
- `docs/MCA_SUBMISSION_MASTER_PLAN.md`
- `docs/PROJECT_GOVERNANCE.md`
- `docs/DECISIONS_LOG.md` (DECISION-036)
- `docs/DEVELOPMENT_ROADMAP.md`
- `docs/TENANCY_DESIGN.md`
- `docs/SECURITY_GUIDELINES.md`
- `docs/TESTING_STRATEGY.md`
- `docs/CHANGELOG.md`
- `prompts/00-governance/03-tenant-isolation-review.md`
- `prompts/00-governance/02-security-review.md`
- `prompts/00-governance/04-documentation-review.md`
- `prompts/00-governance/05-code-review.md`
- `prompts/00-governance/06-release-review.md`
- `prompts/10-reports-analytics-system-operations/07-phase-10-backup-management.md`

## Prerequisites

Verify before review:

- Phase 10 design remediation (DECISION-036) is approved.
- Reports hub, student/attendance/fee/examination reports, Analytics, Activity
  Log and Audit Trail review screens, and Backup Management are implemented.
- Sidebar exposes a single **System Operations** entry with in-page navigation
  for Activity Logs, Audit Trail, and Backup Management (Super Admin only).
- Focused Phase 10 feature suites and the full application suite pass.

## Required Workflow

1. Run tenant isolation review on report, analytics, log, and backup workflows.
2. Run security review on CSV export, log detail privacy, and private backup
   storage/download/delete paths.
3. Run documentation review for roadmap, module, screen-flow, testing, security,
   changelog, prompt inventory, and MCA evidence consistency.
4. Run code review on Phase 10 services, policies, controllers, requests, views,
   routes, and tests.
5. Remediate any blocking or high-risk finding with the smallest correct diff.
6. Rerun focused Phase 10 suites, full suite, Pint, Composer validation, route
   inspection, and `git diff --check`.
7. Execute the manual testing checklist below and record pass/fail evidence.
8. Run release review and record governance evidence.
9. Update roadmap, governance, changelog, testing, security, MCA evidence, and
   prompt inventory.

## Manual Testing Checklist

Use `Dummy-Logins-for-multiple-schools.md` after `php artisan migrate:fresh --seed`.

### Setup

- [ ] `npm install` and `npm run dev` running for Chart.js assets
- [ ] `php artisan serve` reachable at `http://127.0.0.1:8000`

### Super Admin (`superadmin@example.com`)

**Sidebar**

- [ ] Single **System Operations** sidebar item (not three separate log/backup items)
- [ ] In-page tabs: Activity Logs, Audit Trail, Backup Management

**Reports**

- [ ] Reports hub lists authorized categories
- [ ] Student, Attendance, Fee, Examination reports show platform aggregates
- [ ] Filters and CSV export return full filtered datasets (not one page only)

**Analytics**

- [ ] `/analytics` loads up to four charts with server-derived aggregates only
- [ ] No guardian contact, address, DOB, or photo data exposed

**System Operations — Activity Logs**

- [ ] List, search, module/action/date filters work
- [ ] Detail view is read-only
- [ ] CSV export downloads

**System Operations — Audit Trail**

- [ ] List and detail views work
- [ ] Detail omits passwords, guardian fields, addresses, and tokens
- [ ] CSV export downloads

**System Operations — Backup Management**

- [ ] Create manual platform backup completes
- [ ] Detail shows status and size without internal storage path
- [ ] Download works through authorized route
- [ ] Remove file sets status `deleted` but retains history row
- [ ] Deleted backup cannot be downloaded again
- [ ] Activity and audit evidence recorded for backup actions

### School Admin (`admin.sha@example.com`)

- [ ] Reports and Analytics show own-school data only
- [ ] **System Operations** sidebar item visible with Activity Logs and Audit Trail tabs only
- [ ] No Backup Management tab or `/backups` access
- [ ] Cross-tenant activity log ID returns 404

### Teacher (`teachera.sha@example.com`)

- [ ] No Analytics, System Operations, or Backup sidebar items
- [ ] `/analytics`, `/activity-logs`, `/audit-logs`, `/backups` return 403
- [ ] Authorized examination reports remain available where assigned

### Accountant (`accountant.sha@example.com`)

- [ ] No Analytics, System Operations, or Backup sidebar items
- [ ] `/analytics`, `/activity-logs`, `/audit-logs`, `/backups` return 403
- [ ] Fee reports remain available

### School isolation

- [ ] School A admin cannot see School B report or log data
- [ ] School B admin cannot see School A report or log data

### Automated verification

- [ ] `php artisan test` — full suite passes

## Acceptance Criteria

- No critical tenant isolation, authorization, or privacy defect remains open.
- Teacher and Accountant have no Analytics, Activity Log, Audit Trail, or Backup
  route.
- School Admin has System Operations with own-school logs only.
- Super Admin has platform reports, analytics, and full System Operations scope.
- Report CSV exports match on-screen filters across the full result set.
- Backup files remain private; history rows are retained after file removal.
- Governance scorecards are recorded in `docs/PROJECT_GOVERNANCE.md`.
- Phase 10 status moves to release-approved only after release review passes.

## Required Final Response

Provide review scorecards, remediations applied, manual checklist summary,
tests run, and recommended next MCA evidence tasks.
