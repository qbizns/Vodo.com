<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use VodoCommerce\Traits\BelongsToStore;

class CouponUsage extends Model
{
    use BelongsToStore, HasFactory;

    protected $table = 'commerce_coupon_usages';

    protected $fillable = [
        'store_id',
        'discount_id',
        'customer_id',
        'order_id',
        'session_id',
        'discount_code',
        'discount_amount',
        'order_subtotal',
        'applied_to_items',
        'ip_address',
        'user_agent',
    ];

    protected function casts(): array
    {
        return [
            'discount_amount' => 'float',
            'order_subtotal' => 'float',
            'applied_to_items' => 'array',
        ];
    }

    public function discount(): BelongsTo
    {
        return $this->belongsTo(Discount::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    public function order(): BelongsTo
    {
        return $this->belongsTo(Order::class);
    }

    /**
     * Scope to get usages for a specific discount.
     */
    public function scopeForDiscount($query, int $discountId)
    {
        return $query->where('discount_id', $discountId);
    }

    /**
     * Scope to get usages for a specific customer.
     */
    public function scopeForCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    /**
     * Scope to get recent usages within a time period.
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('created_at', '>=', now()->subDays($days));
    }

    /**
     * Get total discount amount saved by customer.
     */
    public static function getTotalSavings(?int $customerId = null): float
    {
        $query = static::query();

        if ($customerId) {
            $query->where('customer_id', $customerId);
        }

        return (float) $query->sum('discount_amount');
    }
}
