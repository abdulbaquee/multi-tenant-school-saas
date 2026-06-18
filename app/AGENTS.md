# app/AGENTS.md

Instructions for Laravel application code under `app/`.

## Sources Of Truth

- Architecture: `../docs/SYSTEM_ARCHITECTURE.md`
- Tenancy: `../docs/TENANCY_DESIGN.md`
- Coding standards: `../docs/CODING_STANDARDS.md`
- Security: `../docs/SECURITY_GUIDELINES.md`
- Modules and permissions: `../docs/MODULE_SPECIFICATIONS.md`

## Controllers

- Keep controllers thin.
- Controllers handle HTTP flow only: accept requests, call Form Requests and Services, return responses.
- Do not put business logic, tenant bypass logic, large queries, calculations, or authorization shortcuts in controllers.

## Services

- Put business workflows in service classes.
- Services may coordinate models, transactions, reports, activity logs, and audit logs.
- Services should not return Blade views.
- Use transactions for multi-step writes that must remain consistent.

## Form Requests

- Use Form Requests for validation.
- Do not validate directly in controllers.
- Do not trust client-side validation.
- Prevent user-submitted `school_id` from overriding tenant context.

## Authorization

- Use Policies, Gates, and Middleware for protected actions.
- UI visibility is not authorization.
- Every view, create, update, delete, export, report, file access, and tenant-scope bypass must be authorized server-side.

## Models And Tenancy

- Tenant-owned models must use the documented `BelongsToTenant` pattern.
- Tenant-owned records must receive `school_id` from tenant context.
- Super Admin scope bypass must be explicit, centralized, and authorized.
- Define relationships, casts, and fillable or guarded fields.
- Keep models focused on data relationships and simple scopes; put workflows in services.

## Middleware

- Tenant context must be resolved after authentication.
- Middleware should set request context and enforce cross-cutting concerns, not contain business workflows.

## Laravel Practices

- Prefer Laravel conventions and native Laravel features.
- Use Eloquent relationships and eager loading.
- Avoid N+1 queries and large unfiltered queries.
- Keep code easy to test and explain during MCA viva.
