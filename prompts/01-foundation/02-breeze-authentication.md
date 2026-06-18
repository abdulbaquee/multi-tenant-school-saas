# Phase 2 Breeze Authentication

## Execution Mode

Implementation. Use this prompt only when the user explicitly approves Phase 2 implementation and Laravel Breeze setup.

## Objective

Install and configure Laravel Breeze for the approved Blade and Bootstrap based authentication foundation.

## Authoritative Documents

Read and follow:

- `AGENTS.md`
- `app/AGENTS.md`
- `resources/AGENTS.md`
- `tests/AGENTS.md`
- `docs/DEVELOPMENT_ROADMAP.md`
- `docs/SYSTEM_ARCHITECTURE.md`
- `docs/SECURITY_GUIDELINES.md`
- `docs/UI_UX_DESIGN_SYSTEM.md`
- `docs/TESTING_STRATEGY.md`

## Scope

Allowed:

- Laravel Breeze authentication setup.
- Login.
- Logout.
- Password reset.
- Profile foundation.
- Authenticated layout integration using Blade and Bootstrap 5.
- Phase 2 authentication tests.

## Out Of Scope

Do not implement:

- Tailwind UI as the final project UI.
- React, Vue, Inertia, Livewire, or Alpine.js.
- Tenant middleware.
- School Management.
- Full RBAC management.
- Any non-authentication modules.

## Constraints

- The final UI direction is Blade Templates and Bootstrap 5.
- If Breeze scaffolding introduces conflicting frontend assets, adapt the UI to the approved stack.
- Do not keep unauthorized framework dependencies or patterns without approval.
- Do not modify `composer.json` unless this prompt is being executed as the approved Breeze installation task.

## Required Workflow

1. Inspect whether Breeze is already installed.
2. If not installed, prepare the approved installation steps.
3. Keep authentication behavior aligned with Laravel defaults unless documentation requires otherwise.
4. Integrate authentication screens with Bootstrap 5 styling.
5. Add or update authentication feature tests.
6. Update documentation if installation or authentication behavior changes.

## Deliverables

- Working authentication foundation.
- Bootstrap-compatible auth views.
- Auth route and middleware alignment.
- Feature tests for login, logout, password reset, and profile access.
- Documentation update notes.

## Acceptance Criteria

- Authentication works through Laravel Breeze.
- Auth views use the approved UI stack.
- No unapproved frontend framework becomes the project UI.
- Authentication tests pass.
- No tenant or RBAC behavior beyond Phase 2 is introduced.
