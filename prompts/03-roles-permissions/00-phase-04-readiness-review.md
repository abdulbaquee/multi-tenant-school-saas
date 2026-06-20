# Phase 4 Roles & Permissions Readiness Review

## Role

Act as a senior Laravel architect, SaaS architect, security architect, database
architect, testing specialist, and MCA project reviewer for this repository.

## Objective

Determine whether the repository is ready to begin Phase 4: Roles & Permissions
without making undocumented RBAC, authorization, tenant-boundary, database, UI,
or lifecycle decisions during implementation.

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

## Phase 4 Scope

Phase 4 includes readiness for:

- The four canonical roles: Super Admin, School Admin, Teacher, and Accountant.
- Canonical permission records and role-permission mappings.
- Permission-aware Policies, Gates, services, menus, and dashboard visibility.
- Super Admin management of approved canonical permission mappings.
- School Admin assignment of approved school roles to users in the active
  tenant only.
- Tenant-safe authorization with explicit Platform and Tenant context.
- Activity and audit recording for permission and role-assignment changes.
- Mandatory allowed-path, denied-path, privilege-escalation, tenant-isolation,
  and mapping-integrity tests.
- Documentation updates after implementation.

## Out Of Scope

Do not implement or approve implementation of:

- Custom or school-defined roles unless explicitly approved in governance.
- Per-user permission overrides.
- External RBAC packages.
- Academic Structure, Student Management, Attendance, Fees, Examinations,
  Reports, Analytics, log-review screens, or Backup Management.
- Application code, migrations, tests, views, or documentation changes during
  this audit.

## Review Criteria

Verify:

- Phase 3 is release-approved, committed, and passing its automated tests.
- The Phase 4 boundary agrees across the roadmap, module specifications,
  permission matrix, screen flow, security guidance, and architecture.
- Governance unambiguously states whether canonical roles, permission records,
  and role-permission mappings are fixed, editable, or protected.
- The meaning of Role Management and Permission Management is implementation-ready.
- `roles`, `permissions`, `role_permissions`, and `users.role_id` match
  `DATABASE_DESIGN.md`, including indexes, uniqueness, and restricted deletion.
- Seeded roles and permission codes are complete. Any bootstrap mapping that
  differs from the canonical matrix is explicitly documented for synchronization
  before database-backed permission enforcement is enabled.
- Permission evaluation has a documented native-Laravel design for models,
  relationships, Gates, Policies, services, menus, and cache invalidation if used.
- Super Admin cannot accidentally remove essential platform access or create an
  unrecoverable authorization state.
- School Admin role assignment is restricted to users and assignable roles in
  the active school; cross-tenant and Super Admin assignment are denied.
- Permission and role-assignment changes are transactional and auditable without
  exposing one tenant's data to another tenant.
- Route, menu, direct-service, forged-input, stale-session, and privilege-
  escalation tests can be specified from current governance.
- No unapproved RBAC package or architecture is present.
- No documentation conflict or missing decision would force implementation to
  guess.

## Required Workflow

1. Inspect repository status and Phase 3 release evidence.
2. Compare Phase 4 scope across governance, roadmap, database, module, screen,
   security, testing, and UI documents.
3. Inspect the current role and permission schema, seed data, models, policies,
   Gates, services, routes, menus, and tests.
4. Compare every seeded role-permission mapping with the canonical matrix.
5. Run non-mutating validation commands and the existing test suite where
   available.
6. Classify every issue as blocking or non-blocking.
7. Recommend the exact documentation-remediation or Phase 4 prompt execution
   order.

## Deliverables

- Phase 4 readiness score from 1 to 10.
- Evidence reviewed.
- Blocking issues.
- Non-blocking improvements.
- Documentation contradictions or missing decisions.
- Security, privilege-escalation, and tenant-isolation risk assessment.
- Recommended Phase 4 prompt execution order.
- Final decision: READY, READY WITH MINOR IMPROVEMENTS, REQUIRES REMEDIATION, or
  NOT READY.

## Acceptance Criteria

The review is complete when:

- Readiness is based on repository evidence rather than assumptions.
- Authorization and privilege-escalation risks are treated as critical.
- No implementation work is performed.
- The editable and immutable parts of the canonical RBAC model are explicit.
- The next action is precise and belongs to Phase 4.

## Stop Conditions

Stop and recommend documentation remediation instead of implementation if:

- Fixed canonical roles conflict with documented role-management behavior.
- Permission-mapping mutability or essential-access protection is undefined.
- School Admin assignment boundaries are ambiguous.
- A seed-mapping conflict is undocumented or has no safe synchronization plan.
- Required authorization, audit, or tenant-isolation tests cannot be specified
  from current governance.
- Phase 3 verification is failing.

## Required Final Response

Provide:

- Readiness score.
- Audit findings ordered by severity.
- Blocking and non-blocking issues.
- Recommended Phase 4 prompt execution order.
- Final readiness decision and justification.
- Exact next task.
