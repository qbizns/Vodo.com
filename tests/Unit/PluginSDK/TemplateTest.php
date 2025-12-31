<?php

declare(strict_types=1);

namespace Tests\Unit\PluginSDK;

use App\Services\PluginSDK\Templates\BasicTemplate;
use App\Services\PluginSDK\Templates\EntityTemplate;
use App\Services\PluginSDK\Templates\ApiTemplate;
use App\Services\PluginSDK\Templates\MarketplaceTemplate;
use App\Services\PluginSDK\Templates\TemplateFactory;
use PHPUnit\Framework\TestCase;
use InvalidArgumentException;

/**
 * Tests for Plugin Templates
 */
class TemplateTest extends TestCase
{
    // =========================================================================
    // Template Factory Tests
    // =========================================================================

    public function test_factory_creates_basic_template(): void
    {
        $template = TemplateFactory::create('basic', 'TestPlugin');

        $this->assertInstanceOf(BasicTemplate::class, $template);
        $this->assertEquals('basic', $template->getType());
    }

    public function test_factory_creates_entity_template(): void
    {
        $template = TemplateFactory::create('entity', 'TestPlugin');

        $this->assertInstanceOf(EntityTemplate::class, $template);
        $this->assertEquals('entity', $template->getType());
    }

    public function test_factory_creates_api_template(): void
    {
        $template = TemplateFactory::create('api', 'TestPlugin');

        $this->assertInstanceOf(ApiTemplate::class, $template);
        $this->assertEquals('api', $template->getType());
    }

    public function test_factory_creates_marketplace_template(): void
    {
        $template = TemplateFactory::create('marketplace', 'TestPlugin');

        $this->assertInstanceOf(MarketplaceTemplate::class, $template);
        $this->assertEquals('marketplace', $template->getType());
    }

    public function test_factory_throws_for_unknown_type(): void
    {
        $this->expectException(InvalidArgumentException::class);

        TemplateFactory::create('unknown', 'TestPlugin');
    }

    public function test_factory_is_case_insensitive(): void
    {
        $template = TemplateFactory::create('BASIC', 'TestPlugin');

        $this->assertInstanceOf(BasicTemplate::class, $template);
    }

    public function test_factory_returns_all_types(): void
    {
        $types = TemplateFactory::getTypes();

        $this->assertContains('basic', $types);
        $this->assertContains('entity', $types);
        $this->assertContains('api', $types);
        $this->assertContains('marketplace', $types);
    }

    public function test_factory_returns_descriptions(): void
    {
        $descriptions = TemplateFactory::getDescriptions();

        $this->assertArrayHasKey('basic', $descriptions);
        $this->assertArrayHasKey('entity', $descriptions);
        $this->assertArrayHasKey('api', $descriptions);
        $this->assertArrayHasKey('marketplace', $descriptions);
    }

    public function test_factory_exists_check(): void
    {
        $this->assertTrue(TemplateFactory::exists('basic'));
        $this->assertTrue(TemplateFactory::exists('ENTITY'));
        $this->assertFalse(TemplateFactory::exists('nonexistent'));
    }

    // =========================================================================
    // Basic Template Tests
    // =========================================================================

    public function test_basic_template_has_correct_structure(): void
    {
        $template = new BasicTemplate('MyPlugin');

        $dirs = $template->getDirectoryStructure();

        $this->assertContains('config', $dirs);
        $this->assertContains('routes', $dirs);
        $this->assertContains('src', $dirs);
        $this->assertContains('tests', $dirs);
    }

    public function test_basic_template_generates_required_files(): void
    {
        $template = new BasicTemplate('MyPlugin');

        $files = $template->getFiles();

        $this->assertArrayHasKey('src/MyPluginPlugin.php', $files);
        $this->assertArrayHasKey('src/MyPluginServiceProvider.php', $files);
        $this->assertArrayHasKey('config/my-plugin.php', $files);
        $this->assertArrayHasKey('plugin.json', $files);
        $this->assertArrayHasKey('composer.json', $files);
    }

    public function test_basic_template_has_default_scopes(): void
    {
        $template = new BasicTemplate('MyPlugin');

        $scopes = $template->getDefaultScopes();

        $this->assertContains('hooks:subscribe', $scopes);
    }

    // =========================================================================
    // Entity Template Tests
    // =========================================================================

    public function test_entity_template_has_crud_structure(): void
    {
        $template = new EntityTemplate('ProductManager');

        $dirs = $template->getDirectoryStructure();

        $this->assertContains('src/Entities', $dirs);
        $this->assertContains('src/Http/Controllers', $dirs);
        $this->assertContains('src/Http/Requests', $dirs);
        $this->assertContains('src/Repositories', $dirs);
        $this->assertContains('database/migrations', $dirs);
    }

    public function test_entity_template_generates_entity_files(): void
    {
        $template = new EntityTemplate('ProductManager', [
            'entity_name' => 'Product',
        ]);

        $files = $template->getFiles();

        $this->assertArrayHasKey('src/Entities/ProductEntity.php', $files);
        $this->assertArrayHasKey('src/Http/Controllers/ProductController.php', $files);
        $this->assertArrayHasKey('src/Http/Requests/StoreProductRequest.php', $files);
        $this->assertArrayHasKey('src/Http/Requests/UpdateProductRequest.php', $files);
        $this->assertArrayHasKey('src/Repositories/ProductRepository.php', $files);
    }

    public function test_entity_template_has_entity_scopes(): void
    {
        $template = new EntityTemplate('ProductManager');

        $scopes = $template->getDefaultScopes();

        $this->assertContains('entities:read', $scopes);
        $this->assertContains('entities:write', $scopes);
    }

    public function test_entity_template_adds_entity_to_manifest(): void
    {
        $template = new EntityTemplate('ProductManager', [
            'entity_name' => 'Product',
        ]);

        $manifest = $template->getManifest();
        $entities = $manifest->getEntities();

        $this->assertNotEmpty($entities);
        $this->assertStringContainsString('product', $entities[0]['name']);
    }

    // =========================================================================
    // API Template Tests
    // =========================================================================

    public function test_api_template_has_api_structure(): void
    {
        $template = new ApiTemplate('PaymentGateway');

        $dirs = $template->getDirectoryStructure();

        $this->assertContains('src/Http/Controllers/Api', $dirs);
        $this->assertContains('src/Http/Resources', $dirs);
        $this->assertContains('src/Http/Middleware', $dirs);
        $this->assertContains('src/Services', $dirs);
        $this->assertContains('src/Webhooks', $dirs);
    }

    public function test_api_template_generates_api_files(): void
    {
        $template = new ApiTemplate('PaymentGateway', [
            'resource_name' => 'Payment',
        ]);

        $files = $template->getFiles();

        $this->assertArrayHasKey('src/Http/Controllers/Api/PaymentController.php', $files);
        $this->assertArrayHasKey('src/Http/Resources/PaymentResource.php', $files);
        $this->assertArrayHasKey('src/Http/Resources/PaymentCollection.php', $files);
        $this->assertArrayHasKey('src/Services/PaymentGatewayApiClient.php', $files);
        $this->assertArrayHasKey('routes/api.php', $files);
    }

    public function test_api_template_has_api_scopes(): void
    {
        $template = new ApiTemplate('PaymentGateway');

        $scopes = $template->getDefaultScopes();

        $this->assertContains('api:read', $scopes);
        $this->assertContains('api:write', $scopes);
        $this->assertContains('network:outbound', $scopes);
    }

    public function test_api_template_adds_endpoints_to_manifest(): void
    {
        $template = new ApiTemplate('PaymentGateway');

        $manifest = $template->getManifest();
        $endpoints = $manifest->getApiEndpoints();

        $this->assertNotEmpty($endpoints);
    }

    public function test_api_template_adds_webhooks_to_manifest(): void
    {
        $template = new ApiTemplate('PaymentGateway');

        $manifest = $template->getManifest();
        $webhooks = $manifest->getWebhooks();

        $this->assertNotEmpty($webhooks);
    }

    // =========================================================================
    // Marketplace Template Tests
    // =========================================================================

    public function test_marketplace_template_has_full_structure(): void
    {
        $template = new MarketplaceTemplate('SuperApp');

        $dirs = $template->getDirectoryStructure();

        $this->assertContains('src/Settings', $dirs);
        $this->assertContains('src/Services', $dirs);
        $this->assertContains('src/Jobs', $dirs);
        $this->assertContains('src/Events', $dirs);
        $this->assertContains('src/Listeners', $dirs);
        $this->assertContains('docs', $dirs);
    }

    public function test_marketplace_template_generates_oauth_files(): void
    {
        $template = new MarketplaceTemplate('SuperApp');

        $files = $template->getFiles();

        $this->assertArrayHasKey('src/Services/OAuthService.php', $files);
        $this->assertArrayHasKey('src/Http/Controllers/OAuthController.php', $files);
    }

    public function test_marketplace_template_generates_settings_files(): void
    {
        $template = new MarketplaceTemplate('SuperApp');

        $files = $template->getFiles();

        $this->assertArrayHasKey('src/Settings/SettingsManager.php', $files);
        $this->assertArrayHasKey('src/Settings/SettingsValidator.php', $files);
        $this->assertArrayHasKey('src/Http/Controllers/SettingsController.php', $files);
    }

    public function test_marketplace_template_generates_billing_files(): void
    {
        $template = new MarketplaceTemplate('SuperApp');

        $files = $template->getFiles();

        $this->assertArrayHasKey('src/Services/BillingService.php', $files);
        $this->assertArrayHasKey('src/Http/Controllers/BillingController.php', $files);
    }

    public function test_marketplace_template_generates_documentation(): void
    {
        $template = new MarketplaceTemplate('SuperApp');

        $files = $template->getFiles();

        $this->assertArrayHasKey('docs/README.md', $files);
        $this->assertArrayHasKey('docs/API.md', $files);
        $this->assertArrayHasKey('docs/CHANGELOG.md', $files);
    }

    public function test_marketplace_template_enables_marketplace_listing(): void
    {
        $template = new MarketplaceTemplate('SuperApp');

        $manifest = $template->getManifest();

        $this->assertTrue($manifest->isMarketplaceListed());
    }

    public function test_marketplace_template_has_settings_schema(): void
    {
        $template = new MarketplaceTemplate('SuperApp');

        $manifest = $template->getManifest();
        $schema = $manifest->getSettingsSchema();

        $this->assertNotEmpty($schema);
        $this->assertArrayHasKey('general', $schema);
    }

    public function test_marketplace_template_supports_pricing_option(): void
    {
        $template = new MarketplaceTemplate('SuperApp', [
            'pricing' => 'subscription',
        ]);

        $manifest = $template->getManifest();

        $this->assertEquals('subscription', $manifest->getPricing());
    }

    // =========================================================================
    // Common Template Tests
    // =========================================================================

    public function test_templates_generate_valid_php_files(): void
    {
        $templates = [
            new BasicTemplate('Test'),
            new EntityTemplate('Test'),
            new ApiTemplate('Test'),
            new MarketplaceTemplate('Test'),
        ];

        foreach ($templates as $template) {
            $files = $template->getFiles();

            foreach ($files as $path => $content) {
                if (str_ends_with($path, '.php')) {
                    // Basic PHP syntax check - should start with <?php
                    $this->assertStringStartsWith('<?php', $content, "File {$path} should start with <?php");
                }
            }
        }
    }

    public function test_templates_generate_valid_json_files(): void
    {
        $templates = [
            new BasicTemplate('Test'),
            new EntityTemplate('Test'),
            new ApiTemplate('Test'),
            new MarketplaceTemplate('Test'),
        ];

        foreach ($templates as $template) {
            $files = $template->getFiles();

            foreach ($files as $path => $content) {
                if (str_ends_with($path, '.json')) {
                    $decoded = json_decode($content, true);
                    $this->assertNotNull($decoded, "File {$path} should be valid JSON");
                }
            }
        }
    }

    public function test_templates_use_correct_namespace(): void
    {
        $template = new BasicTemplate('MyAwesomePlugin');
        $files = $template->getFiles();

        $pluginFile = $files['src/MyAwesomePluginPlugin.php'] ?? '';

        $this->assertStringContainsString('namespace Plugins\\MyAwesomePlugin', $pluginFile);
    }

    public function test_templates_generate_manifest_with_correct_identifier(): void
    {
        $template = new BasicTemplate('MyPlugin');
        $manifest = $template->getManifest();

        $this->assertEquals('my-plugin', $manifest->getIdentifier());
    }

    public function test_templates_pass_options_to_manifest(): void
    {
        $template = new BasicTemplate('MyPlugin', [
            'version' => '2.0.0',
            'description' => 'Custom description',
            'author' => 'Test Author',
        ]);

        $manifest = $template->getManifest();

        $this->assertEquals('2.0.0', $manifest->getVersion());
        $this->assertEquals('Custom description', $manifest->getDescription());
    }
}
