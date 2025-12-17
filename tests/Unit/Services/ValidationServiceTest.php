<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use PHPUnit\Framework\TestCase;
use App\Services\Validation\ValidationService;
use Illuminate\Validation\ValidationException;

class ValidationServiceTest extends TestCase
{
    protected ValidationService $validator;

    protected function setUp(): void
    {
        parent::setUp();
        $this->validator = new ValidationService();
    }

    // =========================================================================
    // Manifest Validation Tests
    // =========================================================================

    public function test_valid_manifest_passes_validation(): void
    {
        $manifest = [
            'name' => 'Test Plugin',
            'slug' => 'test-plugin',
            'version' => '1.0.0',
            'main' => 'TestPlugin.php',
        ];

        $result = $this->validator->validateManifest($manifest);

        $this->assertEquals('Test Plugin', $result['name']);
        $this->assertEquals('test-plugin', $result['slug']);
    }

    public function test_manifest_requires_name(): void
    {
        $manifest = [
            'slug' => 'test-plugin',
            'version' => '1.0.0',
            'main' => 'TestPlugin.php',
        ];

        $this->expectException(ValidationException::class);
        $this->validator->validateManifest($manifest);
    }

    public function test_manifest_requires_valid_slug(): void
    {
        $manifest = [
            'name' => 'Test Plugin',
            'slug' => 'Invalid Slug!',
            'version' => '1.0.0',
            'main' => 'TestPlugin.php',
        ];

        $this->expectException(ValidationException::class);
        $this->validator->validateManifest($manifest);
    }

    public function test_manifest_requires_valid_version(): void
    {
        $manifest = [
            'name' => 'Test Plugin',
            'slug' => 'test-plugin',
            'version' => 'invalid',
            'main' => 'TestPlugin.php',
        ];

        $this->expectException(ValidationException::class);
        $this->validator->validateManifest($manifest);
    }

    public function test_manifest_accepts_semver_versions(): void
    {
        $validVersions = ['1.0', '1.0.0', '2.1.3', '1.0.0-beta', '2.0.0-rc1'];

        foreach ($validVersions as $version) {
            $manifest = [
                'name' => 'Test Plugin',
                'slug' => 'test-plugin',
                'version' => $version,
                'main' => 'TestPlugin.php',
            ];

            $result = $this->validator->validateManifest($manifest);
            $this->assertEquals($version, $result['version']);
        }
    }

    // =========================================================================
    // Entity Definition Validation Tests
    // =========================================================================

    public function test_valid_entity_definition_passes(): void
    {
        $entity = [
            'name' => 'product',
            'slug' => 'products',
            'labels' => [
                'singular' => 'Product',
                'plural' => 'Products',
            ],
        ];

        $result = $this->validator->validateEntityDefinition($entity);

        $this->assertEquals('product', $result['name']);
    }

    public function test_entity_name_must_be_lowercase(): void
    {
        $entity = [
            'name' => 'Product',
        ];

        $this->expectException(ValidationException::class);
        $this->validator->validateEntityDefinition($entity);
    }

    public function test_entity_name_must_start_with_letter(): void
    {
        $entity = [
            'name' => '123entity',
        ];

        $this->expectException(ValidationException::class);
        $this->validator->validateEntityDefinition($entity);
    }

    public function test_entity_supports_valid_features(): void
    {
        $entity = [
            'name' => 'product',
            'supports' => ['title', 'content', 'author', 'thumbnail'],
        ];

        $result = $this->validator->validateEntityDefinition($entity);

        $this->assertEquals(['title', 'content', 'author', 'thumbnail'], $result['supports']);
    }

    public function test_entity_rejects_invalid_features(): void
    {
        $entity = [
            'name' => 'product',
            'supports' => ['title', 'invalid_feature'],
        ];

        $this->expectException(ValidationException::class);
        $this->validator->validateEntityDefinition($entity);
    }

    // =========================================================================
    // Field Definition Validation Tests
    // =========================================================================

    public function test_valid_field_definition_passes(): void
    {
        $field = [
            'slug' => 'price',
            'type' => 'decimal',
            'label' => 'Price',
            'required' => true,
        ];

        $result = $this->validator->validateFieldDefinition($field);

        $this->assertEquals('decimal', $result['type']);
    }

    public function test_field_requires_type(): void
    {
        $field = [
            'slug' => 'price',
            'label' => 'Price',
        ];

        $this->expectException(ValidationException::class);
        $this->validator->validateFieldDefinition($field);
    }

    public function test_field_slug_must_be_valid(): void
    {
        $field = [
            'slug' => 'Invalid-Slug',
            'type' => 'string',
        ];

        $this->expectException(ValidationException::class);
        $this->validator->validateFieldDefinition($field);
    }

    public function test_field_width_must_be_valid(): void
    {
        $field = [
            'slug' => 'test',
            'type' => 'string',
            'width' => 'invalid',
        ];

        $this->expectException(ValidationException::class);
        $this->validator->validateFieldDefinition($field);
    }

    public function test_field_accepts_valid_widths(): void
    {
        $validWidths = ['full', 'half', 'third', 'quarter'];

        foreach ($validWidths as $width) {
            $field = [
                'slug' => 'test',
                'type' => 'string',
                'width' => $width,
            ];

            $result = $this->validator->validateFieldDefinition($field);
            $this->assertEquals($width, $result['width']);
        }
    }

    // =========================================================================
    // API Endpoint Validation Tests
    // =========================================================================

    public function test_valid_api_endpoint_passes(): void
    {
        $endpoint = [
            'method' => 'GET',
            'path' => 'products/{id}',
            'handler' => 'ProductController@show',
        ];

        $result = $this->validator->validateApiEndpoint($endpoint);

        $this->assertEquals('GET', $result['method']);
    }

    public function test_api_method_must_be_valid(): void
    {
        $endpoint = [
            'method' => 'INVALID',
            'path' => 'products',
            'handler' => 'ProductController@index',
        ];

        $this->expectException(ValidationException::class);
        $this->validator->validateApiEndpoint($endpoint);
    }

    public function test_api_rate_limit_format(): void
    {
        $endpoint = [
            'method' => 'GET',
            'path' => 'products',
            'handler' => 'ProductController@index',
            'rate_limit' => '60:60',
        ];

        $result = $this->validator->validateApiEndpoint($endpoint);

        $this->assertEquals('60:60', $result['rate_limit']);
    }

    public function test_api_invalid_rate_limit_fails(): void
    {
        $endpoint = [
            'method' => 'GET',
            'path' => 'products',
            'handler' => 'ProductController@index',
            'rate_limit' => 'invalid',
        ];

        $this->expectException(ValidationException::class);
        $this->validator->validateApiEndpoint($endpoint);
    }

    // =========================================================================
    // Sanitization Tests
    // =========================================================================

    public function test_sanitize_string_removes_null_bytes(): void
    {
        $input = "Hello\0World";
        $result = $this->validator->sanitizeString($input);

        $this->assertStringNotContainsString("\0", $result);
    }

    public function test_sanitize_string_escapes_html(): void
    {
        $input = '<script>alert("xss")</script>';
        $result = $this->validator->sanitizeString($input);

        $this->assertStringNotContainsString('<script>', $result);
        $this->assertStringContainsString('&lt;script&gt;', $result);
    }

    public function test_sanitize_html_allows_safe_tags(): void
    {
        $input = '<p>Hello <strong>World</strong></p><script>bad</script>';
        $result = $this->validator->sanitizeHtml($input);

        $this->assertStringContainsString('<p>', $result);
        $this->assertStringContainsString('<strong>', $result);
        $this->assertStringNotContainsString('<script>', $result);
    }

    public function test_sanitize_slug(): void
    {
        $testCases = [
            'Hello World' => 'hello-world',
            'Test_Plugin' => 'test-plugin',
            'Multiple   Spaces' => 'multiple-spaces',
            '--Leading-Hyphens--' => 'leading-hyphens',
            'Special@#$Characters' => 'specialcharacters',
            'UPPERCASE' => 'uppercase',
        ];

        foreach ($testCases as $input => $expected) {
            $result = $this->validator->sanitizeSlug($input);
            $this->assertEquals($expected, $result, "Failed for input: {$input}");
        }
    }

    // =========================================================================
    // Shortcode Validation Tests
    // =========================================================================

    public function test_valid_shortcode_passes(): void
    {
        $shortcode = [
            'tag' => 'my_shortcode',
            'handler' => fn() => 'content',
            'description' => 'A test shortcode',
        ];

        $result = $this->validator->validateShortcode($shortcode);

        $this->assertEquals('my_shortcode', $result['tag']);
    }

    public function test_shortcode_tag_must_be_valid(): void
    {
        $shortcode = [
            'tag' => 'Invalid-Tag',
            'handler' => fn() => 'content',
        ];

        $this->expectException(ValidationException::class);
        $this->validator->validateShortcode($shortcode);
    }

    // =========================================================================
    // Menu Item Validation Tests
    // =========================================================================

    public function test_valid_menu_item_passes(): void
    {
        $menuItem = [
            'id' => 'products',
            'label' => 'Products',
            'url' => '/admin/products',
            'icon' => 'box',
        ];

        $result = $this->validator->validateMenuItem($menuItem);

        $this->assertEquals('products', $result['id']);
    }

    public function test_menu_item_requires_id_and_label(): void
    {
        $menuItem = [
            'url' => '/admin/products',
        ];

        $this->expectException(ValidationException::class);
        $this->validator->validateMenuItem($menuItem);
    }
}
