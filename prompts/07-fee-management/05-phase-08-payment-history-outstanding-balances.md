# Phase 8 Payment History And Outstanding Balances

## Role

Act as a senior Laravel architect, security architect, database architect,
financial-workflow analyst, testing specialist, documentation maintainer, and
MCA project reviewer for this repository.

## Objective

Implement the approved Phase 8 operational payment-history and outstanding-balance
views without implementing sandbox operational screens, reports, exports,
analytics, dashboard widgets, or production payment integration.

## Execution Mode

Implementation. Modify only the application, tests, views, routes, and
documentation needed for payment history and outstanding balance views.

## Authoritative Documents

Read and follow:

- `AGENTS.md`
- `app/AGENTS.md`
- `database/AGENTS.md`
- `tests/AGENTS.md`
- `resources/AGENTS.md`
- `docs/AGENTS.md`
- `docs/PROJECT_CONSTITUTION.md`
- `docs/MCA_SUBMISSION_MASTER_PLAN.md`
- `docs/PROJECT_GOVERNANCE.md`
- `docs/DECISIONS_LOG.md`
- `docs/DEVELOPMENT_ROADMAP.md`
- `docs/SYSTEM_ARCHITECTURE.md`
- `docs/TENANCY_DESIGN.md`
- `docs/DATABASE_DESIGN.md`
- `docs/MODULE_SPECIFICATIONS.md`
- `docs/SCREEN_FLOW.md`
- `docs/SECURITY_GUIDELINES.md`
- `docs/TESTING_STRATEGY.md`
- `docs/UI_UX_DESIGN_SYSTEM.md`
- `docs/CODING_STANDARDS.md`
- `prompts/07-fee-management/04-phase-08-fee-collection-receipts.md`

## Prerequisites

Verify before implementation:

- Phase 8 setup, assignment, collection, and receipt workflows are implemented.
- Focused collection/receipt tests and the full suite pass before view changes.
- DECISION-034 remains the active Fee Management boundary.

## Scope

Implement:

- School Admin and Accountant tenant-safe payment-history list and detail access
  linked to existing receipt views.
- School Admin and Accountant outstanding-balance operational list with on-screen
  summary totals only.
- Privacy-minimized Student identifiers and no export/report controls.
- Policies, Form Requests, Services, controllers, routes, Blade views, navigation,
  and tests required for these read-only workflows.
- Accountant denial for setup and assignment routes remains unchanged.
- Documentation evidence updates after verification.

## Out Of Scope

Do not implement:

- Dedicated sandbox transaction screens or sandbox-only workflows beyond existing
  collection/receipt data visibility.
- Fee reports, exports, analytics, charts, dashboard widgets, Super Admin platform
  summaries, or Phase 10 Reporting.
- Payment deletion, reversal UI, or production gateway integration.
- Changes to collection, receipt generation, setup, or assignment behavior except
  read-path dependencies.

## Required Workflow

1. Inspect existing Phase 8 collection/receipt patterns and Attendance operational
   read views.
2. Implement thin controllers, Form Requests, Policies, and Services.
3. Build Bootstrap 5 Blade screens and role-aware navigation only for allowed read
   workflows.
4. Enforce tenant, role, permission, and privacy boundaries in services and
   policies.
5. Write allowed-path, denied-path, cross-tenant, filter, and direct-service
   tests.
6. Run focused tests, full suite, Pint, route inspection, and `git diff --check`.
7. Perform self-review scorecard and PASS/FAIL gate before commit.
8. Update roadmap, testing, security, changelog, screen-flow, MCA evidence, and
   prompt inventory.

## Acceptance Criteria

- School Admin and Accountant can review payment history and outstanding balances
  inside the active tenant only.
- Teacher, Super Admin, guests, inactive users/schools, Platform context,
  Unresolved context, and cross-tenant actors are denied.
- Payment history and outstanding views expose only privacy-minimized operational
  data.
- No export, report, analytics, dashboard widget, sandbox operational screen, or
  production gateway behavior is introduced.

## Stop Conditions

Stop and report instead of guessing if:

- These views require a schema field not approved in `DATABASE_DESIGN.md`.
- Tenant isolation cannot be enforced without weakening `BelongsToTenant`.
- Sandbox operational screens or Phase 10 reporting work becomes necessary to
  complete this scope.
- Existing tests fail before implementation.
- A package or external service appears necessary.

## Required Final Response

Provide:

- Files modified.
- Workflows implemented.
- Authorization and tenant protections.
- Tests and validation run.
- Self-review scorecard with PASS/FAIL verdict.
- Documentation updates.
- Remaining Phase 8 work.
- Exact next task.
