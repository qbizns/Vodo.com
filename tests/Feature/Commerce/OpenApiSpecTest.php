<?php

declare(strict_types=1);

namespace Tests\Feature\Commerce;

use Tests\TestCase;
use VodoCommerce\Api\CommerceApiDocumentation;
use VodoCommerce\Api\CommerceOpenApiGenerator;
use App\Services\Api\ApiRegistry;

class OpenApiSpecTest extends TestCase
{
    protected CommerceOpenApiGenerator $generator;

    protected function setUp(): void
    {
        parent::setUp();

        $apiRegistry = $this->createMock(ApiRegistry::class);
        $this->generator = new CommerceOpenApiGenerator($apiRegistry);
    }

    public function test_generates_valid_openapi_spec(): void
    {
        $spec = $this->generator->generate();

        $this->assertArrayHasKey('openapi', $spec);
        $this->assertEquals('3.0.3', $spec['openapi']);

        $this->assertArrayHasKey('info', $spec);
        $this->assertArrayHasKey('paths', $spec);
        $this->assertArrayHasKey('components', $spec);
    }

    public function test_spec_has_required_info_fields(): void
    {
        $spec = $this->generator->generate();

        $this->assertArrayHasKey('title', $spec['info']);
        $this->assertArrayHasKey('version', $spec['info']);
        $this->assertArrayHasKey('description', $spec['info']);
        $this->assertEquals('Vodo Commerce API', $spec['info']['title']);
    }

    public function test_spec_has_security_schemes(): void
    {
        $spec = $this->generator->generate();

        $this->assertArrayHasKey('securitySchemes', $spec['components']);

        $schemes = $spec['components']['securitySchemes'];
        $this->assertArrayHasKey('bearerAuth', $schemes);
        $this->assertArrayHasKey('apiKeyAuth', $schemes);
        $this->assertArrayHasKey('oAuth2', $schemes);
    }

    public function test_spec_has_product_endpoints(): void
    {
        $spec = $this->generator->generate();

        $this->assertArrayHasKey('/products', $spec['paths']);
        $this->assertArrayHasKey('get', $spec['paths']['/products']);
        $this->assertArrayHasKey('post', $spec['paths']['/products']);

        $this->assertArrayHasKey('/products/{id}', $spec['paths']);
        $this->assertArrayHasKey('get', $spec['paths']['/products/{id}']);
        $this->assertArrayHasKey('put', $spec['paths']['/products/{id}']);
        $this->assertArrayHasKey('delete', $spec['paths']['/products/{id}']);
    }

    public function test_spec_has_order_endpoints(): void
    {
        $spec = $this->generator->generate();

        $this->assertArrayHasKey('/orders', $spec['paths']);
        $this->assertArrayHasKey('get', $spec['paths']['/orders']);
        $this->assertArrayHasKey('post', $spec['paths']['/orders']);

        $this->assertArrayHasKey('/orders/{id}', $spec['paths']);
    }

    public function test_spec_has_cart_endpoints(): void
    {
        $spec = $this->generator->generate();

        $this->assertArrayHasKey('/cart', $spec['paths']);
        $this->assertArrayHasKey('/cart/items', $spec['paths']);
        $this->assertArrayHasKey('/cart/discount', $spec['paths']);
    }

    public function test_spec_has_checkout_endpoints(): void
    {
        $spec = $this->generator->generate();

        $this->assertArrayHasKey('/checkout', $spec['paths']);
        $this->assertArrayHasKey('/checkout/complete', $spec['paths']);
        $this->assertArrayHasKey('/checkout/shipping-rates', $spec['paths']);
    }

    public function test_spec_has_category_endpoints(): void
    {
        $spec = $this->generator->generate();

        $this->assertArrayHasKey('/categories', $spec['paths']);
        $this->assertArrayHasKey('/categories/{id}', $spec['paths']);
    }

    public function test_spec_has_customer_endpoints(): void
    {
        $spec = $this->generator->generate();

        $this->assertArrayHasKey('/customers', $spec['paths']);
        $this->assertArrayHasKey('/customers/{id}', $spec['paths']);
    }

    public function test_spec_has_webhook_endpoints(): void
    {
        $spec = $this->generator->generate();

        $this->assertArrayHasKey('/webhooks', $spec['paths']);
        $this->assertArrayHasKey('/webhooks/events', $spec['paths']);
    }

    public function test_spec_has_store_endpoints(): void
    {
        $spec = $this->generator->generate();

        $this->assertArrayHasKey('/store', $spec['paths']);
        $this->assertArrayHasKey('/store/stats', $spec['paths']);
    }

    public function test_spec_has_schemas(): void
    {
        $spec = $this->generator->generate();

        $schemas = $spec['components']['schemas'];

        $this->assertArrayHasKey('Product', $schemas);
        $this->assertArrayHasKey('Order', $schemas);
        $this->assertArrayHasKey('Cart', $schemas);
        $this->assertArrayHasKey('Customer', $schemas);
        $this->assertArrayHasKey('Category', $schemas);
        $this->assertArrayHasKey('Address', $schemas);
        $this->assertArrayHasKey('Error', $schemas);
    }

    public function test_spec_has_common_responses(): void
    {
        $spec = $this->generator->generate();

        $responses = $spec['components']['responses'];

        $this->assertArrayHasKey('BadRequest', $responses);
        $this->assertArrayHasKey('Unauthorized', $responses);
        $this->assertArrayHasKey('Forbidden', $responses);
        $this->assertArrayHasKey('NotFound', $responses);
        $this->assertArrayHasKey('TooManyRequests', $responses);
    }

    public function test_spec_has_tags(): void
    {
        $spec = $this->generator->generate();

        $this->assertArrayHasKey('tags', $spec);
        $this->assertNotEmpty($spec['tags']);

        $tagNames = array_column($spec['tags'], 'name');
        $this->assertContains('Products', $tagNames);
        $this->assertContains('Orders', $tagNames);
        $this->assertContains('Cart', $tagNames);
        $this->assertContains('Checkout', $tagNames);
        $this->assertContains('Customers', $tagNames);
    }

    public function test_spec_has_servers(): void
    {
        $spec = $this->generator->generate();

        $this->assertArrayHasKey('servers', $spec);
        $this->assertNotEmpty($spec['servers']);

        $this->assertArrayHasKey('url', $spec['servers'][0]);
        $this->assertArrayHasKey('description', $spec['servers'][0]);
    }

    public function test_endpoint_has_operation_details(): void
    {
        $spec = $this->generator->generate();

        $listProducts = $spec['paths']['/products']['get'];

        $this->assertArrayHasKey('operationId', $listProducts);
        $this->assertArrayHasKey('summary', $listProducts);
        $this->assertArrayHasKey('description', $listProducts);
        $this->assertArrayHasKey('tags', $listProducts);
        $this->assertArrayHasKey('responses', $listProducts);
    }

    public function test_endpoint_has_parameters(): void
    {
        $spec = $this->generator->generate();

        $listProducts = $spec['paths']['/products']['get'];

        $this->assertArrayHasKey('parameters', $listProducts);
        $this->assertNotEmpty($listProducts['parameters']);

        // Check that parameters have required fields
        foreach ($listProducts['parameters'] as $param) {
            $this->assertArrayHasKey('name', $param);
            $this->assertArrayHasKey('in', $param);
        }
    }

    public function test_post_endpoint_has_request_body(): void
    {
        $spec = $this->generator->generate();

        $createProduct = $spec['paths']['/products']['post'];

        $this->assertArrayHasKey('requestBody', $createProduct);
        $this->assertArrayHasKey('content', $createProduct['requestBody']);
        $this->assertArrayHasKey('application/json', $createProduct['requestBody']['content']);
    }

    public function test_can_export_as_json(): void
    {
        $json = $this->generator->toJson();

        $this->assertJson($json);

        $decoded = json_decode($json, true);
        $this->assertArrayHasKey('openapi', $decoded);
    }

    public function test_documentation_has_all_endpoints(): void
    {
        $endpoints = CommerceApiDocumentation::getEndpoints();

        $this->assertNotEmpty($endpoints);

        // Count endpoints by category
        $productEndpoints = array_filter($endpoints, fn($e) => str_starts_with($e['name'], 'commerce.products'));
        $orderEndpoints = array_filter($endpoints, fn($e) => str_starts_with($e['name'], 'commerce.orders'));
        $cartEndpoints = array_filter($endpoints, fn($e) => str_starts_with($e['name'], 'commerce.cart'));
        $checkoutEndpoints = array_filter($endpoints, fn($e) => str_starts_with($e['name'], 'commerce.checkout'));
        $categoryEndpoints = array_filter($endpoints, fn($e) => str_starts_with($e['name'], 'commerce.categories'));
        $customerEndpoints = array_filter($endpoints, fn($e) => str_starts_with($e['name'], 'commerce.customers'));

        $this->assertGreaterThanOrEqual(5, count($productEndpoints), 'Should have at least 5 product endpoints');
        $this->assertGreaterThanOrEqual(5, count($orderEndpoints), 'Should have at least 5 order endpoints');
        $this->assertGreaterThanOrEqual(5, count($cartEndpoints), 'Should have at least 5 cart endpoints');
        $this->assertGreaterThanOrEqual(5, count($checkoutEndpoints), 'Should have at least 5 checkout endpoints');
        $this->assertGreaterThanOrEqual(4, count($categoryEndpoints), 'Should have at least 4 category endpoints');
        $this->assertGreaterThanOrEqual(2, count($customerEndpoints), 'Should have at least 2 customer endpoints');
    }

    public function test_endpoint_has_required_fields(): void
    {
        $endpoints = CommerceApiDocumentation::getEndpoints();

        foreach ($endpoints as $endpoint) {
            $this->assertArrayHasKey('name', $endpoint, 'Endpoint must have name');
            $this->assertArrayHasKey('method', $endpoint, 'Endpoint must have method');
            $this->assertArrayHasKey('path', $endpoint, 'Endpoint must have path');
            $this->assertArrayHasKey('handler_class', $endpoint, 'Endpoint must have handler_class');
            $this->assertArrayHasKey('summary', $endpoint, 'Endpoint must have summary');
            $this->assertArrayHasKey('tags', $endpoint, 'Endpoint must have tags');
        }
    }

    public function test_endpoints_have_valid_http_methods(): void
    {
        $validMethods = ['GET', 'POST', 'PUT', 'PATCH', 'DELETE', 'OPTIONS'];
        $endpoints = CommerceApiDocumentation::getEndpoints();

        foreach ($endpoints as $endpoint) {
            $this->assertContains(
                strtoupper($endpoint['method']),
                $validMethods,
                "Invalid HTTP method: {$endpoint['method']} for {$endpoint['name']}"
            );
        }
    }

    public function test_endpoints_have_rate_limits(): void
    {
        $endpoints = CommerceApiDocumentation::getEndpoints();

        foreach ($endpoints as $endpoint) {
            $this->assertArrayHasKey('rate_limit', $endpoint, "Endpoint {$endpoint['name']} should have rate_limit");
            $this->assertIsInt($endpoint['rate_limit'], "Rate limit for {$endpoint['name']} should be integer");
            $this->assertGreaterThan(0, $endpoint['rate_limit'], "Rate limit for {$endpoint['name']} should be > 0");
        }
    }

    public function test_endpoints_have_permissions(): void
    {
        $endpoints = CommerceApiDocumentation::getEndpoints();

        foreach ($endpoints as $endpoint) {
            $this->assertArrayHasKey('permissions', $endpoint, "Endpoint {$endpoint['name']} should have permissions");
            $this->assertIsArray($endpoint['permissions'], "Permissions for {$endpoint['name']} should be array");
        }
    }
}
