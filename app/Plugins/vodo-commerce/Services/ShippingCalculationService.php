<?php

declare(strict_types=1);

namespace VodoCommerce\Services;

use VodoCommerce\Models\ShippingMethod;
use VodoCommerce\Models\ShippingZone;
use VodoCommerce\Models\Store;

class ShippingCalculationService
{
    public function getAvailableShippingOptions(Store $store, array $address, array $cartData): array
    {
        $options = [];

        // Find matching shipping zone
        $zone = $this->findMatchingZone($store, $address);

        if (!$zone) {
            return [];
        }

        // Get active shipping methods
        $methods = ShippingMethod::where('store_id', $store->id)
            ->active()
            ->forOrderAmount($cartData['subtotal'])
            ->get();

        foreach ($methods as $method) {
            // Get rate for this method and zone
            $rate = $method->rates()
                ->where('shipping_zone_id', $zone->id)
                ->forWeight($cartData['total_weight'] ?? 0)
                ->forPrice($cartData['subtotal'])
                ->first();

            if ($rate) {
                $cost = $rate->calculateCost(
                    $cartData['item_count'] ?? 1,
                    $cartData['total_weight'] ?? 0,
                    $cartData['subtotal']
                );

                $options[] = [
                    'method_id' => $method->id,
                    'name' => $method->name,
                    'code' => $method->code,
                    'description' => $method->description,
                    'cost' => $cost,
                    'delivery_estimate' => $method->getDeliveryEstimate(),
                    'is_free' => $cost == 0,
                ];
            }
        }

        return $options;
    }

    public function calculateShippingCost(Store $store, int $methodId, array $address, array $cartData): ?float
    {
        $method = ShippingMethod::where('store_id', $store->id)->find($methodId);

        if (!$method || !$method->is_active) {
            return null;
        }

        $zone = $this->findMatchingZone($store, $address);

        if (!$zone) {
            return null;
        }

        $rate = $method->rates()
            ->where('shipping_zone_id', $zone->id)
            ->forWeight($cartData['total_weight'] ?? 0)
            ->forPrice($cartData['subtotal'])
            ->first();

        if (!$rate) {
            return null;
        }

        return $rate->calculateCost(
            $cartData['item_count'] ?? 1,
            $cartData['total_weight'] ?? 0,
            $cartData['subtotal']
        );
    }

    protected function findMatchingZone(Store $store, array $address): ?ShippingZone
    {
        $zones = ShippingZone::where('store_id', $store->id)
            ->active()
            ->with('locations')
            ->ordered()
            ->get();

        foreach ($zones as $zone) {
            if ($zone->matchesAddress($address)) {
                return $zone;
            }
        }

        return null;
    }

    public function validateAddress(array $address): bool
    {
        $required = ['country_code'];

        foreach ($required as $field) {
            if (!isset($address[$field]) || empty($address[$field])) {
                return false;
            }
        }

        // Validate country code format
        if (!preg_match('/^[A-Z]{2}$/', $address['country_code'])) {
            return false;
        }

        return true;
    }
}
