# TENANCY DESIGN

Version: 1.1
Status: Draft - Core Context And Scope Implemented

Project:
Multi-Tenant School Administration Management SaaS Platform

Program:
Master of Computer Applications (MCA)

Framework:
Laravel 13

Language:
PHP 8.4

Database:
MySQL 8

Architecture:
Single Database Multi-Tenant SaaS

Tenant Identifier:
school_id

---

# 0. PURPOSE

This document is the **single source of truth** for multi-tenancy in the project.

It defines the tenancy architecture, tenant resolution flow, the Super Admin
strategy, the automatic isolation mechanism, the authentication and email model,
the security guarantees, and the tenant isolation testing approach.

If any other document conflicts with this file on tenancy, **this document wins**.

---

# 1. ARCHITECTURE DECISION

Tenancy Model:

* Single Database
* Shared Schema
* school_id Tenant Key

Implementation Approach:

**Native Laravel Multi-Tenancy** (no third-party tenancy package).

The application does NOT use Stancl Tenancy or any external tenancy package.

Tenancy is implemented using built-in Laravel features:

* `school_id` foreign key on every business table
* Eloquent **Global Scopes**
* A reusable **BelongsToTenant** trait
* A **TenantContext** middleware
* Policies and Services for authorization and business rules

Rationale:

* Simplicity-first (Project Constitution core principle)
* No external tenancy package to learn, configure, or explain during viva
* Single-database shared-schema does not require connection switching
* Easy to demonstrate and reason about for MCA evaluation
* Full control over tenant resolution and Super Admin bypass

See `DECISIONS_LOG.md` DECISION-005 (Revised) for the formal record.

---

# 2. TENANT RESOLUTION FLOW

```text
Login
  │
  ▼
Authenticated User
  │
  ▼
Read user.school_id
  │
  ▼
Set Tenant Context (TenantContextMiddleware)
  │
  ▼
Global Scope auto-filters all tenant queries by school_id
  │
  ▼
Authorized Tenant Data
```

The tenant is resolved **from the authenticated user record**, not from a
domain, subdomain, or request parameter. The user's `school_id` is the only
source of tenant identity.

---

# 3. USER TYPES

| User Type    | Tenant Scope        | school_id     |
| ------------ | ------------------- | ------------- |
| Super Admin  | Platform-wide       | NULL          |
| School Admin | Single school       | Required      |
| Teacher      | Single school       | Required      |
| Accountant   | Single school       | Required      |

---

# 4. TENANT CONTEXT STATE MODEL

Implementation status: TenantContext state, request middleware, explicit
Platform mode, active-school validation, middleware priority, request cleanup,
`TenantScope`, and `BelongsToTenant` are implemented. `school_settings` is the
first strict tenant-owned model using automatic isolation and now has a complete
School Admin workflow with tenant-partitioned logo storage. Contextual
`activity_logs` and `audit_logs` now use the same default-deny scope with
append-only model enforcement.

Tenant context has exactly three states. A missing tenant id is not itself a
platform bypass.

| State | Meaning | Query Behavior |
| ----- | ------- | -------------- |
| Unresolved | No authenticated and validated tenant mode has been established. This is the initial state. | `TenantScope` applies a deny-all predicate so reads return no rows; tenant-owned writes throw a controlled tenant-context exception. |
| Tenant | An authenticated school user has a valid, active, non-deleted school. | Tenant-owned reads are filtered to one `school_id`; creates receive that `school_id`. |
| Platform | An authenticated Super Admin has been validated and platform mode has been set explicitly, or a trusted service is appending a narrowly defined system-origin security event. | Platform-wide reads are allowed only behind policies/services; tenant-owned creates still require an explicit target workflow. System-origin execution may append its security event but may not read tenant business data. |

Rules:

* Context starts as Unresolved for every request, job, command, and test.
* `users.school_id = NULL` is necessary for a Super Admin but is not sufficient
  to enter Platform state. The user must also hold the canonical Super Admin
  role, be active, be authenticated, and pass route authorization.
* The only unauthenticated Platform-context exception is a temporary callback in
  `SecurityLogService` that appends a credential-free failed or denied login
  event. It cannot infer Platform access from a missing school id, cannot read
  tenant business data, and restores the previous context in `finally`.
* Unresolved context must never behave like Platform context.
* Context is request or execution scoped and must be cleared after use even when
  an exception occurs.

---

# 5. USER AND SCHOOL STRATEGY

## Super Admin

* A Super Admin has the canonical Super Admin role and `school_id = NULL`.
* Authenticated middleware may establish explicit Platform context only after
  validating both conditions and active user status.
* Platform context removes tenant filtering, but Policies and Services still
  authorize every school, user, report, export, and bypass operation.
* Controllers and Blade templates cannot create platform context or remove a
  tenant scope directly.

## School Users

* School Admin, Teacher, and Accountant operate within exactly one school.
* Their `school_id` is required and the referenced school must be active and not
  soft deleted.
* Middleware establishes Tenant context from the authenticated user record,
  never from request input, a URL, a header, or a subdomain.
* A missing school, inactive school, deleted school, null `school_id`, or invalid
  role/school combination fails closed.

---

# 6. MODEL AND GLOBAL SCOPE DESIGN

## Model Classification

| Category | Tables | Tenancy Rule |
| -------- | ------ | ------------ |
| Tenant registry | `schools` | Platform-managed root record. It does not use `BelongsToTenant`; access is Super Admin only through `SchoolPolicy` and `SchoolService`. |
| Platform | `roles`, `permissions`, `role_permissions` and Laravel infrastructure tables | No tenant scope. Mutation is limited to its documented module and authorization. |
| Hybrid identity | `users` | Globally unique identity must be loaded before tenant resolution. It does not use the generic tenant global scope; all operational listing, viewing, updates, role assignment, activation, and deactivation remain policy- and service-scoped. |
| Strict tenant-owned | `school_settings`, all Academic tables, `attendances`, all Fee tables, and all Examination tables | Non-null `school_id`; must use `BelongsToTenant`. Tenant context supplies `school_id` on create. |
| Contextual logs | `activity_logs`, `audit_logs`, `backup_logs` | Use tenant filtering in Tenant state. Platform events may use `school_id = NULL` only in explicit Platform state. |

RBAC tables are shared platform catalogs and never receive `school_id`. Mapping
updates require an authorized Super Admin in explicit Platform context. A School
Admin may receive a Policy-filtered, read-only projection of School Admin,
Teacher, and Accountant mappings but cannot query the Super Admin mapping or
mutate any shared RBAC row. User role assignment remains tenant-bound through
User Policy and User Service checks.

## Phase 5 Academic Structure Tenancy Contract

`academic_years`, `academic_terms`, `teachers`, `classes`, `sections`, and
`subjects` are strict tenant-owned tables. Each model uses `BelongsToTenant`, and
TenantContext supplies `school_id` on creation. No Academic Structure request
may accept tenant ownership from user input.

Database foreign keys prove that a parent exists but do not prove tenant
alignment. Academic services must verify that every Academic Year, Term, Class,
Teacher Profile, Section, Subject, and linked User belongs to the actor's active
school. Tenant-scoped route-model binding resolves cross-school identifiers as
not found before Policy checks.

Teacher Profile creation accepts only an active, non-deleted Teacher-role User
from the same tenant. New Section and Subject assignments accept only an active,
non-deleted Teacher Profile from that tenant. Teacher read access derives the
actor's profile through `teachers.user_id`, then permits only assigned Sections,
assigned Subjects, and their related Classes. It does not rely on request-supplied
teacher or school identifiers.

An authorized Super Admin may read Academic Structure records only after
explicit Platform context and Policy/service authorization. Platform mode does
not permit Academic Structure mutation in Phase 5. School Admin mutations and
their activity/audit records remain tenant-owned.

Implementation Status:

* All six Phase 5 models use `BelongsToTenant`; automatic School A, School B,
  Platform, and Unresolved behavior is covered by foundation tests.
* Academic Year and Academic Term services, Policies, tenant-scoped parent
  validation, cross-tenant route binding, Platform read-only access, and
  tenant-owned mutation logs are implemented and tested.
* Teacher Profile services enforce same-school eligible User linking,
  tenant-derived ownership, immutable identity, retained archived-profile
  isolation, Platform read-only access, and tenant-owned mutation logs.
* Class services enforce tenant-derived immutable ownership, tenant-safe route
  binding, Platform read-only access, and tenant-owned mutation logs. Teacher
  Class reads derive assignment visibility from the actor's own active Teacher
  Profile and active Section or Subject relationships.
* Section and Subject services derive ownership from TenantContext, revalidate
  active same-tenant Classes and optional Teacher Profiles, preserve retained
  uniqueness, and restrict Teacher reads to the actor's own active assignments.

The `users` exception exists only because authentication must retrieve a globally
unique identity before tenant context can be resolved. It does not authorize
unrestricted operational user queries. School Admin user creation always derives
the school from the actor. Super Admin creation of a school user validates an
explicit active target school in the authorized User Service. Super Admin users
must retain `school_id = NULL`.

## Contextual Log Contract

Implemented `activity_logs` and `audit_logs` use the default-deny `TenantScope`.
Tenant execution derives `school_id` from TenantContext, while authorized
Platform execution may record a target school or `NULL` for a platform event.
Their models are append-only: normal Eloquent update and delete operations throw.

Authenticated activity and audit records require actor-to-context alignment.
Public login failures have no authenticated actor, so `SecurityLogService`
temporarily enters Platform context solely to append a system activity record
containing action, timestamp, IP address, and user agent. Attempted email,
password, reset token, and other credentials are not recorded.

A Super Admin operation that changes a record's school ownership spans two
tenants. Its activity and audit records therefore use `school_id = NULL` in
explicit Platform context. This preserves the old and new ownership evidence
without exposing either school's values through the other school's scoped log
queries.

## BelongsToTenant Contract

Every strict tenant-owned model uses a reusable `BelongsToTenant` trait that:

1. Registers `TenantScope` for all queries.
2. In Tenant state, filters by the context `school_id`.
3. In Platform state, permits an authorized platform-wide query.
4. In Unresolved state, applies a deny-all query predicate so reads return no
   tenant rows; it never returns unscoped rows.
5. On creation in Tenant state, overwrites or rejects submitted `school_id` and
   assigns the context school.
6. Rejects tenant-owned creation in Unresolved state with a controlled
   tenant-context exception.

Controllers must not call `withoutGlobalScope(TenantScope::class)`. Any unusual
platform workflow that removes a scope must live in an authorized Service and
must require explicit Platform context.

## Route-Model Binding

Tenant context must be established before implicit or explicit route-model
binding for tenant-owned records. A record belonging to another school resolves
as not found (HTTP 404), preventing record-existence disclosure. Policies remain
required after binding as defense in depth.

---

# 7. MIDDLEWARE AND EXECUTION LIFECYCLE

## Web Requests

Required middleware order for authenticated tenant-aware routes:

1. Session and authentication middleware resolve the authenticated identity.
2. `TenantContextMiddleware` clears any prior context, validates the user and
   school, and establishes Tenant or Platform state.
3. Route-model binding runs with the established context.
4. Authorization middleware and controller policies run.
5. Context is cleared in a `finally`-equivalent step after the response or
   exception.

Public authentication routes run without tenant context. The globally unique
email locates the hybrid `users` identity. Login succeeds only when the user is
active and either is a valid Super Admin or belongs to an active, non-deleted
school. Failed or tenant-denied login activity uses the narrow trusted-system
logging pathway defined above and restores Unresolved context immediately.

## Queue Jobs And Console Commands

* Queue jobs are Unresolved by default and must carry a trusted `school_id` when
  processing tenant data.
* A job establishes Tenant context inside its handler and clears it in a
  `finally`-equivalent step.
* Platform jobs and commands must opt into Platform context explicitly through
  a dedicated authorized application service; a missing school id is not an
  automatic bypass.
* Tenant-aware commands require a validated school argument or iterate schools
  by establishing and clearing one Tenant context per school.
* Long-running workers, sequential feature tests, and repeated requests must
  prove that context does not leak from one execution to the next.

---

# 8. SCHOOL ACCESS LIFECYCLE AND SECURITY GUARANTEES

## School Activation State

Normal Phase 3 states are `active` and `inactive`.

* New schools default to `active` unless an approved onboarding workflow later
  introduces another documented state.
* Deactivation requires a reason, sets `status = inactive` and
  `deactivated_at`, preserves users and tenant data, clears remember tokens for
  school users, and removes their database sessions.
* Every authenticated request revalidates the school state, so a surviving or
  copied session cannot continue after deactivation or soft deletion.
* Login for a user whose school is inactive, missing, or soft deleted is denied
  with a generic authentication response.
* Reactivation sets `status = active`, clears deactivation fields, and allows
  otherwise-active school users to authenticate again. It does not restore a
  soft-deleted school or change individual user status.
* Soft deletion is not a routine Phase 3 UI action. If later exposed, the school
  must first be inactive; tenant data remains retained and access remains denied.

## Security Guarantees

* **Default deny:** Unresolved context cannot read or write tenant-owned data.
* **Defense in depth:** scope, middleware, Policies, Form Requests, and Services
  all participate in isolation.
* **Mass-assignment safety:** strict tenant-owned `school_id` values come from
  context, not browser input.
* **Explicit platform access:** only validated Platform context can bypass
  tenant filtering.
* **No lifecycle leakage:** context is cleared after requests, jobs, commands,
  exceptions, and per-school iterations.
* **Reports and exports:** future reports inherit the same context and scope.

Rule: A school user must never read or write another school's records.

---

# 9. AUTHENTICATION & EMAIL STRATEGY

Authentication: Laravel Breeze (installed).

Authentication Type: Session-based.

## Email Strategy — Decision: Option A (Global Unique Email)

Decision:

`users.email` is **globally unique** across the entire platform.

Rationale:

* Keeps the default Laravel Breeze login flow unchanged: a user logs in with
  email + password, and the tenant is resolved from the authenticated user's
  `school_id`. No school selector or subdomain is required at login.
* Simplicity-first: avoids composite-key login ambiguity where the same email
  could exist in multiple schools.
* Each user belongs to exactly one school (one account = one school).

Trade-off (accepted for MVP):

* A person who works at two schools needs two separate accounts with two
  different email addresses. Multi-school user identity is deferred to Future
  Enhancements.

Constraint:

* `users.email` carries a global UNIQUE index.
* `users.school_id` is nullable (NULL = Super Admin).

This decision is applied consistently across `DATABASE_DESIGN.md`,
`SYSTEM_ARCHITECTURE.md`, `SECURITY_GUIDELINES.md`, and
`MODULE_SPECIFICATIONS.md`.

---

# 10. TENANT ISOLATION TESTING

Tenant isolation is the highest-priority security requirement and must be
verified by automated tests.

Required test scenarios:

* School A cannot list, search, view, bind, update, deactivate, or delete School
  B tenant-owned records.
* Cross-tenant route-model binding returns 404 and does not reveal existence.
* Creating a strict tenant-owned record ignores or rejects submitted
  `school_id` and assigns the Tenant context school.
* Unresolved context returns no tenant data and rejects tenant-owned writes.
* A validated Super Admin receives explicit Platform context and can use only
  policy-authorized platform workflows.
* A null-school non-Super-Admin and a school-scoped Super Admin are denied.
* An inactive, missing, or soft-deleted school cannot log in or continue an
  existing session.
* Deactivation revokes school-user sessions and remember tokens while retaining
  records.
* Sequential requests for School A then School B do not reuse context.
* Exceptions do not leave context active for the next request or test.
* Tenant jobs require a trusted school id and clear context after handling.
* Commands and platform jobs remain Unresolved unless they opt into an explicit
  validated context.
* The global scope works without an explicit per-query `where('school_id')`.

Tenant isolation tests must pass 100%. A tenant isolation failure is a
**Critical** defect (see `TESTING_STRATEGY.md`).

---

# 11. VIVA QUESTIONS & ANSWERS

**Q: What multi-tenancy model do you use?**
A: Single database, shared schema, with a `school_id` tenant key. All schools
share one database and one schema; every business record carries a `school_id`.

**Q: Why not Stancl Tenancy or a database-per-tenant model?**
A: For an MCA-scope project, native Laravel multi-tenancy with a global scope is
simpler to build, demonstrate, and explain, and avoids the operational cost of
managing many databases or an external package.

**Q: How is the tenant identified?**
A: From the authenticated user's `school_id`. After login, the
`TenantContextMiddleware` sets the tenant context, and an Eloquent global scope
filters every query automatically.

**Q: How do you prevent one school from seeing another school's data?**
A: A `BelongsToTenant` trait adds a global `school_id` scope to every tenant
model, so filtering is automatic and default-deny. Manual filtering is not relied
upon.

**Q: How does the Super Admin see all schools?**
A: After authentication validates the canonical Super Admin role,
`school_id = NULL`, and active status, middleware establishes explicit Platform
context. Unresolved context remains default-deny.

**Q: How is `school_id` set on new records?**
A: Automatically by the trait from the tenant context, never from user input.

**Q: Why is the User model a tenancy exception?**
A: Login must locate the globally unique user before tenant context exists.
Operational user management is therefore isolated by Policies and the User
Service, while strict tenant business models use the automatic global scope.

**Q: Can the same email exist in two schools?**
A: No. Email is globally unique (Option A); each account belongs to one school.

---

# 12. RELATIONSHIP TO OTHER DOCUMENTS

This document governs tenancy. The following documents reference it and must
remain consistent with it:

* PROJECT_CONSTITUTION.md
* SYSTEM_ARCHITECTURE.md
* SECURITY_GUIDELINES.md
* DATABASE_DESIGN.md
* CODING_STANDARDS.md
* TESTING_STRATEGY.md
* DECISIONS_LOG.md
* README.md
* AGENTS.md
* app/AGENTS.md
* database/AGENTS.md
* tests/AGENTS.md
* docs/AGENTS.md
* resources/AGENTS.md
