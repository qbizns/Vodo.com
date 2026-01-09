<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Controllers\Api\V2;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Validator;
use VodoCommerce\Http\Resources\CartResource;
use VodoCommerce\Models\Cart;
use VodoCommerce\Models\CartItem;
use VodoCommerce\Models\Product;
use VodoCommerce\Models\ProductVariant;
use VodoCommerce\Models\Store;
use VodoCommerce\Services\CartService;
use VodoCommerce\Services\ShippingCalculationService;

class CartController extends Controller
{
    protected CartService $cartService;
    protected ShippingCalculationService $shippingCalculationService;
    protected Store $store;

    public function __construct(ShippingCalculationService $shippingCalculationService)
    {
        $this->store = resolve_store();
        $this->cartService = new CartService($this->store);
        $this->shippingCalculationService = $shippingCalculationService;
    }

    /**
     * Get the current cart.
     */
    public function show(Request $request): JsonResponse
    {
        $sessionId = $request->header('X-Session-Id') ?? $request->input('session_id');
        $customerId = $request->user()?->id;

        $cart = $this->cartService->getCart($sessionId, $customerId);
        $cart->load(['items.product', 'items.variant', 'customer']);

        return response()->json([
            'success' => true,
            'data' => new CartResource($cart),
        ]);
    }

    /**
     * Add an item to the cart.
     */
    public function addItem(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'product_id' => ['required', 'integer', 'exists:commerce_products,id'],
            'variant_id' => ['nullable', 'integer', 'exists:commerce_product_variants,id'],
            'quantity' => ['required', 'integer', 'min:1'],
            'options' => ['nullable', 'array'],
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'message' => 'Validation failed',
                'errors' => $validator->errors(),
            ], 422);
        }

        $product = Product::findOrFail($request->product_id);
        $variant = $request->variant_id ? ProductVariant::findOrFail($request->variant_id) : null;

        $sessionId = $request->header('X-Session-Id') ?? $request->input('session_id');
        $customerId = $request->user()?->id;

        $this->cartService->getCart($sessionId, $customerId);

        try {
            $item = $this->cartService->addItem(
                $product,
                $request->quantity,
                $variant,
                $request->options ?? []
            );

            $cart = $item->cart;
            $cart->load(['items.product', 'items.variant', 'customer']);

            return response()->json([
                'success' => true,
                'message' => 'Item added to cart successfully',
                'data' => new CartResource($cart),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Update cart item quantity.
     */
    public function updateItem(Request $request, int $itemId): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'quantity' => ['required', 'integer', 'min:0'],
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
        $item = CartItem::where('cart_id', $cart->id)->findOrFail($itemId);

        try {
            $this->cartService->updateItemQuantity($item, $request->quantity);

            $cart->load(['items.product', 'items.variant', 'customer']);

            return response()->json([
                'success' => true,
                'message' => 'Cart item updated successfully',
                'data' => new CartResource($cart),
            ]);
        } catch (\Exception $e) {
            return response()->json([
                'success' => false,
                'message' => $e->getMessage(),
            ], 400);
        }
    }

    /**
     * Remove an item from the cart.
     */
    public function removeItem(Request $request, int $itemId): JsonResponse
    {
        $sessionId = $request->header('X-Session-Id') ?? $request->input('session_id');
        $customerId = $request->user()?->id;

        $cart = $this->cartService->getCart($sessionId, $customerId);
        $item = CartItem::where('cart_id', $cart->id)->findOrFail($itemId);

        $this->cartService->removeItem($item);

        $cart->load(['items.product', 'items.variant', 'customer']);

        return response()->json([
            'success' => true,
            'message' => 'Item removed from cart',
            'data' => new CartResource($cart),
        ]);
    }

    /**
     * Apply a discount code to the cart.
     */
    public function applyDiscount(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code' => ['required', 'string'],
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

        $this->cartService->getCart($sessionId, $customerId);

        $result = $this->cartService->applyDiscountCode($request->code);

        $cart = $this->cartService->getCart();
        $cart->load(['items.product', 'items.variant', 'customer']);

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'data' => new CartResource($cart),
        ], $result['success'] ? 200 : 422);
    }

    /**
     * Remove a discount code from the cart.
     */
    public function removeDiscount(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'code' => ['required', 'string'],
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

        $this->cartService->getCart($sessionId, $customerId);
        $this->cartService->removeDiscountCode($request->code);

        $cart = $this->cartService->getCart();
        $cart->load(['items.product', 'items.variant', 'customer']);

        return response()->json([
            'success' => true,
            'message' => 'Discount code removed',
            'data' => new CartResource($cart),
        ]);
    }

    /**
     * Set billing address.
     */
    public function setBillingAddress(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'first_name' => ['required', 'string'],
            'last_name' => ['required', 'string'],
            'email' => ['required', 'email'],
            'phone' => ['nullable', 'string'],
            'company' => ['nullable', 'string'],
            'address1' => ['required', 'string'],
            'address2' => ['nullable', 'string'],
            'city' => ['required', 'string'],
            'state' => ['nullable', 'string'],
            'postal_code' => ['required', 'string'],
            'country' => ['required', 'string', 'size:2'],
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

        $this->cartService->getCart($sessionId, $customerId);
        $this->cartService->setBillingAddress($request->only([
            'first_name', 'last_name', 'email', 'phone', 'company',
            'address1', 'address2', 'city', 'state', 'postal_code', 'country',
        ]));

        $cart = $this->cartService->getCart();
        $cart->load(['items.product', 'items.variant', 'customer']);

        return response()->json([
            'success' => true,
            'message' => 'Billing address updated',
            'data' => new CartResource($cart),
        ]);
    }

    /**
     * Set shipping address.
     */
    public function setShippingAddress(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'first_name' => ['required', 'string'],
            'last_name' => ['required', 'string'],
            'phone' => ['nullable', 'string'],
            'company' => ['nullable', 'string'],
            'address1' => ['required', 'string'],
            'address2' => ['nullable', 'string'],
            'city' => ['required', 'string'],
            'state' => ['nullable', 'string'],
            'postal_code' => ['required', 'string'],
            'country' => ['required', 'string', 'size:2'],
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

        $this->cartService->getCart($sessionId, $customerId);
        $this->cartService->setShippingAddress($request->only([
            'first_name', 'last_name', 'phone', 'company',
            'address1', 'address2', 'city', 'state', 'postal_code', 'country',
        ]));

        $cart = $this->cartService->getCart();
        $cart->load(['items.product', 'items.variant', 'customer']);

        return response()->json([
            'success' => true,
            'message' => 'Shipping address updated',
            'data' => new CartResource($cart),
        ]);
    }

    /**
     * Set shipping method with server-side cost calculation.
     * SECURITY: Cost is calculated server-side to prevent price manipulation.
     */
    public function setShippingMethod(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'method_id' => ['required', 'integer', 'exists:commerce_shipping_methods,id'],
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
        $cart->load(['items.product', 'items.variant']);

        // Calculate shipping cost server-side
        $shippingAddress = $cart->shipping_address ?? [];
        $cartData = [
            'subtotal' => $cart->subtotal,
            'item_count' => $cart->getItemCount(),
            'total_weight' => $cart->items->sum(function ($item) {
                return ($item->product->weight ?? 0) * $item->quantity;
            }),
        ];

        $cost = $this->shippingCalculationService->calculateShippingCost(
            $this->store,
            (int) $request->method_id,
            $shippingAddress,
            $cartData
        );

        if ($cost === null) {
            return response()->json([
                'success' => false,
                'message' => 'Selected shipping method is not available for your location or cart',
            ], 422);
        }

        // Get method name for storage
        $method = \VodoCommerce\Models\ShippingMethod::find($request->method_id);

        $this->cartService->setShippingMethod($method->name, $cost);

        $cart = $this->cartService->getCart();
        $cart->load(['items.product', 'items.variant', 'customer']);

        return response()->json([
            'success' => true,
            'message' => 'Shipping method updated',
            'data' => new CartResource($cart),
        ]);
    }

    /**
     * Set cart notes.
     */
    public function setNotes(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'notes' => ['nullable', 'string', 'max:1000'],
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

        $this->cartService->getCart($sessionId, $customerId);
        $this->cartService->setNotes($request->notes);

        $cart = $this->cartService->getCart();
        $cart->load(['items.product', 'items.variant', 'customer']);

        return response()->json([
            'success' => true,
            'message' => 'Cart notes updated',
            'data' => new CartResource($cart),
        ]);
    }

    /**
     * Clear the cart.
     */
    public function clear(Request $request): JsonResponse
    {
        $sessionId = $request->header('X-Session-Id') ?? $request->input('session_id');
        $customerId = $request->user()?->id;

        $this->cartService->getCart($sessionId, $customerId);
        $this->cartService->clear();

        $cart = $this->cartService->getCart();
        $cart->load(['items.product', 'items.variant', 'customer']);

        return response()->json([
            'success' => true,
            'message' => 'Cart cleared successfully',
            'data' => new CartResource($cart),
        ]);
    }

    /**
     * Validate cart items.
     */
    public function validate(Request $request): JsonResponse
    {
        $sessionId = $request->header('X-Session-Id') ?? $request->input('session_id');
        $customerId = $request->user()?->id;

        $this->cartService->getCart($sessionId, $customerId);
        $errors = $this->cartService->validateItems();

        return response()->json([
            'success' => empty($errors),
            'errors' => $errors,
            'message' => empty($errors) ? 'Cart is valid' : 'Cart has validation errors',
        ]);
    }

    /**
     * Get cart summary.
     */
    public function summary(Request $request): JsonResponse
    {
        $sessionId = $request->header('X-Session-Id') ?? $request->input('session_id');
        $customerId = $request->user()?->id;

        $this->cartService->getCart($sessionId, $customerId);
        $summary = $this->cartService->getSummary();

        return response()->json([
            'success' => true,
            'data' => $summary,
        ]);
    }

    /**
     * Sync cart prices with current product prices.
     */
    public function syncPrices(Request $request): JsonResponse
    {
        $sessionId = $request->header('X-Session-Id') ?? $request->input('session_id');
        $customerId = $request->user()?->id;

        $this->cartService->getCart($sessionId, $customerId);
        $this->cartService->syncPrices();

        $cart = $this->cartService->getCart();
        $cart->load(['items.product', 'items.variant', 'customer']);

        return response()->json([
            'success' => true,
            'message' => 'Cart prices synchronized',
            'data' => new CartResource($cart),
        ]);
    }
}
