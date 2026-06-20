# Phase 4 RBAC Design Remediation

## Role

Act as a senior Laravel architect, SaaS architect, security architect, database
architect, documentation maintainer, testing specialist, and MCA project
reviewer for this repository.

## Objective

Resolve the documentation blockers identified by the Phase 4 readiness review
and make the Roles & Permissions design implementation-ready without creating
application code.

## Execution Mode

Documentation only. Do not modify application code, migrations, tests, views,
routes, package files, or configuration.

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

Update documentation to define:

- Phase 3 release completion and the transition to Phase 4.
- Fixed canonical roles and a fixed canonical permission catalog.
- Which role-permission mappings are immutable, editable, and prohibited.
- The canonical matrix as the default and maximum permission boundary.
- Essential permissions and Super Admin lockout prevention.
- Native Laravel permission resolution through models, relationships, Policies,
  Gates, services, and Blade authorization directives.
- Separation between permission checks and tenant or record-scope checks.
- Super Admin Platform-context mapping management.
- School Admin tenant-bound role assignment and read-only role visibility.
- Role and permission dashboard screens, actions, and menu visibility.
- Transaction, activity-log, audit-log, and immediate-effect requirements.
- Allowed, denied, forged-input, cross-tenant, direct-service, lockout, mapping-
  integrity, and menu-visibility tests.
- The implementation prompt order for Phase 4.

## Required Decisions

Document these MCA-friendly decisions consistently:

- The MVP has exactly four system roles; custom role creation, role deletion,
  role-code changes, and per-user permission overrides are prohibited.
- Permission records are system-managed and cannot be created, renamed, or
  deleted through the UI.
- The Super Admin role always retains every permission assigned to it by the
  canonical matrix and its mapping is read-only.
- A Super Admin may grant or revoke non-essential permissions for School Admin,
  Teacher, and Accountant only within each role's canonical maximum set.
- Essential profile, dashboard, and role-assignment permissions are locked where
  required by the canonical responsibilities.
- School Admin may view effective school-role permissions and assign School
  Admin, Teacher, or Accountant roles only to users in the active school; School
  Admin cannot edit mappings or assign Super Admin.
- Permission checks never replace TenantContext, Policies, or assigned-record
  restrictions.
- Permission results are read from the database without application caching in
  the MVP, so mapping changes apply on the next request.
- User role changes revoke the target user's active sessions and remember token;
  mapping changes do not require session revocation because permissions are not
  cached.
- Every mapping and role-assignment change is transactional and recorded in
  activity and audit logs without storing sensitive data.

## Out Of Scope

Do not:

- Generate PHP, Blade, migration, JavaScript, CSS, or test code.
- Add custom roles, tenant-specific mappings, permission inheritance, per-user
  overrides, or an external RBAC package.
- Implement later roadmap modules.
- Modify `composer.json`, `composer.lock`, or package files.

## Required Workflow

1. Reconcile the readiness findings against all authoritative documents.
2. Record the approved RBAC behavior in `DECISIONS_LOG.md`.
3. Update the canonical permission matrix and module behavior first.
4. Align architecture, security, database, screen flow, testing, governance,
   roadmap, and changelog documents.
5. Search for contradictory fixed/editable role and permission language.
6. Validate Markdown consistency and report every modified file.
7. Rerun `00-phase-04-readiness-review.md` after remediation.

## Deliverables

- Implementation-ready RBAC governance contract.
- Reconciled role and permission matrix semantics.
- Role Management and Permission Management screen flow.
- Security, tenancy, audit, and lockout rules.
- Phase 4 testing acceptance criteria.
- Updated Phase 3/Phase 4 status and changelog evidence.

## Acceptance Criteria

The remediation is complete when:

- No implementation decision about fixed roles, editable mappings, essential
  permissions, authorization flow, tenant boundaries, UI behavior, auditability,
  or testing must be guessed.
- Every document agrees with the canonical matrix.
- Phase 3 is recorded as release-approved and Phase 4 as the current phase.
- No application or package file is modified.
- The readiness review can be rerun from repository evidence.

## Stop Conditions

Stop and report instead of editing if:

- A required decision conflicts with `PROJECT_CONSTITUTION.md`.
- The canonical matrix cannot define a safe maximum permission boundary.
- The design would require a custom RBAC package or schema change not already
  approved.

## Required Final Response

Provide:

- Files modified.
- Decisions added.
- Contradictions resolved.
- Validation performed.
- Remaining risks.
- Phase 4 readiness-review result.
- Exact next task.
