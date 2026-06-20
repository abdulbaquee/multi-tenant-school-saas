<?php

namespace App\Services;

use App\Models\Role;
use App\Models\School;
use App\Models\User;
use Illuminate\Auth\Access\AuthorizationException;

class DashboardService
{
    /**
     * @return array{
     *     title: string,
     *     description: string,
     *     metrics: list<array{label: string, value: int|string, icon: string, tone: string}>
     * }
     */
    public function summaryFor(User $user): array
    {
        if (! $user->can('dashboard.view')) {
            throw new AuthorizationException;
        }

        $user->loadMissing(['role', 'school']);

        return match ($user->role?->code) {
            Role::SUPER_ADMIN => $this->superAdminSummary(),
            Role::SCHOOL_ADMIN => $this->schoolAdminSummary($user),
            Role::TEACHER => $this->accountSummary(
                'Teaching workspace',
                'Your school and account context for teaching workflows.',
                $user,
            ),
            Role::ACCOUNTANT => $this->accountSummary(
                'Accounts workspace',
                'Your school and account context for financial workflows.',
                $user,
            ),
            default => throw new AuthorizationException,
        };
    }

    /**
     * @return array{title: string, description: string, metrics: list<array{label: string, value: int|string, icon: string, tone: string}>}
     */
    private function superAdminSummary(): array
    {
        return [
            'title' => 'Platform overview',
            'description' => 'Current school and user activity across the platform.',
            'metrics' => [
                [
                    'label' => 'Total schools',
                    'value' => School::query()->count(),
                    'icon' => 'bi-buildings',
                    'tone' => 'primary',
                ],
                [
                    'label' => 'Active schools',
                    'value' => School::query()->where('status', 'active')->count(),
                    'icon' => 'bi-building-check',
                    'tone' => 'success',
                ],
                [
                    'label' => 'Total users',
                    'value' => User::query()->count(),
                    'icon' => 'bi-people',
                    'tone' => 'secondary',
                ],
            ],
        ];
    }

    /**
     * @return array{title: string, description: string, metrics: list<array{label: string, value: int|string, icon: string, tone: string}>}
     */
    private function schoolAdminSummary(User $user): array
    {
        $users = User::query()->where('school_id', $user->school_id);

        return [
            'title' => 'School administration',
            'description' => 'Current user access for '.($user->school?->name ?? 'your school').'.',
            'metrics' => [
                [
                    'label' => 'School users',
                    'value' => (clone $users)->count(),
                    'icon' => 'bi-people',
                    'tone' => 'primary',
                ],
                [
                    'label' => 'Active users',
                    'value' => (clone $users)->where('status', User::STATUS_ACTIVE)->count(),
                    'icon' => 'bi-person-check',
                    'tone' => 'success',
                ],
                [
                    'label' => 'Inactive users',
                    'value' => (clone $users)->where('status', User::STATUS_INACTIVE)->count(),
                    'icon' => 'bi-person-dash',
                    'tone' => 'secondary',
                ],
            ],
        ];
    }

    /**
     * @return array{title: string, description: string, metrics: list<array{label: string, value: int|string, icon: string, tone: string}>}
     */
    private function accountSummary(string $title, string $description, User $user): array
    {
        return [
            'title' => $title,
            'description' => $description,
            'metrics' => [
                [
                    'label' => 'Role',
                    'value' => $user->role->name,
                    'icon' => 'bi-person-badge',
                    'tone' => 'primary',
                ],
                [
                    'label' => 'School',
                    'value' => $user->school?->name ?? 'School unavailable',
                    'icon' => 'bi-building',
                    'tone' => 'secondary',
                ],
                [
                    'label' => 'Account status',
                    'value' => ucfirst($user->status),
                    'icon' => 'bi-shield-check',
                    'tone' => 'success',
                ],
            ],
        ];
    }
}
