<?php

declare(strict_types=1);

namespace Tests\Unit\PluginSDK;

use App\Enums\PluginScope;
use App\Services\PluginSDK\PluginManifest;
use PHPUnit\Framework\TestCase;

/**
 * Tests for PluginManifest
 */
class PluginManifestTest extends TestCase
{
    public function test_can_create_manifest_with_defaults(): void
    {
        $manifest = PluginManifest::create('TestPlugin', 'test-plugin');

        $this->assertEquals('test-plugin', $manifest->getIdentifier());
        $this->assertEquals('TestPlugin', $manifest->getName());
        $this->assertEquals('1.0.0', $manifest->getVersion());
    }

    public function test_can_create_manifest_with_options(): void
    {
        $manifest = PluginManifest::create('MyPlugin', 'my-plugin', [
            'version' => '2.0.0',
            'description' => 'My awesome plugin',
            'author' => 'John Doe',
        ]);

        $this->assertEquals('2.0.0', $manifest->getVersion());
        $this->assertEquals('My awesome plugin', $manifest->getDescription());
        $this->assertEquals('John Doe', $manifest->getAuthorName());
    }

    public function test_can_add_scopes(): void
    {
        $manifest = PluginManifest::create('Test', 'test');

        $manifest->addScope('entities:read');
        $manifest->addScope('entities:write');

        $scopes = $manifest->getScopes();

        $this->assertContains('entities:read', $scopes);
        $this->assertContains('entities:write', $scopes);
    }

    public function test_does_not_duplicate_scopes(): void
    {
        $manifest = PluginManifest::create('Test', 'test');

        $manifest->addScope('entities:read');
        $manifest->addScope('entities:read');

        $scopes = $manifest->getScopes();

        $this->assertCount(2, $scopes); // Default + added (no duplicate)
    }

    public function test_can_add_entity(): void
    {
        $manifest = PluginManifest::create('Test', 'test');

        $manifest->addEntity([
            'name' => 'test.product',
            'label' => 'Product',
            'table' => 'test_products',
        ]);

        $entities = $manifest->getEntities();

        $this->assertCount(1, $entities);
        $this->assertEquals('test.product', $entities[0]['name']);
    }

    public function test_can_add_hook(): void
    {
        $manifest = PluginManifest::create('Test', 'test');

        $manifest->addHook([
            'name' => 'test.created',
            'type' => 'action',
            'description' => 'Fired when test is created',
        ]);

        $hooks = $manifest->getHooks();

        $this->assertCount(1, $hooks);
        $this->assertEquals('test.created', $hooks[0]['name']);
    }

    public function test_can_add_api_endpoint(): void
    {
        $manifest = PluginManifest::create('Test', 'test');

        $manifest->addApiEndpoint([
            'path' => '/api/test/items',
            'method' => 'GET',
            'description' => 'List items',
        ]);

        $endpoints = $manifest->getApiEndpoints();

        $this->assertCount(1, $endpoints);
        $this->assertEquals('/api/test/items', $endpoints[0]['path']);
    }

    public function test_can_add_dependency(): void
    {
        $manifest = PluginManifest::create('Test', 'test');

        $manifest->addDependency('core-plugin', '>=1.0.0');

        $dependencies = $manifest->getDependencies();

        $this->assertArrayHasKey('core-plugin', $dependencies);
        $this->assertEquals('>=1.0.0', $dependencies['core-plugin']);
    }

    public function test_validates_required_fields(): void
    {
        $manifest = new PluginManifest([]);

        $isValid = $manifest->validate();

        $this->assertFalse($isValid);
        $this->assertNotEmpty($manifest->getErrors());
    }

    public function test_validates_identifier_format(): void
    {
        $manifest = new PluginManifest([
            'identifier' => 'Invalid_Identifier',
            'name' => 'Test',
            'version' => '1.0.0',
        ]);

        $manifest->validate();

        $errors = $manifest->getErrors();
        $this->assertTrue(
            collect($errors)->contains(fn($e) => str_contains($e, 'identifier'))
        );
    }

    public function test_validates_version_format(): void
    {
        $manifest = new PluginManifest([
            'identifier' => 'test',
            'name' => 'Test',
            'version' => 'invalid',
        ]);

        $manifest->validate();

        $warnings = $manifest->getWarnings();
        $this->assertTrue(
            collect($warnings)->contains(fn($w) => str_contains($w, 'semantic versioning'))
        );
    }

    public function test_detects_dangerous_scopes_in_wrong_section(): void
    {
        $manifest = new PluginManifest([
            'identifier' => 'test',
            'name' => 'Test',
            'version' => '1.0.0',
            'permissions' => [
                'scopes' => ['system:admin'], // This is dangerous
            ],
        ]);

        $manifest->validate();

        $errors = $manifest->getErrors();
        $this->assertTrue(
            collect($errors)->contains(fn($e) => str_contains($e, 'dangerous_scopes'))
        );
    }

    public function test_warns_about_dangerous_scopes(): void
    {
        $manifest = new PluginManifest([
            'identifier' => 'test',
            'name' => 'Test',
            'version' => '1.0.0',
            'permissions' => [
                'scopes' => [],
                'dangerous_scopes' => ['system:admin'],
            ],
        ]);

        $manifest->validate();

        $warnings = $manifest->getWarnings();
        $this->assertTrue(
            collect($warnings)->contains(fn($w) => str_contains($w, 'dangerous scope'))
        );
    }

    public function test_validates_marketplace_requirements(): void
    {
        $manifest = new PluginManifest([
            'identifier' => 'test',
            'name' => 'Test',
            'version' => '1.0.0',
            'description' => 'Short', // Too short for marketplace
            'marketplace' => [
                'listed' => true,
            ],
        ]);

        $manifest->validate();

        $errors = $manifest->getErrors();
        $this->assertTrue(
            collect($errors)->contains(fn($e) => str_contains($e, 'description'))
        );
    }

    public function test_can_convert_to_json(): void
    {
        $manifest = PluginManifest::create('Test', 'test');

        $json = $manifest->toJson();

        $this->assertJson($json);

        $decoded = json_decode($json, true);
        $this->assertEquals('test', $decoded['identifier']);
    }

    public function test_can_convert_to_array(): void
    {
        $manifest = PluginManifest::create('Test', 'test');

        $array = $manifest->toArray();

        $this->assertIsArray($array);
        $this->assertEquals('test', $array['identifier']);
    }

    public function test_can_get_and_set_values(): void
    {
        $manifest = PluginManifest::create('Test', 'test');

        $manifest->set('custom.key', 'value');

        $this->assertEquals('value', $manifest->get('custom.key'));
    }

    public function test_get_returns_default_for_missing_keys(): void
    {
        $manifest = PluginManifest::create('Test', 'test');

        $value = $manifest->get('missing.key', 'default');

        $this->assertEquals('default', $value);
    }

    public function test_is_valid_returns_true_for_valid_manifest(): void
    {
        $manifest = PluginManifest::create('Test', 'test', [
            'description' => 'A valid test plugin description that is long enough',
        ]);

        $this->assertTrue($manifest->isValid());
    }

    public function test_get_all_scopes_merges_regular_and_dangerous(): void
    {
        $manifest = new PluginManifest([
            'identifier' => 'test',
            'name' => 'Test',
            'version' => '1.0.0',
            'permissions' => [
                'scopes' => ['entities:read'],
                'dangerous_scopes' => ['system:admin'],
            ],
        ]);

        $allScopes = $manifest->getAllScopes();

        $this->assertContains('entities:read', $allScopes);
        $this->assertContains('system:admin', $allScopes);
    }
}
