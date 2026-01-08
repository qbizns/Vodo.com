<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use VodoCommerce\Http\Resources\OrderResource;
use VodoCommerce\Models\Cart;
use VodoCommerce\Models\Store;
use VodoCommerce\Registries\PaymentGatewayRegistry;
use VodoCommerce\Registries\ShippingCarrierRegistry;
use VodoCommerce\Registries\TaxProviderRegistry;
use VodoCommerce\Services\CartService;
use VodoCommerce\Services\CheckoutService;

class CheckoutController extends Controller
{
    protected CheckoutService $checkoutService;
    protected CartService $cartService;
    protected Store $store;

    public function __construct(
        PaymentGatewayRegistry $paymentGateways,
        ShippingCarrierRegistry $shippingCarriers,
        TaxProviderRegistry $taxProviders
    ) {
        $this->store = resolve_store();
        $this->checkoutService = new CheckoutService(
            $this->store,
            $paymentGateways,
            $shippingCarriers,
            $taxProviders
        );
        $this->cartService = new CartService($this->store);
    }

    /**
     * Validate checkout readiness.
     */
    public function validate(Request $request): JsonResponse
    {
        $sessionId = $request->header('X-Session-Id') ?? $request->input('session_id');
        $customerId = $request->user()?->id;

        $cart = $this->cartService->getCart($sessionId, $customerId);
        $errors = $this->checkoutService->validateCheckout($cart);

        return response()->json([
            'success' => empty($errors),
            'errors' => $errors,
            'message' => empty($errors) ? 'Checkout is ready' : 'Checkout validation failed',
        ], empty($errors) ? 200 : 422);
    }

    /**
     * Get available shipping rates for the cart.
     */
    public function shippingRates(Request $request): JsonResponse
    {
        $sessionId = $request->header('X-Session-Id') ?? $request->input('session_id');
        $customerId = $request->user()?->id;

        $cart = $this->cartService->getCart($sessionId, $customerId);

        if (empty($cart->shipping_address)) {
            return response()->json([
                'success' => false,
                'message' => 'Shipping address is required to calculate shipping rates',
            ], 422);
        }

        $rates = $this->checkoutService->getAvailableShippingRates($cart);

        return response()->json([
            'success' => true,
            'data' => [
                'rates' => $rates,
                'currency' => $cart->currency,
            ],
        ]);
    }

    /**
     * Calculate tax for the cart.
     */
    public function calculateTax(Request $request): JsonResponse
    {
        $sessionId = $request->header('X-Session-Id') ?? $request->input('session_id');
        $customerId = $request->user()?->id;

        $cart = $this->cartService->getCart($sessionId, $customerId);

        if (empty($cart->shipping_address)) {
            return response()->json([
                'success' => false,
                'message' => 'Shipping address is required to calculate tax',
            ], 422);
        }

        $taxCalculation = $this->checkoutService->calculateTax($cart);

        // Update cart with calculated tax
        $cart->update(['tax_total' => $taxCalculation['tax_total']]);
        $cart->recalculate();

        return response()->json([
            'success' => true,
            'data' => [
                'tax_total' => $taxCalculation['tax_total'],
                'tax_breakdown' => $taxCalculation['tax_breakdown'],
                'currency' => $cart->currency,
                'cart_total' => $cart->total,
            ],
        ]);
    }

    /**
     * Get available payment methods.
     */
    public function paymentMethods(): JsonResponse
    {
        $methods = $this->checkoutService->getAvailablePaymentMethods();

        return response()->json([
            'success' => true,
            'data' => $methods,
        ]);
    }

    /**
     * Create an order from the cart.
     */
    public function createOrder(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'payment_method' => ['required', 'string'],
            'customer_email' => ['nullable', 'email'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $sessionId = $request->header('X-Session-Id') ?? $request->input('session_id');
        $customerId = $request->user()?->id;

        $cart = $this->cartService->getCart($sessionId, $customerId);

        try {
            $order = $this->checkoutService->createOrder(
                $cart,
                $request->payment_method,
                $request->customer_email
            );

            return response()->json([
                'success' => true,
                'message' => 'Order created successfully',
                'data' => new OrderResource($order),
            ], 201);
        } catch (\InvalidArgumentException $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 422);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to create order: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Initiate payment for an order.
     */
    public function initiatePayment(Request $request, string $orderNumber): JsonResponse
    {
        $order = \VodoCommerce\Models\Order::where('store_id', $this->store->id)
            ->where('order_number', $orderNumber)
            ->firstOrFail();

        // Verify order belongs to current user (if authenticated)
        if ($request->user() && $order->customer_id !== $request->user()->id) {
            return response()->json([
                'success' => false,
                'message' => 'Unauthorized',
            ], 403);
        }

        try {
            $paymentSession = $this->checkoutService->initiatePayment($order);

            return response()->json([
                'success' => true,
                'data' => $paymentSession,
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => 'Failed to initiate payment: ' . $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Process payment webhook.
     */
    public function processWebhook(Request $request, string $gateway): JsonResponse
    {
        try {
            $result = $this->checkoutService->processPaymentWebhook(
                $gateway,
                $request->all(),
                $request->headers->all()
            );

            return response()->json($result, $result['success'] ? 200 : 400);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 500);
        }
    }

    /**
     * Get checkout summary (cart + available methods + validation).
     */
    public function summary(Request $request): JsonResponse
    {
        $sessionId = $request->header('X-Session-Id') ?? $request->input('session_id');
        $customerId = $request->user()?->id;

        $cart = $this->cartService->getCart($sessionId, $customerId);
        $cart->load(['items.product', 'items.variant']);

        $validationErrors = $this->checkoutService->validateCheckout($cart);
        $cartErrors = $this->cartService->validateItems();

        return response()->json([
            'success' => true,
            'data' => [
                'cart' => $this->cartService->getSummary(),
                'validation' => [
                    'is_valid' => empty($validationErrors) && empty($cartErrors),
                    'checkout_errors' => $validationErrors,
                    'cart_errors' => $cartErrors,
                ],
                'available_payment_methods' => $this->checkoutService->getAvailablePaymentMethods(),
                'requires_shipping' => !empty($cart->items->filter(fn($item) =>
                    $item->product->requires_shipping ?? true
                )),
            ],
        ]);
    }
}
