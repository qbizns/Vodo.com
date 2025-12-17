<?php

declare(strict_types=1);

namespace Tests\Unit\Models;

use PHPUnit\Framework\TestCase;
use App\Models\Plugin;
use App\Exceptions\Security\SecurityException;

class PluginTest extends TestCase
{
    // =========================================================================
    // Slug Validation Tests
    // =========================================================================

    public function test_valid_slugs_pass_validation(): void
    {
        $plugin = new Plugin();
        
        $validSlugs = [
            'my-plugin',
            'plugin123',
            'a1',
            'hello-world',
            'test-plugin-v2',
        ];

        foreach ($validSlugs as $slug) {
            $this->assertTrue(
                $plugin->isValidSlug($slug),
                "Slug '{$slug}' should be valid"
            );
        }
    }

    public function test_invalid_slugs_fail_validation(): void
    {
        $plugin = new Plugin();
        
        $invalidSlugs = [
            '-starts-with-hyphen',
            'ends-with-hyphen-',
            'a', // too short
            'Has_Uppercase',
            'has spaces',
            'has.dots',
            'has/slash',
            'has\\backslash',
            '../path-traversal',
            "has\0null",
            str_repeat('a', 65), // too long
        ];

        foreach ($invalidSlugs as $slug) {
            $this->assertFalse(
                $plugin->isValidSlug($slug),
                "Slug '{$slug}' should be invalid"
            );
        }
    }

    // =========================================================================
    // Path Traversal Protection Tests
    // =========================================================================

    public function test_path_traversal_in_slug_throws_exception(): void
    {
        $plugin = new Plugin(['slug' => '../malicious']);
        
        $this->expectException(SecurityException::class);
        $plugin->getFullPath();
    }

    public function test_path_with_double_dots_throws_exception(): void
    {
        $plugin = new Plugin([
            'slug' => 'test',
            'path' => '/var/www/app/Plugins/../../../etc/passwd',
        ]);
        
        $this->expectException(SecurityException::class);
        $plugin->getFullPath();
    }

    public function test_path_with_encoded_traversal_is_detected(): void
    {
        $plugin = new Plugin(['slug' => "test\0../malicious"]);
        
        $this->expectException(SecurityException::class);
        $plugin->getFullPath();
    }

    // =========================================================================
    // Status Tests
    // =========================================================================

    public function test_status_constants_are_defined(): void
    {
        $this->assertEquals('inactive', Plugin::STATUS_INACTIVE);
        $this->assertEquals('active', Plugin::STATUS_ACTIVE);
        $this->assertEquals('error', Plugin::STATUS_ERROR);
    }

    public function test_is_active_returns_correct_value(): void
    {
        $plugin = new Plugin(['status' => Plugin::STATUS_ACTIVE]);
        $this->assertTrue($plugin->isActive());
        $this->assertFalse($plugin->isInactive());
        $this->assertFalse($plugin->hasError());
    }

    public function test_is_inactive_returns_correct_value(): void
    {
        $plugin = new Plugin(['status' => Plugin::STATUS_INACTIVE]);
        $this->assertFalse($plugin->isActive());
        $this->assertTrue($plugin->isInactive());
        $this->assertFalse($plugin->hasError());
    }

    public function test_has_error_returns_correct_value(): void
    {
        $plugin = new Plugin(['status' => Plugin::STATUS_ERROR]);
        $this->assertFalse($plugin->isActive());
        $this->assertFalse($plugin->isInactive());
        $this->assertTrue($plugin->hasError());
    }

    // =========================================================================
    // Settings Tests
    // =========================================================================

    public function test_can_get_setting(): void
    {
        $plugin = new Plugin([
            'settings' => [
                'theme' => 'dark',
                'limit' => 10,
            ],
        ]);

        $this->assertEquals('dark', $plugin->getSetting('theme'));
        $this->assertEquals(10, $plugin->getSetting('limit'));
        $this->assertNull($plugin->getSetting('nonexistent'));
        $this->assertEquals('default', $plugin->getSetting('nonexistent', 'default'));
    }

    public function test_can_set_setting(): void
    {
        $plugin = new Plugin(['settings' => []]);
        
        $plugin->setSetting('key', 'value');
        
        $this->assertEquals('value', $plugin->getSetting('key'));
    }

    // =========================================================================
    // Dependency Tests
    // =========================================================================

    public function test_requires_plugin_returns_correct_value(): void
    {
        $plugin = new Plugin([
            'requires' => [
                'php' => '8.1',
                'other-plugin' => '1.0',
            ],
        ]);

        $this->assertTrue($plugin->requiresPlugin('other-plugin'));
        $this->assertFalse($plugin->requiresPlugin('nonexistent'));
    }

    public function test_get_required_version(): void
    {
        $plugin = new Plugin([
            'requires' => [
                'php' => '8.1',
                'laravel' => '10.0',
            ],
        ]);

        $this->assertEquals('8.1', $plugin->getRequiredVersion('php'));
        $this->assertEquals('10.0', $plugin->getRequiredVersion('laravel'));
        $this->assertNull($plugin->getRequiredVersion('nonexistent'));
    }

    // =========================================================================
    // Class Name Generation Tests
    // =========================================================================

    public function test_generates_main_class_name_from_slug(): void
    {
        $plugin = new Plugin(['slug' => 'my-plugin']);
        
        $className = $plugin->getMainClassName();
        
        $this->assertEquals('App\\Plugins\\my_plugin\\MyPluginPlugin', $className);
    }

    public function test_uses_explicit_main_class_when_provided(): void
    {
        $plugin = new Plugin([
            'slug' => 'my-plugin',
            'main_class' => 'Custom\\Namespace\\MyClass',
        ]);
        
        $className = $plugin->getMainClassName();
        
        $this->assertEquals('Custom\\Namespace\\MyClass', $className);
    }

    // =========================================================================
    // Fillable Tests
    // =========================================================================

    public function test_fillable_attributes(): void
    {
        $data = [
            'name' => 'Test Plugin',
            'slug' => 'test-plugin',
            'version' => '1.0.0',
            'description' => 'A test plugin',
            'author' => 'Test Author',
            'author_url' => 'https://example.com',
            'status' => 'active',
            'settings' => ['key' => 'value'],
            'requires' => ['php' => '8.1'],
            'main_class' => 'App\\Plugins\\TestPlugin',
            'path' => '/path/to/plugin',
        ];

        $plugin = new Plugin($data);

        $this->assertEquals('Test Plugin', $plugin->name);
        $this->assertEquals('test-plugin', $plugin->slug);
        $this->assertEquals('1.0.0', $plugin->version);
        $this->assertEquals('A test plugin', $plugin->description);
        $this->assertEquals('Test Author', $plugin->author);
        $this->assertEquals('active', $plugin->status);
    }

    // =========================================================================
    // Casting Tests
    // =========================================================================

    public function test_settings_is_cast_to_array(): void
    {
        $plugin = new Plugin(['settings' => '{"key":"value"}']);
        
        // Due to casting, this should work after being saved/retrieved
        $this->assertIsArray($plugin->settings ?? []);
    }

    public function test_requires_is_cast_to_array(): void
    {
        $plugin = new Plugin(['requires' => '{"php":"8.1"}']);
        
        // Due to casting, this should work after being saved/retrieved
        $this->assertIsArray($plugin->requires ?? []);
    }
}
