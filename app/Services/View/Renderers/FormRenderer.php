<?php

declare(strict_types=1);

namespace App\Services\View\Renderers;

use Illuminate\Support\Str;

/**
 * Form Renderer - Renders entity form views from view definitions.
 *
 * Converts form view definitions registered via ViewRegistry into HTML.
 * Supports sections, multiple columns, field widgets, and validation.
 *
 * @example Usage
 * ```php
 * $renderer = new FormRenderer();
 * $html = $renderer->render($formDefinition, [
 *     'record' => $record,
 *     'mode' => 'edit',
 * ], [
 *     'user' => auth()->user(),
 * ]);
 * ```
 */
class FormRenderer extends AbstractRenderer
{
    /**
     * {@inheritdoc}
     */
    public function getViewType(): string
    {
        return 'form';
    }

    /**
     * {@inheritdoc}
     */
    public function getViewPath(): string
    {
        return 'backend.entity.form';
    }

    /**
     * {@inheritdoc}
     */
    public function render(array $definition, array $data = [], array $context = []): string
    {
        $prepared = $this->prepareData($definition, $data);
        
        return view($this->getViewPath(), $prepared)->render();
    }

    /**
     * {@inheritdoc}
     */
    public function prepareData(array $definition, array $data): array
    {
        $entityName = $definition['entity'] ?? '';
        $entity = $this->getEntity($entityName);
        $record = $data['record'] ?? null;
        $mode = $data['mode'] ?? 'create';

        // Prepare sections from definition
        $sections = $this->prepareSections($definition, $record);

        // Determine submit URL and method
        $submitUrl = $data['submitUrl'] ?? $this->getSubmitUrl($entityName, $record, $mode);
        $submitMethod = $data['submitMethod'] ?? ($mode === 'edit' ? 'PUT' : 'POST');

        // Get available actions
        $actions = $this->prepareActions($definition, $mode);

        return [
            'viewType' => 'form',
            'viewDefinition' => $definition,
            'entity' => $entity,
            'entityName' => $entityName,
            'record' => $record,
            'mode' => $mode,
            'sections' => $sections,
            'actions' => $actions,
            'submitUrl' => $submitUrl,
            'submitMethod' => $submitMethod,
            'cancelUrl' => $data['cancelUrl'] ?? $this->getCancelUrl($entityName),
            'backUrl' => $data['backUrl'] ?? $this->getCancelUrl($entityName),
            'deleteUrl' => $data['deleteUrl'] ?? ($record ? $this->getDeleteUrl($entityName, $record) : null),
            'pageTitle' => $data['pageTitle'] ?? $this->getPageTitle($entity, $mode),
            'config' => $definition['config'] ?? [],
        ];
    }

    /**
     * Prepare sections from form view definition.
     */
    protected function prepareSections(array $definition, $record): array
    {
        // Handle both 'sections' and 'groups' keys (ViewRegistry uses 'groups')
        $rawSections = $definition['sections'] ?? $definition['groups'] ?? [];

        if (empty($rawSections)) {
            // Fall back to flat fields
            $fields = $definition['fields'] ?? [];
            if (!empty($fields)) {
                $rawSections = ['main' => ['label' => null, 'columns' => 2, 'fields' => $fields]];
            } else {
                // Generate from entity fields
                $entityName = $definition['entity'] ?? '';
                return $this->generateDefaultSections($entityName, $record);
            }
        }

        $sections = [];
        foreach ($rawSections as $sectionKey => $section) {
            $sections[$sectionKey] = $this->prepareSection($sectionKey, $section, $record);
        }

        return $sections;
    }

    /**
     * Prepare a single section.
     */
    protected function prepareSection(string $sectionKey, array $section, $record): array
    {
        $normalizedSection = [
            'key' => $sectionKey,
            'label' => $section['label'] ?? ($sectionKey !== 'main' ? $this->fieldToLabel($sectionKey) : null),
            'columns' => $section['columns'] ?? 2,
            'collapsible' => $section['collapsible'] ?? false,
            'collapsed' => $section['collapsed'] ?? false,
            'condition' => $section['condition'] ?? null,
            'fields' => [],
        ];

        $fields = $section['fields'] ?? [];
        foreach ($fields as $fieldName => $fieldConfig) {
            // Handle array of field names vs associative field configs
            if (is_int($fieldName) && is_string($fieldConfig)) {
                $fieldName = $fieldConfig;
                $fieldConfig = [];
            }

            $normalized = $this->normalizeField($fieldName, $fieldConfig);
            $normalized['value'] = $record ? $this->getFieldValue($record, $fieldName) : null;
            
            $normalizedSection['fields'][$fieldName] = $normalized;
        }

        return $normalizedSection;
    }

    /**
     * Generate default sections from entity fields.
     */
    protected function generateDefaultSections(string $entityName, $record): array
    {
        if (empty($entityName)) {
            return [];
        }

        $fields = $this->getEntityFields($entityName);
        
        $mainFields = [];
        foreach ($fields as $field) {
            if (!$field->show_in_form) {
                continue;
            }

            $mainFields[$field->slug] = [
                'name' => $field->slug,
                'widget' => $this->getWidgetForFieldType($field->type),
                'label' => $field->name,
                'required' => $field->is_required,
                'readonly' => $field->is_system,
                'span' => $field->form_width === 'full' ? 2 : 1,
                'help' => $field->description,
                'placeholder' => null,
                'options' => $field->config['options'] ?? [],
                'config' => $field->config ?? [],
                'value' => $record ? $this->getFieldValue($record, $field->slug) : null,
            ];
        }

        return [
            'main' => [
                'key' => 'main',
                'label' => null,
                'columns' => 2,
                'collapsible' => false,
                'collapsed' => false,
                'fields' => $mainFields,
            ],
        ];
    }

    /**
     * Get field value from record.
     */
    protected function getFieldValue($record, string $fieldName): mixed
    {
        if (!$record) {
            return null;
        }

        // Check direct property
        if (isset($record->{$fieldName})) {
            return $record->{$fieldName};
        }

        // Check meta fields
        if (isset($record->meta[$fieldName])) {
            return $record->meta[$fieldName];
        }

        // Check fields JSON column (for EntityRecord)
        if (isset($record->fields[$fieldName])) {
            return $record->fields[$fieldName];
        }

        return null;
    }

    /**
     * Prepare form actions (buttons).
     */
    protected function prepareActions(array $definition, string $mode): array
    {
        $actions = $definition['actions'] ?? ['save', 'cancel'];

        // Handle header actions separately
        if (isset($definition['actions']['header'])) {
            $actions = $definition['actions']['header'];
        }

        // Add delete action for edit mode
        if ($mode === 'edit' && !in_array('delete', $actions)) {
            $actions[] = 'delete';
        }

        $prepared = [];
        foreach ($actions as $action) {
            if (is_string($action)) {
                $prepared[] = $this->prepareAction($action);
            } else {
                $prepared[] = array_merge($this->prepareAction($action['name'] ?? ''), $action);
            }
        }

        return $prepared;
    }

    /**
     * Prepare a single action button configuration.
     */
    protected function prepareAction(string $actionName): array
    {
        return [
            'name' => $actionName,
            'label' => $this->getActionLabel($actionName),
            'icon' => $this->getActionIcon($actionName),
            'class' => $this->getActionClass($actionName),
            'type' => $this->getActionType($actionName),
            'confirm' => in_array($actionName, ['delete', 'destroy', 'remove']),
        ];
    }

    /**
     * Get action label.
     */
    protected function getActionLabel(string $action): string
    {
        return match ($action) {
            'save' => 'Save',
            'save_and_new' => 'Save & New',
            'save_and_close' => 'Save & Close',
            'cancel' => 'Cancel',
            'delete' => 'Delete',
            'duplicate' => 'Duplicate',
            default => $this->fieldToLabel($action),
        };
    }

    /**
     * Get action type (submit, link, action).
     */
    protected function getActionType(string $action): string
    {
        return match ($action) {
            'save', 'save_and_new', 'save_and_close' => 'submit',
            'cancel' => 'link',
            'delete' => 'delete',
            default => 'action',
        };
    }

    /**
     * Get submit URL for form.
     */
    protected function getSubmitUrl(string $entityName, $record, string $mode): string
    {
        if ($mode === 'edit' && $record) {
            return url("/api/v1/entities/{$entityName}/{$record->id}");
        }

        return url("/api/v1/entities/{$entityName}");
    }

    /**
     * Get cancel URL.
     */
    protected function getCancelUrl(string $entityName): string
    {
        return route('admin.entities.index', $entityName);
    }

    /**
     * Get delete URL.
     */
    protected function getDeleteUrl(string $entityName, $record): string
    {
        return url("/api/v1/entities/{$entityName}/{$record->id}");
    }

    /**
     * Get page title.
     */
    protected function getPageTitle($entity, string $mode): string
    {
        $label = $entity ? $entity->getSingularLabel() : 'Record';
        
        return match ($mode) {
            'create' => "Create {$label}",
            'edit' => "Edit {$label}",
            'view' => $label,
            default => $label,
        };
    }
}

