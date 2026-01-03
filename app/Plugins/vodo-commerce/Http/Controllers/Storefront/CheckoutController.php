<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Controllers\Storefront;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use VodoCommerce\Models\Store;
use VodoCommerce\Registries\PaymentGatewayRegistry;
use VodoCommerce\Registries\ShippingCarrierRegistry;
use VodoCommerce\Registries\TaxProviderRegistry;
use VodoCommerce\Services\CartService;
use VodoCommerce\Services\CheckoutService;

class CheckoutController extends Controller
{
    public function show(Request $request, string $storeSlug)
    {
        $store = Store::withoutGlobalScopes()->where('slug', $storeSlug)->active()->firstOrFail();
        $cartService = $this->getCartService($store, $request);

        if ($cartService->getCart()->isEmpty()) {
            return redirect()->route('storefront.vodo-commerce.cart.show', $storeSlug)
                ->with('error', 'Your cart is empty');
        }

        $checkoutService = $this->getCheckoutService($store);

        return view('vodo-commerce::storefront.checkout', [
            'store' => $store,
            'cart' => $cartService->getSummary(),
            'paymentMethods' => $checkoutService->getAvailablePaymentMethods(),
        ]);
    }

    public function getShippingRates(Request $request, string $storeSlug): JsonResponse
    {
        try {
            $store = Store::withoutGlobalScopes()->where('slug', $storeSlug)->active()->firstOrFail();

            $request->validate([
                'shipping_address' => 'required|array',
                'shipping_address.first_name' => 'required|string',
                'shipping_address.last_name' => 'required|string',
                'shipping_address.address1' => 'required|string',
                'shipping_address.city' => 'required|string',
                'shipping_address.postal_code' => 'required|string',
                'shipping_address.country' => 'required|string',
            ]);

            $cartService = $this->getCartService($store, $request);
            $cartService->setShippingAddress($request->input('shipping_address'));

            // Return simple default rates - bypass complex service for now
            $rates = [
                [
                    'id' => 'standard',
                    'name' => 'Standard Shipping',
                    'cost' => 10.00,
                    'currency' => $store->currency ?? 'USD',
                    'estimated_days' => '5-7',
                ],
                [
                    'id' => 'express',
                    'name' => 'Express Shipping',
                    'cost' => 25.00,
                    'currency' => $store->currency ?? 'USD',
                    'estimated_days' => '2-3',
                ],
                [
                    'id' => 'free',
                    'name' => 'Free Shipping',
                    'cost' => 0.00,
                    'currency' => $store->currency ?? 'USD',
                    'estimated_days' => '7-14',
                ],
            ];

            return response()->json([
                'success' => true,
                'rates' => $rates,
            ]);
        } catch (\Illuminate\Validation\ValidationException $e) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $e->errors(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to get shipping rates: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function setShippingMethod(Request $request, string $storeSlug): JsonResponse
    {
        $store = Store::withoutGlobalScopes()->where('slug', $storeSlug)->active()->firstOrFail();

        $request->validate([
            'shipping_method' => 'required|string',
            'shipping_cost' => 'required|numeric|min:0',
        ]);

        $cartService = $this->getCartService($store, $request);
        $cartService->setShippingMethod(
            $request->input('shipping_method'),
            (float) $request->input('shipping_cost')
        );

        return response()->json([
            'success' => true,
            'cart' => $cartService->getSummary(),
        ]);
    }

    public function calculateTax(Request $request, string $storeSlug): JsonResponse
    {
        $store = Store::withoutGlobalScopes()->where('slug', $storeSlug)->active()->firstOrFail();

        $cartService = $this->getCartService($store, $request);
        $checkoutService = $this->getCheckoutService($store);

        $taxResult = $checkoutService->calculateTax($cartService->getCart());

        // Update cart with tax
        $cart = $cartService->getCart();
        $cart->update(['tax_total' => $taxResult['tax_total']]);
        $cart->recalculate();

        return response()->json([
            'success' => true,
            'tax' => $taxResult,
            'cart' => $cartService->getSummary(),
        ]);
    }

    public function updateAddresses(Request $request, string $storeSlug): JsonResponse
    {
        $store = Store::withoutGlobalScopes()->where('slug', $storeSlug)->active()->firstOrFail();

        $request->validate([
            'billing_address' => 'required|array',
            'billing_address.first_name' => 'required|string',
            'billing_address.last_name' => 'required|string',
            'billing_address.address1' => 'required|string',
            'billing_address.city' => 'required|string',
            'billing_address.postal_code' => 'required|string',
            'billing_address.country' => 'required|string',
            'billing_address.email' => 'required|email',
            'shipping_address' => 'nullable|array',
            'use_billing_for_shipping' => 'boolean',
        ]);

        $cartService = $this->getCartService($store, $request);

        $billingAddress = $request->input('billing_address');
        $cartService->setBillingAddress($billingAddress);

        if ($request->boolean('use_billing_for_shipping')) {
            $cartService->setShippingAddress($billingAddress);
        } elseif ($request->has('shipping_address')) {
            $cartService->setShippingAddress($request->input('shipping_address'));
        }

        return response()->json([
            'success' => true,
            'cart' => $cartService->getSummary(),
        ]);
    }

    public function placeOrder(Request $request, string $storeSlug): JsonResponse
    {
        $store = Store::withoutGlobalScopes()->where('slug', $storeSlug)->active()->firstOrFail();

        $request->validate([
            'payment_method' => 'required|string',
            'notes' => 'nullable|string|max:500',
        ]);

        $cartService = $this->getCartService($store, $request);
        $checkoutService = $this->getCheckoutService($store);

        // Set notes if provided
        if ($request->has('notes')) {
            $cartService->setNotes($request->input('notes'));
        }

        // Validate cart
        $errors = $checkoutService->validateCheckout($cartService->getCart());
        if (!empty($errors)) {
            return response()->json([
                'success' => false,
                'message' => 'Checkout validation failed',
                'errors' => $errors,
            ], 422);
        }

        try {
            $order = $checkoutService->createOrder(
                $cartService->getCart(),
                $request->input('payment_method'),
                $request->input('billing_address.email') ?? $request->user()?->email
            );

            // Initiate payment
            $paymentSession = $checkoutService->initiatePayment($order);

            return response()->json([
                'success' => true,
                'order' => [
                    'id' => $order->id,
                    'order_number' => $order->order_number,
                    'total' => $order->total,
                ],
                'payment' => $paymentSession,
            ]);
        } catch (\Exception $e) {
            report($e);

            return response()->json([
                'success' => false,
                'message' => 'Failed to create order: ' . $e->getMessage(),
            ], 500);
        }
    }

    public function success(Request $request, string $storeSlug, string $orderNumber)
    {
        $store = Store::withoutGlobalScopes()->where('slug', $storeSlug)->active()->firstOrFail();

        $order = \VodoCommerce\Models\Order::where('store_id', $store->id)
            ->where('order_number', $orderNumber)
            ->with(['items'])
            ->firstOrFail();

        return view('vodo-commerce::storefront.checkout-success', [
            'store' => $store,
            'order' => $order,
        ]);
    }

    protected function getCartService(Store $store, Request $request): CartService
    {
        $cartService = new CartService($store);
        $sessionId = $request->session()->get('cart_session_id');

        if (!$sessionId) {
            $sessionId = \Illuminate\Support\Str::uuid()->toString();
            $request->session()->put('cart_session_id', $sessionId);
        }

        $customerId = $request->user()?->customer?->id ?? null;
        $cartService->getCart($sessionId, $customerId);

        return $cartService;
    }

    protected function getCheckoutService(Store $store): CheckoutService
    {
        return new CheckoutService(
            $store,
            app(PaymentGatewayRegistry::class),
            app(ShippingCarrierRegistry::class),
            app(TaxProviderRegistry::class)
        );
    }
}
