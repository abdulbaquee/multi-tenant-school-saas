# Phase 8 Fee Collection And Receipts

## Role

Act as a senior Laravel architect, security architect, database architect,
financial-workflow analyst, testing specialist, documentation maintainer, and
MCA project reviewer for this repository.

## Objective

Implement the approved Phase 8 Fee Collection and receipt viewing/printing
workflows without implementing dedicated payment-history screens,
outstanding-balance dashboards, reports, exports, analytics, or production
payment integration.

## Execution Mode

Implementation. Modify only the application, tests, views, routes, and
documentation needed for Fee collection and receipts.

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
- `prompts/07-fee-management/03-phase-08-fee-setup-assignment.md`

## Prerequisites

Verify before implementation:

- Phase 8 setup and Student Fee assignment workflows are implemented.
- Focused setup/assignment tests and the full suite pass before collection changes.
- DECISION-034 remains the active Fee Management boundary.

## Scope

Implement:

- School Admin and Accountant collectible Student Fee lookup within the active
  tenant only.
- Fee Collection form and atomic collection workflow against pending or partial
  Student Fees with positive balance.
- Server-derived receipt and transaction numbers, balance updates, and status
  transitions (`partial`, `paid`).
- Retained Fee Payment and Payment Transaction rows created in the same database
  transaction with privacy-safe activity and audit evidence.
- Receipt detail and print-friendly views.
- Policies, Form Requests, Services, controllers, routes, Blade views,
  navigation updates, and tests required for these workflows.
- Accountant denial for setup and assignment routes remains unchanged.
- Documentation evidence updates after verification.

## Out Of Scope

Do not implement:

- Dedicated payment-history list/detail screens beyond receipt detail.
- Outstanding balance operational dashboards or widgets.
- Fee reports, exports, analytics, charts, Super Admin platform summaries, or
  Phase 10 Reporting.
- Production payment gateways, external SDKs, webhooks, real card/UPI
  collection, secrets, packages, payment reversal UI, or payment deletion.
- Changes to Fee setup or assignment scope outside collection dependencies.

## Required Workflow

1. Inspect existing Phase 8 schema/models and setup/assignment workflow patterns.
2. Implement thin controllers, Form Requests, Policies, and Services.
3. Build Bootstrap 5 Blade screens and role-aware navigation only for allowed
   collection and receipt workflows.
4. Enforce tenant, role, permission, balance, date, mode, and parent-record
   validation in services and policies.
5. Write allowed-path, denied-path, forged-parent, cross-tenant, rollback,
   privacy-safe log, and direct-service tests.
6. Run focused tests, full suite, Pint, route inspection, and `git diff --check`.
7. Perform self-review scorecard and PASS/FAIL gate before commit.
8. Update roadmap, testing, security, changelog, screen-flow, MCA evidence, and
   prompt inventory.

## Acceptance Criteria

- School Admin and Accountant can collect payments inside the active tenant only.
- Teacher, Super Admin, guests, inactive users/schools, Platform context,
  Unresolved context, and cross-tenant actors are denied.
- Payment amount, balance, receipt number, transaction number, collector, and
  status values are server-derived and validated before persistence.
- Collection is atomic across Student Fee, Fee Payment, Payment Transaction,
  activity log, and audit log.
- Receipt viewing and printing are tenant-safe and privacy-minimized.
- No payment-history dashboard, outstanding-balance dashboard, report, export, or
  production gateway behavior is introduced.

## Stop Conditions

Stop and report instead of guessing if:

- Collection requires a schema field not approved in `DATABASE_DESIGN.md`.
- Tenant isolation cannot be enforced without weakening `BelongsToTenant`.
- Payment-history or reporting work becomes necessary to complete this scope.
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
