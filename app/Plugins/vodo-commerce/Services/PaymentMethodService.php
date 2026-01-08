<?php

declare(strict_types=1);

namespace VodoCommerce\Services;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;
use VodoCommerce\Models\PaymentMethod;

class PaymentMethodService
{
    /**
     * Get all payment methods for a store
     */
    public function getAll(int $storeId, bool $activeOnly = false): Collection
    {
        $query = PaymentMethod::where('store_id', $storeId)->ordered();

        if ($activeOnly) {
            $query->active();
        }

        return $query->get();
    }

    /**
     * Get active payment methods for checkout
     */
    public function getAvailableForCheckout(
        int $storeId,
        float $amount,
        string $currency,
        ?string $countryCode = null
    ): Collection {
        $query = PaymentMethod::where('store_id', $storeId)
            ->active()
            ->forCurrency($currency)
            ->ordered();

        if ($countryCode) {
            $query->forCountry($countryCode);
        }

        $methods = $query->get();

        // Filter by amount limits
        return $methods->filter(function (PaymentMethod $method) use ($amount) {
            return $method->isAmountValid($amount) && $method->isConfigured();
        });
    }

    /**
     * Create a new payment method
     */
    public function create(int $storeId, array $data): PaymentMethod
    {
        // Generate slug if not provided
        if (empty($data['slug'])) {
            $data['slug'] = Str::slug($data['name'] ?? '') . '-' . Str::random(6);
        }

        // Ensure unique slug
        $originalSlug = $data['slug'];
        $counter = 1;
        while (PaymentMethod::where('slug', $data['slug'])->exists()) {
            $data['slug'] = $originalSlug . '-' . $counter++;
        }

        $data['store_id'] = $storeId;

        // If set as default, unset others
        if ($data['is_default'] ?? false) {
            $this->unsetDefaultPaymentMethods($storeId);
        }

        return PaymentMethod::create($data);
    }

    /**
     * Update payment method
     */
    public function update(PaymentMethod $paymentMethod, array $data): PaymentMethod
    {
        // If setting as default, unset others
        if (($data['is_default'] ?? false) && !$paymentMethod->is_default) {
            $this->unsetDefaultPaymentMethods($paymentMethod->store_id);
        }

        $paymentMethod->update($data);

        return $paymentMethod->fresh();
    }

    /**
     * Delete payment method
     */
    public function delete(PaymentMethod $paymentMethod): bool
    {
        // Check if it's the default payment method
        if ($paymentMethod->is_default) {
            throw new \RuntimeException('Cannot delete the default payment method. Please set another method as default first.');
        }

        // Check if it has transactions
        if ($paymentMethod->transactions()->exists()) {
            // Soft delete only
            return $paymentMethod->delete();
        }

        // Hard delete if no transactions
        return $paymentMethod->forceDelete();
    }

    /**
     * Activate payment method
     */
    public function activate(PaymentMethod $paymentMethod): PaymentMethod
    {
        if (!$paymentMethod->isConfigured()) {
            throw new \RuntimeException('Payment method is not properly configured.');
        }

        $paymentMethod->update(['is_active' => true]);

        return $paymentMethod->fresh();
    }

    /**
     * Deactivate payment method
     */
    public function deactivate(PaymentMethod $paymentMethod): PaymentMethod
    {
        // Don't allow deactivating the default payment method
        if ($paymentMethod->is_default) {
            throw new \RuntimeException('Cannot deactivate the default payment method.');
        }

        $paymentMethod->update(['is_active' => false]);

        return $paymentMethod->fresh();
    }

    /**
     * Set as default payment method
     */
    public function setAsDefault(PaymentMethod $paymentMethod): PaymentMethod
    {
        if (!$paymentMethod->is_active) {
            throw new \RuntimeException('Cannot set an inactive payment method as default.');
        }

        $this->unsetDefaultPaymentMethods($paymentMethod->store_id);

        $paymentMethod->update(['is_default' => true]);

        return $paymentMethod->fresh();
    }

    /**
     * Update payment method configuration
     */
    public function updateConfiguration(PaymentMethod $paymentMethod, array $configuration): PaymentMethod
    {
        $paymentMethod->configuration = array_merge($paymentMethod->configuration ?? [], $configuration);
        $paymentMethod->save();

        return $paymentMethod->fresh();
    }

    /**
     * Test payment method connection
     */
    public function testConnection(PaymentMethod $paymentMethod): array
    {
        if (!$paymentMethod->isConfigured()) {
            return [
                'success' => false,
                'message' => 'Payment method is not properly configured.',
            ];
        }

        // For now, just validate configuration exists
        // In a real implementation, you would ping the gateway API
        try {
            $requiredKeys = match ($paymentMethod->provider) {
                PaymentMethod::PROVIDER_STRIPE => ['publishable_key', 'secret_key'],
                PaymentMethod::PROVIDER_PAYPAL => ['client_id', 'client_secret'],
                PaymentMethod::PROVIDER_SQUARE => ['access_token', 'location_id'],
                default => [],
            };

            foreach ($requiredKeys as $key) {
                if (empty($paymentMethod->getConfig($key))) {
                    return [
                        'success' => false,
                        'message' => "Missing required configuration: {$key}",
                    ];
                }
            }

            return [
                'success' => true,
                'message' => 'Payment method configuration is valid.',
                'provider' => $paymentMethod->provider,
            ];
        } catch (\Exception $e) {
            return [
                'success' => false,
                'message' => $e->getMessage(),
            ];
        }
    }

    /**
     * Get supported banks for a payment method
     */
    public function getSupportedBanks(PaymentMethod $paymentMethod): array
    {
        return $paymentMethod->getBanks();
    }

    /**
     * Calculate fees for an amount
     */
    public function calculateFees(PaymentMethod $paymentMethod, float $amount): array
    {
        return $paymentMethod->calculateFees($amount);
    }

    /**
     * Reorder payment methods
     */
    public function reorder(int $storeId, array $orderedIds): void
    {
        foreach ($orderedIds as $index => $id) {
            PaymentMethod::where('store_id', $storeId)
                ->where('id', $id)
                ->update(['display_order' => $index]);
        }
    }

    /**
     * Unset default flag from all payment methods in store
     */
    protected function unsetDefaultPaymentMethods(int $storeId): void
    {
        PaymentMethod::where('store_id', $storeId)
            ->where('is_default', true)
            ->update(['is_default' => false]);
    }

    /**
     * Get default payment method for store
     */
    public function getDefault(int $storeId): ?PaymentMethod
    {
        return PaymentMethod::where('store_id', $storeId)
            ->where('is_default', true)
            ->where('is_active', true)
            ->first();
    }

    /**
     * Get payment method by slug
     */
    public function getBySlug(int $storeId, string $slug): ?PaymentMethod
    {
        return PaymentMethod::where('store_id', $storeId)
            ->where('slug', $slug)
            ->first();
    }
}
