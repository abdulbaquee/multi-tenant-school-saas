# Phase 6 Student Management Readiness Review

## Role

Act as a senior Laravel architect, SaaS architect, security architect, database
architect, student-domain analyst, privacy reviewer, testing specialist, and MCA
project reviewer for this repository.

## Objective

Determine whether the repository is ready to begin Phase 6: Student Management
without making undocumented student-lifecycle, enrollment-history, privacy,
tenant-boundary, authorization, database, file-storage, UI, or reporting
decisions during implementation.

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

## Phase 6 Candidate Scope

Audit readiness for these candidate Phase 6 entities and workflows:

- Student master records and private student-photo handling.
- Student registration, profile, directory, search, edit, status, deactivation,
  archival, restoration, transfer, and graduation boundaries.
- Student enrollment into an Academic Year, Class, and Section with a roll
  number.
- Enrollment history and any approved within-year transfer or reassignment
  workflow.
- School Admin management inside the active tenant.
- Teacher read access limited to students assigned through the Teacher's active
  academic relationships.
- Super Admin and Accountant student access only if current governance defines
  an authorized, least-privilege pathway.
- Student activity and audit evidence without unnecessary minor data.
- Mandatory tenant-isolation, authorization, privacy, lifecycle, validation,
  storage, transaction, and denied-path tests.
- Documentation updates after implementation.

The review must determine the final Phase 6 boundary. Candidate scope is not
permission to implement every listed item.

## Out Of Scope

Do not implement or approve implementation of:

- Attendance, Fees, Examinations, general Reports, Analytics, log-review, or
  Backup Management.
- Parent or Student portals.
- Student promotion, bulk import, messaging, transport, hostel, medical,
  biometric, or document-management features unless already approved.
- Exports or Student Reports if governance assigns them to the reporting phase.
- Application code, migrations, tests, views, or non-prompt documentation
  changes during this audit.

## Review Criteria

Verify:

- Phase 5 is release-approved, committed, pushed, and passing its automated
  tests, with approval recorded consistently in governance documents.
- Phase 6 scope agrees across roadmap, module specifications, database design,
  ERD, screen flow, permissions, security, testing, and menus.
- Governance resolves whether Student Reports and `students.export` belong to
  Phase 6 or the later reporting phase.
- Governance resolves Super Admin student visibility and Accountant limited
  lookup, including whether Accountant access must wait for Fee Management.
- Teacher assigned-student visibility has one enforceable rule derived from
  active Teacher Profile, Class, Section, Subject, and Enrollment relationships.
- `students` and `student_enrollments` have complete columns, indexes, unique
  constraints, restricted foreign keys, statuses, and retention rules.
- `student_enrollments.roll_no` is the only roll-number source of truth and no
  redundant Student roll-number column can drift from it.
- Student status transitions for active, inactive, transferred, and graduated
  records are explicit, including effects on enrollment and downstream access.
- Enrollment history is compatible with the unique student/year constraint and
  the proposed transfer or reassignment workflow. Historical enrollment data
  must not be overwritten or silently discarded.
- Enrollment services can prove that Student, Academic Year, Class, and Section
  belong to the same active school and that the Section belongs to the selected
  Class.
- Enrollment date, Academic Year status, Class/Section status, roll-number
  uniqueness, re-enrollment, promotion, transfer, and completion rules are
  explicit enough to test without guessing.
- Student archival and restoration preconditions preserve enrollment and future
  attendance, fee, examination, and reporting history.
- Student photos use private storage, generated filenames, MIME and size
  validation, authorized controller delivery, tenant validation, permission
  validation, replacement cleanup, and retained-record access rules.
- DOB, guardian details, phone numbers, address, and photos follow least
  privilege and are excluded from unnecessary logs, audit payloads, reports,
  and screens.
- Gender and other constrained student fields have documented accepted values
  and validation behavior suitable for the MCA scope.
- Every Phase 6 model is strict tenant-owned, uses `BelongsToTenant`, derives
  `school_id` from TenantContext, and rejects forged cross-tenant parent IDs.
- Policies, services, route-model binding, menus, private-file routes, and
  direct service calls enforce tenant and record-scope boundaries.
- Activity and audit event names, modules, subjects, lifecycle events, and
  privacy-safe old/new values are documented for Student and Enrollment
  mutations.
- Required allowed, denied, cross-tenant, forged-parent, assigned-record,
  privacy, upload, lifecycle, rollback, retention, and automatic-scope tests can
  be specified without guessing.
- No package or architecture beyond the approved layered monolith and native
  Laravel tenancy is required.

## Required Workflow

1. Inspect repository status, Phase 5 release evidence, and the full test suite.
2. Compare Phase 6 scope across governance, roadmap, database, ERD, modules,
   screens, permissions, security, testing, UI, and coding standards.
3. Inspect existing TenantContext, `BelongsToTenant`, Policies, services, route
   binding, private storage, activity/audit logging, and test conventions.
4. Trace Student and Enrollment dependencies into current Academic Structure
   and future Attendance, Fee, Examination, and Reporting modules.
5. Test the documented enrollment and transfer model against database unique
   constraints and historical-retention requirements.
6. Verify role visibility and sensitive-field access against `config/rbac.php`
   and the canonical permission matrix.
7. Run non-mutating validation commands and the existing test suite where
   available.
8. Classify every issue as blocking or non-blocking and recommend the exact
   remediation and prompt execution order.

## Deliverables

- Phase 6 readiness score from 1 to 10.
- Evidence reviewed.
- Blocking issues.
- Non-blocking improvements.
- Documentation contradictions or missing decisions.
- Database, relationship, lifecycle, and historical-retention assessment.
- Security, privacy, file-storage, tenant-isolation, and assigned-record risk
  assessment.
- Recommended final Phase 6 scope.
- Recommended Phase 6 prompt execution order.
- Final decision: READY, READY WITH MINOR IMPROVEMENTS, REQUIRES REMEDIATION, or
  NOT READY.

## Acceptance Criteria

The review is complete when:

- Readiness is based on repository evidence rather than assumptions.
- Student privacy and cross-tenant risks are treated as blocking where
  ambiguous.
- Enrollment history, transfer behavior, roll-number ownership, and role scope
  are explicitly assessed.
- No implementation work is performed.
- The next action is precise and belongs to Phase 6.

## Stop Conditions

Stop and recommend documentation remediation instead of implementation if:

- Phase 5 release approval is absent, failing, uncommitted, or inconsistent.
- Student or Enrollment schema conflicts with required lifecycle or history.
- Transfer behavior would overwrite immutable enrollment history.
- Super Admin, Teacher, or Accountant student visibility cannot be derived
  consistently from the permission matrix and screen flow.
- Student Reports or exports have conflicting phase ownership.
- Sensitive-field or private-photo access cannot be authorized safely.
- Same-tenant parent validation or assigned-student enforcement is ambiguous.
- Required privacy, tenant-isolation, lifecycle, audit, or rollback tests cannot
  be specified from current governance.

## Required Final Response

Provide:

- Readiness score.
- Audit findings ordered by severity.
- Blocking and non-blocking issues.
- Recommended final Phase 6 scope.
- Recommended Phase 6 prompt execution order.
- Final readiness decision and justification.
- Exact next task.
