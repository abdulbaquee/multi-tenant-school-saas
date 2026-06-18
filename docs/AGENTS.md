# docs/AGENTS.md

Instructions for documentation under `docs/`.

## Documentation Role

This project is documentation-first. Documentation is part of the implementation blueprint and must stay aligned with code.

## Sources Of Truth

- Constitution and priorities: `PROJECT_CONSTITUTION.md`
- Governance status: `PROJECT_GOVERNANCE.md`
- Decisions: `DECISIONS_LOG.md`
- Roadmap and phase order: `DEVELOPMENT_ROADMAP.md`
- Tenancy: `TENANCY_DESIGN.md`
- Database: `DATABASE_DESIGN.md` and `ER_DIAGRAM.md`
- Modules and permissions: `MODULE_SPECIFICATIONS.md`
- Navigation: `SCREEN_FLOW.md`
- UI: `UI_UX_DESIGN_SYSTEM.md`
- Security: `SECURITY_GUIDELINES.md`
- Testing: `TESTING_STRATEGY.md`
- MCA report planning: `MCA_REPORT_NOTES.md`

## Maintenance Rules

- Update docs when implementation changes architecture, schema, security, permissions, testing, UI, roadmap, or deployment assumptions.
- Record architectural decisions in `DECISIONS_LOG.md`.
- Update `CHANGELOG.md` for meaningful milestones and remediation work.
- Keep document status and phase language consistent across governance, roadmap, and changelog.
- Prefer links to canonical docs over repeated long rule blocks.

## Consistency Rules

- `TENANCY_DESIGN.md` wins for tenancy.
- `DATABASE_DESIGN.md` wins for schema.
- `MODULE_SPECIFICATIONS.md` wins for modules and permissions.
- `DEVELOPMENT_ROADMAP.md` wins for phase sequence.
- `SECURITY_GUIDELINES.md` wins for security controls.
- `TESTING_STRATEGY.md` wins for testing requirements.

When conflicts are found, document them and fix the canonical source first.

## Writing Style

- Keep documentation concise, clear, and MCA-friendly.
- Avoid placeholder content.
- Avoid stale next-step references to documents that already exist.
- Use consistent names: Native Laravel Multi-Tenancy, `school_id`, School Admin, Super Admin, Teacher, Accountant.
