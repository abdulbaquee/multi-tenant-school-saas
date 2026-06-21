# Phase 5 Teacher Profile Management

## Role

Act as a senior Laravel architect, multi-tenant SaaS architect, security
architect, database architect, UI/UX implementer, testing specialist, and MCA
project reviewer for this repository.

## Objective

Implement secure, tenant-aware Teacher Profile management that links existing
eligible Teacher-role users to minimal academic identity, preserves retained
history, supports dependency-safe lifecycle transitions, and remains strictly
inside the approved Phase 5 Academic Structure boundary.

## Execution Mode

Implementation. Modify only the application, routes, Blade views, tests,
documentation, and prompt files required for Teacher Profile management.

## Authoritative Documents

Read and follow:

- `AGENTS.md`
- `app/AGENTS.md`
- `resources/AGENTS.md`
- `tests/AGENTS.md`
- `docs/AGENTS.md`
- `docs/PROJECT_CONSTITUTION.md`
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
- `prompts/04-academic-structure/01-phase-05-academic-design-remediation.md`
- `prompts/04-academic-structure/02-phase-05-core-academic-schema.md`
- `prompts/04-academic-structure/03-phase-05-academic-year-term-management.md`

## Prerequisites

Verify before implementation:

- The Phase 5 readiness review is READY.
- The core Academic Structure schema and tenant-aware models are complete.
- Academic Year and Academic Term management is automated-test clean and
  manually accepted.
- Existing User Management blocks role and school changes while any retained
  Teacher Profile exists.
- No package or schema change is required.

## Scope

Implement:

- `TeacherPolicy` with exact `academic.*` permissions and approved role/context
  boundaries.
- A tenant-aware `TeacherService` for:
  - searchable and filterable current and archived profile directories;
  - platform-wide read-only list and detail access for Super Admin;
  - eligible same-school Teacher-user selection for School Admin;
  - profile creation and academic-field updates;
  - activation, deactivation, archive, and restore workflows;
  - sanitized activity and audit evidence in each domain transaction.
- Form Requests for filters, create, update, and lifecycle actions.
- A thin controller and authenticated `tenant.context` routes under
  `/teacher-profiles`.
- Bootstrap 5 and Bootstrap Icons Blade screens for lists, details, forms,
  validation, empty states, current/archived states, assignments, and lifecycle
  confirmations.
- A Teacher Profiles tab in the existing Academic Structure navigation.
- Feature, authorization, tenant-isolation, linked-user eligibility, lifecycle,
  dependency, rollback, navigation, and responsive-view tests.
- Documentation and changelog updates after verification.

## Access Contract

### Super Admin

- Requires `academic.view`, an active authenticated platform user,
  `school_id = NULL`, and explicit Platform context.
- Can list and view current and archived Teacher Profiles across schools.
- Receives no create, update, activation, deactivation, archive, or restore
  controls.
- Every HTTP and direct-service mutation attempt is denied.

### School Admin

- Requires active-school membership and matching Tenant context.
- Requires exact effective permissions:
  - `academic.view` for directories and details;
  - `academic.create` for linking an eligible Teacher user;
  - `academic.update` for profile edits, activation, and restoration;
  - `academic.delete` for deactivation and dependency-safe archival.
- Can access profiles and eligible users from their own school only.

### Teacher, Accountant, Guest, And Malformed Users

- Receive no Teacher Profile administration directory, menu, or route access.
- Teacher assignment-based Academic Structure reads are implemented later with
  Class, Section, and Subject workflows and do not expose profile management.

## Linked User Contract

- Creation accepts one existing active, non-deleted, same-school User with the
  canonical Teacher role.
- A User with any retained Teacher Profile, including an archived profile, is
  not eligible for another profile.
- `school_id` always comes from TenantContext and is never accepted from input.
- `user_id` is selected only during creation and is immutable afterward.
- Activation and restoration revalidate the linked user's school, active state,
  non-deleted state, and Teacher role.
- The existing User Management role/school-change safeguard must remain active
  for current and archived profiles.

## Profile Data Contract

- Required: linked Teacher user and tenant-unique `employee_code`.
- Optional: `qualification`, `specialization`, `phone`, and `joining_date`.
- Employee code is normalized, remains reserved after archival, and cannot be
  reused by another profile in the school.
- New profiles are active and not archived.
- Requests prohibit `school_id`, `status`, `deleted_at`, and update-time
  `user_id` overrides.

## Lifecycle Contract

- Activation requires a current, inactive, non-archived profile and an eligible
  linked Teacher user.
- Deactivation requires a current active profile and fails while any active,
  non-archived Section or Subject is assigned to the profile.
- Archival requires an inactive, non-archived profile with no active Section or
  Subject assignment. It uses SoftDeletes and preserves identity and history.
- Restoration requires an archived profile and an eligible linked Teacher user.
  It restores the original record as inactive; activation remains a separate
  authorized action.
- No hard-delete, force-delete, or normal DELETE route exists.
- Existing inactive or archived downstream assignments remain retained and are
  displayed as historical references; they are not silently cleared.

## Tenant And Service Requirements

- Teacher Profile queries inherit `BelongsToTenant`; `withTrashed()` must not
  remove TenantScope.
- Hybrid User queries explicitly constrain same-school ownership, active state,
  non-deleted state, and Teacher role.
- Services independently validate actor identity, exact permission, context
  mode, tenant alignment, linked-user eligibility, and dependency state.
- Direct calls reject Unresolved context, wrong context mode, mismatched tenant,
  inactive school, malformed users, and unauthorized Platform mutation.
- Cross-school route IDs and forged User IDs fail without exposing record
  existence.
- Activity module is `academic_structure`; actions are `created`, `updated`,
  `activated`, `deactivated`, `archived`, and `restored`.
- Audit targets the Teacher model and includes only approved changed fields.
- Domain changes and both log records commit or roll back together.

## Out Of Scope

Do not implement:

- User account creation or role assignment inside Teacher Profile forms.
- Class, Section, Subject, timetable, payroll, HR, staff-attendance, leave,
  salary, or general employee management.
- Teacher self-service profile editing or Teacher assignment views.
- Student Enrollment or any Phase 6 module.
- Exports, reports, bulk operations, hard deletes, packages, migrations, or seed
  data changes.

## UI Requirements

- Use the existing authenticated shell and Academic Structure tab navigation.
- Use Blade, Bootstrap 5, and Bootstrap Icons only.
- Keep the directory compact, responsive, searchable, and filterable by active,
  inactive, and archived state.
- Display linked-user identity and school context without exposing passwords or
  unrelated account data.
- Show a clear empty state when no eligible Teacher users exist and direct the
  School Admin to User Management without creating a nested workflow.
- Mark required fields with visible asterisks and accessible labels.
- Use Bootstrap confirmation modals for lifecycle transitions.
- Do not use nested cards, custom SVG icons, or unsupported frameworks.

## Required Tests

Prove:

- Guests redirect to login.
- Super Admin can list and view profiles across schools, including archived
  profiles, but cannot mutate.
- School Admin can create and update own-school profiles with normalized,
  tenant-derived data and transaction-coupled logs.
- Eligible-user selection includes only active, non-deleted, same-school,
  unlinked Teacher-role users.
- Wrong-role, inactive, deleted, cross-school, already-linked, archived-linked,
  duplicate-employee-code, and forged lifecycle input fail without partial
  writes or record-existence disclosure.
- `user_id` and `school_id` cannot change after creation.
- Active Section or Subject assignments block profile deactivation and archive.
- Inactive dependency-safe profiles can archive; restore returns inactive and
  activation revalidates linked-user eligibility.
- School A cannot list, search, view, route-bind, update, deactivate, archive, or
  restore School B profiles, including archived profiles.
- Exact permission revocation changes routes, controls, and navigation on the
  next request.
- Teacher, Accountant, inactive users, malformed users, and wrong direct-service
  contexts are denied.
- Activity or audit failure rolls back create and lifecycle transitions.
- No hard-delete or normal DELETE route exists.
- Existing User Management safeguards and all prior tests remain green.

## Required Workflow

1. Verify prerequisites and inspect current Academic Structure and User
   Management conventions.
2. Implement and register the Teacher Policy.
3. Implement the context-aware service and linked-user eligibility rules.
4. Implement Form Requests, thin controller, and lifecycle routes.
5. Build read-only directory/details before mutation forms and controls.
6. Add the Teacher Profiles tab and lifecycle confirmations.
7. Add allowed, denied, tenant, eligibility, dependency, retention, and rollback
   tests.
8. Run focused tests, full tests, Pint, frontend build, Composer validation,
   route/view checks, and `git diff --check`.
9. Update implementation status, test evidence, changelog, and prompt index.
10. Provide manual dashboard test steps and dummy Teacher Profile data.

## Acceptance Criteria

- Teacher Profiles remain minimal academic identities linked one-to-one with
  eligible existing users.
- Every route and direct service method is authorization- and context-aware.
- Tenant ownership, linked-user identity, and reserved employee codes cannot be
  forged or reassigned.
- Lifecycle transitions preserve history and active-assignment integrity.
- Every mutation is transactional and auditable.
- The complete regression suite and quality checks pass.
- No out-of-scope HR, assignment, or later-phase workflow is introduced.

## Stop Conditions

Stop and report instead of guessing if:

- The schema or prior Phase 5 workflow baseline is failing.
- DECISION-030 conflicts with the data dictionary or permission matrix.
- Safe retained-profile lookup would require weakening TenantScope.
- A package, migration, hard delete, or tenant bypass appears necessary.
- User Management identity safeguards would need to be removed or weakened.

## Required Final Response

Provide:

- Files modified.
- Teacher Profile workflows implemented.
- Authorization, tenant, identity, lifecycle, and audit protections.
- Tests and validation run.
- Documentation updates.
- Manual dashboard testing steps and dummy content.
- Remaining Phase 5 work.
- Exact next task.
