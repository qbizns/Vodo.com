<?php

declare(strict_types=1);

namespace VodoCommerce\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;
use VodoCommerce\Traits\BelongsToStore;

class PaymentMethod extends Model
{
    use BelongsToStore, HasFactory, SoftDeletes;

    protected $table = 'commerce_payment_methods';

    // Payment Method Types
    public const TYPE_ONLINE = 'online';
    public const TYPE_OFFLINE = 'offline';
    public const TYPE_WALLET = 'wallet';

    // Payment Providers
    public const PROVIDER_STRIPE = 'stripe';
    public const PROVIDER_PAYPAL = 'paypal';
    public const PROVIDER_SQUARE = 'square';
    public const PROVIDER_MOYASAR = 'moyasar';
    public const PROVIDER_TABBY = 'tabby';
    public const PROVIDER_TAMARA = 'tamara';
    public const PROVIDER_CUSTOM = 'custom';

    protected $fillable = [
        'store_id',
        'name',
        'slug',
        'type',
        'provider',
        'logo',
        'description',
        'configuration',
        'supported_currencies',
        'supported_countries',
        'supported_payment_types',
        'fees',
        'minimum_amount',
        'maximum_amount',
        'supported_banks',
        'is_active',
        'is_default',
        'display_order',
        'requires_shipping_address',
        'requires_billing_address',
        'webhook_url',
        'webhook_secret',
        'meta',
    ];

    protected function casts(): array
    {
        return [
            'configuration' => 'array',
            'supported_currencies' => 'array',
            'supported_countries' => 'array',
            'supported_payment_types' => 'array',
            'fees' => 'array',
            'minimum_amount' => 'float',
            'maximum_amount' => 'float',
            'supported_banks' => 'array',
            'is_active' => 'boolean',
            'is_default' => 'boolean',
            'display_order' => 'integer',
            'requires_shipping_address' => 'boolean',
            'requires_billing_address' => 'boolean',
            'meta' => 'array',
        ];
    }

    // =========================================================================
    // Relationships
    // =========================================================================

    public function transactions(): HasMany
    {
        return $this->hasMany(Transaction::class, 'payment_method_id');
    }

    // =========================================================================
    // Helper Methods
    // =========================================================================

    /**
     * Check if payment method is online
     */
    public function isOnline(): bool
    {
        return $this->type === self::TYPE_ONLINE;
    }

    /**
     * Check if payment method is offline
     */
    public function isOffline(): bool
    {
        return $this->type === self::TYPE_OFFLINE;
    }

    /**
     * Check if payment method is wallet-based
     */
    public function isWallet(): bool
    {
        return $this->type === self::TYPE_WALLET;
    }

    /**
     * Check if currency is supported
     */
    public function supportsCurrency(string $currency): bool
    {
        if (empty($this->supported_currencies)) {
            return true; // No restrictions
        }

        return in_array(strtoupper($currency), $this->supported_currencies);
    }

    /**
     * Check if country is supported
     */
    public function supportsCountry(string $countryCode): bool
    {
        if (empty($this->supported_countries)) {
            return true; // No restrictions
        }

        return in_array(strtoupper($countryCode), $this->supported_countries);
    }

    /**
     * Check if amount is within limits
     */
    public function isAmountValid(float $amount): bool
    {
        if ($this->minimum_amount && $amount < $this->minimum_amount) {
            return false;
        }

        if ($this->maximum_amount && $amount > $this->maximum_amount) {
            return false;
        }

        return true;
    }

    /**
     * Calculate fees for a given amount
     */
    public function calculateFees(float $amount): array
    {
        $fees = $this->fees ?? [];

        $fixedFee = $fees['fixed'] ?? 0;
        $percentageFee = ($fees['percentage'] ?? 0) / 100;
        $minFee = $fees['min'] ?? 0;
        $maxFee = $fees['max'] ?? null;

        $calculatedFee = $fixedFee + ($amount * $percentageFee);

        if ($minFee && $calculatedFee < $minFee) {
            $calculatedFee = $minFee;
        }

        if ($maxFee && $calculatedFee > $maxFee) {
            $calculatedFee = $maxFee;
        }

        $netAmount = $amount - $calculatedFee;

        return [
            'gross_amount' => $amount,
            'fee_amount' => round($calculatedFee, 2),
            'net_amount' => round($netAmount, 2),
            'fee_breakdown' => [
                'fixed' => $fixedFee,
                'percentage' => $fees['percentage'] ?? 0,
                'calculated' => round($calculatedFee, 2),
            ],
        ];
    }

    /**
     * Get configuration value
     */
    public function getConfig(string $key, mixed $default = null): mixed
    {
        return data_get($this->configuration, $key, $default);
    }

    /**
     * Set configuration value
     */
    public function setConfig(string $key, mixed $value): void
    {
        $config = $this->configuration ?? [];
        data_set($config, $key, $value);
        $this->configuration = $config;
    }

    /**
     * Check if payment method requires specific configuration
     */
    public function isConfigured(): bool
    {
        if ($this->isOffline()) {
            return true; // Offline methods don't need configuration
        }

        // Check if required credentials are present
        $requiredKeys = match ($this->provider) {
            self::PROVIDER_STRIPE => ['publishable_key', 'secret_key'],
            self::PROVIDER_PAYPAL => ['client_id', 'client_secret'],
            self::PROVIDER_SQUARE => ['access_token', 'location_id'],
            default => [],
        };

        foreach ($requiredKeys as $key) {
            if (empty($this->getConfig($key))) {
                return false;
            }
        }

        return true;
    }

    /**
     * Get list of supported banks (for bank transfer methods)
     */
    public function getBanks(): array
    {
        return $this->supported_banks ?? [];
    }

    // =========================================================================
    // Scopes
    // =========================================================================

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeOnline($query)
    {
        return $query->where('type', self::TYPE_ONLINE);
    }

    public function scopeOffline($query)
    {
        return $query->where('type', self::TYPE_OFFLINE);
    }

    public function scopeOrdered($query)
    {
        return $query->orderBy('display_order')->orderBy('name');
    }

    public function scopeForCurrency($query, string $currency)
    {
        return $query->where(function ($q) use ($currency) {
            $q->whereNull('supported_currencies')
                ->orWhereJsonContains('supported_currencies', strtoupper($currency));
        });
    }

    public function scopeForCountry($query, string $countryCode)
    {
        return $query->where(function ($q) use ($countryCode) {
            $q->whereNull('supported_countries')
                ->orWhereJsonContains('supported_countries', strtoupper($countryCode));
        });
    }
}
