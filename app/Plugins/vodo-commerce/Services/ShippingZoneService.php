<?php

declare(strict_types=1);

namespace VodoCommerce\Services;

use VodoCommerce\Models\ShippingZone;
use VodoCommerce\Models\ShippingZoneLocation;
use VodoCommerce\Models\Store;

class ShippingZoneService
{
    public function createZone(Store $store, array $data): ShippingZone
    {
        $zone = ShippingZone::create([
            'store_id' => $store->id,
            'name' => $data['name'],
            'description' => $data['description'] ?? null,
            'is_active' => $data['is_active'] ?? true,
            'priority' => $data['priority'] ?? 0,
        ]);

        // Add locations if provided
        if (isset($data['locations']) && is_array($data['locations'])) {
            foreach ($data['locations'] as $location) {
                $this->addLocation($zone, $location);
            }
        }

        do_action('commerce.shipping_zone.created', $zone);

        return $zone->load('locations');
    }

    public function updateZone(ShippingZone $zone, array $data): ShippingZone
    {
        $zone->update([
            'name' => $data['name'] ?? $zone->name,
            'description' => $data['description'] ?? $zone->description,
            'is_active' => $data['is_active'] ?? $zone->is_active,
            'priority' => $data['priority'] ?? $zone->priority,
        ]);

        // Update locations if provided
        if (isset($data['locations'])) {
            $zone->locations()->delete();
            foreach ($data['locations'] as $location) {
                $this->addLocation($zone, $location);
            }
        }

        do_action('commerce.shipping_zone.updated', $zone);

        return $zone->fresh('locations');
    }

    public function deleteZone(ShippingZone $zone): bool
    {
        do_action('commerce.shipping_zone.deleting', $zone);

        $deleted = $zone->delete();

        if ($deleted) {
            do_action('commerce.shipping_zone.deleted', $zone->id);
        }

        return $deleted;
    }

    public function addLocation(ShippingZone $zone, array $locationData): ShippingZoneLocation
    {
        return $zone->locations()->create([
            'country_code' => strtoupper($locationData['country_code']),
            'state_code' => isset($locationData['state_code']) ? strtoupper($locationData['state_code']) : null,
            'city' => $locationData['city'] ?? null,
            'postal_code_pattern' => $locationData['postal_code_pattern'] ?? null,
        ]);
    }

    public function removeLocation(ShippingZoneLocation $location): bool
    {
        return $location->delete();
    }

    public function getZonesForStore(Store $store, bool $activeOnly = false)
    {
        $query = ShippingZone::where('store_id', $store->id)->with('locations');

        if ($activeOnly) {
            $query->active();
        }

        return $query->ordered()->get();
    }

    public function findZoneForAddress(Store $store, array $address): ?ShippingZone
    {
        $zones = $this->getZonesForStore($store, true);

        foreach ($zones as $zone) {
            if ($zone->matchesAddress($address)) {
                return $zone;
            }
        }

        return null;
    }
}
