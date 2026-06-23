<?php

namespace App\Services;

use App\Models\GradeScale;

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
}
