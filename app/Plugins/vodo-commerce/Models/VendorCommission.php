<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class VendorCommission extends Model
{
    use HasFactory;

    protected $table = 'commerce_vendor_commissions';

    protected $fillable = [
        'vendor_id',
        'order_id',
        'order_item_id',
        'product_id',
        'payout_id',
        'item_subtotal',
        'item_discount',
        'item_tax',
        'item_total',
        'commission_type',
        'commission_rate',
        'commission_amount',
        'platform_fee',
        'vendor_earnings',
        'status',
        'approved_at',
        'paid_at',
        'disputed_at',
        'dispute_reason',
        'dispute_resolution',
        'dispute_resolved_at',
        'refunded_amount',
        'refunded_at',
        'notes',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'item_subtotal' => 'decimal:2',
            'item_discount' => 'decimal:2',
            'item_tax' => 'decimal:2',
            'item_total' => 'decimal:2',
            'commission_rate' => 'decimal:2',
            'commission_amount' => 'decimal:2',
            'platform_fee' => 'decimal:2',
            'vendor_earnings' => 'decimal:2',
            'refunded_amount' => 'decimal:2',
            'approved_at' => 'datetime',
            'paid_at' => 'datetime',
            'disputed_at' => 'datetime',
            'dispute_resolved_at' => 'datetime',
            'refunded_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    // =========================================================================
    // RELATIONSHIPS
    // =========================================================================

    public function vendor(): BelongsTo
    {
        return $this->belongsTo(Vendor::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    public function orderItem(): BelongsTo
    {
        return $this->belongsTo(OrderItem::class);
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function payout(): BelongsTo
    {
        return $this->belongsTo(VendorPayout::class);
    }

    // =========================================================================
    // QUERY SCOPES
    // =========================================================================

    public function scopePending(Builder $query): void
    {
        $query->where('status', 'pending');
    }

    public function scopeApproved(Builder $query): void
    {
        $query->where('status', 'approved');
    }

    public function scopePaid(Builder $query): void
    {
        $query->where('status', 'paid');
    }

    public function scopeDisputed(Builder $query): void
    {
        $query->where('status', 'disputed');
    }

    public function scopeRefunded(Builder $query): void
    {
        $query->where('status', 'refunded');
    }

    public function scopeCancelled(Builder $query): void
    {
        $query->where('status', 'cancelled');
    }

    public function scopeForVendor(Builder $query, int $vendorId): void
    {
        $query->where('vendor_id', $vendorId);
    }

    public function scopeForOrder(Builder $query, int $orderId): void
    {
        $query->where('order_id', $orderId);
    }

    public function scopeForProduct(Builder $query, int $productId): void
    {
        $query->where('product_id', $productId);
    }

    public function scopeUnpaid(Builder $query): void
    {
        $query->whereIn('status', ['approved'])
            ->whereNull('payout_id');
    }

    public function scopeInPayout(Builder $query, int $payoutId): void
    {
        $query->where('payout_id', $payoutId);
    }

    public function scopeReadyForPayout(Builder $query): void
    {
        $query->where('status', 'approved')
            ->whereNull('payout_id');
    }

    public function scopeByDateRange(Builder $query, string $startDate, string $endDate): void
    {
        $query->whereBetween('created_at', [$startDate, $endDate]);
    }

    // =========================================================================
    // BUSINESS LOGIC METHODS
    // =========================================================================

    public function approve(): bool
    {
        return $this->update([
            'status' => 'approved',
            'approved_at' => now(),
        ]);
    }

    public function markAsPaid(int $payoutId): bool
    {
        return $this->update([
            'status' => 'paid',
            'payout_id' => $payoutId,
            'paid_at' => now(),
        ]);
    }

    public function dispute(string $reason): bool
    {
        return $this->update([
            'status' => 'disputed',
            'disputed_at' => now(),
            'dispute_reason' => $reason,
        ]);
    }

    public function resolveDispute(string $resolution): bool
    {
        return $this->update([
            'status' => 'approved',
            'dispute_resolution' => $resolution,
            'dispute_resolved_at' => now(),
        ]);
    }

    public function refund(float $amount = null): bool
    {
        $refundAmount = $amount ?? $this->vendor_earnings;

        return $this->update([
            'status' => 'refunded',
            'refunded_amount' => $refundAmount,
            'refunded_at' => now(),
        ]);
    }

    public function cancel(): bool
    {
        return $this->update([
            'status' => 'cancelled',
        ]);
    }

    public function isPending(): bool
    {
        return $this->status === 'pending';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isPaid(): bool
    {
        return $this->status === 'paid';
    }

    public function isDisputed(): bool
    {
        return $this->status === 'disputed';
    }

    public function isRefunded(): bool
    {
        return $this->status === 'refunded';
    }

    public function isCancelled(): bool
    {
        return $this->status === 'cancelled';
    }

    public function isReadyForPayout(): bool
    {
        return $this->status === 'approved' && is_null($this->payout_id);
    }

    public static function calculateFromOrderItem(
        Vendor $vendor,
        OrderItem $orderItem,
        ?float $commissionOverride = null
    ): array {
        $itemSubtotal = (float) $orderItem->price * $orderItem->quantity;
        $itemDiscount = (float) $orderItem->discount_amount;
        $itemTax = (float) $orderItem->tax_amount;
        $itemTotal = $itemSubtotal - $itemDiscount + $itemTax;

        // Determine commission rate
        $commissionRate = $commissionOverride ?? $vendor->commission_value;

        // Calculate commission amount
        $commissionType = $vendor->commission_type;
        if ($commissionType === 'flat') {
            $commissionAmount = (float) $commissionRate;
        } else {
            // Percentage or tiered (tiered is handled in vendor model)
            $commissionAmount = $vendor->calculateCommission($itemTotal);
        }

        // Platform fee (if any) - could be a percentage of commission or fixed
        $platformFee = 0; // Can be configured per marketplace

        // Vendor earnings = item total - commission - platform fee
        $vendorEarnings = $itemTotal - $commissionAmount - $platformFee;

        return [
            'item_subtotal' => $itemSubtotal,
            'item_discount' => $itemDiscount,
            'item_tax' => $itemTax,
            'item_total' => $itemTotal,
            'commission_type' => $commissionType,
            'commission_rate' => $commissionRate,
            'commission_amount' => $commissionAmount,
            'platform_fee' => $platformFee,
            'vendor_earnings' => $vendorEarnings,
        ];
    }
}
