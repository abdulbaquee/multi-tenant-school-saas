# Prompt Architecture V2

This folder contains the active prompt system for the Multi-Tenant School Administration Management SaaS Platform.

The V2 prompts replace the old Cursor-era `.docx` prompts as the maintainable prompt workflow for Codex and other AI agents.

## Purpose

Use these prompts to guide implementation, review, documentation, quality assurance, MCA report preparation, and final submission without drifting from project governance.

The prompt system is designed to:

- Minimize hallucinations.
- Preserve architecture decisions.
- Enforce tenant isolation.
- Enforce security and authorization.
- Require tests for implementation work.
- Keep documentation aligned with code.
- Support MCA report and viva preparation from real project evidence.

## Instruction Precedence

Every prompt in this folder must follow this order:

1. System, developer, and direct user instructions.
2. The nearest applicable `AGENTS.md`.
3. Parent `AGENTS.md` files up to the root `AGENTS.md`.
4. Authoritative project documentation.
5. Existing repository conventions.

Prompts must never override project governance.

## Authoritative Documents

Before using any prompt, read the relevant documents:

- Root governance: `../AGENTS.md`
- Application code: `../app/AGENTS.md`
- Database work: `../database/AGENTS.md`
- Tests: `../tests/AGENTS.md`
- Documentation: `../docs/AGENTS.md`
- UI and Blade views: `../resources/AGENTS.md`
- Constitution: `../docs/PROJECT_CONSTITUTION.md`
- Governance: `../docs/PROJECT_GOVERNANCE.md`
- Decisions: `../docs/DECISIONS_LOG.md`
- Roadmap: `../docs/DEVELOPMENT_ROADMAP.md`
- Architecture: `../docs/SYSTEM_ARCHITECTURE.md`
- Tenancy: `../docs/TENANCY_DESIGN.md`
- Database: `../docs/DATABASE_DESIGN.md`
- Modules and permissions: `../docs/MODULE_SPECIFICATIONS.md`
- Screen flow: `../docs/SCREEN_FLOW.md`
- Security: `../docs/SECURITY_GUIDELINES.md`
- Testing: `../docs/TESTING_STRATEGY.md`
- UI: `../docs/UI_UX_DESIGN_SYSTEM.md`

## Folder Structure

```text
prompts/
├── README.md
├── templates/
│   └── standard-prompt-template.md
├── 00-governance/
│   ├── 00-prompt-audit.md
│   ├── 01-architecture-review.md
│   ├── 02-security-review.md
│   ├── 03-tenant-isolation-review.md
│   ├── 04-documentation-review.md
│   ├── 05-code-review.md
│   └── 06-release-review.md
├── 01-foundation/
│   ├── 00-phase-02-readiness-review.md
│   ├── 01-core-auth-schema.md
│   ├── 02-breeze-authentication.md
│   ├── 03-user-management.md
│   ├── 04-dashboard-navigation.md
│   ├── 05-phase-02-feature-tests.md
│   └── 06-phase-02-documentation-update.md
├── 02-multi-tenant-foundation/
│   ├── 00-phase-03-readiness-review.md
│   ├── 01-phase-03-tenancy-design-remediation.md
│   ├── 02-core-tenant-context.md
│   ├── 03-tenant-scope-and-school-settings-schema.md
│   ├── 04-school-management.md
│   ├── 05-school-settings.md
│   ├── 06-tenant-service-context-hardening.md
│   └── 07-phase-03-security-remediation.md
├── 03-roles-permissions/
    ├── 00-phase-04-readiness-review.md
    ├── 01-phase-04-rbac-design-remediation.md
    ├── 02-phase-04-core-rbac-foundation.md
    └── 03-role-permission-dashboard-management.md
├── 04-academic-structure/
    ├── 00-phase-05-readiness-review.md
    ├── 01-phase-05-academic-design-remediation.md
    ├── 02-phase-05-core-academic-schema.md
    ├── 03-phase-05-academic-year-term-management.md
    ├── 04-phase-05-teacher-profile-management.md
    ├── 05-phase-05-class-management.md
    └── 06-phase-05-section-subject-management.md
└── 05-student-management/
    ├── 00-phase-06-readiness-review.md
    ├── 01-phase-06-student-design-remediation.md
    ├── 02-phase-06-core-student-schema.md
    ├── 03-phase-06-student-profile-management.md
    └── 04-phase-06-enrollment-management.md
```

## Current Prompt Creation Status

Created first:

- Standard prompt template.
- Governance review prompts.
- Phase 2 foundation prompts.

Created for the next roadmap gate:

- Phase 3 Multi-Tenant Foundation readiness review.
- Phase 3 tenancy-design documentation remediation.
- Phase 3 core TenantContext implementation.
- Phase 3 TenantScope and School Settings schema implementation.
- Phase 3 School Management implementation.
- Phase 3 School Settings workflow implementation.
- Phase 4 Roles & Permissions readiness review.
- Phase 4 RBAC design documentation remediation.
- Phase 4 core native RBAC foundation implementation.
- Phase 4 Role & Permission dashboard-management implementation (executed).
- Phase 5 Academic Structure readiness review (executed and approved).
- Phase 5 Academic Structure design remediation (executed).
- Phase 5 core Academic Structure schema and tenant-model foundation (executed).
- Phase 5 Academic Year and Academic Term management (executed).
- Phase 5 Teacher Profile management (executed).
- Phase 5 Class management (executed).
- Phase 5 Section and Subject management (executed).
- Phase 6 Student Management readiness review (executed and approved at 10/10).
- Phase 6 Student Management design remediation (executed).
- Phase 6 core Student schema and model foundation (executed).
- Phase 6 Student profile management (executed).
- Phase 6 Enrollment management (executed).

Future prompt groups should be added only when they are needed for the next roadmap phase.

## Usage Workflow

Use prompts in small increments:

1. Run the relevant governance review prompt.
2. Run the phase readiness prompt.
3. Run one implementation prompt at a time.
4. Run the matching test prompt.
5. Run the documentation update prompt.
6. Run the review gate before moving to the next phase.

Do not combine unrelated modules into one prompt.

## Global Prompt Rules

- Use Laravel 13, PHP 8.4, MySQL 8.
- Use Blade Templates, Bootstrap 5, Bootstrap Icons, and Chart.js.
- Use Laravel Breeze only when executing the approved authentication phase.
- Use Native Laravel Multi-Tenancy with `school_id`.
- Do not introduce Stancl Tenancy, Spatie Multitenancy, React, Vue, Inertia, Livewire, Tailwind CSS, microservices, CQRS, or event sourcing without an approved decision in `../docs/DECISIONS_LOG.md`.
- Keep controllers thin.
- Put workflows in services.
- Use Form Requests for validation.
- Use Policies, Gates, and Middleware for authorization.
- Use tenant-aware models and service-layer validation.
- Require tenant isolation tests for tenant-owned modules.
- Update documentation when implementation changes architecture, database design, permissions, security, tests, UI, roadmap status, or user-visible behavior.

## Phase 2 Scope

Phase 2 is defined by `../docs/DEVELOPMENT_ROADMAP.md` as:

```text
Phase 2 - Authentication & User Management
```

Phase 2 may create schema dependencies required by authentication and user management, but it must not implement later-phase module behavior:

- School Management UI belongs to Phase 3.
- Full Tenant Foundation belongs to Phase 3.
- Full Role and Permission Management belongs to Phase 4.
- Academic, student, attendance, fee, examination, reporting, backup, and MCA report generation belong to later phases.

## Safety Notes

Prompts in this folder may instruct future agents to implement code when they are explicitly used for implementation. Creating or editing these prompt files does not authorize implementation work.

When in doubt, stop and run a governance review prompt before making changes.
