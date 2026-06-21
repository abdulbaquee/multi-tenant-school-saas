<?php

namespace App\Services;

use App\Models\Role;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class SubjectService
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly SecurityLogService $securityLogs,
    ) {}

    /**
     * @param  array{search?: string|null, state?: string|null, class_id?: int|string|null, subject_type?: string|null}  $filters
     * @return LengthAwarePaginator<int, Subject>
     */
    public function listFor(User $actor, array $filters): LengthAwarePaginator
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('viewAny', Subject::class));

        $query = Subject::query()->with(['school', 'schoolClass', 'teacher.user']);

        if ($this->isTeacher($actor)) {
            $teacher = $this->activeTeacherProfile($actor);
            $query->where('teacher_id', $teacher->id)
                ->where('status', Subject::STATUS_ACTIVE)
                ->whereHas('schoolClass', fn ($query) => $query->where('status', SchoolClass::STATUS_ACTIVE)->whereNull('deleted_at'));
        } else {
            $query->withTrashed()
                ->when(($filters['state'] ?? null) === 'active', fn ($query) => $query->whereNull('deleted_at')->where('status', Subject::STATUS_ACTIVE))
                ->when(($filters['state'] ?? null) === 'inactive', fn ($query) => $query->whereNull('deleted_at')->where('status', Subject::STATUS_INACTIVE))
                ->when(($filters['state'] ?? null) === 'archived', fn ($query) => $query->whereNotNull('deleted_at'));
        }

        return $query
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters): void {
                $search = '%'.trim((string) $filters['search']).'%';
                $query->where(fn ($query) => $query->where('name', 'like', $search)->orWhere('code', 'like', $search));
            })
            ->when(filled($filters['class_id'] ?? null), fn ($query) => $query->where('class_id', (int) $filters['class_id']))
            ->when(filled($filters['subject_type'] ?? null), fn ($query) => $query->where('subject_type', $filters['subject_type']))
            ->orderBy('class_id')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();
    }

    public function detailsFor(Subject $subject, User $actor): Subject
    {
        $this->authorizeActorContext($actor);

        if ($this->isTeacher($actor)) {
            $teacher = $this->activeTeacherProfile($actor);
            $subject = Subject::query()
                ->whereKey($subject->getKey())
                ->where('teacher_id', $teacher->id)
                ->where('status', Subject::STATUS_ACTIVE)
                ->whereHas('schoolClass', fn ($query) => $query->where('status', SchoolClass::STATUS_ACTIVE)->whereNull('deleted_at'))
                ->firstOr(fn () => throw (new ModelNotFoundException)->setModel(Subject::class, [$subject->getKey()]));
        }

        $this->authorize($actor->can('view', $subject));

        return $subject->load(['school', 'schoolClass', 'teacher.user']);
    }

    /**
     * @return array{classes: Collection<int, SchoolClass>, teachers: Collection<int, Teacher>}
     */
    public function formOptions(User $actor): array
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->hasRoleCode(Role::SCHOOL_ADMIN)
            && ($actor->hasPermission('academic.create') || $actor->hasPermission('academic.update')));

        return [
            'classes' => SchoolClass::query()
                ->where('status', SchoolClass::STATUS_ACTIVE)
                ->orderBy('sort_order')
                ->orderBy('name')
                ->get(),
            'teachers' => Teacher::query()
                ->where('status', Teacher::STATUS_ACTIVE)
                ->with('user')
                ->orderBy('employee_code')
                ->get(),
        ];
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): Subject
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('create', Subject::class));

        return DB::transaction(function () use ($data, $actor): Subject {
            $this->lockTenantSchool();
            $this->validateRelationships($data);
            $subject = Subject::create([
                'class_id' => $data['class_id'],
                'teacher_id' => $data['teacher_id'] ?? null,
                'name' => $data['name'],
                'code' => $data['code'],
                'subject_type' => $data['subject_type'],
                'status' => Subject::STATUS_ACTIVE,
            ]);
            $this->logMutation($subject, $actor, 'created', [], $this->auditValues($subject));

            return $subject;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Subject $subject, array $data, User $actor): Subject
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('update', $subject));

        return DB::transaction(function () use ($subject, $data, $actor): Subject {
            $this->lockTenantSchool();
            $lockedSubject = Subject::query()->lockForUpdate()->findOrFail($subject->getKey());
            $this->validateRelationships($data);
            $oldValues = $this->auditValues($lockedSubject);
            $lockedSubject->fill([
                'class_id' => $data['class_id'],
                'teacher_id' => $data['teacher_id'] ?? null,
                'name' => $data['name'],
                'code' => $data['code'],
                'subject_type' => $data['subject_type'],
            ])->save();
            $this->logChangedMutation($lockedSubject, $actor, 'updated', $oldValues);

            return $lockedSubject;
        });
    }

    public function activate(Subject $subject, User $actor): Subject
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('activate', $subject));

        return DB::transaction(function () use ($subject, $actor): Subject {
            $this->lockTenantSchool();
            $lockedSubject = Subject::query()->lockForUpdate()->findOrFail($subject->getKey());
            $this->authorize($lockedSubject->status === Subject::STATUS_INACTIVE);
            $this->validateRelationships($lockedSubject->only(['class_id', 'teacher_id']));
            $oldValues = $this->auditValues($lockedSubject);
            $lockedSubject->forceFill(['status' => Subject::STATUS_ACTIVE])->save();
            $this->logChangedMutation($lockedSubject, $actor, 'activated', $oldValues);

            return $lockedSubject;
        });
    }

    public function deactivate(Subject $subject, User $actor): Subject
    {
        return $this->changeStatus($subject, $actor, Subject::STATUS_ACTIVE, Subject::STATUS_INACTIVE, 'deactivate', 'deactivated');
    }

    public function archive(Subject $subject, User $actor): Subject
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('archive', $subject));

        return DB::transaction(function () use ($subject, $actor): Subject {
            $this->lockTenantSchool();
            $lockedSubject = Subject::query()->lockForUpdate()->findOrFail($subject->getKey());
            $this->authorize($lockedSubject->status === Subject::STATUS_INACTIVE);
            $oldValues = $this->auditValues($lockedSubject);
            $lockedSubject->delete();
            $this->logChangedMutation($lockedSubject, $actor, 'archived', $oldValues);

            return $lockedSubject;
        });
    }

    public function restore(Subject $subject, User $actor): Subject
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('restore', $subject));

        return DB::transaction(function () use ($subject, $actor): Subject {
            $this->lockTenantSchool();
            $lockedSubject = Subject::query()->withTrashed()->lockForUpdate()->findOrFail($subject->getKey());
            $this->authorize($lockedSubject->trashed());
            $this->validateRelationships($lockedSubject->only(['class_id', 'teacher_id']));
            $oldValues = $this->auditValues($lockedSubject);
            $lockedSubject->restore();
            $lockedSubject->forceFill(['status' => Subject::STATUS_INACTIVE])->save();
            $this->logChangedMutation($lockedSubject, $actor, 'restored', $oldValues);

            return $lockedSubject;
        });
    }

    private function changeStatus(
        Subject $subject,
        User $actor,
        string $from,
        string $to,
        string $ability,
        string $action,
    ): Subject {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can($ability, $subject));

        return DB::transaction(function () use ($subject, $actor, $from, $to, $action): Subject {
            $this->lockTenantSchool();
            $lockedSubject = Subject::query()->lockForUpdate()->findOrFail($subject->getKey());
            $this->authorize($lockedSubject->status === $from);
            $oldValues = $this->auditValues($lockedSubject);
            $lockedSubject->forceFill(['status' => $to])->save();
            $this->logChangedMutation($lockedSubject, $actor, $action, $oldValues);

            return $lockedSubject;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    private function validateRelationships(array $data): void
    {
        SchoolClass::query()
            ->where('status', SchoolClass::STATUS_ACTIVE)
            ->lockForUpdate()
            ->findOrFail((int) $data['class_id']);

        if (filled($data['teacher_id'] ?? null)) {
            Teacher::query()
                ->where('status', Teacher::STATUS_ACTIVE)
                ->lockForUpdate()
                ->findOrFail((int) $data['teacher_id']);
        }
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

    /**
     * @param  array<string, mixed>  $oldValues
     */
    private function logChangedMutation(Subject $subject, User $actor, string $action, array $oldValues): void
    {
        $newValues = $this->auditValues($subject);
        $changedKeys = array_keys(array_diff_assoc($newValues, $oldValues));

        $this->logMutation(
            $subject,
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
    private function logMutation(Subject $subject, User $actor, string $action, array $oldValues, array $newValues): void
    {
        $this->securityLogs->activity($actor, 'academic_structure', $action, $subject, 'Subject '.$action.'.');
        $this->securityLogs->audit($actor, $subject, $action, $oldValues, $newValues);
    }

    /**
     * @return array<string, mixed>
     */
    private function auditValues(Subject $subject): array
    {
        return [
            'class_id' => (int) $subject->class_id,
            'teacher_id' => $subject->teacher_id === null ? null : (int) $subject->teacher_id,
            'name' => $subject->name,
            'code' => $subject->code,
            'subject_type' => $subject->subject_type,
            'status' => $subject->status,
            'archived' => $subject->trashed(),
        ];
    }
}
