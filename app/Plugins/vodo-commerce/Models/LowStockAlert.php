<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use VodoCommerce\Traits\BelongsToStore;

class LowStockAlert extends Model
{
    use BelongsToStore;

    protected $table = 'commerce_low_stock_alerts';

    protected $fillable = [
        'store_id',
        'location_id',
        'product_id',
        'variant_id',
        'threshold',
        'current_quantity',
        'is_resolved',
        'resolved_at',
        'resolved_by_type',
        'resolved_by_id',
        'resolution_notes',
    ];

    protected function casts(): array
    {
        return [
            'threshold' => 'integer',
            'current_quantity' => 'integer',
            'is_resolved' => 'boolean',
            'resolved_at' => 'datetime',
        ];
    }

    public function location(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class, 'location_id');
    }

    public function product(): BelongsTo
    {
        return $this->belongsTo(Product::class);
    }

    public function variant(): BelongsTo
    {
        return $this->belongsTo(ProductVariant::class, 'variant_id');
    }

    public function resolvedBy(): MorphTo
    {
        return $this->morphTo('resolved_by');
    }

    public function resolve(?string $notes = null, ?string $resolvedByType = null, ?int $resolvedById = null): void
    {
        $this->update([
            'is_resolved' => true,
            'resolved_at' => now(),
            'resolved_by_type' => $resolvedByType,
            'resolved_by_id' => $resolvedById,
            'resolution_notes' => $notes,
        ]);
    }

    public function getSeverity(): string
    {
        if ($this->current_quantity === 0) {
            return 'critical';
        }

        $percentageOfThreshold = ($this->current_quantity / $this->threshold) * 100;

        if ($percentageOfThreshold <= 25) {
            return 'high';
        }

        if ($percentageOfThreshold <= 50) {
            return 'medium';
        }

        return 'low';
    }

    public function scopeUnresolved($query)
    {
        return $query->where('is_resolved', false);
    }

    public function scopeResolved($query)
    {
        return $query->where('is_resolved', true);
    }

    public function scopeCritical($query)
    {
        return $query->where('current_quantity', 0);
    }

    public function scopeForProduct($query, int $productId, ?int $variantId = null)
    {
        return $query->where('product_id', $productId)
            ->where('variant_id', $variantId);
    }
}
