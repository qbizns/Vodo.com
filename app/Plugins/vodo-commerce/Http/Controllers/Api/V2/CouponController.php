<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use VodoCommerce\Http\Controllers\Controller;
use VodoCommerce\Http\Requests\ApplyCouponRequest;
use VodoCommerce\Http\Requests\RemoveCouponRequest;
use VodoCommerce\Http\Requests\ValidateCouponRequest;
use VodoCommerce\Http\Resources\DiscountResource;
use VodoCommerce\Models\Cart;
use VodoCommerce\Models\Store;
use VodoCommerce\Services\CouponApplicationService;

class CouponController extends Controller
{
    public function __construct(
        protected CouponApplicationService $couponService
    ) {
    }

    protected function getCurrentStore(): Store
    {
        return Store::firstOrFail();
    }

    /**
     * Validate a coupon code without applying it
     *
     * @param ValidateCouponRequest $request
     * @return JsonResponse
     */
    public function validate(ValidateCouponRequest $request): JsonResponse
    {
        $store = $this->getCurrentStore();
        $cart = Cart::where('store_id', $store->id)->findOrFail($request->input('cart_id'));

        $result = $this->couponService->validateCoupon(
            $request->input('code'),
            $cart,
            $request->input('customer_id')
        );

        if (!$result['valid']) {
            return $this->errorResponse(
                $result['message'],
                422
            );
        }

        return $this->successResponse([
            'valid' => true,
            'message' => $result['message'],
            'discount' => new DiscountResource($result['discount']),
        ]);
    }

    /**
     * Apply a coupon code to a cart
     *
     * @param ApplyCouponRequest $request
     * @return JsonResponse
     */
    public function apply(ApplyCouponRequest $request): JsonResponse
    {
        $store = $this->getCurrentStore();
        $cart = Cart::where('store_id', $store->id)->findOrFail($request->input('cart_id'));

        $result = $this->couponService->applyCoupon(
            $request->input('code'),
            $cart,
            $request->input('customer_id')
        );

        if (!$result['valid']) {
            return $this->errorResponse(
                $result['message'],
                422
            );
        }

        do_action('commerce.coupon.applied', $result['discount'], $cart);

        return $this->successResponse([
            'message' => $result['message'],
            'discount' => new DiscountResource($result['discount']),
            'cart' => $result['cart'],
        ], null, 'Coupon applied successfully');
    }

    /**
     * Remove a coupon code from a cart
     *
     * @param RemoveCouponRequest $request
     * @return JsonResponse
     */
    public function remove(RemoveCouponRequest $request): JsonResponse
    {
        $store = $this->getCurrentStore();
        $cart = Cart::where('store_id', $store->id)->findOrFail($request->input('cart_id'));

        $result = $this->couponService->removeCoupon(
            $request->input('code'),
            $cart
        );

        if (!$result['valid']) {
            return $this->errorResponse(
                $result['message'],
                422
            );
        }

        do_action('commerce.coupon.removed', $request->input('code'), $cart);

        return $this->successResponse([
            'message' => $result['message'],
            'cart' => $result['cart'],
        ], null, 'Coupon removed successfully');
    }

    /**
     * Get all automatic discounts for a cart
     *
     * @param int $cartId
     * @return JsonResponse
     */
    public function automatic(int $cartId): JsonResponse
    {
        $store = $this->getCurrentStore();
        $cart = Cart::where('store_id', $store->id)->findOrFail($cartId);

        $customerId = request()->input('customer_id');

        $automaticDiscounts = $this->couponService->getAutomaticDiscounts($cart, $customerId);

        return $this->successResponse([
            'automatic_discounts' => $automaticDiscounts,
            'count' => count($automaticDiscounts),
        ]);
    }
}
