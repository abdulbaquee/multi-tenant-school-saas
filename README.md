# Multi-Tenant School SaaS (Laravel 13)

A Laravel 13 school management SaaS starter designed for **multi-tenant isolation** with school onboarding and core operational modules.

## Stack

- Laravel 13
- PHP 8.4 target runtime (compatible with local development constraints)
- MySQL (or SQLite for local tests)
- Bootstrap 5 responsive UI

## Implemented Modules

- School onboarding (`/onboarding`)
- Tenant isolation using tenant middleware + scoped models
- Role-based access control middleware (`school_admin`, `teacher`, `accountant`)
- Student management
- Attendance tracking
- Fee management (invoice + mark paid)
- Examination + grading
- Reporting dashboard
- Audit logging middleware for mutating requests

## Key Routes

- `/onboarding`
- `/schools/{school:slug}/dashboard`
- `/schools/{school:slug}/students`
- `/schools/{school:slug}/attendance`
- `/schools/{school:slug}/fees`
- `/schools/{school:slug}/exams`
- `/schools/{school:slug}/reports`

## Tests Added

- `tests/Feature/OnboardingTest.php`
- `tests/Feature/TenantIsolationTest.php`
