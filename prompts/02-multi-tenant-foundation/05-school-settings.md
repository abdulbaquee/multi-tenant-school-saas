# Phase 3 School Settings

## Role

Act as a senior Laravel architect, SaaS security architect, database architect,
file-storage security specialist, testing specialist, UI reviewer, and MCA
project reviewer for this repository.

## Objective

Implement the tenant-isolated School Settings workflow so a School Admin can
manage its own school profile, contact details, academic, attendance, grading,
and logo settings without accessing or modifying another school.

## Execution Mode

Implementation. Modify only School Settings, directly related school-profile
fields, public school-logo storage, tests, navigation, and Phase 3 status
documentation.

## Authoritative Documents

Read and follow:

- `AGENTS.md`
- `app/AGENTS.md`
- `tests/AGENTS.md`
- `resources/AGENTS.md`
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

## Scope

Implement:

- School Admin-only School Settings routes and update workflow.
- General school profile and contact information for the active school.
- Timezone, currency, academic-year start month, attendance start time, and
  grading-system settings.
- Public school-logo upload, replacement, display, and removal.
- Automatic initialization of a missing settings row for legacy schools within
  the validated Tenant context.
- A SchoolSetting policy, Form Request, service, thin controller, Blade screen,
  and School Admin-only navigation.
- Super Admin read-only settings visibility through School Details only.
- Guest, role denial, validation, file-security, and tenant-isolation tests.
- Phase 3 completion and verification documentation.

## Out Of Scope

Do not implement:

- Platform Settings or System Settings.
- Super Admin editing of School Settings.
- Teacher or Accountant settings access.
- School lifecycle, code, status, deactivation, deletion, or restoration changes.
- Arbitrary browser editing of `school_id`, `logo_path`, or `settings_json`.
- Student-photo or backup storage.
- Academic-year records, attendance records, grade scales, or later modules.
- New packages, migrations, or `composer.json` changes.

## Constraints

- SchoolSetting remains a strict tenant-owned model using `BelongsToTenant`.
- Tenant context and policy authorization must agree with the authenticated
  School Admin's `school_id`.
- Cross-tenant records must resolve as not found or be denied before mutation.
- School Admin may update only documented profile/contact fields for its own
  school; school code and lifecycle fields remain platform-managed.
- Super Admin reads settings only on the existing School Details screen.
- Logo uploads use the public disk under `school-logos/{school_id}` with unique
  generated filenames.
- Accept only JPG, JPEG, PNG, or WebP images up to 2 MB, validated by MIME type.
- Replacing or removing a logo must clean up the previous managed file without
  deleting another school's file.
- Logo upload failure or database failure must not leave an invalid database
  path or silently report success.
- Use Blade, Bootstrap 5, and Bootstrap Icons only.

## Required Workflow

1. Inspect the settings schema, module permissions, screen flow, security policy,
   tenant scope, storage configuration, and current School Details view.
2. Create the SchoolSetting policy, Form Request, service, and controller.
3. Add tenant-bound routes and School Admin navigation.
4. Build one focused settings workspace with clear General, Academic,
   Attendance, Grading, and Logo sections.
5. Implement secure logo upload, replacement, and removal.
6. Add allowed, denied, cross-tenant, validation, initialization, and storage
   lifecycle tests.
7. Run targeted tests, the full suite, formatting, Blade compilation, route
   inspection, and Composer validation.
8. Update Phase 3 status and measured test evidence.

## Deliverables

- SchoolSetting policy, update request, service, and controller.
- Tenant-bound settings routes and navigation.
- School Settings Bootstrap workspace.
- Secure public school-logo lifecycle.
- Comprehensive settings and isolation tests.
- Phase 3 documentation and evidence update.

## Review Requirements

Verify:

- School Admin can view and update only its own school and settings.
- Teacher and Accountant receive 403 and see no settings menu.
- Super Admin receives no direct settings editor and retains read-only settings
  visibility through School Details.
- Forged tenant, logo-path, JSON, school-code, and lifecycle fields cannot be
  submitted.
- Missing legacy settings are initialized only in the active Tenant context.
- Validation matches documented database lengths and file-security limits.
- Logo filenames are generated, tenant-partitioned, publicly displayable, and
  replaced or removed safely.
- Existing School Management and automatic TenantScope tests remain green.
- No migrations, packages, or unrelated modules are introduced.

## Acceptance Criteria

The prompt is complete when:

- The School Admin settings workflow works end to end.
- Automatic tenant isolation and denied paths are proven by tests.
- Logo storage and cleanup behavior are proven with a fake public disk.
- Super Admin read-only behavior remains consistent with the permission matrix.
- Targeted and full tests pass.
- Pint, Blade compilation, routes, and Composer validation pass.
- Documentation truthfully records Phase 3 completion or any remaining gate.

## Stop Conditions

Stop and report instead of guessing if:

- Settings fields conflict with `DATABASE_DESIGN.md`.
- The requested workflow needs a new database column or package.
- Logo handling would expose private student or backup data.
- The implementation requires Platform or System Settings.

## Required Final Response

Provide:

- Files modified.
- Settings and logo behavior implemented.
- Authorization and tenant-isolation controls.
- Tests and validation results.
- Deferred work and remaining risks.
- Exact next review gate or phase prompt.
