<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

#[Fillable(['exam_id', 'subject_id', 'class_id', 'exam_date', 'max_marks', 'passing_marks'])]
class ExamSubject extends Model
{
    use BelongsToTenant;

    /**
     * @var list<string>
     */
    private const IMMUTABLE_SCOPE_FIELDS = [
        'exam_id',
        'subject_id',
        'class_id',
    ];

    protected static function booted(): void
    {
        static::updating(function (ExamSubject $examSubject): void {
            foreach (self::IMMUTABLE_SCOPE_FIELDS as $field) {
                if ($examSubject->isDirty($field)) {
                    throw new LogicException('Exam Subject scope cannot be changed.');
                }
            }
        });
    }

    protected function casts(): array
    {
        return [
            'exam_date' => 'date',
            'max_marks' => 'decimal:2',
            'passing_marks' => 'decimal:2',
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

    public function subject(): BelongsTo
    {
        return $this->belongsTo(Subject::class)->withTrashed();
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id')->withTrashed();
    }

    public function examResults(): HasMany
    {
        return $this->hasMany(ExamResult::class);
    }
}
