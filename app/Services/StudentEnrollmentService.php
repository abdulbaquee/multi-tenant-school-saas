<?php

namespace App\Services;

use App\Models\AcademicYear;
use App\Models\Role;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\User;
use App\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\QueryException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class StudentEnrollmentService
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly SecurityLogService $securityLogs,
    ) {}

    /**
     * @return array{
     *     academicYear: AcademicYear|null,
     *     classes: Collection<int, SchoolClass>,
     *     hasActiveEnrollment: bool,
     *     alreadyEnrolledCurrentYear: bool
     * }
     */
    public function formOptions(Student $student, User $actor): array
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('enroll', $student));

        $academicYear = AcademicYear::query()
            ->where('status', AcademicYear::STATUS_ACTIVE)
            ->where('is_current', true)
            ->first();

        $classes = new Collection;

        if ($academicYear instanceof AcademicYear) {
            $classes = SchoolClass::query()
                ->where('status', SchoolClass::STATUS_ACTIVE)
                ->whereHas('sections', fn ($query) => $query
                    ->whereNull('deleted_at')
                    ->where('status', Section::STATUS_ACTIVE))
                ->with(['sections' => fn ($query) => $query
                    ->whereNull('deleted_at')
                    ->where('status', Section::STATUS_ACTIVE)
                    ->orderBy('name')])
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get();
        }

        $hasActiveEnrollment = $student->enrollments()
            ->where('status', StudentEnrollment::STATUS_ACTIVE)
            ->exists();
        $alreadyEnrolledCurrentYear = $academicYear instanceof AcademicYear
            && $student->enrollments()->where('academic_year_id', $academicYear->id)->exists();

        return compact('academicYear', 'classes', 'hasActiveEnrollment', 'alreadyEnrolledCurrentYear');
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(Student $student, array $data, User $actor): StudentEnrollment
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('enroll', $student));

        return DB::transaction(function () use ($student, $data, $actor): StudentEnrollment {
            $this->lockTenantSchool();
            $lockedStudent = Student::query()->lockForUpdate()->findOrFail($student->getKey());
            $this->authorize($actor->can('enroll', $lockedStudent));

            if ($lockedStudent->enrollments()->where('status', StudentEnrollment::STATUS_ACTIVE)->lockForUpdate()->exists()) {
                throw ValidationException::withMessages([
                    'student_id' => 'Complete the active Enrollment before creating another placement.',
                ]);
            }

            $academicYear = AcademicYear::query()
                ->whereKey((int) $data['academic_year_id'])
                ->where('status', AcademicYear::STATUS_ACTIVE)
                ->where('is_current', true)
                ->lockForUpdate()
                ->firstOrFail();
            $schoolClass = SchoolClass::query()
                ->whereKey((int) $data['class_id'])
                ->where('status', SchoolClass::STATUS_ACTIVE)
                ->lockForUpdate()
                ->firstOrFail();
            $section = Section::query()
                ->whereKey((int) $data['section_id'])
                ->where('status', Section::STATUS_ACTIVE)
                ->lockForUpdate()
                ->firstOrFail();

            if ((int) $section->class_id !== (int) $schoolClass->id) {
                throw ValidationException::withMessages([
                    'section_id' => 'The selected Section must belong to the selected Class.',
                ]);
            }

            $this->validateEnrollmentDate($lockedStudent, $academicYear, (string) $data['enrollment_date']);
            $this->validateUniqueness($lockedStudent, $academicYear, $schoolClass, $section, (string) $data['roll_no']);

            try {
                $enrollment = StudentEnrollment::create([
                    'student_id' => $lockedStudent->id,
                    'academic_year_id' => $academicYear->id,
                    'class_id' => $schoolClass->id,
                    'section_id' => $section->id,
                    'roll_no' => $data['roll_no'],
                    'enrollment_date' => $data['enrollment_date'],
                    'status' => StudentEnrollment::STATUS_ACTIVE,
                ]);
            } catch (QueryException $exception) {
                if (! $this->isConstraintViolation($exception)) {
                    throw $exception;
                }

                throw ValidationException::withMessages([
                    'roll_no' => 'The Student placement or roll number already exists.',
                ]);
            }

            $this->logEnrollmentMutation($enrollment, $actor, 'created', [], $this->enrollmentAuditValues($enrollment));

            return $enrollment;
        });
    }

    public function complete(StudentEnrollment $enrollment, User $actor): StudentEnrollment
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('complete', $enrollment));

        return DB::transaction(function () use ($enrollment, $actor): StudentEnrollment {
            $this->lockTenantSchool();
            $lockedEnrollment = StudentEnrollment::query()->lockForUpdate()->findOrFail($enrollment->getKey());
            $lockedStudent = Student::query()->lockForUpdate()->findOrFail($lockedEnrollment->student_id);

            $this->authorize($actor->can('complete', $lockedEnrollment));
            $this->authorize($lockedStudent->status === Student::STATUS_ACTIVE && ! $lockedStudent->trashed());

            $oldValues = $this->enrollmentAuditValues($lockedEnrollment);
            $lockedEnrollment->forceFill(['status' => StudentEnrollment::STATUS_COMPLETED])->save();
            $this->logEnrollmentMutation(
                $lockedEnrollment,
                $actor,
                'completed',
                $oldValues,
                $this->enrollmentAuditValues($lockedEnrollment),
            );

            return $lockedEnrollment;
        });
    }

    public function transfer(Student $student, User $actor): Student
    {
        return $this->applyTerminalLifecycle(
            $student,
            $actor,
            'transfer',
            Student::STATUS_TRANSFERRED,
            StudentEnrollment::STATUS_TRANSFERRED,
            'transferred',
        );
    }

    public function graduate(Student $student, User $actor): Student
    {
        return $this->applyTerminalLifecycle(
            $student,
            $actor,
            'graduate',
            Student::STATUS_GRADUATED,
            StudentEnrollment::STATUS_COMPLETED,
            'graduated',
        );
    }

    private function applyTerminalLifecycle(
        Student $student,
        User $actor,
        string $ability,
        string $studentStatus,
        string $enrollmentStatus,
        string $action,
    ): Student {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can($ability, $student));

        return DB::transaction(function () use ($student, $actor, $ability, $studentStatus, $enrollmentStatus, $action): Student {
            $this->lockTenantSchool();
            $lockedStudent = Student::query()->lockForUpdate()->findOrFail($student->getKey());
            $this->authorize($actor->can($ability, $lockedStudent));

            $activeEnrollments = StudentEnrollment::query()
                ->where('student_id', $lockedStudent->id)
                ->where('status', StudentEnrollment::STATUS_ACTIVE)
                ->lockForUpdate()
                ->get();

            if ($activeEnrollments->count() !== 1) {
                throw ValidationException::withMessages([
                    'status' => 'The Student must have exactly one active Enrollment for this lifecycle action.',
                ]);
            }

            $enrollment = $activeEnrollments->firstOrFail();
            $oldEnrollmentValues = $this->enrollmentAuditValues($enrollment);
            $oldStudentValues = $this->studentAuditValues($lockedStudent);
            $enrollment->forceFill(['status' => $enrollmentStatus])->save();
            $lockedStudent->forceFill(['status' => $studentStatus])->save();

            $this->logEnrollmentMutation(
                $enrollment,
                $actor,
                $action,
                $oldEnrollmentValues,
                $this->enrollmentAuditValues($enrollment),
            );
            $this->logStudentMutation(
                $lockedStudent,
                $actor,
                $action,
                $oldStudentValues,
                $this->studentAuditValues($lockedStudent),
            );

            return $lockedStudent;
        });
    }

    private function validateEnrollmentDate(Student $student, AcademicYear $academicYear, string $enrollmentDate): void
    {
        $date = CarbonImmutable::parse($enrollmentDate);

        if ($date->lt($academicYear->start_date)
            || $date->gt($academicYear->end_date)
            || $date->lt($student->admission_date)) {
            throw ValidationException::withMessages([
                'enrollment_date' => 'The Enrollment date must be within the Academic Year and on or after admission.',
            ]);
        }
    }

    private function validateUniqueness(
        Student $student,
        AcademicYear $academicYear,
        SchoolClass $schoolClass,
        Section $section,
        string $rollNo,
    ): void {
        if (StudentEnrollment::query()
            ->where('student_id', $student->id)
            ->where('academic_year_id', $academicYear->id)
            ->exists()) {
            throw ValidationException::withMessages([
                'academic_year_id' => 'The Student already has an Enrollment for this Academic Year.',
            ]);
        }

        if (StudentEnrollment::query()
            ->where('academic_year_id', $academicYear->id)
            ->where('class_id', $schoolClass->id)
            ->where('section_id', $section->id)
            ->where('roll_no', $rollNo)
            ->exists()) {
            throw ValidationException::withMessages([
                'roll_no' => 'The roll number is already assigned in this Class and Section.',
            ]);
        }
    }

    private function authorizeActorContext(User $actor): void
    {
        $valid = $actor->canEstablishTenantContext()
            && $actor->hasRoleCode(Role::SCHOOL_ADMIN)
            && filled($actor->school_id)
            && $this->tenantContext->isTenant()
            && $this->tenantContext->tenantId() === (int) $actor->school_id;

        $this->authorize($valid);
    }

    private function authorize(bool $allowed): void
    {
        if (! $allowed) {
            throw new AuthorizationException;
        }
    }

    private function lockTenantSchool(): School
    {
        return School::query()
            ->whereKey($this->tenantContext->tenantId())
            ->where('status', School::STATUS_ACTIVE)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function isConstraintViolation(QueryException $exception): bool
    {
        return in_array((string) $exception->getCode(), ['19', '23000'], true);
    }

    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     */
    private function logEnrollmentMutation(
        StudentEnrollment $enrollment,
        User $actor,
        string $action,
        array $oldValues,
        array $newValues,
    ): void {
        $this->securityLogs->activity(
            $actor,
            'student_management',
            $action,
            $enrollment,
            'Student Enrollment '.$action.'.',
        );
        $this->securityLogs->audit($actor, $enrollment, $action, $oldValues, $newValues);
    }

    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     */
    private function logStudentMutation(
        Student $student,
        User $actor,
        string $action,
        array $oldValues,
        array $newValues,
    ): void {
        $this->securityLogs->activity(
            $actor,
            'student_management',
            $action,
            $student,
            'Student '.$action.'.',
        );
        $this->securityLogs->audit($actor, $student, $action, $oldValues, $newValues);
    }

    /**
     * @return array<string, mixed>
     */
    private function enrollmentAuditValues(StudentEnrollment $enrollment): array
    {
        return [
            'student_id' => $enrollment->student_id,
            'academic_year_id' => $enrollment->academic_year_id,
            'class_id' => $enrollment->class_id,
            'section_id' => $enrollment->section_id,
            'roll_no' => $enrollment->roll_no,
            'status' => $enrollment->status,
        ];
    }

    /**
     * @return array<string, mixed>
     */
    private function studentAuditValues(Student $student): array
    {
        return [
            'admission_no' => $student->admission_no,
            'status' => $student->status,
        ];
    }
}
