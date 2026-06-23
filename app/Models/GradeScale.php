<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

#[Fillable(['grade', 'min_percentage', 'max_percentage', 'grade_point', 'remarks'])]
class GradeScale extends Model
{
    use BelongsToTenant;

    /**
     * @var list<string>
     */
    private const IMMUTABLE_IDENTITY_FIELDS = [
        'grade',
    ];

    protected static function booted(): void
    {
        static::updating(function (GradeScale $gradeScale): void {
            foreach (self::IMMUTABLE_IDENTITY_FIELDS as $field) {
                if ($gradeScale->isDirty($field)) {
                    throw new LogicException('Grade Scale identity cannot be changed.');
                }
            }
        });
    }

    protected function casts(): array
    {
        return [
            'min_percentage' => 'decimal:2',
            'max_percentage' => 'decimal:2',
            'grade_point' => 'decimal:2',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
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
