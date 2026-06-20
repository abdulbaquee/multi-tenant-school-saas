<?php

namespace App\Services;

use App\Models\ActivityLog;
use App\Models\AuditLog;
use App\Models\School;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Http\Request;

class SecurityLogService
{
    private const SENSITIVE_KEY_PARTS = [
        'password',
        'token',
        'secret',
        'credential',
        'session',
        'payload',
    ];

    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly Request $request,
    ) {}

    public function activity(
        User $actor,
        string $module,
        string $action,
        ?Model $subject = null,
        ?string $description = null,
    ): ActivityLog {
        $this->authorizeActorContext($actor);

        return $this->createActivity(
            $actor,
            $module,
            $action,
            $subject,
            $description,
            $this->resolveSchoolId($subject),
        );
    }

    public function platformActivity(
        User $actor,
        string $module,
        string $action,
        ?Model $subject = null,
        ?string $description = null,
    ): ActivityLog {
        $this->authorizePlatformActor($actor);

        return $this->createActivity($actor, $module, $action, $subject, $description, null);
    }

    private function createActivity(
        User $actor,
        string $module,
        string $action,
        ?Model $subject,
        ?string $description,
        ?int $schoolId,
    ): ActivityLog {
        return ActivityLog::create([
            'school_id' => $schoolId,
            'user_id' => $actor->id,
            'module' => $module,
            'action' => $action,
            'description' => $description,
            'subject_type' => $subject?->getMorphClass(),
            'subject_id' => $subject?->getKey(),
            'ip_address' => $this->request->ip(),
            'user_agent' => $this->request->userAgent(),
            'created_at' => now(),
        ]);
    }

    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     */
    public function audit(
        User $actor,
        Model $auditable,
        string $event,
        array $oldValues = [],
        array $newValues = [],
    ): AuditLog {
        $this->authorizeActorContext($actor);

        return $this->createAudit(
            $actor,
            $auditable,
            $event,
            $oldValues,
            $newValues,
            $this->resolveSchoolId($auditable),
        );
    }

    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     */
    public function platformAudit(
        User $actor,
        Model $auditable,
        string $event,
        array $oldValues = [],
        array $newValues = [],
    ): AuditLog {
        $this->authorizePlatformActor($actor);

        return $this->createAudit($actor, $auditable, $event, $oldValues, $newValues, null);
    }

    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     */
    private function createAudit(
        User $actor,
        Model $auditable,
        string $event,
        array $oldValues,
        array $newValues,
        ?int $schoolId,
    ): AuditLog {
        return AuditLog::create([
            'school_id' => $schoolId,
            'user_id' => $actor->id,
            'auditable_type' => $auditable->getMorphClass(),
            'auditable_id' => $auditable->getKey(),
            'event' => $event,
            'old_values' => $this->sanitizeValues($oldValues),
            'new_values' => $this->sanitizeValues($newValues),
            'ip_address' => $this->request->ip(),
            'user_agent' => $this->request->userAgent(),
            'created_at' => now(),
        ]);
    }

    public function authenticationActivity(
        User $actor,
        string $action,
        ?Model $subject = null,
        ?string $description = null,
    ): ActivityLog {
        return $this->withinActorContext(
            $actor,
            fn (): ActivityLog => $this->activity(
                $actor,
                'authentication',
                $action,
                $subject ?? $actor,
                $description,
            ),
        );
    }

    public function systemActivity(
        string $module,
        string $action,
        ?string $description = null,
    ): ActivityLog {
        return $this->tenantContext->runAsPlatform(fn (): ActivityLog => ActivityLog::create([
            'school_id' => null,
            'user_id' => null,
            'module' => $module,
            'action' => $action,
            'description' => $description,
            'subject_type' => null,
            'subject_id' => null,
            'ip_address' => $this->request->ip(),
            'user_agent' => $this->request->userAgent(),
            'created_at' => now(),
        ]));
    }

    /**
     * @param  array<string, mixed>  $oldValues
     * @param  array<string, mixed>  $newValues
     */
    public function authenticationAudit(
        User $actor,
        Model $auditable,
        string $event,
        array $oldValues = [],
        array $newValues = [],
    ): AuditLog {
        return $this->withinActorContext(
            $actor,
            fn (): AuditLog => $this->audit($actor, $auditable, $event, $oldValues, $newValues),
        );
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

    private function authorizePlatformActor(User $actor): void
    {
        if (! $actor->isSuperAdmin() || ! $this->tenantContext->isPlatform()) {
            throw new AuthorizationException;
        }
    }

    private function resolveSchoolId(?Model $subject): ?int
    {
        if ($this->tenantContext->isTenant()) {
            return $this->tenantContext->tenantId();
        }

        if ($subject instanceof School) {
            return (int) $subject->getKey();
        }

        $schoolId = $subject?->getAttribute('school_id');

        return filled($schoolId) ? (int) $schoolId : null;
    }

    /**
     * @param  array<string, mixed>  $values
     * @return array<string, mixed>
     */
    private function sanitizeValues(array $values): array
    {
        return collect($values)
            ->reject(function (mixed $value, string|int $key): bool {
                $normalizedKey = strtolower((string) $key);

                return collect(self::SENSITIVE_KEY_PARTS)
                    ->contains(fn (string $part): bool => str_contains($normalizedKey, $part));
            })
            ->map(fn (mixed $value): mixed => is_array($value) ? $this->sanitizeValues($value) : $value)
            ->all();
    }

    /**
     * @template TResult
     *
     * @param  callable(): TResult  $callback
     * @return TResult
     */
    private function withinActorContext(User $actor, callable $callback): mixed
    {
        if ($actor->isSuperAdmin()) {
            return $this->tenantContext->runAsPlatform($callback);
        }

        if (! filled($actor->school_id)) {
            throw new AuthorizationException;
        }

        return $this->tenantContext->runAsTenant((int) $actor->school_id, $callback);
    }
}
