<?php

namespace App\Services;

use App\Models\School;
use App\Models\SchoolSetting;
use App\Models\User;
use App\Tenancy\TenantContext;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Http\UploadedFile;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Storage;
use RuntimeException;
use Throwable;

class SchoolSettingService
{
    public function __construct(
        private readonly TenantContext $tenantContext,
        private readonly SecurityLogService $securityLogs,
    ) {}

    public function for(User $actor): SchoolSetting
    {
        $this->authorizeTenant($actor, $actor->can('viewAny', SchoolSetting::class));

        $settings = SchoolSetting::query()->first();

        if (! $settings) {
            $settings = DB::transaction(function () use ($actor): SchoolSetting {
                $settings = SchoolSetting::create();

                $this->securityLogs->activity(
                    $actor,
                    'school_settings',
                    'initialized',
                    $settings,
                    'Missing school settings initialized.',
                );
                $this->securityLogs->audit(
                    $actor,
                    $settings,
                    'created',
                    newValues: $this->settingsAuditValues($settings),
                );

                return $settings;
            });
        }

        $this->authorize($actor->can('view', $settings));

        return $settings;
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function update(SchoolSetting $settings, array $data, User $actor): SchoolSetting
    {
        $this->authorizeTenant($actor, $actor->can('update', $settings));

        $school = School::query()->findOrFail($actor->school_id);
        $logo = $data['logo'] ?? null;
        $removeLogo = (bool) ($data['remove_logo'] ?? false);
        unset($data['logo'], $data['remove_logo']);

        $newLogoPath = $logo instanceof UploadedFile
            ? $this->storeLogo($logo, $settings->school_id)
            : null;
        $oldLogoPath = $settings->logo_path;
        $oldSchoolValues = $this->schoolAuditValues($school);
        $oldSettingsValues = $this->settingsAuditValues($settings);

        try {
            DB::transaction(function () use (
                $school,
                $settings,
                $data,
                $newLogoPath,
                $removeLogo,
                $oldSchoolValues,
                $oldSettingsValues,
                $actor,
            ): void {
                $school->fill([
                    'name' => $data['school_name'],
                    'email' => $data['school_email'],
                    'phone' => $data['phone'] ?? null,
                    'address' => $data['address'] ?? null,
                    'city' => $data['city'] ?? null,
                    'state' => $data['state'] ?? null,
                    'country' => $data['country'],
                    'postal_code' => $data['postal_code'] ?? null,
                    'principal_name' => $data['principal_name'] ?? null,
                    'website' => $data['website'] ?? null,
                ])->save();

                $settings->fill([
                    'timezone' => $data['timezone'],
                    'currency' => $data['currency'],
                    'academic_year_start_month' => $data['academic_year_start_month'],
                    'attendance_start_time' => $data['attendance_start_time'] ?? null,
                    'grading_system' => $data['grading_system'],
                ]);

                if ($newLogoPath) {
                    $settings->logo_path = $newLogoPath;
                } elseif ($removeLogo) {
                    $settings->logo_path = null;
                }

                $settings->save();

                $this->securityLogs->activity(
                    $actor,
                    'school_settings',
                    'updated',
                    $settings,
                    'School settings updated.',
                );
                $this->securityLogs->audit(
                    $actor,
                    $school,
                    'updated',
                    $oldSchoolValues,
                    $this->schoolAuditValues($school),
                );
                $this->securityLogs->audit(
                    $actor,
                    $settings,
                    'updated',
                    $oldSettingsValues,
                    $this->settingsAuditValues($settings),
                );
            });
        } catch (Throwable $exception) {
            if ($newLogoPath) {
                Storage::disk('public')->delete($newLogoPath);
            }

            throw $exception;
        }

        if (($newLogoPath || $removeLogo) && $this->isManagedLogo($oldLogoPath, $settings->school_id)) {
            Storage::disk('public')->delete($oldLogoPath);
        }

        return $settings->refresh();
    }

    private function storeLogo(UploadedFile $logo, int $schoolId): string
    {
        $path = $logo->storePublicly('school-logos/'.$schoolId, 'public');

        if (! is_string($path) || $path === '') {
            throw new RuntimeException('The school logo could not be stored.');
        }

        return $path;
    }

    private function isManagedLogo(?string $path, int $schoolId): bool
    {
        return is_string($path)
            && str_starts_with($path, 'school-logos/'.$schoolId.'/');
    }

    private function authorizeTenant(User $actor, bool $allowed): void
    {
        $matchesContext = $this->tenantContext->isTenant()
            && (int) $this->tenantContext->schoolId() === (int) $actor->school_id;

        $this->authorize($allowed && $matchesContext);
    }

    private function authorize(bool $allowed): void
    {
        if (! $allowed) {
            throw new AuthorizationException;
        }
    }

    /**
     * @return array<string, mixed>
     */
    private function schoolAuditValues(School $school): array
    {
        return $school->only([
            'name',
            'email',
            'phone',
            'address',
            'city',
            'state',
            'country',
            'postal_code',
            'principal_name',
            'website',
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function settingsAuditValues(SchoolSetting $settings): array
    {
        return $settings->only([
            'school_id',
            'logo_path',
            'timezone',
            'currency',
            'academic_year_start_month',
            'attendance_start_time',
            'grading_system',
        ]);
    }
}
