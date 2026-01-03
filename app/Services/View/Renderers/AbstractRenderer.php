<?php

declare(strict_types=1);

namespace App\Services\View\Renderers;

use App\Contracts\ViewRendererContract;
use App\Models\EntityDefinition;
use App\Models\EntityField;
use Illuminate\Support\Str;

/**
 * Abstract View Renderer - Base class for all view renderers.
 *
 * Provides common functionality for converting view definitions into HTML.
 * Subclasses implement specific rendering logic for different view types.
 */
abstract class AbstractRenderer implements ViewRendererContract
{
    /**
     * Default widgets per field type.
     */
    protected array $defaultWidgets = [
        'string' => 'char',
        'text' => 'text',
        'html' => 'html',
        'integer' => 'integer',
        'decimal' => 'float',
        'float' => 'float',
        'money' => 'monetary',
        'boolean' => 'checkbox',
        'date' => 'date',
        'datetime' => 'datetime',
        'time' => 'time',
        'email' => 'email',
        'url' => 'url',
        'phone' => 'phone',
        'select' => 'selection',
        'relation' => 'many2one',
        'file' => 'binary',
        'image' => 'image',
        'json' => 'json',
        'color' => 'color',
        'slug' => 'slug',
    ];

    /**
     * Widget to blade partial mapping.
     */
    protected array $widgetPartials = [
        'char' => 'backend.entity.widgets.char',
        'text' => 'backend.entity.widgets.text',
        'html' => 'backend.entity.widgets.html',
        'integer' => 'backend.entity.widgets.integer',
        'float' => 'backend.entity.widgets.float',
        'monetary' => 'backend.entity.widgets.monetary',
        'checkbox' => 'backend.entity.widgets.checkbox',
        'date' => 'backend.entity.widgets.date',
        'datetime' => 'backend.entity.widgets.datetime',
        'time' => 'backend.entity.widgets.time',
        'email' => 'backend.entity.widgets.email',
        'url' => 'backend.entity.widgets.url',
        'phone' => 'backend.entity.widgets.phone',
        'selection' => 'backend.entity.widgets.selection',
        'many2one' => 'backend.entity.widgets.many2one',
        'one2many' => 'backend.entity.widgets.one2many',
        'many2many' => 'backend.entity.widgets.many2many',
        'binary' => 'backend.entity.widgets.binary',
        'image' => 'backend.entity.widgets.image',
        'json' => 'backend.entity.widgets.json',
        'color' => 'backend.entity.widgets.color',
        'slug' => 'backend.entity.widgets.slug',
        'badge' => 'backend.entity.widgets.badge',
        'statusbar' => 'backend.entity.widgets.statusbar',
        'progressbar' => 'backend.entity.widgets.progressbar',
        'tags' => 'backend.entity.widgets.tags',
        'priority' => 'backend.entity.widgets.priority',
    ];

    /**
     * {@inheritdoc}
     */
    abstract public function render(array $definition, array $data = [], array $context = []): string;

    /**
     * {@inheritdoc}
     */
    abstract public function getViewType(): string;

    /**
     * {@inheritdoc}
     */
    public function canRender(array $definition): bool
    {
        return ($definition['type'] ?? null) === $this->getViewType();
    }

    /**
     * {@inheritdoc}
     */
    abstract public function getViewPath(): string;

    /**
     * {@inheritdoc}
     */
    public function prepareData(array $definition, array $data): array
    {
        return array_merge([
            'viewType' => $this->getViewType(),
            'viewDefinition' => $definition,
        ], $data);
    }

    /**
     * Get widget name for a field type.
     */
    protected function getWidgetForFieldType(string $type): string
    {
        return $this->defaultWidgets[$type] ?? 'char';
    }

    /**
     * Get blade partial path for a widget.
     */
    protected function getWidgetPartial(string $widget): string
    {
        return $this->widgetPartials[$widget] ?? 'backend.entity.widgets.char';
    }

    /**
     * Normalize field name to label.
     */
    protected function fieldToLabel(string $fieldName): string
    {
        return Str::title(str_replace(['_', '-'], ' ', $fieldName));
    }

    /**
     * Normalize a field configuration.
     */
    protected function normalizeField(string $fieldName, array|string $fieldConfig): array
    {
        if (is_string($fieldConfig)) {
            $fieldConfig = ['widget' => $fieldConfig];
        }

        return array_merge([
            'name' => $fieldName,
            'widget' => 'char',
            'label' => $this->fieldToLabel($fieldName),
            'required' => false,
            'readonly' => false,
            'span' => 1,
            'help' => null,
            'placeholder' => null,
            'options' => [],
            'config' => [],
            'value' => null,
        ], $fieldConfig);
    }

    /**
     * Get entity definition by name.
     */
    protected function getEntity(string $entityName): ?EntityDefinition
    {
        return EntityDefinition::where('name', $entityName)
            ->orWhere('slug', $entityName)
            ->active()
            ->first();
    }

    /**
     * Get entity fields.
     */
    protected function getEntityFields(string $entityName): \Illuminate\Support\Collection
    {
        return EntityField::where('entity_name', $entityName)
            ->orderBy('form_order')
            ->get();
    }

    /**
     * Build default view definition from entity fields.
     */
    protected function buildDefaultDefinition(string $entityName): array
    {
        $entity = $this->getEntity($entityName);
        $fields = $this->getEntityFields($entityName);

        return [
            'type' => $this->getViewType(),
            'entity' => $entityName,
            'name' => $entity ? $entity->getSingularLabel() : $entityName,
            'fields' => $fields->mapWithKeys(function ($field) {
                return [
                    $field->slug => [
                        'widget' => $this->getWidgetForFieldType($field->type),
                        'label' => $field->name,
                        'required' => $field->is_required,
                        'readonly' => $field->is_system,
                        'help' => $field->description,
                        'options' => $field->config['options'] ?? [],
                        'config' => $field->config ?? [],
                    ],
                ];
            })->toArray(),
        ];
    }

    /**
     * Check if user has permission for an action.
     */
    protected function hasPermission(array $context, string $action): bool
    {
        $user = $context['user'] ?? null;
        if (!$user) {
            return false;
        }

        if (method_exists($user, 'isSuperAdmin') && $user->isSuperAdmin()) {
            return true;
        }

        $permission = $context['permission_prefix'] ?? '';
        if ($permission && method_exists($user, 'hasPermission')) {
            return $user->hasPermission("{$permission}.{$action}");
        }

        return true;
    }

    /**
     * Get actions available for a view.
     */
    protected function getAvailableActions(array $definition, array $context): array
    {
        $actions = $definition['actions'] ?? [];
        $available = [];

        foreach ($actions as $action) {
            if (is_string($action)) {
                $action = ['name' => $action, 'label' => $this->fieldToLabel($action)];
            }

            $actionName = $action['name'] ?? '';
            
            // Check permission
            if ($this->hasPermission($context, $actionName)) {
                $available[] = array_merge([
                    'name' => $actionName,
                    'label' => $this->fieldToLabel($actionName),
                    'icon' => $this->getActionIcon($actionName),
                    'class' => $this->getActionClass($actionName),
                    'confirm' => in_array($actionName, ['delete', 'destroy', 'remove']),
                ], $action);
            }
        }

        return $available;
    }

    /**
     * Get icon for an action.
     */
    protected function getActionIcon(string $action): string
    {
        return match ($action) {
            'create', 'add', 'new' => 'plus',
            'edit', 'update' => 'edit',
            'delete', 'destroy', 'remove' => 'trash',
            'view', 'show', 'detail' => 'eye',
            'save' => 'save',
            'cancel' => 'x',
            'export' => 'download',
            'import' => 'upload',
            'duplicate', 'copy' => 'copy',
            'print' => 'printer',
            'email', 'send' => 'mail',
            'archive' => 'archive',
            'restore' => 'refresh-cw',
            default => 'circle',
        };
    }

    /**
     * Get CSS class for an action button.
     */
    protected function getActionClass(string $action): string
    {
        return match ($action) {
            'create', 'add', 'new', 'save' => 'btn-primary',
            'delete', 'destroy', 'remove' => 'btn-danger',
            'cancel' => 'btn-secondary',
            default => 'btn-secondary',
        };
    }
}

