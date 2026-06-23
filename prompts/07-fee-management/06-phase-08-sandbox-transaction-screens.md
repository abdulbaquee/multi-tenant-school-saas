# Phase 8 Sandbox Transaction Screens

## Role

Act as a senior Laravel architect, security architect, database architect,
financial-workflow analyst, testing specialist, documentation maintainer, and
MCA project reviewer for this repository.

## Objective

Implement the approved Phase 8 operational sandbox Payment Transaction list and
detail screens without implementing reports, exports, analytics, dashboard
widgets, production gateway integration, or transaction mutation workflows.

## Execution Mode

Implementation. Modify only the application, tests, views, routes, and
documentation needed for sandbox transaction operational screens.

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
- `prompts/07-fee-management/05-phase-08-payment-history-outstanding-balances.md`

## Prerequisites

Verify before implementation:

- Phase 8 setup, assignment, collection, receipt, payment-history, and
  outstanding-balance workflows are implemented.
- Focused payment-history/outstanding tests and the full suite pass before
  sandbox screen changes.
- DECISION-034 remains the active Fee Management boundary.

## Scope

Implement:

- School Admin and Accountant tenant-safe sandbox Payment Transaction list and
  detail screens for `sandbox_gateway` records only.
- Privacy-minimized Student identifiers, sanitized payload display, and links to
  existing Fee Payment receipts.
- Policies, Form Requests, Services, controllers, routes, Blade views, navigation,
  and tests required for these read-only workflows.
- Accountant denial for setup and assignment routes remains unchanged.
- Documentation evidence updates after verification.

## Out Of Scope

Do not implement:

- Fee reports, exports, analytics, charts, dashboard widgets, Super Admin platform
  summaries, or Phase 10 Reporting.
- Payment deletion, reversal UI, external gateway integration, or mutation of
  retained Payment Transaction rows.
- Non-sandbox transaction operational screens beyond existing receipt visibility.
- Phase 8 release gate reviews (run as the next checkpoint after this prompt).

## Required Workflow

1. Inspect existing Phase 8 read-view patterns and collection sandbox payload
   generation.
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

- School Admin and Accountant can review sandbox Payment Transactions inside the
  active tenant only.
- Teacher, Super Admin, guests, inactive users/schools, Platform context,
  Unresolved context, and cross-tenant actors are denied.
- Screens expose only privacy-minimized operational data and sanitized sandbox
  payload fields.
- No export, report, analytics, dashboard widget, production gateway, or mutation
  behavior is introduced.

## Stop Conditions

Stop and report instead of guessing if:

- These views require a schema field not approved in `DATABASE_DESIGN.md`.
- Tenant isolation cannot be enforced without weakening `BelongsToTenant`.
- Phase 10 reporting work becomes necessary to complete this scope.
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
