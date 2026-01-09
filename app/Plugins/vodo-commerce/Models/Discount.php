<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use VodoCommerce\Traits\BelongsToStore;

class Discount extends Model
{
    use BelongsToStore, HasFactory, SoftDeletes;

    protected $table = 'commerce_discounts';

    public const TYPE_PERCENTAGE = 'percentage';
    public const TYPE_FIXED_AMOUNT = 'fixed_amount';
    public const TYPE_FREE_SHIPPING = 'free_shipping';

    // Phase 4.2: Advanced promotion types
    public const PROMOTION_BUY_X_GET_Y = 'buy_x_get_y';
    public const PROMOTION_BUNDLE = 'bundle';
    public const PROMOTION_TIERED = 'tiered';
    public const PROMOTION_FREE_GIFT = 'free_gift';

    // Applies to options
    public const APPLIES_TO_ALL = 'all';
    public const APPLIES_TO_SPECIFIC_PRODUCTS = 'specific_products';
    public const APPLIES_TO_SPECIFIC_CATEGORIES = 'specific_categories';
    public const APPLIES_TO_SPECIFIC_BRANDS = 'specific_brands';

    // Customer eligibility options
    public const ELIGIBILITY_ALL = 'all';
    public const ELIGIBILITY_NEW_CUSTOMERS = 'new_customers_only';
    public const ELIGIBILITY_SPECIFIC_GROUPS = 'specific_groups';
    public const ELIGIBILITY_SPECIFIC_CUSTOMERS = 'specific_customers';

    protected $fillable = [
        'store_id',
        'code',
        'name',
        'description',
        'type',
        'value',
        'minimum_order',
        'maximum_discount',
        'usage_limit',
        'usage_count',
        'per_customer_limit',
        'starts_at',
        'expires_at',
        'is_active',
        'conditions',
        // Phase 4.2: Advanced promotion fields
        'applies_to',
        'promotion_type',
        'target_config',
        'included_product_ids',
        'excluded_product_ids',
        'included_category_ids',
        'excluded_category_ids',
        'included_brand_ids',
        'customer_eligibility',
        'allowed_customer_group_ids',
        'allowed_customer_ids',
        'first_order_only',
        'min_items_quantity',
        'max_items_quantity',
        'is_stackable',
        'priority',
        'is_automatic',
        'stop_further_rules',
        'free_shipping_applies_to',
        'display_message',
        'badge_text',
        'badge_color',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'float',
            'minimum_order' => 'float',
            'maximum_discount' => 'float',
            'usage_limit' => 'integer',
            'usage_count' => 'integer',
            'per_customer_limit' => 'integer',
            'starts_at' => 'datetime',
            'expires_at' => 'datetime',
            'is_active' => 'boolean',
            'conditions' => 'array',
            // Phase 4.2: Advanced promotion casts
            'target_config' => 'array',
            'included_product_ids' => 'array',
            'excluded_product_ids' => 'array',
            'included_category_ids' => 'array',
            'excluded_category_ids' => 'array',
            'included_brand_ids' => 'array',
            'allowed_customer_group_ids' => 'array',
            'allowed_customer_ids' => 'array',
            'first_order_only' => 'boolean',
            'min_items_quantity' => 'integer',
            'max_items_quantity' => 'integer',
            'is_stackable' => 'boolean',
            'priority' => 'integer',
            'is_automatic' => 'boolean',
            'stop_further_rules' => 'boolean',
        ];
    }

    public function isValid(): bool
    {
        if (!$this->is_active) {
            return false;
        }

        if ($this->starts_at && $this->starts_at->isFuture()) {
            return false;
        }

        if ($this->expires_at && $this->expires_at->isPast()) {
            return false;
        }

        if ($this->usage_limit && $this->usage_count >= $this->usage_limit) {
            return false;
        }

        return true;
    }

    public function isApplicable(float $orderTotal, ?int $customerId = null): bool
    {
        if (!$this->isValid()) {
            return false;
        }

        if ($this->minimum_order && $orderTotal < $this->minimum_order) {
            return false;
        }

        // Check per-customer limit if customer is provided
        if ($customerId && $this->per_customer_limit) {
            $customerUsage = Order::where('customer_id', $customerId)
                ->whereJsonContains('discount_codes', $this->code)
                ->count();

            if ($customerUsage >= $this->per_customer_limit) {
                return false;
            }
        }

        return true;
    }

    public function calculateDiscount(float $orderTotal): float
    {
        if (!$this->isApplicable($orderTotal)) {
            return 0;
        }

        $discount = match ($this->type) {
            self::TYPE_PERCENTAGE => $orderTotal * ($this->value / 100),
            self::TYPE_FIXED_AMOUNT => $this->value,
            self::TYPE_FREE_SHIPPING => 0, // Handled separately
            default => 0,
        };

        // Apply maximum discount cap
        if ($this->maximum_discount && $discount > $this->maximum_discount) {
            $discount = $this->maximum_discount;
        }

        // Don't exceed order total
        return min($discount, $orderTotal);
    }

    /**
     * Atomically increment usage count with limit check.
     * Returns true if increment was successful, false if limit reached.
     */
    public function incrementUsage(): bool
    {
        $affected = static::where('id', $this->id)
            ->where(function ($query) {
                $query->whereNull('usage_limit')
                    ->orWhereRaw('usage_count < usage_limit');
            })
            ->update(['usage_count' => \Illuminate\Support\Facades\DB::raw('usage_count + 1')]);

        if ($affected > 0) {
            $this->refresh();
            return true;
        }

        return false;
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeValid($query)
    {
        return $query->active()
            ->where(function ($q) {
                $q->whereNull('starts_at')->orWhere('starts_at', '<=', now());
            })
            ->where(function ($q) {
                $q->whereNull('expires_at')->orWhere('expires_at', '>=', now());
            })
            ->where(function ($q) {
                $q->whereNull('usage_limit')
                    ->orWhereRaw('usage_count < usage_limit');
            });
    }

    // ========================================================================
    // Phase 4.2: New Relationships
    // ========================================================================

    public function usages(): HasMany
    {
        return $this->hasMany(CouponUsage::class, 'discount_id');
    }

    public function rules(): HasMany
    {
        return $this->hasMany(PromotionRule::class, 'discount_id');
    }

    // ========================================================================
    // Phase 4.2: New Methods (Backward Compatible)
    // ========================================================================

    /**
     * Check if this is an advanced promotion (BOGO, tiered, etc.).
     */
    public function isAdvancedPromotion(): bool
    {
        return !empty($this->promotion_type);
    }

    /**
     * Check if discount applies to specific products.
     */
    public function appliesToProducts(array $productIds): bool
    {
        if ($this->applies_to === self::APPLIES_TO_ALL) {
            return true;
        }

        if ($this->applies_to === self::APPLIES_TO_SPECIFIC_PRODUCTS) {
            if (empty($this->included_product_ids)) {
                return true;
            }

            return !empty(array_intersect($productIds, $this->included_product_ids));
        }

        return true;
    }

    /**
     * Check if customer is eligible for this discount.
     */
    public function isCustomerEligible(?int $customerId = null, ?array $customerGroupIds = []): bool
    {
        if ($this->customer_eligibility === self::ELIGIBILITY_ALL) {
            return true;
        }

        if ($this->customer_eligibility === self::ELIGIBILITY_NEW_CUSTOMERS) {
            if (!$customerId) {
                return false;
            }

            // Check if customer has any previous orders
            $hasOrders = Order::where('customer_id', $customerId)->exists();

            return !$hasOrders;
        }

        if ($this->customer_eligibility === self::ELIGIBILITY_SPECIFIC_CUSTOMERS) {
            if (!$customerId || empty($this->allowed_customer_ids)) {
                return false;
            }

            return in_array($customerId, $this->allowed_customer_ids);
        }

        if ($this->customer_eligibility === self::ELIGIBILITY_SPECIFIC_GROUPS) {
            if (empty($customerGroupIds) || empty($this->allowed_customer_group_ids)) {
                return false;
            }

            return !empty(array_intersect($customerGroupIds, $this->allowed_customer_group_ids));
        }

        return true;
    }

    /**
     * Check if this is the customer's first order.
     */
    public function requiresFirstOrder(?int $customerId = null): bool
    {
        if (!$this->first_order_only || !$customerId) {
            return false;
        }

        $orderCount = Order::where('customer_id', $customerId)->count();

        return $orderCount === 0;
    }

    /**
     * Get customer usage count for this discount.
     */
    public function getCustomerUsageCount(?int $customerId = null): int
    {
        if (!$customerId) {
            return 0;
        }

        return $this->usages()->where('customer_id', $customerId)->count();
    }

    /**
     * Check if customer has exceeded usage limit.
     */
    public function hasCustomerExceededLimit(?int $customerId = null): bool
    {
        if (!$this->per_customer_limit || !$customerId) {
            return false;
        }

        return $this->getCustomerUsageCount($customerId) >= $this->per_customer_limit;
    }

    /**
     * Additional scope for automatic discounts.
     */
    public function scopeAutomatic($query)
    {
        return $query->where('is_automatic', true);
    }

    /**
     * Scope for discounts ordered by priority.
     */
    public function scopeByPriority($query)
    {
        return $query->orderBy('priority', 'desc');
    }
}
