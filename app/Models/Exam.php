<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use LogicException;

#[Fillable(['academic_year_id', 'academic_term_id', 'name', 'exam_type', 'start_date', 'end_date', 'status'])]
class Exam extends Model
{
    use BelongsToTenant, SoftDeletes;

    public const TYPE_TERM = 'term';

    public const TYPE_UNIT_TEST = 'unit_test';

    public const TYPE_FINAL = 'final';

    public const TYPE_OTHER = 'other';

    public const STATUS_SCHEDULED = 'scheduled';

    public const STATUS_ONGOING = 'ongoing';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_CANCELLED = 'cancelled';

    /**
     * @var list<string>
     */
    private const IMMUTABLE_SCOPE_FIELDS = [
        'academic_year_id',
        'academic_term_id',
        'name',
    ];

    protected static function booted(): void
    {
        static::updating(function (Exam $exam): void {
            foreach (self::IMMUTABLE_SCOPE_FIELDS as $field) {
                if ($exam->isDirty($field)) {
                    throw new LogicException('Exam scope cannot be changed.');
                }
            }
        });
    }

    protected function casts(): array
    {
        return [
            'start_date' => 'date',
            'end_date' => 'date',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function academicTerm(): BelongsTo
    {
        return $this->belongsTo(AcademicTerm::class);
    }

    public function examSubjects(): HasMany
    {
        return $this->hasMany(ExamSubject::class);
    }

    public function examResults(): HasMany
    {
        return $this->hasMany(ExamResult::class);
    }

    public function reportCards(): HasMany
    {
        return $this->hasMany(ReportCard::class);
    }
}
