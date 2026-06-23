<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use LogicException;

#[Fillable(['student_fee_id', 'student_id', 'receipt_no', 'amount_paid', 'payment_date', 'payment_mode', 'status', 'received_by', 'remarks'])]
class FeePayment extends Model
{
    use BelongsToTenant;

    public const MODE_CASH = 'cash';

    public const MODE_CARD = 'card';

    public const MODE_UPI = 'upi';

    public const MODE_BANK_TRANSFER = 'bank_transfer';

    public const MODE_SANDBOX_GATEWAY = 'sandbox_gateway';

    public const STATUS_COMPLETED = 'completed';

    public const STATUS_PENDING = 'pending';

    public const STATUS_FAILED = 'failed';

    public const STATUS_REVERSED = 'reversed';

    /**
     * @var list<string>
     */
    private const IMMUTABLE_FINANCIAL_FIELDS = [
        'student_fee_id',
        'student_id',
        'receipt_no',
        'amount_paid',
        'payment_date',
        'payment_mode',
        'received_by',
    ];

    protected static function booted(): void
    {
        static::updating(function (FeePayment $feePayment): void {
            foreach (self::IMMUTABLE_FINANCIAL_FIELDS as $field) {
                if ($feePayment->isDirty($field)) {
                    throw new LogicException('Fee Payment financial history cannot be changed.');
                }
            }
        });

        static::deleting(fn (): never => throw new LogicException('Fee Payment records are retained and cannot be deleted.'));
    }

    protected function casts(): array
    {
        return [
            'amount_paid' => 'decimal:2',
            'payment_date' => 'date',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function studentFee(): BelongsTo
    {
        return $this->belongsTo(StudentFee::class)->withTrashed();
    }

    public function student(): BelongsTo
    {
        return $this->belongsTo(Student::class)->withTrashed();
    }

    public function receivedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'received_by')->withTrashed();
    }

    public function paymentTransactions(): HasMany
    {
        return $this->hasMany(PaymentTransaction::class);
    }
}
