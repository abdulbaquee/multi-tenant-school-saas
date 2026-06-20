<?php

namespace App\Providers;

use App\Models\School;
use App\Models\User;
use App\Policies\SchoolPolicy;
use App\Policies\UserPolicy;
use App\Tenancy\TenantContext;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;

class AppServiceProvider extends ServiceProvider
{
    /**
     * Register any application services.
     */
    public function register(): void
    {
        $this->app->scoped(TenantContext::class, fn (): TenantContext => new TenantContext);
    }

    /**
     * Bootstrap any application services.
     */
    public function boot(): void
    {
        Gate::policy(School::class, SchoolPolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::define('dashboard.view', fn (User $user): bool => $user->canViewDashboard());
        Paginator::useBootstrapFive();
    }
}
