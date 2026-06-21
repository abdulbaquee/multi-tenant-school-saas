<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use LogicException;

#[Fillable(['admission_no', 'first_name', 'last_name', 'gender', 'date_of_birth', 'photo_path', 'guardian_name', 'guardian_phone', 'guardian_email', 'address', 'admission_date', 'status'])]
class Student extends Model
{
    use BelongsToTenant, SoftDeletes;

    public const GENDER_MALE = 'male';

    public const GENDER_FEMALE = 'female';

    public const GENDER_OTHER = 'other';

    public const GENDER_PREFER_NOT_TO_SAY = 'prefer_not_to_say';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    public const STATUS_TRANSFERRED = 'transferred';

    public const STATUS_GRADUATED = 'graduated';

    protected static function booted(): void
    {
        static::updating(function (Student $student): void {
            if ($student->isDirty('admission_no')) {
                throw new LogicException('Student admission number cannot be changed.');
            }
        });
    }

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'admission_date' => 'date',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function enrollments(): HasMany
    {
        return $this->hasMany(StudentEnrollment::class);
    }
}
