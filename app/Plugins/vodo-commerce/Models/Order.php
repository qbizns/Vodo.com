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

class Order extends Model
{
    use BelongsToStore, HasFactory, SoftDeletes;

    protected $table = 'commerce_orders';

    public const STATUS_PENDING = 'pending';
    public const STATUS_PROCESSING = 'processing';
    public const STATUS_ON_HOLD = 'on_hold';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';
    public const STATUS_REFUNDED = 'refunded';
    public const STATUS_FAILED = 'failed';

    public const PAYMENT_PENDING = 'pending';
    public const PAYMENT_PAID = 'paid';
    public const PAYMENT_FAILED = 'failed';
    public const PAYMENT_REFUNDED = 'refunded';

    public const FULFILLMENT_UNFULFILLED = 'unfulfilled';
    public const FULFILLMENT_PARTIAL = 'partial';
    public const FULFILLMENT_FULFILLED = 'fulfilled';

    protected $fillable = [
        'store_id',
        'customer_id',
        'order_number',
        'customer_email',
        'status',
        'payment_status',
        'fulfillment_status',
        'currency',
        'subtotal',
        'discount_total',
        'shipping_total',
        'tax_total',
        'total',
        'refund_total',
        'has_refunds',
        'billing_address',
        'shipping_address',
        'shipping_method',
        'payment_method',
        'payment_reference',
        'discount_codes',
        'notes',
        'cancel_reason',
        'cancelled_by_type',
        'cancelled_by_id',
        'cancelled_at',
        'is_exported',
        'exported_at',
        'meta',
        'placed_at',
        'paid_at',
        'completed_at',
    ];

    protected function casts(): array
    {
        return [
            'subtotal' => 'decimal:2',
            'discount_total' => 'decimal:2',
            'shipping_total' => 'decimal:2',
            'tax_total' => 'decimal:2',
            'total' => 'decimal:2',
            'refund_total' => 'decimal:2',
            'has_refunds' => 'boolean',
            'is_exported' => 'boolean',
            'billing_address' => 'array',
            'shipping_address' => 'array',
            'discount_codes' => 'array',
            'meta' => 'array',
            'placed_at' => 'datetime',
            'paid_at' => 'datetime',
            'completed_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'exported_at' => 'datetime',
        ];
    }

    protected static function boot(): void
    {
        parent::boot();

        static::creating(function (Order $order) {
            if (empty($order->order_number)) {
                $order->order_number = static::generateOrderNumber($order->store_id);
            }
        });
    }

    /**
     * Generate a unique order number with collision protection.
     *
     * Format: ORD-YYMMDD-XXXXXXXX (8 random chars = 2.8 trillion combinations)
     * Uses retry logic to handle the rare case of collision.
     *
     * @param int $storeId Store ID for scoping
     * @param int $maxRetries Maximum retry attempts
     * @return string Unique order number
     * @throws \RuntimeException If unable to generate unique number after retries
     */
    public static function generateOrderNumber(int $storeId, int $maxRetries = 5): string
    {
        $prefix = 'ORD';
        $timestamp = now()->format('ymd');

        for ($attempt = 1; $attempt <= $maxRetries; $attempt++) {
            // 8 random chars = 36^8 = 2,821,109,907,456 combinations
            $random = strtoupper(Str::random(8));
            $orderNumber = "{$prefix}-{$timestamp}-{$random}";

            // Check for collision within same store
            $exists = static::where('store_id', $storeId)
                ->where('order_number', $orderNumber)
                ->exists();

            if (!$exists) {
                return $orderNumber;
            }

            // Log collision for monitoring (should be extremely rare)
            \Illuminate\Support\Facades\Log::warning('Order number collision detected', [
                'order_number' => $orderNumber,
                'store_id' => $storeId,
                'attempt' => $attempt,
            ]);
        }

        throw new \RuntimeException(
            "Unable to generate unique order number after {$maxRetries} attempts"
        );
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function items(): HasMany
    {
        return $this->hasMany(OrderItem::class);
    }

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

    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    public function isPaid(): bool
    {
        return $this->payment_status === self::PAYMENT_PAID;
    }

    public function isFulfilled(): bool
    {
        return $this->fulfillment_status === self::FULFILLMENT_FULFILLED;
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_PROCESSING, self::STATUS_ON_HOLD]);
    }

    public function canBeRefunded(): bool
    {
        return $this->payment_status === self::PAYMENT_PAID && !$this->isCancelled();
    }

    public function markAsPaid(?string $reference = null): void
    {
        $this->update([
            'payment_status' => self::PAYMENT_PAID,
            'payment_reference' => $reference ?? $this->payment_reference,
            'paid_at' => now(),
        ]);

        if ($this->status === self::STATUS_PENDING) {
            $this->update(['status' => self::STATUS_PROCESSING]);
        }
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'fulfillment_status' => self::FULFILLMENT_FULFILLED,
            'completed_at' => now(),
        ]);
    }

    public function cancel(
        ?string $reason = null,
        ?string $cancelledByType = 'system',
        ?int $cancelledById = null
    ): void {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'cancel_reason' => $reason,
            'cancelled_by_type' => $cancelledByType,
            'cancelled_by_id' => $cancelledById,
            'cancelled_at' => now(),
        ]);

        // Restore stock
        foreach ($this->items as $item) {
            if ($item->product) {
                $item->product->incrementStock($item->quantity);
            }
        }

        // Add timeline event
        OrderTimelineEvent::createEvent(
            $this,
            'order_cancelled',
            'Order Cancelled',
            $reason,
            null,
            $cancelledByType,
            $cancelledById
        );
    }

    public function getItemCount(): int
    {
        return $this->items->sum('quantity');
    }

    public function recalculateTotals(): void
    {
        $subtotal = $this->items->sum('total');

        $this->update([
            'subtotal' => $subtotal,
            'total' => $subtotal - $this->discount_total + $this->shipping_total + $this->tax_total,
        ]);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeProcessing($query)
    {
        return $query->where('status', self::STATUS_PROCESSING);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeUnfulfilled($query)
    {
        return $query->where('fulfillment_status', self::FULFILLMENT_UNFULFILLED);
    }

    /**
     * Phase 3: Order Management Extensions - Relationships
     */

    public function notes(): HasMany
    {
        return $this->hasMany(OrderNote::class);
    }

    public function fulfillments(): HasMany
    {
        return $this->hasMany(OrderFulfillment::class);
    }

    public function refunds(): HasMany
    {
        return $this->hasMany(OrderRefund::class);
    }

    public function timeline(): HasMany
    {
        return $this->hasMany(OrderTimelineEvent::class)->orderBy('created_at', 'desc');
    }

    public function statusHistories(): HasMany
    {
        return $this->hasMany(OrderStatusHistory::class)->orderBy('created_at', 'desc');
    }

    /**
     * Phase 3: Order Management Extensions - Methods
     */

    public function addNote(
        string $content,
        bool $isCustomerVisible = false,
        ?string $authorType = 'admin',
        ?int $authorId = null
    ): OrderNote {
        return $this->notes()->create([
            'store_id' => $this->store_id,
            'content' => $content,
            'is_customer_visible' => $isCustomerVisible,
            'author_type' => $authorType,
            'author_id' => $authorId,
        ]);
    }

    public function createRefund(
        float $amount,
        array $items,
        ?string $reason = null,
        string $refundMethod = 'original_payment'
    ): OrderRefund {
        $refund = $this->refunds()->create([
            'store_id' => $this->store_id,
            'amount' => $amount,
            'reason' => $reason,
            'refund_method' => $refundMethod,
            'status' => 'pending',
        ]);

        foreach ($items as $item) {
            $refund->items()->create([
                'order_item_id' => $item['order_item_id'],
                'quantity' => $item['quantity'],
                'amount' => $item['amount'],
            ]);
        }

        return $refund;
    }

    public function export(): void
    {
        $this->update([
            'is_exported' => true,
            'exported_at' => now(),
        ]);
    }

    /**
     * Phase 3: Order Management Extensions - Scopes
     */

    public function scopeCancelled($query)
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }

    public function scopeRefunded($query)
    {
        return $query->where('status', self::STATUS_REFUNDED);
    }

    public function scopeExported($query)
    {
        return $query->where('is_exported', true);
    }

    public function scopeNotExported($query)
    {
        return $query->where('is_exported', false);
    }

    /**
     * Check if order has been exported.
     */
    public function isExported(): bool
    {
        return $this->is_exported;
    }

    /**
     * Check if order has refunds.
     */
    public function hasRefunds(): bool
    {
        return $this->has_refunds;
    }
}
