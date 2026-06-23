<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

#[Fillable(['exam_id', 'exam_subject_id', 'student_id', 'subject_id', 'marks_obtained', 'grade_scale_id', 'result_status', 'remarks', 'entered_by'])]
class ExamResult extends Model
{
    use BelongsToTenant;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PASS = 'pass';

    public const STATUS_FAIL = 'fail';

    public const STATUS_ABSENT = 'absent';

    /**
     * @var list<string>
     */
    private const IMMUTABLE_RESULT_FIELDS = [
        'exam_id',
        'exam_subject_id',
        'student_id',
        'subject_id',
        'entered_by',
    ];

    protected static function booted(): void
    {
        static::updating(function (ExamResult $examResult): void {
            foreach (self::IMMUTABLE_RESULT_FIELDS as $field) {
                if ($examResult->isDirty($field)) {
                    throw new LogicException('Exam Result identity cannot be changed.');
                }
            }
        });

        static::deleting(fn (): never => throw new LogicException('Exam Result records are retained and cannot be deleted.'));
    }

    protected function casts(): array
    {
        return [
            'marks_obtained' => 'decimal:2',
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

    public function examSubject(): BelongsTo
    {
        return $this->belongsTo(ExamSubject::class);
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class)->withTrashed();
    }

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class)->withTrashed();
    }

    public function gradeScale(): BelongsTo
    {
        return $this->belongsTo(GradeScale::class);
    }

    public function enteredBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'entered_by')->withTrashed();
    }
}
