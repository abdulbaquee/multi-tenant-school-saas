<?php

namespace App\Services\Concerns;

use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;

trait AuthorizesLogReview
{
    private function authorizeLogReviewActor(User $actor): void
    {
        $matchesContext = $actor->isSuperAdmin()
            ? app(TenantContext::class)->isPlatform()
            : app(TenantContext::class)->isTenant()
                && filled($actor->school_id)
                && (int) app(TenantContext::class)->schoolId() === (int) $actor->school_id;

        if (! $matchesContext) {
            throw new AuthorizationException;
        }
    }

    private function authorizeLogReview(bool $allowed): void
    {
        if (! $allowed) {
            throw new AuthorizationException;
        }
    }
}
