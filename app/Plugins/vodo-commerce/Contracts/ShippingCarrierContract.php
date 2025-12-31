<?php

declare(strict_types=1);

namespace VodoCommerce\Contracts;

use VodoCommerce\Models\Order;

/**
 * Shipping Carrier Contract
 *
 * Defines the interface that shipping carrier plugins must implement
 * to integrate with the commerce plugin.
 */
interface ShippingCarrierContract
{
    /**
     * Get the carrier name.
     */
    public function getName(): string;

    /**
     * Get the carrier slug/identifier.
     */
    public function getSlug(): string;

    /**
     * Get the carrier logo/icon.
     */
    public function getIcon(): ?string;

    /**
     * Check if the carrier is available.
     */
    public function isAvailable(): bool;

    /**
     * Check if the carrier ships to a destination.
     */
    public function shipsTo(ShippingAddress $destination): bool;

    /**
     * Get available shipping rates for an order.
     *
     * @param ShippingAddress $origin Origin address
     * @param ShippingAddress $destination Destination address
     * @param array $items Items to ship (weight, dimensions, quantity)
     * @param array $options Additional options
     * @return ShippingRate[]
     */
    public function getRates(
        ShippingAddress $origin,
        ShippingAddress $destination,
        array $items,
        array $options = []
    ): array;

    /**
     * Create a shipment/label.
     *
     * @param Order $order The order to ship
     * @param string $rateId The selected rate ID
     * @param array $options Additional options
     * @return Shipment
     */
    public function createShipment(Order $order, string $rateId, array $options = []): Shipment;

    /**
     * Get shipment tracking information.
     *
     * @param string $trackingNumber Tracking number
     * @return TrackingInfo
     */
    public function trackShipment(string $trackingNumber): TrackingInfo;

    /**
     * Cancel a shipment.
     *
     * @param string $shipmentId Shipment ID
     * @return bool
     */
    public function cancelShipment(string $shipmentId): bool;

    /**
     * Get configuration fields for the admin.
     */
    public function getConfigurationFields(): array;
}

/**
 * Shipping Address - Represents a shipping address.
 */
class ShippingAddress
{
    public function __construct(
        public readonly string $country,
        public readonly ?string $state = null,
        public readonly ?string $city = null,
        public readonly ?string $postalCode = null,
        public readonly ?string $address1 = null,
        public readonly ?string $address2 = null,
        public readonly ?string $name = null,
        public readonly ?string $company = null,
        public readonly ?string $phone = null
    ) {}

    public static function fromArray(array $data): self
    {
        return new self(
            country: $data['country'] ?? '',
            state: $data['state'] ?? null,
            city: $data['city'] ?? null,
            postalCode: $data['postal_code'] ?? $data['postalCode'] ?? null,
            address1: $data['address1'] ?? $data['address_1'] ?? null,
            address2: $data['address2'] ?? $data['address_2'] ?? null,
            name: $data['name'] ?? null,
            company: $data['company'] ?? null,
            phone: $data['phone'] ?? null
        );
    }

    public function toArray(): array
    {
        return [
            'name' => $this->name,
            'company' => $this->company,
            'address1' => $this->address1,
            'address2' => $this->address2,
            'city' => $this->city,
            'state' => $this->state,
            'postal_code' => $this->postalCode,
            'country' => $this->country,
            'phone' => $this->phone,
        ];
    }
}

/**
 * Shipping Rate - Represents a shipping rate option.
 */
class ShippingRate
{
    public function __construct(
        public readonly string $id,
        public readonly string $name,
        public readonly int $amount,
        public readonly string $currency,
        public readonly ?string $carrierSlug = null,
        public readonly ?string $serviceLevel = null,
        public readonly ?int $estimatedDaysMin = null,
        public readonly ?int $estimatedDaysMax = null,
        public readonly ?string $deliveryDate = null,
        public readonly array $metadata = []
    ) {}

    public function getFormattedPrice(): string
    {
        return number_format($this->amount / 100, 2) . ' ' . strtoupper($this->currency);
    }

    public function getDeliveryEstimate(): ?string
    {
        if ($this->estimatedDaysMin && $this->estimatedDaysMax) {
            if ($this->estimatedDaysMin === $this->estimatedDaysMax) {
                return "{$this->estimatedDaysMin} day(s)";
            }
            return "{$this->estimatedDaysMin}-{$this->estimatedDaysMax} days";
        }

        if ($this->deliveryDate) {
            return $this->deliveryDate;
        }

        return null;
    }

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'name' => $this->name,
            'amount' => $this->amount,
            'currency' => $this->currency,
            'carrier_slug' => $this->carrierSlug,
            'service_level' => $this->serviceLevel,
            'estimated_days_min' => $this->estimatedDaysMin,
            'estimated_days_max' => $this->estimatedDaysMax,
            'delivery_date' => $this->deliveryDate,
            'delivery_estimate' => $this->getDeliveryEstimate(),
            'metadata' => $this->metadata,
        ];
    }
}

/**
 * Shipment - Represents a created shipment.
 */
class Shipment
{
    public function __construct(
        public readonly string $id,
        public readonly string $status,
        public readonly ?string $trackingNumber = null,
        public readonly ?string $trackingUrl = null,
        public readonly ?string $labelUrl = null,
        public readonly ?string $carrierSlug = null,
        public readonly ?string $serviceName = null,
        public readonly ?string $estimatedDelivery = null,
        public readonly array $metadata = []
    ) {}

    public function toArray(): array
    {
        return [
            'id' => $this->id,
            'status' => $this->status,
            'tracking_number' => $this->trackingNumber,
            'tracking_url' => $this->trackingUrl,
            'label_url' => $this->labelUrl,
            'carrier_slug' => $this->carrierSlug,
            'service_name' => $this->serviceName,
            'estimated_delivery' => $this->estimatedDelivery,
            'metadata' => $this->metadata,
        ];
    }
}

/**
 * Tracking Info - Represents shipment tracking information.
 */
class TrackingInfo
{
    public function __construct(
        public readonly string $trackingNumber,
        public readonly string $status,
        public readonly ?string $carrierSlug = null,
        public readonly ?string $estimatedDelivery = null,
        public readonly array $events = [],
        public readonly array $metadata = []
    ) {}

    public function isDelivered(): bool
    {
        return $this->status === 'delivered';
    }

    public function isInTransit(): bool
    {
        return $this->status === 'in_transit';
    }

    public function toArray(): array
    {
        return [
            'tracking_number' => $this->trackingNumber,
            'status' => $this->status,
            'carrier_slug' => $this->carrierSlug,
            'estimated_delivery' => $this->estimatedDelivery,
            'events' => $this->events,
            'metadata' => $this->metadata,
        ];
    }
}
