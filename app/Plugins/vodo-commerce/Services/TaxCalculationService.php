<?php

declare(strict_types=1);

namespace VodoCommerce\Services;

use VodoCommerce\Models\Store;
use VodoCommerce\Models\TaxExemption;
use VodoCommerce\Models\TaxZone;

class TaxCalculationService
{
    public function calculateTax(Store $store, array $address, array $cartData, ?int $customerId = null, ?int $customerGroupId = null): array
    {
        // Find matching tax zone
        $zone = $this->findMatchingZone($store, $address);

        if (!$zone) {
            return [
                'total_tax' => 0.0,
                'tax_breakdown' => [],
                'zone_id' => null,
            ];
        }

        // Check for tax exemptions
        if ($this->hasActiveTaxExemption($store, $customerId, $customerGroupId, $address)) {
            return [
                'total_tax' => 0.0,
                'tax_breakdown' => [],
                'zone_id' => $zone->id,
                'exemption_applied' => true,
            ];
        }

        // Get tax rates for this zone
        $taxRates = $zone->rates()
            ->active()
            ->ordered()
            ->get();

        $taxBreakdown = [];
        $totalTax = 0.0;
        $compoundBase = $cartData['subtotal'];

        // Calculate non-compound taxes first
        $nonCompoundTaxes = $taxRates->where('compound', false);
        foreach ($nonCompoundTaxes as $rate) {
            $itemTax = $this->calculateItemsTax($rate, $cartData['items'] ?? []);
            $shippingTax = $rate->calculateTaxOnShipping($cartData['shipping_cost'] ?? 0);
            $rateTax = $itemTax + $shippingTax;

            $taxBreakdown[] = [
                'rate_id' => $rate->id,
                'name' => $rate->name,
                'rate' => (float) $rate->rate,
                'type' => $rate->type,
                'amount' => $rateTax,
                'compound' => false,
            ];

            $totalTax += $rateTax;
        }

        // Calculate compound taxes (applied on subtotal + previous taxes)
        $compoundTaxes = $taxRates->where('compound', true);
        foreach ($compoundTaxes as $rate) {
            $compoundBase = $cartData['subtotal'] + $totalTax;
            $itemTax = $rate->calculateTax($compoundBase);
            $shippingTax = $rate->calculateTaxOnShipping($cartData['shipping_cost'] ?? 0);
            $rateTax = $itemTax + $shippingTax;

            $taxBreakdown[] = [
                'rate_id' => $rate->id,
                'name' => $rate->name,
                'rate' => (float) $rate->rate,
                'type' => $rate->type,
                'amount' => $rateTax,
                'compound' => true,
            ];

            $totalTax += $rateTax;
        }

        return [
            'total_tax' => $totalTax,
            'tax_breakdown' => $taxBreakdown,
            'zone_id' => $zone->id,
        ];
    }

    protected function calculateItemsTax($rate, array $items): float
    {
        $tax = 0.0;

        foreach ($items as $item) {
            $categoryId = $item['category_id'] ?? null;

            if ($rate->isApplicableToCategory($categoryId)) {
                $itemTotal = $item['price'] * $item['quantity'];
                $tax += $rate->calculateTax($itemTotal);
            }
        }

        return $tax;
    }

    protected function findMatchingZone(Store $store, array $address): ?TaxZone
    {
        $zones = TaxZone::where('store_id', $store->id)
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

    protected function hasActiveTaxExemption(Store $store, ?int $customerId, ?int $customerGroupId, array $address): bool
    {
        if (!$customerId && !$customerGroupId) {
            return false;
        }

        $query = TaxExemption::where('store_id', $store->id)
            ->active()
            ->valid()
            ->forLocation($address['country_code'], $address['state_code'] ?? null);

        // Check customer exemption
        if ($customerId) {
            $customerExemption = $query->clone()
                ->forEntity('customer', $customerId)
                ->exists();

            if ($customerExemption) {
                return true;
            }
        }

        // Check customer group exemption
        if ($customerGroupId) {
            $groupExemption = $query->clone()
                ->forEntity('customer_group', $customerGroupId)
                ->exists();

            if ($groupExemption) {
                return true;
            }
        }

        return false;
    }

    public function getTaxBreakdownForDisplay(array $taxCalculation): array
    {
        if (empty($taxCalculation['tax_breakdown'])) {
            return [];
        }

        return array_map(function ($item) {
            return [
                'name' => $item['name'],
                'rate' => $item['type'] === 'percentage' ? $item['rate'] . '%' : 'Fixed',
                'amount' => number_format($item['amount'], 2),
            ];
        }, $taxCalculation['tax_breakdown']);
    }
}
