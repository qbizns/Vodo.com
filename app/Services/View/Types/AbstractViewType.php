<?php

declare(strict_types=1);

namespace App\Services\View\Types;

use App\Contracts\ViewTypeContract;
use Illuminate\Support\Collection;
use Illuminate\Support\Str;

/**
 * Abstract base class for all view types.
 *
 * Provides common functionality and sensible defaults that
 * can be overridden by specific view type implementations.
 */
abstract class AbstractViewType implements ViewTypeContract
{
    /**
     * View type name (e.g., 'list', 'form').
     */
    protected string $name;

    /**
     * Human-readable label.
     */
    protected string $label;

    /**
     * Description of this view type.
     */
    protected string $description = '';

    /**
     * Icon name.
     */
    protected string $icon = 'layout';

    /**
     * Category: data, board, analytics, workflow, special.
     */
    protected string $category = 'data';

    /**
     * Whether this view type requires an entity.
     */
    protected bool $requiresEntity = true;

    /**
     * Whether this is a system (non-removable) type.
     */
    protected bool $isSystem = true;

    /**
     * Priority for ordering.
     */
    protected int $priority = 10;

    /**
     * Supported features.
     */
    protected array $supportedFeatures = [];

    /**
     * Default configuration.
     */
    protected array $defaultConfig = [];

    /**
     * Extension points (slots).
     */
    protected array $extensionPoints = [];

    /**
     * Available actions.
     */
    protected array $availableActions = [];

    /**
     * Required widgets.
     */
    protected array $requiredWidgets = [];

    /**
     * Cached validation errors.
     */
    protected array $validationErrors = [];

    public function getName(): string
    {
        return $this->name;
    }

    public function getLabel(): string
    {
        return $this->label ?? Str::title(str_replace(['_', '-'], ' ', $this->name)) . ' View';
    }

    public function getDescription(): string
    {
        return $this->description;
    }

    public function getIcon(): string
    {
        return $this->icon;
    }

    public function getCategory(): string
    {
        return $this->category;
    }

    public function getDefaultConfig(): array
    {
        return $this->defaultConfig;
    }

    public function getTemplatePath(): string
    {
        return "platform.views.{$this->name}";
    }

    public function getRequiredWidgets(): array
    {
        return $this->requiredWidgets;
    }

    public function supports(string $feature): bool
    {
        return in_array($feature, $this->supportedFeatures, true);
    }

    public function getSupportedFeatures(): array
    {
        return $this->supportedFeatures;
    }

    public function getAvailableActions(): array
    {
        return $this->availableActions;
    }

    public function getExtensionPoints(): array
    {
        return $this->extensionPoints;
    }

    public function requiresEntity(): bool
    {
        return $this->requiresEntity;
    }

    public function isSystem(): bool
    {
        return $this->isSystem;
    }

    public function getPriority(): int
    {
        return $this->priority;
    }

    /**
     * Get the JSON schema for validation.
     * Override in subclasses for type-specific schemas.
     */
    public function getSchema(): array
    {
        return [
            'type' => 'object',
            'required' => ['type'],
            'properties' => [
                'type' => [
                    'type' => 'string',
                    'const' => $this->name,
                ],
                'entity' => [
                    'type' => 'string',
                    'description' => 'Entity this view is for',
                ],
                'name' => [
                    'type' => 'string',
                    'description' => 'Human-readable view name',
                ],
                'config' => [
                    'type' => 'object',
                    'description' => 'View-specific configuration',
                ],
            ],
        ];
    }

    /**
     * Validate a view definition.
     */
    public function validate(array $definition): array
    {
        $this->validationErrors = [];

        // Check required type
        if (!isset($definition['type'])) {
            $this->addError('type', 'View type is required');
        } elseif ($definition['type'] !== $this->name) {
            $this->addError('type', "View type must be '{$this->name}'");
        }

        // Check entity requirement
        if ($this->requiresEntity && empty($definition['entity'])) {
            $this->addError('entity', 'Entity is required for this view type');
        }

        // Run type-specific validation
        $this->validateDefinition($definition);

        return $this->validationErrors;
    }

    /**
     * Type-specific validation. Override in subclasses.
     */
    protected function validateDefinition(array $definition): void
    {
        // Override in subclasses
    }

    /**
     * Add a validation error.
     */
    protected function addError(string $field, string $message): void
    {
        $this->validationErrors[$field] = $message;
    }

    /**
     * Generate a default view for an entity.
     */
    public function generateDefault(string $entityName, Collection $fields): array
    {
        return [
            'type' => $this->name,
            'entity' => $entityName,
            'name' => Str::title(str_replace('_', ' ', $entityName)) . ' ' . $this->getLabel(),
            'config' => $this->getDefaultConfig(),
        ];
    }

    /**
     * Prepare data for rendering.
     */
    public function prepareData(array $definition, array $data): array
    {
        return array_merge([
            'definition' => $definition,
            'viewType' => $this->name,
            'config' => array_merge($this->getDefaultConfig(), $definition['config'] ?? []),
        ], $data);
    }

    /**
     * Get field configuration with defaults.
     */
    protected function getFieldConfig(array $field, array $defaults = []): array
    {
        return array_merge([
            'widget' => 'char',
            'label' => $field['name'] ?? $field['slug'] ?? 'Field',
            'required' => false,
            'readonly' => false,
            'visible' => true,
            'sortable' => false,
            'filterable' => false,
            'searchable' => false,
        ], $defaults, $field);
    }

    /**
     * Filter fields based on visibility criteria.
     */
    protected function filterFields(Collection $fields, string $context = 'list'): Collection
    {
        $showKey = match ($context) {
            'list' => 'show_in_list',
            'form' => 'show_in_form',
            default => 'show_in_list',
        };

        return $fields->filter(fn($field) => $field[$showKey] ?? $field->$showKey ?? true);
    }

    /**
     * Get widget for a field based on its type.
     */
    protected function getWidgetForField(array|object $field): string
    {
        $type = is_array($field) ? ($field['type'] ?? 'string') : ($field->type ?? 'string');

        return match ($type) {
            'string' => 'char',
            'text' => 'text',
            'html' => 'html',
            'integer' => 'integer',
            'decimal', 'float' => 'float',
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
            default => 'char',
        };
    }

    /**
     * Convert to array representation.
     */
    public function toArray(): array
    {
        return [
            'name' => $this->getName(),
            'label' => $this->getLabel(),
            'description' => $this->getDescription(),
            'icon' => $this->getIcon(),
            'category' => $this->getCategory(),
            'requires_entity' => $this->requiresEntity(),
            'is_system' => $this->isSystem(),
            'priority' => $this->getPriority(),
            'supported_features' => $this->getSupportedFeatures(),
            'extension_points' => $this->getExtensionPoints(),
            'available_actions' => $this->getAvailableActions(),
            'default_config' => $this->getDefaultConfig(),
        ];
    }
}
