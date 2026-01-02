<?php

declare(strict_types=1);

namespace VodoCommerce\Services;

use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use VodoCommerce\Models\Cart;
use VodoCommerce\Models\InventoryReservation;
use VodoCommerce\Models\Product;
use VodoCommerce\Models\ProductVariant;
use VodoCommerce\Models\Store;

/**
 * InventoryReservationService
 *
 * Manages temporary stock reservations during the checkout process.
 * Prevents overselling by holding inventory when items are added to cart.
 *
 * Configuration (via config/commerce.php):
 * - reservation_ttl_minutes: How long reservations last (default: 15)
 * - reservation_extend_minutes: How long to extend on activity (default: 15)
 *
 * Usage:
 * - reserve(): Called when adding item to cart
 * - release(): Called when removing item from cart
 * - convert(): Called when order is placed (removes reservation, stock already decremented)
 * - getAvailableStock(): Returns stock minus active reservations
 */
class InventoryReservationService
{
    protected int $reservationTtl;
    protected int $extendMinutes;

    public function __construct(protected Store $store)
    {
        $this->reservationTtl = config('commerce.reservation_ttl_minutes', 15);
        $this->extendMinutes = config('commerce.reservation_extend_minutes', 15);
    }

    /**
     * Reserve inventory for a cart item.
     *
     * @param Cart $cart
     * @param Product $product
     * @param int $quantity
     * @param ProductVariant|null $variant
     * @return InventoryReservation|null Null if insufficient stock
     */
    public function reserve(
        Cart $cart,
        Product $product,
        int $quantity,
        ?ProductVariant $variant = null
    ): ?InventoryReservation {
        return DB::transaction(function () use ($cart, $product, $quantity, $variant) {
            // Lock the product/variant row to prevent race conditions
            $stockHolder = $variant
                ? ProductVariant::lockForUpdate()->find($variant->id)
                : Product::lockForUpdate()->find($product->id);

            if (!$stockHolder) {
                return null;
            }

            // Check available stock (actual stock minus other reservations)
            $availableStock = $this->getAvailableStock(
                $product->id,
                $variant?->id,
                $cart->id
            );

            if ($availableStock < $quantity) {
                Log::debug('Insufficient stock for reservation', [
                    'product_id' => $product->id,
                    'variant_id' => $variant?->id,
                    'requested' => $quantity,
                    'available' => $availableStock,
                ]);

                return null;
            }

            // Check for existing reservation for this cart/product
            $existing = InventoryReservation::forCart($cart->id)
                ->forProduct($product->id, $variant?->id)
                ->first();

            if ($existing) {
                // Update existing reservation
                $existing->update([
                    'quantity' => $quantity,
                    'expires_at' => now()->addMinutes($this->reservationTtl),
                ]);

                return $existing->fresh();
            }

            // Create new reservation
            return InventoryReservation::create([
                'store_id' => $this->store->id,
                'cart_id' => $cart->id,
                'session_id' => $cart->session_id,
                'product_id' => $product->id,
                'variant_id' => $variant?->id,
                'quantity' => $quantity,
                'expires_at' => now()->addMinutes($this->reservationTtl),
            ]);
        });
    }

    /**
     * Update reservation quantity (e.g., when cart quantity changes).
     *
     * @param Cart $cart
     * @param int $productId
     * @param int|null $variantId
     * @param int $newQuantity
     * @return bool True if updated, false if insufficient stock
     */
    public function updateQuantity(
        Cart $cart,
        int $productId,
        ?int $variantId,
        int $newQuantity
    ): bool {
        if ($newQuantity <= 0) {
            $this->release($cart, $productId, $variantId);

            return true;
        }

        return DB::transaction(function () use ($cart, $productId, $variantId, $newQuantity) {
            $reservation = InventoryReservation::forCart($cart->id)
                ->forProduct($productId, $variantId)
                ->first();

            // Calculate how much additional stock we need
            $currentReserved = $reservation?->quantity ?? 0;
            $additionalNeeded = $newQuantity - $currentReserved;

            if ($additionalNeeded > 0) {
                // Need more stock - check availability
                $available = $this->getAvailableStock($productId, $variantId, $cart->id);

                if ($available < $additionalNeeded) {
                    return false;
                }
            }

            if ($reservation) {
                $reservation->update([
                    'quantity' => $newQuantity,
                    'expires_at' => now()->addMinutes($this->reservationTtl),
                ]);
            } else {
                // Create new reservation
                $product = Product::find($productId);
                $variant = $variantId ? ProductVariant::find($variantId) : null;

                if (!$product) {
                    return false;
                }

                $this->reserve($cart, $product, $newQuantity, $variant);
            }

            return true;
        });
    }

    /**
     * Release a reservation (e.g., when item removed from cart).
     */
    public function release(Cart $cart, int $productId, ?int $variantId = null): void
    {
        InventoryReservation::forCart($cart->id)
            ->forProduct($productId, $variantId)
            ->delete();
    }

    /**
     * Release all reservations for a cart.
     */
    public function releaseAll(Cart $cart): void
    {
        InventoryReservation::forCart($cart->id)->delete();
    }

    /**
     * Convert reservations to order (called after successful checkout).
     *
     * Stock has already been decremented by CheckoutService, so we just
     * need to remove the reservations.
     */
    public function convertToOrder(Cart $cart): void
    {
        $this->releaseAll($cart);
    }

    /**
     * Extend all reservations for a cart (called on cart activity).
     */
    public function extendAll(Cart $cart): void
    {
        InventoryReservation::forCart($cart->id)
            ->active()
            ->update(['expires_at' => now()->addMinutes($this->extendMinutes)]);
    }

    /**
     * Get available stock for a product (actual stock minus active reservations).
     *
     * @param int $productId
     * @param int|null $variantId
     * @param int|null $excludeCartId Cart to exclude from reservation count
     * @return int Available stock
     */
    public function getAvailableStock(
        int $productId,
        ?int $variantId = null,
        ?int $excludeCartId = null
    ): int {
        // Get actual stock
        if ($variantId) {
            $variant = ProductVariant::find($variantId);
            $actualStock = $variant?->stock_quantity ?? 0;
        } else {
            $product = Product::find($productId);
            $actualStock = $product?->stock_quantity ?? 0;
        }

        // Subtract active reservations (excluding this cart's own reservations)
        $reserved = InventoryReservation::getTotalReserved(
            $productId,
            $variantId,
            $excludeCartId
        );

        return max(0, $actualStock - $reserved);
    }

    /**
     * Check if requested quantity is available.
     *
     * @param int $productId
     * @param int $quantity
     * @param int|null $variantId
     * @param int|null $excludeCartId
     * @return bool
     */
    public function isAvailable(
        int $productId,
        int $quantity,
        ?int $variantId = null,
        ?int $excludeCartId = null
    ): bool {
        return $this->getAvailableStock($productId, $variantId, $excludeCartId) >= $quantity;
    }

    /**
     * Clean up expired reservations.
     *
     * Should be called periodically via cron job.
     *
     * @return int Number of reservations cleaned up
     */
    public function cleanupExpired(): int
    {
        $count = InventoryReservation::cleanupExpired();

        if ($count > 0) {
            Log::info('Cleaned up expired inventory reservations', ['count' => $count]);
        }

        return $count;
    }

    /**
     * Get reservation summary for a cart.
     *
     * @return array<array{product_id: int, variant_id: int|null, quantity: int, expires_at: string}>
     */
    public function getCartReservations(Cart $cart): array
    {
        return InventoryReservation::forCart($cart->id)
            ->active()
            ->get()
            ->map(fn($r) => [
                'product_id' => $r->product_id,
                'variant_id' => $r->variant_id,
                'quantity' => $r->quantity,
                'expires_at' => $r->expires_at->toIso8601String(),
            ])
            ->toArray();
    }
}
