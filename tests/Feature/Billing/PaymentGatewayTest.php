<?php

declare(strict_types=1);

namespace Tests\Feature\Billing;

use App\Services\Payment\Gateways\StripeGateway;
use App\Services\Payment\Gateways\MoyasarGateway;
use App\Services\Payment\PaymentManager;
use Tests\TestCase;

class PaymentGatewayTest extends TestCase
{
    public function test_stripe_gateway_has_correct_identifier(): void
    {
        $gateway = new StripeGateway();

        $this->assertEquals('stripe', $gateway->getIdentifier());
        $this->assertEquals('Stripe', $gateway->getName());
    }

    public function test_moyasar_gateway_has_correct_identifier(): void
    {
        $gateway = new MoyasarGateway();

        $this->assertEquals('moyasar', $gateway->getIdentifier());
        $this->assertEquals('Moyasar', $gateway->getName());
    }

    public function test_stripe_supports_multiple_currencies(): void
    {
        $gateway = new StripeGateway();

        $this->assertTrue($gateway->supportsCurrency('USD'));
        $this->assertTrue($gateway->supportsCurrency('SAR'));
        $this->assertTrue($gateway->supportsCurrency('EUR'));
        $this->assertFalse($gateway->supportsCurrency('XYZ'));
    }

    public function test_moyasar_supports_gulf_currencies(): void
    {
        $gateway = new MoyasarGateway();

        $this->assertTrue($gateway->supportsCurrency('SAR'));
        $this->assertTrue($gateway->supportsCurrency('AED'));
        $this->assertTrue($gateway->supportsCurrency('KWD'));
        $this->assertTrue($gateway->supportsCurrency('BHD'));
    }

    public function test_payment_manager_returns_correct_gateway(): void
    {
        $manager = app(PaymentManager::class);

        $stripe = $manager->gateway('stripe');
        $this->assertInstanceOf(StripeGateway::class, $stripe);

        $moyasar = $manager->gateway('moyasar');
        $this->assertInstanceOf(MoyasarGateway::class, $moyasar);
    }

    public function test_payment_manager_selects_moyasar_for_sar(): void
    {
        config(['services.moyasar.secret_key' => 'test_key']);

        $manager = app(PaymentManager::class);
        $gateway = $manager->getGatewayForCurrency('SAR');

        $this->assertEquals('moyasar', $gateway->getIdentifier());
    }

    public function test_payment_manager_selects_stripe_for_usd(): void
    {
        $manager = app(PaymentManager::class);
        $gateway = $manager->getGatewayForCurrency('USD');

        $this->assertEquals('stripe', $gateway->getIdentifier());
    }

    public function test_moyasar_creates_virtual_customer(): void
    {
        $gateway = new MoyasarGateway();

        $result = $gateway->createCustomer([
            'email' => 'test@example.com',
            'name' => 'Test User',
        ]);

        $this->assertTrue($result->success);
        $this->assertStringStartsWith('moy_cust_', $result->customerId);
        $this->assertEquals('test@example.com', $result->email);
    }

    public function test_stripe_gateway_implements_payout_interface(): void
    {
        $manager = app(PaymentManager::class);
        $gateway = $manager->payoutGateway('stripe');

        $this->assertInstanceOf(StripeGateway::class, $gateway);
    }

    public function test_moyasar_does_not_implement_payout_interface(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('does not support payouts');

        $manager = app(PaymentManager::class);
        $manager->payoutGateway('moyasar');
    }

    public function test_available_gateways_returns_all(): void
    {
        $manager = app(PaymentManager::class);
        $gateways = $manager->getAvailableGateways();

        $this->assertArrayHasKey('stripe', $gateways);
        $this->assertArrayHasKey('moyasar', $gateways);
    }
}
