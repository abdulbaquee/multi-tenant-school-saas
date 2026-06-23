# Phase 8 Fee Setup And Student Fee Assignment

## Role

Act as a senior Laravel architect, security architect, database architect,
financial-workflow analyst, testing specialist, documentation maintainer, and
MCA project reviewer for this repository.

## Objective

Implement the approved Phase 8 Fee Category, Fee Structure, and Student Fee
assignment workflows without implementing payment collection, receipts, payment
history screens, outstanding-balance dashboards, reports, exports, analytics, or
production payment integration.

## Execution Mode

Implementation. Modify only the application, tests, views, routes, and
documentation needed for Fee setup and Student Fee assignment.

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
- `prompts/07-fee-management/02-phase-08-core-fee-schema.md`

## Prerequisites

Verify before implementation:

- Phase 8 core Fee schema and tenant-model foundation are implemented.
- Focused Fee schema tests and the full suite pass before workflow changes.
- DECISION-034 remains the active Fee Management boundary.

## Scope

Implement:

- School Admin Fee Category list, create, update, deactivate/reactivate, and
  tenant-safe visibility.
- School Admin Fee Structure list, create, update non-scope values,
  deactivate/reactivate, and tenant-safe visibility.
- School Admin Student Fee assignment from active same-tenant Fee Structures to
  eligible active Student Enrollment records matching Academic Year and Class.
- Student Fee assignment list/detail views with privacy-minimized Student
  identifiers and balance fields.
- Policies, Form Requests, Services, controllers, routes, Blade views,
  navigation, activity logs, audit logs, and tests required for these workflows.
- Accountant denial for setup and assignment workflows.
- Documentation evidence updates after verification.

## Out Of Scope

Do not implement:

- Fee Collection, receipts, receipt printing, payment history screens,
  outstanding balance operational screens, sandbox payment processing, payment
  transactions, reversals, or collection audit workflows.
- Fee reports, exports, analytics, dashboard widgets, charts, Super Admin
  platform summaries, or Phase 10 Reporting.
- Production payment gateways, external SDKs, webhooks, real card/UPI
  collection, secrets, packages, parent/student portals, notifications,
  scholarships, fines, payroll, inventory, or accounting-ledger features.
- Changes to Student identity, Enrollment placement, Attendance, Examination,
  Backup, or final-submission workflows outside documented Fee dependencies.

## Required Workflow

1. Inspect existing Phase 8 schema/models and prior module workflow patterns.
2. Implement thin controllers, Form Requests, Policies, and Services.
3. Build Bootstrap 5 Blade screens and role-aware navigation only for allowed
   setup and assignment workflows.
4. Enforce tenant, role, permission, lifecycle, assignment, and parent-record
   validation in services and policies.
5. Write allowed-path, denied-path, forged-parent, cross-tenant, rollback,
   privacy-safe log, and direct-service tests.
6. Run focused tests, full suite, Pint, route inspection, Composer validation,
   and `git diff --check`.
7. Update roadmap, testing, security, changelog, screen-flow, MCA evidence, and
   prompt inventory.

## Acceptance Criteria

- School Admin can manage Fee Categories, Fee Structures, and Student Fee
  assignments inside the active tenant only.
- Accountant, Teacher, Super Admin, guests, inactive users/schools, Platform
  context, Unresolved context, and cross-tenant actors are denied.
- Student Fee amount, discount, payable, paid, and balance values are
  server-derived and validated before persistence.
- Fee setup scope fields and Student Fee assignment identity remain immutable.
- Activity and audit evidence is tenant-owned and privacy-safe.
- No payment collection, report, export, dashboard, or production gateway
  behavior is introduced.

## Stop Conditions

Stop and report instead of guessing if:

- Fee setup or assignment requires a schema field not approved in
  `DATABASE_DESIGN.md`.
- Tenant isolation cannot be enforced without weakening `BelongsToTenant`.
- Payment collection or reporting work becomes necessary to complete this scope.
- Existing tests fail before implementation.
- A package or external service appears necessary.

## Required Final Response

Provide:

- Files modified.
- Workflows implemented.
- Authorization and tenant protections.
- Tests and validation run.
- Documentation updates.
- Remaining Phase 8 work.
- Exact next task.
