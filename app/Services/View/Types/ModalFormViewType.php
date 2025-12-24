<?php

declare(strict_types=1);

namespace App\Services\View\Types;

use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Modal Form View Type - Quick-add modal dialog.
 *
 * Features:
 * - Lightweight form in modal
 * - Quick record creation
 * - Minimal fields
 * - Fast submit
 */
class ModalFormViewType extends AbstractViewType
{
    protected string $name = 'modal-form';
    protected string $label = 'Modal Form View';
    protected string $description = 'Quick-add modal dialog for inline record creation';
    protected string $icon = 'plus-square';
    protected string $category = 'special';
    protected int $priority = 17;

    protected array $supportedFeatures = [
        'quick_create',
        'validation',
        'auto_close',
        'refresh_parent',
    ];

    protected array $defaultConfig = [
        'size' => 'md', // sm, md, lg, xl
        'close_on_save' => true,
        'close_on_escape' => true,
        'close_on_overlay' => true,
        'refresh_on_save' => true,
        'show_close_button' => true,
    ];

    protected array $extensionPoints = [
        'modal_header' => 'Custom modal header',
        'modal_footer' => 'Custom modal footer',
        'before_fields' => 'Content before form fields',
        'after_fields' => 'Content after form fields',
    ];

    protected array $availableActions = [
        'save' => ['label' => 'Save', 'icon' => 'check', 'primary' => true],
        'save_and_new' => ['label' => 'Save & New', 'icon' => 'plus'],
        'cancel' => ['label' => 'Cancel', 'icon' => 'x'],
    ];

    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'required' => ['type', 'fields'],
            'properties' => [
                'type' => ['type' => 'string', 'const' => 'modal-form'],
                'entity' => ['type' => 'string'],
                'name' => ['type' => 'string'],
                'title' => ['type' => 'string'],
                'size' => ['type' => 'string', 'enum' => ['sm', 'md', 'lg', 'xl']],
                'fields' => [
                    'type' => 'object',
                    'additionalProperties' => [
                        'type' => 'object',
                        'properties' => [
                            'widget' => ['type' => 'string'],
                            'label' => ['type' => 'string'],
                            'required' => ['type' => 'boolean'],
                            'placeholder' => ['type' => 'string'],
                        ],
                    ],
                ],
                'config' => ['type' => 'object'],
            ],
        ];
    }

    protected function validateDefinition(array $definition): void
    {
        if (empty($definition['fields'])) {
            $this->addError('fields', 'Modal form requires at least one field');
        }

        // Warn if too many fields
        if (!empty($definition['fields']) && count($definition['fields']) > 6) {
            // Not an error, just a warning logged
        }
    }

    public function generateDefault(string $entityName, Collection $fields): array
    {
        // Get only required and commonly-used fields
        $formFields = $this->filterFields($fields, 'form')
            ->filter(fn($f) =>
                (is_array($f) ? ($f['is_required'] ?? false) : ($f->is_required ?? false)) ||
                in_array(is_array($f) ? $f['slug'] : $f->slug, ['name', 'title', 'email', 'status'])
            )
            ->take(5);

        $modalFields = [];
        foreach ($formFields as $field) {
            $slug = is_array($field) ? $field['slug'] : $field->slug;
            $name = is_array($field) ? ($field['name'] ?? $slug) : ($field->name ?? $slug);
            $required = is_array($field) ? ($field['is_required'] ?? false) : ($field->is_required ?? false);

            $modalFields[$slug] = [
                'widget' => $this->getWidgetForField($field),
                'label' => $name,
                'required' => $required,
            ];
        }

        return [
            'type' => 'modal-form',
            'entity' => $entityName,
            'name' => 'Quick Add ' . Str::title(str_replace('_', ' ', $entityName)),
            'title' => 'Add ' . Str::title(str_replace('_', ' ', $entityName)),
            'fields' => $modalFields,
            'config' => $this->getDefaultConfig(),
        ];
    }
}
