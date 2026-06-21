<?php

namespace App\Services;

use App\Models\Role;
use App\Models\School;
use App\Models\Section;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class TeacherService
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly SecurityLogService $securityLogs,
    ) {}

    /**
     * @param  array{search?: string|null, state?: string|null}  $filters
     * @return LengthAwarePaginator<int, Teacher>
     */
    public function listFor(User $actor, array $filters): LengthAwarePaginator
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('viewAny', Teacher::class));

        return Teacher::query()
            ->withTrashed()
            ->with(['user', 'school'])
            ->withCount([
                'sections' => fn ($query) => $query->withTrashed(),
                'subjects' => fn ($query) => $query->withTrashed(),
            ])
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters): void {
                $search = '%'.trim((string) $filters['search']).'%';

                $query->where(function ($query) use ($search): void {
                    $query->where('employee_code', 'like', $search)
                        ->orWhere('qualification', 'like', $search)
                        ->orWhere('specialization', 'like', $search)
                        ->orWhereHas('user', function ($query) use ($search): void {
                            $query->where('name', 'like', $search)
                                ->orWhere('email', 'like', $search);
                        });
                });
            })
            ->when(($filters['state'] ?? null) === 'active', fn ($query) => $query->whereNull('deleted_at')->where('status', Teacher::STATUS_ACTIVE))
            ->when(($filters['state'] ?? null) === 'inactive', fn ($query) => $query->whereNull('deleted_at')->where('status', Teacher::STATUS_INACTIVE))
            ->when(($filters['state'] ?? null) === 'archived', fn ($query) => $query->whereNotNull('deleted_at'))
            ->orderBy('employee_code')
            ->paginate(15)
            ->withQueryString();
    }

    public function detailsFor(Teacher $teacher, User $actor): Teacher
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('view', $teacher));

        return $teacher->load([
            'user.role',
            'school',
            'sections' => fn ($query) => $query->withTrashed()->with('schoolClass')->orderBy('name'),
            'subjects' => fn ($query) => $query->withTrashed()->with('schoolClass')->orderBy('name'),
        ]);
    }

    /**
     * @return Collection<int, User>
     */
    public function eligibleUsersFor(User $actor): Collection
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('create', Teacher::class));

        $retainedUserIds = Teacher::query()->withTrashed()->pluck('user_id');

        return User::query()
            ->where('school_id', $this->tenantContext->tenantId())
            ->where('status', User::STATUS_ACTIVE)
            ->whereNull('deleted_at')
            ->whereHas('role', fn ($query) => $query->where('code', Role::TEACHER))
            ->whereNotIn('id', $retainedUserIds)
            ->orderBy('name')
            ->get();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): Teacher
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('create', Teacher::class));

        return DB::transaction(function () use ($data, $actor): Teacher {
            $this->lockTenantSchool();
            $this->eligibleLinkedUser((int) $data['user_id'], true);

            $teacher = Teacher::create([
                ...$data,
                'status' => Teacher::STATUS_ACTIVE,
            ]);

            $this->logMutation($teacher, $actor, 'created', [], $this->auditValues($teacher));

            return $teacher;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Teacher $teacher, array $data, User $actor): Teacher
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('update', $teacher));

        return DB::transaction(function () use ($teacher, $data, $actor): Teacher {
            $this->lockTenantSchool();
            $lockedTeacher = Teacher::query()->lockForUpdate()->findOrFail($teacher->getKey());
            $oldValues = $this->auditValues($lockedTeacher);
            $lockedTeacher->fill($data)->save();
            $this->logChangedMutation($lockedTeacher, $actor, 'updated', $oldValues);

            return $lockedTeacher;
        });
    }

    public function activate(Teacher $teacher, User $actor): Teacher
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('activate', $teacher));

        return DB::transaction(function () use ($teacher, $actor): Teacher {
            $this->lockTenantSchool();
            $lockedTeacher = Teacher::query()->lockForUpdate()->findOrFail($teacher->getKey());

            if ($lockedTeacher->status !== Teacher::STATUS_INACTIVE) {
                throw new AuthorizationException;
            }

            $this->eligibleLinkedUser((int) $lockedTeacher->user_id);
            $oldValues = $this->auditValues($lockedTeacher);
            $lockedTeacher->forceFill(['status' => Teacher::STATUS_ACTIVE])->save();
            $this->logChangedMutation($lockedTeacher, $actor, 'activated', $oldValues);

            return $lockedTeacher;
        });
    }

    public function deactivate(Teacher $teacher, User $actor): Teacher
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('deactivate', $teacher));

        return DB::transaction(function () use ($teacher, $actor): Teacher {
            $this->lockTenantSchool();
            $lockedTeacher = Teacher::query()->lockForUpdate()->findOrFail($teacher->getKey());

            if ($lockedTeacher->status !== Teacher::STATUS_ACTIVE) {
                throw new AuthorizationException;
            }

            $this->ensureNoActiveAssignments($lockedTeacher);
            $oldValues = $this->auditValues($lockedTeacher);
            $lockedTeacher->forceFill(['status' => Teacher::STATUS_INACTIVE])->save();
            $this->logChangedMutation($lockedTeacher, $actor, 'deactivated', $oldValues);

            return $lockedTeacher;
        });
    }

    public function archive(Teacher $teacher, User $actor): Teacher
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('archive', $teacher));

        return DB::transaction(function () use ($teacher, $actor): Teacher {
            $this->lockTenantSchool();
            $lockedTeacher = Teacher::query()->lockForUpdate()->findOrFail($teacher->getKey());

            if ($lockedTeacher->status !== Teacher::STATUS_INACTIVE) {
                throw new AuthorizationException;
            }

            $this->ensureNoActiveAssignments($lockedTeacher);
            $oldValues = $this->auditValues($lockedTeacher);
            $lockedTeacher->delete();
            $this->logChangedMutation($lockedTeacher, $actor, 'archived', $oldValues);

            return $lockedTeacher;
        });
    }

    public function restore(Teacher $teacher, User $actor): Teacher
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('restore', $teacher));

        return DB::transaction(function () use ($teacher, $actor): Teacher {
            $this->lockTenantSchool();
            $lockedTeacher = Teacher::query()
                ->withTrashed()
                ->lockForUpdate()
                ->findOrFail($teacher->getKey());

            if (! $lockedTeacher->trashed()) {
                throw new AuthorizationException;
            }

            $this->eligibleLinkedUser((int) $lockedTeacher->user_id);
            $oldValues = $this->auditValues($lockedTeacher);
            $lockedTeacher->restore();
            $lockedTeacher->forceFill(['status' => Teacher::STATUS_INACTIVE])->save();
            $this->logChangedMutation($lockedTeacher, $actor, 'restored', $oldValues);

            return $lockedTeacher;
        });
    }

    private function authorizeActorContext(User $actor): void
    {
        $valid = $actor->canEstablishTenantContext() && ($actor->isSuperAdmin()
            ? $this->tenantContext->isPlatform()
            : $actor->hasRoleCode(Role::SCHOOL_ADMIN)
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

    private function lockTenantSchool(): School
    {
        return School::query()
            ->whereKey($this->tenantContext->tenantId())
            ->where('status', School::STATUS_ACTIVE)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function eligibleLinkedUser(int $userId, bool $mustBeUnlinked = false): User
    {
        $user = User::query()
            ->whereKey($userId)
            ->where('school_id', $this->tenantContext->tenantId())
            ->where('status', User::STATUS_ACTIVE)
            ->whereNull('deleted_at')
            ->whereHas('role', fn ($query) => $query->where('code', Role::TEACHER))
            ->lockForUpdate()
            ->first();

        if (! $user instanceof User) {
            throw ValidationException::withMessages([
                'user_id' => 'Select an active Teacher user from this school.',
            ]);
        }

        if ($mustBeUnlinked && Teacher::query()->withTrashed()->where('user_id', $user->id)->exists()) {
            throw ValidationException::withMessages([
                'user_id' => 'This user already has a retained Teacher Profile.',
            ]);
        }

        return $user;
    }

    private function ensureNoActiveAssignments(Teacher $teacher): void
    {
        $activeSections = Section::query()
            ->where('teacher_id', $teacher->id)
            ->where('status', Section::STATUS_ACTIVE)
            ->exists();
        $activeSubjects = Subject::query()
            ->where('teacher_id', $teacher->id)
            ->where('status', Subject::STATUS_ACTIVE)
            ->exists();

        if ($activeSections || $activeSubjects) {
            throw ValidationException::withMessages([
                'status' => 'Remove or deactivate every active Section and Subject assignment first.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $oldValues
     */
    private function logChangedMutation(Teacher $teacher, User $actor, string $action, array $oldValues): void
    {
        $newValues = $this->auditValues($teacher);
        $changedKeys = array_keys(array_diff_assoc($newValues, $oldValues));

        $this->logMutation(
            $teacher,
            $actor,
            $action,
            array_intersect_key($oldValues, array_flip($changedKeys)),
            array_intersect_key($newValues, array_flip($changedKeys)),
        );
    }

    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     */
    private function logMutation(
        Teacher $teacher,
        User $actor,
        string $action,
        array $oldValues,
        array $newValues,
    ): void {
        $this->securityLogs->activity(
            $actor,
            'academic_structure',
            $action,
            $teacher,
            'Teacher Profile '.$action.'.',
        );
        $this->securityLogs->audit($actor, $teacher, $action, $oldValues, $newValues);
    }

    /**
     * @return array<string, mixed>
     */
    private function auditValues(Teacher $teacher): array
    {
        return [
            'user_id' => (int) $teacher->user_id,
            'employee_code' => $teacher->employee_code,
            'qualification' => $teacher->qualification,
            'specialization' => $teacher->specialization,
            'phone' => $teacher->phone,
            'joining_date' => $teacher->joining_date?->toDateString(),
            'status' => $teacher->status,
            'archived' => $teacher->trashed(),
        ];
    }
}
