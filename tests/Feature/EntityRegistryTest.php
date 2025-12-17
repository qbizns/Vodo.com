<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\Entity\EntityRegistry;
use App\Models\EntityDefinition;
use App\Models\EntityField;
use App\Exceptions\Entity\EntityRegistrationException;
use Illuminate\Foundation\Testing\RefreshDatabase;

class EntityRegistryTest extends TestCase
{
    use RefreshDatabase;

    protected EntityRegistry $registry;

    protected function setUp(): void
    {
        parent::setUp();
        $this->registry = EntityRegistry::getInstance();
        $this->registry->clearCache();
    }

    // =========================================================================
    // Registration Tests
    // =========================================================================

    public function test_can_register_entity(): void
    {
        $entity = $this->registry->register('product', [
            'labels' => [
                'singular' => 'Product',
                'plural' => 'Products',
            ],
        ], 'test-plugin');

        $this->assertInstanceOf(EntityDefinition::class, $entity);
        $this->assertEquals('product', $entity->name);
        $this->assertEquals('Product', $entity->labels['singular']);
    }

    public function test_cannot_register_entity_with_invalid_name(): void
    {
        $this->expectException(EntityRegistrationException::class);

        $this->registry->register('Invalid-Name', []);
    }

    public function test_cannot_register_entity_starting_with_number(): void
    {
        $this->expectException(EntityRegistrationException::class);

        $this->registry->register('123entity', []);
    }

    public function test_cannot_register_duplicate_entity_by_different_plugin(): void
    {
        $this->registry->register('product', [], 'plugin-a');

        $this->expectException(EntityRegistrationException::class);

        $this->registry->register('product', [], 'plugin-b');
    }

    public function test_can_update_entity_by_same_plugin(): void
    {
        $this->registry->register('product', [
            'labels' => ['singular' => 'Product'],
        ], 'test-plugin');

        $updated = $this->registry->register('product', [
            'labels' => ['singular' => 'Updated Product'],
        ], 'test-plugin');

        $this->assertEquals('Updated Product', $updated->labels['singular']);
    }

    // =========================================================================
    // Field Registration Tests
    // =========================================================================

    public function test_can_register_entity_with_fields(): void
    {
        $entity = $this->registry->register('product', [
            'fields' => [
                'price' => [
                    'type' => 'decimal',
                    'label' => 'Price',
                    'required' => true,
                ],
                'sku' => [
                    'type' => 'string',
                    'label' => 'SKU',
                    'unique' => true,
                ],
            ],
        ], 'test-plugin');

        $fields = EntityField::where('entity_name', 'product')->get();

        $this->assertCount(2, $fields);
        $this->assertEquals('price', $fields->firstWhere('slug', 'price')->slug);
        $this->assertEquals('sku', $fields->firstWhere('slug', 'sku')->slug);
    }

    public function test_validates_field_type(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->registry->register('product', [
            'fields' => [
                'bad_field' => [
                    'type' => 'nonexistent_type',
                ],
            ],
        ]);
    }

    public function test_validates_relation_field_config(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->registry->register('product', [
            'fields' => [
                'category' => [
                    'type' => 'relation',
                    // Missing 'entity' config
                ],
            ],
        ]);
    }

    public function test_blocks_dangerous_file_extensions(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->registry->register('product', [
            'fields' => [
                'document' => [
                    'type' => 'file',
                    'config' => [
                        'extensions' => ['pdf', 'php', 'doc'], // php is dangerous
                    ],
                ],
            ],
        ]);
    }

    // =========================================================================
    // Unregistration Tests
    // =========================================================================

    public function test_can_unregister_entity(): void
    {
        $this->registry->register('product', [], 'test-plugin');

        $result = $this->registry->unregister('product', 'test-plugin');

        $this->assertTrue($result);
        $this->assertNull(EntityDefinition::where('name', 'product')->first());
    }

    public function test_cannot_unregister_entity_by_different_plugin(): void
    {
        $this->registry->register('product', [], 'plugin-a');

        $this->expectException(EntityRegistrationException::class);

        $this->registry->unregister('product', 'plugin-b');
    }

    public function test_unregister_returns_false_for_nonexistent_entity(): void
    {
        $result = $this->registry->unregister('nonexistent');

        $this->assertFalse($result);
    }

    public function test_unregister_deletes_fields(): void
    {
        $this->registry->register('product', [
            'fields' => [
                'price' => ['type' => 'decimal'],
                'name' => ['type' => 'string'],
            ],
        ], 'test-plugin');

        $this->registry->unregister('product', 'test-plugin');

        $fields = EntityField::where('entity_name', 'product')->count();
        $this->assertEquals(0, $fields);
    }

    // =========================================================================
    // Query Tests
    // =========================================================================

    public function test_can_get_entity(): void
    {
        $this->registry->register('product', [], 'test-plugin');

        $entity = $this->registry->get('product');

        $this->assertNotNull($entity);
        $this->assertEquals('product', $entity->name);
    }

    public function test_get_returns_null_for_nonexistent(): void
    {
        $entity = $this->registry->get('nonexistent');

        $this->assertNull($entity);
    }

    public function test_exists_returns_correct_value(): void
    {
        $this->registry->register('product', [], 'test-plugin');

        $this->assertTrue($this->registry->exists('product'));
        $this->assertFalse($this->registry->exists('nonexistent'));
    }

    public function test_get_by_plugin(): void
    {
        $this->registry->register('entity_a', [], 'plugin-a');
        $this->registry->register('entity_b', [], 'plugin-a');
        $this->registry->register('entity_c', [], 'plugin-b');

        $entities = $this->registry->getByPlugin('plugin-a');

        $this->assertCount(2, $entities);
    }

    // =========================================================================
    // Field Management Tests
    // =========================================================================

    public function test_can_add_field_to_existing_entity(): void
    {
        $this->registry->register('product', [], 'test-plugin');

        $field = $this->registry->addField('product', 'price', [
            'type' => 'decimal',
            'label' => 'Price',
        ], 'test-plugin');

        $this->assertEquals('price', $field->slug);
        $this->assertEquals('decimal', $field->type);
    }

    public function test_cannot_add_field_to_nonexistent_entity(): void
    {
        $this->expectException(\App\Exceptions\Entity\EntityException::class);

        $this->registry->addField('nonexistent', 'field', ['type' => 'string']);
    }

    public function test_can_remove_field(): void
    {
        $this->registry->register('product', [
            'fields' => [
                'price' => ['type' => 'decimal'],
            ],
        ], 'test-plugin');

        $result = $this->registry->removeField('product', 'price', 'test-plugin');

        $this->assertTrue($result);
        $this->assertNull(EntityField::where('entity_name', 'product')->where('slug', 'price')->first());
    }

    public function test_cannot_remove_field_owned_by_different_plugin(): void
    {
        $this->registry->register('product', [
            'fields' => [
                'price' => ['type' => 'decimal'],
            ],
        ], 'plugin-a');

        $this->expectException(\App\Exceptions\Entity\EntityException::class);

        $this->registry->removeField('product', 'price', 'plugin-b');
    }

    // =========================================================================
    // Validation Rules Tests
    // =========================================================================

    public function test_get_validation_rules(): void
    {
        $this->registry->register('product', [
            'fields' => [
                'name' => [
                    'type' => 'string',
                    'required' => true,
                ],
                'price' => [
                    'type' => 'decimal',
                    'required' => true,
                ],
            ],
        ]);

        $rules = $this->registry->getValidationRules('product');

        $this->assertArrayHasKey('name', $rules);
        $this->assertArrayHasKey('price', $rules);
    }

    public function test_get_validation_rules_returns_empty_for_nonexistent(): void
    {
        $rules = $this->registry->getValidationRules('nonexistent');

        $this->assertEmpty($rules);
    }

    // =========================================================================
    // Cache Tests
    // =========================================================================

    public function test_clear_cache(): void
    {
        $this->registry->register('product', [], 'test-plugin');
        
        // Entity should be cached
        $entity1 = $this->registry->get('product');
        
        $this->registry->clearCache();
        
        // Should still work after cache clear (fetches from DB)
        $entity2 = $this->registry->get('product');
        
        $this->assertEquals($entity1->id, $entity2->id);
    }
}
