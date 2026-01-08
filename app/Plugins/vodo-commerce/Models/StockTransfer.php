<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use VodoCommerce\Traits\BelongsToStore;

class StockTransfer extends Model
{
    use BelongsToStore;

    protected $table = 'commerce_stock_transfers';

    public const STATUS_PENDING = 'pending';
    public const STATUS_IN_TRANSIT = 'in_transit';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    protected $fillable = [
        'store_id',
        'transfer_number',
        'from_location_id',
        'to_location_id',
        'status',
        'notes',
        'requested_by_type',
        'requested_by_id',
        'requested_at',
        'approved_by_type',
        'approved_by_id',
        'approved_at',
        'shipped_at',
        'received_at',
        'cancelled_at',
        'cancellation_reason',
        'tracking_number',
        'carrier',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'requested_at' => 'datetime',
            'approved_at' => 'datetime',
            'shipped_at' => 'datetime',
            'received_at' => 'datetime',
            'cancelled_at' => 'datetime',
            'meta' => 'array',
        ];
    }

    public function fromLocation(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class, 'from_location_id');
    }

    public function toLocation(): BelongsTo
    {
        return $this->belongsTo(InventoryLocation::class, 'to_location_id');
    }

    public function items(): HasMany
    {
        return $this->hasMany(StockTransferItem::class, 'transfer_id');
    }

    public function requestedBy(): MorphTo
    {
        return $this->morphTo('requested_by');
    }

    public function approvedBy(): MorphTo
    {
        return $this->morphTo('approved_by');
    }

    public function approve(?string $approvedByType = null, ?int $approvedById = null): void
    {
        $this->update([
            'approved_by_type' => $approvedByType,
            'approved_by_id' => $approvedById,
            'approved_at' => now(),
        ]);
    }

    public function ship(?string $trackingNumber = null, ?string $carrier = null): void
    {
        $this->update([
            'status' => self::STATUS_IN_TRANSIT,
            'shipped_at' => now(),
            'tracking_number' => $trackingNumber,
            'carrier' => $carrier,
        ]);

        // Create outbound stock movements
        foreach ($this->items as $item) {
            StockMovement::create([
                'store_id' => $this->store_id,
                'location_id' => $this->from_location_id,
                'product_id' => $item->product_id,
                'variant_id' => $item->variant_id,
                'type' => StockMovement::TYPE_TRANSFER_OUT,
                'quantity' => $item->quantity_shipped,
                'reference_type' => self::class,
                'reference_id' => $this->id,
                'reason' => "Stock transfer #{$this->transfer_number} to {$this->toLocation->name}",
            ]);
        }
    }

    public function receive(array $receivedQuantities = []): void
    {
        $this->update([
            'status' => self::STATUS_COMPLETED,
            'received_at' => now(),
        ]);

        // Update received quantities and create inbound stock movements
        foreach ($this->items as $item) {
            $receivedQty = $receivedQuantities[$item->id] ?? $item->quantity_shipped;
            $item->update(['quantity_received' => $receivedQty]);

            StockMovement::create([
                'store_id' => $this->store_id,
                'location_id' => $this->to_location_id,
                'product_id' => $item->product_id,
                'variant_id' => $item->variant_id,
                'type' => StockMovement::TYPE_TRANSFER_IN,
                'quantity' => $receivedQty,
                'reference_type' => self::class,
                'reference_id' => $this->id,
                'reason' => "Stock transfer #{$this->transfer_number} from {$this->fromLocation->name}",
            ]);
        }
    }

    public function cancel(string $reason): void
    {
        $this->update([
            'status' => self::STATUS_CANCELLED,
            'cancelled_at' => now(),
            'cancellation_reason' => $reason,
        ]);
    }

    public function canBeShipped(): bool
    {
        return $this->status === self::STATUS_PENDING && $this->approved_at !== null;
    }

    public function canBeReceived(): bool
    {
        return $this->status === self::STATUS_IN_TRANSIT;
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, [self::STATUS_PENDING, self::STATUS_IN_TRANSIT]);
    }

    public function scopePending($query)
    {
        return $query->where('status', self::STATUS_PENDING);
    }

    public function scopeInTransit($query)
    {
        return $query->where('status', self::STATUS_IN_TRANSIT);
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    public function scopeCancelled($query)
    {
        return $query->where('status', self::STATUS_CANCELLED);
    }
}
