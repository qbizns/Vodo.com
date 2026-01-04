<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ShippingZoneLocation extends Model
{
    use HasFactory;

    protected $table = 'commerce_shipping_zone_locations';

    protected $fillable = [
        'zone_id',
        'country_code',
        'state_code',
        'city',
        'postal_code_pattern',
    ];

    // =========================================================================
    // Relationships
    // =========================================================================

    public function zone(): BelongsTo
    {
        return $this->belongsTo(ShippingZone::class, 'zone_id');
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    public function matchesAddress(array $address): bool
    {
        // Check country
        if (isset($address['country_code']) && $address['country_code'] !== $this->country_code) {
            return false;
        }

        // Check state if specified in location
        if ($this->state_code !== null) {
            if (!isset($address['state_code']) || $address['state_code'] !== $this->state_code) {
                return false;
            }
        }

        // Check city if specified in location
        if ($this->city !== null) {
            if (!isset($address['city']) || strcasecmp($address['city'], $this->city) !== 0) {
                return false;
            }
        }

        // Check postal code pattern if specified
        if ($this->postal_code_pattern !== null && isset($address['postal_code'])) {
            if (!preg_match('/' . $this->postal_code_pattern . '/i', $address['postal_code'])) {
                return false;
            }
        }

        return true;
    }

    public function getLocationString(): string
    {
        $parts = [$this->country_code];

        if ($this->state_code) {
            $parts[] = $this->state_code;
        }

        if ($this->city) {
            $parts[] = $this->city;
        }

        if ($this->postal_code_pattern) {
            $parts[] = "Postal: {$this->postal_code_pattern}";
        }

        return implode(', ', $parts);
    }
}
