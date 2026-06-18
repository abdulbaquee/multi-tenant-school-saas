# CODING STANDARDS

Version: 1.0

Project:
Multi-Tenant School Administration Management SaaS Platform

Program:
Master of Computer Applications (MCA)

Framework:
Laravel 13

Language:
PHP 8.4

Frontend:
Blade Templates + Bootstrap 5

---

# 1. PURPOSE

This document defines the coding standards and development guidelines used throughout the project.

The objective is to ensure:

* Consistent code structure
* Maintainable source code
* Readable implementation
* Reduced technical debt
* Easier debugging and testing
* Professional development practices

---

# 2. GENERAL PRINCIPLES

Follow:

* SOLID Principles
* DRY (Don't Repeat Yourself)
* KISS (Keep It Simple, Stupid)
* Separation of Concerns
* Convention Over Configuration

Code should prioritize:

1. Readability
2. Maintainability
3. Security
4. Performance

---

# 3. PHP STANDARDS

Follow:

* PSR-1
* PSR-12
* PSR-4 Autoloading

Use:

* Strict typing where appropriate
* Type hints
* Return types

Example:

```php
public function findStudent(int $studentId): ?Student
{
    return Student::find($studentId);
}
```

---

# 4. NAMING CONVENTIONS

## Classes

PascalCase

Examples:

```text
StudentController
AttendanceService
FeePayment
ExamResult
```

---

## Methods

camelCase

Examples:

```text
storeStudent()
calculateGrade()
generateReport()
```

---

## Variables

camelCase

Examples:

```text
$studentId
$attendanceDate
$totalFees
```

---

## Constants

UPPER_SNAKE_CASE

Examples:

```text
ROLE_SUPER_ADMIN
STATUS_ACTIVE
STATUS_INACTIVE
```

---

## Database Tables

snake_case
plural

Examples:

```text
students
attendances
fee_payments
exam_results
```

---

## Database Columns

snake_case

Examples:

```text
first_name
last_name
attendance_date
school_id
```

---

# 5. DIRECTORY STRUCTURE

Application code should follow:

```text
app/
├── Http/
│   ├── Controllers/
│   ├── Middleware/
│   └── Requests/
│
├── Models/
├── Policies/
├── Services/
├── Providers/
```

Business logic must not be placed inside controllers.

---

# 6. CONTROLLER STANDARDS

Controllers must:

* Remain thin
* Validate requests
* Call services
* Return responses

Controllers must NOT:

* Contain complex business logic
* Execute large queries
* Perform calculations

Example:

```php
public function store(StoreStudentRequest $request)
{
    $this->studentService->create($request->validated());

    return redirect()
        ->route('students.index')
        ->with('success', 'Student created successfully.');
}
```

---

# 7. SERVICE LAYER STANDARDS

Business logic belongs inside services.

Examples:

```text
StudentService
AttendanceService
FeeService
ExamService
ReportService
```

Responsibilities:

* Data processing
* Calculations
* Workflows
* Transactions

---

# 8. MODEL STANDARDS

Models should contain:

* Relationships
* Scopes
* Accessors
* Mutators

Avoid:

* Large business logic
* Complex workflows

Example:

```php
public function school()
{
    return $this->belongsTo(School::class);
}
```

---

# 9. REQUEST VALIDATION

Always use Form Requests.

Never validate directly inside controllers.

Example:

```php
StoreStudentRequest
UpdateStudentRequest
```

Location:

```text
app/Http/Requests
```

---

# 10. DATABASE STANDARDS

## Multi-Tenant Rule

Every business table must contain:

```text
school_id
```

Examples:

```text
students
attendances
subjects
exams
fee_payments
```

All queries must respect tenant isolation.

---

## Foreign Keys

Use proper foreign key constraints.

Example:

```php
$table->foreignId('school_id')
      ->constrained()
      ->cascadeOnDelete();
```

---

## Soft Deletes

Use Soft Deletes for:

* Schools
* Users
* Teachers
* Students
* Classes
* Sections
* Subjects
* Exams

---

# 11. QUERY STANDARDS

Prefer Eloquent relationships.

Good:

```php
Student::with('class', 'section')->paginate();
```

Avoid:

```php
DB::table(...)
```

unless necessary for performance.

Prevent:

* N+1 Queries
* Unindexed searches
* Large result sets

---

# 12. SECURITY STANDARDS

Always:

* Validate inputs
* Escape output
* Use CSRF protection
* Hash passwords
* Authorize actions
* Enforce tenant isolation

Never:

* Trust user input
* Store plain-text passwords
* Expose sensitive data

---

# 13. AUTHORIZATION STANDARDS

Use:

* Middleware
* Policies
* Gates

Roles:

* Super Admin
* School Admin
* Teacher
* Accountant

Every protected action must be authorized.

---

# 14. ERROR HANDLING

Use:

```php
try {
    // logic
} catch (\Exception $exception) {
    Log::error($exception->getMessage());
}
```

Log unexpected errors.

Show user-friendly messages.

Never expose stack traces in production.

---

# 15. LOGGING STANDARDS

Log:

* Authentication Events
* Attendance Updates
* Fee Payments
* Examination Updates
* Critical Errors

Use:

```php
Log::info()
Log::warning()
Log::error()
```

---

# 16. TESTING STANDARDS

Required Tests:

* Feature Tests
* Authentication Tests
* Authorization Tests
* Tenant Isolation Tests

Coverage Focus:

* Student Management
* Attendance
* Fees
* Examinations

---

# 17. GIT STANDARDS

Commit messages should be meaningful.

Good Examples:

```text
Add student management module

Implement attendance service

Create fee payment migration

Add examination reporting
```

Avoid:

```text
fix

update

changes
```

---

# 18. DOCUMENTATION STANDARDS

Every major module must include:

* Purpose
* Features
* Relationships
* Business Rules

Documentation must be updated whenever architecture changes.

---

# 19. CODE REVIEW CHECKLIST

Before committing code:

✓ Validation Implemented

✓ Authorization Implemented

✓ Tenant Isolation Applied

✓ Business Logic In Services

✓ Database Indexes Considered

✓ No Debug Code Left

✓ No Sensitive Data Exposed

✓ Tests Updated

---

# 20. SUCCESS CRITERIA

Code quality standards are successful when:

✓ Code Is Consistent

✓ Controllers Remain Thin

✓ Business Logic Uses Services

✓ Tenant Isolation Is Enforced

✓ Security Best Practices Are Followed

✓ Database Queries Are Optimized

✓ Documentation Remains Current

✓ Code Is Easy To Explain During MCA Viva
