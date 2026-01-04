<?php

declare(strict_types=1);

namespace VodoCommerce\Services;

use VodoCommerce\Models\Store;
use VodoCommerce\Models\TaxZone;
use VodoCommerce\Models\TaxZoneLocation;

class TaxZoneService
{
    public function createZone(Store $store, array $data): TaxZone
    {
        $zone = TaxZone::create([
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

        do_action('commerce.tax_zone.created', $zone);

        return $zone->load('locations');
    }

    public function updateZone(TaxZone $zone, array $data): TaxZone
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

        do_action('commerce.tax_zone.updated', $zone);

        return $zone->fresh('locations');
    }

    public function deleteZone(TaxZone $zone): bool
    {
        do_action('commerce.tax_zone.deleting', $zone);

        $deleted = $zone->delete();

        if ($deleted) {
            do_action('commerce.tax_zone.deleted', $zone->id);
        }

        return $deleted;
    }

    public function addLocation(TaxZone $zone, array $locationData): TaxZoneLocation
    {
        return $zone->locations()->create([
            'country_code' => strtoupper($locationData['country_code']),
            'state_code' => isset($locationData['state_code']) ? strtoupper($locationData['state_code']) : null,
            'city' => $locationData['city'] ?? null,
            'postal_code_pattern' => $locationData['postal_code_pattern'] ?? null,
        ]);
    }

    public function removeLocation(TaxZoneLocation $location): bool
    {
        return $location->delete();
    }

    public function getZonesForStore(Store $store, bool $activeOnly = false)
    {
        $query = TaxZone::where('store_id', $store->id)->with('locations', 'rates');

        if ($activeOnly) {
            $query->active();
        }

        return $query->ordered()->get();
    }

    public function findZoneForAddress(Store $store, array $address): ?TaxZone
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
