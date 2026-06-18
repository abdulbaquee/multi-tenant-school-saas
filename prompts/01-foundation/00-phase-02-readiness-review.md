# Phase 2 Readiness Review

## Execution Mode

Audit only. Do not modify files.

## Objective

Determine whether the repository is ready to begin Phase 2: Authentication & User Management.

## Authoritative Documents

Read and follow:

- `AGENTS.md`
- `app/AGENTS.md`
- `database/AGENTS.md`
- `tests/AGENTS.md`
- `resources/AGENTS.md`
- `docs/PROJECT_CONSTITUTION.md`
- `docs/PROJECT_GOVERNANCE.md`
- `docs/DEVELOPMENT_ROADMAP.md`
- `docs/SYSTEM_ARCHITECTURE.md`
- `docs/TENANCY_DESIGN.md`
- `docs/DATABASE_DESIGN.md`
- `docs/MODULE_SPECIFICATIONS.md`
- `docs/SCREEN_FLOW.md`
- `docs/SECURITY_GUIDELINES.md`
- `docs/TESTING_STRATEGY.md`
- `docs/UI_UX_DESIGN_SYSTEM.md`

## Phase 2 Scope

Phase 2 includes:

- Laravel Breeze authentication setup.
- Login, logout, password reset, and profile foundation.
- User Management foundation.
- Core schema dependencies required for authentication and user management.
- Basic authenticated dashboard shell.
- Role-aware navigation foundation based on existing canonical roles.
- Feature, authorization, and security tests for Phase 2 behavior.

## Out Of Scope

Do not implement:

- School Management UI.
- Full Tenant Foundation.
- Full Role and Permission Management UI.
- Academic Structure.
- Student Management.
- Attendance, Fees, Examinations, Reports, Analytics, Activity Logs, Audit Logs, or Backup Management.
- MCA report content.

## Review Criteria

Verify:

- Laravel 13 app is installed.
- Documentation is sufficient for Phase 2.
- `DATABASE_DESIGN.md` defines required core tables.
- `MODULE_SPECIFICATIONS.md` defines canonical roles.
- `SCREEN_FLOW.md` defines Phase 2 navigation expectations.
- `SECURITY_GUIDELINES.md` covers authentication and authorization.
- `TESTING_STRATEGY.md` covers required test types.
- No pending documentation contradiction blocks Phase 2.

## Required Output

Provide:

- Readiness Score: 1-10.
- Blocking issues.
- Non-blocking improvements.
- Phase 2 prompt execution order.
- Final decision: READY, READY WITH MINOR IMPROVEMENTS, REQUIRES REMEDIATION, or NOT READY.
