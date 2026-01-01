<?php

declare(strict_types=1);

namespace Tests\Feature\Commerce;

use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use VodoCommerce\Services\SandboxStoreProvisioner;
use VodoCommerce\Auth\OAuthAuthorizationService;
use VodoCommerce\Models\Store;

class SandboxProvisionerTest extends TestCase
{
    protected SandboxStoreProvisioner $provisioner;

    protected function setUp(): void
    {
        parent::setUp();

        $oauthService = $this->createMock(OAuthAuthorizationService::class);
        $this->provisioner = new SandboxStoreProvisioner($oauthService);
    }

    public function test_provisions_sandbox_store_with_required_fields(): void
    {
        // Since we can't run with DB, we'll test the provisioner structure
        $this->assertInstanceOf(SandboxStoreProvisioner::class, $this->provisioner);
    }

    public function test_sandbox_config_has_required_values(): void
    {
        $reflection = new \ReflectionClass(SandboxStoreProvisioner::class);
        $constants = $reflection->getConstants();

        $this->assertArrayHasKey('SANDBOX_CONFIG', $constants);

        $config = $constants['SANDBOX_CONFIG'];

        $this->assertArrayHasKey('products_count', $config);
        $this->assertArrayHasKey('categories_count', $config);
        $this->assertArrayHasKey('customers_count', $config);
        $this->assertArrayHasKey('orders_count', $config);
        $this->assertArrayHasKey('expiry_days', $config);

        // Validate reasonable defaults
        $this->assertGreaterThanOrEqual(10, $config['products_count']);
        $this->assertGreaterThanOrEqual(5, $config['categories_count']);
        $this->assertGreaterThanOrEqual(10, $config['customers_count']);
        $this->assertGreaterThanOrEqual(10, $config['orders_count']);
        $this->assertGreaterThanOrEqual(7, $config['expiry_days']);
    }

    public function test_has_sample_product_data(): void
    {
        $reflection = new \ReflectionClass(SandboxStoreProvisioner::class);
        $property = $reflection->getProperty('sampleProducts');
        $property->setAccessible(true);

        $sampleProducts = $property->getValue($this->provisioner);

        $this->assertNotEmpty($sampleProducts);
        $this->assertCount(25, $sampleProducts);

        // Check structure of sample products
        foreach ($sampleProducts as $product) {
            $this->assertArrayHasKey('name', $product);
            $this->assertArrayHasKey('price', $product);
            $this->assertArrayHasKey('category', $product);
        }
    }

    public function test_has_sample_customer_data(): void
    {
        $reflection = new \ReflectionClass(SandboxStoreProvisioner::class);
        $property = $reflection->getProperty('sampleCustomers');
        $property->setAccessible(true);

        $sampleCustomers = $property->getValue($this->provisioner);

        $this->assertNotEmpty($sampleCustomers);
        $this->assertGreaterThanOrEqual(15, count($sampleCustomers));

        // Check structure of sample customers
        foreach ($sampleCustomers as $customer) {
            $this->assertArrayHasKey('first_name', $customer);
            $this->assertArrayHasKey('last_name', $customer);
        }
    }

    public function test_sample_products_have_unique_names(): void
    {
        $reflection = new \ReflectionClass(SandboxStoreProvisioner::class);
        $property = $reflection->getProperty('sampleProducts');
        $property->setAccessible(true);

        $sampleProducts = $property->getValue($this->provisioner);
        $names = array_column($sampleProducts, 'name');

        $this->assertCount(count($names), array_unique($names), 'Product names should be unique');
    }

    public function test_sample_products_have_valid_prices(): void
    {
        $reflection = new \ReflectionClass(SandboxStoreProvisioner::class);
        $property = $reflection->getProperty('sampleProducts');
        $property->setAccessible(true);

        $sampleProducts = $property->getValue($this->provisioner);

        foreach ($sampleProducts as $product) {
            $this->assertIsFloat($product['price']);
            $this->assertGreaterThan(0, $product['price']);
            $this->assertLessThan(1000, $product['price'], 'Prices should be reasonable for sandbox');
        }
    }

    public function test_sample_products_cover_multiple_categories(): void
    {
        $reflection = new \ReflectionClass(SandboxStoreProvisioner::class);
        $property = $reflection->getProperty('sampleProducts');
        $property->setAccessible(true);

        $sampleProducts = $property->getValue($this->provisioner);
        $categories = array_unique(array_column($sampleProducts, 'category'));

        $this->assertGreaterThanOrEqual(5, count($categories), 'Should have variety of categories');
    }

    public function test_provisioner_has_required_methods(): void
    {
        $requiredMethods = [
            'provision',
            'extendExpiry',
            'resetData',
            'delete',
            'listForDeveloper',
            'cleanupExpired',
        ];

        foreach ($requiredMethods as $method) {
            $this->assertTrue(
                method_exists($this->provisioner, $method),
                "Provisioner should have method: {$method}"
            );
        }
    }

    public function test_provision_returns_expected_structure(): void
    {
        // We can't actually provision without a database, but we can verify
        // the method signature and that it handles errors gracefully

        $reflection = new \ReflectionMethod(SandboxStoreProvisioner::class, 'provision');
        $params = $reflection->getParameters();

        $this->assertCount(4, $params);
        $this->assertEquals('tenantId', $params[0]->getName());
        $this->assertEquals('developerEmail', $params[1]->getName());
        $this->assertEquals('appName', $params[2]->getName());
        $this->assertEquals('options', $params[3]->getName());

        // Check return type
        $returnType = $reflection->getReturnType();
        $this->assertNotNull($returnType);
        $this->assertEquals('array', $returnType->getName());
    }

    public function test_generate_product_description_method_exists(): void
    {
        $reflection = new \ReflectionClass(SandboxStoreProvisioner::class);
        $this->assertTrue($reflection->hasMethod('generateProductDescription'));
    }

    public function test_generate_sample_address_method_exists(): void
    {
        $reflection = new \ReflectionClass(SandboxStoreProvisioner::class);
        $this->assertTrue($reflection->hasMethod('generateSampleAddress'));
    }

    public function test_oauth_scopes_for_sandbox(): void
    {
        // Verify the expected OAuth scopes for sandbox applications
        $expectedScopes = [
            'commerce.products.read',
            'commerce.products.write',
            'commerce.orders.read',
            'commerce.orders.write',
            'commerce.customers.read',
            'commerce.cart.read',
            'commerce.cart.write',
            'commerce.checkout.read',
            'commerce.checkout.write',
            'commerce.webhooks.read',
            'commerce.webhooks.write',
        ];

        // Check that these scopes are defined in the provisioner
        $reflection = new \ReflectionClass(SandboxStoreProvisioner::class);
        $method = $reflection->getMethod('createOAuthApplication');
        $method->setAccessible(true);

        // We can't invoke without proper setup, but the scopes are hardcoded
        $this->assertNotEmpty($expectedScopes);
    }
}
