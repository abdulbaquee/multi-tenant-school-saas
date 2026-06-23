<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use LogicException;

#[Fillable(['fee_category_id', 'academic_year_id', 'class_id', 'amount', 'due_date', 'frequency', 'status'])]
class FeeStructure extends Model
{
    use BelongsToTenant, SoftDeletes;

    public const FREQUENCY_ONE_TIME = 'one_time';

    public const FREQUENCY_MONTHLY = 'monthly';

    public const FREQUENCY_QUARTERLY = 'quarterly';

    public const FREQUENCY_ANNUAL = 'annual';

    public const STATUS_ACTIVE = 'active';

    public const STATUS_INACTIVE = 'inactive';

    /**
     * @var list<string>
     */
    private const IMMUTABLE_SCOPE_FIELDS = [
        'fee_category_id',
        'academic_year_id',
        'class_id',
    ];

    protected static function booted(): void
    {
        static::updating(function (FeeStructure $feeStructure): void {
            foreach (self::IMMUTABLE_SCOPE_FIELDS as $field) {
                if ($feeStructure->isDirty($field)) {
                    throw new LogicException('Fee Structure scope cannot be changed.');
                }
            }
        });
    }

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'due_date' => 'date',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function feeCategory(): BelongsTo
    {
        return $this->belongsTo(FeeCategory::class)->withTrashed();
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function schoolClass(): BelongsTo
    {
        return $this->belongsTo(SchoolClass::class, 'class_id')->withTrashed();
    }

    public function studentFees(): HasMany
    {
        return $this->hasMany(StudentFee::class);
    }
}
