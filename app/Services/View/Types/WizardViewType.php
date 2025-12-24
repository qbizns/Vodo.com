<?php

declare(strict_types=1);

namespace App\Services\View\Types;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Wizard View Type - Multi-step guided forms.
 *
 * Features:
 * - Step-by-step navigation
 * - Progress indicator
 * - Step validation
 * - Conditional steps
 * - Step dependencies
 */
class WizardViewType extends AbstractViewType
{
    protected string $name = 'wizard';
    protected string $label = 'Wizard View';
    protected string $description = 'Multi-step guided form for complex data entry workflows';
    protected string $icon = 'list-ordered';
    protected string $category = 'workflow';
    protected int $priority = 9;

    protected array $supportedFeatures = [
        'step_validation',
        'progress_indicator',
        'conditional_steps',
        'step_dependencies',
        'save_draft',
        'resume',
    ];

    protected array $defaultConfig = [
        'allow_back' => true,
        'allow_skip' => false,
        'show_progress' => true,
        'cancelable' => true,
        'save_draft' => false,
        'confirm_cancel' => true,
    ];

    protected array $extensionPoints = [
        'before_wizard' => 'Content before the wizard',
        'after_wizard' => 'Content after the wizard',
        'step_header' => 'Custom step header',
        'step_footer' => 'Custom step footer',
        'progress_bar' => 'Custom progress bar',
    ];

    protected array $availableActions = [
        'navigation' => [
            'next' => ['label' => 'Next', 'icon' => 'arrow-right'],
            'previous' => ['label' => 'Previous', 'icon' => 'arrow-left'],
            'skip' => ['label' => 'Skip', 'icon' => 'skip-forward'],
            'cancel' => ['label' => 'Cancel', 'icon' => 'x'],
            'finish' => ['label' => 'Finish', 'icon' => 'check'],
        ],
    ];

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'required' => ['type', 'steps'],
            'properties' => [
                'type' => ['type' => 'string', 'const' => 'wizard'],
                'name' => ['type' => 'string'],
                'steps' => [
                    'type' => 'object',
                    'additionalProperties' => [
                        'type' => 'object',
                        'required' => ['label'],
                        'properties' => [
                            'label' => ['type' => 'string'],
                            'description' => ['type' => 'string'],
                            'icon' => ['type' => 'string'],
                            'fields' => ['type' => 'object'],
                            'component' => ['type' => 'string'],
                            'validation' => ['type' => 'string'],
                            'depends_on' => ['type' => 'string'],
                            'condition' => ['type' => 'string'],
                            'action' => ['type' => 'string'],
                        ],
                    ],
                ],
                'config' => ['type' => 'object'],
            ],
        ];
    }

    protected function validateDefinition(array $definition): void
    {
        if (empty($definition['steps'])) {
            $this->addError('steps', 'Wizard requires at least one step');
            return;
        }

        if (count($definition['steps']) < 2) {
            $this->addError('steps', 'Wizard should have at least 2 steps');
        }

        foreach ($definition['steps'] as $key => $step) {
            if (empty($step['label'])) {
                $this->addError("steps.{$key}.label", 'Step label is required');
            }
            if (empty($step['fields']) && empty($step['component'])) {
                $this->addError("steps.{$key}", 'Step must have fields or a component');
            }
        }
    }

    public function generateDefault(string $entityName, Collection $fields): array
    {
        $formFields = $this->filterFields($fields, 'form');
        $fieldCount = $formFields->count();
        $fieldsPerStep = max(3, ceil($fieldCount / 3));

        $steps = [];
        $currentStep = 1;
        $currentFields = [];

        foreach ($formFields as $field) {
            $slug = is_array($field) ? $field['slug'] : $field->slug;
            $currentFields[$slug] = [
                'widget' => $this->getWidgetForField($field),
            ];

            if (count($currentFields) >= $fieldsPerStep) {
                $steps["step_{$currentStep}"] = [
                    'label' => "Step {$currentStep}",
                    'fields' => $currentFields,
                ];
                $currentFields = [];
                $currentStep++;
            }
        }

        // Add remaining fields
        if (!empty($currentFields)) {
            $steps["step_{$currentStep}"] = [
                'label' => "Step {$currentStep}",
                'fields' => $currentFields,
            ];
        }

        return [
            'type' => 'wizard',
            'name' => Str::title(str_replace('_', ' ', $entityName)) . ' Wizard',
            'steps' => $steps,
            'config' => $this->getDefaultConfig(),
        ];
    }
}
