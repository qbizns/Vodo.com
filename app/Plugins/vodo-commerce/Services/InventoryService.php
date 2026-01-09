<?php

declare(strict_types=1);

namespace VodoCommerce\Services;

use Illuminate\Support\Facades\DB;
use VodoCommerce\Models\InventoryItem;
use VodoCommerce\Models\InventoryLocation;
use VodoCommerce\Models\LowStockAlert;
use VodoCommerce\Models\Product;
use VodoCommerce\Models\ProductVariant;
use VodoCommerce\Models\StockMovement;
use VodoCommerce\Models\Store;

class InventoryService
{
    public function __construct(
        protected Store $store
    ) {
    }

    /**
     * Get or create inventory item for a location and product/variant.
     */
    public function getOrCreateInventoryItem(
        int $locationId,
        int $productId,
        ?int $variantId = null
    ): InventoryItem {
        return InventoryItem::firstOrCreate(
            [
                'location_id' => $locationId,
                'product_id' => $productId,
                'variant_id' => $variantId,
            ],
            [
                'quantity' => 0,
                'reserved_quantity' => 0,
            ]
        );
    }

    /**
     * Get total stock across all locations.
     */
    public function getTotalStock(int $productId, ?int $variantId = null): int
    {
        return InventoryItem::query()
            ->whereHas('location', function ($query) {
                $query->where('store_id', $this->store->id)->where('is_active', true);
            })
            ->where('product_id', $productId)
            ->where('variant_id', $variantId)
            ->sum('quantity');
    }

    /**
     * Get total available stock across all locations.
     */
    public function getTotalAvailableStock(int $productId, ?int $variantId = null): int
    {
        $items = InventoryItem::query()
            ->whereHas('location', function ($query) {
                $query->where('store_id', $this->store->id)->where('is_active', true);
            })
            ->where('product_id', $productId)
            ->where('variant_id', $variantId)
            ->get();

        return $items->sum('available_quantity');
    }

    /**
     * Check if product has sufficient stock.
     */
    public function hasStock(int $productId, ?int $variantId = null, int $quantity = 1): bool
    {
        return $this->getTotalAvailableStock($productId, $variantId) >= $quantity;
    }

    /**
     * Add stock to a location.
     */
    public function addStock(
        int $locationId,
        int $productId,
        ?int $variantId,
        int $quantity,
        ?string $reason = null,
        ?string $referenceType = null,
        ?int $referenceId = null,
        ?float $unitCost = null
    ): InventoryItem {
        return DB::transaction(function () use (
            $locationId,
            $productId,
            $variantId,
            $quantity,
            $reason,
            $referenceType,
            $referenceId,
            $unitCost
        ) {
            $item = $this->getOrCreateInventoryItem($locationId, $productId, $variantId);
            $quantityBefore = $item->quantity;

            $item->increment('quantity', $quantity);

            if ($unitCost !== null) {
                $item->update(['unit_cost' => $unitCost]);
            }

            StockMovement::create([
                'store_id' => $this->store->id,
                'location_id' => $locationId,
                'product_id' => $productId,
                'variant_id' => $variantId,
                'type' => StockMovement::TYPE_IN,
                'quantity' => $quantity,
                'quantity_before' => $quantityBefore,
                'quantity_after' => $item->quantity,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'reason' => $reason ?? 'Stock added',
                'unit_cost' => $unitCost,
            ]);

            $this->checkAndResolveLowStockAlert($item);

            return $item->fresh();
        });
    }

    /**
     * Remove stock from a location.
     */
    public function removeStock(
        int $locationId,
        int $productId,
        ?int $variantId,
        int $quantity,
        ?string $reason = null,
        ?string $referenceType = null,
        ?int $referenceId = null
    ): InventoryItem {
        return DB::transaction(function () use (
            $locationId,
            $productId,
            $variantId,
            $quantity,
            $reason,
            $referenceType,
            $referenceId
        ) {
            $item = $this->getOrCreateInventoryItem($locationId, $productId, $variantId);

            if ($item->available_quantity < $quantity) {
                throw new \RuntimeException('Insufficient stock available');
            }

            $quantityBefore = $item->quantity;

            $item->decrement('quantity', $quantity);

            StockMovement::create([
                'store_id' => $this->store->id,
                'location_id' => $locationId,
                'product_id' => $productId,
                'variant_id' => $variantId,
                'type' => StockMovement::TYPE_OUT,
                'quantity' => $quantity,
                'quantity_before' => $quantityBefore,
                'quantity_after' => $item->quantity,
                'reference_type' => $referenceType,
                'reference_id' => $referenceId,
                'reason' => $reason ?? 'Stock removed',
            ]);

            $this->checkAndCreateLowStockAlert($item);

            return $item->fresh();
        });
    }

    /**
     * Adjust stock quantity.
     */
    public function adjustStock(
        int $locationId,
        int $productId,
        ?int $variantId,
        int $newQuantity,
        ?string $reason = null
    ): InventoryItem {
        return DB::transaction(function () use (
            $locationId,
            $productId,
            $variantId,
            $newQuantity,
            $reason
        ) {
            $item = $this->getOrCreateInventoryItem($locationId, $productId, $variantId);
            $quantityBefore = $item->quantity;
            $delta = $newQuantity - $quantityBefore;

            if ($delta === 0) {
                return $item;
            }

            $item->update(['quantity' => $newQuantity]);

            StockMovement::create([
                'store_id' => $this->store->id,
                'location_id' => $locationId,
                'product_id' => $productId,
                'variant_id' => $variantId,
                'type' => StockMovement::TYPE_ADJUSTMENT,
                'quantity' => abs($delta),
                'quantity_before' => $quantityBefore,
                'quantity_after' => $newQuantity,
                'reason' => $reason ?? 'Stock adjustment',
            ]);

            if ($delta > 0) {
                $this->checkAndResolveLowStockAlert($item);
            } else {
                $this->checkAndCreateLowStockAlert($item);
            }

            return $item->fresh();
        });
    }

    /**
     * Reserve stock for an order.
     */
    public function reserveStock(
        int $productId,
        ?int $variantId,
        int $quantity,
        ?int $preferredLocationId = null
    ): array {
        return DB::transaction(function () use ($productId, $variantId, $quantity, $preferredLocationId) {
            $reservations = [];
            $remainingQuantity = $quantity;

            // Try preferred location first
            if ($preferredLocationId) {
                $item = $this->getOrCreateInventoryItem($preferredLocationId, $productId, $variantId);
                $reservable = min($item->available_quantity, $remainingQuantity);

                if ($reservable > 0) {
                    $item->increment('reserved_quantity', $reservable);
                    $reservations[] = [
                        'location_id' => $preferredLocationId,
                        'quantity' => $reservable,
                    ];
                    $remainingQuantity -= $reservable;
                }
            }

            // If more needed, get from other active locations by priority
            if ($remainingQuantity > 0) {
                $locations = InventoryLocation::where('store_id', $this->store->id)
                    ->where('is_active', true)
                    ->when($preferredLocationId, fn($q) => $q->where('id', '!=', $preferredLocationId))
                    ->orderBy('priority', 'asc')
                    ->get();

                foreach ($locations as $location) {
                    if ($remainingQuantity === 0) {
                        break;
                    }

                    $item = $this->getOrCreateInventoryItem($location->id, $productId, $variantId);
                    $reservable = min($item->available_quantity, $remainingQuantity);

                    if ($reservable > 0) {
                        $item->increment('reserved_quantity', $reservable);
                        $reservations[] = [
                            'location_id' => $location->id,
                            'quantity' => $reservable,
                        ];
                        $remainingQuantity -= $reservable;
                    }
                }
            }

            if ($remainingQuantity > 0) {
                // Rollback reservations
                foreach ($reservations as $reservation) {
                    $item = $this->getOrCreateInventoryItem(
                        $reservation['location_id'],
                        $productId,
                        $variantId
                    );
                    $item->decrement('reserved_quantity', $reservation['quantity']);
                }

                throw new \RuntimeException('Insufficient stock to reserve');
            }

            return $reservations;
        });
    }

    /**
     * Release reserved stock.
     */
    public function releaseReservation(array $reservations, int $productId, ?int $variantId): void
    {
        foreach ($reservations as $reservation) {
            $item = $this->getOrCreateInventoryItem(
                $reservation['location_id'],
                $productId,
                $variantId
            );

            $item->decrement('reserved_quantity', min($reservation['quantity'], $item->reserved_quantity));
        }
    }

    /**
     * Check and create low stock alert if needed.
     */
    protected function checkAndCreateLowStockAlert(InventoryItem $item): void
    {
        if (!$item->isLowStock()) {
            return;
        }

        // Check if alert already exists
        $exists = LowStockAlert::query()
            ->where('location_id', $item->location_id)
            ->where('product_id', $item->product_id)
            ->where('variant_id', $item->variant_id)
            ->where('is_resolved', false)
            ->exists();

        if (!$exists) {
            LowStockAlert::create([
                'store_id' => $this->store->id,
                'location_id' => $item->location_id,
                'product_id' => $item->product_id,
                'variant_id' => $item->variant_id,
                'threshold' => $item->reorder_point,
                'current_quantity' => $item->available_quantity,
            ]);
        }
    }

    /**
     * Check and resolve low stock alert if stock is replenished.
     */
    protected function checkAndResolveLowStockAlert(InventoryItem $item): void
    {
        if ($item->isLowStock()) {
            return;
        }

        LowStockAlert::query()
            ->where('location_id', $item->location_id)
            ->where('product_id', $item->product_id)
            ->where('variant_id', $item->variant_id)
            ->where('is_resolved', false)
            ->update([
                'is_resolved' => true,
                'resolved_at' => now(),
                'resolution_notes' => 'Automatically resolved - stock replenished',
            ]);
    }

    /**
     * Get inventory summary across all locations.
     */
    public function getInventorySummary(int $productId, ?int $variantId = null): array
    {
        $items = InventoryItem::query()
            ->with('location')
            ->whereHas('location', function ($query) {
                $query->where('store_id', $this->store->id)->where('is_active', true);
            })
            ->where('product_id', $productId)
            ->where('variant_id', $variantId)
            ->get();

        return [
            'total_quantity' => $items->sum('quantity'),
            'total_reserved' => $items->sum('reserved_quantity'),
            'total_available' => $items->sum('available_quantity'),
            'locations' => $items->map(function ($item) {
                return [
                    'location_id' => $item->location_id,
                    'location_name' => $item->location->name,
                    'quantity' => $item->quantity,
                    'reserved' => $item->reserved_quantity,
                    'available' => $item->available_quantity,
                    'reorder_point' => $item->reorder_point,
                    'is_low_stock' => $item->isLowStock(),
                ];
            })->toArray(),
        ];
    }
}
