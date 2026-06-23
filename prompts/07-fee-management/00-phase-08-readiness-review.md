# Phase 8 Fee Management Readiness Review

## Role

Act as a senior Laravel architect, SaaS architect, security architect, database
architect, financial-workflow analyst, testing specialist, documentation
maintainer, and MCA project reviewer for this repository.

## Objective

Determine whether the repository is ready to begin Phase 8: Fee Management
without making undocumented fee setup, student-fee assignment, collection,
receipt, payment-transaction, sandbox-payment, accountant-access, reporting,
tenant-boundary, authorization, audit, or database decisions during
implementation.

## Execution Mode

Audit only. Do not modify files.

## Authoritative Documents

Read and follow:

- `AGENTS.md`
- `app/AGENTS.md`
- `database/AGENTS.md`
- `tests/AGENTS.md`
- `resources/AGENTS.md`
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

## Phase 8 Candidate Scope

Audit readiness for these candidate Phase 8 entities and workflows:

- Tenant-owned `fee_categories`, `fee_structures`, `student_fees`,
  `fee_payments`, and `payment_transactions` tables and models.
- Fee Category setup for active own-tenant schools.
- Fee Structure setup by Academic Year, Class, category, amount, due date,
  frequency, and active/inactive status.
- Student Fee assignment from approved Fee Structures to eligible active
  Student Enrollment records.
- Student fee lookup for School Admin and Accountant without exposing unrelated
  Student private data.
- Fee Collection with partial payments, full payments, balance updates,
  school-specific receipt numbers, payment modes, and payment history.
- Receipt viewing/printing for collected payments.
- Demonstrable sandbox transaction path without a production payment gateway.
- Activity logs, audit logs, payment-retention evidence, transaction rollback,
  and privacy-safe descriptions.
- Role boundaries for School Admin, Accountant, Super Admin, Teacher, guests,
  inactive schools, unresolved context, and cross-tenant access.
- Mandatory allowed-path, denied-path, tenant-isolation, forged-parent,
  lifecycle, payment-balance, receipt, transaction, logging-failure, and
  rollback tests.
- Documentation updates after implementation.

The review must determine the final Phase 8 boundary. Candidate scope is not
permission to implement every listed item.

## Out Of Scope

Do not implement or approve implementation of:

- Production payment gateway integration, external payment SDKs, webhooks,
  real card/UPI collection, bank APIs, or package installation.
- General Financial Reports, dashboard analytics, exports, charts, or platform
  summaries if governance assigns them to Phase 10.
- Parent portal, Student portal, notifications, SMS/email receipts, recurring
  billing automation, fines, refunds beyond a documented simple reversal path,
  scholarships, payroll, inventory, or accounting-ledger features.
- Changes to Student identity, Enrollment placement, Attendance, Examination,
  Reporting, Backup, or final-submission workflows outside documented Fee
  dependencies.
- Application code, migrations, tests, views, or non-prompt documentation
  changes during this audit.
- New packages, frontend frameworks, tenancy libraries, or architectural
  patterns beyond the approved layered monolith.

## Review Criteria

Verify:

- Phase 7 Attendance is release-approved, committed, pushed, and passing tests,
  with approval recorded consistently in authoritative documents.
- Phase 8 scope agrees across the roadmap, module specifications, database
  design, ERD, screen flow, permission matrix, security, testing, menus, and
  current implementation.
- Governance resolves the boundary between Phase 8 operational payment history
  and Phase 10 Fee Reports, exports, analytics, charts, dashboards, and platform
  summaries.
- Governance resolves whether Super Admin has any Phase 8 fee route or only
  later Phase 10 reporting visibility.
- Governance resolves whether Accountant can manage Fee Categories and Fee
  Structures, or whether Accountant is limited to Student Fee lookup,
  collection, receipts, and payment history.
- The documented Student lookup boundary for Accountant is privacy-minimized
  and tied only to fee workflows.
- The schema has complete columns, indexes, unique constraints, restricted
  foreign keys, soft-delete rules, retention rules, decimal precision, status
  values, and tenant ownership for all Fee tables.
- Fee setup records derive `school_id` from `TenantContext`, use
  `BelongsToTenant`, and reject forged School, Academic Year, Class, Category,
  Student, Student Fee, Payment, Transaction, and collector identifiers.
- Fee Structure rules are explicit for active/inactive Academic Years, current
  versus historical years, active/inactive Classes, duplicate category/year/class
  combinations, and allowed frequencies.
- Student Fee assignment rules are explicit for eligible active Students,
  retained completed/transferred/graduated Students, Enrollment state, class
  matching, duplicate assignment, discounts, payable amount, due dates, waivers,
  cancellation, and soft-deletion limits.
- Payment rules are explicit for zero/negative/overpayment amounts, partial
  payments, full payments, paid/cancelled/waived assignments, payment dates,
  payment modes, receipt-number generation, idempotency, and repeated
  submissions.
- Financial history retention is explicit: payments and transactions must not be
  hard-deleted, and any correction/reversal path must preserve audit evidence.
- Sandbox payment behavior is deterministic, demo-friendly, and does not require
  network access, secrets, real credentials, or an external gateway.
- Services, Form Requests, Policies, route-model binding, menus, direct service
  calls, and views enforce tenant, permission, role, record, lifecycle, and
  context scope.
- Activity and audit event names, module names, subjects, descriptions, old/new
  values, and privacy-safe payloads are documented for setup, assignment,
  collection, payment status change, transaction creation, and receipt access.
- Fee workflows never log or expose unnecessary minor data, guardian contact
  details, full addresses, raw payment notes, secrets, gateway tokens, or raw
  unsanitized gateway responses.
- Required allowed, denied, cross-tenant, forged-parent, duplicate,
  idempotency, balance, payment-mode, receipt, sandbox-transaction, retention,
  audit, rollback, and automatic-scope tests can be specified without guessing.
- No package or architecture beyond the approved layered monolith and native
  Laravel tenancy is required.

## Required Workflow

1. Inspect repository status, Phase 7 release evidence, migration state, and the
   full test suite.
2. Compare Fee scope across governance, roadmap, database, ERD, modules, screen
   flow, permissions, security, testing, UI, and coding standards.
3. Inspect existing TenantContext, `BelongsToTenant`, Policies, services, route
   binding, activity/audit logging, decimal-money conventions, and test
   conventions.
4. Trace Fee dependencies through Schools, Academic Years, Classes, Students,
   Enrollments, Users, Roles, Permissions, and Activity/Audit models.
5. Test the documented uniqueness, balance-update, receipt-number,
   transaction-retention, and retry model against database constraints and
   repeated submissions.
6. Verify role scope against `config/rbac.php` and the canonical permission
   matrix.
7. Separate Phase 8 operational screens from Phase 10 reports, exports,
   analytics, and dashboard work.
8. Run non-mutating validation commands and the existing test suite where
   available.
9. Classify every issue as blocking or non-blocking and recommend the exact
   remediation and prompt execution order.

## Deliverables

- Phase 8 readiness score from 1 to 10.
- Evidence reviewed.
- Findings ordered by severity.
- Blocking issues and non-blocking improvements.
- Documentation contradictions or missing decisions.
- Database, relationship, assignment, collection, receipt, transaction, and
  retention assessment.
- Security, tenant-isolation, accountant-access, privacy, and audit assessment.
- Recommended final Phase 8 scope.
- Recommended Phase 8 prompt execution order.
- Final decision: READY, READY WITH MINOR IMPROVEMENTS, REQUIRES REMEDIATION, or
  NOT READY.

## Acceptance Criteria

The review is complete when:

- The repository is either cleared for Phase 8 implementation or blocked with
  exact remediation steps.
- All Phase 8 scope conflicts are identified before code is generated.
- Reporting, exports, analytics, production gateway integration, and later-phase
  work are either explicitly deferred or explicitly justified by authoritative
  documents.
- Accountant, School Admin, Super Admin, and Teacher boundaries are explicit.
- Tenant isolation, privacy, financial retention, and testing expectations are
  clear enough to implement without guessing.

## Stop Conditions

Stop and report instead of guessing if:

- Required documentation conflicts.
- The database design is insufficient for safe financial workflows.
- Tenant isolation or payment-history retention cannot be enforced safely.
- The task requires a real payment gateway, external package, secrets, or
  architecture change.
- The requested scope belongs to Phase 10 or a later roadmap phase.

## Required Final Response

Provide:

- Readiness score and gate decision.
- Blocking issues.
- Non-blocking improvements.
- Recommended remediation prompt if needed.
- Recommended implementation prompt order.
- Tests or validation run.
