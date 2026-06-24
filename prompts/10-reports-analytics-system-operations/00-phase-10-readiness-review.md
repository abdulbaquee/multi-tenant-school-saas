# Phase 10 Reports, Analytics & System Operations Readiness Review

## Role

Act as a senior Laravel architect, SaaS architect, security architect, database
architect, reporting-workflow analyst, testing specialist, documentation
maintainer, and MCA project reviewer for this repository.

## Objective

Determine whether the repository is ready to begin Phase 10: Reports, Analytics
& System Operations without making undocumented reporting, export, dashboard,
activity-log, audit-log, backup, tenant-boundary, authorization, privacy, file
storage, or database decisions during implementation.

This is a readiness and submission-evidence planning prompt. Do not implement
Phase 10 code in this run.

## Execution Mode

Audit only. Do not modify files.

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
- `docs/ER_DIAGRAM.md`
- `docs/MODULE_SPECIFICATIONS.md`
- `docs/SCREEN_FLOW.md`
- `docs/SECURITY_GUIDELINES.md`
- `docs/TESTING_STRATEGY.md`
- `docs/UI_UX_DESIGN_SYSTEM.md`
- `docs/CODING_STANDARDS.md`
- `docs/CHANGELOG.md`
- `docs/MCA_REPORT_NOTES.md`
- `README.md`

## Prerequisites To Verify

Verify before recommending Phase 10 implementation:

- Phase 7 Attendance is release-approved.
- Phase 8 Fee Management is release-approved.
- Phase 9 Examination Management is release-approved.
- Current working tree state is understood and no unrelated dirty files are
  overwritten.
- Existing full suite passes, or failures are documented before Phase 10 begins.
- The MCA submission deadline, feature-freeze rules, screenshot obligations,
  report-writing needs, deployment needs, presentation needs, and viva evidence
  in `docs/MCA_SUBMISSION_MASTER_PLAN.md` are reflected in the Phase 10 plan.

## Phase 10 Candidate Scope

Audit readiness for these candidate Phase 10 entities and workflows:

- Student Reports.
- Attendance Reports.
- Fee Reports.
- Examination Reports.
- Role-specific dashboard analytics and summary widgets.
- Super Admin platform-level report views where explicitly authorized.
- School Admin own-school report views.
- Teacher assigned-class/subject report views.
- Accountant financial report views.
- Activity Log list/detail screens.
- Audit Trail list/detail screens.
- Backup Management screens and private backup history.
- Report filters, search, pagination, summary cards, and browser-safe views.
- Export boundaries for PDF/Excel/CSV only if existing documentation and
  available project dependencies safely support them without package changes.
- Private storage and authorization rules for backup/export files.
- Submission evidence: screenshot plan, report notes, test totals, demo data,
  user-manual inputs, deployment evidence, presentation and viva talking points.

The review must determine the final Phase 10 boundary. Candidate scope is not
permission to implement every listed item.

## Out Of Scope

Do not implement or approve implementation of:

- Parent Portal, Student Portal, mobile applications, SMS, email marketing, AI,
  online exams, or external analytics services.
- Production payment gateway integration.
- External backup providers, queue infrastructure, object storage services, or
  cron/server automation that requires deployment credentials.
- New packages, SDKs, frontend frameworks, tenancy libraries, PDF libraries, or
  Excel libraries unless an existing dependency already supports the need and
  an explicit follow-up implementation prompt approves it.
- Raw export of sensitive minor data, guardian contact details, addresses,
  raw remarks, secrets, tokens, stack traces, or other schools' data.
- Hard deletion of activity logs, audit logs, payments, attendance, exam
  results, report cards, or backup history.
- Schema changes unless the readiness review proves an existing approved table
  is insufficient and recommends an explicit design-remediation prompt first.
- Any Phase 11 final QA, Phase 12 deployment/user-manual work, or Phase 13 final
  submission packaging, except as evidence planning.

## Review Criteria

Verify:

- Phase 10 scope agrees across roadmap, module specifications, database design,
  ERD, screen flow, permission matrix, security guidelines, testing strategy,
  UI design, governance, changelog, and MCA evidence notes.
- Reports inherit the same tenant, role, assignment, and privacy boundaries as
  their source modules.
- Super Admin report access is platform-authorized and never silently bypasses
  tenant scope outside documented service/report paths.
- School Admin report access is own-school only.
- Teacher reports are limited to assigned Attendance sections, assigned Student
  records, assigned Examination class/subject scope, and privacy-minimized data.
- Accountant reports are limited to financial report needs and do not activate
  general Student, Attendance, or Examination management routes.
- Activity Log and Audit Trail screens are least-privilege and privacy-safe.
- Backup Management keeps backup files and metadata private; public storage is
  not used for backups.
- Any export workflow has explicit authorization, tenant filtering, file-storage
  privacy, output-field restrictions, and test coverage.
- Dashboard analytics use server-derived aggregate queries and do not expose
  unauthorized source records.
- Queries are bounded, paginated, indexed where possible, and avoid avoidable
  N+1 behavior.
- Controllers remain thin; services build report datasets; Form Requests
  validate filters; Policies authorize report/log/backup actions.
- Activity/audit/backup histories are retained and not overwritten.
- Report and backup actions write privacy-safe activity/audit evidence when
  appropriate.
- Tests can be specified for allowed paths, denied roles, cross-tenant access,
  assignment scope, forged filters, export authorization, backup privacy,
  pagination/search, and query/data correctness.
- MCA screenshot and report evidence can be captured from sanitized demo data.

## Required Workflow

1. Inspect repository status and summarize any existing dirty files before
   reviewing.
2. Verify Phase 7, Phase 8, and Phase 9 release approvals are recorded
   consistently.
3. Compare Phase 10 scope across the authoritative docs listed above.
4. Inspect existing report-like patterns, dashboard services, policies, Form
   Requests, activity/audit models, backup-related docs/schema, navigation, and
   test conventions.
5. Trace source data boundaries through Students, Attendance, Fees, Exams,
   Report Cards, Activity Logs, Audit Logs, Users, Roles, Schools, and
   TenantContext.
6. Identify required decisions before implementation: report list, export
   format, backup workflow, dashboard widgets, role visibility, file privacy,
   and screenshot evidence.
7. Separate work into safe implementation prompts small enough for Cursor to run
   quickly and for Codex to review afterward.
8. Run non-mutating validation commands if practical:
   - `git status --short`
   - `/opt/homebrew/bin/php artisan test --compact`
   - `/opt/homebrew/bin/php artisan route:list`
   - `git diff --check`
9. Classify every issue as blocking or non-blocking and recommend exact
   remediation or implementation prompt order.

## Required Phase 10 Prompt Plan

Recommend a prompt sequence. Prefer small, reviewable prompts such as:

1. Phase 10 design remediation, if readiness finds contradictions.
2. Core reporting foundation and shared report filters.
3. Student/Attendance report screens.
4. Fee/Examination report screens.
5. Dashboard analytics widgets.
6. Activity Log and Audit Trail review screens.
7. Backup Management and private backup history.
8. Phase 10 submission evidence update.
9. Phase 10 release gate reviews.

Adjust this order if the audit finds a safer or faster path.

## Deliverables

Provide:

- Phase 10 readiness score from 1 to 10.
- Gate decision: READY, READY WITH MINOR IMPROVEMENTS, REQUIRES REMEDIATION, or
  NOT READY.
- Evidence reviewed.
- Existing dirty-file summary.
- Findings ordered by severity.
- Blocking issues and non-blocking improvements.
- Documentation contradictions or missing decisions.
- Recommended final Phase 10 boundary.
- Recommended Phase 10 prompt execution order.
- Submission evidence plan:
  - screenshots to capture,
  - MCA report sections affected,
  - presentation/demo talking points,
  - viva questions to prepare,
  - sanitized dummy data needed.
- Tests or validation commands run.
- Exact next prompt to execute.

## Acceptance Criteria

The review is complete when:

- The repository is either cleared for Phase 10 implementation or blocked with
  exact remediation steps.
- Reports, analytics, logs, backups, exports, and evidence obligations are
  separated into safe implementation chunks.
- Role and tenant boundaries are explicit for every proposed Phase 10 feature.
- Export and backup privacy decisions are explicit before code is generated.
- Required test coverage is clear enough to implement without guessing.
- No application code, migration, route, view, service, or test file is changed
  during this readiness run.

## Stop Conditions

Stop and report instead of guessing if:

- Required documentation conflicts.
- Export or backup implementation requires an unapproved package, external
  service, credential, storage provider, or architecture change.
- Tenant isolation or minor-data privacy cannot be preserved.
- Existing release approvals are missing or inconsistent.
- The current full suite fails before Phase 10 work begins.
- The requested scope belongs to Phase 11, Phase 12, or Phase 13 rather than
  Phase 10.

## Required Final Response

Provide:

- Readiness score and gate decision.
- Blocking issues.
- Non-blocking improvements.
- Recommended remediation prompt if needed.
- Recommended implementation prompt order.
- Submission evidence plan.
- Tests or validation run.
- Exact next task.
