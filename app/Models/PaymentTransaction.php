<?php

namespace App\Models;

use App\Models\Concerns\BelongsToTenant;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use LogicException;

#[Fillable(['fee_payment_id', 'transaction_no', 'gateway_reference', 'amount', 'payment_mode', 'status', 'processed_at', 'raw_response'])]
class PaymentTransaction extends Model
{
    use BelongsToTenant;

    public const MODE_CASH = FeePayment::MODE_CASH;

    public const MODE_CARD = FeePayment::MODE_CARD;

    public const MODE_UPI = FeePayment::MODE_UPI;

    public const MODE_BANK_TRANSFER = FeePayment::MODE_BANK_TRANSFER;

    public const MODE_SANDBOX_GATEWAY = FeePayment::MODE_SANDBOX_GATEWAY;

    public const STATUS_COMPLETED = FeePayment::STATUS_COMPLETED;

    public const STATUS_PENDING = FeePayment::STATUS_PENDING;

    public const STATUS_FAILED = FeePayment::STATUS_FAILED;

    public const STATUS_REVERSED = FeePayment::STATUS_REVERSED;

    /**
     * @var list<string>
     */
    private const IMMUTABLE_TRANSACTION_FIELDS = [
        'fee_payment_id',
        'transaction_no',
        'gateway_reference',
        'amount',
        'payment_mode',
        'processed_at',
        'raw_response',
    ];

    protected static function booted(): void
    {
        static::updating(function (PaymentTransaction $paymentTransaction): void {
            foreach (self::IMMUTABLE_TRANSACTION_FIELDS as $field) {
                if ($paymentTransaction->isDirty($field)) {
                    throw new LogicException('Payment Transaction financial history cannot be changed.');
                }
            }
        });

        static::deleting(fn (): never => throw new LogicException('Payment Transaction records are retained and cannot be deleted.'));
    }

    protected function casts(): array
    {
        return [
            'amount' => 'decimal:2',
            'processed_at' => 'datetime',
            'raw_response' => 'array',
        ];
    }

    public function school(): BelongsTo
    {
        return $this->belongsTo(School::class);
    }

    public function feePayment(): BelongsTo
    {
        return $this->belongsTo(FeePayment::class);
    }
}
