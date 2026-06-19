# TENANCY DESIGN

Version: 1.0
Status: Draft

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

# 4. SUPER ADMIN STRATEGY

* `users.school_id` is **nullable**.
* A Super Admin has `school_id = NULL`.
* The Super Admin **bypasses the tenant global scope** and has platform-wide
  access (manage schools, view global reports, platform settings, backups).
* The tenant scope bypass is centralized in the `BelongsToTenant` trait /
  `TenantContext` resolution: when no tenant context is set (Super Admin), the
  global scope is not applied.
* Super Admin actions are still subject to authorization (Policies/Gates) and
  are recorded in activity and audit logs.

---

# 5. SCHOOL USER STRATEGY

* School Admin, Teacher, and Accountant always operate within exactly one
  school.
* `school_id` is **required** (NOT NULL) for these users.
* Every query they trigger is **automatically** scoped to their `school_id`
  by the global scope.
* Cross-tenant access is impossible by default, not by developer discipline.

---

# 6. GLOBAL SCOPE DESIGN

## BelongsToTenant Trait

Every tenant-owned model uses a `BelongsToTenant` trait that:

1. Registers an Eloquent **global scope** adding
   `where school_id = <current tenant>` to every query automatically.
2. Auto-fills `school_id` on model creation from the current tenant context.
3. Is skipped when there is no tenant context (Super Admin / console).

Conceptual shape (documentation reference only — not implementation):

```php
trait BelongsToTenant
{
    protected static function bootBelongsToTenant(): void
    {
        if (Tenant::check()) {
            static::addGlobalScope(new TenantScope());

            static::creating(function ($model) {
                $model->school_id ??= Tenant::id();
            });
        }
    }
}
```

## TenantScope

A dedicated `TenantScope` (implements `Illuminate\Database\Eloquent\Scope`)
applies the `school_id` constraint. Queries that legitimately need to bypass the
scope (e.g., Super Admin platform reports) use `withoutGlobalScope(TenantScope::class)`
inside an authorized Service or Policy-protected path only.

---

# 7. MIDDLEWARE DESIGN

## TenantContextMiddleware

Responsibilities:

* Run after authentication.
* If the user is a school user, set the tenant context to `user.school_id`.
* If the user is a Super Admin (`school_id = NULL`), leave the tenant context
  unset so the global scope is not applied.
* Make the active tenant available application-wide for the request lifecycle.

The middleware is registered on all authenticated web routes.

---

# 8. SECURITY GUARANTEES

* **Default-deny isolation:** tenant filtering is applied automatically by the
  global scope, so a forgotten `where('school_id', ...)` cannot leak data.
* **Defense in depth:** isolation is enforced at the model layer (global scope),
  the request layer (middleware), and the authorization layer (policies).
* **Mass-assignment safe:** `school_id` is set by the trait from tenant context,
  not from user input.
* **Super Admin bypass is explicit and centralized,** never ad hoc.
* **Reports and exports** inherit the same global scope as on-screen queries, so
  they cannot expose other schools' data.

Rule: A school user must never read or write another school's records.
No exceptions.

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

* A School A user cannot read School B records (index, show, search, reports,
  exports) — expect empty results or 403/404, never another school's data.
* A School A user cannot update or delete School B records.
* Creating a record auto-assigns the acting user's `school_id`.
* A Super Admin can access platform-wide data across schools.
* A school user cannot escalate to platform-wide access.
* The global scope is applied automatically without explicit `where('school_id')`.

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
A: Super Admin has `school_id = NULL`; when no tenant context is set the global
scope is not applied, granting platform-wide access through authorized paths.

**Q: How is `school_id` set on new records?**
A: Automatically by the trait from the tenant context, never from user input.

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
