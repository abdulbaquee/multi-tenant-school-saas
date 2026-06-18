# database/AGENTS.md

Instructions for migrations, seeders, factories, and database-related work.

## Sources Of Truth

- Database schema: `../docs/DATABASE_DESIGN.md`
- ERD: `../docs/ER_DIAGRAM.md`
- Tenancy: `../docs/TENANCY_DESIGN.md`
- Deletion policy: `../docs/DATABASE_DESIGN.md` and `../docs/SECURITY_GUIDELINES.md`
- Coding standards: `../docs/CODING_STANDARDS.md`

## Migration Standards

- Create migrations from `../docs/DATABASE_DESIGN.md`; do not invent columns or relationships.
- Use plural `snake_case` table names and `snake_case` columns.
- Use `id` primary keys unless the database design says otherwise.
- Add timestamps consistently.
- Add soft deletes only for tables listed in the deletion strategy.
- Use MySQL 8-compatible column types.

## Tenant Columns

- Every tenant-owned table must include `school_id`.
- Platform tables without tenant ownership are limited to documented exceptions such as roles, permissions, and role_permissions.
- Tables that allow `school_id = NULL` for platform-level records must match the database design.

## Foreign Keys And Indexes

- Add foreign keys for documented relationships.
- Use `restrictOnDelete()` by default.
- Do not use cascading deletes for tenant-owned data unless explicitly documented and approved.
- Add indexes and unique constraints from `../docs/DATABASE_DESIGN.md`.
- Preserve the one-to-one uniqueness between `schools` and `school_settings`.

## Soft Deletes And History

- Soft delete schools, users, teachers, students, classes, sections, subjects, exams, and appropriate fee setup records.
- Preserve historical records such as enrollments, attendance, payments, transactions, exam results, report cards, activity logs, audit logs, and backup logs.
- Do not physically delete audit or financial history in normal workflows.

## Seeders And Factories

- Seed canonical roles, permissions, and role-permission mappings from `docs/MODULE_SPECIFICATIONS.md`.
- Demo data must respect tenant isolation.
- Factories for tenant-owned models must assign valid school context.
- Never seed real student or minor data.

## Migration Safety

- Keep migrations deterministic and reversible where practical.
- Do not modify `composer.json` or install packages for database work unless explicitly requested.
- When schema decisions are unclear, update documentation before writing migrations.
