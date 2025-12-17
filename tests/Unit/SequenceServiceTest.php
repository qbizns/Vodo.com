<?php

declare(strict_types=1);

namespace Tests\Unit;

use Tests\TestCase;
use App\Services\Sequence\SequenceService;
use Illuminate\Foundation\Testing\RefreshDatabase;

class SequenceServiceTest extends TestCase
{
    use RefreshDatabase;

    protected SequenceService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new SequenceService();
    }

    public function test_can_define_sequence(): void
    {
        $this->service->define('test', [
            'prefix' => 'TST-',
            'pattern' => '{YYYY}-{####}',
            'reset_on' => 'year',
        ]);

        $this->assertTrue($this->service->exists('test'));
    }

    public function test_generates_sequential_numbers(): void
    {
        $this->service->define('invoice', [
            'prefix' => 'INV-',
            'pattern' => '{####}',
        ]);

        $first = $this->service->next('invoice');
        $second = $this->service->next('invoice');
        $third = $this->service->next('invoice');

        $this->assertEquals('INV-0001', $first);
        $this->assertEquals('INV-0002', $second);
        $this->assertEquals('INV-0003', $third);
    }

    public function test_includes_year_in_pattern(): void
    {
        $this->service->define('order', [
            'prefix' => 'SO-',
            'pattern' => '{YYYY}-{####}',
        ]);

        $number = $this->service->next('order');
        $year = date('Y');

        $this->assertStringStartsWith("SO-{$year}-", $number);
    }

    public function test_preview_does_not_increment(): void
    {
        $this->service->define('quote', [
            'prefix' => 'QT-',
            'pattern' => '{####}',
        ]);

        $preview1 = $this->service->preview('quote');
        $preview2 = $this->service->preview('quote');

        $this->assertEquals($preview1, $preview2);
    }

    public function test_can_set_specific_value(): void
    {
        $this->service->define('custom', [
            'prefix' => '',
            'pattern' => '{####}',
        ]);

        $this->service->set('custom', 100);
        $next = $this->service->next('custom');

        $this->assertEquals('0101', $next);
    }

    public function test_can_reset_sequence(): void
    {
        $this->service->define('reset_test', [
            'prefix' => '',
            'pattern' => '{####}',
            'start_value' => 1,
        ]);

        $this->service->next('reset_test');
        $this->service->next('reset_test');
        $this->service->next('reset_test');

        $this->service->reset('reset_test');
        $next = $this->service->next('reset_test');

        $this->assertEquals('0001', $next);
    }

    public function test_tenant_isolation(): void
    {
        $this->service->define('tenant_test', [
            'prefix' => 'T-',
            'pattern' => '{####}',
        ]);

        $tenant1_num1 = $this->service->next('tenant_test', 1);
        $tenant1_num2 = $this->service->next('tenant_test', 1);
        $tenant2_num1 = $this->service->next('tenant_test', 2);

        $this->assertEquals('T-0001', $tenant1_num1);
        $this->assertEquals('T-0002', $tenant1_num2);
        $this->assertEquals('T-0001', $tenant2_num1); // Separate counter for tenant 2
    }

    public function test_batch_generation(): void
    {
        $this->service->define('batch_test', [
            'prefix' => 'B-',
            'pattern' => '{####}',
        ]);

        $numbers = $this->service->nextBatch('batch_test', 5);

        $this->assertCount(5, $numbers);
        $this->assertEquals('B-0001', $numbers[0]);
        $this->assertEquals('B-0005', $numbers[4]);
    }

    public function test_parses_sequence_string(): void
    {
        $this->service->define('parse_test', [
            'prefix' => 'INV-',
            'pattern' => '{YYYY}-{####}',
        ]);

        $parsed = $this->service->parse('parse_test', 'INV-2025-0042');

        $this->assertEquals(2025, $parsed['year']);
        $this->assertEquals(42, $parsed['number']);
    }

    public function test_registers_default_sequences(): void
    {
        $this->service->registerDefaults();

        $this->assertTrue($this->service->exists('invoice'));
        $this->assertTrue($this->service->exists('order'));
        $this->assertTrue($this->service->exists('customer'));
    }
}
