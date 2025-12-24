<?php

declare(strict_types=1);

namespace App\Services\Workflow;

/**
 * Generic workflow created from array configuration.
 *
 * Used when registering workflows via registerFromArray().
 */
class GenericWorkflow extends AbstractWorkflow
{
    /**
     * Create a new GenericWorkflow.
     *
     * @param string $name Workflow name
     * @param array $config Configuration
     */
    public function __construct(string $name, array $config)
    {
        $this->name = $name;
        $this->label = $config['label'] ?? ucfirst(str_replace('_', ' ', $name)) . ' Workflow';
        $this->entity = $config['entity'] ?? $name;
        $this->stateField = $config['state_field'] ?? 'state';
        $this->states = $config['states'] ?? [];
        $this->transitions = $config['transitions'] ?? [];
        $this->initialState = $config['initial_state'] ?? array_key_first($this->states) ?? 'draft';
        $this->finalStates = $config['final_states'] ?? [];
    }
}
