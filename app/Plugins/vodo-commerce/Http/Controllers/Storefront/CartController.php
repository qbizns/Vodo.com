<?php

declare(strict_types=1);

namespace VodoCommerce\Http\Controllers\Storefront;

use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use VodoCommerce\Models\Product;
use VodoCommerce\Models\ProductVariant;
use VodoCommerce\Models\Store;
use VodoCommerce\Services\CartService;

class CartController extends Controller
{
    public function show(Request $request, string $storeSlug)
    {
        $store = Store::withoutGlobalScopes()->where('slug', $storeSlug)->active()->firstOrFail();
        $cartService = $this->getCartService($store, $request);

        return view('vodo-commerce::storefront.cart', [
            'store' => $store,
            'cart' => $cartService->getSummary(),
        ]);
    }

    public function add(Request $request, string $storeSlug): JsonResponse
    {
        $store = Store::withoutGlobalScopes()->where('slug', $storeSlug)->active()->firstOrFail();

        $request->validate([
            'product_id' => 'required|integer',
            'variant_id' => 'nullable|integer',
            'quantity' => 'integer|min:1',
        ]);

        $product = Product::where('store_id', $store->id)
            ->active()
            ->findOrFail($request->input('product_id'));

        $variant = null;
        if ($request->input('variant_id')) {
            $variant = ProductVariant::where('product_id', $product->id)
                ->where('is_active', true)
                ->findOrFail($request->input('variant_id'));
        }

        $cartService = $this->getCartService($store, $request);
        $item = $cartService->addItem(
            $product,
            $request->input('quantity', 1),
            $variant
        );

        return response()->json([
            'success' => true,
            'message' => 'Item added to cart',
            'item' => [
                'id' => $item->id,
                'name' => $item->getName(),
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'line_total' => $item->getLineTotal(),
            ],
            'cart' => $cartService->getSummary(),
        ]);
    }

    public function update(Request $request, string $storeSlug, int $itemId): JsonResponse
    {
        $store = Store::withoutGlobalScopes()->where('slug', $storeSlug)->active()->firstOrFail();

        $request->validate([
            'quantity' => 'required|integer|min:0',
        ]);

        $cartService = $this->getCartService($store, $request);
        $cart = $cartService->getCart();

        $item = $cart->items()->findOrFail($itemId);
        $cartService->updateItemQuantity($item, $request->input('quantity'));

        return response()->json([
            'success' => true,
            'message' => 'Cart updated',
            'cart' => $cartService->getSummary(),
        ]);
    }

    public function remove(Request $request, string $storeSlug, int $itemId): JsonResponse
    {
        $store = Store::withoutGlobalScopes()->where('slug', $storeSlug)->active()->firstOrFail();

        $cartService = $this->getCartService($store, $request);
        $cart = $cartService->getCart();

        $item = $cart->items()->findOrFail($itemId);
        $cartService->removeItem($item);

        return response()->json([
            'success' => true,
            'message' => 'Item removed from cart',
            'cart' => $cartService->getSummary(),
        ]);
    }

    public function applyDiscount(Request $request, string $storeSlug): JsonResponse
    {
        $store = Store::withoutGlobalScopes()->where('slug', $storeSlug)->active()->firstOrFail();

        $request->validate([
            'code' => 'required|string|max:50',
        ]);

        $cartService = $this->getCartService($store, $request);
        $result = $cartService->applyDiscountCode($request->input('code'));

        return response()->json([
            'success' => $result['success'],
            'message' => $result['message'],
            'discount' => $result['discount'] ?? null,
            'cart' => $cartService->getSummary(),
        ]);
    }

    public function removeDiscount(Request $request, string $storeSlug, string $code): JsonResponse
    {
        $store = Store::withoutGlobalScopes()->where('slug', $storeSlug)->active()->firstOrFail();

        $cartService = $this->getCartService($store, $request);
        $cartService->removeDiscountCode($code);

        return response()->json([
            'success' => true,
            'message' => 'Discount removed',
            'cart' => $cartService->getSummary(),
        ]);
    }

    public function clear(Request $request, string $storeSlug): JsonResponse
    {
        $store = Store::withoutGlobalScopes()->where('slug', $storeSlug)->active()->firstOrFail();

        $cartService = $this->getCartService($store, $request);
        $cartService->clear();

        return response()->json([
            'success' => true,
            'message' => 'Cart cleared',
            'cart' => $cartService->getSummary(),
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

        $customerId = $request->user()?->customer?->id;
        $cartService->getCart($sessionId, $customerId);

        return $cartService;
    }
}
