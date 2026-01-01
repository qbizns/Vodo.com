<?php

declare(strict_types=1);

namespace Tests\Unit\Commerce;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;
use VodoCommerce\Traits\BelongsToStore;

/**
 * Test model that uses BelongsToStore trait.
 */
class TestProduct extends Model
{
    use BelongsToStore;

    protected $table = 'products';
    protected $fillable = ['store_id', 'name', 'price'];
    public $timestamps = false;
}

class BelongsToStoreTraitTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();

        // Reset static store context
        TestProduct::setCurrentStoreId(null);
    }

    public function test_sets_and_gets_current_store_id(): void
    {
        TestProduct::setCurrentStoreId(42);

        $this->assertEquals(42, TestProduct::getCurrentStoreId());
    }

    public function test_clears_current_store_id(): void
    {
        TestProduct::setCurrentStoreId(42);
        TestProduct::setCurrentStoreId(null);

        $this->assertNull(TestProduct::getCurrentStoreId());
    }

    public function test_in_store_context_sets_store_temporarily(): void
    {
        $outsideStore = TestProduct::getCurrentStoreId();

        $result = TestProduct::inStoreContext(99, function () {
            return TestProduct::getCurrentStoreId();
        });

        $this->assertNull($outsideStore);
        $this->assertEquals(99, $result);
        $this->assertNull(TestProduct::getCurrentStoreId()); // Restored
    }

    public function test_in_store_context_restores_on_exception(): void
    {
        TestProduct::setCurrentStoreId(10);

        try {
            TestProduct::inStoreContext(99, function () {
                throw new \Exception('Test exception');
            });
        } catch (\Exception $e) {
            // Expected
        }

        $this->assertEquals(10, TestProduct::getCurrentStoreId());
    }

    public function test_without_store_context_clears_store_temporarily(): void
    {
        TestProduct::setCurrentStoreId(42);

        $result = TestProduct::withoutStoreContext(function () {
            return TestProduct::getCurrentStoreId();
        });

        $this->assertNull($result);
        $this->assertEquals(42, TestProduct::getCurrentStoreId()); // Restored
    }

    public function test_without_store_context_restores_on_exception(): void
    {
        TestProduct::setCurrentStoreId(42);

        try {
            TestProduct::withoutStoreContext(function () {
                throw new \Exception('Test exception');
            });
        } catch (\Exception $e) {
            // Expected
        }

        $this->assertEquals(42, TestProduct::getCurrentStoreId());
    }

    public function test_get_store_column_returns_default(): void
    {
        $product = new TestProduct();

        $this->assertEquals('store_id', $product->getStoreColumn());
    }

    public function test_get_qualified_store_column_includes_table(): void
    {
        $product = new TestProduct();

        $this->assertEquals('products.store_id', $product->getQualifiedStoreColumn());
    }

    public function test_nested_store_context_works_correctly(): void
    {
        TestProduct::setCurrentStoreId(1);

        $results = [];

        TestProduct::inStoreContext(2, function () use (&$results) {
            $results[] = TestProduct::getCurrentStoreId(); // Should be 2

            TestProduct::inStoreContext(3, function () use (&$results) {
                $results[] = TestProduct::getCurrentStoreId(); // Should be 3
            });

            $results[] = TestProduct::getCurrentStoreId(); // Should be 2 again
        });

        $results[] = TestProduct::getCurrentStoreId(); // Should be 1

        $this->assertEquals([2, 3, 2, 1], $results);
    }

    public function test_store_context_is_thread_safe_per_request(): void
    {
        // This tests that the static property behaves correctly within a single request
        TestProduct::setCurrentStoreId(100);

        $this->assertEquals(100, TestProduct::getCurrentStoreId());

        // Simulate another operation changing the context
        TestProduct::setCurrentStoreId(200);

        $this->assertEquals(200, TestProduct::getCurrentStoreId());
    }
}
