<?php

namespace App\Services;

use App\Models\School;
use App\Models\SchoolSetting;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Support\Facades\DB;

class SchoolService
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly SecurityLogService $securityLogs,
    ) {}

    /**
     * @param  array{search?: string|null, status?: string|null}  $filters
     * @return LengthAwarePaginator<int, School>
     */
    public function listFor(User $actor, array $filters): LengthAwarePaginator
    {
        $this->authorizePlatform($actor->can('viewAny', School::class));

        return School::query()
            ->withCount('users')
            ->when(filled($filters['search'] ?? null), function ($query) use ($filters): void {
                $search = '%'.trim((string) $filters['search']).'%';

                $query->where(function ($query) use ($search): void {
                    $query->where('name', 'like', $search)
                        ->orWhere('code', 'like', $search)
                        ->orWhere('email', 'like', $search)
                        ->orWhere('city', 'like', $search);
                });
            })
            ->when(filled($filters['status'] ?? null), fn ($query) => $query->where('status', $filters['status']))
            ->orderBy('name')
            ->paginate(15)
            ->withQueryString();
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function create(array $data, User $actor): School
    {
        $this->authorizePlatform($actor->can('create', School::class));

        return DB::transaction(function () use ($data, $actor): School {
            $school = School::create([
                ...$data,
                'status' => School::STATUS_ACTIVE,
                'deactivated_at' => null,
                'deactivation_reason' => null,
            ]);

            $settings = $this->tenantContext->runAsTenant(
                $school->id,
                fn (): SchoolSetting => SchoolSetting::create(),
            );

            $this->securityLogs->activity(
                $actor,
                'school_management',
                'created',
                $school,
                'School created.',
            );
            $this->securityLogs->audit(
                $actor,
                $school,
                'created',
                newValues: $this->auditValues($school),
            );
            $this->securityLogs->audit(
                $actor,
                $settings,
                'created',
                newValues: $settings->only([
                    'school_id',
                    'timezone',
                    'currency',
                    'academic_year_start_month',
                    'attendance_start_time',
                    'grading_system',
                ]),
            );

            return $school;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(School $school, array $data, User $actor): School
    {
        $this->authorizePlatform($actor->can('update', $school));

        return DB::transaction(function () use ($school, $data, $actor): School {
            $oldValues = $this->auditValues($school);
            $school->fill($data);
            $school->save();

            $this->securityLogs->activity(
                $actor,
                'school_management',
                'updated',
                $school,
                'School profile updated.',
            );
            $this->securityLogs->audit(
                $actor,
                $school,
                'updated',
                $oldValues,
                $this->auditValues($school),
            );

            return $school;
        });
    }

    public function deactivate(School $school, string $reason, User $actor): School
    {
        $this->authorizePlatform($actor->can('deactivate', $school));

        DB::transaction(function () use ($school, $reason, $actor): void {
            $oldValues = $this->auditValues($school);
            $school->forceFill([
                'status' => School::STATUS_INACTIVE,
                'deactivated_at' => now(),
                'deactivation_reason' => trim($reason),
            ])->save();

            $userIds = User::withTrashed()
                ->where('school_id', $school->id)
                ->pluck('id');

            User::withTrashed()
                ->whereIn('id', $userIds)
                ->update(['remember_token' => null]);

            DB::table('sessions')->whereIn('user_id', $userIds)->delete();

            $this->securityLogs->activity(
                $actor,
                'school_management',
                'deactivated',
                $school,
                'School deactivated.',
            );
            $this->securityLogs->audit(
                $actor,
                $school,
                'status_changed',
                $oldValues,
                $this->auditValues($school),
            );
        });

        return $school->refresh();
    }

    public function activate(School $school, User $actor): School
    {
        $this->authorizePlatform($actor->can('activate', $school));

        return DB::transaction(function () use ($school, $actor): School {
            $oldValues = $this->auditValues($school);
            $school->forceFill([
                'status' => School::STATUS_ACTIVE,
                'deactivated_at' => null,
                'deactivation_reason' => null,
            ])->save();

            $this->securityLogs->activity(
                $actor,
                'school_management',
                'activated',
                $school,
                'School activated.',
            );
            $this->securityLogs->audit(
                $actor,
                $school,
                'status_changed',
                $oldValues,
                $this->auditValues($school),
            );

            return $school;
        });
    }

    private function authorizePlatform(bool $allowed): void
    {
        if (! $allowed || ! $this->tenantContext->isPlatform()) {
            throw new AuthorizationException;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function auditValues(School $school): array
    {
        return $school->only([
            'name',
            'code',
            'email',
            'phone',
            'address',
            'city',
            'state',
            'country',
            'postal_code',
            'principal_name',
            'website',
            'status',
            'deactivated_at',
            'deactivation_reason',
        ]);
    }
}
