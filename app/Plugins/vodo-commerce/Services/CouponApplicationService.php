<?php

declare(strict_types=1);

namespace VodoCommerce\Services;

use Illuminate\Support\Facades\DB;
use VodoCommerce\Models\Cart;
use VodoCommerce\Models\CouponUsage;
use VodoCommerce\Models\Discount;

class CouponApplicationService
{
    public function __construct(
        protected PromotionEngine $promotionEngine
    ) {
    }

    /**
     * Validate a coupon code for a cart
     *
     * @param string $code
     * @param Cart $cart
     * @param int|null $customerId
     * @return array ['valid' => bool, 'message' => string, 'discount' => Discount|null]
     */
    public function validateCoupon(string $code, Cart $cart, ?int $customerId = null): array
    {
        $discount = Discount::withoutGlobalScopes()
            ->where('store_id', $cart->store_id)
            ->where('code', $code)
            ->with('rules')
            ->first();

        if (!$discount) {
            return [
                'valid' => false,
                'message' => 'Coupon code not found.',
                'discount' => null,
            ];
        }

        // Check if discount is valid (active, dates, usage limits)
        if (!$discount->isValid()) {
            return [
                'valid' => false,
                'message' => 'This coupon is no longer valid.',
                'discount' => null,
            ];
        }

        // Check if applicable to cart subtotal
        if (!$discount->isApplicable($cart->subtotal, $customerId)) {
            $minOrder = $discount->minimum_order;
            if ($minOrder && $cart->subtotal < $minOrder) {
                return [
                    'valid' => false,
                    'message' => "Minimum order amount of {$minOrder} required.",
                    'discount' => null,
                ];
            }

            return [
                'valid' => false,
                'message' => 'This coupon cannot be applied to your cart.',
                'discount' => null,
            ];
        }

        // Check customer eligibility for advanced promotions
        if ($discount->customer_eligibility && $discount->customer_eligibility !== Discount::ELIGIBILITY_ALL) {
            $customerGroupIds = $this->getCustomerGroupIds($customerId);

            if (!$discount->isCustomerEligible($customerId, $customerGroupIds)) {
                return [
                    'valid' => false,
                    'message' => 'You are not eligible for this promotion.',
                    'discount' => null,
                ];
            }
        }

        // Check first order requirement
        if ($discount->first_order_only && !$discount->requiresFirstOrder($customerId)) {
            return [
                'valid' => false,
                'message' => 'This coupon is only valid for first-time customers.',
                'discount' => null,
            ];
        }

        // Check product/category/brand applicability
        if ($discount->applies_to !== Discount::APPLIES_TO_ALL) {
            $cartProductIds = $this->getCartProductIds($cart);

            if (!$discount->appliesToProducts($cartProductIds)) {
                return [
                    'valid' => false,
                    'message' => 'This coupon does not apply to items in your cart.',
                    'discount' => null,
                ];
            }
        }

        // Evaluate promotion rules
        $context = $this->buildPromotionContext($cart, $customerId);

        if (!$this->promotionEngine->evaluatePromotionRules($discount, $context)) {
            return [
                'valid' => false,
                'message' => 'Promotion requirements not met.',
                'discount' => null,
            ];
        }

        return [
            'valid' => true,
            'message' => $discount->display_message ?? 'Coupon applied successfully!',
            'discount' => $discount,
        ];
    }

    /**
     * Apply a coupon code to a cart
     *
     * @param string $code
     * @param Cart $cart
     * @param int|null $customerId
     * @return array
     */
    public function applyCoupon(string $code, Cart $cart, ?int $customerId = null): array
    {
        $validation = $this->validateCoupon($code, $cart, $customerId);

        if (!$validation['valid']) {
            return $validation;
        }

        $discount = $validation['discount'];

        // Check if coupon is already applied
        $discountCodes = $cart->discount_codes ?? [];

        if (in_array($code, $discountCodes)) {
            return [
                'valid' => false,
                'message' => 'This coupon is already applied.',
                'discount' => null,
            ];
        }

        // Check stacking rules
        if (!$discount->is_stackable && !empty($discountCodes)) {
            return [
                'valid' => false,
                'message' => 'This coupon cannot be combined with other discounts.',
                'discount' => null,
            ];
        }

        // Add coupon to cart
        $discountCodes[] = $code;
        $cart->discount_codes = $discountCodes;
        $cart->save();

        // Recalculate cart totals
        $cart->recalculate();

        return [
            'valid' => true,
            'message' => $validation['message'],
            'discount' => $discount,
            'cart' => $cart->fresh(),
        ];
    }

    /**
     * Remove a coupon code from a cart
     *
     * @param string $code
     * @param Cart $cart
     * @return array
     */
    public function removeCoupon(string $code, Cart $cart): array
    {
        $discountCodes = $cart->discount_codes ?? [];

        if (!in_array($code, $discountCodes)) {
            return [
                'valid' => false,
                'message' => 'Coupon not found in cart.',
            ];
        }

        $cart->discount_codes = array_values(array_filter($discountCodes, fn($c) => $c !== $code));
        $cart->save();

        // Recalculate cart totals
        $cart->recalculate();

        return [
            'valid' => true,
            'message' => 'Coupon removed successfully.',
            'cart' => $cart->fresh(),
        ];
    }

    /**
     * Record coupon usage after order completion
     *
     * @param int $orderId
     * @param int $storeId
     * @param int|null $customerId
     * @param array $discountCodes
     * @param float $subtotal
     * @param array $cartItems
     * @return void
     */
    public function recordUsage(
        int $orderId,
        int $storeId,
        ?int $customerId,
        array $discountCodes,
        float $subtotal,
        array $cartItems
    ): void {
        foreach ($discountCodes as $code) {
            $discount = Discount::withoutGlobalScopes()
                ->where('store_id', $storeId)
                ->where('code', $code)
                ->first();

            if (!$discount) {
                continue;
            }

            // Calculate discount amount for this specific code
            $context = [
                'cart' => [
                    'subtotal' => $subtotal,
                    'quantity' => array_sum(array_column($cartItems, 'quantity')),
                    'items' => $cartItems,
                ],
            ];

            $result = $this->promotionEngine->calculateAdvancedDiscount($discount, $cartItems, $subtotal);

            // Record usage
            CouponUsage::create([
                'store_id' => $storeId,
                'discount_id' => $discount->id,
                'customer_id' => $customerId,
                'order_id' => $orderId,
                'session_id' => session()->getId(),
                'discount_code' => $code,
                'discount_amount' => $result['amount'] ?? 0,
                'order_subtotal' => $subtotal,
                'applied_to_items' => $result['details']['applied_items'] ?? [],
                'ip_address' => request()->ip(),
                'user_agent' => request()->userAgent(),
            ]);

            // Increment usage count on discount
            $discount->incrementUsage();
        }
    }

    /**
     * Get all automatic discounts for a cart
     *
     * @param Cart $cart
     * @param int|null $customerId
     * @return array
     */
    public function getAutomaticDiscounts(Cart $cart, ?int $customerId = null): array
    {
        $context = $this->buildPromotionContext($cart, $customerId);

        $automaticDiscounts = $this->promotionEngine->findAutomaticDiscounts($cart->store_id, $context);

        $applicableDiscounts = [];

        foreach ($automaticDiscounts as $discount) {
            // Double-check eligibility
            if ($discount->customer_eligibility && $discount->customer_eligibility !== Discount::ELIGIBILITY_ALL) {
                $customerGroupIds = $this->getCustomerGroupIds($customerId);

                if (!$discount->isCustomerEligible($customerId, $customerGroupIds)) {
                    continue;
                }
            }

            if ($discount->first_order_only && !$discount->requiresFirstOrder($customerId)) {
                continue;
            }

            $cartItems = $this->getCartItems($cart);
            $result = $this->promotionEngine->calculateAdvancedDiscount($discount, $cartItems, $cart->subtotal);

            if (($result['amount'] ?? 0) > 0) {
                $applicableDiscounts[] = [
                    'discount' => $discount,
                    'amount' => $result['amount'],
                    'details' => $result['details'] ?? [],
                    'message' => $discount->display_message,
                    'badge' => [
                        'text' => $discount->badge_text,
                        'color' => $discount->badge_color,
                    ],
                ];
            }
        }

        return $applicableDiscounts;
    }

    /**
     * Build promotion context from cart and customer data
     *
     * @param Cart $cart
     * @param int|null $customerId
     * @return array
     */
    protected function buildPromotionContext(Cart $cart, ?int $customerId = null): array
    {
        $cartItems = $this->getCartItems($cart);

        return [
            'cart' => [
                'subtotal' => $cart->subtotal,
                'quantity' => array_sum(array_column($cartItems, 'quantity')),
                'items' => $cartItems,
            ],
            'customer' => [
                'id' => $customerId,
                'group_ids' => $this->getCustomerGroupIds($customerId),
                'total_orders' => $this->getCustomerTotalOrders($customerId),
                'lifetime_value' => $this->getCustomerLifetimeValue($customerId),
            ],
            'shipping' => $cart->shipping_address ?? [],
            'payment' => [
                'method' => null, // To be set during checkout
            ],
            'datetime' => [
                'day_of_week' => now()->dayOfWeek,
                'time_of_day' => now()->format('H:i'),
            ],
        ];
    }

    /**
     * Get cart items as array
     *
     * @param Cart $cart
     * @return array
     */
    protected function getCartItems(Cart $cart): array
    {
        return $cart->items()->with('product')->get()->map(function ($item) {
            return [
                'product_id' => $item->product_id,
                'quantity' => $item->quantity,
                'price' => $item->price,
                'category_ids' => $item->product->category_ids ?? [],
                'brand_id' => $item->product->brand_id ?? null,
            ];
        })->toArray();
    }

    /**
     * Get product IDs from cart
     *
     * @param Cart $cart
     * @return array
     */
    protected function getCartProductIds(Cart $cart): array
    {
        return $cart->items()->pluck('product_id')->toArray();
    }

    /**
     * Get customer group IDs (placeholder - implement based on your customer system)
     *
     * @param int|null $customerId
     * @return array
     */
    protected function getCustomerGroupIds(?int $customerId): array
    {
        if (!$customerId) {
            return [];
        }

        // TODO: Implement customer group logic when customer groups are available
        return [];
    }

    /**
     * Get customer total orders (placeholder - implement based on your order system)
     *
     * @param int|null $customerId
     * @return int
     */
    protected function getCustomerTotalOrders(?int $customerId): int
    {
        if (!$customerId) {
            return 0;
        }

        // TODO: Implement order count logic
        return 0;
    }

    /**
     * Get customer lifetime value (placeholder - implement based on your order system)
     *
     * @param int|null $customerId
     * @return float
     */
    protected function getCustomerLifetimeValue(?int $customerId): float
    {
        if (!$customerId) {
            return 0.0;
        }

        // TODO: Implement lifetime value calculation
        return 0.0;
    }
}
