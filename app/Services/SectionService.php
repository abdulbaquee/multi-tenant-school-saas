<?php

namespace App\Services;

use App\Models\Role;
use App\Models\School;
use App\Models\SchoolClass;
use App\Models\Section;
use App\Models\Teacher;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Support\Facades\DB;

class SectionService
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly SecurityLogService $securityLogs,
    ) {}

    /**
     * @param  array{search?: string|null, state?: string|null, class_id?: int|string|null}  $filters
     * @return LengthAwarePaginator<int, Section>
     */
    public function listFor(User $actor, array $filters): LengthAwarePaginator
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('viewAny', Section::class));

        $query = Section::query()->with(['school', 'schoolClass', 'teacher.user']);

        if ($this->isTeacher($actor)) {
            $teacher = $this->activeTeacherProfile($actor);
            $query->where('teacher_id', $teacher->id)
                ->where('status', Section::STATUS_ACTIVE)
                ->whereHas('schoolClass', fn ($query) => $query->where('status', SchoolClass::STATUS_ACTIVE)->whereNull('deleted_at'));
        } else {
            $query->withTrashed()
                ->when(($filters['state'] ?? null) === 'active', fn ($query) => $query->whereNull('deleted_at')->where('status', Section::STATUS_ACTIVE))
                ->when(($filters['state'] ?? null) === 'inactive', fn ($query) => $query->whereNull('deleted_at')->where('status', Section::STATUS_INACTIVE))
                ->when(($filters['state'] ?? null) === 'archived', fn ($query) => $query->whereNotNull('deleted_at'));
        }

        return $query
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters): void {
                $search = '%'.trim((string) $filters['search']).'%';
                $query->where('name', 'like', $search);
            })
            ->when(filled($filters['class_id'] ?? null), fn ($query) => $query->where('class_id', (int) $filters['class_id']))
            ->orderBy('class_id')
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();
    }

    public function detailsFor(Section $section, User $actor): Section
    {
        $this->authorizeActorContext($actor);

        if ($this->isTeacher($actor)) {
            $teacher = $this->activeTeacherProfile($actor);
            $section = Section::query()
                ->whereKey($section->getKey())
                ->where('teacher_id', $teacher->id)
                ->where('status', Section::STATUS_ACTIVE)
                ->whereHas('schoolClass', fn ($query) => $query->where('status', SchoolClass::STATUS_ACTIVE)->whereNull('deleted_at'))
                ->firstOr(fn () => throw (new ModelNotFoundException)->setModel(Section::class, [$section->getKey()]));
        }

        $this->authorize($actor->can('view', $section));

        return $section->load(['school', 'schoolClass', 'teacher.user']);
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
    public function create(array $data, User $actor): Section
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('create', Section::class));

        return DB::transaction(function () use ($data, $actor): Section {
            $this->lockTenantSchool();
            $this->validateRelationships($data);
            $section = Section::create([
                'class_id' => $data['class_id'],
                'teacher_id' => $data['teacher_id'] ?? null,
                'name' => $data['name'],
                'capacity' => $data['capacity'] ?? null,
                'status' => Section::STATUS_ACTIVE,
            ]);
            $this->logMutation($section, $actor, 'created', [], $this->auditValues($section));

            return $section;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(Section $section, array $data, User $actor): Section
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('update', $section));

        return DB::transaction(function () use ($section, $data, $actor): Section {
            $this->lockTenantSchool();
            $lockedSection = Section::query()->lockForUpdate()->findOrFail($section->getKey());
            $this->validateRelationships($data);
            $oldValues = $this->auditValues($lockedSection);
            $lockedSection->fill([
                'class_id' => $data['class_id'],
                'teacher_id' => $data['teacher_id'] ?? null,
                'name' => $data['name'],
                'capacity' => $data['capacity'] ?? null,
            ])->save();
            $this->logChangedMutation($lockedSection, $actor, 'updated', $oldValues);

            return $lockedSection;
        });
    }

    public function activate(Section $section, User $actor): Section
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('activate', $section));

        return DB::transaction(function () use ($section, $actor): Section {
            $this->lockTenantSchool();
            $lockedSection = Section::query()->lockForUpdate()->findOrFail($section->getKey());
            $this->authorize($lockedSection->status === Section::STATUS_INACTIVE);
            $this->validateRelationships($lockedSection->only(['class_id', 'teacher_id']));
            $oldValues = $this->auditValues($lockedSection);
            $lockedSection->forceFill(['status' => Section::STATUS_ACTIVE])->save();
            $this->logChangedMutation($lockedSection, $actor, 'activated', $oldValues);

            return $lockedSection;
        });
    }

    public function deactivate(Section $section, User $actor): Section
    {
        return $this->changeStatus($section, $actor, Section::STATUS_ACTIVE, Section::STATUS_INACTIVE, 'deactivate', 'deactivated');
    }

    public function archive(Section $section, User $actor): Section
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('archive', $section));

        return DB::transaction(function () use ($section, $actor): Section {
            $this->lockTenantSchool();
            $lockedSection = Section::query()->lockForUpdate()->findOrFail($section->getKey());
            $this->authorize($lockedSection->status === Section::STATUS_INACTIVE);
            $oldValues = $this->auditValues($lockedSection);
            $lockedSection->delete();
            $this->logChangedMutation($lockedSection, $actor, 'archived', $oldValues);

            return $lockedSection;
        });
    }

    public function restore(Section $section, User $actor): Section
    {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can('restore', $section));

        return DB::transaction(function () use ($section, $actor): Section {
            $this->lockTenantSchool();
            $lockedSection = Section::query()->withTrashed()->lockForUpdate()->findOrFail($section->getKey());
            $this->authorize($lockedSection->trashed());
            $this->validateRelationships($lockedSection->only(['class_id', 'teacher_id']));
            $oldValues = $this->auditValues($lockedSection);
            $lockedSection->restore();
            $lockedSection->forceFill(['status' => Section::STATUS_INACTIVE])->save();
            $this->logChangedMutation($lockedSection, $actor, 'restored', $oldValues);

            return $lockedSection;
        });
    }

    private function changeStatus(
        Section $section,
        User $actor,
        string $from,
        string $to,
        string $ability,
        string $action,
    ): Section {
        $this->authorizeActorContext($actor);
        $this->authorize($actor->can($ability, $section));

        return DB::transaction(function () use ($section, $actor, $from, $to, $action): Section {
            $this->lockTenantSchool();
            $lockedSection = Section::query()->lockForUpdate()->findOrFail($section->getKey());
            $this->authorize($lockedSection->status === $from);
            $oldValues = $this->auditValues($lockedSection);
            $lockedSection->forceFill(['status' => $to])->save();
            $this->logChangedMutation($lockedSection, $actor, $action, $oldValues);

            return $lockedSection;
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
    private function logChangedMutation(Section $section, User $actor, string $action, array $oldValues): void
    {
        $newValues = $this->auditValues($section);
        $changedKeys = array_keys(array_diff_assoc($newValues, $oldValues));

        $this->logMutation(
            $section,
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
    private function logMutation(Section $section, User $actor, string $action, array $oldValues, array $newValues): void
    {
        $this->securityLogs->activity($actor, 'academic_structure', $action, $section, 'Section '.$action.'.');
        $this->securityLogs->audit($actor, $section, $action, $oldValues, $newValues);
    }

    /**
     * @return array<string, mixed>
     */
    private function auditValues(Section $section): array
    {
        return [
            'class_id' => (int) $section->class_id,
            'teacher_id' => $section->teacher_id === null ? null : (int) $section->teacher_id,
            'name' => $section->name,
            'capacity' => $section->capacity === null ? null : (int) $section->capacity,
            'status' => $section->status,
            'archived' => $section->trashed(),
        ];
    }
}
