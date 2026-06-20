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
    public function __construct(private readonly TenantContext $tenantContext) {}

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

        return DB::transaction(function () use ($data): School {
            $school = School::create([
                ...$data,
                'status' => School::STATUS_ACTIVE,
                'deactivated_at' => null,
                'deactivation_reason' => null,
            ]);

            $this->tenantContext->runAsTenant(
                $school->id,
                fn (): SchoolSetting => SchoolSetting::create(),
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

        $school->fill($data);
        $school->save();

        return $school;
    }

    public function deactivate(School $school, string $reason, User $actor): School
    {
        $this->authorizePlatform($actor->can('deactivate', $school));

        DB::transaction(function () use ($school, $reason): void {
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
        });

        return $school->refresh();
    }

    public function activate(School $school, User $actor): School
    {
        $this->authorizePlatform($actor->can('activate', $school));

        $school->forceFill([
            'status' => School::STATUS_ACTIVE,
            'deactivated_at' => null,
            'deactivation_reason' => null,
        ])->save();

        return $school;
    }

    private function authorizePlatform(bool $allowed): void
    {
        if (! $allowed || ! $this->tenantContext->isPlatform()) {
            throw new AuthorizationException;
        }
    }
}
