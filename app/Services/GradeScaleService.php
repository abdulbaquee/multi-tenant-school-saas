<?php

namespace App\Services;

use App\Models\ExamResult;
use App\Models\GradeScale;
use Illuminate\Validation\ValidationException;

class GradeScaleService
{
    /**
     * @var list<array{grade: string, min_percentage: string, max_percentage: string, grade_point: string, remarks: string}>
     */
    private const DEFAULT_SCALES = [
        ['grade' => 'A+', 'min_percentage' => '91.00', 'max_percentage' => '100.00', 'grade_point' => '10.00', 'remarks' => 'Outstanding'],
        ['grade' => 'A', 'min_percentage' => '81.00', 'max_percentage' => '90.99', 'grade_point' => '9.00', 'remarks' => 'Excellent'],
        ['grade' => 'B+', 'min_percentage' => '71.00', 'max_percentage' => '80.99', 'grade_point' => '8.00', 'remarks' => 'Very Good'],
        ['grade' => 'B', 'min_percentage' => '61.00', 'max_percentage' => '70.99', 'grade_point' => '7.00', 'remarks' => 'Good'],
        ['grade' => 'C', 'min_percentage' => '51.00', 'max_percentage' => '60.99', 'grade_point' => '6.00', 'remarks' => 'Average'],
        ['grade' => 'D', 'min_percentage' => '33.00', 'max_percentage' => '50.99', 'grade_point' => '5.00', 'remarks' => 'Pass'],
        ['grade' => 'F', 'min_percentage' => '0.00', 'max_percentage' => '32.99', 'grade_point' => '0.00', 'remarks' => 'Fail'],
    ];

    public function ensureDefaultScalesExist(): void
    {
        if (GradeScale::query()->exists()) {
            return;
        }

        foreach (self::DEFAULT_SCALES as $scale) {
            GradeScale::create($scale);
        }
    }

    public function resolveForPercentage(string $percentage): ?GradeScale
    {
        return GradeScale::query()
            ->where('min_percentage', '<=', $percentage)
            ->where('max_percentage', '>=', $percentage)
            ->orderByDesc('min_percentage')
            ->first();
    }

    /**
     * @return array{marks_obtained: string, grade_scale_id: int|null, result_status: string}
     */
    public function calculateResultValues(
        string $marksObtained,
        string $maxMarks,
        string $passingMarks,
        string $requestedStatus,
    ): array {
        if ($requestedStatus === ExamResult::STATUS_ABSENT) {
            return [
                'marks_obtained' => '0.00',
                'grade_scale_id' => null,
                'result_status' => ExamResult::STATUS_ABSENT,
            ];
        }

        if (bccomp($marksObtained, $maxMarks, 2) === 1) {
            throw ValidationException::withMessages([
                'entries' => 'Marks obtained cannot exceed maximum marks.',
            ]);
        }

        if (bccomp($marksObtained, '0.00', 2) === -1) {
            throw ValidationException::withMessages([
                'entries' => 'Marks obtained cannot be negative.',
            ]);
        }

        $percentage = bccomp($maxMarks, '0.00', 2) === 0
            ? '0.00'
            : bcmul(bcdiv($marksObtained, $maxMarks, 4), '100', 2);
        $gradeScale = $this->resolveForPercentage($percentage);

        return [
            'marks_obtained' => number_format((float) $marksObtained, 2, '.', ''),
            'grade_scale_id' => $gradeScale?->id,
            'result_status' => bccomp($marksObtained, $passingMarks, 2) >= 0
                ? ExamResult::STATUS_PASS
                : ExamResult::STATUS_FAIL,
        ];
    }
}
