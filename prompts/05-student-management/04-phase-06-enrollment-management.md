# Phase 6 Enrollment Management

## Role

Act as a senior Laravel architect, multi-tenant SaaS architect, database
architect, security reviewer, testing specialist, UI/UX implementer, and MCA
project reviewer for this repository.

## Objective

Implement the approved immutable Student Enrollment workflow, including initial
placement, Enrollment completion, and transaction-coupled Student transfer and
graduation, without adding reassignment, promotion, reporting, or new schema.

## Execution Mode

Implementation. Modify only the application, routes, Blade views, tests,
documentation, and prompt files required for Enrollment Management. Do not add
packages, create migrations, alter the approved schema, or implement later-phase
modules.

## Authoritative Documents

Read and follow:

- `AGENTS.md`
- `app/AGENTS.md`
- `database/AGENTS.md`
- `resources/AGENTS.md`
- `tests/AGENTS.md`
- `docs/AGENTS.md`
- `docs/PROJECT_CONSTITUTION.md`
- `docs/PROJECT_GOVERNANCE.md`
- `docs/DECISIONS_LOG.md`, especially DECISION-031
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
- `prompts/05-student-management/01-phase-06-student-design-remediation.md`
- `prompts/05-student-management/02-phase-06-core-student-schema.md`
- `prompts/05-student-management/03-phase-06-student-profile-management.md`

## Prerequisites

Verify before implementation:

- The Phase 6 Student Profile Management checkpoint is committed and its
  manual School Admin workflow has passed.
- The complete regression suite passes.
- The `students` and `student_enrollments` migration has run locally without
  resetting or destroying developer data.
- Student, StudentEnrollment, AcademicYear, SchoolClass, and Section models and
  tenant relationships match `DATABASE_DESIGN.md`.
- No schema or package change is required.

## Scope

Implement:

- Exact Enrollment authorization through the existing Student policy boundary
  or a dedicated policy that follows the repository's established convention.
- A tenant-aware Enrollment service for:
  - selecting eligible active Students and approved academic placement data;
  - creating one immutable Enrollment for a Student and Academic Year;
  - completing an active Enrollment while leaving the Student active;
  - transferring an active Student and its active Enrollment atomically;
  - graduating an active Student and completing its active Enrollment atomically;
  - privacy-safe, transaction-coupled activity and audit evidence.
- Form Requests for Enrollment creation and dedicated lifecycle actions.
- Thin controllers and authenticated `tenant.context` routes.
- School Admin Blade and Bootstrap 5 controls integrated into the existing
  Student profile and Enrollment history screens.
- Feature, authorization, tenant-isolation, validation, immutability,
  concurrency, lifecycle, rollback, logging, and navigation tests.
- Documentation, roadmap status, testing evidence, changelog, MCA notes, and
  prompt-index updates after verification.

## Access Contract

### School Admin

- Requires an active own-school account, matching Tenant context, and the exact
  effective permissions required by the canonical `students.*` matrix.
- May create an initial Enrollment for an active, non-archived own-school
  Student and may complete that active Enrollment.
- May transfer or graduate an active own-school Student only through dedicated
  transaction-coupled actions.
- Cannot edit Enrollment placement, reassign Class or Section, change roll
  number, delete retained Enrollment history, or select another tenant.

### Teacher

- Retains the privacy-minimized, assignment-scoped read access implemented by
  Student Profile Management.
- Cannot create, complete, transfer, graduate, edit, or delete Enrollments.

### Super Admin

- Retains explicit school-selected, privacy-minimized, read-only Student and
  Enrollment history access.
- Cannot invoke any Enrollment or Student lifecycle mutation through HTTP,
  direct services, or forged Platform context.

### Accountant, Guest, And Malformed Users

- Receive no direct Enrollment route, action, or navigation in Phase 6.
- Accountant lookup remains deferred to the Fee Management workflow.

## Enrollment Creation Contract

- Tenant ownership comes only from TenantContext. Prohibit submitted
  `school_id`, `status`, timestamps, and other server-controlled attributes.
- Accept only `student_id`, `academic_year_id`, `class_id`, `section_id`,
  `roll_no`, and `enrollment_date` from the authorized workflow.
- Require an active, non-archived Student in the current tenant.
- Require an active Academic Year marked current for the tenant.
- Require an active, non-archived same-tenant Class. Classes are school-wide in
  the approved schema; the Enrollment links the Class to the Academic Year.
- Require an active, non-archived Section belonging to the selected Class.
- Require `enrollment_date` inside the Academic Year start and end dates and
  not before the Student's admission date.
- Trim and validate roll number against the documented maximum length.
- Enforce one Enrollment per Student and Academic Year and one roll number per
  Section, Academic Year, and tenant using both service validation and existing
  database unique constraints.
- Treat the active Enrollment as the Student's singular operational placement.
  Reject a new Enrollment while another active Enrollment exists; complete the
  prior year first. Do not add a new database constraint.
- Create the Enrollment with status `active`. Never create Enrollment records
  for inactive, transferred, graduated, or archived Students.

## Immutability And Retention Contract

- Student, Academic Year, Class, Section, and roll number are immutable after
  Enrollment creation.
- Do not create edit, reassignment, promotion, or roll-number-change routes.
- Do not expose soft-delete, delete, destroy, force-delete, or restore actions
  for Enrollments.
- Retain completed and transferred Enrollments permanently for Attendance,
  Fees, Examinations, Reporting, and audit history.
- Parent Academic Year, Class, Section, Student, and School deletion remains
  restricted by the implemented foreign keys and retention policy.

## Lifecycle Contract

### Complete Enrollment

- Permit only `active -> completed`.
- Completion leaves the Student active so a later current-year Enrollment can
  be created.
- Repeated completion and terminal-state mutation are denied.

### Transfer Student

- Permit only an active, non-archived Student with exactly one active
  Enrollment.
- In one locked database transaction, set Enrollment status to `transferred`
  and Student status to `transferred`.
- `transferred` is terminal in the MVP.
- Do not copy, move, reveal, or recreate Student data in another school.

### Graduate Student

- Permit only an active, non-archived Student with exactly one active
  Enrollment.
- In one locked database transaction, set Enrollment status to `completed` and
  Student status to `graduated`.
- `graduated` is terminal in the MVP.

- If no active Enrollment or more than one active Enrollment exists, stop the
  transfer/graduation workflow with a safe validation error. Never guess which
  historical record to mutate.
- Inactive Students may retain an active Enrollment but cannot be completed,
  transferred, graduated, or newly enrolled until reactivated.

## Tenant, Authorization, And Concurrency Requirements

- All Enrollment queries inherit `BelongsToTenant` and TenantScope.
- Cross-school Student, Enrollment, Academic Year, Class, and Section IDs must
  resolve as 404 or validation failure without revealing record existence.
- Form validation is not sufficient authorization. Services must independently
  verify actor identity, role, exact permission, context mode, school lifecycle,
  record ownership, active state, parent relationships, and dependencies.
- Lock the tenant School, Student, and affected Enrollment rows for lifecycle
  writes. Rely on database constraints as the final race-condition defense.
- Translate expected duplicate-placement and duplicate-roll constraint failures
  into safe validation responses without exposing SQL or tenant data.
- Unresolved context, Platform mutation, malformed users, inactive schools,
  stale state, and direct cross-tenant service calls must fail closed.

## Logging And Privacy Requirements

- Enrollment and coupled Student mutations must commit with their activity and
  audit rows in the same transaction.
- Use module `student_management` and actions consistent with the existing
  security logging vocabulary.
- Logs may contain Student/Enrollment IDs, admission number, Academic Year,
  Class, Section, roll number, and lifecycle status.
- Logs must exclude DOB, guardian information, address, phone numbers, email,
  photo paths, filenames, image data, full request payloads, and validation
  values containing minor data.
- Logging failure must roll back Enrollment creation, completion, transfer, and
  graduation, including both records in coupled workflows.

## UI Requirements

- Use the existing authenticated shell, Blade, Bootstrap 5, and Bootstrap Icons.
- Add Enrollment controls only to authorized School Admin Student screens.
- Provide a compact initial Enrollment form using eligible current Academic
  Year, Class, and Section choices derived server-side.
- Keep Enrollment history read-only and clearly label active, completed, and
  transferred states.
- Use confirmation modals for completion, Student transfer, and graduation.
- Mark required fields with visible asterisks and accessible labels.
- Preserve stable action widths, responsive tables, validation messages, empty
  states, and existing Student privacy projections.
- Do not add reports, exports, bulk enrollment, spreadsheet import,
  reassignment, promotion, custom SVG icons, or unsupported frontend frameworks.

## Required Tests

Prove:

- Guest, Accountant, Teacher, and Super Admin mutation attempts are denied and
  unauthorized actions are absent from HTML and navigation.
- School Admin can create and view valid own-school Enrollment history.
- `school_id` is tenant-derived and submitted ownership/status values are
  prohibited.
- Cross-tenant Student, Enrollment, Academic Year, Class, and Section IDs never
  bind, validate, mutate, or disclose existence.
- Creation rejects inactive/archived/terminal Students, non-current or inactive
  Academic Years, inactive/archived Classes or Sections, mismatched
  Section/Class, out-of-range dates, dates before admission, invalid
  roll numbers, duplicate Student/year, duplicate Section roll, and an existing
  active Enrollment.
- Placement fields remain immutable through models, services, mass assignment,
  forged requests, and direct update attempts.
- No Enrollment edit, reassignment, promotion, delete, archive, restore, or
  hard-delete route exists.
- Completion permits only `active -> completed` and leaves Student active.
- Transfer atomically sets Enrollment transferred and Student transferred;
  graduation atomically sets Enrollment completed and Student graduated.
- Missing, multiple, inactive, completed, or transferred Enrollment states make
  coupled lifecycle actions fail closed without partial writes.
- Logging failure and other transaction failures roll back every domain change.
- Activity/audit payloads contain approved references only and omit minor data.
- Concurrent duplicate creation is ultimately rejected by database constraints.
- Existing Student photos, profile lifecycle, Teacher assignment scope, Super
  Admin privacy projection, and all prior Phase 2-5 behavior remain green.
- No reporting, export, Attendance, Fee, Examination, promotion, or later-phase
  route is introduced.

## Out Of Scope

Do not implement:

- Mid-year Class or Section reassignment.
- Promotion, bulk Enrollment, import, or automated next-year placement.
- Cross-school Student transfer or Student data copying.
- Enrollment editing or deletion.
- Attendance, Fees, Examinations, reports, exports, analytics, Parent Portal,
  Student Portal, documents, messaging, or notifications.
- New migrations, columns, indexes, packages, seed data containing real minor
  information, or changes to the approved uniqueness model.

## Required Workflow

1. Verify prerequisites and inspect existing Student, Academic, Service, Policy,
   Form Request, transaction, logging, route, UI, and test conventions.
2. Implement and register exact Enrollment authorization boundaries.
3. Implement context-aware eligible-placement reads and Enrollment creation.
4. Implement completion and coupled Student transfer/graduation with locks.
5. Implement Form Requests, thin controllers, routes, and School Admin UI.
6. Add allowed, denied, cross-tenant, validation, immutability, lifecycle,
   concurrency, rollback, privacy, and regression tests.
7. Run focused tests, the full suite, Pint, frontend build, Composer validation,
   route/view checks, migration status, and `git diff --check`.
8. Update canonical implementation status, testing evidence, changelog, roadmap,
   MCA notes, and prompt index only after verification.
9. Provide manual dashboard steps and fictional Enrollment data.

## Acceptance Criteria

- Every Enrollment read and mutation is permission-, role-, context-, tenant-,
  record-, parent-, and lifecycle-aware.
- Placement is immutable and retained after creation.
- Completion, transfer, and graduation follow the approved state graph without
  partial writes.
- Student transfer never crosses tenants or copies Student data.
- Logs and errors do not expose sensitive minor information.
- The complete regression suite and all quality checks pass.
- No schema, package, report, export, reassignment, promotion, or later-phase
  implementation is added.

## Stop Conditions

Stop and report instead of guessing if:

- The committed Student Profile checkpoint or existing regression baseline
  fails.
- The approved schema cannot enforce required uniqueness or retention safely.
- More than one active Enrollment is found during a coupled lifecycle action.
- Safe implementation would require weakening TenantScope or authorization.
- A migration, package, new role capability, reassignment workflow, or
  undocumented lifecycle transition appears necessary.
- DECISION-031 conflicts with canonical database, security, tenancy, or module
  documentation.

## Required Final Response

Provide:

- Files modified.
- Enrollment workflows implemented.
- Authorization, tenancy, immutability, lifecycle, concurrency, privacy, and
  audit protections.
- Tests and validation run.
- Documentation updates.
- Manual dashboard testing steps and fictional dummy content.
- Remaining Phase 6 review-gate work.
- Exact next task: run the Phase 6 tenant-isolation, security, documentation,
  code, and release review gates before Phase 7 Attendance.
