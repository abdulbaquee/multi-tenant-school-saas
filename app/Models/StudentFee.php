<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use LogicException;

#[Fillable(['student_id', 'fee_structure_id', 'academic_year_id', 'amount', 'discount_amount', 'payable_amount', 'paid_amount', 'balance_amount', 'due_date', 'status'])]
class StudentFee extends Model
{
    use BelongsToTenant, SoftDeletes;

    public const STATUS_PENDING = 'pending';

    public const STATUS_PARTIAL = 'partial';

    public const STATUS_PAID = 'paid';

    public const STATUS_WAIVED = 'waived';

    public const STATUS_CANCELLED = 'cancelled';

    /**
     * @var list<string>
     */
    private const IMMUTABLE_ASSIGNMENT_FIELDS = [
        'student_id',
        'fee_structure_id',
        'academic_year_id',
    ];

    protected static function booted(): void
    {
        static::updating(function (StudentFee $studentFee): void {
            foreach (self::IMMUTABLE_ASSIGNMENT_FIELDS as $field) {
                if ($studentFee->isDirty($field)) {
                    throw new LogicException('Student Fee assignment scope cannot be changed.');
                }
            }
        });
    }

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'discount_amount' => 'decimal:2',
            'payable_amount' => 'decimal:2',
            'paid_amount' => 'decimal:2',
            'balance_amount' => 'decimal:2',
            'due_date' => 'date',
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

    public function feeStructure(): BelongsTo
    {
        return $this->belongsTo(FeeStructure::class)->withTrashed();
    }

    public function academicYear(): BelongsTo
    {
        return $this->belongsTo(AcademicYear::class);
    }

    public function feePayments(): HasMany
    {
        return $this->hasMany(FeePayment::class);
    }
}
