<?php

declare(strict_types=1);

namespace Tests\Unit\Services;

use Tests\TestCase;
use App\Models\WorkflowDefinition;
use App\Services\Workflow\WorkflowEngine;
use App\Services\Workflow\WorkflowException;
use App\Services\Workflow\WorkflowConditionException;
use App\Services\PluginBus\PluginBus;
use Illuminate\Foundation\Testing\RefreshDatabase;

class WorkflowEngineTest extends TestCase
{
    use RefreshDatabase;

    protected WorkflowEngine $engine;

    protected function setUp(): void
    {
        parent::setUp();
        $this->engine = new WorkflowEngine(new PluginBus());
    }

    // =========================================================================
    // Workflow Definition Tests
    // =========================================================================

    public function test_can_define_workflow(): void
    {
        $workflow = $this->engine->defineWorkflow('test_workflow', 'invoice', [
            'states' => [
                'draft' => ['label' => 'Draft'],
                'confirmed' => ['label' => 'Confirmed'],
            ],
            'transitions' => [
                'confirm' => [
                    'from' => 'draft',
                    'to' => 'confirmed',
                ],
            ],
        ]);

        $this->assertInstanceOf(WorkflowDefinition::class, $workflow);
        $this->assertEquals('test_workflow', $workflow->slug);
        $this->assertEquals('invoice', $workflow->entity_name);
    }

    public function test_workflow_requires_states(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->engine->defineWorkflow('test_workflow', 'invoice', [
            'states' => [],
            'transitions' => [],
        ]);
    }

    public function test_workflow_validates_transition_states(): void
    {
        $this->expectException(\InvalidArgumentException::class);

        $this->engine->defineWorkflow('test_workflow', 'invoice', [
            'states' => [
                'draft' => ['label' => 'Draft'],
            ],
            'transitions' => [
                'confirm' => [
                    'from' => 'draft',
                    'to' => 'nonexistent', // Invalid target
                ],
            ],
        ]);
    }

    // =========================================================================
    // Mermaid Diagram Tests
    // =========================================================================

    public function test_generates_mermaid_diagram(): void
    {
        $this->engine->defineWorkflow('invoice_workflow', 'invoice', [
            'states' => [
                'draft' => ['label' => 'Draft'],
                'sent' => ['label' => 'Sent'],
                'paid' => ['label' => 'Paid', 'is_final' => true],
            ],
            'transitions' => [
                'send' => ['from' => 'draft', 'to' => 'sent', 'label' => 'Send'],
                'pay' => ['from' => 'sent', 'to' => 'paid', 'label' => 'Pay'],
            ],
        ]);

        $diagram = $this->engine->generateDiagram('invoice_workflow');

        $this->assertStringContainsString('stateDiagram-v2', $diagram);
        $this->assertStringContainsString('[*] --> draft', $diagram);
        $this->assertStringContainsString('draft --> sent', $diagram);
        $this->assertStringContainsString('sent --> paid', $diagram);
        $this->assertStringContainsString('paid --> [*]', $diagram);
    }

    // =========================================================================
    // Condition Registration Tests
    // =========================================================================

    public function test_can_register_custom_condition(): void
    {
        $conditionCalled = false;

        $this->engine->registerCondition('custom_check', function ($record) use (&$conditionCalled) {
            $conditionCalled = true;
            return true;
        });

        // Condition is registered
        $this->assertFalse($conditionCalled); // Not called until evaluated
    }

    // =========================================================================
    // Action Registration Tests
    // =========================================================================

    public function test_can_register_custom_action(): void
    {
        $actionCalled = false;

        $this->engine->registerAction('custom_action', function ($record, $instance, $data) use (&$actionCalled) {
            $actionCalled = true;
        });

        // Action is registered but not called until transition
        $this->assertFalse($actionCalled);
    }

    // =========================================================================
    // Workflow Definition Model Tests
    // =========================================================================

    public function test_workflow_definition_get_state(): void
    {
        $workflow = $this->engine->defineWorkflow('test_workflow', 'invoice', [
            'states' => [
                'draft' => ['label' => 'Draft', 'color' => 'gray'],
                'confirmed' => ['label' => 'Confirmed', 'color' => 'green'],
            ],
            'transitions' => [
                'confirm' => ['from' => 'draft', 'to' => 'confirmed'],
            ],
        ]);

        $state = $workflow->getState('draft');

        $this->assertEquals('Draft', $state['label']);
        $this->assertEquals('gray', $state['color']);
    }

    public function test_workflow_definition_get_transitions_from(): void
    {
        $workflow = $this->engine->defineWorkflow('test_workflow', 'invoice', [
            'states' => [
                'draft' => ['label' => 'Draft'],
                'sent' => ['label' => 'Sent'],
                'paid' => ['label' => 'Paid'],
            ],
            'transitions' => [
                'send' => ['from' => 'draft', 'to' => 'sent'],
                'pay' => ['from' => 'sent', 'to' => 'paid'],
                'cancel' => ['from' => ['draft', 'sent'], 'to' => 'cancelled'],
            ],
        ]);

        $fromDraft = $workflow->getTransitionsFrom('draft');

        $this->assertArrayHasKey('send', $fromDraft);
        $this->assertArrayHasKey('cancel', $fromDraft);
        $this->assertArrayNotHasKey('pay', $fromDraft);
    }

    public function test_workflow_definition_can_transition(): void
    {
        $workflow = $this->engine->defineWorkflow('test_workflow', 'invoice', [
            'states' => [
                'draft' => ['label' => 'Draft'],
                'confirmed' => ['label' => 'Confirmed'],
            ],
            'transitions' => [
                'confirm' => ['from' => 'draft', 'to' => 'confirmed'],
            ],
        ]);

        $this->assertTrue($workflow->canTransition('draft', 'confirm'));
        $this->assertFalse($workflow->canTransition('confirmed', 'confirm'));
        $this->assertFalse($workflow->canTransition('draft', 'nonexistent'));
    }
}
