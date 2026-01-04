<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use VodoCommerce\Traits\BelongsToStore;

class ShippingZone extends Model
{
    use BelongsToStore, HasFactory;

    protected $table = 'commerce_shipping_zones';

    protected $fillable = [
        'store_id',
        'name',
        'description',
        'is_active',
        'priority',
    ];

    protected function casts(): array
    {
        return [
            'is_active' => 'boolean',
            'priority' => 'integer',
        ];
    }

    // =========================================================================
    // Relationships
    // =========================================================================

    public function store(): BelongsTo
    {
        return $this->belongsTo(Store::class);
    }

    public function locations(): HasMany
    {
        return $this->hasMany(ShippingZoneLocation::class, 'zone_id');
    }

    public function rates(): HasMany
    {
        return $this->hasMany(ShippingRate::class, 'shipping_zone_id');
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('priority', 'desc')->orderBy('name');
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    public function matchesAddress(array $address): bool
    {
        if (!$this->is_active) {
            return false;
        }

        foreach ($this->locations as $location) {
            if ($location->matchesAddress($address)) {
                return true;
            }
        }

        return false;
    }

    public function activate(): bool
    {
        $this->is_active = true;
        return $this->save();
    }

    public function deactivate(): bool
    {
        $this->is_active = false;
        return $this->save();
    }
}
