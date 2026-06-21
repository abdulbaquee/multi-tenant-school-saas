# Phase 6 Student Profile Management

## Role

Act as a senior Laravel architect, multi-tenant SaaS architect, security
architect, privacy reviewer, file-storage specialist, UI/UX implementer,
testing specialist, and MCA project reviewer for this repository.

## Objective

Implement secure, privacy-aware Student registration and profile management,
including role-scoped directories, lifecycle controls, private Student photos,
and retained Enrollment history, without introducing Enrollment creation or
later-phase Student workflows.

## Execution Mode

Implementation. Modify only the application, routes, Blade views, tests,
documentation, and prompt files required for Student profile management. Do not
add packages or change the approved Student or Enrollment schema.

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
- `docs/ER_DIAGRAM.md`
- `docs/MODULE_SPECIFICATIONS.md`
- `docs/SCREEN_FLOW.md`
- `docs/SECURITY_GUIDELINES.md`
- `docs/TESTING_STRATEGY.md`
- `docs/UI_UX_DESIGN_SYSTEM.md`
- `docs/CODING_STANDARDS.md`
- `prompts/05-student-management/01-phase-06-student-design-remediation.md`
- `prompts/05-student-management/02-phase-06-core-student-schema.md`

## Prerequisites

Verify before implementation:

- Phase 6 readiness is READY at 10/10.
- The audited Student and StudentEnrollment schema/model foundation is
  committed and the complete regression suite passes.
- The local developer database migration status is reviewed before manual
  testing; do not destroy existing developer data.
- Existing Academic Structure assignments can derive Teacher record scope.
- No package or additional schema field is required.

## Scope

Implement:

- `StudentPolicy` with exact `students.*` permissions, role boundaries, record
  scope, archived-record rules, and private-photo authorization.
- A tenant-aware `StudentService` for:
  - paginated, searchable, and filterable current and archived directories;
  - School Admin full-detail reads and profile registration/updates;
  - Teacher assigned-record, privacy-minimized reads;
  - Super Admin school-selected, privacy-minimized, read-only reads;
  - activation, deactivation, dependency-safe archival, and restoration;
  - private photo upload, replacement, removal, and authorized delivery;
  - privacy-safe activity and audit evidence inside domain transactions.
- Form Requests for list filters, registration, profile updates, lifecycle
  actions, and photo mutations.
- Thin controllers and authenticated `tenant.context` routes under `/students`.
- Blade and Bootstrap 5 screens for directories, registration, profile details,
  editing, read-only Enrollment history, lifecycle confirmations, photo
  management, archived state, validation, pagination, and empty states.
- Permission-aware Student navigation for implemented Phase 6 roles only.
- Feature, authorization, tenant-isolation, assigned-record, privacy,
  validation, lifecycle, private-file, rollback, and responsive-view tests.
- Documentation, testing evidence, changelog, roadmap status, and prompt-index
  updates after verification.

## Access Contract

### School Admin

- Requires an active authenticated school user, matching Tenant context, and
  exact effective permissions:
  - `students.view` for directories, details, Enrollment history, and authorized
    private-photo delivery;
  - `students.create` for Student registration;
  - `students.update` for profile edits, activation, restoration, and photo
    upload, replacement, and removal;
  - `students.delete` for deactivation and dependency-safe archival.
- Can access complete Student data only inside the active school.
- Cannot change tenant ownership, admission number, Enrollment placement, or
  lifecycle state through an ordinary profile update.

### Teacher

- Requires `students.view`, an active Teacher Profile, matching Tenant context,
  and server-derived assignment scope.
- May list and view only active Students with an active Enrollment reached
  through either:
  - the Teacher's own active Section assignment under an active Class; or
  - an active Subject assigned to that Teacher for the Student's active Class.
- Receives only Student name, admission number, status, and authorized current
  Academic Year/Class/Section context.
- Cannot see DOB, guardian details, address, contact data, photo presence or
  content, archived records, or unrelated Enrollment history.
- Cannot create, update, change status, archive, restore, enroll, or access
  photo endpoints.

### Super Admin

- Requires `students.view`, an active authenticated platform user,
  `school_id = NULL`, and explicit Platform context.
- Must select one authorized, non-deleted School before any Student query; no
  unfiltered platform-wide Student directory is allowed.
- May list and view current and archived Students in that selected school using
  the same privacy-minimized fields allowed to Teachers, plus non-sensitive
  retained Enrollment reference data.
- Cannot create, update, change status, archive, restore, enroll, or access
  Student photos. Every HTTP and direct-service mutation attempt is denied.

### Accountant, Guest, And Malformed Users

- Receive no direct Student menu, directory, profile, search, or photo route in
  Phase 6, even if `students.view` exists in the maximum permission catalog.
- Fee-specific Student lookup remains deferred to Phase 8 Fee Management.

## Student Data Contract

- Registration requires `admission_no`, `first_name`, `gender`,
  `date_of_birth`, `guardian_name`, `guardian_phone`, and `admission_date`.
- `last_name`, `guardian_email`, `address`, and initial photo are optional.
- Enforce documented database lengths, accepted gender constants, valid email,
  and reasonable phone input without inventing unsupported demographic fields.
- Date of birth cannot be in the future and must be earlier than admission date;
  admission date cannot be in the future.
- Admission number is tenant-unique, trimmed, immutable, and remains reserved
  after archival.
- `school_id`, `status`, `deleted_at`, and `photo_path` are never trusted from
  ordinary form input. Tenant ownership comes only from TenantContext and photo
  paths come only from the storage workflow.
- Standard profile updates cannot change admission number, status, ownership,
  Enrollment identity, or audit-controlled fields.

## Lifecycle Contract

- Registration creates an active, non-archived Student. It does not create an
  Enrollment.
- Deactivation permits only `active -> inactive` and uses a dedicated,
  authorized action.
- Activation permits only `inactive -> active` for a non-archived Student.
- Archival requires an inactive, non-archived Student with no active Enrollment;
  it uses SoftDeletes and retains identity, history, and any private photo.
- Restoration requires an archived Student and returns the original record to
  inactive status. Activation remains a separate authorized action.
- No hard-delete, force-delete, or routine DELETE route exists.
- Transfer and graduation are not implemented in this prompt because their
  required Enrollment status changes must be transactional with the later
  Enrollment workflow.
- `transferred` and `graduated` records, if present as retained data, are
  terminal and read-only.

## Enrollment History Boundary

- Student profiles may display existing retained Enrollment history appropriate
  to the actor's access projection.
- This prompt creates no Enrollment and exposes no edit, reassignment,
  promotion, completion, or transfer control.
- Enrollment Class, Section, Academic Year, Student, and roll number remain
  immutable.
- Initial Enrollment creation and its lifecycle belong to the next dedicated
  Phase 6 prompt.

## Private Photo Contract

- Use Laravel's private local storage (`Storage::disk('local')`), whose root is
  `storage/app/private`; never use the public disk or a public storage URL.
- Store generated filenames under a tenant-separated Student photo path. Never
  trust an uploaded filename or expose the stored path to the browser.
- Accept only validated JPG/JPEG, PNG, or WebP images up to 2 MB and prove MIME
  validation with forged-extension tests.
- Only an authorized School Admin may upload, replace, remove, or receive a
  photo for an active own-tenant Student.
- Deliver image bytes through an authorized controller response only. Do not
  create temporary public URLs or direct file links.
- Store a replacement first, commit the Student update and logs, then remove the
  prior file after commit. On transaction failure, remove the new uncommitted
  file and preserve the prior file/reference.
- Remove a photo file only after its database update commits.
- Archival retains the file but denies delivery. Inactive, archived, transferred,
  or graduated Students cannot expose photo bytes.

## Tenant, Authorization, And Logging Requirements

- Student queries inherit `BelongsToTenant`; `withTrashed()` must retain
  TenantScope.
- Route-model binding resolves cross-school Student IDs as 404 before Policy
  checks and must work safely for authorized archived-record routes.
- Teacher assignment scope is derived from the actor's own active Teacher
  Profile and active Section/Subject/Class/Enrollment relationships. Request
  filters never widen that scope.
- Platform Student reads require explicit selected-school scoping in the
  authorized service; Platform mode alone is insufficient.
- Services independently validate actor identity, exact permission, context
  mode, school lifecycle, record scope, lifecycle state, and dependencies.
- Direct service calls deny Unresolved context, wrong context mode, inactive or
  malformed users, unauthorized Platform mutations, and cross-tenant records.
- Mutations and activity/audit rows are transaction-coupled. Use module
  `student_management` and actions `created`, `updated`, `activated`,
  `deactivated`, `archived`, and `restored`; photo mutations use `updated` with
  a boolean `photo_changed` indicator.
- Logs may contain Student/Enrollment IDs, admission number, status, Academic
  Year, Class, Section, roll number, and `photo_changed` only. They must exclude
  DOB, guardian data, address, phone numbers, email, photo paths, filenames,
  image contents, and full request payloads.

## UI Requirements

- Use the existing authenticated shell, Blade, Bootstrap 5, and Bootstrap Icons.
- Add Student navigation only when the actor has an implemented Phase 6 route:
  School Admin, assigned-scope Teacher, or authorized Super Admin.
- Keep lists compact and responsive with pagination, search, status filters,
  archived-state filtering where authorized, and clear empty states.
- Keep full sensitive fields confined to School Admin screens. Use dedicated
  privacy-minimized partials or view models for Teacher and Super Admin; do not
  hide sensitive markup with CSS or JavaScript.
- Mark required fields with visible asterisks and accessible labels.
- Use familiar icons, Bootstrap confirmation modals for lifecycle actions, and
  stable action widths to prevent layout shift.
- Show private photos only through the protected endpoint and provide a safe
  placeholder when none exists.
- Do not add reports, exports, bulk actions, nested cards, custom SVG icons, or
  unsupported frontend frameworks.

## Required Tests

Prove:

- Guests redirect to login and Accountant has no direct Student route or menu.
- School Admin can list, search, filter, paginate, register, view, and update
  complete own-school Students with tenant-derived ownership and privacy-safe
  transaction-coupled logs.
- Validation covers required fields, lengths, gender, email, phone, date rules,
  tenant-unique admission numbers, archived admission-number reservation,
  immutable admission numbers, and prohibited lifecycle/ownership/photo-path
  input.
- School A cannot list, search, view, bind, update, change status, archive,
  restore, or deliver photos for School B Students; forged IDs return 404 and
  reveal no record existence.
- Teacher Section-assignment and Subject-assignment paths expose only active
  assigned Students and the approved privacy projection; inactive assignments,
  inactive Classes, inactive Enrollments, unassigned records, sensitive fields,
  photos, archived records, and all mutations are denied.
- Super Admin must select a School, receives only the privacy-minimized
  projection for that School, may read authorized archived records, and cannot
  mutate or access photos through routes or direct services.
- Exact permission revocation updates routes, actions, and navigation on the
  next request.
- Lifecycle transitions follow the approved state graph; archive rejects active
  Students or active Enrollments, restore returns inactive, terminal states are
  read-only, and no hard-delete route exists.
- Photo tests use fake private storage and cover valid upload, forged MIME,
  invalid type, oversize, generated filename, private path, protected delivery,
  cross-tenant denial, role denial, inactive/archived denial, replacement,
  removal, retained archived file, and cleanup on database/log failure.
- Activity and audit evidence contains no DOB, guardian, address, phone, email,
  photo path, filename, image content, or unsanitized validation value.
- Logging failure rolls back Student mutations; file cleanup and prior-file
  preservation follow the private-photo contract.
- Existing Student schema safeguards and all prior Phase 2-5 tests remain green.
- No Student report, export, Enrollment mutation, or later-phase route exists.

## Out Of Scope

Do not implement:

- Enrollment creation, reassignment, promotion, completion, transfer, or
  graduation workflows.
- Attendance, Fees, Examinations, Student reports, exports, analytics, bulk
  import, Parent Portal, Student Portal, documents, messaging, or notifications.
- Accountant fee lookup, Teacher mutation, or Super Admin mutation/photo access.
- New migrations, columns, packages, public photo URLs, hard deletes, or seed
  data containing real minor information.

## Required Workflow

1. Verify prerequisites and inspect existing Service, Policy, Form Request,
   route-binding, logging, private-storage, navigation, and test conventions.
2. Implement and register Student Policy boundaries and privacy projections.
3. Implement context-aware Student service reads before mutation workflows.
4. Implement registration, updates, lifecycle actions, and privacy-safe logs.
5. Implement private-photo storage, protected delivery, replacement, removal,
   after-commit cleanup, and rollback cleanup.
6. Implement Form Requests, thin controllers, tenant-aware routes, and archived
   binding behavior.
7. Build role-appropriate Blade directories, profiles, forms, Enrollment
   history, lifecycle confirmations, and navigation.
8. Add allowed, denied, tenant, assignment, privacy, lifecycle, storage,
   rollback, permission-revocation, and responsive-view tests.
9. Run focused tests, the full suite, Pint, frontend build, Composer validation,
   route/view checks, migration status, and `git diff --check`.
10. Update implementation status, test evidence, changelog, roadmap, MCA notes,
    and prompt index.
11. Provide manual dashboard testing steps and fictional Student data only.

## Acceptance Criteria

- Every Student read and mutation is permission-, role-, context-, tenant-, and
  record-scope aware.
- Sensitive minor data never appears in unauthorized HTML, JSON, logs, errors,
  routes, or files.
- Private photos never use public storage and cannot be fetched without current
  School Admin authorization for an active own-tenant Student.
- Student ownership and admission identity cannot be forged or reassigned.
- Lifecycle and archival rules preserve Enrollment history and private files.
- Teacher and Super Admin receive only the documented privacy-minimized read
  projection; Accountant receives no direct Student access.
- The complete regression suite and all quality checks pass.
- No Enrollment mutation, report, export, package, or schema change is added.

## Stop Conditions

Stop and report instead of guessing if:

- The committed schema/model foundation or existing regression baseline fails.
- Safe Teacher assignment scope would require weakening TenantScope.
- Private delivery would require a public URL or public disk.
- Transfer/graduation requires implementing Enrollment mutation in this prompt.
- A migration, package, new sensitive field, or undocumented role capability
  appears necessary.
- DECISION-031 conflicts with the canonical database, security, or permission
  documentation.

## Required Final Response

Provide:

- Files modified.
- Student profile workflows implemented.
- Authorization, privacy, tenant, lifecycle, photo, and audit protections.
- Tests and validation run.
- Documentation updates.
- Manual dashboard testing steps and fictional dummy content.
- Remaining Phase 6 work.
- Exact next task: create the dedicated Phase 6 Enrollment-management prompt.
