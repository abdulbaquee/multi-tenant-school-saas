<?php

namespace App\Providers;

use App\Models\AcademicTerm;
use App\Models\AcademicYear;
use App\Models\Role;
use App\Models\School;
use App\Models\SchoolSetting;
use App\Models\Teacher;
use App\Models\User;
use App\Policies\AcademicTermPolicy;
use App\Policies\AcademicYearPolicy;
use App\Policies\RolePolicy;
use App\Policies\SchoolPolicy;
use App\Policies\SchoolSettingPolicy;
use App\Policies\TeacherPolicy;
use App\Policies\UserPolicy;
use App\Tenancy\TenantContext;
use Illuminate\Pagination\Paginator;
use Illuminate\Support\Facades\Gate;
use Illuminate\Support\ServiceProvider;
use Illuminate\Validation\Rules\Password;

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
        Password::defaults(fn (): Password => Password::min(8)->mixedCase()->numbers());

        Gate::policy(AcademicYear::class, AcademicYearPolicy::class);
        Gate::policy(AcademicTerm::class, AcademicTermPolicy::class);
        Gate::policy(School::class, SchoolPolicy::class);
        Gate::policy(SchoolSetting::class, SchoolSettingPolicy::class);
        Gate::policy(Teacher::class, TeacherPolicy::class);
        Gate::policy(Role::class, RolePolicy::class);
        Gate::policy(User::class, UserPolicy::class);
        Gate::define('dashboard.view', fn (User $user): bool => $user->canViewDashboard());
        Paginator::useBootstrapFive();
    }
}
