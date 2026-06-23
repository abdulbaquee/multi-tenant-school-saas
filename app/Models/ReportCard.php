<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

#[Fillable(['exam_id', 'student_id', 'academic_year_id', 'class_id', 'section_id', 'total_marks', 'marks_obtained', 'percentage', 'grade_scale_id', 'result_status', 'generated_at', 'generated_by'])]
class ReportCard extends Model
{
    use BelongsToTenant;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PASS = 'pass';

    public const STATUS_FAIL = 'fail';

    /**
     * @var list<string>
     */
    private const IMMUTABLE_GENERATION_FIELDS = [
        'exam_id',
        'student_id',
        'academic_year_id',
        'class_id',
        'section_id',
        'generated_by',
    ];

    protected static function booted(): void
    {
        static::updating(function (ReportCard $reportCard): void {
            foreach (self::IMMUTABLE_GENERATION_FIELDS as $field) {
                if ($reportCard->isDirty($field)) {
                    throw new LogicException('Report Card generation scope cannot be changed.');
                }
            }
        });

        static::deleting(fn (): never => throw new LogicException('Report Card records are retained and cannot be deleted.'));
    }

    protected function casts(): array
    {
        return [
            'total_marks' => 'decimal:2',
            'marks_obtained' => 'decimal:2',
            'percentage' => 'decimal:2',
            'generated_at' => 'datetime',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function exam(): BelongsTo
    {
        return $this->belongsTo(Exam::class)->withTrashed();
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class)->withTrashed();
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id')->withTrashed();
    }

    public function section(): BelongsTo
    {
        return $this->belongsTo(Section::class)->withTrashed();
    }

    public function gradeScale(): BelongsTo
    {
        return $this->belongsTo(GradeScale::class);
    }

    public function generatedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'generated_by')->withTrashed();
    }
}
