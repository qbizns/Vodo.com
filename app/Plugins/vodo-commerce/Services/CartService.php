<?php

declare(strict_types=1);

namespace VodoCommerce\Services;

use Illuminate\Support\Str;
use VodoCommerce\Models\Cart;
use VodoCommerce\Models\CartItem;
use VodoCommerce\Models\Discount;
use VodoCommerce\Models\Product;
use VodoCommerce\Models\ProductVariant;
use VodoCommerce\Models\Store;

class CartService
{
    protected ?Cart $cart = null;
    protected ?InventoryReservationService $reservationService = null;

    public function __construct(protected Store $store)
    {
        $this->reservationService = new InventoryReservationService($store);
    }

    /**
     * Get the reservation service instance.
     */
    public function getReservationService(): InventoryReservationService
    {
        return $this->reservationService;
    }

    public function getCart(?string $sessionId = null, ?int $customerId = null): Cart
    {
        if ($this->cart) {
            return $this->cart;
        }

        $this->cart = $this->findOrCreateCart($sessionId, $customerId);

        return $this->cart;
    }

    protected function findOrCreateCart(?string $sessionId, ?int $customerId): Cart
    {
        $query = Cart::query()
            ->where('store_id', $this->store->id)
            ->notExpired();

        if ($customerId) {
            $cart = $query->where('customer_id', $customerId)->first();
            if ($cart) {
                return $cart;
            }
        }

        if ($sessionId) {
            $cart = $query->where('session_id', $sessionId)->first();
            if ($cart) {
                // Attach customer if provided
                if ($customerId && !$cart->customer_id) {
                    $cart->update(['customer_id' => $customerId]);
                }

                return $cart;
            }
        }

        return Cart::create([
            'store_id' => $this->store->id,
            'customer_id' => $customerId,
            'session_id' => $sessionId ?? Str::uuid()->toString(),
            'currency' => $this->store->currency,
            'expires_at' => now()->addDays(7),
        ]);
    }

    public function addItem(
        Product $product,
        int $quantity = 1,
        ?ProductVariant $variant = null,
        array $options = []
    ): CartItem {
        $cart = $this->getCart();

        // Check if item already exists
        $existingItem = $cart->getItem($product->id, $variant?->id);

        if ($existingItem) {
            return $this->updateItemQuantity($existingItem, $existingItem->quantity + $quantity);
        }

        // Try to reserve inventory before adding to cart
        $reservation = $this->reservationService->reserve($cart, $product, $quantity, $variant);
        if (!$reservation) {
            throw new \RuntimeException(
                "Unable to add item to cart: insufficient stock for '{$product->name}'"
            );
        }

        // Determine price
        $unitPrice = $variant ? $variant->getEffectivePrice() : $product->price;

        $item = CartItem::create([
            'cart_id' => $cart->id,
            'product_id' => $product->id,
            'variant_id' => $variant?->id,
            'quantity' => $quantity,
            'unit_price' => $unitPrice,
            'options' => $options,
        ]);

        $cart->recalculate();

        return $item;
    }

    public function updateItemQuantity(CartItem $item, int $quantity): CartItem
    {
        if ($quantity <= 0) {
            $this->removeItem($item);

            return $item;
        }

        $cart = $this->getCart();

        // Try to update the reservation first
        $reserved = $this->reservationService->updateQuantity(
            $cart,
            $item->product_id,
            $item->variant_id,
            $quantity
        );

        if (!$reserved) {
            // Could not reserve requested quantity - get maximum available
            $availableQuantity = $this->reservationService->getAvailableStock(
                $item->product_id,
                $item->variant_id,
                $cart->id
            );

            // Include what we already have reserved
            $currentReservation = $item->quantity;
            $maxQuantity = $availableQuantity + $currentReservation;

            if ($quantity > $maxQuantity) {
                $quantity = $maxQuantity;

                // Update reservation with the adjusted quantity
                $this->reservationService->updateQuantity(
                    $cart,
                    $item->product_id,
                    $item->variant_id,
                    $quantity
                );
            }
        }

        $item->update(['quantity' => $quantity]);

        $cart->recalculate();

        return $item->fresh();
    }

    public function removeItem(CartItem $item): void
    {
        $cart = $this->getCart();

        // Release the reservation for this item
        $this->reservationService->release($cart, $item->product_id, $item->variant_id);

        $item->delete();
        $cart->recalculate();
    }

    public function applyDiscountCode(string $code): array
    {
        $cart = $this->getCart();
        $discount = Discount::where('store_id', $this->store->id)
            ->where('code', $code)
            ->first();

        if (!$discount) {
            return ['success' => false, 'message' => 'Invalid discount code'];
        }

        if (!$discount->isApplicable((float) $cart->subtotal, $cart->customer_id)) {
            if (!$discount->isValid()) {
                return ['success' => false, 'message' => 'This discount code has expired or is no longer valid'];
            }

            if ($discount->minimum_order && (float) $cart->subtotal < $discount->minimum_order) {
                return [
                    'success' => false,
                    'message' => "Minimum order of {$discount->minimum_order} required for this discount",
                ];
            }

            return ['success' => false, 'message' => 'This discount code cannot be applied'];
        }

        // Check if already applied
        $codes = $cart->discount_codes ?? [];
        if (in_array($code, $codes)) {
            return ['success' => false, 'message' => 'This discount code is already applied'];
        }

        $codes[] = $code;
        $cart->update(['discount_codes' => $codes]);
        $cart->recalculate();

        return [
            'success' => true,
            'message' => 'Discount code applied successfully',
            'discount' => $discount->calculateDiscount((float) $cart->subtotal),
        ];
    }

    public function removeDiscountCode(string $code): void
    {
        $cart = $this->getCart();
        $codes = $cart->discount_codes ?? [];
        $codes = array_filter($codes, fn($c) => $c !== $code);
        $cart->update(['discount_codes' => array_values($codes)]);
        $cart->recalculate();
    }

    public function setShippingAddress(array $address): void
    {
        $this->getCart()->update(['shipping_address' => $address]);
    }

    public function setBillingAddress(array $address): void
    {
        $this->getCart()->update(['billing_address' => $address]);
    }

    public function setShippingMethod(string $method, float $cost): void
    {
        $cart = $this->getCart();
        $cart->update([
            'shipping_method' => $method,
            'shipping_total' => $cost,
        ]);
        $cart->recalculate();
    }

    public function setNotes(?string $notes): void
    {
        $this->getCart()->update(['notes' => $notes]);
    }

    public function clear(): void
    {
        $cart = $this->getCart();

        // Release all reservations for this cart
        $this->reservationService->releaseAll($cart);

        $cart->clear();
    }

    public function validateItems(): array
    {
        $cart = $this->getCart();
        $cart->load('items.product', 'items.variant');

        $errors = [];

        foreach ($cart->items as $item) {
            // Check if product still exists and is active
            if (!$item->product || !$item->product->isActive()) {
                $errors[] = [
                    'item_id' => $item->id,
                    'type' => 'product_unavailable',
                    'message' => "{$item->getName()} is no longer available",
                ];
                continue;
            }

            // Check stock
            if (!$item->isInStock()) {
                $available = $item->getAvailableQuantity();
                $errors[] = [
                    'item_id' => $item->id,
                    'type' => 'insufficient_stock',
                    'message' => "Only {$available} of {$item->getName()} available",
                    'available' => $available,
                ];
            }

            // Check price changes
            $currentPrice = $item->getCurrentPrice();
            if ((float) $item->unit_price !== $currentPrice) {
                $errors[] = [
                    'item_id' => $item->id,
                    'type' => 'price_changed',
                    'message' => "Price of {$item->getName()} has changed",
                    'old_price' => $item->unit_price,
                    'new_price' => $currentPrice,
                ];
            }
        }

        return $errors;
    }

    public function syncPrices(): void
    {
        $cart = $this->getCart();
        $cart->load('items.product', 'items.variant');

        foreach ($cart->items as $item) {
            if ($item->product) {
                $item->updatePrice();
            }
        }

        $cart->recalculate();
    }

    public function mergeCart(Cart $guestCart): void
    {
        $customerCart = $this->getCart();

        if ($guestCart->id === $customerCart->id) {
            return;
        }

        // Create a guest cart reservation service to transfer reservations
        $guestReservationService = new InventoryReservationService($this->store);

        foreach ($guestCart->items as $guestItem) {
            $existingItem = $customerCart->getItem($guestItem->product_id, $guestItem->variant_id);

            if ($existingItem) {
                // Merge quantities and update reservation
                $this->updateItemQuantity($existingItem, $existingItem->quantity + $guestItem->quantity);
                // Release guest cart reservation (now covered by customer cart)
                $guestReservationService->release($guestCart, $guestItem->product_id, $guestItem->variant_id);
            } else {
                // Move item to customer cart
                $guestItem->update(['cart_id' => $customerCart->id]);
                // Transfer the reservation to customer cart
                $guestReservationService->release($guestCart, $guestItem->product_id, $guestItem->variant_id);
                $product = Product::find($guestItem->product_id);
                $variant = $guestItem->variant_id ? ProductVariant::find($guestItem->variant_id) : null;
                if ($product) {
                    $this->reservationService->reserve($customerCart, $product, $guestItem->quantity, $variant);
                }
            }
        }

        $guestCart->delete();
        $customerCart->recalculate();
    }

    public function getSummary(): array
    {
        $cart = $this->getCart();
        $cart->load('items.product', 'items.variant');

        return [
            'items' => $cart->items->map(fn(CartItem $item) => [
                'id' => $item->id,
                'product_id' => $item->product_id,
                'variant_id' => $item->variant_id,
                'name' => $item->getName(),
                'sku' => $item->getSku(),
                'image' => $item->getImage(),
                'quantity' => $item->quantity,
                'unit_price' => $item->unit_price,
                'line_total' => $item->getLineTotal(),
                'in_stock' => $item->isInStock(),
            ])->toArray(),
            'item_count' => $cart->getItemCount(),
            'subtotal' => $cart->subtotal,
            'discount_total' => $cart->discount_total,
            'discount_codes' => $cart->discount_codes ?? [],
            'shipping_total' => $cart->shipping_total,
            'shipping_method' => $cart->shipping_method,
            'tax_total' => $cart->tax_total,
            'total' => $cart->total,
            'currency' => $cart->currency,
        ];
    }
}
