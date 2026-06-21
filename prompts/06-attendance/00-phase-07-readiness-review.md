# Phase 7 Attendance Management Readiness Review

## Role

Act as a senior Laravel architect, SaaS architect, security architect, database
architect, attendance-domain analyst, testing specialist, and MCA project
reviewer for this repository.

## Objective

Determine whether the repository is ready to begin Phase 7: Attendance
Management without making undocumented attendance eligibility, correction,
history, tenant-boundary, teacher-assignment, authorization, database, reporting,
UI, or audit decisions during implementation.

## Execution Mode

Audit only. Do not modify files.

## Authoritative Documents

Read and follow:

- `AGENTS.md`
- `app/AGENTS.md`
- `database/AGENTS.md`
- `tests/AGENTS.md`
- `resources/AGENTS.md`
- `docs/PROJECT_CONSTITUTION.md`
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

## Phase 7 Candidate Scope

Audit readiness for these candidate Phase 7 entities and workflows:

- The tenant-owned `attendances` table and `Attendance` model.
- Daily and bulk Student attendance entry for one Academic Year, Class, Section,
  and date.
- Attendance statuses: present, absent, leave, late, and holiday.
- Attendance history, search, daily views, and monthly operational summaries.
- Controlled correction of retained Attendance records with complete audit
  evidence.
- School Admin access to all eligible Students in the active school.
- Teacher access limited to precisely documented active academic assignments.
- Super Admin read access only if the current phase defines an approved,
  privacy-minimized pathway.
- Transaction-safe bulk writes, duplicate protection, activity logs, audit logs,
  and rollback behavior.
- Mandatory tenant-isolation, assignment-scope, authorization, lifecycle,
  validation, correction, history, and denied-path tests.
- Documentation updates after implementation.

The review must determine the final Phase 7 boundary. Candidate scope is not
permission to implement every listed item.

## Out Of Scope

Do not implement or approve implementation of:

- Attendance exports, general Attendance Reports, dashboard analytics, or
  platform summaries if governance assigns them to Phase 10.
- Fees, Examinations, parent or Student portals, notifications, biometrics,
  geolocation, RFID, timetable automation, staff attendance, or leave approval.
- Changes to Student Enrollment placement or historical Enrollment records.
- Application code, migrations, tests, views, or non-prompt documentation
  changes during this audit.
- New packages, frontend frameworks, or tenancy libraries.

## Review Criteria

Verify:

- Phase 6 is release-approved, committed, pushed, and passing its automated
  tests, with approval recorded consistently in authoritative documents.
- Phase 7 scope agrees across the roadmap, module specifications, database
  design, ERD, screen flow, permission matrix, security, testing, menus, and
  current implementation.
- Governance resolves the conflict between Phase 7 Attendance Reports and
  Analytics references and Phase 10 ownership of reporting and analytics.
- Phase 7 clearly distinguishes operational history and monthly views from
  report generation and export.
- The canonical Teacher assignment rule is enforceable from current Teacher,
  Section, Subject, Class, and User relationships. The review must decide whether
  a Teacher may mark attendance only for an assigned Section or through another
  explicitly documented relationship.
- Super Admin and Accountant visibility is consistent across permissions,
  screens, menus, and the phase boundary.
- `attendances` has complete columns, indexes, unique constraints, restricted
  foreign keys, status values, retention behavior, and tenant ownership.
- The unique attendance rule prevents more than one retained daily record for a
  Student while remaining compatible with bulk entry and safe retries.
- Attendance eligibility is derived from one same-tenant Student Enrollment for
  the selected Academic Year, Class, and Section, without trusting client-sent
  tenant or placement data.
- Rules are explicit for active, inactive, transferred, graduated, and archived
  Student states and active, completed, and transferred Enrollment states on the
  selected attendance date.
- Rules are explicit for current versus historical Academic Years, active versus
  inactive Classes and Sections, future dates, dates outside the Academic Year,
  dates before Enrollment, and dates after Enrollment completion or transfer.
- The design explains whether attendance is limited to the current Academic
  Year and how authorized historical corrections work.
- Bulk entry defines the eligible roster, omitted-row behavior, all-present
  defaults if any, validation failures, duplicate submissions, transaction
  boundaries, and rollback expectations.
- Attendance correction preserves history through privacy-safe audit old/new
  values and never hard deletes or silently overwrites evidence.
- `attendance.delete` is reconciled with the historical-retention policy and
  cannot authorize physical deletion.
- Holiday handling is defined clearly enough to avoid contradictory per-Student
  and school-day behavior within the one-table MCA scope.
- Optional remarks have documented validation and do not capture unnecessary
  health, disability, or other sensitive minor data.
- `marked_by` behavior is defined for creation and correction, including
  retained soft-deleted Users and whether the original marker must remain
  distinguishable from the correcting actor through audit evidence.
- Every Attendance record derives `school_id` from `TenantContext`, uses
  `BelongsToTenant`, and rejects forged Student, Academic Year, Class, Section,
  and marker identifiers from another school.
- Policies, services, route-model binding, menus, bulk endpoints, and direct
  service calls enforce tenant, permission, role, assignment, and record scope.
- Activity and audit event names, module names, subjects, descriptions, and
  privacy-safe payloads are documented for attendance creation and correction.
- Required allowed, denied, cross-tenant, forged-parent, teacher-assignment,
  duplicate, idempotency, correction, historical, retention, audit, rollback,
  and automatic-scope tests can be specified without guessing.
- No package or architecture beyond the approved layered monolith and native
  Laravel tenancy is required.

## Required Workflow

1. Inspect repository status, Phase 6 release evidence, migration state, and the
   full test suite.
2. Compare Attendance scope across governance, roadmap, database, ERD, modules,
   screens, permissions, security, testing, UI, and coding standards.
3. Inspect the implemented TenantContext, `BelongsToTenant`, Policies, services,
   route binding, activity/audit logging, and test conventions.
4. Trace Attendance dependencies through Students, immutable Student
   Enrollments, Academic Years, Classes, Sections, Teacher Profiles, and Users.
5. Test the documented daily uniqueness, bulk-write, correction, and retention
   model against database constraints and concurrent or repeated submissions.
6. Verify role and Teacher assignment scope against `config/rbac.php` and the
   canonical permission matrix.
7. Separate Phase 7 operational screens from Phase 10 reports, exports, and
   analytics.
8. Run non-mutating validation commands and the existing test suite where
   available.
9. Classify every issue as blocking or non-blocking and recommend the exact
   remediation and prompt execution order.

## Deliverables

- Phase 7 readiness score from 1 to 10.
- Evidence reviewed.
- Findings ordered by severity.
- Blocking issues and non-blocking improvements.
- Documentation contradictions or missing decisions.
- Database, relationship, eligibility, correction, and retention assessment.
- Security, tenant-isolation, teacher-assignment, privacy, and audit assessment.
- Recommended final Phase 7 scope.
- Recommended Phase 7 prompt execution order.
- Final decision: READY, READY WITH MINOR IMPROVEMENTS, REQUIRES REMEDIATION, or
  NOT READY.

## Acceptance Criteria

The review is complete when:

- Readiness is based on repository evidence rather than assumptions.
- Cross-tenant exposure and unauthorized Teacher marking are treated as
  blocking defects.
- Enrollment-based roster eligibility, date rules, bulk behavior, correction,
  retention, and reporting ownership are explicitly assessed.
- No implementation or non-prompt documentation work is performed.
- The next action is precise and belongs to Phase 7.

## Stop Conditions

Stop and recommend documentation remediation instead of implementation if:

- Phase 6 release approval is absent, failing, uncommitted, unpushed, or
  inconsistent.
- Reports, exports, analytics, or Super Admin access conflict across governance.
- Teacher assignment scope cannot be enforced from current relationships.
- Attendance eligibility cannot be derived safely from immutable Enrollment
  history and the selected date.
- Daily uniqueness, bulk retry, correction, deletion, or retention behavior is
  ambiguous.
- Same-tenant parent validation or direct-service isolation cannot be specified.
- Required tenant-isolation, authorization, assignment, audit, correction, or
  rollback tests cannot be written from current governance.

## Required Final Response

Provide:

- Readiness score.
- Audit findings ordered by severity.
- Blocking and non-blocking issues.
- Recommended final Phase 7 scope.
- Recommended Phase 7 prompt execution order.
- Final readiness decision and justification.
- Exact next task.
