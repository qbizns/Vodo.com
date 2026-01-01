<?php

declare(strict_types=1);

namespace Tests\Feature\Commerce;

use App\Services\Plugins\ContractRegistry;
use Tests\TestCase;
use VodoCommerce\Contracts\PaymentGatewayContract;
use VodoCommerce\Contracts\ShippingCarrierContract;
use VodoCommerce\Contracts\TaxProviderContract;
use VodoCommerce\Registries\PaymentGatewayRegistry;
use VodoCommerce\Registries\ShippingCarrierRegistry;
use VodoCommerce\Registries\TaxProviderRegistry;

class RegistryIntegrationTest extends TestCase
{
    protected ContractRegistry $contractRegistry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->contractRegistry = new ContractRegistry();
        app()->instance(ContractRegistry::class, $this->contractRegistry);
    }

    // =========================================================================
    // Payment Gateway Registry Tests
    // =========================================================================

    public function test_payment_gateway_registry_registers_with_contract_registry(): void
    {
        $registry = new PaymentGatewayRegistry();

        // Define the contract first
        $this->contractRegistry->defineContract(PaymentGatewayContract::class, 'Payment gateway integration');

        // Mock gateway
        $mockGateway = $this->createMock(PaymentGatewayContract::class);
        $mockGateway->method('getIdentifier')->willReturn('stripe');
        $mockGateway->method('getName')->willReturn('Stripe');
        $mockGateway->method('isEnabled')->willReturn(true);

        $registry->register('stripe', $mockGateway, 'stripe-plugin');

        // Verify local registry
        $this->assertNotNull($registry->get('stripe'));
        $this->assertEquals('Stripe', $registry->get('stripe')->getName());
    }

    public function test_payment_gateway_registry_returns_all_enabled(): void
    {
        $registry = new PaymentGatewayRegistry();

        $enabledGateway = $this->createMock(PaymentGatewayContract::class);
        $enabledGateway->method('getIdentifier')->willReturn('stripe');
        $enabledGateway->method('isEnabled')->willReturn(true);

        $disabledGateway = $this->createMock(PaymentGatewayContract::class);
        $disabledGateway->method('getIdentifier')->willReturn('paypal');
        $disabledGateway->method('isEnabled')->willReturn(false);

        $registry->register('stripe', $enabledGateway, 'stripe-plugin');
        $registry->register('paypal', $disabledGateway, 'paypal-plugin');

        $enabled = $registry->allEnabled();

        $this->assertCount(1, $enabled);
        $this->assertEquals('stripe', $enabled[0]->getIdentifier());
    }

    public function test_payment_gateway_registry_unregisters_from_contract_registry(): void
    {
        $this->contractRegistry->defineContract(PaymentGatewayContract::class, 'Payment gateway');

        $registry = new PaymentGatewayRegistry();

        $mockGateway = $this->createMock(PaymentGatewayContract::class);
        $mockGateway->method('getIdentifier')->willReturn('stripe');

        $registry->register('stripe', $mockGateway, 'stripe-plugin');
        $registry->unregister('stripe');

        $this->assertNull($registry->get('stripe'));
    }

    public function test_payment_gateway_registry_returns_all_gateways(): void
    {
        $registry = new PaymentGatewayRegistry();

        $gateway1 = $this->createMock(PaymentGatewayContract::class);
        $gateway1->method('getIdentifier')->willReturn('stripe');

        $gateway2 = $this->createMock(PaymentGatewayContract::class);
        $gateway2->method('getIdentifier')->willReturn('paypal');

        $registry->register('stripe', $gateway1);
        $registry->register('paypal', $gateway2);

        $all = $registry->all();

        $this->assertCount(2, $all);
        $this->assertArrayHasKey('stripe', $all);
        $this->assertArrayHasKey('paypal', $all);
    }

    // =========================================================================
    // Shipping Carrier Registry Tests
    // =========================================================================

    public function test_shipping_carrier_registry_registers_carrier(): void
    {
        $registry = new ShippingCarrierRegistry();

        $mockCarrier = $this->createMock(ShippingCarrierContract::class);
        $mockCarrier->method('getIdentifier')->willReturn('fedex');
        $mockCarrier->method('getName')->willReturn('FedEx');
        $mockCarrier->method('isEnabled')->willReturn(true);

        $registry->register('fedex', $mockCarrier, 'fedex-plugin');

        $this->assertNotNull($registry->get('fedex'));
        $this->assertEquals('FedEx', $registry->get('fedex')->getName());
    }

    public function test_shipping_carrier_registry_returns_all_enabled(): void
    {
        $registry = new ShippingCarrierRegistry();

        $enabledCarrier = $this->createMock(ShippingCarrierContract::class);
        $enabledCarrier->method('getIdentifier')->willReturn('fedex');
        $enabledCarrier->method('isEnabled')->willReturn(true);

        $disabledCarrier = $this->createMock(ShippingCarrierContract::class);
        $disabledCarrier->method('getIdentifier')->willReturn('ups');
        $disabledCarrier->method('isEnabled')->willReturn(false);

        $registry->register('fedex', $enabledCarrier);
        $registry->register('ups', $disabledCarrier);

        $enabled = $registry->allEnabled();

        $this->assertCount(1, $enabled);
        $this->assertEquals('fedex', $enabled[0]->getIdentifier());
    }

    public function test_shipping_carrier_registry_syncs_with_contract_registry(): void
    {
        $this->contractRegistry->defineContract(ShippingCarrierContract::class, 'Shipping carrier');

        $registry = new ShippingCarrierRegistry();

        $mockCarrier = $this->createMock(ShippingCarrierContract::class);
        $mockCarrier->method('getIdentifier')->willReturn('fedex');

        $registry->register('fedex', $mockCarrier, 'fedex-plugin');

        // Verify it was synced to contract registry
        $implementations = $this->contractRegistry->getImplementations(ShippingCarrierContract::class);
        $this->assertArrayHasKey('fedex', $implementations);
    }

    // =========================================================================
    // Tax Provider Registry Tests
    // =========================================================================

    public function test_tax_provider_registry_registers_provider(): void
    {
        $registry = new TaxProviderRegistry();

        $mockProvider = $this->createMock(TaxProviderContract::class);
        $mockProvider->method('getIdentifier')->willReturn('taxjar');
        $mockProvider->method('getName')->willReturn('TaxJar');
        $mockProvider->method('isEnabled')->willReturn(true);

        $registry->register('taxjar', $mockProvider, 'taxjar-plugin');

        $this->assertNotNull($registry->get('taxjar'));
        $this->assertEquals('TaxJar', $registry->get('taxjar')->getName());
    }

    public function test_tax_provider_registry_returns_default_provider(): void
    {
        $registry = new TaxProviderRegistry();

        $provider1 = $this->createMock(TaxProviderContract::class);
        $provider1->method('getIdentifier')->willReturn('taxjar');
        $provider1->method('isEnabled')->willReturn(true);

        $provider2 = $this->createMock(TaxProviderContract::class);
        $provider2->method('getIdentifier')->willReturn('avalara');
        $provider2->method('isEnabled')->willReturn(true);

        $registry->register('taxjar', $provider1);
        $registry->register('avalara', $provider2);
        $registry->setDefault('avalara');

        $default = $registry->getDefault();

        $this->assertNotNull($default);
        $this->assertEquals('avalara', $default->getIdentifier());
    }

    public function test_tax_provider_registry_returns_first_enabled_when_no_default(): void
    {
        $registry = new TaxProviderRegistry();

        $provider = $this->createMock(TaxProviderContract::class);
        $provider->method('getIdentifier')->willReturn('taxjar');
        $provider->method('isEnabled')->willReturn(true);

        $registry->register('taxjar', $provider);

        $default = $registry->getDefault();

        $this->assertNotNull($default);
        $this->assertEquals('taxjar', $default->getIdentifier());
    }

    public function test_tax_provider_registry_returns_null_when_no_providers(): void
    {
        $registry = new TaxProviderRegistry();

        $default = $registry->getDefault();

        $this->assertNull($default);
    }

    // =========================================================================
    // Cross-Registry Tests
    // =========================================================================

    public function test_registries_are_independent(): void
    {
        $paymentRegistry = new PaymentGatewayRegistry();
        $shippingRegistry = new ShippingCarrierRegistry();
        $taxRegistry = new TaxProviderRegistry();

        $mockPayment = $this->createMock(PaymentGatewayContract::class);
        $mockPayment->method('getIdentifier')->willReturn('stripe');

        $mockShipping = $this->createMock(ShippingCarrierContract::class);
        $mockShipping->method('getIdentifier')->willReturn('fedex');

        $mockTax = $this->createMock(TaxProviderContract::class);
        $mockTax->method('getIdentifier')->willReturn('taxjar');

        $paymentRegistry->register('stripe', $mockPayment);
        $shippingRegistry->register('fedex', $mockShipping);
        $taxRegistry->register('taxjar', $mockTax);

        // Each registry should only have its own items
        $this->assertCount(1, $paymentRegistry->all());
        $this->assertCount(1, $shippingRegistry->all());
        $this->assertCount(1, $taxRegistry->all());

        $this->assertNotNull($paymentRegistry->get('stripe'));
        $this->assertNull($paymentRegistry->get('fedex'));
        $this->assertNull($paymentRegistry->get('taxjar'));
    }

    public function test_registries_handle_missing_contract_registry_gracefully(): void
    {
        // Remove the ContractRegistry binding
        app()->forgetInstance(ContractRegistry::class);

        $registry = new PaymentGatewayRegistry();

        $mockGateway = $this->createMock(PaymentGatewayContract::class);
        $mockGateway->method('getIdentifier')->willReturn('stripe');

        // Should not throw exception
        $registry->register('stripe', $mockGateway);

        $this->assertNotNull($registry->get('stripe'));
    }
}
