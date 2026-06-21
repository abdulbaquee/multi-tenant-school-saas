<?php

namespace App\Services;

use App\Models\Role;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Arr;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;
use Throwable;

class StudentService
{
    /**
     * @var list<string>
     */
    private const PRIVACY_COLUMNS = [
        'id',
        'school_id',
        'admission_no',
        'first_name',
        'last_name',
        'status',
        'deleted_at',
    ];

    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly SecurityLogService $securityLogs,
    ) {}

    /**
     * @param  array{search?: string|null, state?: string|null, school_id?: int|string|null}  $filters
     * @return LengthAwarePaginator<int, Student>
     */
    public function listFor(User $actor, array $filters): LengthAwarePaginator
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('viewAny', Student::class));

        $query = Student::query();
        $teacher = null;

        if ($actor->isSuperAdmin()) {
            $schoolId = $this->normalizeSchoolId($filters['school_id'] ?? null);

            if ($schoolId === null) {
                return $query->whereRaw('1 = 0')->paginate(15)->withQueryString();
            }

            $this->selectedSchool($schoolId);
            $query->withTrashed()
                ->where('school_id', $schoolId)
                ->select(self::PRIVACY_COLUMNS);
        } elseif ($this->isTeacher($actor)) {
            $teacher = $this->activeTeacherProfile($actor);
            $query->select(self::PRIVACY_COLUMNS);
            $this->applyTeacherAssignmentScope($query, $teacher);
        } else {
            $query->withTrashed();
        }

        if (! $this->isTeacher($actor)) {
            $query
                ->when(($filters['state'] ?? null) === Student::STATUS_ACTIVE, fn (Builder $query) => $query->whereNull('deleted_at')->where('status', Student::STATUS_ACTIVE))
                ->when(($filters['state'] ?? null) === Student::STATUS_INACTIVE, fn (Builder $query) => $query->whereNull('deleted_at')->where('status', Student::STATUS_INACTIVE))
                ->when(($filters['state'] ?? null) === Student::STATUS_TRANSFERRED, fn (Builder $query) => $query->whereNull('deleted_at')->where('status', Student::STATUS_TRANSFERRED))
                ->when(($filters['state'] ?? null) === Student::STATUS_GRADUATED, fn (Builder $query) => $query->whereNull('deleted_at')->where('status', Student::STATUS_GRADUATED))
                ->when(($filters['state'] ?? null) === 'archived', fn (Builder $query) => $query->whereNotNull('deleted_at'));
        }

        return $query
            ->with([
                'enrollments' => function (HasMany $query) use ($teacher): void {
                    $query->where('status', StudentEnrollment::STATUS_ACTIVE);

                    if ($teacher instanceof Teacher) {
                        $this->applyTeacherEnrollmentScope($query, $teacher);
                    }

                    $query->with(['academicYear', 'schoolClass', 'section'])
                        ->latest('enrollment_date');
                },
            ])
            ->when(filled($filters['search'] ?? null), function (Builder $query) use ($filters): void {
                $search = '%'.trim((string) $filters['search']).'%';
                $query->where(fn (Builder $query) => $query
                    ->where('admission_no', 'like', $search)
                    ->orWhere('first_name', 'like', $search)
                    ->orWhere('last_name', 'like', $search));
            })
            ->orderBy('first_name')
            ->orderBy('last_name')
            ->orderBy('admission_no')
            ->paginate(15)
            ->withQueryString();
    }

    /**
     * @return Collection<int, School>
     */
    public function selectableSchoolsFor(User $actor): Collection
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('viewAny', Student::class));

        if (! $actor->isSuperAdmin()) {
            return new Collection;
        }

        return School::query()
            ->select(['id', 'name', 'code', 'status'])
            ->orderBy('name')
            ->get();
    }

    public function detailsFor(Student $student, User $actor, ?int $selectedSchoolId = null): Student
    {
        $this->authorizeActorContext($actor);
        $teacher = null;

        if ($actor->isSuperAdmin()) {
            $school = $this->selectedSchool($selectedSchoolId);
            $student = Student::query()
                ->withTrashed()
                ->whereKey($student->getKey())
                ->where('school_id', $school->id)
                ->select(self::PRIVACY_COLUMNS)
                ->firstOr(fn () => throw $this->notFound($student));
        } elseif ($this->isTeacher($actor)) {
            $teacher = $this->activeTeacherProfile($actor);
            $query = Student::query()
                ->whereKey($student->getKey())
                ->select(self::PRIVACY_COLUMNS);
            $this->applyTeacherAssignmentScope($query, $teacher);
            $student = $query->firstOr(fn () => throw $this->notFound($student));
        }

        $this->authorize($actor->can('view', $student));

        $includeStudent = $actor->hasRoleCode(Role::SCHOOL_ADMIN);
        $enrollmentQuery = function (HasMany $query) use ($teacher, $includeStudent): void {
            if ($teacher instanceof Teacher) {
                $query->where('status', StudentEnrollment::STATUS_ACTIVE);
                $this->applyTeacherEnrollmentScope($query, $teacher);
            }

            $relations = ['academicYear', 'schoolClass', 'section'];

            if ($includeStudent) {
                $relations[] = 'student';
            }

            $query->with($relations)
                ->latest('enrollment_date');
        };

        return $student->load(['enrollments' => $enrollmentQuery, 'school']);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): Student
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('create', Student::class));

        $photo = $data['photo'] ?? null;
        $photoPath = $photo instanceof UploadedFile ? $this->storePhoto($photo) : null;

        try {
            return DB::transaction(function () use ($data, $actor, $photoPath): Student {
                $this->lockTenantSchool();
                $student = Student::create([
                    ...Arr::except($data, ['photo']),
                    'photo_path' => $photoPath,
                    'status' => Student::STATUS_ACTIVE,
                ]);
                $this->logMutation($student, $actor, 'created', [], $this->auditValues($student, filled($photoPath)));

                return $student;
            });
        } catch (Throwable $exception) {
            $this->deletePhoto($photoPath);

            throw $exception;
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Student $student, array $data, User $actor): Student
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('update', $student));

        return DB::transaction(function () use ($student, $data, $actor): Student {
            $this->lockTenantSchool();
            $lockedStudent = Student::query()->lockForUpdate()->findOrFail($student->getKey());

            if (! in_array($lockedStudent->status, [Student::STATUS_ACTIVE, Student::STATUS_INACTIVE], true)) {
                throw new AuthorizationException;
            }

            $oldValues = $this->auditValues($lockedStudent);
            $lockedStudent->fill($data)->save();
            $this->logChangedMutation($lockedStudent, $actor, 'updated', $oldValues);

            return $lockedStudent;
        });
    }

    public function activate(Student $student, User $actor): Student
    {
        return $this->changeStatus(
            $student,
            $actor,
            Student::STATUS_INACTIVE,
            Student::STATUS_ACTIVE,
            'activate',
            'activated',
        );
    }

    public function deactivate(Student $student, User $actor): Student
    {
        return $this->changeStatus(
            $student,
            $actor,
            Student::STATUS_ACTIVE,
            Student::STATUS_INACTIVE,
            'deactivate',
            'deactivated',
        );
    }

    public function archive(Student $student, User $actor): Student
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('archive', $student));

        return DB::transaction(function () use ($student, $actor): Student {
            $this->lockTenantSchool();
            $lockedStudent = Student::query()->lockForUpdate()->findOrFail($student->getKey());

            if ($lockedStudent->status !== Student::STATUS_INACTIVE) {
                throw new AuthorizationException;
            }

            if ($lockedStudent->enrollments()->where('status', StudentEnrollment::STATUS_ACTIVE)->exists()) {
                throw ValidationException::withMessages([
                    'status' => 'Complete or transfer the active Enrollment before archiving this Student.',
                ]);
            }

            $oldValues = $this->auditValues($lockedStudent);
            $lockedStudent->delete();
            $this->logChangedMutation($lockedStudent, $actor, 'archived', $oldValues);

            return $lockedStudent;
        });
    }

    public function restore(Student $student, User $actor): Student
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('restore', $student));

        return DB::transaction(function () use ($student, $actor): Student {
            $this->lockTenantSchool();
            $lockedStudent = Student::query()
                ->withTrashed()
                ->lockForUpdate()
                ->findOrFail($student->getKey());

            if (! $lockedStudent->trashed()) {
                throw new AuthorizationException;
            }

            $oldValues = $this->auditValues($lockedStudent);
            $lockedStudent->restore();
            $lockedStudent->forceFill(['status' => Student::STATUS_INACTIVE])->save();
            $this->logChangedMutation($lockedStudent, $actor, 'restored', $oldValues);

            return $lockedStudent;
        });
    }

    public function replacePhoto(Student $student, UploadedFile $photo, User $actor): Student
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('updatePhoto', $student));
        $newPath = $this->storePhoto($photo);

        try {
            [$updatedStudent, $oldPath] = DB::transaction(function () use ($student, $actor, $newPath): array {
                $this->lockTenantSchool();
                $lockedStudent = Student::query()->lockForUpdate()->findOrFail($student->getKey());

                if ($lockedStudent->status !== Student::STATUS_ACTIVE) {
                    throw new AuthorizationException;
                }

                $oldPath = $lockedStudent->photo_path;
                $lockedStudent->forceFill(['photo_path' => $newPath])->save();
                $this->logPhotoMutation($lockedStudent, $actor);

                return [$lockedStudent, $oldPath];
            });
        } catch (Throwable $exception) {
            $this->deletePhoto($newPath);

            throw $exception;
        }

        $this->deletePhoto($oldPath);

        return $updatedStudent;
    }

    public function removePhoto(Student $student, User $actor): Student
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('updatePhoto', $student));

        [$updatedStudent, $oldPath] = DB::transaction(function () use ($student, $actor): array {
            $this->lockTenantSchool();
            $lockedStudent = Student::query()->lockForUpdate()->findOrFail($student->getKey());

            if ($lockedStudent->status !== Student::STATUS_ACTIVE || blank($lockedStudent->photo_path)) {
                throw new AuthorizationException;
            }

            $oldPath = $lockedStudent->photo_path;
            $lockedStudent->forceFill(['photo_path' => null])->save();
            $this->logPhotoMutation($lockedStudent, $actor);

            return [$lockedStudent, $oldPath];
        });

        $this->deletePhoto($oldPath);

        return $updatedStudent;
    }

    public function photoPathFor(Student $student, User $actor): string
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('viewPhoto', $student));

        if (blank($student->photo_path) || ! Storage::disk('local')->exists($student->photo_path)) {
            throw $this->notFound($student);
        }

        return $student->photo_path;
    }

    private function changeStatus(
        Student $student,
        User $actor,
        string $from,
        string $to,
        string $ability,
        string $action,
    ): Student {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can($ability, $student));

        return DB::transaction(function () use ($student, $actor, $from, $to, $action): Student {
            $this->lockTenantSchool();
            $lockedStudent = Student::query()->lockForUpdate()->findOrFail($student->getKey());

            if ($lockedStudent->status !== $from) {
                throw new AuthorizationException;
            }

            $oldValues = $this->auditValues($lockedStudent);
            $lockedStudent->forceFill(['status' => $to])->save();
            $this->logChangedMutation($lockedStudent, $actor, $action, $oldValues);

            return $lockedStudent;
        });
    }

    private function authorizeActorContext(User $actor): void
    {
        $validSchoolRole = $actor->hasRoleCode(Role::SCHOOL_ADMIN) || $this->isTeacher($actor);
        $valid = $actor->canEstablishTenantContext() && ($actor->isSuperAdmin()
            ? $this->tenantContext->isPlatform()
            : $validSchoolRole
                && filled($actor->school_id)
                && $this->tenantContext->isTenant()
                && $this->tenantContext->tenantId() === (int) $actor->school_id);

        $this->authorize($valid);
    }

    private function authorize(bool $allowed): void
    {
        if (! $allowed) {
            throw new AuthorizationException;
        }
    }

    private function isTeacher(User $actor): bool
    {
        return $actor->hasRoleCode(Role::TEACHER);
    }

    private function activeTeacherProfile(User $actor): Teacher
    {
        $teacher = $actor->teacherProfile()
            ->where('status', Teacher::STATUS_ACTIVE)
            ->first();

        if (! $teacher instanceof Teacher) {
            throw new AuthorizationException;
        }

        return $teacher;
    }

    /**
     * @param  Builder<Student>  $query
     */
    private function applyTeacherAssignmentScope(Builder $query, Teacher $teacher): void
    {
        $query
            ->where('status', Student::STATUS_ACTIVE)
            ->whereHas('enrollments', function (Builder $query) use ($teacher): void {
                $query->where('status', StudentEnrollment::STATUS_ACTIVE);
                $this->applyTeacherEnrollmentScope($query, $teacher);
            });
    }

    /**
     * @param  Builder<StudentEnrollment>|HasMany<StudentEnrollment, Student>  $query
     */
    private function applyTeacherEnrollmentScope(Builder|HasMany $query, Teacher $teacher): void
    {
        $query
            ->whereHas('schoolClass', fn (Builder $query) => $query
                ->whereNull('deleted_at')
                ->where('status', SchoolClass::STATUS_ACTIVE))
            ->whereHas('section', fn (Builder $query) => $query
                ->whereNull('deleted_at')
                ->where('status', Section::STATUS_ACTIVE))
            ->where(function (Builder $query) use ($teacher): void {
                $query->whereHas('section', fn (Builder $query) => $query
                    ->whereNull('deleted_at')
                    ->where('status', Section::STATUS_ACTIVE)
                    ->where('teacher_id', $teacher->id))
                    ->orWhereHas('schoolClass.subjects', fn (Builder $query) => $query
                        ->whereNull('deleted_at')
                        ->where('status', Subject::STATUS_ACTIVE)
                        ->where('teacher_id', $teacher->id));
            });
    }

    private function selectedSchool(?int $schoolId): School
    {
        if ($schoolId === null) {
            throw (new ModelNotFoundException)->setModel(School::class);
        }

        return School::query()
            ->whereKey($schoolId)
            ->firstOr(fn () => throw (new ModelNotFoundException)->setModel(School::class, [$schoolId]));
    }

    private function normalizeSchoolId(mixed $schoolId): ?int
    {
        return filter_var($schoolId, FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]) ?: null;
    }

    private function lockTenantSchool(): School
    {
        return School::query()
            ->whereKey($this->tenantContext->tenantId())
            ->where('status', School::STATUS_ACTIVE)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function storePhoto(UploadedFile $photo): string
    {
        $directory = 'student-photos/'.$this->tenantContext->tenantId();
        $filename = Str::uuid()->toString().'.'.$photo->extension();
        $path = $photo->storeAs($directory, $filename, 'local');

        if (! is_string($path)) {
            throw ValidationException::withMessages(['photo' => 'The Student photo could not be stored.']);
        }

        return $path;
    }

    private function deletePhoto(?string $path): void
    {
        if (filled($path)) {
            Storage::disk('local')->delete($path);
        }
    }

    /**
     * @param  array<string, mixed>  $oldValues
     */
    private function logChangedMutation(Student $student, User $actor, string $action, array $oldValues): void
    {
        $newValues = $this->auditValues($student);
        $changedKeys = array_keys(array_diff_assoc($newValues, $oldValues));

        $this->logMutation(
            $student,
            $actor,
            $action,
            array_intersect_key($oldValues, array_flip($changedKeys)),
            array_intersect_key($newValues, array_flip($changedKeys)),
        );
    }

    private function logPhotoMutation(Student $student, User $actor): void
    {
        $this->securityLogs->activity(
            $actor,
            'student_management',
            'updated',
            $student,
            'Student photo updated.',
        );
        $this->securityLogs->audit(
            $actor,
            $student,
            'updated',
            ['admission_no' => $student->admission_no, 'photo_changed' => false],
            ['admission_no' => $student->admission_no, 'photo_changed' => true],
        );
    }

    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     */
    private function logMutation(
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
    private function auditValues(Student $student, bool $photoChanged = false): array
    {
        return [
            'admission_no' => $student->admission_no,
            'status' => $student->status,
            'archived' => $student->trashed(),
            'photo_changed' => $photoChanged,
        ];
    }

    private function notFound(Student $student): ModelNotFoundException
    {
        return (new ModelNotFoundException)->setModel(Student::class, [$student->getKey()]);
    }
}
