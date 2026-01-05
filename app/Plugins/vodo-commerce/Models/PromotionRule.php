<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use VodoCommerce\Traits\BelongsToStore;

class PromotionRule extends Model
{
    use BelongsToStore, HasFactory;

    protected $table = 'commerce_promotion_rules';

    // Rule types
    public const RULE_CART_SUBTOTAL = 'cart_subtotal';
    public const RULE_CART_QUANTITY = 'cart_quantity';
    public const RULE_PRODUCT_QUANTITY = 'product_quantity';
    public const RULE_CATEGORY_QUANTITY = 'category_quantity';
    public const RULE_BRAND_QUANTITY = 'brand_quantity';
    public const RULE_CUSTOMER_GROUP = 'customer_group';
    public const RULE_SHIPPING_COUNTRY = 'shipping_country';
    public const RULE_SHIPPING_STATE = 'shipping_state';
    public const RULE_SHIPPING_CITY = 'shipping_city';
    public const RULE_PAYMENT_METHOD = 'payment_method';
    public const RULE_CUSTOMER_TOTAL_ORDERS = 'customer_total_orders';
    public const RULE_CUSTOMER_LIFETIME_VALUE = 'customer_lifetime_value';
    public const RULE_DAY_OF_WEEK = 'day_of_week';
    public const RULE_TIME_OF_DAY = 'time_of_day';

    // Operators
    public const OPERATOR_EQUALS = 'equals';
    public const OPERATOR_NOT_EQUALS = 'not_equals';
    public const OPERATOR_GREATER_THAN = 'greater_than';
    public const OPERATOR_GREATER_THAN_OR_EQUAL = 'greater_than_or_equal';
    public const OPERATOR_LESS_THAN = 'less_than';
    public const OPERATOR_LESS_THAN_OR_EQUAL = 'less_than_or_equal';
    public const OPERATOR_CONTAINS = 'contains';
    public const OPERATOR_NOT_CONTAINS = 'not_contains';
    public const OPERATOR_IN = 'in';
    public const OPERATOR_NOT_IN = 'not_in';
    public const OPERATOR_BETWEEN = 'between';

    protected $fillable = [
        'store_id',
        'discount_id',
        'rule_type',
        'operator',
        'value',
        'metadata',
        'position',
    ];

    protected function casts(): array
    {
        return [
            'value' => 'string',
            'metadata' => 'array',
            'position' => 'integer',
        ];
    }

    public function discount(): BelongsTo
    {
        return $this->belongsTo(Discount::class);
    }

    /**
     * Evaluate this rule against the provided context
     *
     * @param array $context Context data (cart, customer, shipping, etc.)
     * @return bool
     */
    public function evaluate(array $context): bool
    {
        $contextValue = $this->extractContextValue($context);

        if ($contextValue === null) {
            return false;
        }

        return match ($this->operator) {
            self::OPERATOR_EQUALS => $this->evaluateEquals($contextValue),
            self::OPERATOR_NOT_EQUALS => !$this->evaluateEquals($contextValue),
            self::OPERATOR_GREATER_THAN => $this->evaluateGreaterThan($contextValue),
            self::OPERATOR_GREATER_THAN_OR_EQUAL => $this->evaluateGreaterThanOrEqual($contextValue),
            self::OPERATOR_LESS_THAN => $this->evaluateLessThan($contextValue),
            self::OPERATOR_LESS_THAN_OR_EQUAL => $this->evaluateLessThanOrEqual($contextValue),
            self::OPERATOR_CONTAINS => $this->evaluateContains($contextValue),
            self::OPERATOR_NOT_CONTAINS => !$this->evaluateContains($contextValue),
            self::OPERATOR_IN => $this->evaluateIn($contextValue),
            self::OPERATOR_NOT_IN => !$this->evaluateIn($contextValue),
            self::OPERATOR_BETWEEN => $this->evaluateBetween($contextValue),
            default => false,
        };
    }

    /**
     * Extract the relevant value from context based on rule type
     *
     * @param array $context
     * @return mixed
     */
    protected function extractContextValue(array $context): mixed
    {
        return match ($this->rule_type) {
            self::RULE_CART_SUBTOTAL => $context['cart']['subtotal'] ?? null,
            self::RULE_CART_QUANTITY => $context['cart']['quantity'] ?? null,
            self::RULE_PRODUCT_QUANTITY => $this->getProductQuantity($context),
            self::RULE_CATEGORY_QUANTITY => $this->getCategoryQuantity($context),
            self::RULE_BRAND_QUANTITY => $this->getBrandQuantity($context),
            self::RULE_CUSTOMER_GROUP => $context['customer']['group_ids'] ?? null,
            self::RULE_SHIPPING_COUNTRY => $context['shipping']['country'] ?? null,
            self::RULE_SHIPPING_STATE => $context['shipping']['state'] ?? null,
            self::RULE_SHIPPING_CITY => $context['shipping']['city'] ?? null,
            self::RULE_PAYMENT_METHOD => $context['payment']['method'] ?? null,
            self::RULE_CUSTOMER_TOTAL_ORDERS => $context['customer']['total_orders'] ?? null,
            self::RULE_CUSTOMER_LIFETIME_VALUE => $context['customer']['lifetime_value'] ?? null,
            self::RULE_DAY_OF_WEEK => $context['datetime']['day_of_week'] ?? now()->dayOfWeek,
            self::RULE_TIME_OF_DAY => $context['datetime']['time_of_day'] ?? now()->format('H:i'),
            default => null,
        };
    }

    protected function getProductQuantity(array $context): ?int
    {
        $productId = $this->metadata['product_id'] ?? null;
        if (!$productId || !isset($context['cart']['items'])) {
            return null;
        }

        $quantity = 0;
        foreach ($context['cart']['items'] as $item) {
            if (($item['product_id'] ?? null) == $productId) {
                $quantity += $item['quantity'] ?? 0;
            }
        }

        return $quantity;
    }

    protected function getCategoryQuantity(array $context): ?int
    {
        $categoryId = $this->metadata['category_id'] ?? null;
        if (!$categoryId || !isset($context['cart']['items'])) {
            return null;
        }

        $quantity = 0;
        foreach ($context['cart']['items'] as $item) {
            $categories = $item['category_ids'] ?? [];
            if (in_array($categoryId, $categories)) {
                $quantity += $item['quantity'] ?? 0;
            }
        }

        return $quantity;
    }

    protected function getBrandQuantity(array $context): ?int
    {
        $brandId = $this->metadata['brand_id'] ?? null;
        if (!$brandId || !isset($context['cart']['items'])) {
            return null;
        }

        $quantity = 0;
        foreach ($context['cart']['items'] as $item) {
            if (($item['brand_id'] ?? null) == $brandId) {
                $quantity += $item['quantity'] ?? 0;
            }
        }

        return $quantity;
    }

    protected function evaluateEquals(mixed $contextValue): bool
    {
        return $contextValue == $this->value;
    }

    protected function evaluateGreaterThan(mixed $contextValue): bool
    {
        return is_numeric($contextValue) && is_numeric($this->value) && $contextValue > $this->value;
    }

    protected function evaluateGreaterThanOrEqual(mixed $contextValue): bool
    {
        return is_numeric($contextValue) && is_numeric($this->value) && $contextValue >= $this->value;
    }

    protected function evaluateLessThan(mixed $contextValue): bool
    {
        return is_numeric($contextValue) && is_numeric($this->value) && $contextValue < $this->value;
    }

    protected function evaluateLessThanOrEqual(mixed $contextValue): bool
    {
        return is_numeric($contextValue) && is_numeric($this->value) && $contextValue <= $this->value;
    }

    protected function evaluateContains(mixed $contextValue): bool
    {
        if (is_string($contextValue)) {
            return str_contains($contextValue, (string) $this->value);
        }

        if (is_array($contextValue)) {
            return in_array($this->value, $contextValue);
        }

        return false;
    }

    protected function evaluateIn(mixed $contextValue): bool
    {
        $allowedValues = is_array($this->value) ? $this->value : explode(',', (string) $this->value);

        return in_array($contextValue, $allowedValues);
    }

    protected function evaluateBetween(mixed $contextValue): bool
    {
        if (!is_numeric($contextValue)) {
            return false;
        }

        $range = is_array($this->value) ? $this->value : explode(',', (string) $this->value);

        if (count($range) !== 2) {
            return false;
        }

        [$min, $max] = $range;

        return is_numeric($min) && is_numeric($max) && $contextValue >= $min && $contextValue <= $max;
    }

    /**
     * Scope to get rules ordered by position
     */
    public function scopeOrdered($query)
    {
        return $query->orderBy('position');
    }

    /**
     * Scope to get rules for a specific discount
     */
    public function scopeForDiscount($query, int $discountId)
    {
        return $query->where('discount_id', $discountId);
    }
}
