# Phase 7 Daily Attendance Management

## Role

Act as a senior Laravel architect, multi-tenant SaaS architect, security
architect, database architect, testing specialist, UI reviewer, and MCA project
reviewer for this repository.

## Objective

Implement the approved Phase 7 operational Attendance workflow for complete
daily roster entry, authorized correction, history, and monthly on-screen
summaries without introducing Phase 10 reporting or export capabilities.

## Execution Mode

Implementation. Create the approved Policies, Form Requests, Service,
controller, routes, Bootstrap Blade views, navigation, tests, and synchronized
implementation evidence.

## Authoritative Documents

Read and follow:

- `AGENTS.md`
- `app/AGENTS.md`
- `tests/AGENTS.md`
- `resources/AGENTS.md`
- `docs/AGENTS.md`
- `docs/PROJECT_CONSTITUTION.md`
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
- `prompts/06-attendance/01-phase-07-attendance-design-remediation.md`
- `prompts/06-attendance/02-phase-07-core-attendance-schema.md`

## Prerequisites

Verify before implementation:

- Phase 6 is release-approved.
- Phase 7 readiness passed at 10/10.
- The Attendance schema foundation is audited and committed.
- The complete existing test suite passes.
- No Attendance HTTP workflow already exists.

## Scope

Implement:

- `AttendancePolicy` with Phase 7 role, permission, tenant, and direct active
  Section-assignment boundaries.
- Dedicated Form Requests for workspace filters, complete-roster saves, history
  filters, and monthly-summary filters.
- `AttendanceService` for current-year resolution, school-local date checks,
  accessible Sections, eligible Enrollment rosters, atomic writes, correction,
  history, monthly operational counts, and privacy-safe evidence.
- A thin `AttendanceController`.
- Protected routes for:
  - Attendance workspace and roster entry.
  - Atomic roster save and correction.
  - Operational history and search.
  - Monthly on-screen summary.
- Bootstrap 5 Blade views with breadcrumbs, clear required fields, complete
  roster controls, Mark All Present, School Admin-only whole-roster Holiday,
  history filters, and monthly status totals.
- A permission-aware Attendance sidebar item for authorized School Admin and
  Teacher users only.
- Focused feature, Policy, direct-service, tenant-isolation, assignment,
  validation, transaction, privacy, navigation, and denied-path tests.
- Documentation implementation status and exact test evidence after
  verification.

## Authorization Contract

- School Admin may view and operate every eligible active Section in the active
  tenant.
- Teacher requires an active Teacher-role User, active Teacher Profile, and
  direct assignment to the active Section through `sections.teacher_id`.
- Subject assignment, related-Class access, inactive profile, inactive User,
  stale assignment, and unassigned Section grant no Attendance authority.
- Super Admin, Accountant, guests, Platform context, Unresolved context, actor-
  context mismatch, inactive schools, and permission-revoked users are denied.
- School A cannot list, infer, load, save, or correct School B Attendance.
- `attendance.view`, `attendance.create`, and `attendance.update` are the only
  operational Attendance permissions used in Phase 7.
- No delete, report, export, analytics, dashboard-summary, or Platform route is
  introduced.

## Roster And Date Contract

- Resolve exactly one active current Academic Year in the tenant.
- Resolve an active, non-archived same-tenant Section and its active,
  non-archived Class.
- Derive the roster server-side from active same-tenant Student Enrollments for
  that Academic Year and Section whose Class matches, whose Student is active
  and not archived, and whose Enrollment date is on or before the Attendance
  date.
- Require one explicit allowed status for every eligible Student.
- Reject duplicate, omitted, and extraneous Student IDs.
- Permit `present`, `absent`, `leave`, and `late` for row entry.
- Permit `holiday` only as a School Admin whole-roster action.
- Require the date to be inside the Academic Year and no later than the current
  date in `school_settings.timezone`.
- Reject an empty eligible roster rather than creating a partial or misleading
  batch.

## Write And Retention Contract

- Derive School, Academic Year, Class, Section, Student placement, and original
  marker server-side.
- Perform each complete-roster save inside one transaction after locking the
  tenant school, selected Section, eligible Enrollments, and applicable
  Attendance rows.
- Create missing rows only with `attendance.create`.
- Correct existing rows only with `attendance.update`.
- Preserve `school_id`, Student, Academic Year, Class, Section, date, and
  original `marked_by` during correction.
- Repeated identical saves are idempotent and create no duplicate mutation
  evidence.
- Treat the database unique constraint as the final concurrency guard and roll
  back the entire batch on any validation, authorization, persistence, activity
  logging, or audit logging failure.
- Never delete Attendance.

## Privacy And Logging Contract

- Remarks are optional strings limited to 500 characters.
- Do not copy raw remarks, DOB, guardian information, phone numbers, addresses,
  photos, or unrelated minor data into activity or audit logs.
- Create one privacy-safe activity summary only when a batch changes data.
- Create immutable audit evidence for each created or corrected row.
- Audit values may contain Attendance, Student, Enrollment, Academic Year,
  Class, Section IDs, date, old/new status, and `remarks_changed` only.
- A correction audit uses the correcting actor while Attendance `marked_by`
  remains the original creator.

## Operational Views

- The entry screen identifies current Academic Year, Class, Section, date, and
  whether rows are new or retained corrections.
- The roster contains only Student name, admission number, roll number, status,
  and optional remark required for this workflow.
- History supports current-tenant, role-scoped date, Section, status, Student
  name, and admission-number search without export controls.
- Monthly summary displays on-screen counts by status for an authorized Section
  and month. It is not a report, chart, export, dashboard widget, or Platform
  summary.
- Historical-year rows remain readable but cannot be corrected in Phase 7.

## Required Tests

Prove:

- School Admin complete-roster entry, correction, repeated-save idempotency,
  history, monthly summary, and whole-roster Holiday behavior.
- Directly assigned active Teacher entry, history, and current-year correction.
- Teacher Holiday denial and denial for Subject-only, inactive-profile,
  inactive-User, stale-assignment, and unassigned-Section paths.
- Exact roster derivation and rejection of omitted, duplicate, extraneous,
  inactive, archived, completed, transferred, mismatched, and future-enrollment
  records.
- Date rejection for future, out-of-year, historical-year writes, and dates
  before Enrollment.
- Invalid statuses, remarks over 500 characters, and prohibited ownership,
  placement, marker, timestamp, report, export, and delete inputs/routes fail.
- School A/B HTTP and direct-service isolation, route-binding concealment,
  Platform/Unresolved denial, actor-context mismatch, permission revocation,
  Super Admin denial, Accountant denial, and guest redirection.
- Atomic rollback on one invalid row and forced logging failure.
- Privacy-safe activity/audit contents and preservation of original
  `marked_by` during correction.
- Attendance navigation appears only for authorized School Admin and Teacher
  users.
- Existing Phase 2-7 foundation tests remain green.

## Out Of Scope

Do not implement:

- Attendance deletion, soft deletion, or delete routes.
- PDF, Excel, CSV, print, report, export, analytics, charts, dashboard widgets,
  trends, or Platform summaries.
- Super Admin or Accountant Attendance screens or service pathways.
- Automated Late classification from `attendance_start_time`.
- Cross-tenant transfer, packages, queues, jobs, events, notifications, APIs,
  or new database columns.
- Fees, Examinations, Reporting, Analytics, or system operations.

## Required Workflow

1. Inspect existing Policy, Service, Form Request, logging, route, Blade, and
   test conventions.
2. Implement authorization and server-derived roster/query boundaries first.
3. Implement atomic create/correction and privacy-safe evidence.
4. Add thin HTTP flow and Bootstrap views.
5. Add exhaustive allowed and denied tests, including direct-service paths.
6. Run focused tests, the full suite, route inspection, Pint, frontend build,
   Composer validation, and `git diff --check`.
7. Synchronize implementation, security, testing, roadmap, changelog, and MCA
   evidence.

## Acceptance Criteria

- Every write is complete-roster, tenant-derived, assignment-scoped, current-
  year, school-local-date valid, atomic, and retained.
- No actor can expand scope through submitted IDs or direct service calls.
- Corrections preserve original identity and create privacy-safe audit evidence.
- History and monthly summaries expose only authorized operational data.
- No Phase 10 or deletion capability is introduced.
- All focused and regression tests pass.
- No package, migration, or undocumented architecture is introduced.

## Stop Conditions

Stop and report instead of guessing if:

- DECISION-032 conflicts with another higher-authority document.
- A new column, table, package, queue, API, or reporting component appears
  necessary.
- Tenant isolation would require bypassing TenantScope.
- Complete-roster atomicity or privacy-safe logging cannot be maintained.
- Existing tests fail before implementation.

## Required Final Response

Provide:

- Files modified.
- Workflows and role boundaries implemented.
- Tenant, validation, retention, and privacy protections.
- Tests and validation run.
- Documentation updates.
- Manual testing steps and safe dummy data.
- Remaining Phase 7 review work.
- Exact next task.
