# Phase 6 Student Management Design Remediation

## Role

Act as a senior Laravel architect, SaaS architect, security architect, database
architect, student-domain analyst, privacy reviewer, testing specialist,
documentation maintainer, and MCA project reviewer for this repository.

## Objective

Resolve the Phase 6 Student Management readiness findings in authoritative
documentation so Student and Enrollment migrations, models, services, routes,
private-photo delivery, and tests can be implemented without undocumented
decisions.

## Execution Mode

Documentation only. Modify prompt and documentation files only. Do not modify
application code, migrations, tests, configuration, dependencies, or packages.

## Authoritative Documents

Read and follow:

- `AGENTS.md`
- `docs/AGENTS.md`
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

## Scope

Resolve and document:

- Phase 5 release approval and the transition to the Phase 6 design gate.
- The final Phase 6 Student Management boundary.
- Student and Enrollment lifecycle, retention, archival, restoration, and
  status transitions.
- One immutable Student Enrollment placement per student and Academic Year.
- The treatment of internal transfer, promotion, reassignment, and external
  departure for the MCA scope.
- The canonical roll-number source of truth.
- Same-tenant Student, Academic Year, Class, and Section relationship rules.
- Canonical Super Admin, School Admin, Teacher, and Accountant access paths.
- Student Reports and export ownership by phase.
- Private student-photo access, replacement, removal, retention, and logging.
- Sensitive-field visibility and privacy-safe Student/Enrollment audit records.
- Required Phase 6 test coverage.

## Required Decisions

Document these decisions consistently:

1. `student_enrollments.roll_no` is the only roll-number source of truth; remove
   the redundant `students.roll_no` design column and ERD attribute.
2. The unique `(student_id, academic_year_id)` constraint means one immutable
   placement per student and Academic Year. Class, Section, and roll number do
   not change after enrollment creation in the MVP.
3. Internal transfer, promotion, and within-year reassignment are deferred. A
   transferred Student represents departure from the current school only and
   never copies data across tenants.
4. Student Reports and exports are deferred to Phase 10 Reporting. Phase 6
   supplies only authorized directories, search, profiles, and enrollment
   history.
5. School Admin manages own-tenant Students. Teacher access is limited to the
   current active enrollments that are reachable through the Teacher's active
   Section or Subject assignment. Super Admin access is read-only, explicitly
   authorized, school-scoped, and privacy-minimized. Accountant has no direct
   Student screen before Phase 8 Fee Management.
6. Student photos use private storage and are delivered only to an authorized
   School Admin in the Student's tenant. Photo activity and audit evidence never
   stores the file path or image contents.
7. Student audit records exclude DOB, guardian details, address, phone numbers,
   email, and photo paths. They retain only privacy-safe lifecycle and reference
   metadata.

## Out Of Scope

Do not:

- Implement application code, database migrations, models, services, requests,
  policies, controllers, routes, views, private-file endpoints, or tests.
- Add new database tables, packages, frameworks, or architecture patterns.
- Implement Attendance, Fees, Examinations, Reports, Analytics, Parent Portal,
  Student Portal, promotion, import, messaging, or document management.
- Change the approved Laravel 13, PHP 8.4, MySQL 8, Blade, Bootstrap 5, or
  Native Laravel Multi-Tenancy stack.

## Required Workflow

1. Inspect the Phase 6 readiness findings and all affected authoritative
   documentation.
2. Update canonical sources first: roadmap, database design, module
   specifications, screen flow, security, testing, tenancy, and decisions.
3. Reconcile supporting governance, README, changelog, and MCA notes with those
   canonical decisions.
4. Remove stale Phase 5 release-pending language and record the approved gate.
5. Verify that role access, menus, lifecycle rules, private storage, and test
   requirements agree across every updated document.
6. Run documentation consistency searches and whitespace validation.
7. Do not run implementation commands or modify application behavior.

## Deliverables

- A Decision Log entry defining the Phase 6 Student and Enrollment boundary.
- A migration-ready `students` and `student_enrollments` data contract.
- Reconciled role matrix and Student menus.
- Privacy, private-photo, lifecycle, audit, and testing rules.
- Correct Phase 5 release and Phase 6 gate status across governance documents.
- Changelog and MCA evidence updates.

## Acceptance Criteria

This remediation is complete when:

- No Student Report or export work remains assigned to Phase 6.
- The enrollment uniqueness constraint and historical retention policy agree.
- Roll-number ownership is unambiguous and represented consistently in the ERD
  and database design.
- Every role has one documented Student access path and sensitive-field scope.
- Student photos and audit records meet the documented privacy policy.
- Phase 6 migration and test work can be planned without guessing.
- No application code or implementation artifact changed.

## Required Final Response

Provide:

- Files modified.
- Decisions recorded.
- Documentation changes made.
- Consistency checks run.
- Remaining issues.
- Exact next task: rerun `00-phase-06-readiness-review.md`.
