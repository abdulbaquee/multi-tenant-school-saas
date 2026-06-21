# Phase 5 Academic Structure Readiness Review

## Role

Act as a senior Laravel architect, SaaS architect, security architect, database
architect, academic-domain analyst, testing specialist, and MCA project reviewer
for this repository.

## Objective

Determine whether the repository is ready to begin Phase 5: Academic Structure
without making undocumented academic-lifecycle, teacher-profile, tenant-boundary,
database, authorization, UI, or deletion decisions during implementation.

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

## Phase 5 Candidate Scope

Audit readiness for these candidate Phase 5 entities and workflows:

- Academic years and one-current-year lifecycle per school.
- Academic terms belonging to an academic year.
- Classes belonging to a school.
- Sections belonging to a class with an optional class teacher.
- Subjects belonging to a class with an optional assigned teacher.
- Teacher profiles only to the extent required by approved Phase 5 section and
  subject assignments.
- School Admin academic structure management inside the active tenant.
- Super Admin read-only Platform access and Teacher assigned-record read access
  only if both are explicitly approved by current governance.
- Activity and audit evidence for academic structure mutations.
- Mandatory tenant-isolation, authorization, lifecycle, validation, and denied-
  path tests.
- Documentation updates after implementation.

The review must determine the final Phase 5 boundary. Candidate scope is not
permission to implement every listed item.

## Out Of Scope

Do not implement or approve implementation of:

- Student registration, profiles, enrollment, or transfers.
- Attendance, Fees, Examinations, Reports, Analytics, log-review, or Backup
  Management.
- Teacher attendance, payroll, HR, or staff-management features.
- Unapproved timetable, scheduling, curriculum, or subject-allocation systems.
- Application code, migrations, tests, views, or non-prompt documentation
  changes during this audit.

## Review Criteria

Verify:

- Phase 4 is release-approved, committed, pushed, and passing its automated
  tests.
- Phase 5 terminology is canonical: resolve whether "Academic Sessions" means
  `academic_years`, `academic_terms`, or both.
- The Phase 5 entity list agrees across roadmap, module specifications, database
  design, ERD, screen flow, architecture, permissions, and menus.
- Governance explicitly decides whether the `teachers` table and teacher-profile
  management belong to Phase 5 or a later phase, despite `sections.teacher_id`
  and `subjects.teacher_id` dependencies.
- `academic_years`, `academic_terms`, `classes`, `sections`, `subjects`, and any
  approved `teachers` table have complete columns, indexes, unique constraints,
  restricted foreign keys, status values, and soft-delete rules.
- Every approved Phase 5 model is strict tenant-owned, uses `BelongsToTenant`,
  derives `school_id` from TenantContext, and rejects cross-tenant parent IDs.
- Parent-child services verify that academic years, classes, teachers, sections,
  and subjects belong to the same active school.
- One-current-academic-year behavior is transaction-safe and specifies what
  happens when a year is activated, deactivated, or overlaps another year.
- Academic term date ranges, ordering, uniqueness, overlap behavior, and
  containment within the parent academic year are explicit.
- Class, section, subject, and teacher status/deactivation behavior is explicit,
  including downstream-reference restrictions and historical-data retention.
- Super Admin, School Admin, Teacher, and Accountant access matches the canonical
  permission matrix. In particular, reconcile Super Admin `academic.view` with
  screen-flow access and define Teacher assigned-class/subject scope.
- Policies, services, route-model binding, menus, and direct service calls can
  enforce tenant and assigned-record boundaries without manual tenant filtering
  as the primary control.
- Activity and audit events, modules, actions, subjects, old/new values, and
  tenant ownership are documented for all Phase 5 mutations.
- Required allowed, denied, forged-parent, cross-tenant, stale/lifecycle,
  transaction-rollback, and automatic-scope tests can be specified without
  guessing.
- No package, schema abstraction, or architecture beyond the approved layered
  monolith and native Laravel tenancy is required.

## Required Workflow

1. Inspect repository status, Phase 4 release evidence, and current tests.
2. Compare Phase 5 scope across governance, roadmap, database, ERD, modules,
   screens, security, testing, UI, and coding standards.
3. Inspect existing migrations, TenantContext, `BelongsToTenant`, Policies,
   services, routes, menus, activity/audit logging, and test conventions.
4. Trace every Phase 5 relationship and later-module dependency.
5. Verify permissions and role visibility against `config/rbac.php` and the
   canonical matrix.
6. Run non-mutating validation commands and the existing test suite where
   available.
7. Classify every issue as blocking or non-blocking.
8. Recommend the exact documentation-remediation and Phase 5 prompt execution
   order.

## Deliverables

- Phase 5 readiness score from 1 to 10.
- Evidence reviewed.
- Blocking issues.
- Non-blocking improvements.
- Documentation contradictions or missing decisions.
- Database and relationship readiness assessment.
- Security, tenant-isolation, lifecycle, and assigned-record risk assessment.
- Recommended final Phase 5 scope.
- Recommended Phase 5 prompt execution order.
- Final decision: READY, READY WITH MINOR IMPROVEMENTS, REQUIRES REMEDIATION, or
  NOT READY.

## Acceptance Criteria

The review is complete when:

- Readiness is based on repository evidence rather than assumptions.
- Academic lifecycle and cross-tenant relationship risks are treated as
  blocking where ambiguous.
- The teacher-profile dependency is explicitly classified inside or outside
  Phase 5.
- No implementation work is performed.
- The next action is precise and belongs to Phase 5.

## Stop Conditions

Stop and recommend documentation remediation instead of implementation if:

- Phase 5 entities or teacher-profile ownership conflict across authoritative
  documents.
- Current-year activation, term dates, deactivation, or deletion behavior is
  undefined.
- Super Admin or Teacher academic visibility cannot be derived consistently from
  the permission matrix and screen flow.
- Same-tenant parent-child validation or assigned-record enforcement is
  ambiguous.
- Required tenant-isolation, lifecycle, audit, or rollback tests cannot be
  specified from current governance.
- Phase 4 verification is failing or uncommitted.

## Required Final Response

Provide:

- Readiness score.
- Audit findings ordered by severity.
- Blocking and non-blocking issues.
- Recommended final Phase 5 scope.
- Recommended Phase 5 prompt execution order.
- Final readiness decision and justification.
- Exact next task.
