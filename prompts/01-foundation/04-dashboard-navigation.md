# Phase 2 Dashboard And Navigation Foundation

## Execution Mode

Implementation. Use this prompt only when the user explicitly approves Phase 2 implementation.

## Objective

Create the authenticated dashboard shell and role-aware navigation foundation for Phase 2.

## Authoritative Documents

Read and follow:

- `AGENTS.md`
- `resources/AGENTS.md`
- `app/AGENTS.md`
- `docs/SCREEN_FLOW.md`
- `docs/MODULE_SPECIFICATIONS.md`
- `docs/UI_UX_DESIGN_SYSTEM.md`
- `docs/SECURITY_GUIDELINES.md`

## Scope

Allowed:

- Authenticated base layout.
- Header.
- Sidebar.
- Breadcrumb area.
- Page title area.
- Dashboard landing screen for authenticated users.
- Phase 2 navigation items only.
- Bootstrap 5 and Bootstrap Icons integration.

## Out Of Scope

Do not implement:

- Menu links for modules not yet implemented unless clearly disabled or omitted.
- Platform Settings or System Settings.
- Full analytics dashboards.
- Student, attendance, fee, examination, report, audit, or backup screens.
- Client-side-only authorization.

## Constraints

- Menus must match `docs/SCREEN_FLOW.md` and `docs/MODULE_SPECIFICATIONS.md`.
- UI visibility is not authorization.
- Every linked action must have server-side authorization.
- Use Blade Templates, Bootstrap 5, Bootstrap Icons, and Chart.js only where appropriate.
- Do not introduce React, Vue, Inertia, Livewire, Tailwind CSS, or Alpine.js.

## Required Workflow

1. Inspect existing Blade layout and auth views.
2. Build or update the shared authenticated layout.
3. Add only Phase 2 menu entries.
4. Ensure role-aware visibility uses permissions or authorized checks.
5. Add feature tests for dashboard access and unauthorized redirects where appropriate.
6. Update documentation if screen flow changes.

## Deliverables

- Authenticated layout shell.
- Phase 2 dashboard.
- Phase 2 navigation.
- Access tests for dashboard routes.

## Acceptance Criteria

- Dashboard requires authentication.
- Navigation does not expose later modules as active features.
- No removed settings modules appear.
- UI follows the approved design system.
- Authorization exists server-side for protected routes.
