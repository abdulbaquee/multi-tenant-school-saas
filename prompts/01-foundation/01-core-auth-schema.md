# Phase 2 Core Authentication Schema

## Execution Mode

Implementation. Use this prompt only when the user explicitly approves Phase 2 implementation.

## Objective

Create only the database foundation required for Authentication & User Management.

## Authoritative Documents

Read and follow:

- `AGENTS.md`
- `database/AGENTS.md`
- `docs/DATABASE_DESIGN.md`
- `docs/ER_DIAGRAM.md`
- `docs/TENANCY_DESIGN.md`
- `docs/MODULE_SPECIFICATIONS.md`
- `docs/SECURITY_GUIDELINES.md`
- `docs/DEVELOPMENT_ROADMAP.md`

## Scope

Allowed:

- Core table migrations required by Phase 2 authentication and users.
- Seeders required for canonical roles and initial Super Admin foundation.
- Factory adjustments only when needed for Phase 2 tests.

Use `docs/DATABASE_DESIGN.md` exactly for:

- `schools`
- `roles`
- `permissions`
- `role_permissions`
- `users`

## Out Of Scope

Do not implement:

- School Management UI.
- Full Tenant Foundation middleware or model trait.
- Full Role and Permission Management UI.
- Non-core module tables unless required by already existing Laravel defaults.
- Application feature code outside the Phase 2 schema need.

## Constraints

- Do not invent columns.
- Do not omit documented indexes, unique constraints, or foreign keys.
- Use `restrictOnDelete()` by default.
- Preserve nullable `users.school_id` for Super Admin.
- Do not use cascading deletes for tenant-owned data.
- Keep seed data demo-safe and free of real student or minor data.

## Required Workflow

1. Inspect existing migrations and seeders.
2. Compare with `docs/DATABASE_DESIGN.md`.
3. Add only missing Phase 2 schema artifacts.
4. Keep migration order deterministic.
5. Run migration validation commands if available.
6. Report files changed and verification results.

## Deliverables

- Phase 2 core schema migrations.
- Required role and Super Admin seed foundation.
- Notes on any deferred Phase 3 or Phase 4 schema behavior.

## Acceptance Criteria

- Core auth schema matches `docs/DATABASE_DESIGN.md`.
- Foreign keys and indexes are represented.
- Super Admin can exist with `school_id = NULL`.
- School users can later belong to a school through `school_id`.
- No later module behavior is implemented.
