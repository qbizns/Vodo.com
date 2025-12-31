<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Integration\Transform;

use App\Services\Integration\Transform\DataTransformer;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class DataTransformerTest extends TestCase
{
    protected DataTransformer $transformer;

    protected function setUp(): void
    {
        parent::setUp();
        $this->transformer = new DataTransformer();
    }

    // =========================================================================
    // Config Function Tests
    // =========================================================================

    public function test_config_function_is_registered(): void
    {
        $this->assertTrue($this->transformer->hasFunction('config'));
    }

    public function test_config_function_retrieves_config_value(): void
    {
        Config::set('test.value', 'test-config-value');

        $result = $this->transformer->evaluate('{{ config("test.value") }}', []);

        $this->assertEquals('test-config-value', $result);
    }

    public function test_config_function_uses_default_when_key_not_found(): void
    {
        $result = $this->transformer->evaluate('{{ config("nonexistent.key", "default-value") }}', []);

        $this->assertEquals('default-value', $result);
    }

    public function test_config_function_in_transform_mappings(): void
    {
        Config::set('app.name', 'Vodo Platform');

        $data = ['user' => 'John'];
        $mappings = [
            [
                'expression' => '{{ config("app.name") }}',
                'target' => 'platform_name',
            ],
        ];

        $result = $this->transformer->transform($data, $mappings);

        $this->assertEquals('Vodo Platform', $result['platform_name']);
    }

    public function test_config_function_can_access_nested_config(): void
    {
        Config::set('services.api.endpoint', 'https://api.example.com');

        $result = $this->transformer->evaluate('{{ config("services.api.endpoint") }}', []);

        $this->assertEquals('https://api.example.com', $result);
    }

    // =========================================================================
    // Legacy Env Function Tests (Ensuring It No Longer Exists)
    // =========================================================================

    public function test_env_function_is_not_registered(): void
    {
        $this->assertFalse($this->transformer->hasFunction('env'));
    }
}
