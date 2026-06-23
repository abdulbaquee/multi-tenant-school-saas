# Phase 8 Core Fee Schema

## Role

Act as a senior Laravel architect, database architect, multi-tenant SaaS
architect, security architect, financial-workflow reviewer, testing specialist,
and MCA project reviewer for this repository.

## Objective

Implement the approved Phase 8 Fee Management schema and tenant-aware model
foundation without introducing user-facing Fee workflows.

## Execution Mode

Implementation. Create only the approved migration, models, relationships,
integrity safeguards, focused tests, and synchronized implementation evidence.

## Authoritative Documents

Read and follow:

- `AGENTS.md`
- `app/AGENTS.md`
- `database/AGENTS.md`
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
- `docs/SECURITY_GUIDELINES.md`
- `docs/TESTING_STRATEGY.md`
- `docs/CODING_STANDARDS.md`
- `prompts/07-fee-management/01-phase-08-fee-design-remediation.md`

## Prerequisites

Verify before implementation:

- Phase 7 is committed, pushed, and release-approved.
- DECISION-034 is recorded and docs are aligned to it.
- Phase 8 readiness rerun is approved with no blocking issues.
- Existing tests pass before schema changes.
- No Fee Management implementation already exists.

## Scope

Implement:

- One reversible migration creating `fee_categories`, `fee_structures`,
  `student_fees`, `fee_payments`, and `payment_transactions` exactly as
  documented in `DATABASE_DESIGN.md`.
- Every documented column, type, default, named index, unique constraint, and
  `restrictOnDelete()` foreign key.
- Soft deletes only on `fee_categories`, `fee_structures`, and `student_fees`.
- No soft deletes on `fee_payments` or `payment_transactions`.
- Canonical tenant-aware models: `FeeCategory`, `FeeStructure`, `StudentFee`,
  `FeePayment`, and `PaymentTransaction`.
- Fillable workflow fields excluding tenant ownership, canonical status and
  payment-mode constants, money casts, date/datetime casts, and documented
  Eloquent relationships.
- Inverse Fee relationships on School, AcademicYear, SchoolClass, Student, and
  User where appropriate.
- Model-level integrity safeguards that keep tenant ownership and financial
  identity fields immutable after creation.
- Deletion guards that prevent deleting retained Fee Payment and Payment
  Transaction history.
- Focused schema, index, relationship, casting, retained-history,
  automatic-scope, ownership, unresolved-context, Platform-read, immutable-
  ownership, unique-constraint, and foreign-key tests.
- Documentation implementation-status and test-evidence updates after
  verification.

## Tenant And Integrity Requirements

- `school_id` is never fillable and is always derived from TenantContext.
- Tenant-context creation overwrites forged `school_id` with the context school.
- Unresolved and Platform context cannot create Fee records.
- Unresolved context reads no Fee records.
- Tenant context reads only its school records.
- Explicit Platform context may read all records at the model foundation layer;
  future HTTP access still requires Policies and services.
- Changing `school_id` after creation throws the existing controlled tenant-
  ownership exception.
- All foreign keys use `restrictOnDelete()` and never cascade.
- Foreign keys do not replace future same-tenant, lifecycle, authorization,
  payment-balance, receipt, or transaction validation in the service layer.

## Fee Integrity Contract

- `fee_categories.school_id + name` is unique.
- `fee_structures.school_id + fee_category_id + academic_year_id + class_id` is
  unique.
- `student_fees.student_id + fee_structure_id` is unique.
- `fee_payments.school_id + receipt_no` is unique.
- `payment_transactions.school_id + transaction_no` is unique.
- Fee setup and Student Fee records may be soft deleted where documented, but
  paid financial history must remain retained.
- Fee Payments and Payment Transactions cannot be deleted.
- Canonical Student Fee statuses are `pending`, `partial`, `paid`, `waived`,
  and `cancelled`.
- Canonical payment statuses are `completed`, `pending`, `failed`, and
  `reversed`.
- Canonical payment modes are `cash`, `card`, `upi`, `bank_transfer`, and
  `sandbox_gateway`.
- Money fields use decimal casts with two decimal places and remain compatible
  with MySQL `DECIMAL(10,2)`.
- Payment and transaction records retain relationships to soft-deleted Student,
  Fee setup, and collector User records where needed.
- This prompt does not implement setup forms, assignment workflows, collection
  workflows, receipt screens, payment-history pages, sandbox processing,
  balance updates, authorization policies, audit/activity writes, or route/UI
  behavior. Those belong to later Phase 8 prompts.

## Required Tests

Prove:

- All five tables and every documented column exist after migration.
- Named indexes and unique constraints match the data dictionary.
- Missing foreign-key parents and hard deletion of referenced parents fail.
- Fee Categories, Fee Structures, and Student Fees use soft deletes.
- Fee Payments and Payment Transactions do not use soft deletes and reject
  deletion.
- Every Fee model derives tenant ownership and filters automatically across
  School A, School B, Platform, and Unresolved context.
- Forged `school_id` cannot override TenantContext.
- Tenant ownership and documented financial identity fields are immutable.
- Canonical relationships and casts resolve inside matching tenant context,
  including retained soft-deleted parents.
- Duplicate category, structure, student-fee, receipt, and transaction rows are
  rejected.
- Existing Phase 2-7 tests remain green.

## Out Of Scope

Do not implement:

- Fee services, Policies, Form Requests, controllers, routes, menus, Blade
  views, setup pages, assignment pages, collection pages, receipt pages, payment
  history, outstanding balances, or sandbox processing.
- School Admin or Accountant HTTP authorization.
- Student Fee lifecycle validation, payment locking, balance updates, receipt
  generation, transaction idempotency, audit logging, or activity logging.
- Fee reports, exports, analytics, dashboards, charts, or platform summaries.
- Production payment gateways, external SDKs, webhooks, secrets, real card/UPI
  collection, packages, triggers, tenancy libraries, or schema outside the
  approved Fee tables and inverse relationships.
- Examination, Reporting, Analytics, Backup, or final-submission workflows.
- Demo seed data or real student/minor/payment data.

## Required Workflow

1. Verify prerequisites and inspect migration, model, relationship, and test
   conventions.
2. Create the migration directly from `DATABASE_DESIGN.md`.
3. Create Fee models with `BelongsToTenant`, immutable identity safeguards, and
   retained-history deletion protection.
4. Add inverse relationships without adding workflow logic.
5. Add focused schema and automatic-isolation tests.
6. Run targeted tests, the full suite, migration verification, Pint, Composer
   validation, and `git diff --check`.
7. Update implementation status, testing evidence, changelog, and prompt index.

## Acceptance Criteria

- The schema and models match the approved data dictionary and DECISION-034.
- Fee tables default to deny without a resolved tenant context.
- Soft-delete and retained-history behavior matches the documented deletion
  policy.
- Financial identity, tenant ownership, receipt, transaction, Student Fee, and
  setup scope cannot drift after creation.
- No user-facing Fee workflow is introduced.
- The complete regression suite passes.
- No package or out-of-scope module is changed.

## Stop Conditions

Stop and report instead of guessing if:

- The database design conflicts with DECISION-034.
- A schema field beyond the approved tables appears necessary.
- Tenant isolation would require weakening `BelongsToTenant` or TenantScope.
- Existing tests fail before implementation.
- A package, trigger, gateway SDK, or cross-tenant mechanism appears necessary.

## Required Final Response

Provide:

- Files modified.
- Tables and models implemented.
- Tenant and integrity protections.
- Tests and validation run.
- Documentation updates.
- Remaining Phase 8 work.
- Exact next task.
