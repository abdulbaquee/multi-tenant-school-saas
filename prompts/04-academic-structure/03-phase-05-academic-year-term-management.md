# Phase 5 Academic Year And Term Management

## Role

Act as a senior Laravel architect, database architect, multi-tenant SaaS
architect, security architect, UI/UX implementer, testing specialist, and MCA
project reviewer for this repository.

## Objective

Implement secure, tenant-aware Academic Year and Academic Term management on
top of the approved Phase 5 schema foundation, including deterministic
lifecycle rules, read-only platform visibility, transactional logging, and
complete allowed- and denied-path tests.

## Execution Mode

Implementation. Modify only the application, routes, Blade views, tests,
documentation, and prompt files required for Academic Year and Academic Term
management.

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

## Prerequisites

Verify before implementation:

- The Phase 5 readiness review is READY.
- The academic design remediation and core schema foundation are complete.
- The six approved Academic Structure models apply automatic tenant scope.
- Existing tests pass before workflow changes.
- No external tenancy, RBAC, or frontend package is required.

## Scope

Implement:

- `AcademicYearPolicy` and `AcademicTermPolicy` with exact `academic.*`
  permission checks and role/context boundaries.
- Tenant-aware `AcademicYearService` and `AcademicTermService` workflows for:
  - searchable and filterable directories;
  - platform-wide read-only list and detail access for Super Admin;
  - same-school create and update access for School Admin;
  - Academic Year activation as the school's single current year;
  - Academic Year deactivation and reactivation;
  - Academic Term deactivation and reactivation;
  - sanitized activity and audit evidence in the domain transaction.
- Form Requests for index filters, create, update, and lifecycle actions.
- Thin controllers and authenticated `tenant.context` routes.
- Bootstrap 5 and Bootstrap Icons Blade screens for directories, details,
  forms, filters, lifecycle states, empty states, validation, and confirmations.
- A single Academic Structure navigation entry with authorized visibility and
  correct active state across Year and Term routes.
- Feature, authorization, tenant-isolation, lifecycle, concurrency-oriented
  transaction, rollback, logging, navigation, and responsive-view tests.
- Documentation and changelog updates after verification.

## Access Contract

### Super Admin

- Requires `academic.view`, an active authenticated platform user,
  `school_id = NULL`, and explicit Platform context.
- Can list and view Academic Years and Terms across schools.
- Receives no create, update, activation, deactivation, or reactivation control.
- Every HTTP and direct-service mutation attempt is denied.

### School Admin

- Requires an active authenticated user, active-school membership, and matching
  Tenant context.
- Requires the exact effective permission for each action:
  - `academic.view` for lists and details;
  - `academic.create` for creation;
  - `academic.update` for edits, current-year activation, and reactivation;
  - `academic.delete` for status-only deactivation.
- Can access records and parent selectors from their own school only.

### Teacher, Accountant, Guest, And Malformed Users

- Receive no Academic Year or Academic Term administration navigation.
- Are denied every Year/Term route and direct-service workflow.
- Teacher assignment-based Academic Structure access is outside this prompt and
  does not include Year or Term administration.

## Academic Year Lifecycle

- New years are `active` and non-current.
- Names are unique per school and date ranges must not overlap any retained year
  in that school; touching boundary dates count as overlap.
- Start date must be before end date.
- Updating a year must keep every retained Term inside the resulting date range.
- Making a year current requires an active target and uses one transaction that:
  - locks the owning School row;
  - locks that school's Academic Year rows;
  - clears any previous current year;
  - marks exactly the target year current;
  - writes activity and sanitized audit evidence.
- The current year cannot be deactivated.
- A non-current year with any active Term cannot be deactivated.
- Deactivation changes status only and preserves the record.
- Reactivation changes an inactive year to active and leaves it non-current.
- No Academic Year delete or hard-delete route exists.

## Academic Term Lifecycle

- New Terms are `active` and belong to an active same-school Academic Year.
- Name and positive `term_order` are unique within the parent year.
- Dates must be ordered, remain inside the parent year, and not overlap another
  retained Term in that year; touching boundary dates count as overlap.
- Parent Academic Year IDs are untrusted and must be tenant-aligned.
- Deactivation changes status only and preserves the Term.
- Reactivation requires an active parent year and revalidates date integrity.
- No Academic Term delete or hard-delete route exists.

## Tenant And Service Requirements

- Every query inherits `BelongsToTenant`; manual `school_id` filters are defense
  in depth, not the primary isolation mechanism.
- Requests prohibit client-supplied `school_id`, `status`, and `is_current`
  ownership/lifecycle overrides where those fields are service-controlled.
- Services independently validate actor identity, active state, role, exact
  permission, and Platform/Tenant context alignment.
- Direct service calls reject Unresolved context, wrong context mode,
  mismatched tenant, inactive school, and malformed users.
- Cross-school record and parent IDs fail without exposing record existence.
- Activity module is `academic_structure`; action vocabulary is `created`,
  `updated`, `activated`, and `deactivated`.
- Audit targets the mutated model and stores only approved changed fields.
- Domain changes and both logs commit or roll back together.

## Out Of Scope

Do not implement:

- Teacher Profile, Class, Section, or Subject management workflows.
- Student Enrollment or any Phase 6 module.
- Exports, reports, bulk actions, calendars, scheduling, cloning, rollover, or
  automatic year generation.
- Hard delete, soft delete, restore, or force-delete behavior for Years/Terms.
- Custom roles, per-user permissions, packages, schema changes, or seed-data
  changes.
- Teacher assignment views or Accountant Academic Structure access.

## UI Requirements

- Use the existing authenticated shell, breadcrumbs, page headers, and sidebar.
- Use Blade, Bootstrap 5, and Bootstrap Icons only.
- Keep directories compact, responsive, searchable, and filterable.
- Display school context on Platform lists without exposing mutation controls.
- Mark required fields with visible asterisks and accessible labels.
- Use stable action areas and Bootstrap confirmation modals/forms for lifecycle
  transitions.
- Do not use nested cards, custom SVG icons, or unsupported frameworks.

## Required Tests

Prove:

- Guests redirect to login.
- Super Admin can list and view records from multiple schools but cannot mutate.
- School Admin can create, update, activate, deactivate, and reactivate valid
  own-school records only when the exact permission is effective.
- Teacher, Accountant, inactive users, malformed users, and wrong direct-service
  contexts are denied.
- School A cannot list, search, view, bind, mutate, or select School B records.
- Forged `school_id`, lifecycle fields, and parent IDs do not bypass controls.
- Year names and ranges, Term names, order, containment, ranges, and overlap
  rules fail with user-friendly validation and no partial write.
- Current-year activation leaves exactly one current active year and rolls back
  fully when logging fails.
- Current years and non-current years with active Terms cannot be deactivated.
- Terms cannot reactivate under an inactive year.
- Every successful mutation writes correctly tenant-owned activity and sanitized
  audit evidence; forced logging failure rolls back the domain change.
- Revoking an exact `academic.*` permission changes route, control, and menu
  access on the next request.
- No Year/Term delete route exists.
- Navigation visibility and active state agree with authorization.
- Existing Phase 2-5 schema and tenant-isolation tests remain green.

## Required Workflow

1. Verify prerequisites and inspect existing Policy, Request, service, UI, log,
   and test conventions.
2. Implement Policies and register model-policy mappings.
3. Implement context-aware services and transactional lifecycle rules.
4. Implement Form Requests, thin controllers, and routes.
5. Build read-only directories/details before mutation forms and controls.
6. Add navigation and lifecycle confirmations.
7. Add comprehensive allowed, denied, tenant, lifecycle, and rollback tests.
8. Run targeted tests, full tests, Pint, frontend build, Composer validation,
   route inspection, and `git diff --check`.
9. Update implementation status, testing evidence, changelog, and prompt index.
10. Provide manual dashboard test steps and dummy Academic Year/Term data.

## Acceptance Criteria

- Year and Term workflows match DECISION-030 and the canonical data dictionary.
- Every route and direct service method is authorization- and context-aware.
- Tenant ownership and cross-school parent integrity cannot be forged.
- At most one active current year exists per school after any completed action.
- Lifecycle history is retained without delete routes.
- Every mutation is transactional and auditable.
- The complete regression suite and quality checks pass.
- No out-of-scope Academic Structure workflow is introduced.

## Stop Conditions

Stop and report instead of guessing if:

- The core schema or readiness baseline is failing.
- DECISION-030 conflicts with the database design or permission matrix.
- A migration, package, trigger, or tenancy bypass appears necessary.
- Safe current-year serialization cannot be implemented with existing Laravel
  transactions and row locks.
- Existing Phase 4 behavior would need to be weakened.

## Required Final Response

Provide:

- Files modified.
- Academic Year and Academic Term workflows implemented.
- Authorization, tenant, lifecycle, and audit protections.
- Tests and validation run.
- Documentation updates.
- Manual dashboard test steps and dummy content.
- Remaining Phase 5 work.
- Exact next task.
