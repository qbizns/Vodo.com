<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use App\Traits\BelongsToStore;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasManyThrough;
use Illuminate\Database\Eloquent\SoftDeletes;

class OrderRefund extends Model
{
    use BelongsToStore, SoftDeletes;

    protected $table = 'commerce_order_refunds';

    protected $fillable = [
        'store_id',
        'order_id',
        'refund_number',
        'amount',
        'reason',
        'status',
        'refund_method',
        'processed_at',
        'notes',
    ];

    protected $casts = [
        'amount' => 'decimal:2',
        'processed_at' => 'datetime',
        'created_at' => 'datetime',
        'updated_at' => 'datetime',
        'deleted_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (OrderRefund $refund) {
            if (empty($refund->refund_number)) {
                $refund->refund_number = $refund->generateRefundNumber();
            }
        });

        static::saved(function (OrderRefund $refund) {
            $refund->updateOrderRefundTotals();
        });
    }

    /**
     * Get the order that owns the refund.
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class, 'order_id');
    }

    /**
     * Get the refund items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(OrderRefundItem::class, 'refund_id');
    }

    /**
     * Get order items through refund items.
     */
    public function orderItems(): HasManyThrough
    {
        return $this->hasManyThrough(
            OrderItem::class,
            OrderRefundItem::class,
            'refund_id',
            'id',
            'id',
            'order_item_id'
        );
    }

    /**
     * Scope: Filter by status.
     */
    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope: Get pending refunds.
     */
    public function scopePending($query)
    {
        return $query->where('status', 'pending');
    }

    /**
     * Scope: Get completed refunds.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    /**
     * Approve the refund.
     */
    public function approve(): bool
    {
        $this->status = 'processing';

        return $this->save();
    }

    /**
     * Reject the refund.
     */
    public function reject(string $reason = null): bool
    {
        $this->status = 'rejected';

        if ($reason) {
            $this->notes = $reason;
        }

        return $this->save();
    }

    /**
     * Process and complete the refund.
     */
    public function process(): bool
    {
        $this->status = 'completed';
        $this->processed_at = now();

        return $this->save();
    }

    /**
     * Get the count of items in this refund.
     */
    public function getItemsCount(): int
    {
        return $this->items()->count();
    }

    /**
     * Generate a unique refund number.
     */
    public function generateRefundNumber(): string
    {
        $prefix = 'RF';
        $timestamp = now()->format('YmdHis');
        $random = strtoupper(substr(md5(uniqid((string) mt_rand(), true)), 0, 4));

        return "{$prefix}-{$timestamp}-{$random}";
    }

    /**
     * Update order refund totals.
     */
    protected function updateOrderRefundTotals(): void
    {
        if (!$this->order) {
            return;
        }

        $totalRefunds = $this->order->refunds()
            ->whereIn('status', ['processing', 'completed'])
            ->sum('amount');

        $this->order->update([
            'refund_total' => $totalRefunds,
            'has_refunds' => $totalRefunds > 0,
        ]);
    }

    /**
     * Check if refund is pending.
     */
    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    /**
     * Check if refund is approved.
     */
    public function isApproved(): bool
    {
        return $this->status === 'processing';
    }

    /**
     * Check if refund is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === 'completed';
    }

    /**
     * Check if refund is rejected.
     */
    public function isRejected(): bool
    {
        return $this->status === 'rejected';
    }
}
