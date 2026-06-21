# Phase 5 Class Management

## Role

Act as a senior Laravel architect, multi-tenant SaaS architect, security
architect, database architect, UI/UX implementer, testing specialist, and MCA
project reviewer for this repository.

## Objective

Implement secure, tenant-aware Class management with School Admin lifecycle
operations, Super Admin platform read-only access, Teacher assigned-class reads,
dependency-safe retention, transactional audit evidence, and complete denied-
path coverage.

## Execution Mode

Implementation. Modify only the application, routes, Blade views, tests,
documentation, and prompt files required for Class management.

## Authoritative Documents

Read and follow:

- `AGENTS.md`
- `app/AGENTS.md`
- `resources/AGENTS.md`
- `tests/AGENTS.md`
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
- `prompts/04-academic-structure/01-phase-05-academic-design-remediation.md`
- `prompts/04-academic-structure/02-phase-05-core-academic-schema.md`
- `prompts/04-academic-structure/03-phase-05-academic-year-term-management.md`
- `prompts/04-academic-structure/04-phase-05-teacher-profile-management.md`

## Prerequisites

Verify before implementation:

- Phase 5 schema, Academic Year/Term management, and Teacher Profile management
  are committed, pushed, and regression-clean.
- The existing `SchoolClass` model maps to `classes` and uses
  `BelongsToTenant` plus SoftDeletes.
- Existing Teacher Profile assignment relationships are available.
- No package or schema change is required.

## Scope

Implement:

- `SchoolClassPolicy` with exact `academic.*` permissions and role/context/
  assignment boundaries.
- A tenant-aware `SchoolClassService` for:
  - searchable and filterable current and archived directories;
  - Super Admin Platform-context read-only lists and details;
  - School Admin same-school creation, update, activation, deactivation,
    archival, and restoration;
  - Teacher read-only lists/details limited to active related Classes through
    their own active Teacher Profile's active Section or Subject assignments;
  - sanitized transaction-coupled activity and audit evidence.
- Form Requests for filters, create, update, and lifecycle actions.
- A thin controller and authenticated `tenant.context` routes under `/classes`.
- Responsive Blade screens, filters, details, forms, assignment summaries,
  empty states, and lifecycle confirmations.
- An authorized Classes tab and Teacher-compatible Academic Structure sidebar
  entry without exposing Year, Term, or Teacher Profile administration.
- Feature, authorization, tenant-isolation, assigned-read, lifecycle,
  dependency, rollback, navigation, retention, and responsive-view tests.
- Documentation and changelog updates after verification.

## Access Contract

### Super Admin

- Requires `academic.view`, active authentication, `school_id = NULL`, and
  explicit Platform context.
- Can list and view current and archived Classes across schools.
- Cannot create, update, activate, deactivate, archive, or restore.

### School Admin

- Requires active-school membership and matching Tenant context.
- Requires exact permissions:
  - `academic.view` for list and detail;
  - `academic.create` for creation;
  - `academic.update` for edit, activation, and restoration;
  - `academic.delete` for deactivation and archival.
- Manages own-school Classes only.

### Teacher

- Requires `academic.view`, matching Tenant context, and their own active,
  non-archived Teacher Profile.
- Can list and view only active, non-archived Classes connected through an
  active, non-archived Section or Subject assigned to that profile.
- Cannot view unassigned Classes or any archived/inactive Class.
- Receives no create, update, lifecycle, Year, Term, or Teacher Profile controls.
- Assignment scope is derived from the authenticated user, never request input.

### Accountant, Guest, And Malformed Users

- Receive no Class or Academic Structure access.

## Data Contract

- Required: tenant-unique `name`, tenant-unique normalized `code`, and
  `sort_order` from 0 through 65535.
- New Classes are active and not archived.
- Name, code, and sort order may be edited without changing tenant ownership or
  lifecycle state.
- Requests prohibit `school_id`, `status`, and `deleted_at` overrides.
- Names and codes remain reserved after archival; reuse requires restoration of
  the original Class.

## Lifecycle Contract

- Activation requires a current inactive, non-archived Class.
- Deactivation requires a current active Class and fails while any active,
  non-archived Section or Subject belongs to it.
- Archival requires an inactive, non-archived Class with no active Section or
  Subject dependency.
- Restoration requires an archived Class and returns the original record
  inactive; activation remains separate.
- Inactive or archived downstream records remain retained and are displayed as
  historical references; they are never silently removed.
- No hard-delete, force-delete, or normal DELETE route exists.

## Tenant And Service Requirements

- `SchoolClass` reads inherit `BelongsToTenant`; `withTrashed()` must retain
  TenantScope.
- Teacher assignment filtering derives the profile from `users.id` and accepts
  only the actor's active, non-archived profile and active, non-archived
  Section/Subject links.
- Services independently validate actor identity, exact permission, context
  mode, tenant alignment, Teacher assignment scope, and dependency state.
- Cross-school and unassigned record identifiers fail without leaking tenant
  data.
- Activity module is `academic_structure`; actions are `created`, `updated`,
  `activated`, `deactivated`, `archived`, and `restored`.
- Audit targets `SchoolClass` and contains only approved changed fields.
- Domain and log changes commit or roll back together.

## Out Of Scope

Do not implement:

- Section or Subject creation, assignment, edit, or lifecycle screens.
- Student Enrollment, class promotion, timetable, rooms, curriculum, streams,
  departments, or grade-level automation.
- Class exports, reports, bulk operations, hard deletes, packages, migrations,
  or seed changes.
- Teacher mutation or request-selected assignment scope.

## UI Requirements

- Use the existing authenticated shell and Academic Structure tabs.
- Use Blade, Bootstrap 5, and Bootstrap Icons only.
- Keep directories compact, responsive, searchable, and filterable by active,
  inactive, and archived state.
- Show school identity on Platform screens and assignment context for Teacher
  screens.
- Display retained Section and Subject dependency summaries on details.
- Mark required fields with visible asterisks and accessible labels.
- Use Bootstrap confirmation modals for lifecycle transitions.
- Do not use nested cards, custom SVG icons, or unsupported frameworks.

## Required Tests

Prove:

- Guests redirect to login.
- Super Admin reads current and archived Classes across schools but cannot
  mutate.
- School Admin creates and updates normalized own-school Classes with exact
  permissions, tenant-derived ownership, and transaction-coupled logs.
- Teacher sees only active Classes connected through their own active Section or
  Subject assignments and cannot mutate or infer unassigned records.
- Teacher access disappears when the profile, assignment, or Class becomes
  inactive or archived.
- Accountant, inactive users, malformed users, and wrong direct-service
  contexts are denied.
- Cross-school current and archived records cannot be listed, searched, bound,
  updated, deactivated, archived, or restored.
- Duplicate and archived-reserved names/codes, out-of-range sort order, and
  forged lifecycle/tenant input fail without partial writes.
- Active Section or Subject dependencies block Class deactivation and archival.
- Dependency-safe inactive Classes archive, restore inactive, and activate.
- Exact permission revocation changes menus, routes, and controls immediately.
- Logging failures roll back create and lifecycle changes.
- No hard-delete or normal DELETE route exists.
- All prior tests remain green.

## Required Workflow

1. Verify the committed baseline and inspect Class/assignment conventions.
2. Implement and register `SchoolClassPolicy`.
3. Implement context-aware service, Teacher assignment scope, and lifecycle.
4. Implement Form Requests, thin controller, and retained-record routes.
5. Build read-only directories/details before mutation forms and controls.
6. Add the Classes tab and Teacher-compatible sidebar behavior.
7. Add allowed, denied, tenant, assignment, dependency, retention, and rollback
   tests.
8. Run focused tests, full tests, Pint, frontend build, Composer validation,
   route/view checks, and `git diff --check`.
9. Update implementation status, testing evidence, changelog, and prompt index.
10. Provide manual dashboard test steps and dummy Class data.

## Acceptance Criteria

- Class management follows the approved schema, role matrix, tenant isolation,
  retention, and audit contracts.
- Teacher reads are derived from real assignments and expose no unassigned data.
- Every route and direct service method is authorization- and context-aware.
- Active dependencies prevent invalid lifecycle transitions.
- Archived identity remains reserved and restoration is explicit.
- Every mutation is transactional and auditable.
- Full regression and quality checks pass without out-of-scope work.

## Stop Conditions

Stop and report instead of guessing if:

- The committed Phase 5 baseline fails.
- DECISION-030 conflicts with the schema or role matrix.
- Assigned-Class reads require request-selected teacher or tenant identifiers.
- A package, migration, hard delete, or tenant bypass appears necessary.
- Section or Subject management would need to be implemented prematurely.

## Required Final Response

Provide:

- Commit and push checkpoint.
- Files modified.
- Class workflows implemented.
- Authorization, tenant, assigned-read, lifecycle, and audit protections.
- Tests and validation run.
- Documentation updates.
- Manual dashboard test steps and dummy content.
- Remaining Phase 5 work.
- Exact next task.
