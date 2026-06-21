<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

#[Fillable(['student_id', 'academic_year_id', 'class_id', 'section_id', 'roll_no', 'enrollment_date', 'status'])]
class StudentEnrollment extends Model
{
    use BelongsToTenant;

    public const STATUS_ACTIVE = 'active';

    public const STATUS_TRANSFERRED = 'transferred';

    public const STATUS_COMPLETED = 'completed';

    /**
     * @var list<string>
     */
    private const IMMUTABLE_PLACEMENT_FIELDS = [
        'student_id',
        'academic_year_id',
        'class_id',
        'section_id',
        'roll_no',
    ];

    protected static function booted(): void
    {
        static::updating(function (StudentEnrollment $enrollment): void {
            foreach (self::IMMUTABLE_PLACEMENT_FIELDS as $field) {
                if ($enrollment->isDirty($field)) {
                    throw new LogicException('Student Enrollment placement cannot be changed.');
                }
            }
        });

        static::deleting(fn (): never => throw new LogicException('Student Enrollment records are retained and cannot be deleted.'));
    }

    protected function casts(): array
    {
        return [
            'enrollment_date' => 'date',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
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
}
