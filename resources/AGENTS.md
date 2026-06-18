# resources/AGENTS.md

Instructions for Blade views, frontend assets, and UI work under `resources/`.

## Sources Of Truth

- UI system: `../docs/UI_UX_DESIGN_SYSTEM.md`
- Screen flow: `../docs/SCREEN_FLOW.md`
- Security: `../docs/SECURITY_GUIDELINES.md`
- Modules and permissions: `../docs/MODULE_SPECIFICATIONS.md`

## Frontend Stack

- Use Blade templates.
- Use Bootstrap 5.
- Use Bootstrap Icons.
- Use Chart.js for charts.

Do not introduce React, Vue, Inertia, Livewire, Tailwind CSS, or Alpine.js unless an approved decision is recorded in `../docs/DECISIONS_LOG.md`.

## UI Rules

- Build simple, professional, responsive SaaS screens.
- Use the shared authenticated layout: header, sidebar, breadcrumb, page title, content area, footer.
- Keep navigation aligned with `../docs/SCREEN_FLOW.md`.
- Do not show menus or actions the role cannot access.
- UI restrictions are not security; server-side authorization is still required.

## Accessibility And Privacy

- Use semantic HTML and labels for form controls.
- Preserve keyboard accessibility and readable contrast.
- Do not expose student photos through public URLs.
- Do not display DOB, guardian information, mobile numbers, or minor data unless the role and screen require it.

## Blade Practices

- Escape user content by default with Blade escaping.
- Avoid raw `{!! !!}` output unless content is sanitized and justified.
- Keep views presentational; move business logic to services.
