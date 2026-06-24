<?php

namespace App\Services;

use App\Models\Attendance;
use App\Models\ExamResult;
use App\Models\Role;
use App\Models\School;
use App\Models\Student;
use App\Models\StudentFee;
use App\Models\User;
use App\Tenancy\TenantContext;
use Carbon\CarbonImmutable;
use Illuminate\Auth\Access\AuthorizationException;
use Illuminate\Support\Facades\Gate;

class AnalyticsService
{
    public function __construct(private readonly TenantContext $tenantContext) {}

    /**
     * @return array{
     *     title: string,
     *     description: string,
     *     charts: list<array{
     *         type: string,
     *         title: string,
     *         labels: list<string>,
     *         datasets: list<array{label: string, data: list<int|float>}>
     *     }>
     * }
     */
    public function pageFor(User $actor): array
    {
        $this->authorizeActorContext($actor);

        if (! Gate::forUser($actor)->allows('analytics.view')) {
            throw new AuthorizationException;
        }

        return match ($actor->role?->code) {
            Role::SUPER_ADMIN => $this->platformPage(),
            Role::SCHOOL_ADMIN => $this->schoolPage($actor),
            default => throw new AuthorizationException,
        };
    }

    /**
     * @return array{title: string, description: string, charts: list<array<string, mixed>>}
     */
    private function platformPage(): array
    {
        return [
            'title' => 'Platform analytics',
            'description' => 'Aggregate operational trends across schools.',
            'charts' => [
                $this->chart(
                    'doughnut',
                    'Schools by status',
                    ['Active', 'Inactive'],
                    [
                        [
                            'label' => 'Schools',
                            'data' => [
                                School::query()->where('status', School::STATUS_ACTIVE)->count(),
                                School::query()->where('status', School::STATUS_INACTIVE)->count(),
                            ],
                        ],
                    ],
                ),
                $this->chart(
                    'bar',
                    'Students by school',
                    $this->topSchoolLabels(),
                    [
                        [
                            'label' => 'Active students',
                            'data' => $this->topSchoolStudentCounts(),
                        ],
                    ],
                ),
                $this->chart(
                    'line',
                    'Attendance records (last 6 months)',
                    $this->recentMonthLabels(),
                    [
                        [
                            'label' => 'Records',
                            'data' => $this->recentAttendanceTrend(),
                        ],
                    ],
                ),
                $this->chart(
                    'pie',
                    'Fee assignments by status',
                    ['Pending', 'Partial', 'Paid', 'Waived'],
                    [
                        [
                            'label' => 'Assignments',
                            'data' => [
                                StudentFee::query()->where('status', StudentFee::STATUS_PENDING)->count(),
                                StudentFee::query()->where('status', StudentFee::STATUS_PARTIAL)->count(),
                                StudentFee::query()->where('status', StudentFee::STATUS_PAID)->count(),
                                StudentFee::query()->where('status', StudentFee::STATUS_WAIVED)->count(),
                            ],
                        ],
                    ],
                ),
            ],
        ];
    }

    /**
     * @return array{title: string, description: string, charts: list<array<string, mixed>>}
     */
    private function schoolPage(User $actor): array
    {
        $actor->loadMissing('school');

        return [
            'title' => 'School analytics',
            'description' => 'Operational trends for '.($actor->school?->name ?? 'your school').'.',
            'charts' => [
                $this->chart(
                    'doughnut',
                    'Students by status',
                    ['Active', 'Inactive', 'Transferred', 'Graduated'],
                    [
                        [
                            'label' => 'Students',
                            'data' => [
                                Student::query()->whereNull('deleted_at')->where('status', Student::STATUS_ACTIVE)->count(),
                                Student::query()->whereNull('deleted_at')->where('status', Student::STATUS_INACTIVE)->count(),
                                Student::query()->whereNull('deleted_at')->where('status', Student::STATUS_TRANSFERRED)->count(),
                                Student::query()->whereNull('deleted_at')->where('status', Student::STATUS_GRADUATED)->count(),
                            ],
                        ],
                    ],
                ),
                $this->chart(
                    'bar',
                    'Attendance this month',
                    ['Present', 'Absent', 'Leave', 'Late'],
                    [
                        [
                            'label' => 'Records',
                            'data' => [
                                $this->attendanceStatusCount(Attendance::STATUS_PRESENT),
                                $this->attendanceStatusCount(Attendance::STATUS_ABSENT),
                                $this->attendanceStatusCount(Attendance::STATUS_LEAVE),
                                $this->attendanceStatusCount(Attendance::STATUS_LATE),
                            ],
                        ],
                    ],
                ),
                $this->chart(
                    'pie',
                    'Fee assignments by status',
                    ['Pending', 'Partial', 'Paid', 'Waived'],
                    [
                        [
                            'label' => 'Assignments',
                            'data' => [
                                StudentFee::query()->where('status', StudentFee::STATUS_PENDING)->count(),
                                StudentFee::query()->where('status', StudentFee::STATUS_PARTIAL)->count(),
                                StudentFee::query()->where('status', StudentFee::STATUS_PAID)->count(),
                                StudentFee::query()->where('status', StudentFee::STATUS_WAIVED)->count(),
                            ],
                        ],
                    ],
                ),
                $this->chart(
                    'doughnut',
                    'Examination results',
                    ['Pass', 'Fail', 'Absent', 'Pending'],
                    [
                        [
                            'label' => 'Results',
                            'data' => [
                                ExamResult::query()->where('result_status', ExamResult::STATUS_PASS)->count(),
                                ExamResult::query()->where('result_status', ExamResult::STATUS_FAIL)->count(),
                                ExamResult::query()->where('result_status', ExamResult::STATUS_ABSENT)->count(),
                                ExamResult::query()->where('result_status', ExamResult::STATUS_PENDING)->count(),
                            ],
                        ],
                    ],
                ),
            ],
        ];
    }

    /**
     * @param  list<string>  $labels
     * @param  list<array{label: string, data: list<int|float>}>  $datasets
     * @return array{type: string, title: string, labels: list<string>, datasets: list<array{label: string, data: list<int|float>}>}
     */
    private function chart(string $type, string $title, array $labels, array $datasets): array
    {
        return [
            'type' => $type,
            'title' => $title,
            'labels' => $labels,
            'datasets' => $datasets,
        ];
    }

    /**
     * @return list<string>
     */
    private function topSchoolLabels(): array
    {
        return School::query()
            ->orderBy('name')
            ->limit(5)
            ->pluck('name')
            ->all();
    }

    /**
     * @return list<int>
     */
    private function topSchoolStudentCounts(): array
    {
        return School::query()
            ->orderBy('name')
            ->limit(5)
            ->get()
            ->map(fn (School $school): int => Student::query()
                ->where('school_id', $school->id)
                ->whereNull('deleted_at')
                ->where('status', Student::STATUS_ACTIVE)
                ->count())
            ->all();
    }

    /**
     * @return list<string>
     */
    private function recentMonthLabels(): array
    {
        $now = CarbonImmutable::now();

        return collect(range(5, 0))
            ->map(fn (int $offset): string => $now->subMonths($offset)->format('M Y'))
            ->all();
    }

    /**
     * @return list<int>
     */
    private function recentAttendanceTrend(): array
    {
        $now = CarbonImmutable::now();

        return collect(range(5, 0))
            ->map(function (int $offset) use ($now): int {
                $month = $now->subMonths($offset);

                return Attendance::query()
                    ->whereYear('attendance_date', $month->year)
                    ->whereMonth('attendance_date', $month->month)
                    ->count();
            })
            ->all();
    }

    private function attendanceStatusCount(string $status): int
    {
        $now = CarbonImmutable::now();

        return Attendance::query()
            ->whereYear('attendance_date', $now->year)
            ->whereMonth('attendance_date', $now->month)
            ->where('status', $status)
            ->count();
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
