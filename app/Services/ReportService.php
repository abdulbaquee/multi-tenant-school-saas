<?php

namespace App\Services;

use App\Models\User;
use App\Policies\ReportPolicy;
use App\Reporting\ReportCategory;
use App\Tenancy\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;

class ReportService
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly ReportPolicy $reports,
    ) {}

    /**
     * @return array{
     *     title: string,
     *     description: string,
     *     categories: list<array{
     *         key: string,
     *         label: string,
     *         description: string,
     *         icon: string,
     *         route: string|null,
     *         can_export: bool
     *     }>
     * }
     */
    public function hubFor(User $actor): array
    {
        $this->authorizeActorContext($actor);

        if (! $this->reports->viewAny($actor)) {
            throw new AuthorizationException;
        }

        $actor->loadMissing(['role', 'school']);

        return [
            'title' => $actor->isSuperAdmin() ? 'Platform reports' : 'School reports',
            'description' => $actor->isSuperAdmin()
                ? 'Review authorized platform summary reports across schools.'
                : 'Review authorized operational reports for '.($actor->school?->name ?? 'your school').'.',
            'categories' => $this->catalogFor($actor),
        ];
    }

    /**
     * @param  array<string, mixed>  $filters
     * @return array{search: ?string, date_from: ?string, date_to: ?string}
     */
    public function normalizeFilters(array $filters): array
    {
        return [
            'search' => filled($filters['search'] ?? null) ? trim((string) $filters['search']) : null,
            'date_from' => filled($filters['date_from'] ?? null) ? (string) $filters['date_from'] : null,
            'date_to' => filled($filters['date_to'] ?? null) ? (string) $filters['date_to'] : null,
        ];
    }

    public function authorizeCategoryExport(User $actor, ReportCategory $category): void
    {
        $this->authorizeActorContext($actor);

        if (! $this->reports->exportCategory($actor, $category)) {
            throw new AuthorizationException;
        }
    }

    /**
     * @return list<array{
     *     key: string,
     *     label: string,
     *     description: string,
     *     icon: string,
     *     route: string|null,
     *     can_export: bool
     * }>
     */
    private function catalogFor(User $actor): array
    {
        $categories = [];

        foreach (ReportCategory::ordered() as $category) {
            if (! Gate::forUser($actor)->allows("reports.{$category->value}.view")) {
                continue;
            }

            $routeName = $category->routeName();

            $categories[] = [
                'key' => $category->value,
                'label' => $category->label(),
                'description' => $category->description(),
                'icon' => $category->icon(),
                'route' => app('router')->has($routeName) ? $routeName : null,
                'can_export' => $this->reports->exportCategory($actor, $category),
            ];
        }

        return $categories;
    }

    private function authorizeActorContext(User $actor): void
    {
        $matchesContext = $actor->isSuperAdmin()
            ? $this->tenantContext->isPlatform()
            : $this->tenantContext->isTenant()
                && filled($actor->school_id)
                && (int) $this->tenantContext->schoolId() === (int) $actor->school_id;

        if (! $matchesContext) {
            throw new AuthorizationException;
        }
    }
}
