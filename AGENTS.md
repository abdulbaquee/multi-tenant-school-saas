# AGENTS.md

This file is the root instruction entry point for Codex and other AI agents working in this repository.

## Project Mission

Build a secure, maintainable, MCA-friendly Multi-Tenant School Administration Management SaaS Platform using Laravel 13, PHP 8.4, MySQL 8, Blade, Bootstrap 5, Laravel Breeze, and native Laravel multi-tenancy.

The project prioritizes academic demonstrability, security, maintainability, simplicity, and documentation-first development.

## Instruction Precedence

Follow instructions in this order:

1. System, developer, and direct user instructions.
2. The nearest `AGENTS.md` file for the files being edited.
3. Parent `AGENTS.md` files, up to this root file.
4. Authoritative project documentation.
5. Existing code conventions.

When project documentation conflicts, use this order:

1. `docs/PROJECT_CONSTITUTION.md`
2. Domain-specific source of truth:
   - Tenancy: `docs/TENANCY_DESIGN.md`
   - Phase order: `docs/DEVELOPMENT_ROADMAP.md`
   - Modules and permissions: `docs/MODULE_SPECIFICATIONS.md`
   - Database schema: `docs/DATABASE_DESIGN.md`
   - Security: `docs/SECURITY_GUIDELINES.md`
   - Testing: `docs/TESTING_STRATEGY.md`
   - UI: `docs/UI_UX_DESIGN_SYSTEM.md`
3. `docs/DECISIONS_LOG.md`
4. Other documentation in `docs/`

When implementation trade-offs conflict, prioritize:

1. Security
2. Tenant isolation
3. Correctness
4. Maintainability
5. Simplicity
6. Feature velocity

## Authoritative Documents

Read the relevant document before changing its domain:

- Governance: `docs/PROJECT_CONSTITUTION.md`, `docs/PROJECT_GOVERNANCE.md`, `docs/DECISIONS_LOG.md`
- Roadmap: `docs/DEVELOPMENT_ROADMAP.md`
- Architecture: `docs/SYSTEM_ARCHITECTURE.md`, `docs/TENANCY_DESIGN.md`
- Database: `docs/DATABASE_DESIGN.md`, `docs/ER_DIAGRAM.md`
- Modules and navigation: `docs/MODULE_SPECIFICATIONS.md`, `docs/SCREEN_FLOW.md`
- UI: `docs/UI_UX_DESIGN_SYSTEM.md`
- Code standards: `docs/CODING_STANDARDS.md`
- Security and quality: `docs/SECURITY_GUIDELINES.md`, `docs/TESTING_STRATEGY.md`
- MCA report planning: `docs/MCA_REPORT_NOTES.md`

## Architecture Guardrails

- Use a layered monolithic Laravel architecture.
- Keep controllers thin.
- Put workflow and business logic in services.
- Use Form Requests for validation.
- Use Policies, Gates, and Middleware for authorization.
- Prefer native Laravel features before adding packages.
- Keep the implementation simple enough to explain during MCA evaluation.

Do not introduce these without an approved decision in `docs/DECISIONS_LOG.md`:

- Stancl Tenancy or Spatie Multitenancy
- Microservices, CQRS, Event Sourcing, service meshes, or Kubernetes complexity
- React, Vue, Inertia, Livewire, Tailwind CSS, or Alpine.js

## Tenancy Requirements

Native Laravel Multi-Tenancy is mandatory.

- Tenant key: `school_id`
- Tenant enforcement: `TenantContextMiddleware`, `BelongsToTenant`, Eloquent global scopes, policies, and service-layer validation
- Every tenant-owned model must use the tenant scope pattern.
- New tenant-owned records must derive `school_id` from tenant context, not user input.
- Never rely on manual `where('school_id', ...)` clauses as the primary isolation mechanism.
- Super Admin users have `school_id = NULL` and may bypass tenant scope only through authorized paths.
- Cross-tenant data exposure is a critical defect.

## Security Requirements

- Enforce authentication, authorization, CSRF protection, validation, session security, and tenant isolation.
- Use private storage for student photos and backups.
- Public storage is allowed for school logos only.
- Preserve audit, activity, payment, attendance, and result history.
- Use `restrictOnDelete()` for foreign keys unless a documented exception exists.
- Never expose stack traces, secrets, passwords, tokens, or other schools' data.

## Documentation Requirements

- Update documentation when architecture, database design, permissions, security policy, testing strategy, or roadmap status changes.
- Add or update `docs/DECISIONS_LOG.md` for architectural decisions.
- Update `docs/CHANGELOG.md` for meaningful documentation or implementation milestones.
- Keep AGENTS files concise and reference docs instead of duplicating large sections.

## Workflow Expectations

- Check existing patterns before editing.
- Keep changes scoped to the request.
- Do not generate application code during documentation-only tasks.
- Do not install packages or modify `composer.json` unless explicitly requested.
- Run relevant tests or validation commands when implementation changes are made.
- Leave unrelated dirty files untouched.

## Cursor Compatibility

`.cursor/rules/*` files are compatibility bridges only. `AGENTS.md` and nested `AGENTS.md` files remain the canonical AI governance system.
