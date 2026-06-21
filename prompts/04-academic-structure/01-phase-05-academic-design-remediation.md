# Phase 5 Academic Structure Design Remediation

## Role

Act as a senior Laravel architect, SaaS architect, database architect, security
architect, academic-domain analyst, documentation architect, testing specialist,
and MCA project reviewer for this repository.

## Objective

Resolve every blocking finding from the Phase 5 readiness review and make the
Academic Structure design implementation-ready without creating migrations or
application code.

## Execution Mode

Documentation only. Modify prompt and documentation Markdown files only.

Do not create or modify PHP, Blade, JavaScript, CSS, migration, configuration,
test, package, environment, or generated files.

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
- `prompts/04-academic-structure/00-phase-05-readiness-review.md`

## Required Remediation

### Canonical Scope

- Define Phase 5 as Academic Years, Academic Terms, minimal Teacher Profiles,
  Classes, Sections, and Subjects.
- Define "Academic Sessions" as the combined academic-year and academic-term
  lifecycle; use canonical entity names in implementation prompts.
- Keep Students and Student Enrollment in Phase 6.
- Limit Teacher Profiles to academic identity and assignment support. Exclude
  payroll, HR, attendance, timetable, and staff-management expansion.
- Reconcile the roadmap, architecture, module specifications, project overview,
  screen flow, and ERD terminology.

### Tenancy And Relationships

- Confirm every Phase 5 entity is strict tenant-owned and uses
  `BelongsToTenant`.
- Require `school_id` derivation from TenantContext rather than request input.
- Require same-tenant validation for every parent, linked user, and teacher
  assignment.
- Define the allowed user role, school, lifecycle, and one-profile-per-user rules
  for Teacher Profiles.
- Define how an existing Teacher Profile affects user role and school transfers.
- Preserve automatic tenant-scoped route-model binding and direct-service
  context checks.

### Academic Lifecycle

- Define academic-year date validation, overlap behavior, status, activation,
  one-current-year behavior, transaction locking, and deactivation rules.
- Define academic-term ordering, uniqueness, date containment, overlap behavior,
  status, and parent-year lifecycle rules.
- Define class, section, subject, and teacher activation, deactivation, archive,
  restore, downstream-reference, and historical-retention behavior.
- Reconcile soft-delete coverage, especially Sections, with `DECISIONS_LOG.md`
  and `DATABASE_DESIGN.md`.
- Explain whether soft-deleted unique names/codes remain reserved and how records
  are restored.

### Authorization And Screens

- Reconcile the canonical permission matrix with navigation and screen flows.
- Define Super Admin as Platform-context, read-only Academic Structure access.
- Define School Admin management as Tenant-context and permission-bound.
- Define Teacher read-only access as only their assigned sections, assigned
  subjects, and related classes.
- Define Accountant as denied.
- Specify menus, route families, service boundaries, Policies, and Form Request
  responsibilities without generating code.

### Audit And Testing

- Define the activity module, allowed action vocabulary, audit targets, tenant
  ownership, sanitized old/new values, and transactional rollback behavior.
- Define mandatory tenant isolation, forged-parent, role, lifecycle, date,
  concurrency, deletion, route-model binding, direct-service, audit, rollback,
  and UI/menu tests.
- Preserve all existing Phase 4 verification evidence.

## Out Of Scope

Do not:

- Generate migrations, models, controllers, services, requests, policies,
  routes, views, tests, seeders, or application code.
- Add Student Enrollment to Phase 5.
- Add packages or modify `composer.json`, `composer.lock`, `package.json`, or
  package lock files.
- Introduce custom tenancy packages, microservices, CQRS, event sourcing, or an
  unapproved frontend framework.
- Expand Teacher Profiles into a general HR module.

## Deliverables

- Reconciled Phase 5 scope and terminology.
- Approved Teacher Profile boundary and user-link lifecycle.
- Complete academic-year and academic-term lifecycle contract.
- Complete class, section, subject, and teacher retention contract.
- Role, menu, screen, Policy, and service access contract.
- Tenant-safe parent and assignment validation contract.
- Activity and audit contract.
- Mandatory Phase 5 testing contract.
- Updated roadmap, architecture, decisions, modules, screens, security, testing,
  UI, database notes, governance status, changelog, and prompt index as needed.

## Acceptance Criteria

- No Phase 5 implementation decision remains ambiguous.
- All authoritative documents agree on scope and terminology.
- Student Enrollment remains Phase 6.
- Teacher Profile behavior is minimal, explicit, and tenant-safe.
- One-current-year and term-date behavior can be implemented without guessing.
- Role visibility matches the canonical permission matrix.
- Deactivation, soft deletion, restoration, and history rules agree.
- Required tests can be written directly from documentation.
- Only Markdown documentation and prompt files are modified.

## Required Workflow

1. Reconfirm each readiness finding against repository evidence.
2. Update the decision log before dependent design documents.
3. Reconcile scope and status documents.
4. Reconcile database, architecture, module, screen, security, UI, and testing
   contracts.
5. Update the changelog and prompt index.
6. Run documentation consistency searches and `git diff --check`.
7. Rerun `00-phase-05-readiness-review.md` without modifying files.

## Required Final Response

Provide:

- Files modified.
- Findings remediated.
- Architectural decisions added or clarified.
- Validation performed.
- Rerun readiness score and decision.
- Remaining issues.
- Exact next task.
