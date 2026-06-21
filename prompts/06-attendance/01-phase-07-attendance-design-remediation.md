# Phase 7 Attendance Design Remediation

## Role

Act as a senior Laravel architect, SaaS architect, security architect, database
architect, Attendance-domain analyst, documentation maintainer, testing
specialist, and MCA project reviewer for this repository.

## Objective

Resolve every blocking and non-blocking documentation finding from the Phase 7
Attendance Management readiness review so Attendance migrations, models, and
workflows can later be implemented without undocumented decisions.

## Execution Mode

Documentation only. Modify prompt and documentation files only.

## Authoritative Documents

Read and follow:

- `AGENTS.md`
- `docs/AGENTS.md`
- `database/AGENTS.md`
- `tests/AGENTS.md`
- `app/AGENTS.md`
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
- `docs/MCA_REPORT_NOTES.md`
- `docs/CHANGELOG.md`
- `config/rbac.php` as read-only implementation evidence

## Scope

Resolve and document:

- The Phase 7 operational boundary and Phase 10 ownership of Attendance reports,
  exports, dashboard analytics, and platform summaries.
- School Admin, Teacher, Super Admin, and Accountant Phase 7 access.
- The exact Teacher assignment relationship that authorizes Attendance entry,
  correction, and history.
- Attendance roster eligibility derived from immutable Student Enrollment.
- Current Academic Year, school-local date, Enrollment date, Student lifecycle,
  Enrollment lifecycle, Class, and Section rules.
- Daily uniqueness, bulk-entry payload completeness, repeated submissions,
  concurrency, transactions, rollback, and correction behavior.
- Historical retention and the non-operational status of physical deletion.
- `attendance.delete`, `attendance.report`, and `attendance.export` phase
  availability without modifying the existing permission catalog in this task.
- Holiday, late, remarks, `marked_by`, activity-log, audit-log, and privacy rules.
- Attendance table nullability, relationships, constraints, and ERD cardinality.
- Mandatory Phase 7 tenant-isolation, authorization, assignment, lifecycle,
  validation, correction, retention, privacy, audit, and rollback tests.
- Governance, roadmap, MCA evidence, changelog, and prompt-index status.

## Allowed Files

- `prompts/06-attendance/01-phase-07-attendance-design-remediation.md`
- `prompts/README.md`
- Relevant Markdown files under `docs/`

## Out Of Scope

Do not:

- Create or modify migrations, models, controllers, Form Requests, Policies,
  services, routes, views, tests, factories, seeders, or configuration files.
- Modify `config/rbac.php`, `composer.json`, `composer.lock`, package manifests,
  environment files, or the database.
- Implement Attendance behavior.
- Add Attendance Reports, exports, analytics, dashboards, notifications,
  biometrics, geolocation, RFID, timetable automation, staff attendance, or
  leave approval.
- Change immutable Student Enrollment placement or add speculative architecture.

## Required Decisions

Document one canonical rule for each item:

1. Phase 7 contains daily/bulk entry, history, search, correction, and monthly
   operational summaries. Phase 10 owns reports, exports, analytics, and Super
   Admin platform summaries.
2. School Admin manages Attendance for the active school. A Teacher may act only
   for an active Section directly assigned through `sections.teacher_id` to the
   actor's active Teacher Profile. Subject assignment alone grants no Attendance
   access. Super Admin and Accountant have no Phase 7 Attendance route or menu.
3. New Attendance requires the active current Academic Year, an active Class and
   Section, an active non-archived Student, and that Student's matching active
   Enrollment. All parents must belong to the active tenant.
4. The date must be inside the Academic Year, on or after `enrollment_date`, and
   not after the current date in the school's configured timezone. New rows are
   not created after the Student or Enrollment becomes terminal. Existing rows
   remain retained and may be corrected under the documented authorization rule.
5. One row exists for each school, Student, and date. A bulk save submits exactly
   one allowed status for every eligible roster member, rejects extraneous or
   duplicate Student IDs, derives placement server-side, and writes atomically.
6. Repeated or mixed saves safely create missing eligible rows and update existing
   rows only with the required permissions. Unchanged rows create no duplicate
   evidence. Validation, authorization, logging, or concurrency failure rolls
   back the entire batch.
7. Attendance is never soft or hard deleted. `attendance.delete` remains a dormant
   catalog permission with no Phase 7 route. Corrections use `attendance.update`.
   Report and export permissions remain dormant until Phase 10.
8. `marked_by` is required and records the original creator. It is immutable;
   correction actors are preserved through the audit record's user identity.
9. Holiday is a School Admin-only bulk status for the entire eligible Section
   roster. Late is manually selected in the MCA scope; the configured attendance
   start time is a display/reference value, not an automatic classifier.
10. Remarks are optional, length-limited, and excluded from activity and audit
    payloads. Logs may record a `remarks_changed` flag but no private note text.

## Required Workflow

1. Reconcile Phase 7 and Phase 10 ownership across all canonical documents.
2. Add an implementation-ready Attendance architecture and workflow contract.
3. Complete the Attendance data dictionary rules without adding speculative
   tables or packages.
4. Reconcile role matrix, phase availability, menus, and screen flows.
5. Define privacy-safe activity and audit evidence.
6. Replace generic Attendance testing bullets with enforceable allowed and denied
   requirements.
7. Record the decision, governance status, changelog entry, MCA evidence, and
   prompt execution status.
8. Search for remaining contradictory Attendance report, analytics, deletion,
   role, assignment, and lifecycle references.
9. Run Markdown and repository diff checks that do not modify application files.

## Deliverables

- Canonical Phase 7 scope and Phase 10 deferrals.
- Implementation-ready Attendance schema and workflow rules.
- Reconciled role, permission, menu, and screen-flow rules.
- Complete tenant-isolation, security, privacy, logging, and testing contract.
- Updated decision, governance, roadmap, changelog, MCA, and prompt evidence.
- A documentation consistency report and exact readiness-rerun instruction.

## Acceptance Criteria

The remediation is complete when:

- Every readiness finding has one documented resolution.
- Migrations and models can be created without guessing about schema, tenancy,
  eligibility, assignment, retention, correction, or authorization.
- Phase 7 contains no report, export, analytics, or platform-summary delivery.
- The permission catalog can remain unchanged while dormant permissions create no
  Phase 7 route or capability.
- Attendance retention and corrections cannot destroy historical evidence.
- Required tests are explicit enough for future implementation.
- No application, database, test, configuration, or package file is modified.

## Stop Conditions

Stop and report instead of guessing if:

- A required decision conflicts with `PROJECT_CONSTITUTION.md`.
- The documented Attendance table cannot support the approved MCA workflow.
- Safe Teacher assignment or Enrollment eligibility requires an unapproved new
  module or package.
- The task would require application code, a migration, or a configuration
  change.

## Required Final Response

Provide:

- Files modified.
- Findings resolved.
- Architectural decisions added.
- Validation performed.
- Remaining issues.
- Exact instruction to rerun the Phase 7 readiness review.
