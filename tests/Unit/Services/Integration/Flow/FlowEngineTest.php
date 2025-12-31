<?php

declare(strict_types=1);

namespace Tests\Unit\Services\Integration\Flow;

use App\Services\Integration\Flow\FlowEngine;
use Illuminate\Support\Facades\Config;
use Tests\TestCase;

class FlowEngineTest extends TestCase
{
    protected FlowEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new FlowEngine();
    }

    // =========================================================================
    // Config Variable Resolution Tests
    // =========================================================================

    public function test_resolves_config_variables_in_input(): void
    {
        Config::set('app.name', 'Vodo Platform');

        $input = [
            'message' => 'Welcome to {{ config.app.name }}',
            'other' => 'static value',
        ];

        $context = ['data' => []];
        $result = $this->engine->resolveInputVariables($input, $context);

        $this->assertEquals('Welcome to Vodo Platform', $result['message']);
        $this->assertEquals('static value', $result['other']);
    }

    public function test_resolves_nested_config_values(): void
    {
        Config::set('services.api.endpoint', 'https://api.example.com');

        $input = [
            'api_url' => '{{ config.services.api.endpoint }}',
        ];

        $context = ['data' => []];
        $result = $this->engine->resolveInputVariables($input, $context);

        $this->assertEquals('https://api.example.com', $result['api_url']);
    }

    public function test_resolves_config_with_default_value_when_not_found(): void
    {
        $input = [
            'value' => '{{ config.nonexistent.key }}',
        ];

        $context = ['data' => []];
        $result = $this->engine->resolveInputVariables($input, $context);

        // When config key doesn't exist, it should return empty string
        $this->assertEquals('', $result['value']);
    }

    public function test_resolves_multiple_config_variables_in_same_string(): void
    {
        Config::set('app.name', 'Vodo');
        Config::set('app.env', 'production');

        $input = [
            'message' => 'Running {{ config.app.name }} in {{ config.app.env }} mode',
        ];

        $context = ['data' => []];
        $result = $this->engine->resolveInputVariables($input, $context);

        $this->assertEquals('Running Vodo in production mode', $result['message']);
    }

    public function test_config_resolution_works_alongside_other_context_variables(): void
    {
        Config::set('app.name', 'Vodo');

        $input = [
            'message' => '{{ config.app.name }} user: {{ user.name }}',
        ];

        $context = [
            'data' => ['user' => ['name' => 'John Doe']],
        ];

        $result = $this->engine->resolveInputVariables($input, $context);

        $this->assertEquals('Vodo user: John Doe', $result['message']);
    }

    public function test_non_string_values_are_not_processed(): void
    {
        $input = [
            'number' => 123,
            'boolean' => true,
            'array' => ['a', 'b'],
        ];

        $context = ['data' => []];
        $result = $this->engine->resolveInputVariables($input, $context);

        $this->assertEquals(123, $result['number']);
        $this->assertTrue($result['boolean']);
        $this->assertEquals(['a', 'b'], $result['array']);
    }
}
