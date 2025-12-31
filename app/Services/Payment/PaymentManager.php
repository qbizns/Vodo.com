<?php

declare(strict_types=1);

namespace App\Services\Payment;

use App\Services\Payment\Contracts\PaymentGatewayInterface;
use App\Services\Payment\Contracts\PayoutGatewayInterface;
use App\Services\Payment\Gateways\StripeGateway;
use App\Services\Payment\Gateways\MoyasarGateway;
use Illuminate\Support\Manager;

/**
 * Payment Manager
 *
 * Manages multiple payment gateway drivers and provides a unified interface.
 */
class PaymentManager extends Manager
{
    public function getDefaultDriver(): string
    {
        return config('services.payment.default', 'stripe');
    }

    /**
     * Get a payment gateway instance.
     */
    public function gateway(?string $driver = null): PaymentGatewayInterface
    {
        return $this->driver($driver);
    }

    /**
     * Get a payout gateway instance.
     */
    public function payoutGateway(?string $driver = null): PayoutGatewayInterface
    {
        $gateway = $this->driver($driver);

        if (!$gateway instanceof PayoutGatewayInterface) {
            throw new \InvalidArgumentException("Driver [{$driver}] does not support payouts.");
        }

        return $gateway;
    }

    /**
     * Create Stripe driver.
     */
    protected function createStripeDriver(): StripeGateway
    {
        return new StripeGateway();
    }

    /**
     * Create Moyasar driver.
     */
    protected function createMoyasarDriver(): MoyasarGateway
    {
        return new MoyasarGateway();
    }

    /**
     * Get all available gateways.
     *
     * @return array<string, PaymentGatewayInterface>
     */
    public function getAvailableGateways(): array
    {
        return [
            'stripe' => $this->createStripeDriver(),
            'moyasar' => $this->createMoyasarDriver(),
        ];
    }

    /**
     * Get the best gateway for a currency.
     */
    public function getGatewayForCurrency(string $currency): PaymentGatewayInterface
    {
        $currency = strtoupper($currency);

        // Prefer Moyasar for SAR (Saudi Riyal) as it has better local support
        if ($currency === 'SAR' && config('services.moyasar.secret_key')) {
            return $this->gateway('moyasar');
        }

        // Default to Stripe for other currencies
        return $this->gateway('stripe');
    }
}
