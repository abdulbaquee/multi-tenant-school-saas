<?php

namespace App\Services;

use App\Models\Role;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;
use Illuminate\Validation\ValidationException;

class SchoolClassService
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly SecurityLogService $securityLogs,
    ) {}

    /**
     * @param  array{search?: string|null, state?: string|null}  $filters
     * @return LengthAwarePaginator<int, SchoolClass>
     */
    public function listFor(User $actor, array $filters): LengthAwarePaginator
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('viewAny', SchoolClass::class));

        $query = SchoolClass::query()->with(['school']);

        if ($this->isTeacher($actor)) {
            $teacher = $this->activeTeacherProfile($actor);
            $query->where('status', SchoolClass::STATUS_ACTIVE)
                ->where(function ($query) use ($teacher): void {
                    $query->whereHas('sections', fn ($query) => $query
                        ->where('teacher_id', $teacher->id)
                        ->where('status', Section::STATUS_ACTIVE))
                        ->orWhereHas('subjects', fn ($query) => $query
                            ->where('teacher_id', $teacher->id)
                            ->where('status', Subject::STATUS_ACTIVE));
                })
                ->withCount([
                    'sections' => fn ($query) => $query
                        ->where('teacher_id', $teacher->id)
                        ->where('status', Section::STATUS_ACTIVE),
                    'subjects' => fn ($query) => $query
                        ->where('teacher_id', $teacher->id)
                        ->where('status', Subject::STATUS_ACTIVE),
                ]);
        } else {
            $query->withTrashed()
                ->when(($filters['state'] ?? null) === 'active', fn ($query) => $query->whereNull('deleted_at')->where('status', SchoolClass::STATUS_ACTIVE))
                ->when(($filters['state'] ?? null) === 'inactive', fn ($query) => $query->whereNull('deleted_at')->where('status', SchoolClass::STATUS_INACTIVE))
                ->when(($filters['state'] ?? null) === 'archived', fn ($query) => $query->whereNotNull('deleted_at'))
                ->withCount([
                    'sections' => fn ($query) => $query->withTrashed(),
                    'subjects' => fn ($query) => $query->withTrashed(),
                ]);
        }

        return $query
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters): void {
                $search = '%'.trim((string) $filters['search']).'%';
                $query->where(fn ($query) => $query
                    ->where('name', 'like', $search)
                    ->orWhere('code', 'like', $search));
            })
            ->orderBy('sort_order')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();
    }

    public function detailsFor(SchoolClass $schoolClass, User $actor): SchoolClass
    {
        $this->authorizeActorContext($actor);

        if ($this->isTeacher($actor)) {
            $teacher = $this->activeTeacherProfile($actor);
            $schoolClass = SchoolClass::query()
                ->whereKey($schoolClass->getKey())
                ->where('status', SchoolClass::STATUS_ACTIVE)
                ->where(function ($query) use ($teacher): void {
                    $query->whereHas('sections', fn ($query) => $query
                        ->where('teacher_id', $teacher->id)
                        ->where('status', Section::STATUS_ACTIVE))
                        ->orWhereHas('subjects', fn ($query) => $query
                            ->where('teacher_id', $teacher->id)
                            ->where('status', Subject::STATUS_ACTIVE));
                })
                ->firstOr(fn () => throw (new ModelNotFoundException)->setModel(SchoolClass::class, [$schoolClass->getKey()]));
        }

        $this->authorize($actor->can('view', $schoolClass));

        $relations = ['school'];

        if ($this->isTeacher($actor)) {
            $teacher = $this->activeTeacherProfile($actor);
            $relations['sections'] = fn ($query) => $query
                ->where('teacher_id', $teacher->id)
                ->where('status', Section::STATUS_ACTIVE)
                ->with('teacher.user')
                ->orderBy('name');
            $relations['subjects'] = fn ($query) => $query
                ->where('teacher_id', $teacher->id)
                ->where('status', Subject::STATUS_ACTIVE)
                ->with('teacher.user')
                ->orderBy('name');
        } else {
            $relations['sections'] = fn ($query) => $query->withTrashed()->with('teacher.user')->orderBy('name');
            $relations['subjects'] = fn ($query) => $query->withTrashed()->with('teacher.user')->orderBy('name');
        }

        return $schoolClass->load($relations);
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): SchoolClass
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('create', SchoolClass::class));

        return DB::transaction(function () use ($data, $actor): SchoolClass {
            $this->lockTenantSchool();
            $schoolClass = SchoolClass::create([
                ...$data,
                'status' => SchoolClass::STATUS_ACTIVE,
            ]);
            $this->logMutation($schoolClass, $actor, 'created', [], $this->auditValues($schoolClass));

            return $schoolClass;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(SchoolClass $schoolClass, array $data, User $actor): SchoolClass
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('update', $schoolClass));

        return DB::transaction(function () use ($schoolClass, $data, $actor): SchoolClass {
            $this->lockTenantSchool();
            $lockedClass = SchoolClass::query()->lockForUpdate()->findOrFail($schoolClass->getKey());
            $oldValues = $this->auditValues($lockedClass);
            $lockedClass->fill($data)->save();
            $this->logChangedMutation($lockedClass, $actor, 'updated', $oldValues);

            return $lockedClass;
        });
    }

    public function activate(SchoolClass $schoolClass, User $actor): SchoolClass
    {
        return $this->changeStatus($schoolClass, $actor, SchoolClass::STATUS_INACTIVE, SchoolClass::STATUS_ACTIVE, 'activate', 'activated');
    }

    public function deactivate(SchoolClass $schoolClass, User $actor): SchoolClass
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('deactivate', $schoolClass));

        return DB::transaction(function () use ($schoolClass, $actor): SchoolClass {
            $this->lockTenantSchool();
            $lockedClass = SchoolClass::query()->lockForUpdate()->findOrFail($schoolClass->getKey());

            if ($lockedClass->status !== SchoolClass::STATUS_ACTIVE) {
                throw new AuthorizationException;
            }

            $this->ensureNoActiveDependencies($lockedClass);
            $oldValues = $this->auditValues($lockedClass);
            $lockedClass->forceFill(['status' => SchoolClass::STATUS_INACTIVE])->save();
            $this->logChangedMutation($lockedClass, $actor, 'deactivated', $oldValues);

            return $lockedClass;
        });
    }

    public function archive(SchoolClass $schoolClass, User $actor): SchoolClass
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('archive', $schoolClass));

        return DB::transaction(function () use ($schoolClass, $actor): SchoolClass {
            $this->lockTenantSchool();
            $lockedClass = SchoolClass::query()->lockForUpdate()->findOrFail($schoolClass->getKey());

            if ($lockedClass->status !== SchoolClass::STATUS_INACTIVE) {
                throw new AuthorizationException;
            }

            $this->ensureNoActiveDependencies($lockedClass);
            $oldValues = $this->auditValues($lockedClass);
            $lockedClass->delete();
            $this->logChangedMutation($lockedClass, $actor, 'archived', $oldValues);

            return $lockedClass;
        });
    }

    public function restore(SchoolClass $schoolClass, User $actor): SchoolClass
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('restore', $schoolClass));

        return DB::transaction(function () use ($schoolClass, $actor): SchoolClass {
            $this->lockTenantSchool();
            $lockedClass = SchoolClass::query()->withTrashed()->lockForUpdate()->findOrFail($schoolClass->getKey());

            if (! $lockedClass->trashed()) {
                throw new AuthorizationException;
            }

            $oldValues = $this->auditValues($lockedClass);
            $lockedClass->restore();
            $lockedClass->forceFill(['status' => SchoolClass::STATUS_INACTIVE])->save();
            $this->logChangedMutation($lockedClass, $actor, 'restored', $oldValues);

            return $lockedClass;
        });
    }

    private function changeStatus(
        SchoolClass $schoolClass,
        User $actor,
        string $from,
        string $to,
        string $ability,
        string $action,
    ): SchoolClass {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can($ability, $schoolClass));

        return DB::transaction(function () use ($schoolClass, $actor, $from, $to, $action): SchoolClass {
            $this->lockTenantSchool();
            $lockedClass = SchoolClass::query()->lockForUpdate()->findOrFail($schoolClass->getKey());

            if ($lockedClass->status !== $from) {
                throw new AuthorizationException;
            }

            $oldValues = $this->auditValues($lockedClass);
            $lockedClass->forceFill(['status' => $to])->save();
            $this->logChangedMutation($lockedClass, $actor, $action, $oldValues);

            return $lockedClass;
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

    private function lockTenantSchool(): School
    {
        return School::query()
            ->whereKey($this->tenantContext->tenantId())
            ->where('status', School::STATUS_ACTIVE)
            ->lockForUpdate()
            ->firstOrFail();
    }

    private function ensureNoActiveDependencies(SchoolClass $schoolClass): void
    {
        $hasActiveSections = Section::query()
            ->where('class_id', $schoolClass->id)
            ->where('status', Section::STATUS_ACTIVE)
            ->exists();
        $hasActiveSubjects = Subject::query()
            ->where('class_id', $schoolClass->id)
            ->where('status', Subject::STATUS_ACTIVE)
            ->exists();

        if ($hasActiveSections || $hasActiveSubjects) {
            throw ValidationException::withMessages([
                'status' => 'Deactivate or archive every active Section and Subject first.',
            ]);
        }
    }

    /**
     * @param  array<string, mixed>  $oldValues
     */
    private function logChangedMutation(SchoolClass $schoolClass, User $actor, string $action, array $oldValues): void
    {
        $newValues = $this->auditValues($schoolClass);
        $changedKeys = array_keys(array_diff_assoc($newValues, $oldValues));

        $this->logMutation(
            $schoolClass,
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
        SchoolClass $schoolClass,
        User $actor,
        string $action,
        array $oldValues,
        array $newValues,
    ): void {
        $this->securityLogs->activity($actor, 'academic_structure', $action, $schoolClass, 'Class '.$action.'.');
        $this->securityLogs->audit($actor, $schoolClass, $action, $oldValues, $newValues);
    }

    /**
     * @return array<string, mixed>
     */
    private function auditValues(SchoolClass $schoolClass): array
    {
        return [
            'name' => $schoolClass->name,
            'code' => $schoolClass->code,
            'sort_order' => (int) $schoolClass->sort_order,
            'status' => $schoolClass->status,
            'archived' => $schoolClass->trashed(),
        ];
    }
}
