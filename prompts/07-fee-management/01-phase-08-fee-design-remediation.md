# Phase 8 Fee Management Design Remediation

## Role

Act as a senior Laravel architect, security architect, database architect,
financial-workflow analyst, RBAC reviewer, documentation maintainer, testing
specialist, and MCA project reviewer for this repository.

## Objective

Resolve the Phase 8 readiness blockers before generating Fee Management
migrations, models, controllers, services, views, or workflow tests.

## Execution Mode

Documentation and RBAC alignment only. Do not implement Fee Management
application workflows.

## Authoritative Documents

Read and follow:

- `AGENTS.md`
- `app/AGENTS.md`
- `tests/AGENTS.md`
- `docs/AGENTS.md`
- `docs/PROJECT_CONSTITUTION.md`
- `docs/MCA_SUBMISSION_MASTER_PLAN.md`
- `docs/PROJECT_GOVERNANCE.md`
- `docs/DECISIONS_LOG.md`
- `docs/DEVELOPMENT_ROADMAP.md`
- `docs/SYSTEM_ARCHITECTURE.md`
- `docs/TENANCY_DESIGN.md`
- `docs/DATABASE_DESIGN.md`
- `docs/ER_DIAGRAM.md`
- `docs/MODULE_SPECIFICATIONS.md`
- `docs/SCREEN_FLOW.md`
- `docs/SECURITY_GUIDELINES.md`
- `docs/TESTING_STRATEGY.md`
- `docs/UI_UX_DESIGN_SYSTEM.md`
- `docs/CODING_STANDARDS.md`
- `docs/CHANGELOG.md`

## Scope

Allowed work:

- Add a Phase 8 Fee Management decision to `docs/DECISIONS_LOG.md`.
- Reconcile Fee Management scope in roadmap, module, database, screen-flow,
  tenancy, security, testing, MCA, and changelog documentation.
- Clearly defer Fee reports, exports, analytics, dashboards, and platform
  summaries to Phase 10.
- Define Phase 8 operational screens: setup, assignment, collection, receipts,
  payment history, outstanding balance, and deterministic sandbox transactions.
- Define School Admin, Accountant, Super Admin, Teacher, guest, and inactive
  context boundaries.
- Align `config/rbac.php` so Accountant does not receive setup, delete, report,
  or export Fee permissions by default.
- Add or update focused RBAC regression tests for the permission boundary.
- Add the remediation prompt to `prompts/README.md`.

## Out Of Scope

Do not:

- Create Fee migrations, models, policies, services, controllers, requests,
  views, routes, factories, seeders, or workflow tests.
- Add production payment gateway integration, external SDKs, packages, webhooks,
  secrets, or network dependencies.
- Implement reports, exports, analytics, charts, dashboard widgets, platform
  summaries, parent/student portals, notifications, fines, scholarships,
  payroll, inventory, accounting-ledger workflows, or real bank/card/UPI
  collection.
- Change Student, Enrollment, Attendance, Examination, Reporting, Backup, or
  final-submission implementation outside documented Fee dependencies.

## Required Decisions To Record

Document that Phase 8 implements:

- School Admin setup for Fee Categories and Fee Structures.
- School Admin Student Fee assignment from active same-tenant Fee Structures to
  eligible active Student Enrollment records.
- School Admin and Accountant Student Fee lookup, collection, receipt viewing,
  payment history, and outstanding balance operations.
- Accountant collection access only; no Fee setup administration, delete,
  report, export, or general Student route.
- No Phase 8 Super Admin or Teacher Fee route.
- Operational payment history and outstanding balances only; Fee reports,
  exports, analytics, dashboard widgets, and platform summaries remain Phase 10.
- Payments and transactions are retained immutable financial history; corrections
  require status/reversal evidence and no hard deletion.
- Sandbox payment uses deterministic local transaction records with sanitized
  payloads and no external gateway, secrets, or packages.
- Fee workflows must be tenant-derived, service-validated, privacy-safe,
  transaction-safe, and test-covered.

## Acceptance Criteria

The remediation is complete when:

- The Phase 8 implementation boundary is explicit enough for schema work without
  guessing.
- Accountant, School Admin, Super Admin, and Teacher Fee boundaries are
  consistent across docs and RBAC defaults.
- Fee reports/export/analytics/dashboard work is consistently deferred to Phase
  10.
- Payment amount, balance, receipt, transaction, sandbox, retention, and privacy
  rules are documented.
- Relevant RBAC tests pass, and the full suite remains green.

## Required Final Response

Provide:

- Files modified.
- Decisions recorded.
- RBAC changes.
- Tests or validation run.
- Whether Phase 8 readiness should be rerun.
