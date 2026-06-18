# Phase 2 User Management Foundation

## Execution Mode

Implementation. Use this prompt only when the user explicitly approves Phase 2 implementation.

## Objective

Implement the Phase 2 User Management foundation without implementing later-phase RBAC management or tenant management modules.

## Authoritative Documents

Read and follow:

- `AGENTS.md`
- `app/AGENTS.md`
- `resources/AGENTS.md`
- `tests/AGENTS.md`
- `docs/DATABASE_DESIGN.md`
- `docs/MODULE_SPECIFICATIONS.md`
- `docs/SCREEN_FLOW.md`
- `docs/SECURITY_GUIDELINES.md`
- `docs/TESTING_STRATEGY.md`

## Scope

Allowed:

- User listing.
- User create.
- User edit.
- User view.
- User deactivation or soft delete where documented.
- Password reset/admin password update workflow if documented.
- Basic role assignment using existing canonical roles.
- Form Requests for user validation.
- UserPolicy for user access.
- UserService for user workflows.
- Bootstrap 5 Blade screens for Phase 2 user workflows.
- Feature and authorization tests.

## Out Of Scope

Do not implement:

- Full Role and Permission Management UI.
- Custom permission editor.
- School Management UI.
- Teacher or student profile modules.
- Tenant middleware or global tenant scope.
- Later module dashboards or reports.

## Constraints

- Controllers must remain thin.
- Validation belongs in Form Requests.
- Authorization belongs in Policies, Gates, and Middleware.
- Business workflows belong in services.
- `school_id` must not be trusted from arbitrary user input.
- Super Admin access to platform users must be authorized.
- School-scoped user behavior must be prepared for Phase 3 tenant enforcement.

## Required Workflow

1. Inspect existing auth and user files.
2. Confirm Phase 2 user fields against `DATABASE_DESIGN.md`.
3. Implement only the documented user management workflows.
4. Add authorization checks for every action.
5. Add feature tests for allowed and denied paths.
6. Update documentation if user behavior changes.

## Deliverables

- User management routes, controller, requests, policy, service, and views.
- Role-aware user creation foundation.
- Soft delete or deactivation behavior aligned with the deletion policy.
- Tests for user access and validation.

## Acceptance Criteria

- User management follows canonical roles and permissions.
- Unauthorized users cannot manage users.
- Validation is server-side.
- Controllers are thin.
- User deletion follows retention policy.
- Tests cover permitted and denied access.
