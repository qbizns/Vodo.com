<?php

declare(strict_types=1);

namespace Tests\Feature;

use Tests\TestCase;
use App\Services\Workflow\WorkflowEngine;
use App\Services\PluginBus\PluginBus;
use App\Models\WorkflowDefinition;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Auth;

class WorkflowEngineTest extends TestCase
{
    use RefreshDatabase;

    protected WorkflowEngine $engine;
    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();
        
        $bus = new PluginBus();
        $this->engine = new WorkflowEngine($bus);
        
        $this->user = User::factory()->create();
        Auth::login($this->user);
    }

    public function test_can_define_workflow(): void
    {
        $workflow = $this->engine->defineWorkflow('test_workflow', 'tests', [
            'states' => [
                'draft' => ['label' => 'Draft'],
                'published' => ['label' => 'Published'],
            ],
            'transitions' => [
                'publish' => [
                    'from' => 'draft',
                    'to' => 'published',
                ],
            ],
        ]);

        $this->assertInstanceOf(WorkflowDefinition::class, $workflow);
        $this->assertEquals('test_workflow', $workflow->slug);
        $this->assertArrayHasKey('draft', $workflow->states);
    }

    public function test_validates_workflow_definition(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->engine->defineWorkflow('invalid', 'tests', [
            'states' => [],
            'transitions' => [],
        ]);
    }

    public function test_validates_transitions_reference_valid_states(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->engine->defineWorkflow('invalid_transition', 'tests', [
            'states' => [
                'draft' => ['label' => 'Draft'],
            ],
            'transitions' => [
                'publish' => [
                    'from' => 'draft',
                    'to' => 'nonexistent', // Invalid state
                ],
            ],
        ]);
    }

    public function test_can_register_custom_condition(): void
    {
        $this->engine->registerCondition('always_true', fn($record) => true);
        $this->engine->registerCondition('always_false', fn($record) => false);

        $workflow = $this->engine->defineWorkflow('condition_test', 'tests', [
            'states' => [
                'draft' => ['label' => 'Draft'],
                'published' => ['label' => 'Published'],
            ],
            'transitions' => [
                'publish' => [
                    'from' => 'draft',
                    'to' => 'published',
                    'conditions' => ['always_true'],
                ],
            ],
        ]);

        $this->assertNotNull($workflow);
    }

    public function test_can_register_custom_action(): void
    {
        $actionExecuted = false;

        $this->engine->registerAction('test_action', function ($record, $instance, $data) use (&$actionExecuted) {
            $actionExecuted = true;
        });

        $this->assertFalse($actionExecuted);
    }

    public function test_generates_mermaid_diagram(): void
    {
        $workflow = $this->engine->defineWorkflow('diagram_test', 'tests', [
            'states' => [
                'draft' => ['label' => 'Draft'],
                'published' => ['label' => 'Published'],
            ],
            'transitions' => [
                'publish' => [
                    'from' => 'draft',
                    'to' => 'published',
                ],
            ],
        ]);

        $diagram = $this->engine->generateDiagram('diagram_test');

        $this->assertStringContainsString('stateDiagram', $diagram);
        $this->assertStringContainsString('draft', $diagram);
        $this->assertStringContainsString('published', $diagram);
    }
}
