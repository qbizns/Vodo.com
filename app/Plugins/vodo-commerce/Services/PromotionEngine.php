<?php

declare(strict_types=1);

namespace VodoCommerce\Services;

use Illuminate\Support\Collection;
use VodoCommerce\Models\Discount;
use VodoCommerce\Models\PromotionRule;

class PromotionEngine
{
    /**
     * Find all automatic discounts applicable to the given context
     *
     * @param int $storeId
     * @param array $context Cart and customer context
     * @return Collection Collection of applicable Discount models
     */
    public function findAutomaticDiscounts(int $storeId, array $context): Collection
    {
        $automaticDiscounts = Discount::withoutGlobalScopes()
            ->where('store_id', $storeId)
            ->where('is_active', true)
            ->where('is_automatic', true)
            ->where(function ($query) {
                $query->whereNull('starts_at')
                    ->orWhere('starts_at', '<=', now());
            })
            ->where(function ($query) {
                $query->whereNull('expires_at')
                    ->orWhere('expires_at', '>=', now());
            })
            ->with('rules')
            ->byPriority()
            ->get();

        return $automaticDiscounts->filter(function (Discount $discount) use ($context) {
            return $this->evaluatePromotionRules($discount, $context);
        });
    }

    /**
     * Evaluate all promotion rules for a discount
     *
     * @param Discount $discount
     * @param array $context
     * @return bool True if all rules pass
     */
    public function evaluatePromotionRules(Discount $discount, array $context): bool
    {
        // If no rules exist, the discount is applicable
        if ($discount->rules->isEmpty()) {
            return true;
        }

        // All rules must pass (AND logic)
        foreach ($discount->rules as $rule) {
            if (!$rule->evaluate($context)) {
                return false;
            }
        }

        return true;
    }

    /**
     * Calculate discount for advanced promotion types (BOGO, tiered, etc.)
     *
     * @param Discount $discount
     * @param array $cartItems Array of cart items
     * @param float $subtotal
     * @return array ['amount' => float, 'details' => array]
     */
    public function calculateAdvancedDiscount(Discount $discount, array $cartItems, float $subtotal): array
    {
        if (!$discount->isAdvancedPromotion()) {
            // Fall back to standard discount calculation
            return [
                'amount' => $discount->calculateDiscount($subtotal),
                'details' => [
                    'type' => 'standard',
                    'applied_to' => 'cart',
                ],
            ];
        }

        return match ($discount->promotion_type) {
            Discount::PROMOTION_BUY_X_GET_Y => $this->calculateBuyXGetY($discount, $cartItems, $subtotal),
            Discount::PROMOTION_BUNDLE => $this->calculateBundleDiscount($discount, $cartItems, $subtotal),
            Discount::PROMOTION_TIERED => $this->calculateTieredDiscount($discount, $cartItems, $subtotal),
            Discount::PROMOTION_FREE_GIFT => $this->calculateFreeGift($discount, $cartItems, $subtotal),
            default => [
                'amount' => $discount->calculateDiscount($subtotal),
                'details' => [
                    'type' => 'standard',
                    'applied_to' => 'cart',
                ],
            ],
        };
    }

    /**
     * Calculate Buy X Get Y discount
     *
     * @param Discount $discount
     * @param array $cartItems
     * @param float $subtotal
     * @return array
     */
    protected function calculateBuyXGetY(Discount $discount, array $cartItems, float $subtotal): array
    {
        $config = $discount->target_config ?? [];
        $buyQuantity = $config['buy_quantity'] ?? 1;
        $getQuantity = $config['get_quantity'] ?? 1;
        $getDiscountPercent = $config['get_discount_percent'] ?? 100;
        $maxApplications = $config['max_applications'] ?? null;

        $applicableItems = $this->filterApplicableItems($discount, $cartItems);

        if (empty($applicableItems)) {
            return ['amount' => 0, 'details' => ['type' => 'buy_x_get_y', 'applied_items' => []]];
        }

        // Sort items by price (cheapest first for "get" items)
        usort($applicableItems, fn($a, $b) => ($a['price'] ?? 0) <=> ($b['price'] ?? 0));

        $totalQuantity = array_sum(array_column($applicableItems, 'quantity'));
        $setsQualified = (int) floor($totalQuantity / ($buyQuantity + $getQuantity));

        if ($maxApplications !== null) {
            $setsQualified = min($setsQualified, $maxApplications);
        }

        if ($setsQualified === 0) {
            return ['amount' => 0, 'details' => ['type' => 'buy_x_get_y', 'applied_items' => []]];
        }

        // Calculate discount on the cheapest "get" items
        $discountAmount = 0;
        $appliedItems = [];
        $remainingGetQuantity = $setsQualified * $getQuantity;

        foreach ($applicableItems as $item) {
            if ($remainingGetQuantity <= 0) {
                break;
            }

            $itemGetQuantity = min($remainingGetQuantity, $item['quantity'] ?? 0);
            $itemDiscount = ($item['price'] ?? 0) * $itemGetQuantity * ($getDiscountPercent / 100);

            $discountAmount += $itemDiscount;
            $appliedItems[] = [
                'product_id' => $item['product_id'] ?? null,
                'quantity' => $itemGetQuantity,
                'discount' => $itemDiscount,
            ];

            $remainingGetQuantity -= $itemGetQuantity;
        }

        return [
            'amount' => $discountAmount,
            'details' => [
                'type' => 'buy_x_get_y',
                'sets_qualified' => $setsQualified,
                'applied_items' => $appliedItems,
            ],
        ];
    }

    /**
     * Calculate bundle discount
     *
     * @param Discount $discount
     * @param array $cartItems
     * @param float $subtotal
     * @return array
     */
    protected function calculateBundleDiscount(Discount $discount, array $cartItems, float $subtotal): array
    {
        $config = $discount->target_config ?? [];
        $requiredProducts = $config['required_products'] ?? [];

        if (empty($requiredProducts)) {
            return ['amount' => 0, 'details' => ['type' => 'bundle', 'bundle_complete' => false]];
        }

        // Check if all required products are in cart
        $cartProductIds = array_column($cartItems, 'product_id');
        $hasAllProducts = empty(array_diff($requiredProducts, $cartProductIds));

        if (!$hasAllProducts) {
            return ['amount' => 0, 'details' => ['type' => 'bundle', 'bundle_complete' => false]];
        }

        // Calculate bundle discount
        $bundleTotal = 0;
        $appliedItems = [];

        foreach ($cartItems as $item) {
            if (in_array($item['product_id'] ?? null, $requiredProducts)) {
                $bundleTotal += ($item['price'] ?? 0) * ($item['quantity'] ?? 0);
                $appliedItems[] = [
                    'product_id' => $item['product_id'] ?? null,
                    'quantity' => $item['quantity'] ?? 0,
                ];
            }
        }

        $discountAmount = $discount->calculateDiscount($bundleTotal);

        return [
            'amount' => $discountAmount,
            'details' => [
                'type' => 'bundle',
                'bundle_complete' => true,
                'bundle_total' => $bundleTotal,
                'applied_items' => $appliedItems,
            ],
        ];
    }

    /**
     * Calculate tiered discount
     *
     * @param Discount $discount
     * @param array $cartItems
     * @param float $subtotal
     * @return array
     */
    protected function calculateTieredDiscount(Discount $discount, array $cartItems, float $subtotal): array
    {
        $config = $discount->target_config ?? [];
        $tiers = $config['tiers'] ?? [];

        if (empty($tiers)) {
            return ['amount' => 0, 'details' => ['type' => 'tiered', 'tier_reached' => null]];
        }

        // Sort tiers by threshold descending to find the highest applicable tier
        usort($tiers, fn($a, $b) => ($b['threshold'] ?? 0) <=> ($a['threshold'] ?? 0));

        $applicableItems = $this->filterApplicableItems($discount, $cartItems);
        $applicableTotal = array_sum(array_map(
            fn($item) => ($item['price'] ?? 0) * ($item['quantity'] ?? 0),
            $applicableItems
        ));

        foreach ($tiers as $tier) {
            $threshold = $tier['threshold'] ?? 0;
            if ($applicableTotal >= $threshold) {
                $discountPercent = $tier['discount_percent'] ?? 0;
                $discountAmount = $applicableTotal * ($discountPercent / 100);

                return [
                    'amount' => $discountAmount,
                    'details' => [
                        'type' => 'tiered',
                        'tier_reached' => $tier,
                        'applicable_total' => $applicableTotal,
                    ],
                ];
            }
        }

        return ['amount' => 0, 'details' => ['type' => 'tiered', 'tier_reached' => null]];
    }

    /**
     * Calculate free gift promotion (informational, no discount amount)
     *
     * @param Discount $discount
     * @param array $cartItems
     * @param float $subtotal
     * @return array
     */
    protected function calculateFreeGift(Discount $discount, array $cartItems, float $subtotal): array
    {
        $config = $discount->target_config ?? [];
        $freeProductIds = $config['free_product_ids'] ?? [];
        $minimumPurchase = $config['minimum_purchase'] ?? 0;

        if ($subtotal < $minimumPurchase) {
            return [
                'amount' => 0,
                'details' => [
                    'type' => 'free_gift',
                    'qualified' => false,
                    'free_product_ids' => $freeProductIds,
                ],
            ];
        }

        return [
            'amount' => 0, // Free gift doesn't reduce cart total directly
            'details' => [
                'type' => 'free_gift',
                'qualified' => true,
                'free_product_ids' => $freeProductIds,
                'message' => $discount->display_message ?? 'You qualify for a free gift!',
            ],
        ];
    }

    /**
     * Filter cart items applicable to the discount based on applies_to settings
     *
     * @param Discount $discount
     * @param array $cartItems
     * @return array
     */
    protected function filterApplicableItems(Discount $discount, array $cartItems): array
    {
        if ($discount->applies_to === Discount::APPLIES_TO_ALL) {
            return $cartItems;
        }

        $productIds = $this->extractProductIds($discount);

        if (empty($productIds)) {
            return $cartItems;
        }

        return array_filter($cartItems, function ($item) use ($discount, $productIds) {
            $itemProductId = $item['product_id'] ?? null;

            return $discount->appliesToProducts([$itemProductId]);
        });
    }

    /**
     * Extract product IDs from discount configuration
     *
     * @param Discount $discount
     * @return array
     */
    protected function extractProductIds(Discount $discount): array
    {
        $productIds = [];

        if ($discount->applies_to === Discount::APPLIES_TO_SPECIFIC_PRODUCTS) {
            $productIds = $discount->included_product_ids ?? [];
        }

        // Note: Category and brand filtering would require product catalog access
        // This is a simplified version

        return $productIds;
    }

    /**
     * Apply stacking logic to multiple discounts
     *
     * @param Collection $discounts Collection of Discount models
     * @param array $cartItems
     * @param float $subtotal
     * @return array ['total_discount' => float, 'applied_discounts' => array]
     */
    public function applyStackingLogic(Collection $discounts, array $cartItems, float $subtotal): array
    {
        $totalDiscount = 0;
        $appliedDiscounts = [];

        // Discounts are already sorted by priority (via byPriority scope)
        foreach ($discounts as $discount) {
            if (!$discount->is_stackable && !empty($appliedDiscounts)) {
                // Non-stackable discount, skip if we already have discounts applied
                continue;
            }

            $result = $this->calculateAdvancedDiscount($discount, $cartItems, $subtotal);
            $discountAmount = $result['amount'] ?? 0;

            if ($discountAmount > 0) {
                $totalDiscount += $discountAmount;
                $appliedDiscounts[] = [
                    'discount_id' => $discount->id,
                    'code' => $discount->code,
                    'amount' => $discountAmount,
                    'details' => $result['details'] ?? [],
                ];

                if ($discount->stop_further_rules) {
                    // Stop processing further discounts
                    break;
                }
            }
        }

        return [
            'total_discount' => $totalDiscount,
            'applied_discounts' => $appliedDiscounts,
        ];
    }
}
