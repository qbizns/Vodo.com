<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;
use VodoCommerce\Traits\BelongsToStore;

class Transaction extends Model
{
    use BelongsToStore, HasFactory, SoftDeletes;

    protected $table = 'commerce_transactions';

    // Transaction Types
    public const TYPE_PAYMENT = 'payment';
    public const TYPE_REFUND = 'refund';
    public const TYPE_PAYOUT = 'payout';
    public const TYPE_FEE = 'fee';
    public const TYPE_ADJUSTMENT = 'adjustment';

    // Transaction Statuses
    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_FAILED = 'failed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REFUNDED = 'refunded';

    // Payment Statuses
    public const PAYMENT_STATUS_AUTHORIZED = 'authorized';
    public const PAYMENT_STATUS_CAPTURED = 'captured';
    public const PAYMENT_STATUS_SETTLED = 'settled';

    protected $fillable = [
        'store_id',
        'order_id',
        'customer_id',
        'payment_method_id',
        'transaction_id',
        'external_id',
        'reference_number',
        'type',
        'status',
        'payment_status',
        'currency',
        'amount',
        'fee_amount',
        'net_amount',
        'fees',
        'payment_method_type',
        'card_brand',
        'card_last4',
        'bank_name',
        'wallet_provider',
        'gateway_response',
        'failure_reason',
        'failure_code',
        'ip_address',
        'user_agent',
        'is_test',
        'authorized_at',
        'captured_at',
        'settled_at',
        'failed_at',
        'refunded_at',
        'processed_at',
        'parent_transaction_id',
        'refunded_amount',
        'refund_reason',
        'metadata',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'amount' => 'float',
            'fee_amount' => 'float',
            'net_amount' => 'float',
            'refunded_amount' => 'float',
            'fees' => 'array',
            'gateway_response' => 'array',
            'metadata' => 'array',
            'is_test' => 'boolean',
            'authorized_at' => 'datetime',
            'captured_at' => 'datetime',
            'settled_at' => 'datetime',
            'failed_at' => 'datetime',
            'refunded_at' => 'datetime',
            'processed_at' => 'datetime',
        ];
    }

    // =========================================================================
    // Boot Method
    // =========================================================================

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function ($transaction) {
            if (empty($transaction->transaction_id)) {
                $transaction->transaction_id = 'TXN-' . strtoupper(Str::random(16));
            }

            if (empty($transaction->reference_number)) {
                $transaction->reference_number = 'REF-' . strtoupper(Str::random(12));
            }
        });
    }

    // =========================================================================
    // Relationships
    // =========================================================================

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function paymentMethod(): BelongsTo
    {
        return $this->belongsTo(PaymentMethod::class);
    }

    public function parentTransaction(): BelongsTo
    {
        return $this->belongsTo(Transaction::class, 'parent_transaction_id');
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(Transaction::class, 'parent_transaction_id')
            ->where('type', self::TYPE_REFUND);
    }

    // =========================================================================
    // Status Check Methods
    // =========================================================================

    public function isPending(): bool
    {
        return $this->status === self::STATUS_PENDING;
    }

    public function isProcessing(): bool
    {
        return $this->status === self::STATUS_PROCESSING;
    }

    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    public function isFailed(): bool
    {
        return $this->status === self::STATUS_FAILED;
    }

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isRefunded(): bool
    {
        return $this->status === self::STATUS_REFUNDED;
    }

    public function isPayment(): bool
    {
        return $this->type === self::TYPE_PAYMENT;
    }

    public function isRefund(): bool
    {
        return $this->type === self::TYPE_REFUND;
    }

    // =========================================================================
    // Action Methods
    // =========================================================================

    /**
     * Mark transaction as completed
     */
    public function markAsCompleted(?string $externalId = null): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'external_id' => $externalId ?? $this->external_id,
            'processed_at' => now(),
        ]);
    }

    /**
     * Mark transaction as failed
     */
    public function markAsFailed(string $reason, ?string $code = null): void
    {
        $this->update([
            'status' => self::STATUS_FAILED,
            'failure_reason' => $reason,
            'failure_code' => $code,
            'failed_at' => now(),
        ]);
    }

    /**
     * Authorize transaction (for card payments)
     */
    public function authorize(?string $externalId = null): void
    {
        $this->update([
            'status' => self::STATUS_PROCESSING,
            'payment_status' => self::PAYMENT_STATUS_AUTHORIZED,
            'external_id' => $externalId ?? $this->external_id,
            'authorized_at' => now(),
        ]);
    }

    /**
     * Capture transaction (for card payments)
     */
    public function capture(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'payment_status' => self::PAYMENT_STATUS_CAPTURED,
            'captured_at' => now(),
            'processed_at' => now(),
        ]);
    }

    /**
     * Settle transaction
     */
    public function settle(): void
    {
        $this->update([
            'payment_status' => self::PAYMENT_STATUS_SETTLED,
            'settled_at' => now(),
        ]);
    }

    /**
     * Cancel transaction
     */
    public function cancel(): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
        ]);
    }

    /**
     * Create a refund transaction
     */
    public function createRefund(float $amount, ?string $reason = null): Transaction
    {
        if ($amount > $this->amount) {
            throw new \InvalidArgumentException('Refund amount cannot exceed original transaction amount');
        }

        if ($this->refunded_amount + $amount > $this->amount) {
            throw new \InvalidArgumentException('Total refund amount would exceed original transaction amount');
        }

        $refund = static::create([
            'store_id' => $this->store_id,
            'order_id' => $this->order_id,
            'customer_id' => $this->customer_id,
            'payment_method_id' => $this->payment_method_id,
            'parent_transaction_id' => $this->id,
            'type' => self::TYPE_REFUND,
            'status' => self::STATUS_PENDING,
            'currency' => $this->currency,
            'amount' => $amount,
            'fee_amount' => 0,
            'net_amount' => $amount,
            'refund_reason' => $reason,
            'is_test' => $this->is_test,
        ]);

        $this->increment('refunded_amount', $amount);

        if ($this->refunded_amount >= $this->amount) {
            $this->update([
                'status' => self::STATUS_REFUNDED,
                'refunded_at' => now(),
            ]);
        }

        return $refund;
    }

    /**
     * Get remaining refundable amount
     */
    public function getRefundableAmount(): float
    {
        return max(0, $this->amount - $this->refunded_amount);
    }

    /**
     * Check if transaction can be refunded
     */
    public function canBeRefunded(): bool
    {
        return $this->isCompleted()
            && $this->isPayment()
            && $this->getRefundableAmount() > 0;
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeFailed($query)
    {
        return $query->where('status', self::STATUS_FAILED);
    }

    public function scopePayments($query)
    {
        return $query->where('type', self::TYPE_PAYMENT);
    }

    public function scopeRefunds($query)
    {
        return $query->where('type', self::TYPE_REFUND);
    }

    public function scopeRecent($query)
    {
        return $query->orderBy('created_at', 'desc');
    }

    public function scopeForOrder($query, int $orderId)
    {
        return $query->where('order_id', $orderId);
    }

    public function scopeForCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    public function scopeInCurrency($query, string $currency)
    {
        return $query->where('currency', strtoupper($currency));
    }

    public function scopeBetweenDates($query, $startDate, $endDate)
    {
        return $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    // =========================================================================
    // Static Helper Methods
    // =========================================================================

    /**
     * Get total revenue for a store
     */
    public static function getTotalRevenue(int $storeId, ?string $currency = null): float
    {
        $query = static::where('store_id', $storeId)
            ->payments()
            ->completed();

        if ($currency) {
            $query->inCurrency($currency);
        }

        return $query->sum('net_amount');
    }

    /**
     * Get total fees for a store
     */
    public static function getTotalFees(int $storeId, ?string $currency = null): float
    {
        $query = static::where('store_id', $storeId)
            ->payments()
            ->completed();

        if ($currency) {
            $query->inCurrency($currency);
        }

        return $query->sum('fee_amount');
    }
}
