<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\ExamResult;
use App\Models\Role;
use App\Models\School;
use App\Models\Section;
use App\Models\Student;
use App\Models\StudentEnrollment;
use App\Models\StudentFee;
use App\Models\Subject;
use App\Models\Teacher;
use App\Models\User;
use App\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Support\Facades\DB;

class DashboardService
{
    public function __construct(private readonly TenantContext $tenantContext) {}

    /**
     * @return array{
     *     title: string,
     *     description: string,
     *     metrics: list<array{label: string, value: int|string, icon: string, tone: string}>
     * }
     */
    public function summaryFor(User $user): array
    {
        $this->authorizeActorContext($user);

        if (! $user->can('dashboard.view')) {
            throw new AuthorizationException;
        }

        $user->loadMissing(['role', 'school']);

        return match ($user->role?->code) {
            Role::SUPER_ADMIN => $this->superAdminSummary(),
            Role::SCHOOL_ADMIN => $this->schoolAdminSummary($user),
            Role::TEACHER => $this->teacherSummary($user),
            Role::ACCOUNTANT => $this->accountantSummary($user),
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
                    'value' => School::query()->where('status', School::STATUS_ACTIVE)->count(),
                    'icon' => 'bi-building-check',
                    'tone' => 'success',
                ],
                [
                    'label' => 'Active students',
                    'value' => Student::query()->whereNull('deleted_at')->where('status', Student::STATUS_ACTIVE)->count(),
                    'icon' => 'bi-people',
                    'tone' => 'secondary',
                ],
                [
                    'label' => 'Total users',
                    'value' => User::query()->count(),
                    'icon' => 'bi-person-badge',
                    'tone' => 'info',
                ],
            ],
        ];
    }

    /**
     * @return array{title: string, description: string, metrics: list<array{label: string, value: int|string, icon: string, tone: string}>}
     */
    private function schoolAdminSummary(User $user): array
    {
        $now = CarbonImmutable::now();
        $outstanding = StudentFee::query()
            ->whereIn('status', [StudentFee::STATUS_PENDING, StudentFee::STATUS_PARTIAL])
            ->where('balance_amount', '>', 0);

        return [
            'title' => 'School administration',
            'description' => 'Operational summary for '.($user->school?->name ?? 'your school').'.',
            'metrics' => [
                [
                    'label' => 'Active students',
                    'value' => Student::query()->whereNull('deleted_at')->where('status', Student::STATUS_ACTIVE)->count(),
                    'icon' => 'bi-people',
                    'tone' => 'primary',
                ],
                [
                    'label' => 'Attendance this month',
                    'value' => Attendance::query()
                        ->whereYear('attendance_date', $now->year)
                        ->whereMonth('attendance_date', $now->month)
                        ->count(),
                    'icon' => 'bi-calendar2-check',
                    'tone' => 'success',
                ],
                [
                    'label' => 'Outstanding balance',
                    'value' => number_format((float) ((clone $outstanding)->sum(DB::raw('balance_amount')) ?: 0), 2, '.', ''),
                    'icon' => 'bi-cash-coin',
                    'tone' => 'warning',
                ],
                [
                    'label' => 'Examination results',
                    'value' => ExamResult::query()->count(),
                    'icon' => 'bi-journal-check',
                    'tone' => 'secondary',
                ],
            ],
        ];
    }

    /**
     * @return array{title: string, description: string, metrics: list<array{label: string, value: int|string, icon: string, tone: string}>}
     */
    private function teacherSummary(User $user): array
    {
        $teacher = $this->activeTeacherProfile($user);
        $now = CarbonImmutable::now();

        if (! $teacher instanceof Teacher) {
            return [
                'title' => 'Teaching workspace',
                'description' => 'Assigned-class and subject summary for '.($user->school?->name ?? 'your school').'.',
                'metrics' => $this->teacherMetrics($user, $teacher, $now),
            ];
        }

        return [
            'title' => 'Teaching workspace',
            'description' => 'Assigned-class and subject summary for '.($user->school?->name ?? 'your school').'.',
            'metrics' => $this->teacherMetrics($user, $teacher, $now),
        ];
    }

    /**
     * @return list<array{label: string, value: int|string, icon: string, tone: string}>
     */
    private function teacherMetrics(User $user, ?Teacher $teacher, CarbonImmutable $now): array
    {
        $assignedSections = $teacher instanceof Teacher
            ? Section::query()
                ->whereNull('deleted_at')
                ->where('status', Section::STATUS_ACTIVE)
                ->where('teacher_id', $teacher->id)
                ->count()
            : 0;

        $studentsInScope = $teacher instanceof Teacher
            ? Student::query()
                ->where('status', Student::STATUS_ACTIVE)
                ->whereHas('enrollments', function (Builder $enrollmentQuery) use ($teacher): void {
                    $enrollmentQuery
                        ->where('status', StudentEnrollment::STATUS_ACTIVE)
                        ->where(function (Builder $nested) use ($teacher): void {
                            $nested
                                ->whereHas('section', fn (Builder $sectionQuery) => $sectionQuery
                                    ->whereNull('deleted_at')
                                    ->where('status', Section::STATUS_ACTIVE)
                                    ->where('teacher_id', $teacher->id))
                                ->orWhereHas('schoolClass.subjects', fn (Builder $subjectQuery) => $subjectQuery
                                    ->whereNull('deleted_at')
                                    ->where('status', Subject::STATUS_ACTIVE)
                                    ->where('teacher_id', $teacher->id));
                        });
                })
                ->count()
            : 0;

        $metrics = [
            [
                'label' => 'Assigned sections',
                'value' => $assignedSections,
                'icon' => 'bi-diagram-3',
                'tone' => 'primary',
            ],
            [
                'label' => 'Students in scope',
                'value' => $studentsInScope,
                'icon' => 'bi-people',
                'tone' => 'success',
            ],
        ];

        if ($user->hasPermission('attendance.view')) {
            $metrics[] = [
                'label' => 'Attendance this month',
                'value' => $teacher instanceof Teacher
                    ? Attendance::query()
                        ->whereYear('attendance_date', $now->year)
                        ->whereMonth('attendance_date', $now->month)
                        ->whereHas('section', fn (Builder $sectionQuery) => $sectionQuery->where('teacher_id', $teacher->id))
                        ->count()
                    : 0,
                'icon' => 'bi-calendar2-check',
                'tone' => 'secondary',
            ];
        }

        if ($user->hasPermission('reports.view')) {
            $metrics[] = [
                'label' => 'Examination results',
                'value' => $teacher instanceof Teacher
                    ? ExamResult::query()
                        ->whereHas('examSubject.subject', fn (Builder $subjectQuery) => $subjectQuery->where('teacher_id', $teacher->id))
                        ->count()
                    : 0,
                'icon' => 'bi-journal-check',
                'tone' => 'info',
            ];
        }

        return $metrics;
    }

    /**
     * @return array{title: string, description: string, metrics: list<array{label: string, value: int|string, icon: string, tone: string}>}
     */
    private function accountantSummary(User $user): array
    {
        $outstanding = StudentFee::query()
            ->whereIn('status', [StudentFee::STATUS_PENDING, StudentFee::STATUS_PARTIAL])
            ->where('balance_amount', '>', 0);

        return [
            'title' => 'Accounts workspace',
            'description' => 'Financial summary for '.($user->school?->name ?? 'your school').'.',
            'metrics' => [
                [
                    'label' => 'Outstanding balance',
                    'value' => number_format((float) ((clone $outstanding)->sum(DB::raw('balance_amount')) ?: 0), 2, '.', ''),
                    'icon' => 'bi-cash-coin',
                    'tone' => 'warning',
                ],
                [
                    'label' => 'Pending assignments',
                    'value' => StudentFee::query()->where('status', StudentFee::STATUS_PENDING)->count(),
                    'icon' => 'bi-hourglass-split',
                    'tone' => 'primary',
                ],
                [
                    'label' => 'Partial assignments',
                    'value' => StudentFee::query()->where('status', StudentFee::STATUS_PARTIAL)->count(),
                    'icon' => 'bi-pie-chart',
                    'tone' => 'secondary',
                ],
                [
                    'label' => 'Paid assignments',
                    'value' => StudentFee::query()->where('status', StudentFee::STATUS_PAID)->count(),
                    'icon' => 'bi-check2-circle',
                    'tone' => 'success',
                ],
            ],
        ];
    }

    private function activeTeacherProfile(User $user): ?Teacher
    {
        return $user->teacherProfile()
            ->whereNull('deleted_at')
            ->where('status', Teacher::STATUS_ACTIVE)
            ->first();
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
