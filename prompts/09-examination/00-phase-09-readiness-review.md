# Phase 9 Examination Management Readiness Review

## Role

Act as a senior Laravel architect, SaaS architect, security architect, database
architect, academic-workflow analyst, testing specialist, documentation
maintainer, and MCA project reviewer for this repository.

## Objective

Determine whether the repository is ready to begin Phase 9: Examination
Management without making undocumented exam setup, subject assignment, marks
entry, grade calculation, result processing, report-card, teacher-assignment,
reporting, tenant-boundary, authorization, audit, or database decisions during
implementation.

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
- `docs/MCA_SUBMISSION_MASTER_PLAN.md`
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

## Phase 9 Candidate Scope

Audit readiness for these candidate Phase 9 entities and workflows:

- Tenant-owned `exams`, `exam_subjects`, `exam_results`, `grade_scales`, and
  `report_cards` tables and models.
- School Admin exam setup for active own-tenant schools.
- Exam subject assignment by Exam, Subject, Class, max marks, passing marks, and
  exam date.
- Teacher marks entry for assigned class/subject scope only.
- Grade calculation from school grade scales and exam subject max marks.
- Result processing and retained exam result history.
- Operational report-card generation and print/view for own-tenant schools.
- Activity logs, audit logs, result-retention evidence, transaction rollback,
  and privacy-safe descriptions.
- Role boundaries for School Admin, Teacher, Super Admin, Accountant, guests,
  inactive schools, unresolved context, and cross-tenant access.
- Mandatory allowed-path, denied-path, tenant-isolation, forged-parent,
  assignment-scope, duplicate-result, grade-calculation, report-card, logging-
  failure, and rollback tests.
- Documentation updates after implementation.

The review must determine the final Phase 9 boundary. Candidate scope is not
permission to implement every listed item.

## Out Of Scope

Do not implement or approve implementation of:

- Platform-wide examination analytics, export dashboards, charts, or Super Admin
  operational exam routes if governance assigns them to Phase 10.
- General Reporting module screens, PDF/Excel export infrastructure, or
  dashboard widgets beyond documented Phase 9 operational report cards.
- Parent portal, Student portal, notifications, SMS/email report cards,
  automated proctoring, online exam delivery, or external assessment integrations.
- Changes to Student identity, Enrollment placement, Attendance, Fee Management,
  Backup, or final-submission workflows outside documented Examination
  dependencies.
- Application code, migrations, tests, views, or non-prompt documentation changes
  during this audit.
- New packages, frontend frameworks, tenancy libraries, or architectural
  patterns beyond the approved layered monolith.

## Review Criteria

Verify:

- Phase 8 Fee Management is release-approved, committed, passing tests, and
  recorded consistently in authoritative documents.
- Phase 9 scope agrees across the roadmap, module specifications, database
  design, ERD, screen flow, permission matrix, security, testing, menus, and
  current implementation.
- Governance resolves the boundary between Phase 9 operational examination
  workflows/report cards and Phase 10 Examination Reports, exports, analytics,
  charts, dashboards, and platform summaries.
- Governance resolves whether Super Admin has any Phase 9 examination route or
  only later Phase 10 reporting visibility.
- Governance resolves whether Teacher may create/update/delete/publish exams or
  is limited to assigned-class/subject marks entry, result review, and
  operational report-card access.
- Teacher assignment scope for marks entry is explicit and aligned with existing
  Teacher Profile and Academic Structure assignment patterns.
- The schema has complete columns, indexes, unique constraints, restricted
  foreign keys, soft-delete rules, retention rules, decimal precision, status
  values, and tenant ownership for all Examination tables.
- Exam setup records derive `school_id` from `TenantContext`, use
  `BelongsToTenant`, and reject forged School, Academic Year, Term, Class,
  Subject, Student, Exam, Result, and entered-by identifiers.
- Exam lifecycle rules are explicit for scheduled/ongoing/completed/cancelled
  states, duplicate exam names, subject/class assignment, and soft delete before
  results exist.
- Marks entry rules are explicit for eligible enrolled Students, max marks,
  passing marks, absent status, duplicate result rows, corrections, and retained
  history.
- Grade scale rules are explicit for school-specific ranges, default seed data,
  overlapping ranges, and grade calculation timing.
- Report card rules are explicit for generation timing, regeneration, retained
  summaries, and privacy-minimized student identifiers.
- Result history retention is explicit: exam results and report cards must not be
  hard-deleted, and any correction path must preserve audit evidence.
- Services, Form Requests, Policies, route-model binding, menus, direct service
  calls, and views enforce tenant, permission, role, record, lifecycle, and
  context scope.
- Activity and audit event names, module names, subjects, descriptions, old/new
  values, and privacy-safe payloads are documented for setup, assignment, marks
  entry, grade calculation, result processing, and report-card generation.
- Examination workflows never log or expose unnecessary minor data, guardian
  contact details, full addresses, raw remarks beyond approved fields, or secrets.
- Required allowed, denied, cross-tenant, forged-parent, duplicate, assignment-
  scope, grade-calculation, report-card, retention, audit, rollback, and
  automatic-scope tests can be specified without guessing.
- No package or architecture beyond the approved layered monolith and native
  Laravel tenancy is required.

## Required Workflow

1. Inspect repository status, Phase 8 release evidence, migration state, and the
   full test suite.
2. Compare Examination scope across governance, roadmap, database, ERD, modules,
   screen flow, permissions, security, testing, UI, and coding standards.
3. Inspect existing TenantContext, `BelongsToTenant`, Policies, services, route
   binding, activity/audit logging, decimal conventions, Teacher assignment
   patterns, and test conventions.
4. Trace Examination dependencies through Schools, Academic Years, Terms,
   Classes, Sections, Subjects, Students, Enrollments, Teacher Profiles, Users,
   Roles, Permissions, and Activity/Audit models.
5. Test the documented uniqueness, grade-calculation, result-retention, and
   report-card model against database constraints and repeated submissions.
6. Verify role scope against `config/rbac.php` and the canonical permission
   matrix.
7. Separate Phase 9 operational screens from Phase 10 reports, exports,
   analytics, and dashboard work.
8. Run non-mutating validation commands and the existing test suite where
   available.
9. Classify every issue as blocking or non-blocking and recommend the exact
   remediation and prompt execution order.

## Deliverables

- Phase 9 readiness score from 1 to 10.
- Evidence reviewed.
- Findings ordered by severity.
- Blocking issues and non-blocking improvements.
- Documentation contradictions or missing decisions.
- Database, relationship, assignment, marks entry, grade, result, report-card,
  and retention assessment.
- Security, tenant-isolation, teacher-assignment, privacy, and audit assessment.
- Recommended final Phase 9 scope.
- Recommended Phase 9 prompt execution order.
- Final decision: READY, READY WITH MINOR IMPROVEMENTS, REQUIRES REMEDIATION, or
  NOT READY.

## Acceptance Criteria

The review is complete when:

- The repository is either cleared for Phase 9 implementation or blocked with
  exact remediation steps.
- All Phase 9 scope conflicts are identified before code is generated.
- Reporting, exports, analytics, and later-phase work are either explicitly
  deferred or explicitly justified by authoritative documents.
- School Admin, Teacher, Super Admin, and Accountant boundaries are explicit.
- Tenant isolation, privacy, result retention, and testing expectations are
  clear enough to implement without guessing.

## Stop Conditions

Stop and report instead of guessing if:

- Required documentation conflicts.
- The database design is insufficient for safe examination workflows.
- Tenant isolation or result-history retention cannot be enforced safely.
- The task requires a package, external service, secrets, or architecture change.
- The requested scope belongs to Phase 10 or a later roadmap phase.

## Required Final Response

Provide:

- Readiness score and gate decision.
- Blocking issues.
- Non-blocking improvements.
- Recommended remediation prompt if needed.
- Recommended implementation prompt order.
- Tests or validation run.
- Exact next task.
