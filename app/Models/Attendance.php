<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

#[Fillable(['student_id', 'academic_year_id', 'class_id', 'section_id', 'attendance_date', 'status', 'remarks', 'marked_by'])]
class Attendance extends Model
{
    use BelongsToTenant;

    public const STATUS_PRESENT = 'present';

    public const STATUS_ABSENT = 'absent';

    public const STATUS_LEAVE = 'leave';

    public const STATUS_LATE = 'late';

    public const STATUS_HOLIDAY = 'holiday';

    /**
     * @var list<string>
     */
    private const IMMUTABLE_IDENTITY_FIELDS = [
        'student_id',
        'academic_year_id',
        'class_id',
        'section_id',
        'attendance_date',
        'marked_by',
    ];

    protected static function booted(): void
    {
        static::updating(function (Attendance $attendance): void {
            foreach (self::IMMUTABLE_IDENTITY_FIELDS as $field) {
                if ($attendance->isDirty($field)) {
                    throw new LogicException('Attendance identity and original marker cannot be changed.');
                }
            }
        });

        static::deleting(fn (): never => throw new LogicException('Attendance records are retained and cannot be deleted.'));
    }

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
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

    public function markedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'marked_by')->withTrashed();
    }
}
